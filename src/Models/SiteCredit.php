<?php

namespace Techigh\CreditMessaging\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SiteCredit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'site_id',
        'balance',
        'alimtalk_cost',
        'sms_cost',
        'lms_cost',
        'mms_cost',
        'auto_charge_enabled',
        'auto_charge_threshold',
        'auto_charge_amount',
        'sort_order',
    ];

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
}
