<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\PhoneVerificationController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Registration routes - using YOUR custom RegisterController
    Route::get('register', [RegisterController::class, 'create'])
                ->name('register');

    Route::post('register', [RegisterController::class, 'store']);

    // AJAX validation routes for registration
    Route::get('register/check-username', [RegisterController::class, 'checkUsername'])
                ->name('register.check-username');
    
    Route::get('register/check-sponsor', [RegisterController::class, 'checkSponsorId'])
                ->name('register.check-sponsor');

    // Login routes
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Password reset routes
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
                ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
                ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
                ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
                ->name('password.store');
});

Route::middleware('auth')->group(function () {
    // Email verification routes
    Route::get('verify-email', EmailVerificationPromptController::class)
                ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
                ->middleware(['signed', 'throttle:6,1'])
                ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
                ->middleware('throttle:6,1')
                ->name('verification.send');

    // AJAX route to check verification status
    Route::get('email/verification-check', [EmailVerificationNotificationController::class, 'check'])
                ->name('verification.check');

    // Phone verification routes
    Route::get('verify-phone', [PhoneVerificationController::class, 'show'])
                ->name('phone.verify');
    
    Route::post('verify-phone/generate', [PhoneVerificationController::class, 'generateCode'])
                ->name('phone.generate-code');
    
    Route::post('verify-phone', [PhoneVerificationController::class, 'verify'])
                ->name('phone.verify.submit');
    
    Route::get('verify-phone/status', [PhoneVerificationController::class, 'checkStatus'])
                ->name('phone.check-status');
    
    Route::post('verify-phone/update', [PhoneVerificationController::class, 'updatePhone'])
                ->name('phone.update');

    // Two-Factor Authentication Challenge Routes (NEW)
    Route::get('two-factor-challenge', [AuthenticatedSessionController::class, 'showTwoFactorChallenge'])
                ->name('two-factor.challenge');
    
    Route::post('two-factor-challenge', [AuthenticatedSessionController::class, 'verifyTwoFactor'])
                ->name('two-factor.verify');
    
    Route::post('two-factor-recovery', [AuthenticatedSessionController::class, 'verifyRecoveryCode'])
                ->name('two-factor.recovery');

    // Password confirmation
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
                ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    // Password update
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    // Logout
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');
});

// Two-Factor Authentication Management Routes (NEW)
// These routes allow users to setup/disable 2FA - no 2FA middleware to avoid setup loops
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('two-factor/setup', [DashboardController::class, 'twoFactorSetup'])
                ->name('user.two-factor.setup');
    
    Route::post('two-factor/enable', [DashboardController::class, 'enableTwoFactor'])
                ->name('user.two-factor.enable');
    
    Route::delete('two-factor/disable', [DashboardController::class, 'disableTwoFactor'])
                ->name('user.two-factor.disable');
    
    Route::get('two-factor/recovery-codes', [DashboardController::class, 'showRecoveryCodes'])
                ->name('user.two-factor.recovery');
    
    Route::post('two-factor/recovery-codes/regenerate', [DashboardController::class, 'regenerateRecoveryCodes'])
                ->name('user.two-factor.recovery.regenerate');
});