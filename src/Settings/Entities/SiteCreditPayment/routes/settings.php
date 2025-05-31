<?php

use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditPayment\Screens\SiteCreditPaymentEditScreen;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditPayment\Screens\SiteCreditPaymentListScreen;

// Platform > System > Site Credit Payments > Payment
Route::screen('site-credit-payments/{siteCreditPayment}/edit', SiteCreditPaymentEditScreen::class)
    ->name('site_credit_payments.edit')
    ->breadcrumbs(fn (Trail $trail, $siteCreditPayment) => $trail
        ->parent('settings.entities.site_credit_payments')
        ->push($siteCreditPayment->transaction_id, route('settings.entities.site_credit_payments.edit', $siteCreditPayment)));

// Platform > System > Site Credit Payments > Create
Route::screen('site-credit-payments/create', SiteCreditPaymentEditScreen::class)
    ->name('site_credit_payments.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('settings.entities.site_credit_payments')
        ->push(__('Create'), route('settings.entities.site_credit_payments.create')));

// Platform > System > Site Credit Payments
Route::screen('site-credit-payments', SiteCreditPaymentListScreen::class)
    ->name('site_credit_payments')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('settings.index')
        ->push(__('Site Credit Payments'), route('settings.entities.site_credit_payments')));