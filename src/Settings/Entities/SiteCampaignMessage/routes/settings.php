<?php

use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;
use Techigh\CreditMessaging\Settings\Entities\SiteCampaignMessage\Screens\SiteCampaignMessageEditScreen;
use Techigh\CreditMessaging\Settings\Entities\SiteCampaignMessage\Screens\SiteCampaignMessageListScreen;

// Platform > System > Site Credit Usages > Log
Route::screen('site-campaign-messages/{siteCampaignMessage}/edit', SiteCampaignMessageEditScreen::class)
    ->name('site_campaign_messages.edit')
    ->breadcrumbs(fn(Trail $trail, $siteCampaignMessage) => $trail
        ->parent('settings.entities.site_campaign_messages')
        ->push($siteCampaignMessage->id, route('settings.entities.site_campaign_messages.edit', $siteCampaignMessage)));

// Platform > System > Site Credit Usages > Create
Route::screen('site-campaign-messages/create', SiteCampaignMessageEditScreen::class)
    ->name('site_campaign_messages.create')
    ->breadcrumbs(fn(Trail $trail) => $trail
        ->parent('settings.entities.site_campaign_messages')
        ->push(__('Create'), route('settings.entities.site_campaign_messages.create')));

// Platform > System > Site Credit Usages
Route::screen('site-campaign-messages', SiteCampaignMessageListScreen::class)
    ->name('site_campaign_messages')
    ->breadcrumbs(fn(Trail $trail) => $trail
        ->parent('settings.index')
        ->push(__('Site Credit Usages'), route('settings.entities.site_campaign_messages')));
