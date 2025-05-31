<?php

use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\Screens\SiteCreditUsageEditScreen;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\Screens\SiteCreditUsageListScreen;

// Platform > System > Site Credit Usages > Usage
Route::screen('site-credit-usages/{siteCreditUsage}/edit', SiteCreditUsageEditScreen::class)
    ->name('site_credit_usages.edit')
    ->breadcrumbs(fn (Trail $trail, $siteCreditUsage) => $trail
        ->parent('settings.entities.site_credit_usages')
        ->push($siteCreditUsage->id, route('settings.entities.site_credit_usages.edit', $siteCreditUsage)));

// Platform > System > Site Credit Usages > Create
Route::screen('site-credit-usages/create', SiteCreditUsageEditScreen::class)
    ->name('site_credit_usages.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('settings.entities.site_credit_usages')
        ->push(__('Create'), route('settings.entities.site_credit_usages.create')));

// Platform > System > Site Credit Usages
Route::screen('site-credit-usages', SiteCreditUsageListScreen::class)
    ->name('site_credit_usages')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('settings.index')
        ->push(__('Site Credit Usages'), route('settings.entities.site_credit_usages')));