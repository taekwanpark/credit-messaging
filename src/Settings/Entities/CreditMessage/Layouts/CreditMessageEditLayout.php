<?php

namespace Techigh\CreditMessaging\Settings\Entities\CreditMessage\Layouts;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Layouts\Rows;

class CreditMessageEditLayout extends Rows
{
    /**
     * The screen's layout elements.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Input::make('creditMessage.site_id')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('Site ID'))
                ->placeholder(__('Site ID'))
                ->help(__('Site identifier for this message')),

            Input::make('creditMessage.title')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('Title'))
                ->placeholder(__('Message title'))
                ->help(__('Title for this credit message')),

            Select::make('creditMessage.message_type')
                ->options([
                    'alimtalk' => 'Alimtalk',
                    'sms' => 'SMS',
                    'lms' => 'LMS',
                    'mms' => 'MMS',
                ])
                ->required()
                ->title(__('Message Type'))
                ->help(__('Type of message to send')),

            Select::make('creditMessage.routing_strategy')
                ->options([
                    'alimtalk_first' => 'Alimtalk First',
                    'sms_only' => 'SMS Only',
                    'cost_optimized' => 'Cost Optimized',
                ])
                ->required()
                ->title(__('Routing Strategy'))
                ->help(__('How to route this message')),

            TextArea::make('creditMessage.message_content')
                ->rows(5)
                ->required()
                ->title(__('Message Content'))
                ->placeholder(__('Enter message content'))
                ->help(__('The content of the message')),

            TextArea::make('creditMessage.recipients')
                ->rows(3)
                ->title(__('Recipients'))
                ->placeholder(__('Enter recipients (JSON format)'))
                ->help(__('Recipients in JSON format')),

            Select::make('creditMessage.status')
                ->options([
                    'draft' => 'Draft',
                    'scheduled' => 'Scheduled',
                    'sending' => 'Sending',
                    'completed' => 'Completed',
                    'failed' => 'Failed',
                    'cancelled' => 'Cancelled',
                ])
                ->title(__('Status'))
                ->help(__('Current status of the message')),

            DateTimer::make('creditMessage.scheduled_at')
                ->title(__('Scheduled At'))
                ->help(__('When to send the message')),

            DateTimer::make('creditMessage.sent_at')
                ->title(__('Sent At'))
                ->help(__('When the message was sent')),

            Input::make('creditMessage.estimated_cost')
                ->type('number')
                ->step(0.01)
                ->min(0)
                ->title(__('Estimated Cost'))
                ->placeholder(__('Estimated cost'))
                ->help(__('Estimated cost for sending')),

            Input::make('creditMessage.actual_cost')
                ->type('number')
                ->step(0.01)
                ->min(0)
                ->title(__('Actual Cost'))
                ->placeholder(__('Actual cost'))
                ->help(__('Actual cost after sending')),

            Input::make('creditMessage.total_recipients')
                ->type('number')
                ->min(0)
                ->title(__('Total Recipients'))
                ->placeholder(__('Total recipients count'))
                ->help(__('Total number of recipients')),

            Input::make('creditMessage.success_count')
                ->type('number')
                ->min(0)
                ->title(__('Success Count'))
                ->placeholder(__('Successful sends'))
                ->help(__('Number of successful sends')),

            Input::make('creditMessage.failed_count')
                ->type('number')
                ->min(0)
                ->title(__('Failed Count'))
                ->placeholder(__('Failed sends'))
                ->help(__('Number of failed sends')),
        ];
    }
}