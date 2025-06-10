<?php

namespace Techigh\CreditMessaging\Settings\Entities\SitePlan\Layouts;

use App\Settings\Entities\Tenant\Tenant;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Rows;

class SitePlanEditLayout extends Rows
{
    /**
     * Get the fields elements to be displayed.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Input::make('sitePlan.id')
                ->type('hidden'),

            Input::make('sitePlan.title')
                ->title(__('Title'))
                ->placeholder(__('Enter SitePlan title'))
                ->required(),

            Input::make('sitePlan.cost_per_credit')
                ->title(__('Cost Per Credit'))
                ->mask([
                    'alias' => 'decimal',
                    'digits' => 2,
                    'groupSeparator' => ',',
                    'digitsOptional' => true,
                    'allowMinus' => false,
                    'removeMaskOnSubmit' => true,
                ])
                ->value(siteConfigs('site_cost_per_credit', config('credit-messaging.default_credit_costs.cost_per_credit')))
                ->placeholder(__('Enter cost per credit'))
                ->help(__('Cost Per Credit')),

            Group::make([
                Input::make('sitePlan.alimtalk_credits_cost')
                    ->title(__('Alimtalk Credits Cost'))
                    ->mask([
                        'alias' => 'currency',
                        'prefix' => '©',
                        'groupSeparator' => ',',
                        'digitsOptional' => true,
                        'removeMaskOnSubmit' => true,
                    ])
                    ->value(siteConfigs('site_alimtalk_credits_cost', config('credit-messaging.default_credit_costs.alimtalk')))
                    ->placeholder(__('Enter Alimtalk credits cost'))
                    ->help(__('Alimtalk Credits Cost')),

                Input::make('sitePlan.sms_credits_cost')
                    ->title(__('SMS Credits Cost'))
                    ->mask([
                        'alias' => 'decimal',
                        'digits' => 2,
                        'prefix' => '©',
                        'groupSeparator' => ',',
                        'digitsOptional' => true,
                        'allowMinus' => false,
                        'removeMaskOnSubmit' => true,
                    ])
                    ->value(siteConfigs('site_sms_credits_cost', config('credit-messaging.default_credit_costs.sms')))
                    ->placeholder(__('Enter SMS credits cost'))
                    ->help(__('SMS Credits Cost')),

                Input::make('sitePlan.lms_credits_cost')
                    ->title(__('LMS Credits Cost'))
                    ->mask([
                        'alias' => 'decimal',
                        'digits' => 2,
                        'prefix' => '©',
                        'groupSeparator' => ',',
                        'digitsOptional' => true,
                        'allowMinus' => false,
                        'removeMaskOnSubmit' => true,
                    ])
                    ->value(siteConfigs('site_lms_credits_cost', config('credit-messaging.default_credit_costs.lms')))
                    ->placeholder(__('Enter LMS credits cost'))
                    ->help(__('LMS Credits Cost')),

                Input::make('sitePlan.mms_credits_cost')
                    ->title(__('MMS Credits Cost'))
                    ->mask([
                        'alias' => 'decimal',
                        'digits' => 2,
                        'prefix' => '©',
                        'groupSeparator' => ',',
                        'digitsOptional' => true,
                        'allowMinus' => false,
                        'removeMaskOnSubmit' => true,
                    ])
                    ->value(siteConfigs('site_mms_credits_cost', config('credit-messaging.default_credit_costs.mms')))
                    ->placeholder(__('Enter MMS credits cost'))
                    ->help(__('MMS Credits Cost')),
            ]),

            Relation::make('tenantIds')
                ->title(__('Tenants'))
                ->displayAppend('sitePlanLabel')
                ->fromModel(Tenant::class, 'host')
                ->applyScope('exceptThisSitePlan', $this->query->get('sitePlan.id')) // 커스텀 스코프 사용
                ->async()
                ->multiple(),

            DateTimer::make('sitePlan.created_at')
                ->title(__('Created At'))
                ->format('Y-m-d H:i:s')
                ->required()
                ->value($this->query->get('sitePlan.created_at') ?? now())
                ->canSee(false),

            DateTimer::make('sitePlan.updated_at')
                ->title(__('Last Edited At'))
                ->format('Y-m-d H:i:s')
                ->required()
                ->value($this->query->get('sitePlan.updated_at') ?? now())
                ->canSee(false),
        ];
    }
}
