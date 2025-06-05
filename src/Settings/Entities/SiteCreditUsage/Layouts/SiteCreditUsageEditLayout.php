<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\Layouts;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Layouts\Rows;

class SiteCreditUsageEditLayout extends Rows
{
    /**
     * Get the fields elements to be displayed.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Input::make('siteCreditUsage.id')
                ->type('hidden'),


            Relation::make('siteCreditUsage.site_credit_id')
                ->title(__('Site Credit'))
                ->fromModel(\Techigh\CreditMessaging\Settings\Entities\SiteCredit\SiteCredit::class, 'title')
                ->required()
                ->help(__('Select the related site credit')),

            Relation::make('siteCreditUsage.site_campaign_id')
                ->title(__('Site Campaign'))
                ->fromModel(\Techigh\CreditMessaging\Settings\Entities\SiteCampaign\SiteCampaign::class, 'title')
                ->required()
                ->help(__('Select the related site campaign')),

            Select::make('siteCreditUsage.type')
                ->title(__('Type'))
                ->options([
                    '1' => __('DEDUCT (차감)'),
                    '-1' => __('REFUND (환급)')
                ])
                ->value('1')
                ->required()
                ->help(__('1: 차감(DEDUCT), -1: 환급(REFUND)')),

            Select::make('siteCreditUsage.credit_type')
                ->title(__('Credit Type'))
                ->options([
                    'sms' => __('SMS'),
                    'lms' => __('LMS'),
                    'mms' => __('MMS'),
                    'alimtalk' => __('Alimtalk')
                ])
                ->value('sms')
                ->required()
                ->help(__('메시지 타입 선택')),

            Input::make('siteCreditUsage.used_count')
                ->title(__('Used Count'))
                ->type('number')
                ->min('0')
                ->value('0')
                ->placeholder(__('Enter used count'))
                ->help(__('사용된 건수'))
                ->required(),

            Input::make('siteCreditUsage.used_credits')
                ->title(__('Used Credits'))
                ->type('number')
                ->step('0.01')
                ->min('0')
                ->value('0')
                ->placeholder(__('Enter used credits'))
                ->help(__('사용된 크레딧'))
                ->required(),

            Input::make('siteCreditUsage.used_cost')
                ->title(__('Used Cost'))
                ->type('number')
                ->step('0.01')
                ->min('0')
                ->value('0')
                ->placeholder(__('Enter used cost'))
                ->help(__('사용 금액'))
                ->required(),

            Input::make('siteCreditUsage.sort_order')
                ->title(__('Sort Order'))
                ->type('number')
                ->min('0')
                ->value('0')
                ->placeholder(__('Enter sort order')),
        ];
    }
}
