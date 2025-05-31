<?php

namespace Techigh\CreditMessaging\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MessageSendLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'usage_id',
        'message_ids',
        'message_type',
        'total_count',
        'success_count',
        'failed_count',
        'webhook_result',
        'webhook_received_at',
        'settlement_status',
        'final_cost',
        'settled_at',
        'error_message',
        'retry_count',
        'sort_order',
    ];

    protected $casts = [
        'message_ids' => 'array',
        'total_count' => 'integer',
        'success_count' => 'integer',
        'failed_count' => 'integer',
        'webhook_result' => 'array',
        'webhook_received_at' => 'datetime',
        'final_cost' => 'decimal:2',
        'settled_at' => 'datetime',
        'retry_count' => 'integer',
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
     * Get the usage record this log belongs to
     */
    public function usage()
    {
        return $this->belongsTo(SiteCreditUsage::class, 'usage_id');
    }

    /**
     * Check if settlement is pending
     */
    public function isSettlementPending(): bool
    {
        return $this->settlement_status === 'pending';
    }

    /**
     * Check if settlement is processing
     */
    public function isSettlementProcessing(): bool
    {
        return $this->settlement_status === 'processing';
    }

    /**
     * Check if settlement is completed
     */
    public function isSettlementCompleted(): bool
    {
        return $this->settlement_status === 'completed';
    }

    /**
     * Check if settlement failed
     */
    public function isSettlementFailed(): bool
    {
        return $this->settlement_status === 'failed';
    }

    /**
     * Mark settlement as processing
     */
    public function markSettlementAsProcessing(): bool
    {
        $this->settlement_status = 'processing';

        return $this->save();
    }

    /**
     * Mark settlement as completed
     */
    public function markSettlementAsCompleted(float $finalCost = null): bool
    {
        $this->settlement_status = 'completed';
        $this->settled_at = now();

        if ($finalCost !== null) {
            $this->final_cost = $finalCost;
        }

        return $this->save();
    }

    /**
     * Mark settlement as failed
     */
    public function markSettlementAsFailed(string $errorMessage = null): bool
    {
        $this->settlement_status = 'failed';

        if ($errorMessage) {
            $this->error_message = $errorMessage;
        }

        return $this->save();
    }

    /**
     * Increment retry count
     */
    public function incrementRetryCount(): bool
    {
        $this->retry_count++;

        return $this->save();
    }

    /**
     * Process webhook result
     */
    public function processWebhookResult(array $webhookData): bool
    {
        $this->webhook_result = $webhookData;
        $this->webhook_received_at = now();

        // Extract success and failed counts from webhook data
        if (isset($webhookData['delivery_results'])) {
            $delivered = 0;
            $failed = 0;

            foreach ($webhookData['delivery_results'] as $result) {
                if (isset($result['status'])) {
                    if ($result['status'] === 'delivered') {
                        $delivered++;
                    } else {
                        $failed++;
                    }
                }
            }

            $this->success_count = $delivered;
            $this->failed_count = $failed;
        }

        return $this->save();
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_count === 0) {
            return 0.0;
        }

        return round(($this->success_count / $this->total_count) * 100, 2);
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
     * Get settlement status display name
     */
    public function getSettlementStatusDisplayAttribute(): string
    {
        return match ($this->settlement_status) {
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
            default => ucfirst($this->settlement_status)
        };
    }

    /**
     * Check if should retry settlement
     */
    public function shouldRetrySettlement(int $maxRetries = 5): bool
    {
        return $this->isSettlementFailed() && $this->retry_count < $maxRetries;
    }
}
