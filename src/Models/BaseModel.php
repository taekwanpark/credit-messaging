<?php

namespace Techigh\CreditMessaging\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Base Model for Credit Messaging Package
 * 
 * This class provides compatibility for different Laravel applications.
 * If the app uses DynamicModel (like smpp-provider), models will extend that.
 * Otherwise, they'll use this base model with similar functionality.
 */
class BaseModel extends Model
{
    use HasUuids, SoftDeletes, HasFactory;

    protected $guarded = [];
    
    public array $translatable = ['title'];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected $appends = [
        'created_at_formatted',
        'updated_at_formatted',
    ];

    public function getCreatedAtFormattedAttribute(): ?string
    {
        return !empty($this->created_at) ? Carbon::parse($this->created_at)->diffForHumans() : null;
    }

    public function getUpdatedAtFormattedAttribute(): ?string
    {
        return !empty($this->updated_at) ? Carbon::parse($this->updated_at)->diffForHumans() : null;
    }

    /**
     * Check if we're in an app that has DynamicModel (like smpp-provider)
     */
    public static function shouldUseDynamicModel(): bool
    {
        return class_exists('App\Services\DynamicModel');
    }

    /**
     * Get the appropriate parent class
     */
    public static function getParentClass(): string
    {
        return static::shouldUseDynamicModel() ? 'App\Services\DynamicModel' : static::class;
    }
}