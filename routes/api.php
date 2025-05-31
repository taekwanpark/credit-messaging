<?php

use Illuminate\Support\Facades\Route;
use Techigh\CreditMessaging\Http\Controllers\WebhookController;

/*
|--------------------------------------------------------------------------
| Credit Messaging API Routes
|--------------------------------------------------------------------------
|
| These routes handle webhook processing and API endpoints for the
| credit messaging system.
|
*/

Route::prefix('api/credit-messaging')->group(function () {

    // Webhook endpoints for message service providers (no auth required)
    Route::post('/webhook/{provider}', [WebhookController::class, 'handle'])
        ->name('credit-messaging.webhook');

    Route::post('/webhook/delivery-status', [WebhookController::class, 'handleDeliveryStatus'])
        ->name('credit-messaging.webhook.delivery-status');

    Route::post('/webhook/settlement', [WebhookController::class, 'handleSettlement'])
        ->name('credit-messaging.webhook.settlement');

    // Health check endpoint
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'service' => 'credit-messaging',
            'timestamp' => now()->toISOString()
        ]);
    })->name('credit-messaging.health');
});

// Authenticated API routes
Route::prefix('api/credit-messaging')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        
        // Site Credits Management
        Route::prefix('sites/{site_id}/credits')->group(function () {
            Route::get('/', [\Techigh\CreditMessaging\Http\Controllers\SiteCreditController::class, 'index'])
                ->name('credit-messaging.sites.credits.index');
            Route::post('/', [\Techigh\CreditMessaging\Http\Controllers\SiteCreditController::class, 'store'])
                ->name('credit-messaging.sites.credits.store');
            Route::get('/balance', [\Techigh\CreditMessaging\Http\Controllers\SiteCreditController::class, 'getBalance'])
                ->name('credit-messaging.sites.credits.balance');
            Route::post('/charge', [\Techigh\CreditMessaging\Http\Controllers\SiteCreditController::class, 'charge'])
                ->name('credit-messaging.sites.credits.charge');
            Route::get('/usage-stats', [\Techigh\CreditMessaging\Http\Controllers\SiteCreditController::class, 'getUsageStats'])
                ->name('credit-messaging.sites.credits.usage-stats');
        });

        // Credit Messages Management
        Route::prefix('sites/{site_id}/messages')->group(function () {
            Route::get('/', [\Techigh\CreditMessaging\Http\Controllers\CreditMessageController::class, 'index'])
                ->name('credit-messaging.sites.messages.index');
            Route::post('/', [\Techigh\CreditMessaging\Http\Controllers\CreditMessageController::class, 'store'])
                ->name('credit-messaging.sites.messages.store');
            Route::get('/{message}', [\Techigh\CreditMessaging\Http\Controllers\CreditMessageController::class, 'show'])
                ->name('credit-messaging.sites.messages.show');
            Route::put('/{message}', [\Techigh\CreditMessaging\Http\Controllers\CreditMessageController::class, 'update'])
                ->name('credit-messaging.sites.messages.update');
            Route::delete('/{message}', [\Techigh\CreditMessaging\Http\Controllers\CreditMessageController::class, 'destroy'])
                ->name('credit-messaging.sites.messages.destroy');
            Route::post('/{message}/send', [\Techigh\CreditMessaging\Http\Controllers\CreditMessageController::class, 'send'])
                ->name('credit-messaging.sites.messages.send');
            Route::post('/{message}/schedule', [\Techigh\CreditMessaging\Http\Controllers\CreditMessageController::class, 'schedule'])
                ->name('credit-messaging.sites.messages.schedule');
            Route::post('/{message}/cancel', [\Techigh\CreditMessaging\Http\Controllers\CreditMessageController::class, 'cancel'])
                ->name('credit-messaging.sites.messages.cancel');
            Route::get('/{message}/status', [\Techigh\CreditMessaging\Http\Controllers\CreditMessageController::class, 'getStatus'])
                ->name('credit-messaging.sites.messages.status');
        });

        // Credit Usage Reports
        Route::prefix('sites/{site_id}/usage')->group(function () {
            Route::get('/', [\Techigh\CreditMessaging\Http\Controllers\SiteCreditUsageController::class, 'index'])
                ->name('credit-messaging.sites.usage.index');
            Route::get('/export', [\Techigh\CreditMessaging\Http\Controllers\SiteCreditUsageController::class, 'export'])
                ->name('credit-messaging.sites.usage.export');
            Route::get('/stats', [\Techigh\CreditMessaging\Http\Controllers\SiteCreditUsageController::class, 'getStats'])
                ->name('credit-messaging.sites.usage.stats');
            Route::post('/{usage}/refund', [\Techigh\CreditMessaging\Http\Controllers\SiteCreditUsageController::class, 'refund'])
                ->name('credit-messaging.sites.usage.refund');
        });

        // Payment Management
        Route::prefix('sites/{site_id}/payments')->group(function () {
            Route::get('/', [\Techigh\CreditMessaging\Http\Controllers\SiteCreditPaymentController::class, 'index'])
                ->name('credit-messaging.sites.payments.index');
            Route::post('/', [\Techigh\CreditMessaging\Http\Controllers\SiteCreditPaymentController::class, 'store'])
                ->name('credit-messaging.sites.payments.store');
            Route::get('/{payment}', [\Techigh\CreditMessaging\Http\Controllers\SiteCreditPaymentController::class, 'show'])
                ->name('credit-messaging.sites.payments.show');
            Route::put('/{payment}/complete', [\Techigh\CreditMessaging\Http\Controllers\SiteCreditPaymentController::class, 'complete'])
                ->name('credit-messaging.sites.payments.complete');
            Route::put('/{payment}/fail', [\Techigh\CreditMessaging\Http\Controllers\SiteCreditPaymentController::class, 'fail'])
                ->name('credit-messaging.sites.payments.fail');
        });

        // Send Logs Management
        Route::prefix('sites/{site_id}/send-logs')->group(function () {
            Route::get('/', [\Techigh\CreditMessaging\Http\Controllers\MessageSendLogController::class, 'index'])
                ->name('credit-messaging.sites.send-logs.index');
            Route::get('/{log}', [\Techigh\CreditMessaging\Http\Controllers\MessageSendLogController::class, 'show'])
                ->name('credit-messaging.sites.send-logs.show');
            Route::post('/{log}/retry-settlement', [\Techigh\CreditMessaging\Http\Controllers\MessageSendLogController::class, 'retrySettlement'])
                ->name('credit-messaging.sites.send-logs.retry-settlement');
        });
    });
