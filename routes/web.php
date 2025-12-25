<?php

use App\Http\Controllers\Admin\AdminLeaderboardController;
use App\Http\Controllers\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoutingController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminWalletController;
use App\Http\Controllers\Admin\AdminLoginReportController;
use App\Http\Controllers\Admin\AdminPushController;
use App\Http\Controllers\Admin\AdminCryptocurrencyController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminSupportController;
use App\Http\Controllers\Admin\AdminInvestmentController;
use App\Http\Controllers\Admin\AdminCRMController;
use App\Http\Controllers\Admin\AdminReferralController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\AdminFaqController;
use App\Http\Controllers\Admin\AdminImpersonationController;
use App\Http\Controllers\Admin\AdminCommissionController;
use App\Http\Controllers\Admin\AdminMassEmailController;
use App\Http\Controllers\Admin\AdminAnnouncementsController;
use App\Http\Controllers\Admin\ComprehensiveAnalyticsController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\AdminTransactionController;
use App\Http\Controllers\Admin\EmailSettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserAnnouncementController;
use App\Http\Controllers\UserLeaderboardController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\PushController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\BotController;
use App\Http\Controllers\CRMController;
use App\Notifications\GeneralNotification;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Guest Routes (Authentication)
|--------------------------------------------------------------------------
*/

// routes/web.php
Route::get('.well-known/appspecific/com.chrome.devtools.json', function () {
    return response()->json([]);
});

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store'])->name('register.store');
    Route::get('register/check-username', [RegisterController::class, 'checkUsername'])->name('register.check-username');
    Route::get('register/check-sponsor', [RegisterController::class, 'checkSponsorId'])->name('register.check-sponsor');
});

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/

Route::prefix('forms')->name('forms.public.')->group(function () {
    Route::get('/{slug}', [CRMController::class, 'showPublicForm'])->name('show');
    Route::post('/{slug}/submit', [CRMController::class, 'submitForm'])->name('submit');
});

// Include Laravel's default auth routes
require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Push Notification Routes - PUBLIC VAPID KEY
|--------------------------------------------------------------------------
*/

