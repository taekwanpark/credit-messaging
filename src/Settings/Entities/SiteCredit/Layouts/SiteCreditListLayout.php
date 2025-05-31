<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCredit\Layouts;

use Orchid\Screen\TD;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Layouts\Table;
use Techigh\CreditMessaging\Settings\Entities\SiteCredit\SiteCredit;

class SiteCreditListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'siteCredits';

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
                ->render(function (SiteCredit $siteCredit) {
                    return Link::make($siteCredit->site_id)
                        ->route('settings.entities.site_credits.edit', $siteCredit);
                }),

            TD::make('balance', __('Balance'))
                ->sort()
                ->render(function (SiteCredit $siteCredit) {
                    $color = $siteCredit->balance > 1000 ? 'text-success' :
                        ($siteCredit->balance > 100 ? 'text-warning' : 'text-danger');
                    return "<span class='{$color}'>" . number_format($siteCredit->balance, 2) . "</span>";
                }),

            TD::make('costs', __('Message Costs'))
                ->render(function (SiteCredit $siteCredit) {
                    return "Alimtalk: {$siteCredit->alimtalk_cost}<br>" .
                        "SMS: {$siteCredit->sms_cost}<br>" .
                        "LMS: {$siteCredit->lms_cost}<br>" .
                        "MMS: {$siteCredit->mms_cost}";
                }),

            TD::make('auto_charge_enabled', __('Auto Charge'))
                ->sort()
                ->render(function (SiteCredit $siteCredit) {
                    return $siteCredit->auto_charge_enabled ?
                        '<span class="badge bg-success">Enabled</span>' :
                        '<span class="badge bg-secondary">Disabled</span>';
                }),

            TD::make('auto_charge_info', __('Auto Charge Settings'))
                ->render(function (SiteCredit $siteCredit) {
                    if (!$siteCredit->auto_charge_enabled) {
                        return '-';
                    }
                    return "Threshold: {$siteCredit->auto_charge_threshold}<br>" .
                        "Amount: {$siteCredit->auto_charge_amount}";
                }),

            TD::make('created_at', __('Created'))
                ->sort()
                ->render(function (SiteCredit $siteCredit) {
                    return $siteCredit->created_at->toDateTimeString();
                }),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(function (SiteCredit $siteCredit) {
                    return DropDown::make()
                        ->icon('options-vertical')
                        ->list([
                            Link::make(__('Edit'))
                                ->route('settings.entities.site_credits.edit', $siteCredit)
                                ->icon('pencil'),

                            Button::make(__('Delete'))
                                ->icon('trash')
                                ->confirm(__('Are you sure you want to delete this site credit?'))
                                ->method('remove')
                                ->parameters([
                                    'id' => $siteCredit->id,
                                ]),
                        ]);
                }),
        ];
    }
}