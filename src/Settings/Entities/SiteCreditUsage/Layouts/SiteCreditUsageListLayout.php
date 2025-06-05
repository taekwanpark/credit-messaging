<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\Layouts;

use App\Settings\Entities\User\User;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\SiteCreditUsage;

class SiteCreditUsageListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'siteCreditUsages';

    /**
     * Get the table columns.
     *
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('id', 'ID')
                ->sort()
                ->filter(TD::FILTER_TEXT),

            TD::make('site_credit_id', __('Site Credit'))
                ->sort()
                ->filter(TD::FILTER_TEXT)
                ->render(function (SiteCreditUsage $siteCreditUsage) {
                    return 'Credit #' . $siteCreditUsage->siteCredit->id;
                }),

            TD::make('site_campaign_id', __('Campaign'))
                ->sort()
                ->filter(TD::FILTER_TEXT)
                ->render(function (SiteCreditUsage $siteCreditUsage) {
                    return 'Campaign #' . $siteCreditUsage->siteCampaign->id;
                }),

            TD::make('type', __('Type'))
                ->sort()
                ->filter(TD::FILTER_SELECT, [
                    '1' => __('DEDUCT'),
                    '-1' => __('REFUND')
                ])
                ->render(function (SiteCreditUsage $siteCreditUsage) {
                    $class = $siteCreditUsage->type === 1 ? 'text-danger' : 'text-success';
                    $text = $siteCreditUsage->type === 1 ? __('DEDUCT') : __('REFUND');
                    return "<span class='{$class}'>{$text}</span>";
                }),

            TD::make('credit_type', __('Credit Type'))
                ->sort()
                ->filter(TD::FILTER_SELECT, [
                    'sms' => 'SMS',
                    'lms' => 'LMS',
                    'mms' => 'MMS',
                    'alimtalk' => 'Alimtalk'
                ])
                ->render(function (SiteCreditUsage $siteCreditUsage) {
                    $class = match ($siteCreditUsage->credit_type) {
                        'alimtalk' => 'text-primary',
                        'sms' => 'text-success',
                        'lms' => 'text-warning',
                        'mms' => 'text-info',
                        default => 'text-muted'
                    };
                    return "<span class='{$class}'>" . strtoupper($siteCreditUsage->credit_type) . "</span>";
                }),

            TD::make('used_count', __('Used Count'))
                ->align(TD::ALIGN_RIGHT)
                ->sort()
                ->render(function (SiteCreditUsage $siteCreditUsage) {
                    return number_format($siteCreditUsage->used_count);
                }),

            TD::make('used_credits', __('Used Credits'))
                ->align(TD::ALIGN_RIGHT)
                ->sort()
                ->render(function (SiteCreditUsage $siteCreditUsage) {
                    $class = $siteCreditUsage->type === 1 ? 'text-danger' : 'text-success';
                    return "<span class='{$class}'>" . number_format($siteCreditUsage->used_credits, 2) . "</span>";
                }),

            TD::make('used_cost', __('Used Cost'))
                ->align(TD::ALIGN_RIGHT)
                ->sort()
                ->render(function (SiteCreditUsage $siteCreditUsage) {
                    $class = $siteCreditUsage->type === 1 ? 'text-danger' : 'text-success';
                    return "<span class='{$class}'>" . number_format($siteCreditUsage->used_cost, 2) . "원</span>";
                }),

            TD::make('created_at_formatted', __('Created'))
                ->align(TD::ALIGN_RIGHT)
                ->sort(),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(function (SiteCreditUsage $siteCreditUsage) {
                    $actions = $this->getActions($siteCreditUsage);
                    if (count($actions) < 1) return null;
                    return DropDown::make()->icon('bs.three-dots')->list($actions);
                }),
        ];
    }

    private function getActions(SiteCreditUsage $siteCreditUsage): array
    {
        /** @var User $user */
        $user = Auth::user();
        $actions = [];

        if ($user->hasAccess('settings.entities.site_credit_usages.edit')) {
            $actions[] = Link::make(__('Edit'))
                ->route('settings.entities.site_credit_usages.edit', $siteCreditUsage->getKey())
                ->icon('bs.pencil');
        }

        if ($user->hasAccess('settings.entities.site_credit_usages.delete')) {
            $actions[] = Button::make(__('Delete'))
                ->confirm(__('Once the account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.'))
                ->method('remove', ['id' => $siteCreditUsage->getKey()])
                ->icon('bs.trash3');
        }

        return $actions;
    }
}
