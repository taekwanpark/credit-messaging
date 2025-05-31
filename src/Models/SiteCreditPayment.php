<?php

namespace Techigh\CreditMessaging\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SiteCreditPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'site_id',
        'amount',
        'payment_method',
        'status',
        'transaction_id',
        'payment_gateway',
        'payment_data',
        'notes',
        'completed_at',
        'sort_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_data' => 'array',
        'completed_at' => 'datetime',
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
     * Get the site credit this payment belongs to
     */
    public function siteCredit()
    {
        return $this->belongsTo(SiteCredit::class, 'site_id', 'site_id');
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
