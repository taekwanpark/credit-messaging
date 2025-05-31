<?php

use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;
use Techigh\CreditMessaging\Settings\Entities\MessageSendLog\Screens\MessageSendLogEditScreen;
use Techigh\CreditMessaging\Settings\Entities\MessageSendLog\Screens\MessageSendLogListScreen;

// Platform > System > Message Send Logs > Log
Route::screen('message-send-logs/{messageSendLog}/edit', MessageSendLogEditScreen::class)
    ->name('message_send_logs.edit')
    ->breadcrumbs(fn (Trail $trail, $messageSendLog) => $trail
        ->parent('settings.entities.message_send_logs')
        ->push($messageSendLog->id, route('settings.entities.message_send_logs.edit', $messageSendLog)));

// Platform > System > Message Send Logs > Create
Route::screen('message-send-logs/create', MessageSendLogEditScreen::class)
    ->name('message_send_logs.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('settings.entities.message_send_logs')
        ->push(__('Create'), route('settings.entities.message_send_logs.create')));

// Platform > System > Message Send Logs
Route::screen('message-send-logs', MessageSendLogListScreen::class)
    ->name('message_send_logs')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('settings.index')
        ->push(__('Message Send Logs'), route('settings.entities.message_send_logs')));