<?php

namespace Techigh\CreditMessaging\Settings\Entities\SitePlan\Layouts;

use App\Settings\Entities\User\User;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;
use Techigh\CreditMessaging\Settings\Entities\SitePlan\SitePlan;

class SitePlanListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'sitePlans';

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

            TD::make('title', __('Title'))
                ->sort()
                ->filter(TD::FILTER_TEXT)
                ->render(function (SitePlan $sitePlan) {
                    return $sitePlan->getTranslation('title', app()->getLocale());
                }),

            TD::make('cost_per_credit', __('Cost Per Credit'))
                ->align(TD::ALIGN_RIGHT)
                ->sort()
                ->render(function (SitePlan $sitePlan) {
                    return number_format($sitePlan->cost_per_credit, 2) . '원';
                }),

            TD::make('alimtalk_credits_cost', __('Alimtalk Credits Cost'))
                ->align(TD::ALIGN_RIGHT)
                ->sort()
                ->render(function (SitePlan $sitePlan) {
                    return '©' . number_format($sitePlan->alimtalk_credits_cost, 2);
                }),

            TD::make('sms_credits_cost', __('SMS Credits Cost'))
                ->align(TD::ALIGN_RIGHT)
                ->sort()
                ->render(function (SitePlan $sitePlan) {
                    return '©' . number_format($sitePlan->sms_credits_cost, 2);
                }),

            TD::make('lms_credits_cost', __('LMS Credits Cost'))
                ->align(TD::ALIGN_RIGHT)
                ->sort()
                ->render(function (SitePlan $sitePlan) {
                    return '©' . number_format($sitePlan->lms_credits_cost, 2);
                }),

            TD::make('mms_credits_cost', __('MMS Credits Cost'))
                ->align(TD::ALIGN_RIGHT)
                ->sort()
                ->render(function (SitePlan $sitePlan) {
                    return '©' . number_format($sitePlan->mms_credits_cost, 2);
                }),

            TD::make('created_at_formatted', __('Created'))
                ->align(TD::ALIGN_RIGHT)
                ->sort(),

            TD::make('updated_at_formatted', __('Last edit'))
                ->align(TD::ALIGN_RIGHT)
                ->sort(),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(function (SitePlan $sitePlan) {
                    $actions = $this->getActions($sitePlan);
                    if (count($actions) < 1) return null;
                    return DropDown::make()->icon('bs.three-dots')->list($actions);
                }),
        ];
    }

    private function getActions(SitePlan $sitePlan): array
    {
        /** @var User $user */
        $user = Auth::user();
        $actions = [];

        if ($user->hasAccess('settings.entities.site_plans.edit')) {
            $actions[] = Link::make(__('Edit'))
                ->route('settings.entities.site_plans.edit', $sitePlan->getKey())
                ->icon('bs.pencil');
        }

        if ($user->hasAccess('settings.entities.site_plans.delete')) {
            $actions[] = Button::make(__('Delete'))
                ->confirm(__('Once the account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.'))
                ->method('remove', ['id' => $sitePlan->getKey()])
                ->icon('bs.trash3');
        }

        return $actions;
    }
}
