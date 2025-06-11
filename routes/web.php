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
])->group(function () {
    Route::get('/site-credit/payment', [PaymentController::class, 'create'])->name('site-credit.payment');

    Route::post('/site-credit/store', [PaymentController::class, 'store'])->name('site-credit.store');

    Route::post('/site-credit/destroy/{orderId}', [PaymentController::class, 'destroy'])->name('site-credit.destroy');

    Route::get('/site-credit/payment/success', [PaymentController::class, 'success'])->name('site-credit.payments.success');

    Route::get('/site-credit/payment/fail', [PaymentController::class, 'fail'])->name('site-credit.payments.fail');
});
