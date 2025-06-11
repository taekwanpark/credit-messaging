<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCredit\Layouts;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Rows;

class SiteCreditEditLayout extends Rows
{
    /**
     * Get the fields elements to be displayed.
     *
     * @return Field[]
     */
    public function fields(): array
    {

        $showCreditAttributes = $this->query->get('siteCredit.order_id') !== null;
        return [
            Input::make('siteCredit.id')
                ->type('hidden'),

            Input::make('siteCredit.order_id')
                ->title(__('Order ID'))
                ->placeholder(__('Enter order ID'))
                ->help(__('Unique order identifier'))
                ->readonly()
                ->canSee($showCreditAttributes),

            Select::make('siteCredit.type')
                ->title(__('Type'))
                ->options([
                    'CHARGE' => __('CHARGE'),
                    'RECHARGE' => __('RECHARGE')
                ])
                ->disabled()
                ->value('CHARGE')
                ->required()
                ->canSee($showCreditAttributes),

            Select::make('siteCredit.status')
                ->title(__('Status'))
                ->options([
                    'PENDING' => __('PENDING'),
                    'SUCCESS' => __('SUCCESS'),
                    'CANCELLED' => __('CANCELLED')
                ])
                ->value('PENDING')
                ->disabled()
                ->required()
                ->canSee($showCreditAttributes),

            Input::make('siteCredit.purchase_amount')
                ->title(__('Purchase Amount'))
                ->mask([
                    'alias' => 'currency',
                    'groupSeparator' => ',',
                    'digitsOptional' => true,
                    'allowMinus' => false,
                    'removeMaskOnSubmit' => true,
                ])
                ->required()
                ->placeholder(__('Enter purchase amount'))
                ->readonly($showCreditAttributes)
                ->help(__('Purchase Amount')),

            Input::make('siteCredit.credits_amount')
                ->title(__('Credits Amount'))
                ->mask([
                    'alias' => 'decimal',
                    'digits' => 2,
                    'prefix' => '©',
                    'groupSeparator' => ',',
                    'digitsOptional' => true,
                    'allowMinus' => false,
                    'removeMaskOnSubmit' => true,
                ])
                ->required()
                ->value(0)
                ->placeholder(__('Enter credits amount'))
                ->readonly()
                ->help(__('Credits Amount')),

            Input::make('siteCredit.used_credits')
                ->title(__('Used Credits'))
                ->mask([
                    'alias' => 'decimal',
                    'digits' => 2,
                    'prefix' => '©',
                    'groupSeparator' => ',',
                    'digitsOptional' => true,
                    'allowMinus' => false,
                    'removeMaskOnSubmit' => true,
                ])
                ->placeholder(__('Enter used credits'))
                ->help(__('Used Credits'))
                ->readonly()
                ->canSee($showCreditAttributes),

            Input::make('siteCredit.balance_credits')
                ->title(__('Balance Credits'))
                ->mask([
                    'alias' => 'decimal',
                    'digits' => 2,
                    'prefix' => '©',
                    'groupSeparator' => ',',
                    'digitsOptional' => true,
                    'allowMinus' => false,
                    'removeMaskOnSubmit' => true,
                ])
                ->placeholder(__('Enter balance credits'))
                ->help(__('Balance Credits'))
                ->readonly()
                ->canSee($showCreditAttributes),

            Input::make('siteCredit.cost_per_credit')
                ->title(__('Cost Per Credit'))
                ->mask([
                    'alias' => 'decimal',
                    'digits' => 2,
                    'groupSeparator' => ',',
                    'digitsOptional' => true,
                    'allowMinus' => false,
                    'removeMaskOnSubmit' => true,
                ])
                ->placeholder(__('Enter cost per credit'))
                ->help(__('Cost Per Credit'))
                ->readonly()
                ->canSee($showCreditAttributes),

            Group::make([
                Input::make('siteCredit.alimtalk_credits_cost')
                    ->title(__('Alimtalk Credits Cost'))
                    ->mask([
                        'alias' => 'currency',
                        'prefix' => '©',
                        'groupSeparator' => ',',
                        'digitsOptional' => true,
                        'removeMaskOnSubmit' => true,
                    ])
                    ->placeholder(__('Enter Alimtalk credits cost'))
                    ->help(__('Alimtalk Credits Cost'))
                    ->readonly()
                    ->canSee($showCreditAttributes),

                Input::make('siteCredit.sms_credits_cost')
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
                    ->placeholder(__('Enter SMS credits cost'))
                    ->help(__('SMS Credits Cost'))
                    ->readonly()
                    ->canSee($showCreditAttributes),

                Input::make('siteCredit.lms_credits_cost')
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
                    ->placeholder(__('Enter LMS credits cost'))
                    ->help(__('LMS Credits Cost'))
                    ->readonly()
                    ->canSee($showCreditAttributes),

                Input::make('siteCredit.mms_credits_cost')
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
                    ->placeholder(__('Enter MMS credits cost'))
                    ->help(__('MMS Credits Cost'))
                    ->readonly()
                    ->canSee($showCreditAttributes),
            ]),

            DateTimer::make('siteCredit.created_at')
                ->title(__('Created At'))
                ->format('Y-m-d H:i:s')
                ->required()
                ->disabled()
                ->value($this->query->get('siteCredit.created_at') ?? now())
                ->canSee($this->query->get('siteCredit.order_id') !== null),

            DateTimer::make('siteCredit.updated_at')
                ->title(__('Last Edited At'))
                ->format('Y-m-d H:i:s')
                ->required()
                ->disabled()
                ->value($this->query->get('siteCredit.updated_at') ?? now())
                ->canSee($this->query->get('siteCredit.order_id') !== null),
        ];
    }
}
