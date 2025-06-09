<?php

use Illuminate\Support\Facades\Route;
use Techigh\CreditMessaging\Settings\Entities\SitePlan\Screens\SitePlanEditScreen;
use Techigh\CreditMessaging\Settings\Entities\SitePlan\Screens\SitePlanListScreen;
use Tabuna\Breadcrumbs\Trail;

// Platform > System > SitePlans > SitePlan
Route::screen('site-plans/{sitePlan}/edit', SitePlanEditScreen::class)
    ->name('site_plans.edit')
    ->breadcrumbs(fn(Trail $trail, $sitePlan) => $trail
        ->parent('settings.entities.site_plans')
        ->push($sitePlan->title, route('settings.entities.site_plans.edit', $sitePlan)));

// Platform > System > SitePlans > Create
Route::screen('site-plans/create', SitePlanEditScreen::class)
    ->name('site_plans.create')
    ->breadcrumbs(fn(Trail $trail) => $trail
        ->parent('settings.entities.site_plans')
        ->push(__('Create'), route('settings.entities.site_plans.create')));

// Platform > System > SitePlans
Route::screen('site-plans', SitePlanListScreen::class)
    ->name('site_plans')
    ->breadcrumbs(fn(Trail $trail) => $trail
        ->parent('settings.index')
        ->push(__('SitePlans'), route('settings.entities.site_plans')));
