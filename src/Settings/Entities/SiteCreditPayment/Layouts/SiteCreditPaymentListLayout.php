<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCreditPayment\Layouts;

use Orchid\Screen\TD;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Layouts\Table;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditPayment\SiteCreditPayment;

class SiteCreditPaymentListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'siteCreditPayments';

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
                ->render(function (SiteCreditPayment $payment) {
                    return Link::make($payment->site_id)
                        ->route('settings.entities.site_credit_payments.edit', $payment);
                }),

            TD::make('transaction_id', __('Transaction ID'))
                ->sort()
                ->render(function (SiteCreditPayment $payment) {
                    return $payment->transaction_id ?: '-';
                }),

            TD::make('amount', __('Amount'))
                ->sort()
                ->render(function (SiteCreditPayment $payment) {
                    return number_format($payment->amount, 2);
                }),

            TD::make('payment_method', __('Method'))
                ->sort()
                ->render(function (SiteCreditPayment $payment) {
                    return $payment->payment_method_display;
                }),

            TD::make('status', __('Status'))
                ->sort()
                ->render(function (SiteCreditPayment $payment) {
                    $color = match($payment->status) {
                        'completed' => 'bg-success',
                        'failed' => 'bg-danger',
                        'cancelled' => 'bg-warning',
                        'pending' => 'bg-info',
                        default => 'bg-secondary'
                    };
                    return "<span class='badge {$color}'>" . ucfirst($payment->status) . "</span>";
                }),

            TD::make('payment_gateway', __('Gateway'))
                ->sort()
                ->render(function (SiteCreditPayment $payment) {
                    return $payment->payment_gateway ?: '-';
                }),

            TD::make('completed_at', __('Completed'))
                ->sort()
                ->render(function (SiteCreditPayment $payment) {
                    return $payment->completed_at ? $payment->completed_at->format('Y-m-d H:i') : '-';
                }),

            TD::make('created_at', __('Created'))
                ->sort()
                ->render(function (SiteCreditPayment $payment) {
                    return $payment->created_at->format('Y-m-d H:i');
                }),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(function (SiteCreditPayment $payment) {
                    return DropDown::make()
                        ->icon('options-vertical')
                        ->list([
                            Link::make(__('Edit'))
                                ->route('settings.entities.site_credit_payments.edit', $payment)
                                ->icon('pencil'),

                            Button::make(__('Delete'))
                                ->icon('trash')
                                ->confirm(__('Are you sure you want to delete this payment record?'))
                                ->method('remove')
                                ->parameters([
                                    'id' => $payment->id,
                                ]),
                        ]);
                }),
        ];
    }
}