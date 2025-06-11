<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCredit;

use App\Services\DynamicModel;
use App\Services\Traits\HasPermissions;
use App\Services\Traits\SettingMenuItemTrait;
use App\Settings\Entities\Payment\Payment;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;
use Stancl\Tenancy\Enums\RouteMode;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\SiteCreditUsage;

/**
 * @property $balance_credits
 */
class SiteCredit extends DynamicModel
{
    use SettingMenuItemTrait;
    use HasPermissions;

    protected $appends = [
        'available_alimtalk_count'
    ];


    protected static function booted(): void
    {
        parent::booted();

        self::creating(function (SiteCredit $model) {
            if (!isset($model->order_id)) {
                $model->setAttribute('order_id', self::generateOrderId());
                $model->setAttribute('balance_credits', $model->getAttribute('credits_amount'));
                $model->setAttribute('used_credits', 0);
            };
        });
    }

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

    public static function generateOrderId(): string
    {
        do {
            $orderId = 'ORD-' . now()->format('YmdHis') . '-' . Str::random(4);
        } while (self::query()->where('order_id', $orderId)->exists());

        return $orderId;
    }

    public function availableAlimtalkCount(): Attribute
    {
        $cost = $this->alimtalk_credits_cost;
        $balance = $this->balance_credits;
        $count = $cost > 0 ? floor($balance / $cost) : 0;
        return Attribute::get(function () use ($count) {
            return $count;
        });
    }

    public function siteCreditUsages(): HasMany
    {
        return $this->hasMany(SiteCreditUsage::class);
    }

    public function payment(): MorphOne
    {
        return $this->morphOne(
            Payment::class,
            'payable',
            'payable_type',
            'payable_id',
            'uuid'
        );
    }
}
