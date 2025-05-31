<?php

namespace Techigh\CreditMessaging\Settings\Entities\MessageSendLog\Layouts;

use Orchid\Screen\TD;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Layouts\Table;
use Techigh\CreditMessaging\Settings\Entities\MessageSendLog\MessageSendLog;

class MessageSendLogListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'messageSendLogs';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('id', __('ID'))
                ->sort()
                ->cantHide()
                ->render(function (MessageSendLog $log) {
                    return Link::make($log->id)
                        ->route('settings.entities.message_send_logs.edit', $log);
                }),

            TD::make('usage_id', __('Usage ID'))
                ->sort()
                ->render(function (MessageSendLog $log) {
                    return $log->usage_id ?: '-';
                }),

            TD::make('message_type', __('Type'))
                ->sort()
                ->render(function (MessageSendLog $log) {
                    return $log->message_type_display;
                }),

            TD::make('total_count', __('Total'))
                ->sort()
                ->render(function (MessageSendLog $log) {
                    return number_format($log->total_count);
                }),

            TD::make('success_count', __('Success'))
                ->sort()
                ->render(function (MessageSendLog $log) {
                    return number_format($log->success_count);
                }),

            TD::make('failed_count', __('Failed'))
                ->sort()
                ->render(function (MessageSendLog $log) {
                    return number_format($log->failed_count);
                }),

            TD::make('success_rate', __('Success Rate'))
                ->render(function (MessageSendLog $log) {
                    $rate = $log->success_rate;
                    $color = $rate >= 90 ? 'text-success' : ($rate >= 70 ? 'text-warning' : 'text-danger');
                    return "<span class='{$color}'>{$rate}%</span>";
                }),

            TD::make('settlement_status', __('Settlement'))
                ->sort()
                ->render(function (MessageSendLog $log) {
                    $color = match($log->settlement_status) {
                        'completed' => 'bg-success',
                        'failed' => 'bg-danger',
                        'processing' => 'bg-warning',
                        'pending' => 'bg-info',
                        default => 'bg-secondary'
                    };
                    return "<span class='badge {$color}'>" . ucfirst($log->settlement_status) . "</span>";
                }),

            TD::make('final_cost', __('Final Cost'))
                ->sort()
                ->render(function (MessageSendLog $log) {
                    return $log->final_cost ? number_format($log->final_cost, 2) : '-';
                }),

            TD::make('created_at', __('Created'))
                ->sort()
                ->render(function (MessageSendLog $log) {
                    return $log->created_at->format('Y-m-d H:i');
                }),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(function (MessageSendLog $log) {
                    return DropDown::make()
                        ->icon('options-vertical')
                        ->list([
                            Link::make(__('Edit'))
                                ->route('settings.entities.message_send_logs.edit', $log)
                                ->icon('pencil'),

                            Button::make(__('Delete'))
                                ->icon('trash')
                                ->confirm(__('Are you sure you want to delete this send log?'))
                                ->method('remove')
                                ->parameters([
                                    'id' => $log->id,
                                ]),
                        ]);
                }),
        ];
    }
}