// VAPID public key route (must be accessible without auth)
Route::get('/push/vapid-public-key', [PushController::class, 'vapidPublicKey'])->name('push.vapid-key');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth', '2fa', 'password.change')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Password Change Routes
    |--------------------------------------------------------------------------
    */

    Route::get('/password/change', [App\Http\Controllers\Auth\PasswordChangeController::class, 'showChangeForm'])
        ->name('password.change.form');
    Route::post('/password/change', [App\Http\Controllers\Auth\PasswordChangeController::class, 'changePassword'])
        ->name('password.change.update');

    Route::get('/admin/impersonation/stop', [AdminImpersonationController::class, 'stopImpersonation'])->name('admin.impersonation.stop');

    /*
    |--------------------------------------------------------------------------
    | Push Notification Routes - AUTHENTICATED
    |--------------------------------------------------------------------------
    */

    Route::prefix('push')->name('push.')->group(function () {
        Route::post('/subscribe', [PushController::class, 'subscribe'])->name('subscribe');
        Route::post('/unsubscribe', [PushController::class, 'unsubscribe'])->name('unsubscribe');
        Route::get('/subscriptions', [PushController::class, 'subscriptions'])->name('subscriptions');
        Route::post('/test', [PushController::class, 'sendTestNotification'])->name('test');
        Route::delete('/subscriptions/{subscriptionId}', [PushController::class, 'removeSubscription'])->name('remove-subscription');
        Route::post('/preferences', [PushController::class, 'updatePreferences'])->name('update-preferences');
        Route::get('/preferences', [PushController::class, 'getPreferences'])->name('get-preferences');
    });

    Route::get('/test-push-notification', function () {
        try {
            auth()->user()->notify(
                \App\Notifications\PushNotification::welcome(config('app.name'))
            );
            return back()->with('success', 'Test notification sent! 🔔');
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    })->middleware('auth');
    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware(['verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/transaction-chart-data', [AdminDashboardController::class, 'getTransactionChartData'])->name('dashboard.transaction-chart-data');
        Route::get('profile', [DashboardController::class, 'profile'])->name('profile');
        Route::put('profile', [DashboardController::class, 'updateProfile'])->name('profile.update');
        Route::put('profile/password', [DashboardController::class, 'updatePassword'])->name('profile.password');
        Route::get('/dashboard/transactions/filter', [AdminDashboardController::class, 'getFilteredTransactions'])
            ->name('dashboard.transactions.filter');
        Route::get('/dashboard/users/filter', [AdminDashboardController::class, 'getFilteredUsers'])
            ->name('dashboard.users.filter');


        // Login Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/login', [AdminLoginReportController::class, 'index'])->name('login.index');
            Route::get('/login/filter-ajax', [AdminLoginReportController::class, 'getFilteredLoginLogsAjax'])->name('login.filter-ajax');
            Route::get('/login/{id}', [AdminLoginReportController::class, 'show'])->name('login.show');
            Route::get('/login/search-users', [AdminLoginReportController::class, 'searchUsers'])->name('login.search-users');
            Route::get('/login/export', [AdminLoginReportController::class, 'export'])->name('login.export');
            Route::get('/login/analytics', [AdminLoginReportController::class, 'analytics'])->name('login.analytics');
        });


        // Users Management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [AdminUserController::class, 'index'])->name('index');
            Route::get('/{user}', [AdminUserController::class, 'show'])->name('show');
            Route::get('/{user}/edit', [AdminUserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [AdminUserController::class, 'update'])->name('update');
            Route::get('/create/new', [AdminUserController::class, 'create'])->name('create');
            Route::post('/create/store', [AdminUserController::class, 'store'])->name('store');
            Route::post('/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{user}/verify-email', [AdminUserController::class, 'verifyEmail'])->name('verify-email');
            Route::post('/{user}/update-kyc-status', [AdminUserController::class, 'updateKycStatus'])->name('update-kyc-status');
            Route::post('/{user}/adjust-balance', [AdminUserController::class, 'adjustBalance'])->name('adjust-balance');
            Route::get('/export/data', [AdminUserController::class, 'export'])->name('export');

            Route::get('/{user}/referral-investments', [AdminUserController::class, 'referralInvestments'])->name('referral-investments');
            Route::get('/{user}/referral-summary-by-level', [AdminUserController::class, 'getReferralSummaryByLevel'])->name('referral-summary-by-level');
            Route::get('/{user}/export-referral-investments', [AdminUserController::class, 'exportReferralInvestments'])->name('export-referral-investments');

        });

        // Staff Management
        Route::prefix('staff')->name('staff.')->group(function () {
            Route::get('/', [AdminUserController::class, 'staffIndex'])->name('index');
            Route::get('/search-users', [AdminUserController::class, 'searchUsers'])->name('search-users');
            Route::post('/promote-user', [AdminUserController::class, 'promoteUser'])->name('promote-user');
            Route::post('/{user}/change-role', [AdminUserController::class, 'changeRole'])->name('change-role');
            Route::post('/{user}/demote', [AdminUserController::class, 'demoteStaff'])->name('demote');
        });

        // Replace/expand the existing admin push routes with these:
        Route::prefix('push')->name('push.')->group(function () {
            Route::get('/', [AdminPushController::class, 'index'])->name('index');
            Route::get('/statistics', [AdminPushController::class, 'statistics'])->name('statistics');
            Route::get('/history', [AdminPushController::class, 'history'])->name('history');

            // Sending notifications
            Route::post('/send-to-user', [AdminPushController::class, 'sendToUser'])->name('send-to-user');
            Route::post('/broadcast', [AdminPushController::class, 'broadcast'])->name('broadcast');
            Route::post('/send-test', [AdminPushController::class, 'sendTest'])->name('send-test');

            // User targeting
            Route::post('/recipient-count', [AdminPushController::class, 'getRecipientCount'])->name('recipient-count');
            Route::get('/search-users', [AdminPushController::class, 'searchUsers'])->name('search-users');

            // Analytics & Maintenance
            Route::get('/browser-distribution', [AdminPushController::class, 'browserDistribution'])->name('browser-distribution');
            Route::post('/cleanup', [AdminPushController::class, 'cleanup'])->name('cleanup');
        });

        // KYC Management
        Route::prefix('kyc')->name('kyc.')->group(function () {
            Route::get('/', [AdminUserController::class, 'kycIndex'])->name('index');
            Route::post('/{user}/update-status', [AdminUserController::class, 'updateKycStatus'])->name('update-status');
            Route::get('/{user}/details', [AdminUserController::class, 'getKycDetails'])->name('details');
            Route::post('/bulk/update-status', [AdminUserController::class, 'bulkUpdateKycStatus'])->name('bulk.update-status');
            Route::get('/export', [AdminUserController::class, 'exportKycData'])->name('export');
        });

        // Finance Management
        Route::prefix('finance')->name('finance.')->group(function () {
            // Transactions
            Route::prefix('transactions')->name('transactions.')->group(function () {
                Route::get('/filter-ajax', [AdminTransactionController::class, 'getFilteredTransactionsAjax'])
                    ->name('filter-ajax');
                Route::get('/', [AdminTransactionController::class, 'index'])->name('index');
                Route::get('/{transaction}', [AdminTransactionController::class, 'show'])->name('show');
                Route::post('/{transaction}/update-status', [AdminTransactionController::class, 'updateStatus'])->name('update-status');
                Route::post('/bulk/update-status', [AdminTransactionController::class, 'bulkUpdateStatus'])->name('bulk.update-status');
                Route::get('/analytics/data', [AdminTransactionController::class, 'analytics'])->name('analytics');
                Route::get('/export', [AdminTransactionController::class, 'export'])->name('export');
            });

            // Wallets
            Route::prefix('wallets')->name('wallets.')->group(function () {
                Route::get('/', [AdminWalletController::class, 'index'])->name('index');
                Route::get('/get-wallets', [AdminWalletController::class, 'getWallets'])->name('get-wallets');
                Route::get('/{wallet}', [AdminWalletController::class, 'show'])->name('show');
                Route::post('/{wallet}/adjust-balance', [AdminWalletController::class, 'adjustBalance'])->name('adjust-balance');
                Route::post('/{wallet}/toggle-status', [AdminWalletController::class, 'toggleStatus'])->name('toggle-status');
                Route::post('/{wallet}/update-address', [AdminWalletController::class, 'updateAddress'])->name('update-address');
                Route::post('/create', [AdminWalletController::class, 'createWallet'])->name('create');
                Route::post('/search-users', [AdminWalletController::class, 'searchUsers'])->name('search-users');
                Route::post('/bulk-action', [AdminWalletController::class, 'bulkAction'])->name('bulk-action');
                Route::get('/export', [AdminWalletController::class, 'export'])->name('export');
            });

            // Cryptocurrencies
            Route::prefix('cryptocurrencies')->name('cryptocurrencies.')->group(function () {
                Route::get('/', [AdminCryptocurrencyController::class, 'index'])->name('index');
                Route::get('/create', [AdminCryptocurrencyController::class, 'create'])->name('create');
                Route::post('/', [AdminCryptocurrencyController::class, 'store'])->name('store');
                Route::get('/{cryptocurrency}', [AdminCryptocurrencyController::class, 'show'])->name('show');
                Route::get('/{cryptocurrency}/edit', [AdminCryptocurrencyController::class, 'edit'])->name('edit');
                Route::put('/{cryptocurrency}', [AdminCryptocurrencyController::class, 'update'])->name('update');
                Route::delete('/{cryptocurrency}', [AdminCryptocurrencyController::class, 'destroy'])->name('destroy');
                Route::post('/{cryptocurrency}/toggle-status', [AdminCryptocurrencyController::class, 'toggleStatus'])->name('toggle-status');
                Route::post('/update-order', [AdminCryptocurrencyController::class, 'updateOrder'])->name('update-order');
                Route::post('/bulk-action', [AdminCryptocurrencyController::class, 'bulkAction'])->name('bulk-action');
                Route::get('/api/statistics', [AdminCryptocurrencyController::class, 'getStatistics'])->name('statistics');
            });

            // Pending Withdrawals
            Route::prefix('withdrawals')->name('withdrawals.')->group(function () {
                Route::get('/pending', [AdminTransactionController::class, 'pendingWithdrawals'])->name('pending');
                Route::get('/pending/filter-ajax', [AdminTransactionController::class, 'getFilteredPendingWithdrawalsAjax'])->name('pending.filter-ajax');
            });
        });

        // Support
        Route::prefix('support')->name('support.')->group(function () {
            Route::get('/', [AdminSupportController::class, 'index'])->name('index');
            Route::get('/tickets', [AdminSupportController::class, 'tickets'])->name('tickets');
            Route::get('/tickets/{ticket}', [AdminSupportController::class, 'show'])->name('show');
            Route::post('/tickets/{ticket}/reply', [AdminSupportController::class, 'storeReply'])->name('reply');
            Route::patch('/tickets/{ticket}/status', [AdminSupportController::class, 'updateStatus'])->name('update-status');
            Route::patch('/tickets/{ticket}/priority', [AdminSupportController::class, 'updatePriority'])->name('update-priority');
            Route::patch('/tickets/{ticket}/assign', [AdminSupportController::class, 'assign'])->name('assign');
            Route::get('/api/statistics', [AdminSupportController::class, 'getStatistics'])->name('api.statistics');
            Route::get('/search/users', [AdminSupportController::class, 'searchUsers'])->name('search.users');
        });

        // FAQ
        Route::prefix('faq')->name('faq.')->group(function () {
            Route::get('/', [AdminFaqController::class, 'index'])->name('index');
            Route::get('/manage', [AdminFaqController::class, 'faqs'])->name('faqs');
            Route::get('/create', [AdminFaqController::class, 'create'])->name('create');
            Route::post('/', [AdminFaqController::class, 'store'])->name('store');
            Route::get('/{faq}', [AdminFaqController::class, 'show'])->name('show');
            Route::get('/{faq}/edit', [AdminFaqController::class, 'edit'])->name('edit');
            Route::put('/{faq}', [AdminFaqController::class, 'update'])->name('update');
            Route::delete('/{faq}', [AdminFaqController::class, 'destroy'])->name('destroy');
            Route::post('/{faq}/toggle-status', [AdminFaqController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{faq}/toggle-featured', [AdminFaqController::class, 'toggleFeatured'])->name('toggle-featured');
            Route::post('/update-order', [AdminFaqController::class, 'updateOrder'])->name('update-order');
            Route::post('/bulk-action', [AdminFaqController::class, 'bulkAction'])->name('bulk-action');
            Route::get('/api/statistics', [AdminFaqController::class, 'getStatistics'])->name('statistics');
            Route::get('/search', [AdminFaqController::class, 'search'])->name('search');
            Route::get('/export/csv', [AdminFaqController::class, 'export'])->name('export');
        });

        // Settings
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::post('/update', [SettingsController::class, 'update'])->name('update');
            Route::get('/export', [SettingsController::class, 'exportSettings'])->name('export');
            Route::get('/category/{category}', [SettingsController::class, 'getSettings'])->name('category');
            Route::post('/reset', [SettingsController::class, 'resetToDefaults'])->name('reset');
            Route::post('/cache/clear', [SettingsController::class, 'clearCache'])->name('cache.clear');
        });

        // Investment Management
        Route::prefix('investment')->name('investment.')->group(function () {
            Route::get('/', [AdminInvestmentController::class, 'index'])->name('index');
            Route::get('/create', [AdminInvestmentController::class, 'create'])->name('create');
            Route::post('/', [AdminInvestmentController::class, 'store'])->name('store');
            Route::get('/{investmentPlan}', [AdminInvestmentController::class, 'show'])->name('show');
            Route::get('/{investmentPlan}/edit', [AdminInvestmentController::class, 'edit'])->name('edit');
            Route::put('/{investmentPlan}', [AdminInvestmentController::class, 'update'])->name('update');
            Route::delete('/{investmentPlan}', [AdminInvestmentController::class, 'destroy'])->name('destroy');
            Route::post('/{investmentPlan}/toggle-status', [AdminInvestmentController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/update-order', [AdminInvestmentController::class, 'updateOrder'])->name('update-order');
            Route::post('/{investmentPlan}/toggle-profit-sharing', [AdminInvestmentController::class, 'toggleProfitSharing'])->name('toggle-profit-sharing');
            Route::put('/{investmentPlan}/update-profit-sharing', [AdminInvestmentController::class, 'updateProfitSharing'])->name('update-profit-sharing');
            Route::post('/profit-sharing-preview', [AdminInvestmentController::class, 'getProfitSharingPreview'])->name('profit-sharing-preview');
            Route::get('/{investmentPlan}/profit-sharing-transactions', [AdminInvestmentController::class, 'getProfitSharingTransactions'])->name('profit-sharing-transactions');
            Route::post('/process-profit-sharing-transactions', [AdminInvestmentController::class, 'processProfitSharingTransactions'])->name('process-profit-sharing-transactions');
            Route::get('/{investmentPlan}/tiers', [AdminInvestmentController::class, 'getTierDetails'])->name('tiers.details');
            Route::get('/user-options', [AdminInvestmentController::class, 'getUserInvestmentOptions'])->name('user-options');
            Route::post('/simulate', [AdminInvestmentController::class, 'simulateInvestment'])->name('simulate');
            Route::post('/update-user-levels', [AdminInvestmentController::class, 'updateUserLevels'])->name('update-user-levels');
            Route::get('/user-level-stats', [AdminInvestmentController::class, 'getUserLevelStats'])->name('user-level-stats');
            Route::get('/user-investments', [AdminInvestmentController::class, 'userInvestments'])->name('user-investments');
            Route::get('/returns', [AdminInvestmentController::class, 'investmentReturns'])->name('returns');
            Route::get('/statistics', [AdminInvestmentController::class, 'getStatistics'])->name('statistics');
            Route::get('/export', [AdminInvestmentController::class, 'export'])->name('export');
        });

        // Email Settings
        Route::prefix('email-settings')->name('email-settings.')->group(function () {
            Route::get('/', [EmailSettingsController::class, 'index'])->name('index');
            Route::post('/update', [EmailSettingsController::class, 'update'])->name('update');
            Route::post('/test-connection', [EmailSettingsController::class, 'testConnection'])->name('test-connection');
            Route::post('/send-test', [EmailSettingsController::class, 'sendTestEmail'])->name('send-test');
            Route::get('/queue-status', [EmailSettingsController::class, 'queueStatus'])->name('queue-status');
            Route::post('/clear-queue', [EmailSettingsController::class, 'clearQueue'])->name('clear-queue');
        });

        Route::prefix('email-templates')->name('email-templates.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\EmailTemplateController::class, 'index'])->name('index');
            Route::get('/get', [App\Http\Controllers\Admin\EmailTemplateController::class, 'getTemplates'])->name('get');
            Route::get('/{id}', [App\Http\Controllers\Admin\EmailTemplateController::class, 'show'])->name('show');
            Route::post('/', [App\Http\Controllers\Admin\EmailTemplateController::class, 'store'])->name('store');
            Route::put('/{id}', [App\Http\Controllers\Admin\EmailTemplateController::class, 'update'])->name('update');
            Route::delete('/{id}', [App\Http\Controllers\Admin\EmailTemplateController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle-status', [App\Http\Controllers\Admin\EmailTemplateController::class, 'toggleStatus'])->name('toggle-status');
            Route::get('/{id}/preview', [App\Http\Controllers\Admin\EmailTemplateController::class, 'preview'])->name('preview');
            Route::post('/{id}/send-test', [App\Http\Controllers\Admin\EmailTemplateController::class, 'sendTest'])->name('send-test');
            Route::post('/{id}/duplicate', [App\Http\Controllers\Admin\EmailTemplateController::class, 'duplicate'])->name('duplicate');
            Route::get('/variables/{category}', [App\Http\Controllers\Admin\EmailTemplateController::class, 'getVariables'])->name('variables');
            Route::post('/seed-defaults', [App\Http\Controllers\Admin\EmailTemplateController::class, 'seedDefaults'])->name('seed-defaults');
        });

        // Impersonation
        Route::prefix('impersonation')->name('impersonation.')->group(function () {
            Route::get('/', [AdminImpersonationController::class, 'index'])->name('index');
            Route::get('/search-users', [AdminImpersonationController::class, 'searchUsers'])->name('search-users');
            Route::post('/start', [AdminImpersonationController::class, 'startImpersonation'])->name('start');
            Route::get('/history', [AdminImpersonationController::class, 'history'])->name('history');
            Route::get('/status', [AdminImpersonationController::class, 'getStatus'])->name('status');
        });

        // Leaderboards
        Route::prefix('leaderboards')->name('leaderboards.')->group(function () {
            Route::get('/', [AdminLeaderboardController::class, 'index'])->name('index');
            Route::get('/create', [AdminLeaderboardController::class, 'create'])->name('create');
            Route::post('/', [AdminLeaderboardController::class, 'store'])->name('store');
            Route::get('/{leaderboard}', [AdminLeaderboardController::class, 'show'])->name('show');
            Route::get('/{leaderboard}/edit', [AdminLeaderboardController::class, 'edit'])->name('edit');
            Route::put('/{leaderboard}', [AdminLeaderboardController::class, 'update'])->name('update');
            Route::delete('/{leaderboard}', [AdminLeaderboardController::class, 'destroy'])->name('destroy');
            Route::get('/statistics', [AdminLeaderboardController::class, 'getStatistics'])->name('statistics');
            Route::post('/{leaderboard}/toggle-status', [AdminLeaderboardController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{leaderboard}/calculate-positions', [AdminLeaderboardController::class, 'calculatePositions'])->name('calculate-positions');
            Route::post('/{leaderboard}/distribute-prizes', [AdminLeaderboardController::class, 'distributePrizes'])->name('distribute-prizes');
            Route::post('/{leaderboard}/complete', [AdminLeaderboardController::class, 'complete'])->name('complete');
            Route::post('/auto-complete-expired', [AdminLeaderboardController::class, 'autoCompleteExpired'])->name('auto-complete-expired');
        });

        // Mass Email
        Route::prefix('mass-email')->name('mass-email.')->group(function () {
            Route::get('/', [AdminMassEmailController::class, 'index'])->name('index');
            Route::post('/recipient-count', [AdminMassEmailController::class, 'getRecipientCount'])->name('recipient-count');
            Route::post('/search-users', [AdminMassEmailController::class, 'searchUsers'])->name('search-users');
            Route::post('/preview', [AdminMassEmailController::class, 'preview'])->name('preview');
            Route::post('/send', [AdminMassEmailController::class, 'send'])->name('send');
            Route::post('/test-config', [AdminMassEmailController::class, 'testConfiguration'])->name('test-config');
            Route::get('/campaigns', [AdminMassEmailController::class, 'campaigns'])->name('campaigns');
            Route::get('/campaigns/{campaign}', [AdminMassEmailController::class, 'campaignStatus'])->name('campaign-status');
            Route::post('/campaigns/{campaign}/cancel', [AdminMassEmailController::class, 'cancelCampaign'])->name('cancel-campaign');
        });

        // Announcements
        Route::prefix('announcements')->name('announcements.')->group(function () {
            Route::get('/', [AdminAnnouncementsController::class, 'index'])->name('index');
            Route::get('/create', [AdminAnnouncementsController::class, 'create'])->name('create');
            Route::post('/', [AdminAnnouncementsController::class, 'store'])->name('store');
            Route::get('/{announcement}', [AdminAnnouncementsController::class, 'show'])->name('show');
            Route::get('/{announcement}/edit', [AdminAnnouncementsController::class, 'edit'])->name('edit');
            Route::put('/{announcement}', [AdminAnnouncementsController::class, 'update'])->name('update');
            Route::delete('/{announcement}', [AdminAnnouncementsController::class, 'destroy'])->name('destroy');
            Route::post('/{announcement}/toggle-status', [AdminAnnouncementsController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{announcement}/reset-views', [AdminAnnouncementsController::class, 'resetViews'])->name('reset-views');
            Route::get('/statistics/data', [AdminAnnouncementsController::class, 'getStatistics'])->name('statistics');
            Route::post('/search-users', [AdminAnnouncementsController::class, 'searchUsers'])->name('search-users');
            Route::post('/target-count', [AdminAnnouncementsController::class, 'getTargetCount'])->name('target-count');
            Route::post('/preview', [AdminAnnouncementsController::class, 'preview'])->name('preview');
        });

        // CRM Management
        Route::prefix('crm')->name('crm.')->group(function () {
            Route::get('/', [AdminCRMController::class, 'index'])->name('dashboard');

            // Leads
            Route::prefix('leads')->name('leads.')->group(function () {
                Route::get('/', [AdminCRMController::class, 'leads'])->name('index');
                Route::post('/', [AdminCRMController::class, 'storeLead'])->name('store');
                Route::put('/{id}/status', [AdminCRMController::class, 'updateLeadStatus'])->name('update-status');
                Route::get('/{id}', [AdminCRMController::class, 'showLead'])->name('show');
                Route::put('/{id}', [AdminCRMController::class, 'updateLead'])->name('update');
                Route::delete('/{id}', [AdminCRMController::class, 'deleteLead'])->name('delete');
                Route::post('/bulk/update-status', [AdminCRMController::class, 'bulkUpdateLeadStatus'])->name('bulk.update-status');
                Route::post('/bulk/assign', [AdminCRMController::class, 'bulkAssignLeads'])->name('bulk.assign');
                Route::delete('/bulk/delete', [AdminCRMController::class, 'bulkDeleteLeads'])->name('bulk.delete');
                Route::get('/export', [AdminCRMController::class, 'exportLeads'])->name('export');
            });

            // Forms
            Route::prefix('forms')->name('forms.')->group(function () {
                Route::get('/', [AdminCRMController::class, 'forms'])->name('index');
                Route::post('/', [AdminCRMController::class, 'storeForm'])->name('store');
                Route::get('/{id}', [AdminCRMController::class, 'showForm'])->name('show');
                Route::put('/{id}', [AdminCRMController::class, 'updateForm'])->name('update');
                Route::delete('/{id}', [AdminCRMController::class, 'deleteForm'])->name('delete');
                Route::put('/{id}/toggle-status', [AdminCRMController::class, 'toggleFormStatus'])->name('toggle-status');
                Route::get('/{id}/submissions', [AdminCRMController::class, 'formSubmissions'])->name('submissions');
                Route::get('/{id}/analytics', [AdminCRMController::class, 'formAnalytics'])->name('analytics');
            });

            // Followups
            Route::prefix('followups')->name('followups.')->group(function () {
                Route::get('/', [AdminCRMController::class, 'followups'])->name('index');
                Route::post('/', [AdminCRMController::class, 'storeFollowup'])->name('store');
                Route::get('/today', [AdminCRMController::class, 'todayFollowups'])->name('today');
                Route::get('/overdue', [AdminCRMController::class, 'overdueFollowups'])->name('overdue');
                Route::put('/{id}/complete', [AdminCRMController::class, 'completeFollowup'])->name('complete');
                Route::put('/{id}', [AdminCRMController::class, 'updateFollowup'])->name('update');
                Route::delete('/{id}', [AdminCRMController::class, 'deleteFollowup'])->name('delete');
            });

            // Assignments
            Route::prefix('assignments')->name('assignments.')->group(function () {
                Route::get('/', [AdminCRMController::class, 'assignments'])->name('index');
                Route::post('/', [AdminCRMController::class, 'assignLead'])->name('store');
                Route::put('/{id}/complete', [AdminCRMController::class, 'completeAssignment'])->name('complete');
                Route::put('/{id}/reassign', [AdminCRMController::class, 'reassignLead'])->name('reassign');
                Route::delete('/{id}', [AdminCRMController::class, 'deleteAssignment'])->name('delete');
            });

            // Utilities
            Route::prefix('utils')->name('utils.')->group(function () {
                Route::get('/users/assignable', [AdminCRMController::class, 'getAssignableUsers'])->name('users.assignable');
                Route::get('/leads/search', [AdminCRMController::class, 'searchLeads'])->name('leads.search');
                Route::get('/filters/sources', [AdminCRMController::class, 'getLeadSources'])->name('filters.sources');
                Route::get('/filters/countries', [AdminCRMController::class, 'getCountries'])->name('filters.countries');
            });
        });

        // Referrals Management
        Route::prefix('referrals')->name('referrals.')->group(function () {
            Route::get('/', [AdminReferralController::class, 'index'])->name('index');
            Route::get('/tree', [AdminReferralController::class, 'tree'])->name('tree');
            Route::get('/tree/data', [AdminReferralController::class, 'getTreeData'])->name('tree.data');
            Route::get('/tree/stats', [AdminReferralController::class, 'getTreeStats'])->name('tree.stats');
            Route::get('/search/users', [AdminReferralController::class, 'searchUsers'])->name('search.users');
            Route::get('/{referral}', [AdminReferralController::class, 'show'])->name('show');
            Route::post('/{referral}/update-status', [AdminReferralController::class, 'updateStatus'])->name('update-status');
            Route::post('/bulk/update-status', [AdminReferralController::class, 'bulkUpdateStatus'])->name('bulk.update-status');
            Route::get('/analytics/data', [AdminReferralController::class, 'analytics'])->name('analytics');
            Route::get('/api/dashboard-stats', [AdminReferralController::class, 'getDashboardStats'])->name('api.dashboard-stats');
            Route::get('/api/top-sponsors', [AdminReferralController::class, 'topSponsors'])->name('api.top-sponsors');
            Route::get('/export/csv', [AdminReferralController::class, 'export'])->name('export');
            Route::get('/search/sponsors', [AdminReferralController::class, 'searchSponsors'])->name('search.sponsors');
            Route::get('/{referral}/commission-history', [AdminReferralController::class, 'commissionHistory'])->name('commission-history');
        });

        // Commission Settings
        Route::prefix('commission')->name('commission.')->group(function () {
            Route::get('/', [AdminCommissionController::class, 'index'])->name('index');
            Route::get('/create', [AdminCommissionController::class, 'create'])->name('create');
            Route::post('/', [AdminCommissionController::class, 'store'])->name('store');
            Route::get('/{commissionSetting}', [AdminCommissionController::class, 'show'])->name('show');
            Route::get('/{commissionSetting}/edit', [AdminCommissionController::class, 'edit'])->name('edit');
            Route::put('/{commissionSetting}', [AdminCommissionController::class, 'update'])->name('update');
            Route::delete('/{commissionSetting}', [AdminCommissionController::class, 'destroy'])->name('destroy');
            Route::post('/{commissionSetting}/toggle-status', [AdminCommissionController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/update-order', [AdminCommissionController::class, 'updateOrder'])->name('update-order');
            Route::post('/calculate-preview', [AdminCommissionController::class, 'calculatePreview'])->name('calculate-preview');
            Route::post('/update-user-tiers', [AdminCommissionController::class, 'updateUserTiers'])->name('update-user-tiers');
            Route::get('/api/statistics', [AdminCommissionController::class, 'getStatistics'])->name('statistics');
            Route::get('/export/csv', [AdminCommissionController::class, 'export'])->name('export');
            Route::post('/seed-default', [AdminCommissionController::class, 'seedDefault'])->name('seed-default');
        });

        // Maintenance Mode
        Route::prefix('maintenance')->name('maintenance.')->group(function () {
            Route::get('/', [MaintenanceController::class, 'index'])->name('index');
            Route::post('/enable', [MaintenanceController::class, 'enable'])->name('enable');
            Route::post('/disable', [MaintenanceController::class, 'disable'])->name('disable');
            Route::get('/status', [MaintenanceController::class, 'status'])->name('status');
            Route::post('/settings', [MaintenanceController::class, 'updateSettings'])->name('settings');
            Route::get('/preview', [MaintenanceController::class, 'preview'])->name('preview');
        });

        // Notifications
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [AdminNotificationController::class, 'index'])->name('index');
            Route::get('/create', [AdminNotificationController::class, 'create'])->name('create');
            Route::post('/store', [AdminNotificationController::class, 'store'])->name('store');
            Route::get('/{notification}', [AdminNotificationController::class, 'show'])->name('show');
            Route::delete('/{notification}', [AdminNotificationController::class, 'destroy'])->name('destroy');
            Route::post('/test', [AdminNotificationController::class, 'sendTest'])->name('test');
            Route::post('/clear', [AdminNotificationController::class, 'clearOld'])->name('clear');
            Route::get('/logs/view', [AdminNotificationController::class, 'logs'])->name('logs');
            Route::post('/{id}/mark-read', [AdminNotificationController::class, 'markAsRead'])->name('mark-read');
            Route::delete('/log/{id}', [AdminNotificationController::class, 'deleteLog'])->name('delete-log');
            Route::get('/export/csv', [AdminNotificationController::class, 'exportCsv'])->name('export');
            Route::post('/bulk-actions', [AdminNotificationController::class, 'bulkActions'])->name('bulk');
        });

        // Blocked Users
        Route::prefix('blocked-users')->name('blocked-users.')->group(function () {
            Route::get('/', [AdminUserController::class, 'blockedUsersIndex'])->name('index');
            Route::get('/search-active-users', [AdminUserController::class, 'searchActiveUsers'])->name('search-active-users');
            Route::post('/block-user', [AdminUserController::class, 'blockUser'])->name('block-user');
            Route::post('/{user}/unblock', [AdminUserController::class, 'unblockUser'])->name('unblock');
            Route::get('/{user}/block-details', [AdminUserController::class, 'getBlockDetails'])->name('block-details');
            Route::post('/process-expired', [AdminUserController::class, 'processExpiredBlocks'])->name('process-expired');
        });

        Route::get('/transactions', [AdminDashboardController::class, 'transactions'])->name('transactions.index');
        Route::get('/system-health', [AdminDashboardController::class, 'systemHealth'])->name('system.health');
        Route::get('/export-data', [AdminDashboardController::class, 'exportData'])->name('export.data');

        // API routes for dashboard
        Route::get('/api/stats', [AdminDashboardController::class, 'getStats'])->name('api.stats');
        Route::get('/api/recent-activity', [AdminDashboardController::class, 'getRecentActivity'])->name('api.recent.activity');

        // Comprehensive Analytics
        Route::get('/analytics', [ComprehensiveAnalyticsController::class, 'index'])->name('analytics');
        Route::get('/api/comprehensive-chart-data', [ComprehensiveAnalyticsController::class, 'getChartData'])->name('comprehensive-chart-data');
        Route::get('/api/comprehensive-summary', [ComprehensiveAnalyticsController::class, 'getSummaryData'])->name('comprehensive-summary');
        Route::get('/api/comprehensive-report', [ComprehensiveAnalyticsController::class, 'getDetailedReport'])->name('comprehensive-report');
        Route::get('/analytics/export', [ComprehensiveAnalyticsController::class, 'exportReport'])->name('analytics.export');

        Route::get('/transaction-analytics', [AdminDashboardController::class, 'transactionAnalytics'])->name('transaction-analytics');
        Route::get('/api/transaction-chart-data', [AdminDashboardController::class, 'getTransactionChartData'])->name('transaction-chart-data');
        Route::get('/api/transaction-summary', [AdminDashboardController::class, 'getTransactionSummary'])->name('transaction-summary');

        // Admin Push Notification Management Routes (BONUS)
        Route::prefix('push')->name('push.')->group(function () {
            Route::post('/send-to-user', [PushController::class, 'sendToUser'])->name('send-to-user');
            Route::post('/broadcast', [PushController::class, 'broadcast'])->name('broadcast');
            Route::post('/cleanup', [PushController::class, 'cleanup'])->name('cleanup');
            Route::get('/statistics', [PushController::class, 'statistics'])->name('statistics');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | User Dashboard Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware(['verified'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('', [DashboardController::class, 'index'])->name('root');
        Route::get('/home', [DashboardController::class, 'index'])->name('home');

        // Dashboard AJAX endpoints
        Route::prefix('dashboard/api')->name('dashboard.api.')->group(function () {
            Route::get('/balance', [DashboardController::class, 'getBalanceUpdate'])->name('balance');
            Route::get('/earnings', [DashboardController::class, 'getEarningsUpdate'])->name('earnings');
            Route::get('/activity', [DashboardController::class, 'getRecentActivity'])->name('activity');
            Route::get('/referrals', [DashboardController::class, 'getReferralStats'])->name('referrals');
            Route::get('/pending-commissions', [DashboardController::class, 'getPendingCommissionsDetail'])->name('pending-commissions');
            Route::get('/stats', [DashboardController::class, 'getStats'])->name('stats');
        });

        // Support
        Route::prefix('support')->name('support.')->group(function () {
            Route::get('/', [SupportController::class, 'index'])->name('index');
            Route::get('/create', [SupportController::class, 'create'])->name('create');
            Route::post('/', [SupportController::class, 'store'])->name('store');
            Route::get('/{ticket}', [SupportController::class, 'show'])->name('show');
            Route::post('/{ticket}/reply', [SupportController::class, 'storeReply'])->name('reply');
            Route::patch('/{ticket}/close', [SupportController::class, 'close'])->name('close');
            Route::patch('/{ticket}/reopen', [SupportController::class, 'reopen'])->name('reopen');
            Route::get('/{ticket}/download/{reply}/{attachment}', [SupportController::class, 'downloadAttachment'])->name('download');
            Route::get('/api/stats', [SupportController::class, 'getStats'])->name('api.stats');
        });

        // FAQs
        Route::prefix('faqs')->name('user.faq.')->group(function () {
            Route::get('/', [FaqController::class, 'index'])->name('index');
            Route::get('/{faq}', [FaqController::class, 'show'])->name('show');
            Route::get('/api/search', [FaqController::class, 'search'])->name('search');
            Route::get('/api/category/{category}', [FaqController::class, 'byCategory'])->name('by-category');
            Route::get('/api/popular', [FaqController::class, 'popular'])->name('popular');
            Route::get('/api/featured', [FaqController::class, 'featured'])->name('featured');
        });

        // User Announcements API
        Route::prefix('api/user/announcements')->name('api.user.announcements.')->group(function () {
            Route::get('pending', [UserAnnouncementController::class, 'getPending'])->name('pending');
            Route::post('{announcement}/viewed', [UserAnnouncementController::class, 'markViewed'])->name('mark-viewed');
            Route::get('history', [UserAnnouncementController::class, 'getHistory'])->name('history');
            Route::get('stats', [UserAnnouncementController::class, 'getUserStats'])->name('stats');
            Route::get('{announcement}', [UserAnnouncementController::class, 'show'])->name('show');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | User Profile Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('verified')->group(function () {
        Route::get('profile', [DashboardController::class, 'profile'])->name('user.profile');
        Route::put('profile', [DashboardController::class, 'updateProfile'])->name('user.profile.update');
        Route::put('profile/password', [DashboardController::class, 'updatePassword'])->name('user.profile.password');
    });

    /*
    |--------------------------------------------------------------------------
    | Notification Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
        Route::get('/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unreadCount');
    });

    /*
    |--------------------------------------------------------------------------
    | Financial Management Routes
    |--------------------------------------------------------------------------
    */

    // Basic wallet routes
    Route::prefix('wallets')->name('wallets.')->controller(WalletController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::patch('/{wallet}/address', 'updateAddress')->name('update-address');
        Route::post('/{wallet}/toggle', 'toggle')->name('toggle');
        Route::post('/update-prices', 'updateCryptoPrices')->name('update-prices');
    });

    // Deposit routes - Phone verification required
    Route::middleware('verification.required:phone')->prefix('wallets')->name('wallets.')->controller(WalletController::class)->group(function () {
        Route::get('/deposit', 'deposit')->name('deposit.wallet');
        Route::post('/{wallet}/deposit', 'generateDepositPayment')->name('deposit.generate');
        Route::post('/payment/status', 'checkPaymentStatus')->name('payment.status');
    });

    // Withdrawal routes - Phone + KYC verification required
    Route::middleware('verification.required:phone,kyc')->prefix('wallets')->name('wallets.')->controller(WalletController::class)->group(function () {
        Route::get('/withdraw', 'withdraw')->name('withdraw.wallet');
        Route::post('/{wallet}/withdraw', 'processWithdraw')->name('process-withdraw');
    });

    // Transactions
    Route::middleware('verified')->group(function () {
        Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('/transactions/{transaction}/details', [TransactionController::class, 'details'])->name('transactions.details');
        Route::get('transactions-list', [TransactionController::class, 'index'])->name('transactions');
    });

    /*
    |--------------------------------------------------------------------------
    | Referral System Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('verified')->prefix('referrals')->name('referrals.')->group(function () {
        Route::get('/', [ReferralController::class, 'index'])->name('index');
        Route::get('/pending-commissions', [ReferralController::class, 'getPendingCommissions'])->name('pending-commissions');
        Route::get('/users-page', [ReferralController::class, 'getUsersForPage'])->name('users-page');
        Route::get('/tree-data', [ReferralController::class, 'getTreeData'])->name('tree-data');
        Route::get('/create-direct', [ReferralController::class, 'createDirectReferral'])->name('create-direct');
        Route::post('/store-direct', [ReferralController::class, 'storeDirectReferral'])->name('store-direct');
        Route::get('/success/{data}', [ReferralController::class, 'referralSuccess'])->name('success');
        Route::get('/check-game-username', [ReferralController::class, 'checkGameUsername'])->name('check-game-username');
        Route::post('/validate-game-credentials', [ReferralController::class, 'validateGameCredentialsAjax'])->name('validate-game-credentials');
    });

    Route::middleware('verified')->get('user/referrals', [ReferralController::class, 'index'])->name('user.referrals');

    /*
    |--------------------------------------------------------------------------
    | Investment Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('verified')->group(function () {
        Route::get('user/investments', [DashboardController::class, 'investments'])->name('user.investments');
        Route::get('investments', [DashboardController::class, 'investments'])->name('investments.index');
    });

    /*
    |--------------------------------------------------------------------------
    | Leaderboards Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('verified')->prefix('promotions')->name('user.leaderboards.')->group(function () {
        Route::get('/', [UserLeaderboardController::class, 'index'])->name('index');
        Route::get('/{leaderboard}', [UserLeaderboardController::class, 'show'])->name('show');

        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/dashboard', [UserLeaderboardController::class, 'getDashboardData'])->name('dashboard');
            Route::get('/history', [UserLeaderboardController::class, 'getHistory'])->name('history');
            Route::post('/live-updates', [UserLeaderboardController::class, 'getLiveUpdates'])->name('live-updates');
            Route::get('/target-qualifications', [UserLeaderboardController::class, 'getTargetQualifications'])->name('target-qualifications');
            Route::get('/{leaderboard}/data', [UserLeaderboardController::class, 'getData'])->name('data');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Bot Management Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware(['verified', 'verification.required:phone,kyc'])->prefix('bot')->name('bot.')->group(function () {
        Route::get('/', [BotController::class, 'index'])->name('index');
        Route::get('/investments/{investment}/process-return', [BotController::class, 'processAllDueReturns'])->name('investments.process-return');
        Route::get('/investment/{investment}/return-status', [BotController::class, 'getInvestmentReturnStatus'])->name('investment.return-status');
        Route::get('/color-trading', [BotController::class, 'colorTradingSetup'])->name('color-trading');
        Route::get('/color-trading/game', [BotController::class, 'colorTradingGame'])->name('color-trading.game');
        Route::post('/color-trading/link', [BotController::class, 'linkGameAccount'])->name('color-trading.link');
        Route::delete('/color-trading/unlink', [BotController::class, 'unlinkGameAccount'])->name('color-trading.unlink');
        Route::post('/color-trading/invest', [BotController::class, 'createInvestment'])->name('color-trading.invest');
        Route::get('/aviator', [BotController::class, 'aviator'])->name('aviator');

        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/investment-stats', [BotController::class, 'getInvestmentStats'])->name('investment-stats');
            Route::post('/refresh-balance', [BotController::class, 'refreshGameBalance'])->name('refresh-balance');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | KYC Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('verified')->group(function () {
        Route::get('/kyc', [KycController::class, 'index'])->name('kyc.index');
        Route::post('/kyc/session', [KycController::class, 'createSession'])->name('kyc.session');
        Route::post('/kyc/start', [KycController::class, 'start'])->name('kyc.start');
        Route::post('/kyc/cancel-session', [KycController::class, 'cancelSession'])->name('kyc.cancel');
        Route::get('/kyc/status', [KycController::class, 'status'])->name('kyc.status');
        Route::get('/kyc/complete', [KycController::class, 'complete'])->name('kyc.complete');
    });

    /*
    |--------------------------------------------------------------------------
    | Phone Verification Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('verified')->prefix('phone')->name('phone.')->group(function () {
        Route::get('/verify', function () {
            return redirect()->route('user.profile')->with('info', 'Please update your phone number in your profile.');
        })->name('verify');
    });

    /*
    |--------------------------------------------------------------------------
    | CRM Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('verified')->group(function () {
        Route::get('/crm', [CRMController::class, 'index'])->name('crm.dashboard');
        Route::get('/crm/dashboard-data', [CRMController::class, 'dashboardData'])->name('crm.dashboard.data');
        Route::get('/crm/stats', [CRMController::class, 'getStats'])->name('crm.stats');

        // Leads
        Route::prefix('crm/leads')->name('crm.leads.')->group(function () {
            Route::get('/', [CRMController::class, 'leads'])->name('index');
            Route::post('/', [CRMController::class, 'storeLead'])->name('store');
            Route::get('/{id}', [CRMController::class, 'showLead'])->name('show');
            Route::put('/{id}', [CRMController::class, 'updateLead'])->name('update');
            Route::delete('/{id}', [CRMController::class, 'deleteLead'])->name('delete');
            Route::put('/{id}/status', [CRMController::class, 'updateLeadStatus'])->name('status.update');
            Route::get('/search/autocomplete', [CRMController::class, 'searchLeads'])->name('search');
            Route::get('/export/csv', [CRMController::class, 'exportLeads'])->name('export');
            Route::post('/bulk/update-status', [CRMController::class, 'bulkUpdateStatus'])->name('bulk.status');
            Route::post('/bulk/assign', [CRMController::class, 'bulkAssign'])->name('bulk.assign');
            Route::delete('/bulk/delete', [CRMController::class, 'bulkDelete'])->name('bulk.delete');
        });

        // Followups
        Route::prefix('crm/followups')->name('crm.followups.')->group(function () {
            Route::get('/', [CRMController::class, 'followups'])->name('index');
            Route::post('/', [CRMController::class, 'storeFollowup'])->name('store');
            Route::get('/today', [CRMController::class, 'todayFollowups'])->name('today');
            Route::get('/overdue', [CRMController::class, 'overdueFollowups'])->name('overdue');
            Route::put('/{id}/complete', [CRMController::class, 'completeFollowup'])->name('complete');
            Route::put('/{id}', [CRMController::class, 'updateFollowup'])->name('update');
            Route::delete('/{id}', [CRMController::class, 'deleteFollowup'])->name('delete');
        });

        // Assignments
        Route::prefix('crm/assignments')->name('crm.assignments.')->group(function () {
            Route::get('/', [CRMController::class, 'assignments'])->name('index');
            Route::post('/', [CRMController::class, 'assignLead'])->name('store');
            Route::put('/{id}/complete', [CRMController::class, 'completeAssignment'])->name('complete');
            Route::put('/{id}/reassign', [CRMController::class, 'reassignLead'])->name('reassign');
            Route::delete('/{id}', [CRMController::class, 'deleteAssignment'])->name('delete');
        });

        // Forms
        Route::prefix('crm/forms')->name('crm.forms.')->group(function () {
            Route::get('/', [CRMController::class, 'forms'])->name('index');
            Route::post('/', [CRMController::class, 'storeForm'])->name('store');
            Route::get('/{id}', [CRMController::class, 'showForm'])->name('show');
            Route::put('/{id}', [CRMController::class, 'updateForm'])->name('update');
            Route::delete('/{id}', [CRMController::class, 'deleteForm'])->name('delete');
            Route::put('/{id}/toggle-status', [CRMController::class, 'toggleFormStatus'])->name('toggle');
            Route::get('/{id}/submissions', [CRMController::class, 'formSubmissions'])->name('submissions');
            Route::get('/{id}/analytics', [CRMController::class, 'formAnalytics'])->name('analytics');
        });

        // Utilities
        Route::prefix('crm/utils')->name('crm.utils.')->group(function () {
            Route::get('/users/assignable', [CRMController::class, 'getAssignableUsers'])->name('users.assignable');
            Route::get('/filters/sources', [CRMController::class, 'getLeadSources'])->name('filters.sources');
            Route::get('/filters/countries', [CRMController::class, 'getCountries'])->name('filters.countries');
            Route::get('/filters/options', [CRMController::class, 'getFilterOptions'])->name('filters.options');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Support & Information Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('verified')->group(function () {
        Route::get('contact-us', [DashboardController::class, 'contact'])->name('support.contact-us');
        Route::get('news', [DashboardController::class, 'news'])->name('support.news');
        Route::get('about-us', [DashboardController::class, 'about'])->name('support.about-us');
    });


});

/*
|--------------------------------------------------------------------------
| Offline Route
|--------------------------------------------------------------------------
*/

Route::get('/offline', function () {
    return view('pages.offline');
})->name('offline');

// Test general notification route (for development/testing)
Route::get('/test-notification', function () {
    auth()->user()->notify(new GeneralNotification(
        'Test Notification',
        'This is a test notification to check if everything is working!',
        'iconamoon:check-circle-duotone'
    ));
    return redirect()->back()->with('success', 'Test notification sent! Check your notification dropdown.');
})->name('test.notification');
