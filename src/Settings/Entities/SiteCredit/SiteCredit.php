<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCredit;

use App\Services\DynamicModel;
use App\Services\Traits\HasPermissions;
use App\Services\Traits\SettingMenuItemTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\SiteCreditUsage;

/**
 * @property $balance_credits
 */
class SiteCredit extends DynamicModel
{
    use SettingMenuItemTrait;
    use HasPermissions;

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

    public function siteCreditUsages(): HasMany
    {
        return $this->hasMany(SiteCreditUsage::class);
    }

    public static function generateOrderId(): string
    {
        do {
            $orderId = 'ORD-' . now()->format('YmdHis') . '-' . Str::random(4);
        } while (self::query()->where('order_id', $orderId)->exists());

        return $orderId;
    }
}
