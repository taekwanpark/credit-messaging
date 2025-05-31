<?php

use Illuminate\Support\Facades\Route;
use Techigh\CreditMessaging\Http\Controllers\WebhookController;
use Techigh\CreditMessaging\Http\Controllers\CreditMessageController;
use Techigh\CreditMessaging\Http\Controllers\SiteCreditController;
use Techigh\CreditMessaging\Http\Controllers\SiteCreditPaymentController;
use Techigh\CreditMessaging\Http\Controllers\SiteCreditUsageController;
use Techigh\CreditMessaging\Http\Controllers\MessageSendLogController;
use Techigh\CreditMessaging\Http\Controllers\TestController;

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

    // Webhook endpoints for message service providers
    Route::post('/webhook/{provider}', [WebhookController::class, 'handle'])
        ->name('credit-messaging.webhook');

    // Health check endpoint
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'service' => 'credit-messaging',
            'timestamp' => now()->toISOString()
        ]);
    })->name('credit-messaging.health');

    // Test endpoints for service provider bindings (개발용)
    Route::prefix('test')->group(function () {
        Route::get('/bindings', [TestController::class, 'testBindings']);
        Route::get('/facades', [TestController::class, 'testFacades']);
        Route::get('/config', [TestController::class, 'testConfig']);
        Route::get('/binding-identity', [TestController::class, 'testBindingIdentity']);
        Route::get('/current-status', [TestController::class, 'testCurrentBindingStatus']);
    });

    // API routes for authenticated users
    Route::middleware(['auth:sanctum'])->group(function () {

        // Credit Messages API
        //        Route::prefix('sites/{siteId}/messages')->group(function () {
        //            Route::get('/', [CreditMessageController::class, 'index'])->name('credit-messages.index');
        //            Route::post('/', [CreditMessageController::class, 'store'])->name('credit-messages.store');
        //            Route::get('/{message}', [CreditMessageController::class, 'show'])->name('credit-messages.show');
        //            Route::put('/{message}', [CreditMessageController::class, 'update'])->name('credit-messages.update');
        //            Route::delete('/{message}', [CreditMessageController::class, 'destroy'])->name('credit-messages.destroy');
        //
        //            // Message actions
        //            Route::post('/{message}/send', [CreditMessageController::class, 'send'])->name('credit-messages.send');
        //            Route::post('/{message}/schedule', [CreditMessageController::class, 'schedule'])->name('credit-messages.schedule');
        //            Route::post('/{message}/cancel', [CreditMessageController::class, 'cancel'])->name('credit-messages.cancel');
        //            Route::get('/{message}/estimate', [CreditMessageController::class, 'estimate'])->name('credit-messages.estimate');
        //            Route::get('/{message}/status', [CreditMessageController::class, 'status'])->name('credit-messages.status');
        //        });

        // Site Credits API
        //        Route::prefix('sites/{siteId}/credits')->group(function () {
        //            Route::get('/', [SiteCreditController::class, 'index'])->name('site-credits.index');
        //            Route::post('/', [SiteCreditController::class, 'store'])->name('site-credits.store');
        //            Route::get('/balance', [SiteCreditController::class, 'getBalance'])->name('site-credits.balance');
        //            Route::get('/config', [SiteCreditController::class, 'getConfig'])->name('site-credits.config');
        //            Route::put('/config', [SiteCreditController::class, 'updateConfig'])->name('site-credits.update-config');
        //
        //            // Credit operations
        //            Route::post('/charge', [SiteCreditController::class, 'charge'])->name('site-credits.charge');
        //            Route::post('/refund', [SiteCreditController::class, 'refund'])->name('site-credits.refund');
        //            Route::get('/statistics', [SiteCreditController::class, 'statistics'])->name('site-credits.statistics');
        //        });

        // Site Credit Payments API
        Route::prefix('sites/{siteId}/payments')->group(function () {
            //            Route::get('/', [SiteCreditPaymentController::class, 'index'])->name('site-credit-payments.index');
            Route::post('/', [SiteCreditPaymentController::class, 'store'])->name('site-credit-payments.store');
            //            Route::get('/{payment}', [SiteCreditPaymentController::class, 'show'])->name('site-credit-payments.show');
            //            Route::put('/{payment}', [SiteCreditPaymentController::class, 'update'])->name('site-credit-payments.update');

            // Payment actions
            //            Route::post('/{payment}/complete', [SiteCreditPaymentController::class, 'complete'])->name('site-credit-payments.complete');
            //            Route::post('/{payment}/cancel', [SiteCreditPaymentController::class, 'cancel'])->name('site-credit-payments.cancel');
            //            Route::post('/{payment}/refund', [SiteCreditPaymentController::class, 'refund'])->name('site-credit-payments.refund');
        });

        // Site Credit Usages API
        //        Route::prefix('sites/{siteId}/usages')->group(function () {
        //            Route::get('/', [SiteCreditUsageController::class, 'index'])->name('site-credit-usages.index');
        //            Route::get('/{usage}', [SiteCreditUsageController::class, 'show'])->name('site-credit-usages.show');
        //
        //            // Usage actions
        //            Route::post('/{usage}/refund', [SiteCreditUsageController::class, 'refund'])->name('site-credit-usages.refund');
        //            Route::get('/summary/daily', [SiteCreditUsageController::class, 'dailySummary'])->name('site-credit-usages.daily-summary');
        //            Route::get('/summary/monthly', [SiteCreditUsageController::class, 'monthlySummary'])->name('site-credit-usages.monthly-summary');
        //        });
        //
        //        // Message Send Logs API
        //        Route::prefix('sites/{siteId}/send-logs')->group(function () {
        //            Route::get('/', [MessageSendLogController::class, 'index'])->name('message-send-logs.index');
        //            Route::get('/{log}', [MessageSendLogController::class, 'show'])->name('message-send-logs.show');
        //            Route::get('/message/{messageId}', [MessageSendLogController::class, 'getByMessage'])->name('message-send-logs.by-message');
        //
        //            // Log analysis
        //            Route::get('/analytics/delivery-rate', [MessageSendLogController::class, 'deliveryRate'])->name('message-send-logs.delivery-rate');
        //            Route::get('/analytics/failure-reasons', [MessageSendLogController::class, 'failureReasons'])->name('message-send-logs.failure-reasons');
        //        });

        // Global API endpoints (not site-specific)
        //        Route::prefix('admin')->group(function () {
        //            // System-wide statistics
        //            Route::get('/statistics/overview', function () {
        //                return response()->json([
        //                    'total_sites' => \Techigh\CreditMessaging\Settings\Entities\SiteCredit\SiteCredit::distinct('site_id')->count(),
        //                    'total_messages' => \Techigh\CreditMessaging\Settings\Entities\CreditMessage\CreditMessage::count(),
        //                    'total_revenue' => \Techigh\CreditMessaging\Settings\Entities\SiteCreditPayment\SiteCreditPayment::where('status', 'completed')->sum('amount'),
        //                    'active_sites' => \Techigh\CreditMessaging\Settings\Entities\SiteCredit\SiteCredit::where('balance', '>', 0)->distinct('site_id')->count(),
        //                ]);
        //            })->name('credit-messaging.admin.overview');
        //
        //            // Health check with detailed info
        //            Route::get('/health/detailed', function () {
        //                $queueSize = \Illuminate\Support\Facades\Queue::size('credit-messaging');
        //                $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')->where('queue', 'credit-messaging')->count();
        //
        //                return response()->json([
        //                    'status' => 'ok',
        //                    'service' => 'credit-messaging',
        //                    'timestamp' => now()->toISOString(),
        //                    'queue_size' => $queueSize,
        //                    'failed_jobs' => $failedJobs,
        //                    'database_connection' => \Illuminate\Support\Facades\DB::connection()->getPdo() ? 'ok' : 'failed',
        //                ]);
        //            })->name('credit-messaging.admin.health');
        //        });
    });
});
