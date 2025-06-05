<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCampaign\Layouts;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Layouts\Rows;

class SiteCampaignEditLayout extends Rows
{
    /**
     * Get the fields elements to be displayed.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Input::make('siteCampaign.id')
                ->type('hidden'),

            Select::make('siteCampaign.type')
                ->title(__('Type'))
                ->options([
                    'alimtalk' => __('Alimtalk'),
                    'sms' => __('SMS'),
                    'lms' => __('LMS'),
                    'mms' => __('MMS'),
                ])
                ->value('alimtalk')
                ->disabled(),

            Select::make('siteCampaign.status')
                ->title(__('Status'))
                ->options([
                    'PENDING' => __('PENDING'),
                    'PROGRESS' => __('PROGRESS'),
                    'SUCCESS' => __('SUCCESS'),
                    'CANCELLED' => __('CANCELLED'),
                    'FAILED' => __('FAILED'),
                ])
                ->value(__('CHARGE'))
                ->disabled(),

            DateTimer::make('siteCampaign.send_at')
                ->title(__('Send At'))
                ->format('Y-m-d H:i:s')
                ->help(__('캠페인 발송 시간'))
                ->required(),

            DateTimer::make('siteCampaign.webhook_received_at')
                ->title(__('Webhook Received At'))
                ->format('Y-m-d H:i:s')
                ->help(__('Webhook received at'))
                ->disabled(),

            Input::make('siteCampaign.template_code')
                ->title(__('Template Code'))
                ->placeholder(__('Enter template code'))
                ->help(__('Alimtalk Template Code'))
                ->required(),

            CheckBox::make('siteCampaign.replace_sms')
                ->title(__('Replace SMS'))
                ->help(__('Replace SMS'))
                ->value(true)
                ->sendTrueOrFalse(),

            Input::make('siteCampaign.sms_title')
                ->title(__('SMS Title'))
                ->placeholder(__('Enter SMS title'))
                ->help(__('대체 SMS 제목')),

            TextArea::make('siteCampaign.sms_content')
                ->title(__('SMS Content'))
                ->placeholder(__('Enter SMS content'))
                ->help(__('대체 SMS 내용'))
                ->maxlength(2000),

            Input::make('siteCampaign.total_count')
                ->title(__('Total Count'))
                ->type('number')
                ->value('0')
                ->help(__('총 발송 건수'))
                ->readonly(),

            Input::make('siteCampaign.pending_count')
                ->title(__('Pending Count'))
                ->type('number')
                ->value('0')
                ->help(__('대기 건수'))
                ->readonly(),

            Input::make('siteCampaign.success_count')
                ->title(__('Success Count'))
                ->type('number')
                ->value('0')
                ->help(__('성공 건수'))
                ->readonly(),

            Input::make('siteCampaign.failed_count')
                ->title(__('Failed Count'))
                ->type('number')
                ->value('0')
                ->help(__('실패 건수'))
                ->readonly(),

            Input::make('siteCampaign.rejected_count')
                ->title(__('Rejected Count'))
                ->type('number')
                ->value('0')
                ->help(__('거부 건수'))
                ->readonly(),

            Input::make('siteCampaign.canceled_count')
                ->title(__('Canceled Count'))
                ->type('number')
                ->value('0')
                ->help(__('취소 건수'))
                ->readonly(),

            Input::make('siteCampaign.sms_success_count')
                ->title(__('SMS Success Count'))
                ->type('number')
                ->value('0')
                ->help(__('대체 SMS 성공 건수'))
                ->readonly(),

            Input::make('siteCampaign.sms_failed_count')
                ->title(__('SMS Failed Count'))
                ->type('number')
                ->value('0')
                ->help(__('대체 SMS 실패 건수'))
                ->readonly(),

            DateTimer::make('siteCredit.created_at')
                ->title(__('Created At'))
                ->format('Y-m-d H:i:s')
                ->required()
                ->value($this->query->get('siteCredit.created_at') ?? now()),

            DateTimer::make('siteCredit.updated_at')
                ->title(__('Last Edited At'))
                ->format('Y-m-d H:i:s')
                ->required()
                ->value($this->query->get('siteCredit.updated_at') ?? now()),
        ];
    }
}
