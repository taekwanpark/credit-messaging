<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCredit\Layouts;

use App\Settings\Entities\User\User;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;
use Techigh\CreditMessaging\Settings\Entities\SiteCredit\SiteCredit;

class SiteCreditListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'siteCredits';

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

            TD::make('order_id', __('Order ID'))
                ->sort()
                ->filter(TD::FILTER_TEXT),


            TD::make('type', __('Type'))
                ->sort()
                ->filter(TD::FILTER_SELECT, [
                    'CHARGE' => __('CHARGE'),
                    'REFUND' => __('REFUND')
                ])
                ->render(function (SiteCredit $siteCredit) {
                    $class = $siteCredit->type === 'CHARGE' ? 'text-success' : 'text-warning';
                    return "<span class='{$class}'>{$siteCredit->type}</span>";
                }),

            TD::make('status', __('Status'))
                ->sort()
                ->filter(TD::FILTER_SELECT, [
                    'PENDING' => __('PENDING'),
                    'SUCCESS' => __('SUCCESS'),
                    'CANCELLED' => __('CANCELLED')
                ])
                ->render(function (SiteCredit $siteCredit) {
                    $class = match ($siteCredit->status) {
                        'SUCCESS' => 'text-success',
                        'PENDING' => 'text-warning',
                        'CANCELLED' => 'text-danger',
                        default => 'text-muted'
                    };
                    return "<span class='{$class}'>{$siteCredit->status}</span>";
                }),

            TD::make('purchase_amount', __('Purchase Amount'))
                ->align(TD::ALIGN_RIGHT)
                ->sort()
                ->render(function (SiteCredit $siteCredit) {
                    return number_format($siteCredit->purchase_amount, 2) . '원';
                }),

            TD::make('credits_amount', __('Credits Amount'))
                ->align(TD::ALIGN_RIGHT)
                ->sort()
                ->render(function (SiteCredit $siteCredit) {
                    return number_format($siteCredit->credits_amount, 2);
                }),

            TD::make('balance_credits', __('Balance Credits'))
                ->align(TD::ALIGN_RIGHT)
                ->sort()
                ->render(function (SiteCredit $siteCredit) {
                    $percentage = $siteCredit->credits_amount > 0
                        ? ($siteCredit->balance_credits / $siteCredit->credits_amount) * 100
                        : 0;
                    $class = $percentage > 50 ? 'text-success' : ($percentage > 20 ? 'text-warning' : 'text-danger');
                    return "<span class='{$class}'>" . number_format($siteCredit->balance_credits, 2) . "</span>";
                }),

            TD::make('cost_per_credit', __('Cost Per Credit'))
                ->align(TD::ALIGN_RIGHT)
                ->sort()
                ->render(function (SiteCredit $siteCredit) {
                    return number_format($siteCredit->cost_per_credit, 2) . '원';
                }),

            TD::make('created_at_formatted', __('Created'))
                ->align(TD::ALIGN_RIGHT)
                ->sort(),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(function (SiteCredit $siteCredit) {
                    $actions = $this->getActions($siteCredit);
                    if (count($actions) < 1) return null;
                    return DropDown::make()->icon('bs.three-dots')->list($actions);
                }),
        ];
    }

    private function getActions(SiteCredit $siteCredit): array
    {
        /** @var User $user */
        $user = Auth::user();
        $actions = [];

        if ($user->hasAccess('settings.entities.site_credits.edit')) {
            $actions[] = Link::make(__('Edit'))
                ->route('settings.entities.site_credits.edit', $siteCredit->getKey())
                ->icon('bs.pencil');
        }

        if ($user->hasAccess('settings.entities.site_credits.delete')) {
            $actions[] = Button::make(__('Delete'))
                ->confirm(__('Once the account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.'))
                ->method('remove', ['id' => $siteCredit->getKey()])
                ->icon('bs.trash3');
        }

        return $actions;
    }
}
