<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCampaign;

use App\Services\DynamicModel;
use App\Services\Traits\HasPermissions;
use App\Services\Traits\SettingMenuItemTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Enums\RouteMode;
use Techigh\CreditMessaging\Settings\Entities\SiteCampaignMessage\SiteCampaignMessage;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\SiteCreditUsage;

/**
 * @property $siteCampaignMessages
 */
class SiteCampaign extends DynamicModel
{
    use SettingMenuItemTrait;
    use HasPermissions;

    protected $casts = [
        'replace_sms' => 'boolean',
        'send_at' => 'datetime',
        'webhook_received_at' => 'datetime',
    ];

    public static function getMenuSection(): string
    {
        return __('Credit Message');
    }

    public static function menuItemRouteMode(): RouteMode
    {
        if (config('credit-messaging.route_mode', 'none') === 'tenant') {
            return RouteMode::TENANT;
        } else if (config('credit-messaging.route_mode', 'none') === 'central') {
            return RouteMode::CENTRAL;
        } else {
            return RouteMode::UNIVERSAL;
        }
    }


    public function siteCampaignMessages(): HasMany
    {
        return $this->hasMany(SiteCampaignMessage::class);
    }

    public function siteCreditUsages(): HasMany
    {
        return $this->hasMany(SiteCreditUsage::class);
    }
}
