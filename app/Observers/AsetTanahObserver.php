<?php

namespace App\Observers;

use App\Models\AsetTanah;
use App\Services\SipatService;

class AsetTanahObserver
{
    /**
     * Handle the AsetTanah "created" event.
     */
    public function created(AsetTanah $asetTanah): void
    {
        app(SipatService::class)->invalidateDashboardCache();
    }

    /**
     * Handle the AsetTanah "updated" event.
     */
    public function updated(AsetTanah $asetTanah): void
    {
        app(SipatService::class)->invalidateDashboardCache();
    }

    /**
     * Handle the AsetTanah "deleted" event.
     */
    public function deleted(AsetTanah $asetTanah): void
    {
        app(SipatService::class)->invalidateDashboardCache();
    }
}
