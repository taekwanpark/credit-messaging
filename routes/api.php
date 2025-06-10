<?php

use Illuminate\Support\Facades\Route;
use Techigh\CreditMessaging\Http\Controllers\WebhookController;
use Techigh\CreditMessaging\Settings\Entities\SiteCredit\SiteCredit;

/*
|--------------------------------------------------------------------------
| Credit Messaging API Routes
|--------------------------------------------------------------------------
|
| 크레딧 메시징 시스템의 API 라우트를 정의합니다.
|
*/

Route::post(config('credit-messaging.webhook.uri'), [WebhookController::class, 'handleDeliveryStatus'])
    ->name('credit-messaging.webhook.delivery-status');
