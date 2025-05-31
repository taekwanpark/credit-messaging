<?php

namespace Techigh\CreditMessaging\Settings\Entities\SiteCreditPayment;

use Techigh\CreditMessaging\Services\DynamicModel;
use App\Services\Traits\HasPermissions;
use App\Services\Traits\SettingMenuItemTrait;
use App\Traits\HasOrchidAttributes;
use Laravel\Scout\Searchable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;

class SiteCreditPayment extends DynamicModel
{
    use SettingMenuItemTrait;
    use HasPermissions;
    use Searchable;

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_data' => 'array',
        'completed_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    protected $allowedFilters = [
        'id' => Where::class,
        'site_id' => Like::class,
        'status' => Where::class,
        'payment_method' => Where::class,
        'transaction_id' => Like::class,
    ];

    protected $allowedSorts = [
        'id',
        'site_id',
        'amount',
        'status',
        'payment_method',
        'transaction_id',
        'completed_at',
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'payment_method_display',
    ];

    public static function getMenuSection(): string
    {
        return __('Credits');
    }

    public static function getMenuPriority(): int
    {
        return 1340;
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => (int)$this->id,
            'uuid' => (string)$this->uuid,
            'site_id' => (string)$this->site_id,
            'transaction_id' => (string)$this->transaction_id,
        ];
    }

    /**
     * Get the site credit this payment belongs to
     */
    public function siteCredit()
    {
        return $this->belongsTo(\Techigh\CreditMessaging\Settings\Entities\SiteCredit\SiteCredit::class, 'site_id', 'site_id');
    }

    /**
     * Check if payment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark payment as completed
     */
    public function markAsCompleted(): bool
    {
        $this->status = 'completed';
        $this->completed_at = now();

        return $this->save();
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed(): bool
    {
        $this->status = 'failed';

        return $this->save();
    }

    /**
     * Get payment method display name
     */
    public function getPaymentMethodDisplayAttribute(): string
    {
        return match ($this->payment_method) {
            'card' => 'Credit Card',
            'bank' => 'Bank Transfer',
            'virtual' => 'Virtual Account',
            'admin' => 'Admin Manual',
            default => ucfirst($this->payment_method)
        };
    }
}