<?php

namespace Techigh\CreditMessaging\Models;

use App\Services\DynamicModel;
use App\Services\Traits\HasPermissions;
use App\Services\Traits\SettingMenuItemTrait;
use App\Traits\HasOrchidAttributes;
use Laravel\Scout\Searchable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;

class SiteCreditUsage extends DynamicModel
{
    use SettingMenuItemTrait, HasPermissions, HasOrchidAttributes, Searchable;

    protected $casts = [
        'quantity' => 'integer',
        'cost_per_unit' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'metadata' => 'array',
        'refunded_at' => 'datetime',
    ];

    protected $allowedFilters = [
        'id' => Where::class,
        'site_id' => Like::class,
        'message_type' => Where::class,
        'status' => Where::class,
        'batch_id' => Like::class,
    ];

    protected $allowedSorts = [
        'id',
        'site_id',
        'message_type',
        'quantity',
        'total_cost',
        'status',
        'refunded_at',
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'net_cost',
        'message_type_display',
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
            'batch_id' => (string)$this->batch_id,
        ];
    }

    /**
     * Get the site credit this usage belongs to
     */
    public function siteCredit()
    {
        return $this->belongsTo(SiteCredit::class, 'site_id', 'site_id');
    }

    /**
     * Get send logs for this usage
     */
    public function sendLogs()
    {
        return $this->hasMany(MessageSendLog::class, 'usage_id');
    }

    /**
     * Check if usage is refunded
     */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /**
     * Check if usage is used
     */
    public function isUsed(): bool
    {
        return $this->status === 'used';
    }

    /**
     * Check if usage is reserved
     */
    public function isReserved(): bool
    {
        return $this->status === 'reserved';
    }

    /**
     * Check if usage failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Process refund for this usage
     */
    public function processRefund(float $amount, string $reason): bool
    {
        $this->refund_amount = $amount;
        $this->refund_reason = $reason;
        $this->refunded_at = now();
        $this->status = 'refunded';

        return $this->save();
    }

    /**
     * Mark as used
     */
    public function markAsUsed(): bool
    {
        $this->status = 'used';

        return $this->save();
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(): bool
    {
        $this->status = 'failed';

        return $this->save();
    }

    /**
     * Get net cost (total cost minus refund)
     */
    public function getNetCostAttribute(): float
    {
        return $this->total_cost - $this->refund_amount;
    }

    /**
     * Get message type display name
     */
    public function getMessageTypeDisplayAttribute(): string
    {
        return match ($this->message_type) {
            'alimtalk' => 'Alimtalk',
            'sms' => 'SMS',
            'lms' => 'LMS',
            'mms' => 'MMS',
            default => strtoupper($this->message_type)
        };
    }

    /**
     * Get refundable amount for this usage
     */
    public function getRefundableAmount(): float
    {
        return $this->total_cost - $this->refund_amount;
    }

    /**
     * Process refund for this usage
     */
    public function refund(float $amount, ?string $reason = null): bool
    {
        if ($amount > $this->getRefundableAmount()) {
            throw new \Exception("환불 가능 금액을 초과했습니다.");
        }

        $this->refund_amount += $amount;
        $this->refund_reason = $reason;
        $this->refunded_at = now();
        $this->status = 'refunded';

        return $this->save();
    }
}
