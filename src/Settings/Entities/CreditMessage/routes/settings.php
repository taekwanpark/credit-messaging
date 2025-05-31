<?php

use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;
use Techigh\CreditMessaging\Settings\Entities\CreditMessage\Screens\CreditMessageEditScreen;
use Techigh\CreditMessaging\Settings\Entities\CreditMessage\Screens\CreditMessageListScreen;

// Platform > System > Credit Messages > Message
Route::screen('credit-messages/{creditMessage}/edit', CreditMessageEditScreen::class)
    ->name('credit_messages.edit')
    ->breadcrumbs(fn (Trail $trail, $creditMessage) => $trail
        ->parent('settings.entities.credit_messages')
        ->push($creditMessage->getTitleForLocale(), route('settings.entities.credit_messages.edit', $creditMessage)));

// Platform > System > Credit Messages > Create
Route::screen('credit-messages/create', CreditMessageEditScreen::class)
    ->name('credit_messages.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('settings.entities.credit_messages')
        ->push(__('Create'), route('settings.entities.credit_messages.create')));

// Platform > System > Credit Messages
Route::screen('credit-messages', CreditMessageListScreen::class)
    ->name('credit_messages')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('settings.index')
        ->push(__('Credit Messages'), route('settings.entities.credit_messages')));