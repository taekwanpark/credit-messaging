<?php

namespace Techigh\CreditMessaging\Services;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Techigh\CreditMessaging\Settings\Entities\SitePlan\SitePlan;

trait HasSitePlan
{

    public function sitePlan(): BelongsTo
    {
        return $this->belongsTo(SitePlan::class, 'site_plan_id', 'id');
    }

    public function scopeExceptThisSitePlan($query, $id)
    {
        return $query->where('site_plan_id', '!=', $id)->orWhereNull('site_plan_id');
    }

    public function sitePlanLabel(): Attribute
    {
        return Attribute::get(function (): ?string {
            return sprintf('%s (%s)', $this->host, is_null($this->sitePlan) ? "-" : "{$this->sitePlan->title} - {$this->sitePlan->cost_per_credit}");
        });
    }
}