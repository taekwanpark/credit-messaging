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
});
