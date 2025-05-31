<?php

namespace Techigh\CreditMessaging\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SiteCreditUsage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'site_id',
        'message_type',
        'quantity',
        'cost_per_unit',
        'total_cost',
        'refund_amount',
        'refund_reason',
        'refunded_at',
        'batch_id',
        'metadata',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'cost_per_unit' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'metadata' => 'array',
        'refunded_at' => 'datetime',
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
        });
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
}
