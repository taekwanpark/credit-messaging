<?php

namespace Techigh\CreditMessaging\Models;

// For smpp-provider compatibility - uses DynamicModel when available
if (class_exists('App\Services\DynamicModel')) {
    class_alias('App\Services\DynamicModel', 'Techigh\CreditMessaging\Models\BaseParentModel');
} else {
    class_alias('Techigh\CreditMessaging\Models\BaseModel', 'Techigh\CreditMessaging\Models\BaseParentModel');
}

use Laravel\Scout\Searchable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;

// Conditionally use traits based on availability
if (trait_exists('App\Services\Traits\HasPermissions')) {
    use App\Services\Traits\HasPermissions;
}
if (trait_exists('App\Services\Traits\SettingMenuItemTrait')) {
    use App\Services\Traits\SettingMenuItemTrait;
}
if (trait_exists('App\Traits\HasOrchidAttributes')) {
    use App\Traits\HasOrchidAttributes;
}

class SiteCredit extends BaseParentModel
{
    use Searchable;

    protected $casts = [
        'balance' => 'decimal:2',
        'alimtalk_cost' => 'decimal:2',
        'sms_cost' => 'decimal:2',
        'lms_cost' => 'decimal:2',
        'mms_cost' => 'decimal:2',
        'auto_charge_enabled' => 'boolean',
        'auto_charge_threshold' => 'decimal:2',
        'auto_charge_amount' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    protected $allowedFilters = [
        'id' => Where::class,
        'site_id' => Like::class,
        'balance' => Where::class,
        'auto_charge_enabled' => Where::class,
    ];

    protected $allowedSorts = [
        'id',
        'site_id',
        'balance',
        'created_at',
        'updated_at',
    ];

    public static function getMenuSection(): string
    {
        return __('Credits');
    }

    public static function getMenuPriority(): int
    {
        return 1320;
    }


    public function toSearchableArray(): array
    {
        return [
            'id' => (int)$this->id,
            'uuid' => (string)$this->uuid,
            'site_id' => (string)$this->site_id,
        ];
    }

    /**
     * Get payments for this site credit
     */
    public function payments()
    {
        return $this->hasMany(SiteCreditPayment::class, 'site_id', 'site_id');
    }

    /**
     * Get usages for this site credit
     */
    public function usages()
    {
        return $this->hasMany(SiteCreditUsage::class, 'site_id', 'site_id');
    }

    /**
     * Get credit messages for this site
     */
    public function creditMessages()
    {
        return $this->hasMany(CreditMessage::class, 'site_id', 'site_id');
    }

    /**
     * Check if auto charge should be triggered
     */
    public function shouldAutoCharge(): bool
    {
        if (!$this->auto_charge_enabled || !$this->auto_charge_threshold) {
            return false;
        }

        return $this->balance <= $this->auto_charge_threshold;
    }

    /**
     * Get cost for specific message type
     */
    public function getCostForMessageType(string $messageType): float
    {
        return match ($messageType) {
            'alimtalk' => $this->alimtalk_cost,
            'sms' => $this->sms_cost,
            'lms' => $this->lms_cost,
            'mms' => $this->mms_cost,
            default => 0.00
        };
    }

    /**
     * Check if there's enough balance for the given amount
     */
    public function hasEnoughBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }
}
