<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\Layouts;

use Orchid\Screen\TD;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Layouts\Table;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\SiteCreditUsage;

class SiteCreditUsageListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'siteCreditUsages';

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
                ->render(function (SiteCreditUsage $usage) {
                    return Link::make($usage->site_id)
                        ->route('settings.entities.site_credit_usages.edit', $usage);
                }),

            TD::make('message_type', __('Type'))
                ->sort()
                ->render(function (SiteCreditUsage $usage) {
                    return $usage->message_type_display;
                }),

            TD::make('quantity', __('Quantity'))
                ->sort()
                ->render(function (SiteCreditUsage $usage) {
                    return number_format($usage->quantity);
                }),

            TD::make('total_cost', __('Total Cost'))
                ->sort()
                ->render(function (SiteCreditUsage $usage) {
                    return number_format($usage->total_cost, 2);
                }),

            TD::make('status', __('Status'))
                ->sort()
                ->render(function (SiteCreditUsage $usage) {
                    $color = match($usage->status) {
                        'used' => 'bg-success',
                        'failed' => 'bg-danger',
                        'refunded' => 'bg-warning',
                        'reserved' => 'bg-info',
                        default => 'bg-secondary'
                    };
                    return "<span class='badge {$color}'>" . ucfirst($usage->status) . "</span>";
                }),

            TD::make('refund_amount', __('Refund'))
                ->sort()
                ->render(function (SiteCreditUsage $usage) {
                    return $usage->refund_amount > 0 ? number_format($usage->refund_amount, 2) : '-';
                }),

            TD::make('created_at', __('Created'))
                ->sort()
                ->render(function (SiteCreditUsage $usage) {
                    return $usage->created_at->format('Y-m-d H:i');
                }),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(function (SiteCreditUsage $usage) {
                    return DropDown::make()
                        ->icon('options-vertical')
                        ->list([
                            Link::make(__('Edit'))
                                ->route('settings.entities.site_credit_usages.edit', $usage)
                                ->icon('pencil'),

                            Button::make(__('Delete'))
                                ->icon('trash')
                                ->confirm(__('Are you sure you want to delete this usage record?'))
                                ->method('remove')
                                ->parameters([
                                    'id' => $usage->id,
                                ]),
                        ]);
                }),
        ];
    }
}