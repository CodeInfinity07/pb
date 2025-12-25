<?php

namespace App\Http\Controllers;

use App\Models\CryptoWallet;
use App\Models\Cryptocurrency;
use App\Models\Transaction;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class WalletController extends Controller
{
    protected $paymentService;

    // Currency short codes mapping
    private const CURRENCY_SHORT_CODES = [
        'USDT_BEP20' => 'UB',
        'USDT_TRC20' => 'UT',
        'USDT_ERC20' => 'UE',
        'BTC' => 'BT',
        'ETH' => 'ET',
        'BNB' => 'BN',
        'ADA' => 'AD',
        'SOL' => 'SL',
        'MATIC' => 'MT',
        'LTC' => 'LT',
        'DOGE' => 'DG',
        'TRX' => 'TR',
        'XRP' => 'XR',
        'DOT' => 'DT',
        'AVAX' => 'AV',
    ];

    public function __construct(PaymentGatewayService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Get dynamic minimum withdrawal based on user's withdrawal count
     */
    private function getMinimumWithdrawal($userId)
    {
        // Count completed withdrawals for this user
        $withdrawalCount = Transaction::where('user_id', $userId)
            ->where('type', Transaction::TYPE_WITHDRAWAL)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->count();

        // Calculate dynamic minimum based on withdrawal count
        switch ($withdrawalCount) {
            case 0:
                return 2;  // First withdrawal
            case 1:
                return 4;  // Second withdrawal
            case 2:
                return 8;  // Third withdrawal
            case 3:
                return 16; // Fourth withdrawal
            case 4:
                return 32; // Fifth withdrawal
            default:
                return 50; // Sixth withdrawal onwards
        }
    }

    /**
     * Display the wallets page
     */
    public function index()
    {
        $user = Auth::user();

        // Get all active cryptocurrencies
        $cryptocurrencies = Cryptocurrency::active()->ordered()->get();

        // Get user's existing wallets
        $userWallets = CryptoWallet::where('user_id', $user->id)
            ->with('cryptocurrency')
            ->get()
            ->keyBy('currency');

        // Create wallets for cryptocurrencies that don't exist for this user
        foreach ($cryptocurrencies as $crypto) {
            if (!$userWallets->has($crypto->symbol)) {
                CryptoWallet::create([
                    'user_id' => $user->id,
                    'currency' => $crypto->symbol,
                    'name' => $crypto->name,
                    'balance' => 0,
                    'usd_rate' => 1, // This should be updated from external API
                    'is_active' => true
                ]);
            }
        }

        // Re-fetch wallets after creation
        $wallets = CryptoWallet::where('user_id', $user->id)
            ->with('cryptocurrency')
            ->active()
            ->get();

        // Calculate total portfolio value
        $totalUsdValue = $wallets->sum('usd_value');

        return view('finance.wallets', compact('wallets', 'totalUsdValue', 'user'));
    }

    /**
     * Show deposit page - either currency selection or specific wallet deposit
     */
    public function deposit()
    {
        $user = Auth::user();

        // Re-fetch wallets after creation
        $wallet = CryptoWallet::where('user_id', $user->id)
            ->with('cryptocurrency')
            ->active()
            ->first();

        if ($wallet) {
            // Ensure the wallet belongs to the authenticated user
            if ($wallet->user_id !== Auth::id()) {
                abort(403);
            }

            return view('finance.deposit', [
                'selectedWallet' => $wallet,
                'wallets' => null,
                'user' => $user,
                'paymentData' => null
            ]);
        }

        // Show currency selection
        $wallets = CryptoWallet::where('user_id', $user->id)
            ->with('cryptocurrency')
            ->active()
            ->get();

        return view('finance.deposit', [
            'selectedWallet' => null,
            'wallets' => $wallets,
            'user' => $user,
            'paymentData' => null
        ]);
    }

    /**
     * Generate payment for deposit
     */
    public function generateDepositPayment(Request $request, CryptoWallet $wallet)
    {
        // Ensure the wallet belongs to the authenticated user
        if ($wallet->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to wallet');
        }

        // Validate the request
        $request->validate([
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:100000' // Set reasonable maximum
            ]
        ], [
            'amount.required' => 'Please enter a deposit amount',
            'amount.numeric' => 'Amount must be a valid number',
            'amount.min' => 'Minimum deposit amount is $0.01',
            'amount.max' => 'Maximum deposit amount is $100,000'
        ]);

        try {
            $amount = $request->input('amount');
            $currency = $wallet->currency;
            $orderId = $this->generateOrderId($wallet);

            // Package information for the payment
            $packageInfo = [
                'name' => "Deposit to {$wallet->name} Wallet"
            ];

            // User information for the payment
            $userInfo = [
                'username' => Auth::user()->username ?? Auth::user()->email,
                'email' => Auth::user()->email,
                'user_id' => Auth::id()
            ];

            // Generate payment using the gateway service
            $paymentData = $this->paymentService->generateCoinmentsPayment(
                $currency,
                $amount,
                $orderId,
                $packageInfo,
                $userInfo
            );

            // Store payment record for tracking
            $this->createPaymentRecord($wallet, $amount, $orderId, $paymentData);

            // Return view with payment data
            return view('finance.deposit', [
                'selectedWallet' => $wallet,
                'wallets' => null,
                'user' => Auth::user(),
                'paymentData' => $paymentData,
                'requestedAmount' => $amount,
                'orderId' => $orderId
            ]);

        } catch (Exception $e) {
            Log::error('Deposit payment generation failed', [
                'user_id' => Auth::id(),
                'wallet_id' => $wallet->id,
                'amount' => $request->input('amount'),
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to generate payment: ' . $e->getMessage());
        }
    }

    /**
     * Handle Plisio webhook callbacks
     */
    public function handlePlisioWebhook(Request $request)
    {
        Log::info('=== PLISIO WEBHOOK RECEIVED ===', [
            'timestamp' => now()->toISOString(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'query_params' => $request->query(),
            'form_data' => $request->all(),
            'raw_content' => $request->getContent()
        ]);

        try {
            $data = $request->all();

            if (empty($data)) {
                $data = json_decode($request->getContent(), true) ?? [];
            }

            if (!$this->paymentService->verifyCallbackSignature($data)) {
                Log::warning('Plisio webhook signature verification failed', ['data' => $data]);
                return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 401);
            }

            if (empty($data['order_number']) && empty($data['txn_id'])) {
                Log::warning('Plisio webhook missing required fields', ['data' => $data]);
                return response()->json(['status' => 'error', 'message' => 'Missing required fields'], 400);
            }

            $status = $data['status'] ?? '';
            $orderId = $data['order_number'] ?? '';
            $txnId = $data['txn_id'] ?? '';
            $sourceAmount = floatval($data['source_amount'] ?? 0);
            $cryptoAmount = floatval($data['amount'] ?? 0);
            $currency = $data['currency'] ?? $data['psys_cid'] ?? '';
            $confirmations = intval($data['confirmations'] ?? 0);

            Log::info('Plisio webhook parsed', [
                'status' => $status,
                'order_id' => $orderId,
                'txn_id' => $txnId,
                'source_amount' => $sourceAmount,
                'crypto_amount' => $cryptoAmount,
                'currency' => $currency,
                'confirmations' => $confirmations
            ]);

            if ($status === 'completed') {
                $result = $this->confirmPlisioDeposit(
                    $orderId,
                    $sourceAmount,
                    $currency,
                    $txnId,
                    json_encode($data),
                    $confirmations
                );

                if ($result) {
                    Log::info('=== PLISIO DEPOSIT CONFIRMED ===', ['order_id' => $orderId, 'txn_id' => $txnId]);
                    return response()->json(['status' => 'success']);
                } else {
                    Log::error('=== PLISIO DEPOSIT FAILED ===', ['order_id' => $orderId]);
                    return response()->json(['status' => 'error', 'message' => 'Deposit confirmation failed'], 500);
                }
            }

            if ($status === 'mismatch') {
                Log::warning('Plisio payment amount mismatch - requires manual review', [
                    'order_id' => $orderId,
                    'expected_amount' => $data['expected_amount'] ?? 'unknown',
                    'received_amount' => $cryptoAmount
                ]);
                return response()->json(['status' => 'success', 'message' => 'Mismatch logged for review']);
            }

            if (in_array($status, ['pending', 'new'])) {
                Log::info('Plisio payment pending', ['order_id' => $orderId, 'status' => $status]);
                return response()->json(['status' => 'success', 'message' => 'Payment pending']);
            }

            if (in_array($status, ['cancelled', 'expired', 'error'])) {
                $this->cancelPendingTransaction($orderId);
                Log::info('Plisio payment cancelled/expired', ['order_id' => $orderId, 'status' => $status]);
                return response()->json(['status' => 'success', 'message' => 'Payment cancelled']);
            }

            Log::info('Plisio webhook status acknowledged', ['status' => $status, 'order_id' => $orderId]);
            return response()->json(['status' => 'success']);

        } catch (Exception $e) {
            Log::error('=== PLISIO WEBHOOK ERROR ===', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);
            return response()->json(['status' => 'error', 'message' => 'Internal error'], 500);
        }
    }

    /**
     * Confirm Plisio deposit and credit user wallet
     */
    private function confirmPlisioDeposit(string $orderId, float $sourceAmount, string $currency, string $txnId, string $rawData, int $confirmations): bool
    {
        try {
            return DB::transaction(function () use ($orderId, $sourceAmount, $currency, $txnId, $rawData, $confirmations) {
                $orderParts = explode('_', $orderId);

                if (count($orderParts) !== 3) {
                    Log::error('Invalid Plisio order ID format', ['order_id' => $orderId]);
                    return false;
                }

                $shortCode = $orderParts[0];
                $userId = $orderParts[1];

                $walletCurrency = $this->getFullCurrencyFromShortCode($shortCode);
                if (!$walletCurrency) {
                    Log::error('Unknown currency short code', ['short_code' => $shortCode]);
                    return false;
                }

                $wallet = CryptoWallet::where('user_id', $userId)
                    ->where('currency', $walletCurrency)
                    ->lockForUpdate()
                    ->first();

                if (!$wallet) {
                    Log::error('Wallet not found for Plisio deposit', ['user_id' => $userId, 'currency' => $walletCurrency]);
                    return false;
                }

                $existingByTxid = Transaction::where('crypto_txid', $txnId)
                    ->where('status', Transaction::STATUS_COMPLETED)
                    ->first();

                if ($existingByTxid) {
                    Log::warning('Plisio transaction already processed', ['txn_id' => $txnId]);
                    return true;
                }

                $transaction = Transaction::where('transaction_id', $orderId)
                    ->where('status', Transaction::STATUS_PENDING)
                    ->lockForUpdate()
                    ->first();

                if (!$transaction) {
                    $completedTransaction = Transaction::where('transaction_id', $orderId)
                        ->where('status', Transaction::STATUS_COMPLETED)
                        ->first();

                    if ($completedTransaction) {
                        return true;
                    }

                    Log::error('No pending transaction found for Plisio deposit', ['order_id' => $orderId]);
                    return false;
                }

                $expectedAmount = $transaction->amount;
                if (abs($sourceAmount - $expectedAmount) > 0.01) {
                    Log::warning('Plisio deposit amount mismatch', [
                        'order_id' => $orderId,
                        'expected' => $expectedAmount,
                        'received' => $sourceAmount
                    ]);
                }

                $creditAmount = $sourceAmount > 0 ? $sourceAmount : $expectedAmount;

                $oldBalance = $wallet->balance;
                $wallet->increment('balance', $creditAmount);
                $newBalance = $wallet->fresh()->balance;

                $transaction->update([
                    'status' => Transaction::STATUS_COMPLETED,
                    'crypto_txid' => $txnId,
                    'processed_at' => now(),
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'webhook_data' => json_decode($rawData, true),
                        'confirmations' => $confirmations,
                        'old_balance' => $oldBalance,
                        'new_balance' => $newBalance,
                        'credited_amount' => $creditAmount,
                        'gateway' => 'plisio'
                    ]),
                ]);

                Log::info('=== PLISIO DEPOSIT PROCESSED ===', [
                    'order_id' => $orderId,
                    'user_id' => $userId,
                    'credited_amount' => $creditAmount,
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance
                ]);

                return true;
            });
        } catch (Exception $e) {
            Log::error('Plisio deposit confirmation error', ['error' => $e->getMessage(), 'order_id' => $orderId]);
            return false;
        }
    }

    /**
     * Cancel pending transaction
     */
    private function cancelPendingTransaction(string $orderId): void
    {
        Transaction::where('transaction_id', $orderId)
            ->where('status', Transaction::STATUS_PENDING)
            ->update([
                'status' => Transaction::STATUS_FAILED,
                'processed_at' => now()
            ]);
    }

    /**
     * Handle Coinments webhook (legacy - kept for backward compatibility)
     */
    public function handleCoinmentsWebhook(Request $request)
    {
        Log::info('=== COINMENTS WEBHOOK RECEIVED ===', [
            'timestamp' => now()->toISOString(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all(),
            'query_params' => $request->query(),
            'form_data' => $request->all(),
            'raw_content' => $request->getContent(),
            'content_type' => $request->header('Content-Type'),
            'content_length' => $request->header('Content-Length')
        ]);

        try {
            // Check if this is a Coinments callback
            if (!$request->has('coinments') && !$request->has('uuid')) {
                Log::warning('Webhook called without coinments parameter', [
                    'has_coinments' => $request->has('coinments'),
                    'has_uuid' => $request->has('uuid'),
                    'all_params' => $request->all()
                ]);
                return response('Invalid webhook call', 400);
            }

            // Get payment method settings
            $api_key = config('payment.coinments.secret_key');

            if (empty($api_key)) {
                Log::error('Coinments secret key not configured');
                return response('Configuration error', 500);
            }

            // Log the secret key info (safely)
            Log::info('Secret key info', [
                'key_configured' => !empty($api_key),
                'key_length' => strlen($api_key),
                'key_preview' => substr($api_key, 0, 8) . '...'
            ]);

            // Get raw input data
            $rawInput = $request->getContent();
            Log::info('Raw webhook input received', [
                'raw_input' => $rawInput,
                'raw_length' => strlen($rawInput)
            ]);

            $data = json_decode($rawInput, true);

            if (!$data) {
                Log::error('Invalid JSON data received', [
                    'raw_input' => $rawInput,
                    'json_error' => json_last_error_msg(),
                    'json_error_code' => json_last_error()
                ]);
                return response('Invalid JSON', 400);
            }

            Log::info('Parsed webhook data', [
                'parsed_data' => $data,
                'data_keys' => array_keys($data)
            ]);

            // Check if this is a status update webhook (amount_received = 0 means waiting for payment)
            $amountReceived = $data['amount_received'] ?? 0;
            $paymentStatus = $data['payment_status'] ?? '';

            if ($amountReceived == 0 && $paymentStatus === 'waiting') {
                Log::info('Payment status webhook - waiting for payment', [
                    'order_id' => $data['order_id'] ?? '',
                    'payment_status' => $paymentStatus,
                    'amount_received' => $amountReceived
                ]);

                // For waiting status, just verify signature and return OK
                $signatureValid = $this->verifyCoinmentsSignature($data, $api_key);

                if ($signatureValid) {
                    Log::info('Payment waiting status confirmed', ['order_id' => $data['order_id'] ?? '']);
                    return response('OK - Payment waiting', 200);
                } else {
                    Log::error('Invalid signature for waiting payment', ['data' => $data]);
                    return response('Invalid signature', 400);
                }
            }

            // Verify signature for actual payments
            $signatureValid = $this->verifyCoinmentsSignature($data, $api_key);

            if (!$signatureValid) {
                Log::error('Invalid signature for payment', ['data' => $data]);
                return response('Invalid signature', 400);
            }

            // Extract callback data
            $uuid = $data['uuid'] ?? '';
            $currencyId = $data['currency_id'] ?? '';
            $txnId = $data['txn_id'] ?? '';
            $orderId = $data['order_id'] ?? '';
            $timestamp = $data['timestamp'] ?? '';
            $symbol = $data['coin'] ?? $data['token_symbol'] ?? '';
            $address = $data['address'] ?? '';
            $confirmations = $data['confirmations'] ?? 0;

            Log::info('Extracted webhook fields', [
                'uuid' => $uuid,
                'currency_id' => $currencyId,
                'txn_id' => $txnId,
                'amount_received' => $amountReceived,
                'order_id' => $orderId,
                'timestamp' => $timestamp,
                'symbol' => $symbol,
                'address' => $address,
                'confirmations' => $confirmations
            ]);

            // Only process if we have actual payment (amount > 0)
            if ($amountReceived > 0) {
                // Validate required fields
                if (empty($orderId) || empty($amountReceived) || empty($symbol)) {
                    Log::error('Missing required webhook data for payment', [
                        'missing_fields' => [
                            'order_id_empty' => empty($orderId),
                            'amount_received_empty' => empty($amountReceived),
                            'symbol_empty' => empty($symbol)
                        ],
                        'data' => $data
                    ]);
                    return response('Missing required data', 400);
                }

                Log::info('Starting deposit confirmation process', [
                    'order_id' => $orderId,
                    'amount' => $amountReceived,
                    'symbol' => $symbol,
                    'txn_id' => $txnId
                ]);

                // Process the deposit confirmation
                $result = $this->confirmDeposit(
                    $orderId,
                    $amountReceived,
                    $symbol,
                    $txnId,
                    json_encode($data),
                    $address,
                    $confirmations
                );

                if ($result) {
                    Log::info('=== DEPOSIT CONFIRMED SUCCESSFULLY ===', [
                        'order_id' => $orderId,
                        'amount' => $amountReceived,
                        'symbol' => $symbol,
                        'txn_id' => $txnId
                    ]);
                    return response('OK', 200);
                } else {
                    Log::error('=== DEPOSIT CONFIRMATION FAILED ===', [
                        'order_id' => $orderId,
                        'amount' => $amountReceived,
                        'symbol' => $symbol,
                        'txn_id' => $txnId
                    ]);
                    return response('Deposit confirmation failed', 500);
                }
            } else {
                Log::info('Webhook processed - no payment to credit yet', [
                    'order_id' => $orderId,
                    'payment_status' => $paymentStatus
                ]);
                return response('OK', 200);
            }

        } catch (Exception $e) {
            Log::error('=== WEBHOOK PROCESSING ERROR ===', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'raw_input' => $request->getContent()
            ]);
            return response('Internal server error', 500);
        }
    }

    /**
     * Verify Coinments signature
     */
    private function verifyCoinmentsSignature(array $data, string $api_key): bool
    {
        $receivedSignature = $data['sign'] ?? '';

        if (empty($receivedSignature)) {
            Log::error('No signature provided in webhook data');
            return false;
        }

        // Build signature array - use ?: operator to treat 0 as empty string
        $sign = [
            $data['uuid'] ?? '',
            $data['currency_id'] ?? '',
            $data['txn_id'] ?: '',  // Empty string if falsy
            $data['amount_received'] ?: '',  // Empty string if 0, null, or falsy
            $data['order_id'] ?? '',
            $data['timestamp'] ?? '',
            $api_key
        ];

        $stringToSign = implode(':', $sign);
        $calculatedSignature = hash('sha256', $stringToSign);

        Log::info('Signature verification', [
            'received_signature' => $receivedSignature,
            'calculated_signature' => $calculatedSignature,
            'api_key_length' => strlen($api_key),
            'api_key_preview' => substr($api_key, 0, 8) . '...',
            'signature_parts' => $sign,
            'string_to_sign' => $stringToSign,
            'signatures_match' => hash_equals($calculatedSignature, $receivedSignature)
        ]);

        $isValid = hash_equals($calculatedSignature, $receivedSignature);

        if ($isValid) {
            Log::info('✅ Signature verified successfully!');
        } else {
            Log::error('❌ Signature verification failed', [
                'expected' => $receivedSignature,
                'calculated' => $calculatedSignature
            ]);
        }

        return $isValid;
    }

    /**
     * Confirm deposit and credit user wallet - FIXED VERSION
     */
    private function confirmDeposit(string $orderId, float $amount, string $symbol, string $txnId, string $hash, string $address, int $confirmations): bool
    {
        try {
            return DB::transaction(function () use ($orderId, $amount, $symbol, $txnId, $hash, $address, $confirmations) {

                // Parse order ID: SHORTCODE_USERID_TIMESTAMP
                $orderParts = explode('_', $orderId);

                if (count($orderParts) !== 3) {
                    Log::error('Invalid order ID format', [
                        'order_id' => $orderId,
                        'parts_count' => count($orderParts),
                        'parts' => $orderParts,
                        'expected_format' => 'SHORTCODE_USERID_TIMESTAMP'
                    ]);
                    return false;
                }

                $shortCode = $orderParts[0];   // UB, UT, BT, etc.
                $userId = $orderParts[1];      // User ID
                $timestamp = $orderParts[2];   // Timestamp

                // Convert short code back to full currency
                $currency = $this->getFullCurrencyFromShortCode($shortCode);

                if (!$currency) {
                    Log::error('Unknown currency short code', [
                        'order_id' => $orderId,
                        'short_code' => $shortCode,
                        'available_codes' => array_keys(self::CURRENCY_SHORT_CODES)
                    ]);
                    return false;
                }

                Log::info('Parsed order ID', [
                    'order_id' => $orderId,
                    'short_code' => $shortCode,
                    'currency' => $currency,
                    'user_id' => $userId,
                    'timestamp' => $timestamp
                ]);

                // Find user's wallet
                $wallet = CryptoWallet::where('user_id', $userId)
                    ->where('currency', $currency)
                    ->first();

                if (!$wallet) {
                    Log::error('Wallet not found for deposit', [
                        'user_id' => $userId,
                        'currency' => $currency,
                        'order_id' => $orderId,
                        'available_wallets' => CryptoWallet::where('user_id', $userId)->pluck('currency')->toArray()
                    ]);
                    return false;
                }

                // Check if this transaction was already processed by crypto_txid
                $existingByTxid = Transaction::where('crypto_txid', $txnId)
                    ->where('status', Transaction::STATUS_COMPLETED)
                    ->first();

                if ($existingByTxid) {
                    Log::warning('Transaction already processed by crypto_txid', [
                        'order_id' => $orderId,
                        'txn_id' => $txnId,
                        'existing_id' => $existingByTxid->id
                    ]);
                    return true; // Return true since it was already processed
                }

                // Find pending transaction by order ID
                $transaction = Transaction::where('transaction_id', $orderId)
                    ->where('status', Transaction::STATUS_PENDING)
                    ->first();

                if (!$transaction) {
                    // Check if order was already completed
                    $completedTransaction = Transaction::where('transaction_id', $orderId)
                        ->where('status', Transaction::STATUS_COMPLETED)
                        ->first();

                    if ($completedTransaction) {
                        Log::warning('Transaction already completed', [
                            'order_id' => $orderId,
                            'txn_id' => $txnId,
                            'existing_id' => $completedTransaction->id
                        ]);
                        return true;
                    }

                    // No pending record found - create new one (fallback)
                    Log::warning('No pending transaction found, creating new record', [
                        'order_id' => $orderId,
                        'user_id' => $userId,
                        'currency' => $currency
                    ]);

                    $transaction = Transaction::create([
                        'user_id' => $userId,
                        'transaction_id' => $orderId,
                        'type' => Transaction::TYPE_DEPOSIT,
                        'amount' => $amount,
                        'currency' => $currency,
                        'status' => Transaction::STATUS_PENDING,
                        'payment_method' => 'coinments',
                        'description' => "Crypto deposit - {$currency} via Coinments Gateway",
                        'metadata' => [
                            'wallet_id' => $wallet->id,
                            'gateway' => 'coinments',
                            'created_via' => 'webhook_fallback'
                        ]
                    ]);
                }

                // Capture balance BEFORE any increments
                $oldBalance = $wallet->balance;

                // Credit the wallet - ONLY ONCE HERE
                $wallet->increment('balance', $amount);
                $newBalance = $wallet->fresh()->balance;

                // Update transaction to completed
                $transaction->update([
                    'status' => Transaction::STATUS_COMPLETED,
                    'crypto_txid' => $txnId,
                    'crypto_address' => $address,
                    'processed_at' => now(),
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'webhook_data' => json_decode($hash, true),
                        'confirmations' => $confirmations,
                        'old_balance' => $oldBalance,
                        'new_balance' => $newBalance,
                        'gateway' => 'coinments'
                    ]),
                ]);

                Log::info('=== DEPOSIT PROCESSED SUCCESSFULLY ===', [
                    'transaction_id' => $transaction->id,
                    'user_id' => $userId,
                    'wallet_id' => $wallet->id,
                    'currency' => $currency,
                    'symbol' => $symbol,
                    'amount' => $amount,
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                    'crypto_txid' => $txnId,
                    'order_id' => $orderId,
                    'balance_incremented_once' => true
                ]);

                // 🔔 SEND DEPOSIT NOTIFICATION HERE
                try {
                    $user = \App\Models\User::find($userId);
                    if ($user) {
                        $user->notify(
                            \App\Notifications\UnifiedNotification::depositConfirmed(
                                $amount,
                                $currency,
                                $orderId
                            )
                        );

                        Log::info('Deposit notification sent', [
                            'user_id' => $userId,
                            'amount' => $amount,
                            'currency' => $currency
                        ]);
                    }
                } catch (\Exception $notificationError) {
                    // Don't fail the deposit if notification fails
                    Log::error('Deposit notification failed', [
                        'user_id' => $userId,
                        'error' => $notificationError->getMessage()
                    ]);
                }

                return true;
            });

        } catch (Exception $e) {
            Log::error('Deposit confirmation failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Check payment status
     */
    public function checkPaymentStatus(Request $request)
    {
        try {
            $orderId = $request->input('order_id');

            if (empty($orderId)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order ID is required'
                ], 400);
            }

            // Check if transaction exists in our records
            $transaction = Transaction::where('transaction_id', $orderId)
                ->where('type', Transaction::TYPE_DEPOSIT)
                ->first();

            if ($transaction) {
                // We have a record of this transaction
                if ($transaction->isCompleted()) {
                    return response()->json([
                        'status' => 'confirmed',
                        'message' => 'Payment has been confirmed and credited to your wallet.',
                        'data' => [
                            'amount' => $transaction->amount,
                            'currency' => $transaction->currency,
                            'crypto_txid' => $transaction->crypto_txid,
                            'confirmations' => $transaction->metadata['confirmations'] ?? 0,
                            'processed_at' => $transaction->processed_at,
                            'new_balance' => $transaction->metadata['new_balance'] ?? null
                        ]
                    ]);
                } elseif ($transaction->status === Transaction::STATUS_PROCESSING) {
                    return response()->json([
                        'status' => 'processing',
                        'message' => 'Payment is being processed. Please wait for confirmation.',
                        'confirmations' => $transaction->metadata['confirmations'] ?? 0
                    ]);
                } elseif ($transaction->status === Transaction::STATUS_FAILED) {
                    return response()->json([
                        'status' => 'failed',
                        'message' => 'Payment failed. Please try again or contact support.',
                        'error_details' => $transaction->metadata['error'] ?? null
                    ]);
                } else {
                    return response()->json([
                        'status' => 'pending',
                        'message' => 'Payment is pending confirmation.',
                        'confirmations' => $transaction->metadata['confirmations'] ?? 0
                    ]);
                }
            }

            // No record found - payment might still be pending or order doesn't exist
            return response()->json([
                'status' => 'pending',
                'message' => 'Waiting for payment confirmation...'
            ]);

        } catch (Exception $e) {
            Log::error('Payment status check failed', [
                'order_id' => $request->input('order_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check payment status'
            ], 500);
        }
    }

    /**
     * Show withdraw page - either currency selection or specific wallet withdrawal
     */
    public function withdraw()
    {
        $user = Auth::user();
        $dynamicMinWithdrawal = $this->getMinimumWithdrawal($user->id);

        // Re-fetch wallets after creation
        $wallet = CryptoWallet::where('user_id', $user->id)
            ->with('cryptocurrency')
            ->active()
            ->first();

        if ($wallet) {
            // Ensure the wallet belongs to the authenticated user
            if ($wallet->user_id !== Auth::id()) {
                abort(403);
            }

            return view('finance.withdraw', [
                'selectedWallet' => $wallet,
                'wallets' => null,
                'user' => $user,
                'dynamicMinWithdrawal' => $dynamicMinWithdrawal
            ]);
        }

        // Show currency selection - only wallets with balance
        $wallets = CryptoWallet::where('user_id', $user->id)
            ->with('cryptocurrency')
            ->active()
            ->withBalance()
            ->get();

        return view('finance.withdraw', [
            'selectedWallet' => null,
            'wallets' => $wallets,
            'user' => $user,
            'dynamicMinWithdrawal' => $dynamicMinWithdrawal
        ]);
    }
    /**
     * Update wallet address
     */
    public function updateAddress(Request $request, CryptoWallet $wallet)
    {
        // Ensure the wallet belongs to the authenticated user
        if ($wallet->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'address' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($wallet) {
                    // Basic address validation based on cryptocurrency
                    if (!$this->validateCryptoAddress($value, $wallet->cryptocurrency)) {
                        $fail('The address format is invalid for ' . $wallet->currency);
                    }
                }
            ]
        ]);

        $wallet->update([
            'address' => $request->address
        ]);

        return back()->with('success', 'Wallet address updated successfully!');
    }

    /**
     * Process withdrawal request
     */
    public function processWithdraw(Request $request, CryptoWallet $wallet)
    {
        // Ensure the wallet belongs to the authenticated user
        if ($wallet->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to wallet');
        }

        // Check if wallet has address set
        if (!$wallet->hasAddress()) {
            return back()->with('error', 'Please set your wallet address first!');
        }

        $crypto = $wallet->cryptocurrency;

        // Get dynamic minimum withdrawal
        $dynamicMinWithdrawal = $this->getMinimumWithdrawal(Auth::id());

        // Validate withdrawal request
        $request->validate([
            'amount' => [
                'required',
                'numeric',
                'min:' . $dynamicMinWithdrawal,
                'max:' . $wallet->balance
            ],
            'to_address' => 'required|string',
        ], [
            'amount.required' => 'Please enter a withdrawal amount',
            'amount.numeric' => 'Amount must be a valid number',
            'amount.min' => "Minimum withdrawal amount is \${$dynamicMinWithdrawal}",
            'amount.max' => 'Insufficient balance for withdrawal',
            'to_address.required' => 'Withdrawal address is required'
        ]);

        $requestedAmount = $request->amount; // Amount user wants to withdraw
        $feePercentage = $crypto->withdrawal_fee; // e.g., 10 for 10%
        $fee = ($requestedAmount * $feePercentage) / 100; // Calculate fee
        $actualAmountToSend = $requestedAmount - $fee; // What user actually receives
        $toAddress = $request->to_address;

        // Final balance check
        if ($wallet->balance < $requestedAmount) {
            return back()->with('error', 'Insufficient balance for withdrawal!');
        }

        // Validate withdrawal address format
        if (!$this->validateCryptoAddress($toAddress, $crypto)) {
            return back()->with('error', 'Invalid withdrawal address format for ' . $wallet->currency);
        }

        try {
            // Generate unique order ID for withdrawal
            $orderId = $this->generateWithdrawalOrderId($wallet);

            // Start database transaction
            return DB::transaction(function () use ($wallet, $requestedAmount, $actualAmountToSend, $fee, $feePercentage, $toAddress, $orderId, $crypto) {

                // Capture current balance
                $oldBalance = $wallet->balance;

                // Deduct the full requested amount from wallet
                $wallet->decrement('balance', $requestedAmount);
                $newBalance = $wallet->fresh()->balance;

                // Create withdrawal transaction record
                $transaction = Transaction::create([
                    'user_id' => Auth::id(),
                    'transaction_id' => $orderId,
                    'type' => Transaction::TYPE_WITHDRAWAL,
                    'amount' => $actualAmountToSend, // Amount user receives
                    'currency' => $wallet->currency,
                    'status' => Transaction::STATUS_PROCESSING,
                    'payment_method' => 'ycpay',
                    'crypto_address' => $toAddress,
                    'description' => "Withdrawal of {$requestedAmount} {$wallet->currency} to {$toAddress}",
                    'metadata' => [
                        'wallet_id' => $wallet->id,
                        'requested_amount' => $requestedAmount,
                        'withdrawal_fee' => $fee,
                        'fee_percentage' => $feePercentage,
                        'actual_amount_sent' => $actualAmountToSend,
                        'total_deducted' => $requestedAmount,
                        'old_balance' => $oldBalance,
                        'new_balance' => $newBalance,
                        'to_address' => $toAddress,
                        'gateway' => 'ycpay',
                        'initiated_at' => now()->toISOString()
                    ]
                ]);

                Log::info('Withdrawal transaction created', [
                    'transaction_id' => $transaction->id,
                    'order_id' => $orderId,
                    'user_id' => Auth::id(),
                    'wallet_id' => $wallet->id,
                    'currency' => $wallet->currency,
                    'requested_amount' => $requestedAmount,
                    'fee' => $fee,
                    'fee_percentage' => $feePercentage,
                    'actual_amount_sent' => $actualAmountToSend,
                    'to_address' => $toAddress,
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance
                ]);

                try {
                    // Process withdrawal through payment gateway
                    $userInfo = [
                        'username' => Auth::user()->username ?? Auth::user()->email,
                        'email' => Auth::user()->email,
                        'user_id' => Auth::id()
                    ];

                    // Send the actual amount (after fee deduction)
                    $withdrawalResult = $this->paymentService->processWithdrawal(
                        $toAddress,
                        $actualAmountToSend, // Send amount minus fee
                        $orderId,
                        $wallet->currency,
                        $userInfo
                    );

                    // Update transaction with successful result
                    $transaction->update([
                        'status' => Transaction::STATUS_COMPLETED,
                        'crypto_txid' => $withdrawalResult['txn_id'],
                        'processed_at' => now(),
                        'metadata' => array_merge($transaction->metadata, [
                            'withdrawal_result' => $withdrawalResult,
                            'explorer_url' => $withdrawalResult['explorer_url'] ?? null,
                            'gateway_response' => $withdrawalResult['raw_response'] ?? null,
                            'completed_at' => now()->toISOString()
                        ])
                    ]);

                    Log::info('=== WITHDRAWAL COMPLETED SUCCESSFULLY ===', [
                        'transaction_id' => $transaction->id,
                        'order_id' => $orderId,
                        'user_id' => Auth::id(),
                        'currency' => $wallet->currency,
                        'requested_amount' => $requestedAmount,
                        'fee' => $fee,
                        'actual_amount_sent' => $actualAmountToSend,
                        'to_address' => $toAddress,
                        'txn_id' => $withdrawalResult['txn_id'],
                        'explorer_url' => $withdrawalResult['explorer_url'] ?? null
                    ]);

                    // 🔔 SEND WITHDRAWAL SUCCESS NOTIFICATION HERE
                    try {
                        Auth::user()->notify(
                            \App\Notifications\UnifiedNotification::withdrawalApproved(
                                $actualAmountToSend,
                                $wallet->currency,
                                $orderId,
                                $toAddress
                            )
                        );

                        Log::info('Withdrawal success notification sent', [
                            'user_id' => Auth::id(),
                            'order_id' => $orderId
                        ]);
                    } catch (\Exception $notificationError) {
                        Log::error('Withdrawal success notification failed', [
                            'user_id' => Auth::id(),
                            'error' => $notificationError->getMessage()
                        ]);
                    }

                    return back()->with(
                        'success',
                        "Withdrawal processed successfully. You will receive {$actualAmountToSend} {$wallet->currency} at your address."
                    );

                } catch (Exception $gatewayException) {
                    Log::error('Withdrawal gateway processing failed', [
                        'transaction_id' => $transaction->id,
                        'order_id' => $orderId,
                        'error' => $gatewayException->getMessage(),
                        'trace' => $gatewayException->getTraceAsString()
                    ]);

                    // Refund the full requested amount back to wallet since gateway failed
                    $wallet->increment('balance', $requestedAmount);
                    $refundedBalance = $wallet->fresh()->balance;

                    // Update transaction status to failed
                    $transaction->update([
                        'status' => Transaction::STATUS_FAILED,
                        'metadata' => array_merge($transaction->metadata, [
                            'error' => $gatewayException->getMessage(),
                            'failed_at' => now()->toISOString(),
                            'refunded' => true,
                            'refunded_amount' => $requestedAmount,
                            'balance_after_refund' => $refundedBalance
                        ])
                    ]);

                    Log::info('Amount refunded due to gateway failure', [
                        'transaction_id' => $transaction->id,
                        'order_id' => $orderId,
                        'refunded_amount' => $requestedAmount,
                        'balance_after_refund' => $refundedBalance
                    ]);

                    return back()->with(
                        'error',
                        'Withdrawal failed: ' . $gatewayException->getMessage() .
                        '. Your funds have been refunded to your wallet.'
                    );
                }
            });

        } catch (Exception $e) {
            Log::error('Withdrawal processing failed', [
                'user_id' => Auth::id(),
                'wallet_id' => $wallet->id,
                'requested_amount' => $requestedAmount,
                'to_address' => $toAddress,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Withdrawal failed: ' . $e->getMessage());
        }
    }

    /**
     * Check withdrawal status
     */
    public function checkWithdrawalStatus(Request $request)
    {
        try {
            $orderId = $request->input('order_id');

            if (empty($orderId)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order ID is required'
                ], 400);
            }

            // Check if transaction exists in our records
            $transaction = Transaction::where('transaction_id', $orderId)
                ->where('type', Transaction::TYPE_WITHDRAWAL)
                ->where('user_id', Auth::id()) // Ensure user can only check their own withdrawals
                ->first();

            if (!$transaction) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Withdrawal not found'
                ], 404);
            }

            if ($transaction->isCompleted()) {
                return response()->json([
                    'status' => 'completed',
                    'message' => 'Withdrawal has been completed successfully.',
                    'data' => [
                        'amount' => $transaction->amount,
                        'currency' => $transaction->currency,
                        'to_address' => $transaction->crypto_address,
                        'crypto_txid' => $transaction->crypto_txid,
                        'explorer_url' => $transaction->metadata['explorer_url'] ?? null,
                        'processed_at' => $transaction->processed_at,
                        'withdrawal_fee' => $transaction->metadata['withdrawal_fee'] ?? 0
                    ]
                ]);
            } elseif ($transaction->status === Transaction::STATUS_PROCESSING) {
                return response()->json([
                    'status' => 'processing',
                    'message' => 'Withdrawal is being processed. Please wait...',
                    'initiated_at' => $transaction->created_at
                ]);
            } elseif ($transaction->status === Transaction::STATUS_FAILED) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Withdrawal failed. Your funds have been refunded.',
                    'error_details' => $transaction->metadata['error'] ?? 'Unknown error',
                    'refunded' => $transaction->metadata['refunded'] ?? false
                ]);
            } else {
                return response()->json([
                    'status' => 'pending',
                    'message' => 'Withdrawal is pending processing.',
                    'initiated_at' => $transaction->created_at
                ]);
            }

        } catch (Exception $e) {
            Log::error('Withdrawal status check failed', [
                'order_id' => $request->input('order_id'),
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check withdrawal status'
            ], 500);
        }
    }

    /**
     * Get withdrawal history for user
     */
    public function withdrawalHistory(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->get('per_page', 15);

        $withdrawals = Transaction::where('user_id', $user->id)
            ->where('type', Transaction::TYPE_WITHDRAWAL)
            ->with([
                'wallet' => function ($query) {
                    $query->select('id', 'currency', 'name');
                }
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return view('finance.withdrawal-history', compact('withdrawals', 'user'));
    }

    /**
     * Get withdrawal limits and fees for a wallet
     */
    public function getWithdrawalInfo(CryptoWallet $wallet)
    {
        if ($wallet->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $crypto = $wallet->cryptocurrency;

        return response()->json([
            'currency' => $wallet->currency,
            'balance' => $wallet->balance,
            'formatted_balance' => number_format($wallet->balance, 8),
            'min_withdrawal' => $crypto->min_withdrawal,
            'max_withdrawal' => $crypto->max_withdrawal,
            'withdrawal_fee' => $crypto->withdrawal_fee,
            'max_available' => max(0, $wallet->balance - $crypto->withdrawal_fee),
            'has_address' => $wallet->hasAddress(),
            'wallet_address' => $wallet->address,
            'network' => $crypto->network,
            'fees_info' => [
                'withdrawal_fee' => $crypto->withdrawal_fee,
                'fee_currency' => $wallet->currency,
                'total_deduction_example' => $crypto->withdrawal_fee + $crypto->min_withdrawal
            ]
        ]);
    }

    /**
     * Estimate withdrawal fees
     */
    public function estimateWithdrawalFee(Request $request, CryptoWallet $wallet)
    {
        if ($wallet->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.00000001'
        ]);

        $amount = $request->amount;
        $crypto = $wallet->cryptocurrency;
        $fee = $crypto->withdrawal_fee;
        $totalDeduction = $amount + $fee;

        return response()->json([
            'withdrawal_amount' => $amount,
            'withdrawal_fee' => $fee,
            'total_deduction' => $totalDeduction,
            'remaining_balance' => max(0, $wallet->balance - $totalDeduction),
            'can_withdraw' => $wallet->balance >= $totalDeduction && $amount >= $crypto->min_withdrawal,
            'errors' => $this->getWithdrawalValidationErrors($wallet, $amount, $crypto)
        ]);
    }

    /**
     * Cancel pending withdrawal (if supported)
     */
    public function cancelWithdrawal(Request $request)
    {
        $orderId = $request->input('order_id');

        if (empty($orderId)) {
            return response()->json([
                'success' => false,
                'message' => 'Order ID is required'
            ], 400);
        }

        $transaction = Transaction::where('transaction_id', $orderId)
            ->where('type', Transaction::TYPE_WITHDRAWAL)
            ->where('user_id', Auth::id())
            ->where('status', Transaction::STATUS_PENDING)
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Withdrawal not found or cannot be cancelled'
            ], 404);
        }

        try {
            return DB::transaction(function () use ($transaction) {
                // Get wallet
                $wallet = CryptoWallet::find($transaction->metadata['wallet_id']);

                if (!$wallet) {
                    throw new Exception('Wallet not found');
                }

                // Refund the amount
                $refundAmount = $transaction->metadata['total_deducted'] ?? ($transaction->amount + ($transaction->metadata['withdrawal_fee'] ?? 0));
                $wallet->increment('balance', $refundAmount);

                // Update transaction status
                $transaction->update([
                    'status' => Transaction::STATUS_CANCELLED,
                    'metadata' => array_merge($transaction->metadata, [
                        'cancelled_at' => now()->toISOString(),
                        'cancelled_by' => Auth::id(),
                        'refunded_amount' => $refundAmount,
                        'balance_after_refund' => $wallet->fresh()->balance
                    ])
                ]);

                Log::info('Withdrawal cancelled and refunded', [
                    'transaction_id' => $transaction->id,
                    'order_id' => $transaction->transaction_id,
                    'user_id' => Auth::id(),
                    'refunded_amount' => $refundAmount
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Withdrawal cancelled successfully. Funds have been refunded to your wallet.',
                    'refunded_amount' => $refundAmount
                ]);
            });

        } catch (Exception $e) {
            Log::error('Withdrawal cancellation failed', [
                'order_id' => $orderId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel withdrawal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle wallet active status
     */
    public function toggle(CryptoWallet $wallet)
    {
        if ($wallet->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $wallet->update(['is_active' => !$wallet->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Wallet status updated successfully!',
            'is_active' => $wallet->is_active
        ]);
    }

    /**
     * Bulk withdrawal status check (for admin or batch processing)
     */
    public function bulkWithdrawalStatus(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array|max:50',
            'order_ids.*' => 'required|string'
        ]);

        $orderIds = $request->input('order_ids');
        $user = Auth::user();

        $transactions = Transaction::whereIn('transaction_id', $orderIds)
            ->where('type', Transaction::TYPE_WITHDRAWAL)
            ->where('user_id', $user->id)
            ->get(['transaction_id', 'status', 'amount', 'currency', 'crypto_txid', 'processed_at', 'metadata']);

        $results = [];
        foreach ($orderIds as $orderId) {
            $transaction = $transactions->firstWhere('transaction_id', $orderId);

            if ($transaction) {
                $results[$orderId] = [
                    'status' => $transaction->status,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'crypto_txid' => $transaction->crypto_txid,
                    'processed_at' => $transaction->processed_at,
                    'explorer_url' => $transaction->metadata['explorer_url'] ?? null
                ];
            } else {
                $results[$orderId] = [
                    'status' => 'not_found',
                    'error' => 'Withdrawal not found'
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    }

    /**
     * Get user's total withdrawal stats
     */
    public function getWithdrawalStats()
    {
        $user = Auth::user();

        $stats = Transaction::where('user_id', $user->id)
            ->where('type', Transaction::TYPE_WITHDRAWAL)
            ->selectRaw('
                currency,
                COUNT(*) as total_withdrawals,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_withdrawals,
                SUM(CASE WHEN status = ? THEN amount ELSE 0 END) as total_withdrawn_amount,
                MAX(created_at) as last_withdrawal_date
            ', [Transaction::STATUS_COMPLETED, Transaction::STATUS_COMPLETED])
            ->groupBy('currency')
            ->get();

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'summary' => [
                'total_withdrawal_requests' => $stats->sum('total_withdrawals'),
                'total_completed' => $stats->sum('completed_withdrawals'),
                'currencies_withdrawn' => $stats->count()
            ]
        ]);
    }

    /**
     * Generate unique order ID for payment - FIXED VERSION
     * Format: SHORTCODE_USERID_TIMESTAMP
     * Examples: UB_123_1234567890, BT_456_1234567891
     */
    private function generateOrderId(CryptoWallet $wallet): string
    {
        $shortCode = $this->getCurrencyShortCode($wallet->currency);

        return sprintf(
            '%s_%s_%s',
            $shortCode,
            Auth::id(),
            time()
        );
    }

    /**
     * Generate unique order ID for withdrawal
     * Format: WD_SHORTCODE_USERID_TIMESTAMP
     * Examples: WD_UB_123_1234567890, WD_BT_456_1234567891
     */
    private function generateWithdrawalOrderId(CryptoWallet $wallet): string
    {
        $shortCode = $this->getCurrencyShortCode($wallet->currency);

        return sprintf(
            '%s_%s_%s',
            $shortCode,
            Auth::id(),
            time()
        );
    }

    /**
     * Get currency short code
     */
    private function getCurrencyShortCode(string $currency): string
    {
        return self::CURRENCY_SHORT_CODES[$currency] ?? 'UK'; // UK = Unknown
    }

    /**
     * Get full currency from short code
     */
    private function getFullCurrencyFromShortCode(string $shortCode): ?string
    {
        $mapping = array_flip(self::CURRENCY_SHORT_CODES);
        return $mapping[$shortCode] ?? null;
    }

    /**
     * Create payment record for tracking
     */
    private function createPaymentRecord(CryptoWallet $wallet, float $amount, string $orderId, array $paymentData): void
    {
        try {
            // Create pending transaction record
            $transaction = Transaction::create([
                'user_id' => Auth::id(),
                'transaction_id' => $orderId,
                'type' => Transaction::TYPE_DEPOSIT,
                'amount' => $amount,
                'currency' => $wallet->currency,
                'status' => Transaction::STATUS_PENDING,
                'payment_method' => 'plisio',
                'crypto_address' => $paymentData['address'] ?? null,
                'description' => "Pending crypto deposit - {$wallet->currency} via Plisio Gateway",
                'metadata' => [
                    'payment_data' => $paymentData,
                    'wallet_id' => $wallet->id,
                    'gateway' => 'plisio',
                    'txn_id' => $paymentData['txn_id'] ?? null,
                    'generated_at' => now()->toISOString()
                ],
                'processed_at' => null // Will be set when confirmed
            ]);

            Log::info('Payment record created', [
                'transaction_id' => $transaction->id,
                'user_id' => Auth::id(),
                'wallet_id' => $wallet->id,
                'currency' => $wallet->currency,
                'amount' => $amount,
                'order_id' => $orderId,
                'payment_type' => $paymentData['type'] ?? 'unknown',
                'payment_address' => $paymentData['address'] ?? null,
                'payment_url' => $paymentData['url'] ?? null
            ]);

        } catch (Exception $e) {
            Log::error('Failed to create payment record', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Don't throw exception here as payment generation was successful
            // Just log the error for debugging
        }
    }

    /**
     * Get withdrawal validation errors
     */
    private function getWithdrawalValidationErrors(CryptoWallet $wallet, float $amount, Cryptocurrency $crypto): array
    {
        $errors = [];
        $fee = $crypto->withdrawal_fee;
        $totalDeduction = $amount + $fee;

        if ($amount < $crypto->min_withdrawal) {
            $errors[] = "Minimum withdrawal amount is {$crypto->min_withdrawal} {$wallet->currency}";
        }

        if ($crypto->max_withdrawal && $amount > $crypto->max_withdrawal) {
            $errors[] = "Maximum withdrawal amount is {$crypto->max_withdrawal} {$wallet->currency}";
        }

        if ($wallet->balance < $totalDeduction) {
            $errors[] = "Insufficient balance. You need at least {$totalDeduction} {$wallet->currency} (including {$fee} {$wallet->currency} fee)";
        }

        if (!$wallet->hasAddress()) {
            $errors[] = "Please set your withdrawal address first";
        }

        return $errors;
    }

    /**
     * Validate cryptocurrency address format
     */
    private function validateCryptoAddress(string $address, Cryptocurrency $crypto): bool
    {
        // Basic validation patterns for different cryptocurrencies
        $patterns = [
            'Bitcoin' => '/^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$|^bc1[a-z0-9]{39,59}$/',
            'Ethereum' => '/^0x[a-fA-F0-9]{40}$/',
            'BSC' => '/^0x[a-fA-F0-9]{40}$/',
            'TRON' => '/^T[A-Za-z1-9]{33}$/',
            'Cardano' => '/^addr1[a-zA-Z0-9]{98}$/',
            'Solana' => '/^[1-9A-HJ-NP-Za-km-z]{32,44}$/',
            'Polygon' => '/^0x[a-fA-F0-9]{40}$/',
            'Litecoin' => '/^[LM3][a-km-zA-HJ-NP-Z1-9]{26,33}$|^ltc1[a-z0-9]{39,59}$/',
        ];

        $pattern = $patterns[$crypto->network] ?? $patterns['Ethereum'];

        return preg_match($pattern, $address) === 1;
    }

    /**
     * Get real-time crypto prices (placeholder)
     * You should integrate with a real crypto price API like CoinGecko, CoinMarketCap, etc.
     */
    public function updateCryptoPrices()
    {
        $wallets = CryptoWallet::with('cryptocurrency')->get();

        foreach ($wallets as $wallet) {
            // Placeholder - integrate with real API
            $price = $this->getCryptoPrice($wallet->currency);
            $wallet->update(['usd_rate' => $price]);
        }

        return response()->json(['message' => 'Prices updated successfully']);
    }

    /**
     * Get crypto price from external API (placeholder)
     */
    private function getCryptoPrice(string $symbol): float
    {
        // Placeholder prices - integrate with real API
        $prices = [
            'BTC' => 45000,
            'ETH' => 3000,
            'USDT_BEP20' => 1.00,
            'USDT_TRC20' => 1.00,
            'USDT_ERC20' => 1.00,
            'BNB' => 400,
            'ADA' => 0.50,
            'SOL' => 100,
            'MATIC' => 0.80,
            'LTC' => 100,
            'DOGE' => 0.08,
        ];

        return $prices[$symbol] ?? 1.00;
    }
}