<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCampaignMessage;

use App\Services\DynamicModel;
use App\Services\HasE164PhoneNumber;
use App\Services\Traits\HasPermissions;
use App\Services\Traits\SettingMenuItemTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Enums\RouteMode;
use Techigh\CreditMessaging\Settings\Entities\SiteCampaign\SiteCampaign;

class SiteCampaignMessage extends DynamicModel
{
    use SettingMenuItemTrait;
    use HasPermissions;
    use HasE164PhoneNumber;

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

    public static function getMenuSection(): string
    {
        return __('Credit Message');
    }

    public function siteCampaign(): BelongsTo
    {
        return $this->belongsTo(SiteCampaign::class);
    }
}
