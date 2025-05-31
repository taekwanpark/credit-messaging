<?php

namespace Techigh\CreditMessaging\Settings\Entities\CreditMessage;

use App\Services\DynamicModel;
use App\Services\Traits\HasPermissions;
use App\Services\Traits\SettingMenuItemTrait;
use Laravel\Scout\Searchable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;

class CreditMessage extends DynamicModel
{
    use SettingMenuItemTrait;
    use HasPermissions;
    use Searchable;

    protected $casts = [
        'recipients' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'total_recipients' => 'integer',
        'success_count' => 'integer',
        'failed_count' => 'integer',
    ];

    protected $allowedFilters = [
        'id' => Where::class,
        'site_id' => Like::class,
        'message_type' => Where::class,
        'status' => Where::class,
        'routing_strategy' => Where::class,
    ];

    protected $allowedSorts = [
        'id',
        'site_id',
        'message_type',
        'status',
        'scheduled_at',
        'sent_at',
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'success_rate',
        'message_type_display',
        'routing_strategy_display',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (!empty($model->recipients) && empty($model->total_recipients)) {
                $model->total_recipients = count($model->recipients);
            }
        });
    }

    public static function getMenuSection(): string
    {
        return __('Credit Messaging');
    }

    public static function getMenuPriority(): int
    {
        return 8010;
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => (int)$this->id,
            'uuid' => (string)$this->uuid,
            'site_id' => (string)$this->site_id,
            'message_content' => (string)$this->message_content,
        ];
    }

    /**
     * Get the site credit this message belongs to
     */
    public function siteCredit()
    {
        return $this->belongsTo(\Techigh\CreditMessaging\Settings\Entities\SiteCredit\SiteCredit::class, 'site_id', 'site_id');
    }

    /**
     * Check if message is scheduled
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    /**
     * Check if message is draft
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if message is sending
     */
    public function isSending(): bool
    {
        return $this->status === 'sending';
    }

    /**
     * Check if message is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if message failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if message is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Mark as scheduled
     */
    public function markAsScheduled(): bool
    {
        $this->status = 'scheduled';
        return $this->save();
    }

    /**
     * Mark as sending
     */
    public function markAsSending(): bool
    {
        $this->status = 'sending';
        $this->sent_at = now();
        return $this->save();
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(int $successCount = null, int $failedCount = null, float $actualCost = null): bool
    {
        $this->status = 'completed';

        if ($successCount !== null) {
            $this->success_count = $successCount;
        }

        if ($failedCount !== null) {
            $this->failed_count = $failedCount;
        }

        if ($actualCost !== null) {
            $this->actual_cost = $actualCost;
        }

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
     * Get title for specific locale
     */
    public function getTitleForLocale(string $locale = 'ko'): string
    {
        if (is_array($this->title) && isset($this->title[$locale])) {
            return $this->title[$locale];
        }

        if (is_array($this->title) && isset($this->title['ko'])) {
            return $this->title['ko'];
        }

        if (is_array($this->title) && count($this->title) > 0) {
            return array_values($this->title)[0];
        }

        return $this->title ?? '';
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_recipients === 0) {
            return 0.0;
        }

        return round(($this->success_count / $this->total_recipients) * 100, 2);
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
     * Get routing strategy display name
     */
    public function getRoutingStrategyDisplayAttribute(): string
    {
        return match ($this->routing_strategy) {
            'alimtalk_first' => 'Alimtalk First',
            'sms_only' => 'SMS Only',
            'cost_optimized' => 'Cost Optimized',
            default => ucfirst(str_replace('_', ' ', $this->routing_strategy))
        };
    }
}