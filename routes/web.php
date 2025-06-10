<?php

use Illuminate\Support\Facades\Route;
use Techigh\CreditMessaging\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| Credit Messaging Web Routes
|--------------------------------------------------------------------------
|
| 크레딧 메시징 시스템의 웹 라우트를 정의합니다.
|
*/

Route::middleware([
    'web',
    'universal',
    'tenancy',
    'frontend'
])->get('/settings/site-credit/payment/{siteCredit}', [PaymentController::class, 'show'])
    ->name('sitecredit.payment')
    ->where('siteCredit', '[0-9]+');
