<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PaymentGatewayService
{
    protected $secretKey;
    protected $apiUrl;
    protected $callbackUrl;
    protected $timeout;

    public function __construct()
    {
        $this->secretKey = config('payment.plisio.secret_key');
        $this->apiUrl = config('payment.plisio.api_url');
        $this->callbackUrl = config('payment.plisio.callback_url');
        $this->timeout = config('payment.plisio.timeout');
        
        if (empty($this->secretKey)) {
            throw new Exception('Plisio secret key not configured');
        }
    }

    /**
     * Generate payment invoice for Plisio gateway
     *
     * @param string $currency Cryptocurrency code (BTC, ETH, USDT, etc.)
     * @param float $amount Amount in USD
     * @param string $orderId Unique order identifier
     * @param array $package Package/product information
     * @param array $userInfo User information
     * @return array
     * @throws Exception
     */
    public function generatePlisioPayment(string $currency, float $amount, string $orderId, array $package, array $userInfo): array
    {
        $this->validatePaymentParams($currency, $amount, $orderId, $package, $userInfo);

        try {
            $params = [
                'source_currency' => 'USD',
                'source_amount' => $amount,
                'currency' => $this->mapCurrencyToPlisio($currency),
                'order_number' => $orderId,
                'order_name' => $package['name'] ?? "Deposit - {$orderId}",
                'email' => $userInfo['email'] ?? '',
                'api_key' => $this->secretKey,
            ];

            if ($this->callbackUrl) {
                $params['callback_url'] = $this->callbackUrl . '?json=true';
            }

            Log::info('Making Plisio invoice request', [
                'url' => "{$this->apiUrl}/invoices/new",
                'params' => array_merge($params, ['api_key' => '[REDACTED]'])
            ]);

            $response = Http::timeout($this->timeout)
                ->get("{$this->apiUrl}/invoices/new", $params);

            Log::info('Plisio API Response', [
                'status_code' => $response->status(),
                'body' => $response->body()
            ]);

            if (!$response->successful()) {
                throw new RequestException($response);
            }

            $data = $response->json();

            if (!isset($data['status']) || $data['status'] !== 'success') {
                $errorMessage = $data['data']['message'] ?? $data['message'] ?? 'Unknown error from Plisio';
                throw new Exception("Plisio payment failed: {$errorMessage}");
            }

            return $this->formatPaymentResponse($data['data'], $amount);

        } catch (RequestException $e) {
            Log::error('Plisio API request failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'response' => $e->response?->body()
            ]);
            throw new Exception('Payment gateway is currently unavailable. Please try again later.');

        } catch (Exception $e) {
            Log::error('Plisio payment generation failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process withdrawal through Plisio gateway
     *
     * @param string $address Recipient wallet address
     * @param float $amount Amount in crypto
     * @param string $orderId Unique order identifier
     * @param string $currency Cryptocurrency code
     * @param array $userInfo User information
     * @return array
     * @throws Exception
     */
    public function processWithdrawal(string $address, float $amount, string $orderId, string $currency, array $userInfo): array
    {
        $this->validateWithdrawalParams($address, $amount, $orderId, $currency);

        try {
            $formattedAmount = number_format($amount, 8, '.', '');
            $plisioCurrency = $this->mapCurrencyToPlisio($currency);

            $params = [
                'currency' => $plisioCurrency,
                'to' => $address,
                'amount' => $formattedAmount,
                'type' => 'cash_out',
                'api_key' => $this->secretKey,
            ];

            Log::info('Processing Plisio withdrawal request', [
                'order_id' => $orderId,
                'currency' => $plisioCurrency,
                'amount' => $formattedAmount,
                'to_address' => $address,
                'user_id' => $userInfo['user_id'] ?? 'unknown'
            ]);

            $response = Http::timeout($this->timeout)
                ->get("{$this->apiUrl}/operations/withdraw", $params);

            Log::info('Plisio Withdrawal API Response', [
                'status_code' => $response->status(),
                'body' => $response->body()
            ]);

            if (!$response->successful()) {
                throw new RequestException($response);
            }

            $data = $response->json();

            if (!isset($data['status']) || $data['status'] !== 'success') {
                $errorMessage = $data['data']['message'] ?? $data['message'] ?? 'Withdrawal failed';
                throw new Exception("Plisio withdrawal failed: {$errorMessage}");
            }

            return $this->formatWithdrawalResponse($data['data'], $amount);

        } catch (RequestException $e) {
            Log::error('Plisio withdrawal API request failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'response' => $e->response?->body()
            ]);
            throw new Exception('Withdrawal gateway is currently unavailable. Please try again later.');

        } catch (Exception $e) {
            Log::error('Plisio withdrawal processing failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get cryptocurrency balance from Plisio
     *
     * @param string $currency Cryptocurrency code
     * @return array
     */
    public function getBalance(string $currency): array
    {
        try {
            $plisioCurrency = $this->mapCurrencyToPlisio($currency);

            $response = Http::timeout($this->timeout)
                ->get("{$this->apiUrl}/balances/{$plisioCurrency}", [
                    'api_key' => $this->secretKey
                ]);

            if (!$response->successful()) {
                throw new Exception('Failed to get balance');
            }

            $data = $response->json();

            if ($data['status'] === 'success') {
                return [
                    'balance' => $data['data']['balance'] ?? 0,
                    'currency' => $currency
                ];
            }

            throw new Exception($data['data']['message'] ?? 'Balance fetch failed');

        } catch (Exception $e) {
            Log::error('Plisio balance fetch failed', [
                'currency' => $currency,
                'error' => $e->getMessage()
            ]);
            return ['balance' => 0, 'currency' => $currency, 'error' => $e->getMessage()];
        }
    }

    /**
     * Map internal currency codes to Plisio currency codes
     */
    protected function mapCurrencyToPlisio(string $currency): string
    {
        $mapping = [
            'USDT_TRC20' => 'USDT_TRX',
            'USDT_ERC20' => 'USDT',
            'USDT_BEP20' => 'USDT_BSC',
            'BTC' => 'BTC',
            'ETH' => 'ETH',
            'LTC' => 'LTC',
            'DOGE' => 'DOGE',
            'TRX' => 'TRX',
            'BNB' => 'BNB',
            'XRP' => 'XRP',
            'SOL' => 'SOL',
            'MATIC' => 'MATIC',
            'ADA' => 'ADA',
            'DOT' => 'DOT',
            'AVAX' => 'AVAX',
        ];

        return $mapping[$currency] ?? $currency;
    }

    /**
     * Validate payment parameters
     */
    protected function validatePaymentParams(string $currency, float $amount, string $orderId, array $package, array $userInfo): void
    {
        if (empty($currency)) {
            throw new Exception('Currency is required');
        }

        if ($amount <= 0) {
            throw new Exception('Amount must be greater than zero');
        }

        if (empty($orderId)) {
            throw new Exception('Order ID is required');
        }

        if (empty($package['name'])) {
            throw new Exception('Package name is required');
        }

        if (empty($userInfo['username'])) {
            throw new Exception('Username is required');
        }
    }

    /**
     * Validate withdrawal parameters
     */
    protected function validateWithdrawalParams(string $address, float $amount, string $orderId, string $currency): void
    {
        if (empty($address)) {
            throw new Exception('Withdrawal address is required');
        }

        if ($amount <= 0) {
            throw new Exception('Withdrawal amount must be greater than zero');
        }

        if (empty($orderId)) {
            throw new Exception('Order ID is required');
        }

        if (empty($currency)) {
            throw new Exception('Currency is required');
        }
    }

    /**
     * Format payment response
     */
    protected function formatPaymentResponse(array $apiResponse, float $amount): array
    {
        $response = [
            'amount' => $amount,
            'txn_id' => $apiResponse['txn_id'] ?? null,
        ];

        if (!empty($apiResponse['wallet_hash'])) {
            $response['address'] = $apiResponse['wallet_hash'];
            $response['type'] = 'address';
            $response['crypto_amount'] = $apiResponse['amount'] ?? $apiResponse['pending_amount'] ?? null;
            $response['qr_code'] = $apiResponse['qr_code'] ?? null;
            $response['expire_utc'] = $apiResponse['expire_utc'] ?? null;
        }

        if (!empty($apiResponse['invoice_url'])) {
            $response['invoice_url'] = $apiResponse['invoice_url'];
            $response['type'] = $response['type'] ?? 'redirect';
        }

        return $response;
    }

    /**
     * Format withdrawal response
     */
    protected function formatWithdrawalResponse(array $apiResponse, float $amount): array
    {
        return [
            'success' => true,
            'txn_id' => $apiResponse['txn_id'] ?? $apiResponse['id'] ?? null,
            'status' => $apiResponse['status'] ?? 'pending',
            'amount' => $amount,
            'message' => 'Withdrawal processed successfully',
            'raw_response' => $apiResponse
        ];
    }

    /**
     * Verify Plisio callback signature
     *
     * @param array $data Callback data
     * @return bool
     */
    public function verifyCallbackSignature(array $data): bool
    {
        if (empty($data['verify_hash'])) {
            Log::warning('No verify_hash in Plisio callback');
            return false;
        }

        $receivedHash = $data['verify_hash'];
        unset($data['verify_hash']);

        ksort($data);
        $dataString = http_build_query($data);
        $calculatedHash = hash_hmac('sha1', $dataString, $this->secretKey);

        $isValid = hash_equals($calculatedHash, $receivedHash);

        Log::info('Plisio signature verification', [
            'received_hash' => $receivedHash,
            'calculated_hash' => $calculatedHash,
            'is_valid' => $isValid
        ]);

        return $isValid;
    }

    /**
     * Alias method for backward compatibility
     */
    public function generateCoinmentsPayment(string $currency, float $amount, string $orderId, array $package, array $userInfo): array
    {
        return $this->generatePlisioPayment($currency, $amount, $orderId, $package, $userInfo);
    }
}
