<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KycController;
use App\Http\Controllers\GreenApiWebhookController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\CRMController;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/kyc/webhook', [KycController::class, 'webhook'])->name('kyc.webhook');

// Green API Webhook - NO AUTH REQUIRED
Route::post('webhook/green-api', [GreenApiWebhookController::class, 'handleWebhook'])
    ->name('webhook.green-api');

// Plisio payment webhook callbacks
Route::get('/webhooks/plisio', [WalletController::class, 'handlePlisioWebhook'])
    ->name('webhooks.plisio.get');

Route::post('/webhooks/plisio', [WalletController::class, 'handlePlisioWebhook'])
    ->name('webhooks.plisio.post');


Route::middleware(['auth:sanctum'])->prefix('v1/crm')->name('api.crm.')->group(function () {

    // Dashboard API
    Route::get('/dashboard', [CRMController::class, 'dashboardData']);
    Route::get('/stats', [CRMController::class, 'getStats']);

    // Leads API
    Route::apiResource('leads', CRMController::class);
    Route::get('/leads/{id}/timeline', [CRMController::class, 'getLeadTimeline']);
    Route::post('/leads/{id}/convert', [CRMController::class, 'convertLead']);
    Route::post('/leads/bulk-update', [CRMController::class, 'bulkUpdateStatus']);

    // Followups API
    Route::apiResource('followups', CRMController::class);
    Route::get('/followups/due/today', [CRMController::class, 'todayFollowups']);
    Route::get('/followups/overdue', [CRMController::class, 'overdueFollowups']);

    // Assignments API
    Route::apiResource('assignments', CRMController::class);
    Route::get('/assignments/by-user/{userId}', [CRMController::class, 'assignmentsByUser']);

    // Forms API
    Route::apiResource('forms', CRMController::class);
    Route::get('/forms/{id}/submissions', [CRMController::class, 'formSubmissions']);

    // Search & Filters
    Route::get('/search/leads', [CRMController::class, 'searchLeads']);
    Route::get('/filters/all', [CRMController::class, 'getFilterOptions']);
});

// Public API (rate limited)
Route::middleware(['throttle:60,1'])->prefix('api/public/crm')->group(function () {
    Route::post('/forms/{slug}/submit', [CRMController::class, 'submitForm']);
    Route::get('/forms/{slug}/fields', [CRMController::class, 'getFormFields']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});