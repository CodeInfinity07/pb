<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\User;
use App\Models\Transaction;
use App\Models\CryptoWallet;
use App\Models\KycVerification;
class KycController extends Controller
{
    /**
     * Show KYC verification page
     */
    public function index(): View
    {
        $user = auth()->user();

        // Get status directly from profile, not from User accessor
        $kycStatus = $user->profile?->kyc_status ?? 'pending';
        $latestVerification = $user->latestKycVerification;

        // Check if user has minimum balance for KYC fee (skip if already verified or under review)
        if (in_array($kycStatus, ['pending', 'rejected', 'session_created'])) {
            $hasMinBalance = $this->hasMinimumBalance($user);

            if (!$hasMinBalance) {
                // Get user's total balance
                $totalUsdValue = $this->calculateTotalUsdBalance($user);

                // Show insufficient balance page
                return view('kyc.insufficient-balance', [
                    'user' => $user,
                    'requiredAmount' => 1.0,
                    'currentBalance' => $totalUsdValue,
                    'shortfall' => max(0, 1.0 - $totalUsdValue)
                ]);
            }
        }

        return view('kyc.index', [
            'user' => $user,
            'kycStatus' => $kycStatus, // This now comes from profile, not the accessor
            'latestVerification' => $latestVerification,
            'veriffApiKey' => config('services.veriff.api_key')
        ]);
    }

