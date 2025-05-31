<?php

namespace Techigh\CreditMessaging\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'site_id',
        'title',
        'message_type',
        'routing_strategy',
        'message_content',
        'recipients',
        'status',
        'scheduled_at',
        'sent_at',
        'estimated_cost',
        'actual_cost',
        'total_recipients',
        'success_count',
        'failed_count',
        'sort_order',
    ];

    protected $casts = [
        'title' => 'array',
        'recipients' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'total_recipients' => 'integer',
        'success_count' => 'integer',
        'failed_count' => 'integer',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid();
            }
            if (empty($model->sort_order)) {
                $model->sort_order = time();
            }
            if (!empty($model->recipients) && empty($model->total_recipients)) {
                $model->total_recipients = count($model->recipients);
            }
        });
    }

    /**
     * Get the site credit this message belongs to
     */
    public function siteCredit()
    {
        return $this->belongsTo(SiteCredit::class, 'site_id', 'site_id');
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
