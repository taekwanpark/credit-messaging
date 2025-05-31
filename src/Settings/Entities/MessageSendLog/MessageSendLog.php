<?php

namespace Techigh\CreditMessaging\Settings\Entities\MessageSendLog;

use Techigh\CreditMessaging\Services\DynamicModel;
use App\Services\Traits\HasPermissions;
use App\Services\Traits\SettingMenuItemTrait;
use App\Traits\HasOrchidAttributes;
use Laravel\Scout\Searchable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;

class MessageSendLog extends DynamicModel
{
    use SettingMenuItemTrait;
    use HasPermissions;
    use Searchable;

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

    protected $allowedFilters = [
        'id' => Where::class,
        'usage_id' => Where::class,
        'message_type' => Where::class,
        'settlement_status' => Where::class,
    ];

    protected $allowedSorts = [
        'id',
        'usage_id',
        'message_type',
        'total_count',
        'success_count',
        'failed_count',
        'settlement_status',
        'final_cost',
        'settled_at',
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'success_rate',
        'message_type_display',
        'settlement_status_display',
    ];

    public static function getMenuSection(): string
    {
        return __('Credit Messaging');
    }

    public static function getMenuPriority(): int
    {
        return 5050;
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => (int)$this->id,
            'uuid' => (string)$this->uuid,
            'usage_id' => (int)$this->usage_id,
            'message_type' => (string)$this->message_type,
        ];
    }

    /**
     * Get the usage record this log belongs to
     */
    public function usage()
    {
        return $this->belongsTo(\Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\SiteCreditUsage::class, 'usage_id');
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