    /**
     * Create Veriff session (don't set status to submitted here)
     */
    public function createSession(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $apiKey = config('services.veriff.api_key');

            if (empty($apiKey)) {
                Log::error('Veriff API key not configured');
                return response()->json([
                    'success' => false,
                    'message' => 'Verification service not configured'
                ], 500);
            }

            // Callback URL - where to redirect user after verification (frontend page)
            $callbackUrl = config('app.url') . '/kyc/complete';

            $firstName = $user->first_name;
            $lastName = $user->last_name;

            // Prepare payload for Veriff API
            $payload = [
                'verification' => [
                    'callback' => $callbackUrl,
                    'person' => [
                        'firstName' => $firstName,
                        'lastName' => $lastName
                    ],
                    'vendorData' => (string) $user->id
                ]
            ];

            Log::info('Creating Veriff session', [
                'user_id' => $user->id,
                'payload' => $payload,
                'api_key_prefix' => substr($apiKey, 0, 8) . '...'
            ]);

            // Make request to Veriff API
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-AUTH-CLIENT' => $apiKey
                ])
                ->post('https://stationapi.veriff.com/v1/sessions', $payload);

            if ($response->failed()) {
                Log::error('Veriff session creation failed', [
                    'user_id' => $user->id,
                    'http_code' => $response->status(),
                    'response' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create verification session',
                    'debug' => [
                        'http_code' => $response->status(),
                        'response' => $response->json()
                    ]
                ], 500);
            }

            $sessionData = $response->json();

            if (!isset($sessionData['verification'])) {
                Log::error('Invalid Veriff response format', [
                    'user_id' => $user->id,
                    'response' => $sessionData
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid response format'
                ], 500);
            }

            // Store session ID and mark as session created (NOT submitted yet)
            if ($user->profile) {
                $updateData = [
                    'kyc_session_id' => $sessionData['verification']['id'] ?? null,
                ];

                // Only update status if it's in a state that allows session creation
                $currentStatus = $user->profile->kyc_status;
                if (in_array($currentStatus, ['pending', 'rejected'])) {
                    $updateData['kyc_status'] = 'session_created';
                    $updateData['kyc_session_created_at'] = now();
                }

                $user->profile->update($updateData);
            }

            Log::info('Veriff session created successfully', [
                'user_id' => $user->id,
                'session_id' => $sessionData['verification']['id'] ?? 'unknown',
                'session_url' => $sessionData['verification']['url'] ?? 'unknown'
            ]);

            return response()->json([
                'success' => true,
                'session' => $sessionData,
                'url' => $sessionData['verification']['url'] ?? '',
                'sessionId' => $sessionData['verification']['id'] ?? ''
            ]);

        } catch (Exception $e) {
            Log::error('KYC session creation failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'System error occurred'
            ], 500);
        }
    }

    /**
     * Mark KYC as submitted and deduct fee (only when user actually submits documents)
     */
    public function start(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user->profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'User profile not found'
                ], 404);
            }

            $currentStatus = $user->profile->kyc_status;

            // Check minimum balance before processing
            if (!$this->hasMinimumBalance($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance. You need at least $1 to complete KYC verification.'
                ], 400);
            }

            // Only update to submitted if current status allows it
            if (in_array($currentStatus, ['pending', 'rejected', 'session_created'])) {
                // Deduct KYC fee
                $feeDeducted = $this->deductKYCFee($user);

                if (!$feeDeducted) {
                    Log::error('KYC fee deduction failed', [
                        'user_id' => $user->id,
                        'current_status' => $currentStatus
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to process KYC fee. Please try again.'
                    ], 500);
                }

                // Update status to submitted
                $user->profile->update([
                    'kyc_status' => 'submitted',
                    'kyc_submitted_at' => now()
                ]);

                Log::info('KYC status updated to submitted with fee deducted', [
                    'user_id' => $user->id,
                    'previous_status' => $currentStatus
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Verification started and fee processed',
                    'fee_deducted' => true
                ]);
            } else {
                Log::info('KYC status update skipped - invalid current status', [
                    'user_id' => $user->id,
                    'current_status' => $currentStatus
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update status from current state',
                    'current_status' => $currentStatus
                ], 400);
            }

        } catch (Exception $e) {
            Log::error('KYC start failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start verification'
            ], 500);
        }
    }

    /**
     * Cancel verification session (reset from session_created back to pending)
     */
    public function cancelSession(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user->profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'User profile not found'
                ], 404);
            }

            // Only cancel if status is session_created
            if ($user->profile->kyc_status === 'session_created') {
                $user->profile->update([
                    'kyc_status' => 'pending',
                    'kyc_session_id' => null,
                    'kyc_session_created_at' => null
                ]);

                Log::info('KYC session canceled', ['user_id' => $user->id]);

                return response()->json([
                    'success' => true,
                    'message' => 'Session canceled'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No active session to cancel'
            ], 400);

        } catch (Exception $e) {
            Log::error('KYC session cancellation failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel session'
            ], 500);
        }
    }

    /**
     * Handle Veriff webhook with proper security verification
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            // Get raw body for signature verification
            $rawPayload = $request->getContent();

            // Verify webhook authenticity
            if (!$this->verifyWebhookSignature($request, $rawPayload)) {
                Log::warning('Invalid webhook signature', [
                    'headers' => $request->headers->all(),
                    'ip' => $request->ip()
                ]);
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Parse the verified payload
            $payload = json_decode($rawPayload, true);

            if (!$payload) {
                Log::warning('Invalid JSON payload', ['raw_payload' => $rawPayload]);
                return response()->json(['error' => 'Invalid payload'], 400);
            }

            Log::info('Verified Veriff webhook received', $payload);

            // Extract user ID and validate
            $userId = $payload['vendorData'] ?? null;
            if (!$userId) {
                Log::warning('Missing vendorData in webhook', $payload);
                return response()->json(['error' => 'Missing user ID'], 400);
            }

            $user = User::find($userId);
            if (!$user) {
                Log::error('User not found for KYC webhook', ['user_id' => $userId]);
                return response()->json(['error' => 'User not found'], 404);
            }

            // Create KYC verification record
            $kycVerification = KycVerification::createFromWebhook($payload, $user->id);

            // Update user profile for backward compatibility
            $this->updateUserProfileFromVerification($user, $kycVerification, $payload);

            // Send notifications based on verification status
            try {
                if ($kycVerification->isApproved()) {
                    $user->notify(
                        \App\Notifications\UnifiedNotification::kycApproved()
                    );

                    Log::info('KYC approved notification sent', [
                        'user_id' => $user->id,
                        'kyc_verification_id' => $kycVerification->id
                    ]);
                } elseif ($kycVerification->isDeclined()) {
                    $rejectionReason = $kycVerification->rejection_reason ?? 'Verification failed. Please try again with valid documents.';

                    $user->notify(
                        \App\Notifications\UnifiedNotification::kycRejected($rejectionReason)
                    );

                    Log::info('KYC rejected notification sent', [
                        'user_id' => $user->id,
                        'kyc_verification_id' => $kycVerification->id,
                        'reason' => $rejectionReason
                    ]);
                }
            } catch (Exception $notificationError) {
                Log::error('KYC notification failed', [
                    'user_id' => $user->id,
                    'kyc_verification_id' => $kycVerification->id,
                    'verification_status' => $kycVerification->status,
                    'error' => $notificationError->getMessage()
                ]);
            }

            Log::info('KYC verification processed successfully', [
                'user_id' => $userId,
                'session_id' => $kycVerification->session_id,
                'status' => $kycVerification->status,
                'decision' => $kycVerification->decision,
                'kyc_verification_id' => $kycVerification->id
            ]);

            return response()->json(['message' => 'Webhook processed successfully'], 200);

        } catch (Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->getContent()
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Get KYC status
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $latestVerification = $user->latestKycVerification;

            return response()->json([
                'success' => true,
                'kyc_status' => $user->kyc_status,
                'is_kyc_verified' => $user->isKycVerified(),
                'latest_verification' => $latestVerification ? [
                    'id' => $latestVerification->id,
                    'session_id' => $latestVerification->session_id,
                    'status' => $latestVerification->status,
                    'decision' => $latestVerification->decision,
                    'decision_score' => $latestVerification->decision_score,
                    'verified_at' => $latestVerification->verified_at,
                    'verified_full_name' => $latestVerification->verified_full_name,
                    'document_type_display' => $latestVerification->document_type_display,
                    'document_country_name' => $latestVerification->document_country_name,
                    'rejection_reason' => $latestVerification->rejection_reason,
                ] : null,
                'profile' => [
                    'kyc_status' => $user->profile?->kyc_status ?? 'pending',
                    'kyc_submitted_at' => $user->profile?->kyc_submitted_at,
                    'kyc_verified_at' => $user->profile?->kyc_verified_at,
                    'kyc_session_created_at' => $user->profile?->kyc_session_created_at,
                    'session_id' => $user->profile?->kyc_session_id
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get KYC status', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get status'
            ], 500);
        }
    }

    /**
     * Get KYC verification history
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $verifications = $user->kycVerifications()
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($verification) {
                    return [
                        'id' => $verification->id,
                        'session_id' => $verification->session_id,
                        'status' => $verification->status,
                        'decision' => $verification->decision,
                        'decision_display' => $verification->decision_display,
                        'decision_score' => $verification->decision_score,
                        'verified_at' => $verification->verified_at,
                        'formatted_verification_time' => $verification->formatted_verification_time,
                        'verified_full_name' => $verification->verified_full_name,
                        'document_type_display' => $verification->document_type_display,
                        'document_country_name' => $verification->document_country_name,
                        'status_badge_class' => $verification->status_badge_class,
                        'is_approved' => $verification->isApproved(),
                        'is_declined' => $verification->isDeclined(),
                        'is_pending' => $verification->isPending(),
                        'rejection_reason' => $verification->rejection_reason,
                        'created_at' => $verification->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'verifications' => $verifications,
                'total_count' => $verifications->count(),
                'approved_count' => $verifications->where('is_approved', true)->count(),
                'declined_count' => $verifications->where('is_declined', true)->count(),
                'pending_count' => $verifications->where('is_pending', true)->count(),
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get KYC history', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get verification history'
            ], 500);
        }
    }

    /**
     * KYC completion callback page
     */
    public function complete(Request $request): View
    {
        $user = auth()->user();
        $kycStatus = $user->kyc_status ?? 'pending';
        $latestVerification = $user->latestKycVerification;
        $veriffParams = $request->query();

        return view('kyc.complete', [
            'user' => $user,
            'kycStatus' => $kycStatus,
            'latestVerification' => $latestVerification,
            'veriffParams' => $veriffParams
        ]);
    }

    /**
     * Test Veriff API connection
     */
    public function testConnection(Request $request): JsonResponse
    {
        try {
            $apiKey = config('services.veriff.api_key');

            if (empty($apiKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'API key not configured'
                ]);
            }

            $callbackUrl = config('app.url') . '/kyc/complete';

            $payload = [
                'verification' => [
                    'callback' => $callbackUrl,
                    'person' => [
                        'firstName' => 'Test',
                        'lastName' => 'User'
                    ],
                    'vendorData' => 'test-' . time()
                ]
            ];

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-AUTH-CLIENT' => $apiKey
                ])
                ->post('https://stationapi.veriff.com/v1/sessions', $payload);

            return response()->json([
                'success' => $response->successful(),
                'http_code' => $response->status(),
                'response' => $response->json(),
                'api_key_configured' => !empty($apiKey),
                'api_key_prefix' => substr($apiKey, 0, 8) . '...',
                'callback_url' => $callbackUrl,
                'payload_sent' => $payload,
                'note' => 'Webhook URL should be configured in Veriff Customer Portal'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Clean up expired sessions
     */
    public function cleanupExpiredSessions(): JsonResponse
    {
        try {
            $expiredSessions = User::whereHas('profile', function ($query) {
                $query->where('kyc_status', 'session_created')
                    ->where('kyc_session_created_at', '<', now()->subHour());
            })->get();

            $cleanedCount = 0;
            foreach ($expiredSessions as $user) {
                $user->profile->update([
                    'kyc_status' => 'pending',
                    'kyc_session_id' => null,
                    'kyc_session_created_at' => null
                ]);
                $cleanedCount++;
            }

            Log::info('Expired KYC sessions cleaned up', ['count' => $cleanedCount]);

            return response()->json([
                'success' => true,
                'cleaned_sessions' => $cleanedCount
            ]);

        } catch (Exception $e) {
            Log::error('Failed to clean up expired sessions', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Cleanup failed'
            ], 500);
        }
    }

    /**
     * Calculate total USD balance across all wallets
     */
    private function calculateTotalUsdBalance(User $user): float
    {
        $wallets = CryptoWallet::where('user_id', $user->id)
            ->active()
            ->get();

        return $wallets->sum(function ($wallet) {
            return $wallet->balance * $wallet->usd_rate;
        });
    }

    /**
     * Check if user has minimum balance for KYC fee
     */
    private function hasMinimumBalance(User $user, float $requiredAmount = 1.0): bool
    {
        $totalUsdValue = $this->calculateTotalUsdBalance($user);
        return $totalUsdValue >= $requiredAmount;
    }

    /**
     * Deduct KYC fee from user's wallet
     */
    private function deductKYCFee(User $user): bool
    {
        try {
            return DB::transaction(function () use ($user) {
                $feeAmount = 1.0; // $1 USD

                // Get all active wallets with sufficient balance
                $wallets = CryptoWallet::where('user_id', $user->id)
                    ->active()
                    ->get()
                    ->map(function ($wallet) {
                        $wallet->calculated_usd_value = $wallet->balance * $wallet->usd_rate;
                        return $wallet;
                    })
                    ->filter(function ($wallet) use ($feeAmount) {
                        return $wallet->calculated_usd_value >= $feeAmount;
                    })
                    ->sortByDesc(function ($wallet) {
                        // Prioritize USDT wallets
                        return (str_contains($wallet->currency, 'USDT') ? 1000000 : 0) + $wallet->calculated_usd_value;
                    });

                $wallet = $wallets->first();

                if (!$wallet) {
                    Log::error('No wallet found with sufficient balance for KYC fee', [
                        'user_id' => $user->id,
                        'required_amount' => $feeAmount
                    ]);
                    return false;
                }

                // Calculate crypto amount to deduct
                $cryptoAmount = $wallet->usd_rate > 0
                    ? $feeAmount / $wallet->usd_rate
                    : $feeAmount;

                // Capture balance before deduction
                $oldBalance = $wallet->balance;

                // Deduct from wallet
                $wallet->decrement('balance', $cryptoAmount);
                $newBalance = $wallet->fresh()->balance;

                // Create transaction record
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'transaction_id' => 'KYC_FEE_' . $user->id . '_' . time(),
                    'type' => Transaction::TYPE_FEE,
                    'amount' => $cryptoAmount,
                    'currency' => $wallet->currency,
                    'status' => Transaction::STATUS_COMPLETED,
                    'payment_method' => 'wallet_deduction',
                    'description' => 'KYC Verification Fee',
                    'processed_at' => now(),
                    'metadata' => [
                        'wallet_id' => $wallet->id,
                        'fee_type' => 'kyc_verification',
                        'usd_amount' => $feeAmount,
                        'crypto_amount' => $cryptoAmount,
                        'exchange_rate' => $wallet->usd_rate,
                        'old_balance' => $oldBalance,
                        'new_balance' => $newBalance
                    ]
                ]);

                Log::info('KYC fee deducted successfully', [
                    'user_id' => $user->id,
                    'transaction_id' => $transaction->id,
                    'wallet_id' => $wallet->id,
                    'currency' => $wallet->currency,
                    'crypto_amount' => $cryptoAmount,
                    'usd_amount' => $feeAmount,
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance
                ]);

                return true;
            });

        } catch (Exception $e) {
            Log::error('KYC fee deduction failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Update user profile from KYC verification
     */
    private function updateUserProfileFromVerification(User $user, KycVerification $verification, array $webhookPayload): void
    {
        if (!$user->profile) {
            return;
        }

        $kycStatus = $this->mapVerificationToProfileStatus($verification);

        $updateData = [
            'kyc_status' => $kycStatus,
            'kyc_session_id' => $verification->session_id,
        ];

        if (in_array($kycStatus, ['submitted', 'under_review', 'verified']) && !$user->profile->kyc_submitted_at) {
            $updateData['kyc_submitted_at'] = $verification->verified_at ?? now();
        }

        if ($kycStatus === 'verified') {
            $updateData['kyc_verified_at'] = $verification->verified_at ?? now();
        }

        if ($kycStatus === 'rejected') {
            $updateData['kyc_rejection_reason'] = $verification->rejection_reason ?? 'Verification failed - please try again';
        }

        $user->profile->update($updateData);

        Log::info('User profile updated from KYC verification', [
            'user_id' => $user->id,
            'kyc_verification_id' => $verification->id,
            'profile_status' => $kycStatus
        ]);
    }

    /**
     * Map KYC verification to profile status
     */
    private function mapVerificationToProfileStatus(KycVerification $verification): string
    {
        if ($verification->isApproved()) {
            return 'verified';
        }

        if ($verification->isDeclined()) {
            return 'rejected';
        }

        if ($verification->isPending()) {
            return 'under_review';
        }

        return 'under_review';
    }

    /**
     * Verify Veriff webhook signature
     */
    private function verifyWebhookSignature(Request $request, string $rawPayload): bool
    {
        try {
            $apiKey = config('services.veriff.api_key');
            $sharedSecret = config('services.veriff.secret_key');

            $authClient = $request->header('X-AUTH-CLIENT');
            $hmacSignature = $request->header('X-HMAC-SIGNATURE');

            if (!$authClient || !$hmacSignature || !$sharedSecret) {
                Log::warning('Missing required webhook headers or config', [
                    'has_auth_client' => !empty($authClient),
                    'has_hmac_signature' => !empty($hmacSignature),
                    'has_shared_secret' => !empty($sharedSecret)
                ]);
                return false;
            }

            if ($authClient !== $apiKey) {
                Log::warning('Invalid X-AUTH-CLIENT header', [
                    'received' => substr($authClient, 0, 8) . '...',
                    'expected' => substr($apiKey, 0, 8) . '...'
                ]);
                return false;
            }

            $expectedSignature = hash_hmac('sha256', $rawPayload, $sharedSecret);

            if (!hash_equals($expectedSignature, $hmacSignature)) {
                Log::warning('HMAC signature verification failed', [
                    'expected_prefix' => substr($expectedSignature, 0, 8) . '...',
                    'received_prefix' => substr($hmacSignature, 0, 8) . '...'
                ]);
                return false;
            }

            return true;

        } catch (Exception $e) {
            Log::error('Webhook signature verification error', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
;