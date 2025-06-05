<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCampaign\Layouts;

use App\Settings\Entities\User\User;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;
use Techigh\CreditMessaging\Settings\Entities\SiteCampaign\SiteCampaign;

class SiteCampaignListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'siteCampaigns';

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

            TD::make('type', __('Type')),
            TD::make('status', __('Status')),

            TD::make('template_code', __('Template Code'))
                ->sort()
                ->filter(TD::FILTER_TEXT)
                ->render(function (SiteCampaign $siteCampaign) {
                    return "<code>{$siteCampaign->template_code}</code>";
                }),

            TD::make('replace_sms', __('Replace SMS'))
                ->sort()
                ->filter(TD::FILTER_SELECT, [
                    '1' => 'Yes',
                    '0' => 'No'
                ])
                ->render(function (SiteCampaign $siteCampaign) {
                    $class = $siteCampaign->replace_sms ? 'text-success' : 'text-muted';
                    $text = $siteCampaign->replace_sms ? 'Yes' : 'No';
                    return "<span class='{$class}'>{$text}</span>";
                })
                ->defaultHidden(),

            TD::make('total_count', __('Total'))
                ->align(TD::ALIGN_RIGHT)
                ->sort()
                ->render(function (SiteCampaign $siteCampaign) {
                    return number_format($siteCampaign->total_count);
                }),

            TD::make('success_count', __('Success'))
                ->align(TD::ALIGN_RIGHT)
                ->sort()
                ->render(function (SiteCampaign $siteCampaign) {
                    $percentage = $siteCampaign->total_count > 0
                        ? ($siteCampaign->success_count / $siteCampaign->total_count) * 100
                        : 0;
                    return "<span class='text-success'>" . number_format($siteCampaign->success_count) . " (" . number_format($percentage, 1) . "%)</span>";
                }),

            TD::make('failed_count', __('Failed'))
                ->align(TD::ALIGN_RIGHT)
                ->sort()
                ->render(function (SiteCampaign $siteCampaign) {
                    $totalFailed = $siteCampaign->failed_count + $siteCampaign->rejected_count + $siteCampaign->canceled_count;
                    $percentage = $siteCampaign->total_count > 0
                        ? ($totalFailed / $siteCampaign->total_count) * 100
                        : 0;
                    return "<span class='text-danger'>" . number_format($totalFailed) . " (" . number_format($percentage, 1) . "%)</span>";
                }),

            TD::make('sms_results', __('SMS Results'))
                ->align(TD::ALIGN_CENTER)
                ->render(function (SiteCampaign $siteCampaign) {
                    if (!$siteCampaign->replace_sms) {
                        return "<span class='text-muted'>N/A</span>";
                    }
                    $smsTotal = $siteCampaign->sms_success_count + $siteCampaign->sms_failed_count;
                    if ($smsTotal == 0) {
                        return "<span class='text-muted'>0</span>";
                    }
                    return "<span class='text-info'>" .
                        "<small>S: {$siteCampaign->sms_success_count} / F: {$siteCampaign->sms_failed_count}</small>" .
                        "</span>";
                }),

            TD::make('send_at', __('Send At'))
                ->align(TD::ALIGN_RIGHT)
                ->sort()
                ->render(function (SiteCampaign $siteCampaign) {
                    return $siteCampaign->send_at ? $siteCampaign->send_at->format('Y-m-d H:i') : '-';
                }),

            TD::make('webhook_received_at', __('Webhook'))
                ->align(TD::ALIGN_RIGHT)
                ->sort()
                ->render(function (SiteCampaign $siteCampaign) {
                    if (!$siteCampaign->webhook_received_at) {
                        return "<span class='text-warning'>Pending</span>";
                    }
                    return "<span class='text-success'>" . $siteCampaign->webhook_received_at->format('H:i') . "</span>";
                }),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(function (SiteCampaign $siteCampaign) {
                    $actions = $this->getActions($siteCampaign);
                    if (count($actions) < 1) return null;
                    return DropDown::make()->icon('bs.three-dots')->list($actions);
                }),
        ];
    }

    private function getActions(SiteCampaign $siteCampaign): array
    {
        /** @var User $user */
        $user = Auth::user();
        $actions = [];

        if ($user->hasAccess('settings.entities.site_campaigns.edit')) {
            $actions[] = Link::make(__('Edit'))
                ->route('settings.entities.site_campaigns.edit', $siteCampaign->getKey())
                ->icon('bs.pencil');
        }

        if ($user->hasAccess('settings.entities.site_campaigns.delete')) {
            $actions[] = Button::make(__('Delete'))
                ->confirm(__('Once the account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.'))
                ->method('remove', ['id' => $siteCampaign->getKey()])
                ->icon('bs.trash3');
        }

        return $actions;
    }
}
