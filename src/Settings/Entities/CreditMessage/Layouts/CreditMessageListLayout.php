<?php

namespace Techigh\CreditMessaging\Settings\Entities\CreditMessage\Layouts;

use Orchid\Screen\TD;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Layouts\Table;
use Techigh\CreditMessaging\Settings\Entities\CreditMessage\CreditMessage;

class CreditMessageListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'creditMessages';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('site_id', __('Site ID'))
                ->sort()
                ->cantHide()
                ->filter()
                ->render(function (CreditMessage $creditMessage) {
                    return Link::make($creditMessage->site_id)
                        ->route('settings.entities.credit_messages.edit', $creditMessage);
                }),

            TD::make('title', __('Title'))
                ->sort()
                ->render(function (CreditMessage $creditMessage) {
                    return $creditMessage->getTitleForLocale();
                }),

            TD::make('message_type', __('Type'))
                ->sort()
                ->render(function (CreditMessage $creditMessage) {
                    return $creditMessage->message_type_display;
                }),

            TD::make('status', __('Status'))
                ->sort()
                ->render(function (CreditMessage $creditMessage) {
                    $color = match($creditMessage->status) {
                        'completed' => 'bg-success',
                        'failed' => 'bg-danger',
                        'sending' => 'bg-warning',
                        'scheduled' => 'bg-info',
                        default => 'bg-secondary'
                    };
                    return "<span class='badge {$color}'>" . ucfirst($creditMessage->status) . "</span>";
                }),

            TD::make('total_recipients', __('Recipients'))
                ->sort()
                ->render(function (CreditMessage $creditMessage) {
                    return number_format($creditMessage->total_recipients);
                }),

            TD::make('estimated_cost', __('Est. Cost'))
                ->sort()
                ->render(function (CreditMessage $creditMessage) {
                    return number_format($creditMessage->estimated_cost, 2);
                }),

            TD::make('created_at', __('Created'))
                ->sort()
                ->render(function (CreditMessage $creditMessage) {
                    return $creditMessage->created_at->format('Y-m-d H:i');
                }),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(function (CreditMessage $creditMessage) {
                    return DropDown::make()
                        ->icon('options-vertical')
                        ->list([
                            Link::make(__('Edit'))
                                ->route('settings.entities.credit_messages.edit', $creditMessage)
                                ->icon('pencil'),

                            Button::make(__('Delete'))
                                ->icon('trash')
                                ->confirm(__('Are you sure you want to delete this message?'))
                                ->method('remove')
                                ->parameters([
                                    'id' => $creditMessage->id,
                                ]),
                        ]);
                }),
        ];
    }
}