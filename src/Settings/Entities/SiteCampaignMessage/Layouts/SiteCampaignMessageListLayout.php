<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCampaignMessage\Layouts;

use App\Settings\Entities\User\User;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;
use Techigh\CreditMessaging\Settings\Entities\SiteCampaignMessage\SiteCampaignMessage;

class SiteCampaignMessageListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'siteCampaignMessages';

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

            TD::make('site_campaign_id', __('Campaign'))
                ->sort()
                ->filter(TD::FILTER_TEXT)
                ->render(function (SiteCampaignMessage $siteCampaignMessage) {
                    return $siteCampaignMessage->siteCampaign->getTranslation('title', app()->getLocale()) ?? 'Campaign #' . $siteCampaignMessage->site_campaign_id;
                }),

            TD::make('phone_e164', __('Phone Number'))
                ->sort()
                ->filter(TD::FILTER_TEXT)
                ->render(function (SiteCampaignMessage $siteCampaignMessage) {
                    // 전화번호 마스킹 처리 (보안상)
                    $phone = $siteCampaignMessage->phone_e164;
                    if (strlen($phone) > 4) {
                        $masked = substr($phone, 0, 3) . str_repeat('*', strlen($phone) - 6) . substr($phone, -3);
                        return "<code>{$masked}</code>";
                    }
                    return "<code>{$phone}</code>";
                }),

            TD::make('name', __('Name'))
                ->sort()
                ->filter(TD::FILTER_TEXT)
                ->render(function (SiteCampaignMessage $siteCampaignMessage) {
                    if (!$siteCampaignMessage->name) {
                        return "<span class='text-muted'>-</span>";
                    }
                    // 이름 마스킹 처리 (보안상)
                    $name = $siteCampaignMessage->name;
                    if (mb_strlen($name) > 1) {
                        $masked = mb_substr($name, 0, 1) . str_repeat('*', mb_strlen($name) - 1);
                        return $masked;
                    }
                    return $name;
                }),

            TD::make('kakao_result_code', __('Kakao Result'))
                ->sort()
                ->filter(TD::FILTER_TEXT)
                ->render(function (SiteCampaignMessage $siteCampaignMessage) {
                    if (!$siteCampaignMessage->kakao_result_code) {
                        return "<span class='text-muted'>Pending</span>";
                    }
                    
                    $class = match($siteCampaignMessage->kakao_result_code) {
                        '0000' => 'text-success',
                        '1000', '1001', '1002' => 'text-warning',
                        default => 'text-danger'
                    };
                    
                    return "<span class='{$class}'><code>{$siteCampaignMessage->kakao_result_code}</code></span>";
                }),

            TD::make('sms_result_code', __('SMS Result'))
                ->sort()
                ->filter(TD::FILTER_TEXT)
                ->render(function (SiteCampaignMessage $siteCampaignMessage) {
                    if (!$siteCampaignMessage->sms_result_code) {
                        return "<span class='text-muted'>N/A</span>";
                    }
                    
                    $class = match($siteCampaignMessage->sms_result_code) {
                        '0000' => 'text-success',
                        '1000', '1001' => 'text-warning',
                        default => 'text-danger'
                    };
                    
                    return "<span class='{$class}'><code>{$siteCampaignMessage->sms_result_code}</code></span>";
                }),

            TD::make('message_status', __('Status'))
                ->render(function (SiteCampaignMessage $siteCampaignMessage) {
                    // 상태 판단 로직
                    if ($siteCampaignMessage->kakao_result_code === '0000') {
                        return "<span class='badge bg-success'>Kakao Success</span>";
                    } elseif ($siteCampaignMessage->sms_result_code === '0000') {
                        return "<span class='badge bg-info'>SMS Success</span>";
                    } elseif ($siteCampaignMessage->kakao_result_code && $siteCampaignMessage->kakao_result_code !== '0000') {
                        if ($siteCampaignMessage->sms_result_code) {
                            return "<span class='badge bg-danger'>Both Failed</span>";
                        } else {
                            return "<span class='badge bg-warning'>Kakao Failed</span>";
                        }
                    } else {
                        return "<span class='badge bg-secondary'>Pending</span>";
                    }
                }),

            TD::make('created_at_formatted', __('Created'))
                ->align(TD::ALIGN_RIGHT)
                ->sort(),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(function (SiteCampaignMessage $siteCampaignMessage) {
                    $actions = $this->getActions($siteCampaignMessage);
                    if (count($actions) < 1) return null;
                    return DropDown::make()->icon('bs.three-dots')->list($actions);
                }),
        ];
    }

    private function getActions(SiteCampaignMessage $siteCampaignMessage): array
    {
        /** @var User $user */
        $user = Auth::user();
        $actions = [];

        if ($user->hasAccess('settings.entities.site_campaign_messages.edit')) {
            $actions[] = Link::make(__('Edit'))
                ->route('settings.entities.site_campaign_messages.edit', $siteCampaignMessage->getKey())
                ->icon('bs.pencil');
        }

        if ($user->hasAccess('settings.entities.site_campaign_messages.delete')) {
            $actions[] = Button::make(__('Delete'))
                ->confirm(__('Once the account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.'))
                ->method('remove', ['id' => $siteCampaignMessage->getKey()])
                ->icon('bs.trash3');
        }

        return $actions;
    }
}
