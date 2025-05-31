<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\Layouts;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Layouts\Rows;

class SiteCreditUsageEditLayout extends Rows
{
    /**
     * The screen's layout elements.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Input::make('siteCreditUsage.site_id')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('Site ID'))
                ->placeholder(__('Site ID'))
                ->help(__('Site identifier for this usage')),

            Select::make('siteCreditUsage.message_type')
                ->options([
                    'alimtalk' => 'Alimtalk',
                    'sms' => 'SMS',
                    'lms' => 'LMS',
                    'mms' => 'MMS',
                ])
                ->title(__('Message Type'))
                ->help(__('Type of message')),

            Input::make('siteCreditUsage.quantity')
                ->type('number')
                ->min(0)
                ->required()
                ->title(__('Quantity'))
                ->placeholder(__('Number of messages'))
                ->help(__('Number of messages sent')),

            Input::make('siteCreditUsage.cost_per_unit')
                ->type('number')
                ->step(0.01)
                ->min(0)
                ->required()
                ->title(__('Cost Per Unit'))
                ->placeholder(__('Cost per message'))
                ->help(__('Cost per individual message')),

            Input::make('siteCreditUsage.total_cost')
                ->type('number')
                ->step(0.01)
                ->min(0)
                ->required()
                ->title(__('Total Cost'))
                ->placeholder(__('Total cost'))
                ->help(__('Total cost for all messages')),

            Input::make('siteCreditUsage.refund_amount')
                ->type('number')
                ->step(0.01)
                ->min(0)
                ->title(__('Refund Amount'))
                ->placeholder(__('Refunded amount'))
                ->help(__('Amount refunded if any')),

            TextArea::make('siteCreditUsage.refund_reason')
                ->rows(3)
                ->title(__('Refund Reason'))
                ->placeholder(__('Reason for refund'))
                ->help(__('Explanation for refund')),

            DateTimer::make('siteCreditUsage.refunded_at')
                ->title(__('Refunded At'))
                ->help(__('When the refund was processed')),

            Select::make('siteCreditUsage.status')
                ->options([
                    'reserved' => 'Reserved',
                    'used' => 'Used',
                    'failed' => 'Failed',
                    'refunded' => 'Refunded',
                ])
                ->title(__('Status'))
                ->help(__('Current status of usage')),

            Input::make('siteCreditUsage.batch_id')
                ->type('text')
                ->title(__('Batch ID'))
                ->placeholder(__('Batch identifier'))
                ->help(__('Batch identifier for grouping')),
        ];
    }
}