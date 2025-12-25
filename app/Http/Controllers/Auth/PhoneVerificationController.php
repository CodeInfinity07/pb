<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppMessage;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PhoneVerificationController extends Controller
{
    // WhatsApp verification number - update this to your Green API number
    const WHATSAPP_VERIFICATION_NUMBER = '923196783934';

    /**
     * Show the phone verification form
     */
    public function show(): View
    {
        $user = Auth::user();
        
        // Ensure user has a profile
        $this->ensureUserProfile($user);
        
        // Generate verification code if not in session or expired
        if (!$this->hasValidVerificationCode()) {
            $this->generateVerificationCode();
        }

        return view('auth.verify-phone', [
            'phone' => $user->phone,
            'phone_verified' => $user->profile->phone_verified ?? false,
            'verification_code' => session('phone_verification_code'),
            'whatsapp_number' => self::WHATSAPP_VERIFICATION_NUMBER,
            'code_expires_at' => session('phone_verification_expires') 
                ? session('phone_verification_expires')->toISOString() 
                : null
        ]);
    }

    /**
     * Generate new verification code (AJAX)
     */
    public function generateCode(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $code = $this->generateVerificationCode();
            
            Log::info('New verification code generated', [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'code' => config('app.debug') ? $code : '***masked***'
            ]);
            
            return response()->json([
                'success' => true,
                'code' => $code,
                'expires_at' => session('phone_verification_expires')->toISOString(),
                'message' => 'New verification code generated successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to generate verification code', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate verification code. Please try again.'
            ], 500);
        }
    }

    /**
     * Verify the phone with WhatsApp message (Manual verification)
     */
    public function verify(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $sessionCode = session('phone_verification_code');
            $expiresAt = session('phone_verification_expires');

            // Validate session data
            if (!$sessionCode || !$expiresAt || now()->gt($expiresAt)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verification code has expired. Please generate a new one.'
                ]);
            }

            // Ensure user has profile
            $this->ensureUserProfile($user);

            // Check if already verified
            if ($user->profile->phone_verified) {
                Log::info('Manual verification attempted on already verified phone', [
                    'user_id' => $user->id,
                    'verified_at' => $user->profile->phone_verified_at
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Phone number is already verified!',
                    'already_verified' => true,
                    'verified_at' => $user->profile->phone_verified_at,
                    'redirect' => route('dashboard')
                ]);
            }

            // Extract last 7 digits from user's phone number
            $last7Digits = $this->extractLast7Digits($user->phone);
            
            if (strlen($last7Digits) < 7) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid phone number format. Please update your phone number.'
                ]);
            }
            
            Log::info('Starting manual phone verification process', [
                'user_id' => $user->id,
                'user_phone' => $user->phone,
                'last_7_digits' => $last7Digits,
                'session_code' => config('app.debug') ? $sessionCode : '***masked***'
            ]);
            
            // Look for recent WhatsApp messages containing the verification code
            $recentMessage = $this->findVerificationMessage($last7Digits, $sessionCode);

            if (!$recentMessage) {
                $this->logVerificationFailure($user, $last7Digits, $sessionCode);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Verification code not found in recent messages. Please make sure you sent the correct code via WhatsApp.'
                ]);
            }

            // Update user profile with verification status
            $this->updateVerificationStatus($user, $recentMessage);

            // Clear verification session data
            $this->clearVerificationSession();

            Log::info('Phone verified successfully via manual verification', [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'message_id' => $recentMessage->id,
                'verified_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Phone verified successfully!',
                'verified_at' => now()->toISOString(),
                'redirect' => route('dashboard')
            ]);

        } catch (\Exception $e) {
            Log::error('Manual phone verification error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Verification failed due to system error. Please try again.'
            ], 500);
        }
    }

    /**
     * Check verification status (AJAX polling) - Auto-completes verification
     */
    public function checkStatus(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $sessionCode = session('phone_verification_code');
            $expiresAt = session('phone_verification_expires');
            
            if (!$sessionCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'No verification code found'
                ]);
            }

            // Check if code has expired
            if (!$expiresAt || now()->gt($expiresAt)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verification code has expired',
                    'code_expired' => true
                ]);
            }

            // Ensure user has profile
            $this->ensureUserProfile($user);

            // Check if already verified
            if ($user->profile->phone_verified) {
                return response()->json([
                    'success' => true,
                    'message_received' => true,
                    'already_verified' => true,
                    'verified_at' => $user->profile->phone_verified_at
                ]);
            }

            $last7Digits = $this->extractLast7Digits($user->phone);
            $recentMessage = $this->findVerificationMessage($last7Digits, $sessionCode);

            $debugInfo = null;
            if (config('app.debug')) {
                $debugInfo = [
                    'user_phone' => $user->phone,
                    'last_7_digits' => $last7Digits,
                    'session_code' => $sessionCode,
                    'phone_verified' => $user->profile->phone_verified ?? false,
                    'phone_verified_at' => $user->profile->phone_verified_at ?? null,
                    'recent_messages_count' => WhatsAppMessage::incoming()->recent(10)->count(),
                    'matching_phone_count' => WhatsAppMessage::incoming()
                        ->recent(10)
                        ->where('user_phone', 'like', "%{$last7Digits}%")
                        ->count(),
                    'message_found' => $recentMessage ? [
                        'id' => $recentMessage->id,
                        'phone' => $recentMessage->user_phone,
                        'text' => substr($recentMessage->message_text, 0, 50) . '...',
                        'created_at' => $recentMessage->created_at
                    ] : null
                ];
            }

            // If message found, automatically complete verification
            if ($recentMessage) {
                try {
                    Log::info('Auto-completing phone verification via status check', [
                        'user_id' => $user->id,
                        'message_id' => $recentMessage->id
                    ]);

                    // Update user profile with verification status
                    $this->updateVerificationStatus($user, $recentMessage);

                    // Clear verification session data
                    $this->clearVerificationSession();

                    Log::info('Phone auto-verified successfully via status check', [
                        'user_id' => $user->id,
                        'phone' => $user->phone,
                        'message_id' => $recentMessage->id,
                        'verified_at' => now()
                    ]);

                    return response()->json([
                        'success' => true,
                        'message_received' => true,
                        'auto_verified' => true,
                        'verified_at' => now()->toISOString(),
                        'message' => 'Phone verified successfully!',
                        'redirect' => route('dashboard'),
                        'debug' => $debugInfo
                    ]);

                } catch (\Exception $e) {
                    Log::error('Auto-verification failed in status check', [
                        'user_id' => $user->id,
                        'message_id' => $recentMessage->id,
                        'error' => $e->getMessage()
                    ]);

                    return response()->json([
                        'success' => false,
                        'message_received' => true,
                        'auto_verification_failed' => true,
                        'message' => 'Message received but verification failed. Please try manual verification.',
                        'debug' => $debugInfo
                    ]);
                }
            }

            // No message found yet
            return response()->json([
                'success' => true,
                'message_received' => false,
                'code' => $sessionCode,
                'expires_at' => $expiresAt,
                'debug' => $debugInfo
            ]);

        } catch (\Exception $e) {
            Log::error('Check status error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error checking verification status'
            ], 500);
        }
    }

    /**
     * Update phone number
     */
    public function updatePhone(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'phone' => [
                    'required', 
                    'string', 
                    'max:20', 
                    'unique:users,phone,' . Auth::id(),
                    'regex:/^(\+92|92|03)\d{9,10}$/'
                ]
            ], [
                'phone.regex' => 'Please enter a valid Pakistani phone number (e.g., +923001234567 or 03001234567)'
            ]);

            $user = Auth::user();
            $oldPhone = $user->phone;
            $cleanPhone = $this->cleanPhoneNumber($request->phone);

            // Update user phone number
            $user->update(['phone' => $cleanPhone]);
            
            // Ensure user has profile and reset verification status
            $this->ensureUserProfile($user);
            $this->resetVerificationStatus($user);
            
            // Generate new verification code
            $this->generateVerificationCode();

            Log::info('Phone number updated successfully', [
                'user_id' => $user->id,
                'old_phone' => $oldPhone,
                'new_phone' => $cleanPhone
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Phone number updated successfully',
                'phone' => $cleanPhone
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Phone update error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update phone number. Please try again.'
            ], 500);
        }
    }

    /**
     * Generate verification code and store in session
     */
    private function generateVerificationCode(): string
    {
        $code = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(10);
        
        session([
            'phone_verification_code' => $code,
            'phone_verification_expires' => $expiresAt
        ]);

        Log::info('Verification code generated', [
            'user_id' => Auth::id(),
            'expires_at' => $expiresAt->toISOString(),
            'code' => config('app.debug') ? $code : '***masked***'
        ]);

        return $code;
    }

    /**
     * Check if current verification code is valid
     */
    private function hasValidVerificationCode(): bool
    {
        $code = session('phone_verification_code');
        $expiresAt = session('phone_verification_expires');
        
        return $code && $expiresAt && now()->lte($expiresAt);
    }

    /**
     * Extract last 7 digits from phone number
     */
    private function extractLast7Digits(string $phone): string
    {
        $digits = preg_replace('/[^\d]/', '', $phone);
        return substr($digits, -7);
    }

    /**
     * Find verification message in WhatsApp messages
     */
    private function findVerificationMessage(string $last7Digits, string $sessionCode): ?WhatsAppMessage
    {
        return WhatsAppMessage::incoming()
            ->recent(10) // Within last 10 minutes
            ->where('user_phone', 'like', "%{$last7Digits}%")
            ->where('message_text', 'like', "%{$sessionCode}%")
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Log verification failure details
     */
    private function logVerificationFailure($user, string $last7Digits, string $sessionCode): void
    {
        $recentCount = WhatsAppMessage::incoming()->recent(10)->count();
        $matchingPhoneCount = WhatsAppMessage::incoming()
            ->recent(10)
            ->where('user_phone', 'like', "%{$last7Digits}%")
            ->count();
        $matchingCodeCount = WhatsAppMessage::incoming()
            ->recent(10)
            ->where('message_text', 'like', "%{$sessionCode}%")
            ->count();

        Log::warning('Verification message not found', [
            'user_id' => $user->id,
            'user_phone' => $user->phone,
            'last_7_digits' => $last7Digits,
            'session_code' => config('app.debug') ? $sessionCode : '***masked***',
            'recent_messages_count' => $recentCount,
            'matching_phone_count' => $matchingPhoneCount,
            'matching_code_count' => $matchingCodeCount
        ]);
    }

    /**
     * Update user profile with verification status
     */
    private function updateVerificationStatus($user, WhatsAppMessage $message): void
    {
        $verificationTime = now();
        
        try {
            // Method 1: Eloquent update
            $updated = $user->profile()->update([
                'phone_verified' => 1,
                'phone_verified_at' => $verificationTime
            ]);

            if (!$updated) {
                // Method 2: Direct database update
                DB::table('user_profiles')
                    ->where('user_id', $user->id)
                    ->update([
                        'phone_verified' => 1,
                        'phone_verified_at' => $verificationTime,
                        'updated_at' => now()
                    ]);
            }

            // Refresh the relationship
            $user->load('profile');

            Log::info('Phone verification status updated', [
                'user_id' => $user->id,
                'verification_time' => $verificationTime,
                'message_id' => $message->id,
                'update_method' => $updated ? 'eloquent' : 'direct_db'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update verification status', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Reset verification status when phone is changed
     */
    private function resetVerificationStatus($user): void
    {
        try {
            $user->profile()->update([
                'phone_verified' => 0,
                'phone_verified_at' => null
            ]);

            Log::info('Phone verification status reset', [
                'user_id' => $user->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to reset verification status', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Ensure user has a profile record
     */
    private function ensureUserProfile($user): void
    {
        if (!$user->profile) {
            Log::info('Creating missing profile for user', ['user_id' => $user->id]);
            
            UserProfile::create([
                'user_id' => $user->id,
                'phone_verified' => 0,
                'phone_verified_at' => null,
                'kyc_status' => 'pending',
                'email_notifications' => true,
                'sms_notifications' => false,
                'preferred_language' => 'en',
                'timezone' => 'UTC',
                'two_factor_enabled' => false,
            ]);

            // Refresh the relationship
            $user->load('profile');
        }
    }

    /**
     * Clear verification session data
     */
    private function clearVerificationSession(): void
    {
        session()->forget([
            'phone_verification_code',
            'phone_verification_expires'
        ]);
    }

    /**
     * Clean and format phone number
     */
    private function cleanPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $phone);
        
        // Pakistani number formatting
        if (str_starts_with($cleaned, '+92')) {
            return $cleaned;
        } elseif (str_starts_with($cleaned, '92')) {
            return '+' . $cleaned;
        } elseif (str_starts_with($cleaned, '03')) {
            return '+92' . substr($cleaned, 1);
        } elseif (str_starts_with($cleaned, '3') && strlen($cleaned) === 10) {
            return '+92' . $cleaned;
        } elseif (str_starts_with($cleaned, '+')) {
            return $cleaned;
        } else {
            // Default to Pakistani format
            return '+92' . $cleaned;
        }
    }
}