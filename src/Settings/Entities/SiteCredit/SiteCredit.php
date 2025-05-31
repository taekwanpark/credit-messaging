<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCredit;

use Techigh\CreditMessaging\Services\DynamicModel;
use App\Services\Traits\HasPermissions;
use App\Services\Traits\SettingMenuItemTrait;
use App\Traits\HasOrchidAttributes;
use Laravel\Scout\Searchable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;

class SiteCredit extends DynamicModel
{
    use SettingMenuItemTrait;
    use HasPermissions;
    use HasOrchidAttributes;
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
        return __('Credit Messaging');
    }

    public static function getMenuPriority(): int
    {
        return 8020;
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
        return $this->hasMany(\Techigh\CreditMessaging\Settings\Entities\SiteCreditPayment\SiteCreditPayment::class, 'site_id', 'site_id');
    }

    /**
     * Get usages for this site credit
     */
    public function usages()
    {
        return $this->hasMany(\Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\SiteCreditUsage::class, 'site_id', 'site_id');
    }

    /**
     * Get credit messages for this site
     */
    public function creditMessages()
    {
        return $this->hasMany(\Techigh\CreditMessaging\Settings\Entities\CreditMessage\CreditMessage::class, 'site_id', 'site_id');
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