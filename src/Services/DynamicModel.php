<?php

namespace Techigh\CreditMessaging\Services;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Orchid\Attachment\Attachable;
use Orchid\Screen\AsSource;
use Spatie\Activitylog\LogOptions;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Translatable\HasTranslations;
use Orchid\Filters\Filterable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\EloquentSortable\Sortable;

class DynamicModel extends Model implements Sortable
{
    use HasUuids, AsSource, SoftDeletes, HasFactory, Attachable, HasTranslations, Filterable;
    use LogsActivity, SortableTrait;

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

    public function getActivitylogOptions(): LogOptions
    {
        $logOptions = new LogOptions();
        $logOptions->dontSubmitEmptyLogs();
        $logOptions->logAttributes = ['*'];
        $logOptions->logExceptAttributes = ['created_at', 'updated_at'];
        $logOptions->logOnlyDirty = true;
        return $logOptions;
    }

    /**
     * The sortable configuration.
     *
     * @var array<string, mixed>
     */
    public array $sortable = [
        'order_column_name' => 'sort_order',
        'sort_when_creating' => true,
        'sort_on_has_many' => true,
    ];
}