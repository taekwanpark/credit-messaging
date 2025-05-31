<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCredit\Layouts;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Layouts\Rows;

class SiteCreditEditLayout extends Rows
{
    /**
     * The screen's layout elements.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Input::make('siteCredit.site_id')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('Site ID'))
                ->placeholder(__('Site ID'))
                ->help(__('Unique identifier for the site')),

            Input::make('siteCredit.balance')
                ->type('number')
                ->step(0.01)
                ->min(0)
                ->required()
                ->title(__('Balance'))
                ->placeholder(__('Current credit balance'))
                ->help(__('Current available credit balance')),

            Input::make('siteCredit.alimtalk_cost')
                ->type('number')
                ->step(0.01)
                ->min(0)
                ->required()
                ->title(__('Alimtalk Cost'))
                ->placeholder(__('Cost per Alimtalk message'))
                ->help(__('Cost charged per Alimtalk message')),

            Input::make('siteCredit.sms_cost')
                ->type('number')
                ->step(0.01)
                ->min(0)
                ->required()
                ->title(__('SMS Cost'))
                ->placeholder(__('Cost per SMS message'))
                ->help(__('Cost charged per SMS message')),

            Input::make('siteCredit.lms_cost')
                ->type('number')
                ->step(0.01)
                ->min(0)
                ->required()
                ->title(__('LMS Cost'))
                ->placeholder(__('Cost per LMS message'))
                ->help(__('Cost charged per LMS message')),

            Input::make('siteCredit.mms_cost')
                ->type('number')
                ->step(0.01)
                ->min(0)
                ->required()
                ->title(__('MMS Cost'))
                ->placeholder(__('Cost per MMS message'))
                ->help(__('Cost charged per MMS message')),

            CheckBox::make('siteCredit.auto_charge_enabled')
                ->title(__('Auto Charge Enabled'))
                ->placeholder(__('Enable automatic charging'))
                ->help(__('Automatically charge when balance is low')),

            Input::make('siteCredit.auto_charge_threshold')
                ->type('number')
                ->step(0.01)
                ->min(0)
                ->title(__('Auto Charge Threshold'))
                ->placeholder(__('Threshold amount for auto charge'))
                ->help(__('Balance threshold to trigger auto charge')),

            Input::make('siteCredit.auto_charge_amount')
                ->type('number')
                ->step(0.01)
                ->min(0)
                ->title(__('Auto Charge Amount'))
                ->placeholder(__('Amount to charge automatically'))
                ->help(__('Amount to add when auto charging')),

            Input::make('siteCredit.sort_order')
                ->type('number')
                ->min(0)
                ->title(__('Sort Order'))
                ->placeholder(__('Display order'))
                ->help(__('Order for display purposes')),
        ];
    }
}