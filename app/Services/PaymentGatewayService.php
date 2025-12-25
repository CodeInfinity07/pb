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
    protected $testnet;
    protected $timeout;

    public function __construct()
    {
        $this->secretKey = config('payment.coinments.secret_key');
        $this->apiUrl = config('payment.coinments.api_url');
        $this->testnet = config('payment.coinments.testnet');
        $this->timeout = config('payment.coinments.timeout');
        
        if (empty($this->secretKey)) {
            throw new Exception('Payment gateway secret key not configured');
        }
    }

    /**
     * Generate payment for Coinments gateway
     *
     * @param string $currency
     * @param float $amount
     * @param string $orderId
     * @param array $package
     * @param array $userInfo
     * @return array
     * @throws Exception
     */
    public function generateCoinmentsPayment(string $currency, float $amount, string $orderId, array $package, array $userInfo): array
    {
        // Validate inputs
        $this->validatePaymentParams($currency, $amount, $orderId, $package, $userInfo);

        try {
            // Convert currency amount
            $convertedAmount = $this->convertCurrency($amount, $currency);
            
            // Build payload
            $payload = $this->buildPaymentPayload($orderId, $package, $userInfo, $convertedAmount, $currency);
            
            // Make API call
            $response = $this->makePaymentApiCall($payload);
            
            // Process and return response
            return $this->formatPaymentResponse($response, $convertedAmount);
            
        } catch (RequestException $e) {
            Log::error('Payment API request failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'response' => $e->response?->body()
            ]);
            throw new Exception('Payment gateway is currently unavailable. Please try again later.');
            
        } catch (Exception $e) {
            Log::error('Payment generation failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process withdrawal through YCPay gateway
     *
     * @param string $address
     * @param float $amount
     * @param string $orderId
     * @param string $currency
     * @param array $userInfo
     * @return array
     * @throws Exception
     */
    public function processWithdrawal(string $address, float $amount, string $orderId, string $currency, array $userInfo): array
    {
        // Validate withdrawal parameters
        $this->validateWithdrawalParams($address, $amount, $orderId, $currency);

        try {
            // Convert currency amount
            $convertedAmount = $this->convertCurrency($amount, $currency);
            $formattedAmount = number_format($convertedAmount, 8, '.', '');
            
            // Build withdrawal payload
            $payload = $this->buildWithdrawalPayload($address, $formattedAmount, $orderId, $currency, $userInfo);
            
            Log::info('Processing withdrawal request', [
                'order_id' => $orderId,
                'currency' => $currency,
                'amount' => $formattedAmount,
                'to_address' => $address,
                'user_id' => $userInfo['user_id'] ?? 'unknown'
            ]);
            
            // Make withdrawal API call
            $response = $this->makeWithdrawalApiCall($payload);
            
            // Process and return response
            return $this->formatWithdrawalResponse($response, $convertedAmount);
            
        } catch (RequestException $e) {
            Log::error('Withdrawal API request failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'response' => $e->response?->body()
            ]);
            throw new Exception('Withdrawal gateway is currently unavailable. Please try again later.');
            
        } catch (Exception $e) {
            Log::error('Withdrawal processing failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
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
     * Convert currency amount
     */
    protected function convertCurrency(float $amount, string $currency): float
    {
        // Replace this with your currency conversion logic
        // For now, assuming you have a helper function
        if (function_exists('fromcurrency')) {
            return fromcurrency($amount, $currency);
        }
        
        // Fallback - you might want to integrate with a currency API
        return $amount;
    }

    /**
     * Build payment payload
     */
    protected function buildPaymentPayload(string $orderId, array $package, array $userInfo, float $amount, string $currency): array
    {
        return [
            "order_id" => $orderId,
            "order_memo" => "Invoice for {$package['name']} - Username: {$userInfo['username']}",
            "amount" => $amount,
            "symbol" => $currency,
            "testnet" => $this->testnet
        ];
    }

    /**
     * Build withdrawal payload
     */
    protected function buildWithdrawalPayload(string $address, string $amount, string $orderId, string $currency, array $userInfo): array
    {
        $memo = "Withdraw # {$orderId}";
        
        return [
            "memo" => $memo,
            "amount" => $amount,
            "order_id" => $orderId,
            "symbol" => $currency,
            "to_address" => $address,
            "testnet" => $this->testnet
        ];
    }

    /**
     * Make API call to payment gateway
     */
    protected function makePaymentApiCall(array $payload): array
    {
        Log::info('Making API call to payment gateway', [
            'url' => "{$this->apiUrl}/invoice",
            'payload' => $payload,
            'headers' => [
                'x-api-key' => substr($this->secretKey, 0, 10) . '...' // Only show first 10 chars for security
            ]
        ]);

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'x-api-key' => $this->secretKey,
                'Content-Type' => 'application/json'
            ])
            ->post("{$this->apiUrl}/invoice", $payload);

        Log::info('API Response received', [
            'status_code' => $response->status(),
            'headers' => $response->headers(),
            'body' => $response->body(),
            'successful' => $response->successful()
        ]);

        if (!$response->successful()) {
            Log::error('API request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new RequestException($response);
        }

        // Try to decode JSON
        try {
            $data = $response->json();
            Log::info('JSON decoded successfully', ['data' => $data]);
        } catch (\Exception $e) {
            Log::error('Failed to decode JSON response', [
                'error' => $e->getMessage(),
                'raw_body' => $response->body()
            ]);
            throw new Exception('Invalid JSON response from payment gateway: ' . $response->body());
        }

        // Check if we have the expected structure
        if (!isset($data['result'])) {
            Log::error('Response missing result field', [
                'response_keys' => array_keys($data),
                'full_response' => $data
            ]);
            throw new Exception('Malformed response from payment gateway. Response: ' . json_encode($data));
        }

        if (!$data['result']) {
            $errorMessage = $data['error'] ?? $data['message'] ?? 'Unknown error from payment gateway';
            Log::error('Payment gateway returned error', [
                'error' => $errorMessage,
                'full_response' => $data
            ]);
            throw new Exception("Payment generation failed: {$errorMessage}");
        }

        return $data;
    }

    /**
     * Make API call to withdrawal gateway
     */
    protected function makeWithdrawalApiCall(array $payload): array
    {
        Log::info('Making withdrawal API call', [
            'url' => "{$this->apiUrl}/payment",
            'payload' => $payload,
            'headers' => [
                'x-api-key' => substr($this->secretKey, 0, 10) . '...' // Only show first 10 chars for security
            ]
        ]);

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'x-api-key' => $this->secretKey,
                'Content-Type' => 'application/json'
            ])
            ->post("{$this->apiUrl}/payment", $payload);

        Log::info('Withdrawal API Response received', [
            'status_code' => $response->status(),
            'headers' => $response->headers(),
            'body' => $response->body(),
            'successful' => $response->successful()
        ]);

        if (!$response->successful()) {
            Log::error('Withdrawal API request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new RequestException($response);
        }

        // Try to decode JSON
        try {
            $data = $response->json();
            Log::info('Withdrawal JSON decoded successfully', ['data' => $data]);
        } catch (\Exception $e) {
            Log::error('Failed to decode withdrawal JSON response', [
                'error' => $e->getMessage(),
                'raw_body' => $response->body()
            ]);
            throw new Exception('Invalid JSON response from withdrawal gateway: ' . $response->body());
        }

        // Check for transaction ID in response
        if (empty($data['txn_id'])) {
            $errorMessage = $data['message'] ?? $data['error'] ?? 'Withdrawal failed - no transaction ID returned';
            Log::error('Withdrawal gateway returned error', [
                'error' => $errorMessage,
                'full_response' => $data
            ]);
            throw new Exception("Withdrawal failed: {$errorMessage}");
        }

        return $data;
    }

    /**
     * Format payment response
     */
    protected function formatPaymentResponse(array $apiResponse, float $amount): array
    {
        $response = ['amount' => $amount];

        // Handle different response types
        if (!empty($apiResponse['address'])) {
            $response['address'] = $apiResponse['address'];
            $response['type'] = 'address';
        } elseif (!empty($apiResponse['url'])) {
            $response['form'] = $this->generatePaymentForm($apiResponse['url']);
            $response['type'] = 'form';
            $response['url'] = $apiResponse['url'];
        } else {
            throw new Exception('Invalid payment response format');
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
            'txn_id' => $apiResponse['txn_id'],
            'explorer_url' => $apiResponse['explorer_url'] ?? null,
            'amount' => $amount,
            'message' => 'Withdrawal processed successfully',
            'raw_response' => $apiResponse
        ];
    }

    /**
     * Generate HTML payment form
     */
    protected function generatePaymentForm(string $url): string
    {
        return sprintf(
            '<form method="post" target="_blank" action="%s">
                <input type="submit" name="m_process" value="Pay Now" class="btn btn-primary" />
            </form>',
            e($url) // Laravel's escape function for XSS protection
        );
    }
}