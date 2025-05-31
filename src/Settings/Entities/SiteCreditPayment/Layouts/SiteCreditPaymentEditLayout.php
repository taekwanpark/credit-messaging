<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCreditPayment\Layouts;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Layouts\Rows;

class SiteCreditPaymentEditLayout extends Rows
{
    /**
     * The screen's layout elements.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Input::make('siteCreditPayment.site_id')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('Site ID'))
                ->placeholder(__('Site ID'))
                ->help(__('Site identifier for this payment')),

            Input::make('siteCreditPayment.amount')
                ->type('number')
                ->step(0.01)
                ->min(0)
                ->required()
                ->title(__('Amount'))
                ->placeholder(__('Payment amount'))
                ->help(__('Payment amount in credits')),

            Select::make('siteCreditPayment.payment_method')
                ->options([
                    'card' => 'Credit Card',
                    'bank' => 'Bank Transfer',
                    'virtual' => 'Virtual Account',
                    'admin' => 'Admin Manual',
                ])
                ->required()
                ->title(__('Payment Method'))
                ->help(__('Method used for payment')),

            Select::make('siteCreditPayment.status')
                ->options([
                    'pending' => 'Pending',
                    'completed' => 'Completed',
                    'failed' => 'Failed',
                    'cancelled' => 'Cancelled',
                ])
                ->required()
                ->title(__('Status'))
                ->help(__('Current payment status')),

            Input::make('siteCreditPayment.transaction_id')
                ->type('text')
                ->max(255)
                ->title(__('Transaction ID'))
                ->placeholder(__('Transaction identifier'))
                ->help(__('Unique transaction identifier')),

            Input::make('siteCreditPayment.payment_gateway')
                ->type('text')
                ->max(255)
                ->title(__('Payment Gateway'))
                ->placeholder(__('Gateway used'))
                ->help(__('Payment gateway provider')),

            TextArea::make('siteCreditPayment.notes')
                ->rows(3)
                ->title(__('Notes'))
                ->placeholder(__('Additional notes'))
                ->help(__('Any additional notes about the payment')),

            DateTimer::make('siteCreditPayment.completed_at')
                ->title(__('Completed At'))
                ->help(__('When the payment was completed')),
        ];
    }
}