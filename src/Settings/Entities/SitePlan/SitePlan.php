<?php

namespace Techigh\CreditMessaging\Settings\Entities\SitePlan;

use App\Services\DynamicModel;
use App\Services\Traits\HasPermissions;
use App\Services\Traits\SettingMenuItemTrait;
use App\Settings\Entities\Tenant\Tenant;
use Stancl\Tenancy\Enums\RouteMode;

class SitePlan extends DynamicModel
{
    use SettingMenuItemTrait;
    use HasPermissions;

    public static function menuItemRouteMode(): RouteMode
    {
        return RouteMode::CENTRAL;
    }

    public static function getMenuSection(): string
    {
        return __('Credit Message');
    }

    public function tenants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Tenant::class);
    }
    
}
