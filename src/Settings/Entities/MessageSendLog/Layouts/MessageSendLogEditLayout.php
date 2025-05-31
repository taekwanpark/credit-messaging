<?php

namespace Techigh\CreditMessaging\Settings\Entities\MessageSendLog\Layouts;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Layouts\Rows;

class MessageSendLogEditLayout extends Rows
{
    /**
     * The screen's layout elements.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Input::make('messageSendLog.usage_id')
                ->type('number')
                ->min(0)
                ->title(__('Usage ID'))
                ->placeholder(__('Related usage record ID'))
                ->help(__('ID of the related usage record')),

            Select::make('messageSendLog.message_type')
                ->options([
                    'alimtalk' => 'Alimtalk',
                    'sms' => 'SMS',
                    'lms' => 'LMS',
                    'mms' => 'MMS',
                ])
                ->title(__('Message Type'))
                ->help(__('Type of message sent')),

            Input::make('messageSendLog.total_count')
                ->type('number')
                ->min(0)
                ->title(__('Total Count'))
                ->placeholder(__('Total number of messages'))
                ->help(__('Total number of messages in this batch')),

            Input::make('messageSendLog.success_count')
                ->type('number')
                ->min(0)
                ->title(__('Success Count'))
                ->placeholder(__('Successful deliveries'))
                ->help(__('Number of successfully delivered messages')),

            Input::make('messageSendLog.failed_count')
                ->type('number')
                ->min(0)
                ->title(__('Failed Count'))
                ->placeholder(__('Failed deliveries'))
                ->help(__('Number of failed message deliveries')),

            Select::make('messageSendLog.settlement_status')
                ->options([
                    'pending' => 'Pending',
                    'processing' => 'Processing',
                    'completed' => 'Completed',
                    'failed' => 'Failed',
                ])
                ->title(__('Settlement Status'))
                ->help(__('Status of cost settlement')),

            Input::make('messageSendLog.final_cost')
                ->type('number')
                ->step(0.01)
                ->min(0)
                ->title(__('Final Cost'))
                ->placeholder(__('Final settled cost'))
                ->help(__('Final cost after settlement')),

            DateTimer::make('messageSendLog.settled_at')
                ->title(__('Settled At'))
                ->help(__('When the settlement was completed')),

            DateTimer::make('messageSendLog.webhook_received_at')
                ->title(__('Webhook Received At'))
                ->help(__('When webhook was received')),

            TextArea::make('messageSendLog.error_message')
                ->rows(3)
                ->title(__('Error Message'))
                ->placeholder(__('Any error messages'))
                ->help(__('Error messages if settlement failed')),

            Input::make('messageSendLog.retry_count')
                ->type('number')
                ->min(0)
                ->title(__('Retry Count'))
                ->placeholder(__('Number of retries'))
                ->help(__('Number of settlement retry attempts')),
        ];
    }
}