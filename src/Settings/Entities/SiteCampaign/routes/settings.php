<?php

use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;
use Techigh\CreditMessaging\Settings\Entities\SiteCampaign\Screens\SiteCampaignEditScreen;
use Techigh\CreditMessaging\Settings\Entities\SiteCampaign\Screens\SiteCampaignListScreen;

// Platform > System > Site Credit Usages > Log
Route::screen('site-campaigns/{siteCampaign}/edit', SiteCampaignEditScreen::class)
    ->name('site_campaigns.edit')
    ->breadcrumbs(fn(Trail $trail, $siteCampaign) => $trail
        ->parent('settings.entities.site_campaigns')
        ->push($siteCampaign->id, route('settings.entities.site_campaigns.edit', $siteCampaign)));

// Platform > System > Site Credit Usages > Create
Route::screen('site-campaigns/create', SiteCampaignEditScreen::class)
    ->name('site_campaigns.create')
    ->breadcrumbs(fn(Trail $trail) => $trail
        ->parent('settings.entities.site_campaigns')
        ->push(__('Create'), route('settings.entities.site_campaigns.create')));

// Platform > System > Site Credit Usages
Route::screen('site-campaigns', SiteCampaignListScreen::class)
    ->name('site_campaigns')
    ->breadcrumbs(fn(Trail $trail) => $trail
        ->parent('settings.index')
        ->push(__('Site Credit Usages'), route('settings.entities.site_campaigns')));
