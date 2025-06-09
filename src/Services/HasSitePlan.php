<?php

use Techigh\CreditMessaging\Settings\Entities\SitePlan\SitePlan;

trait HasSitePlan
{
    public function sitePlan()
    {
        return $this->belongsTo(SitePlan::class);
    }
}