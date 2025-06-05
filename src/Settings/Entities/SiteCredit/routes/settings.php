<?php

use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;
use Techigh\CreditMessaging\Settings\Entities\SiteCredit\Screens\SiteCreditEditScreen;
use Techigh\CreditMessaging\Settings\Entities\SiteCredit\Screens\SiteCreditListScreen;

// Platform > System > Site Credit Usages > Log
Route::screen('site-credits/{siteCredit}/edit', SiteCreditEditScreen::class)
    ->name('site_credits.edit')
    ->breadcrumbs(fn(Trail $trail, $siteCredit) => $trail
        ->parent('settings.entities.site_credits')
        ->push($siteCredit->id, route('settings.entities.site_credits.edit', $siteCredit)));

// Platform > System > Site Credit Usages > Create
Route::screen('site-credits/create', SiteCreditEditScreen::class)
    ->name('site_credits.create')
    ->breadcrumbs(fn(Trail $trail) => $trail
        ->parent('settings.entities.site_credits')
        ->push(__('Create'), route('settings.entities.site_credits.create')));

// Platform > System > Site Credit Usages
Route::screen('site-credits', SiteCreditListScreen::class)
    ->name('site_credits')
    ->breadcrumbs(fn(Trail $trail) => $trail
        ->parent('settings.index')
        ->push(__('Site Credit Usages'), route('settings.entities.site_credits')));
