<?php

namespace App\Observers;

use App\Models\ProsesAset;
use App\Services\SipatService;

class ProsesAsetObserver
{
    /**
     * Handle the ProsesAset "created" event.
     */
    public function created(ProsesAset $prosesAset): void
    {
        app(SipatService::class)->invalidateDashboardCache();
    }

    /**
     * Handle the ProsesAset "updated" event.
     */
    public function updated(ProsesAset $prosesAset): void
    {
        app(SipatService::class)->invalidateDashboardCache();
    }

    /**
     * Handle the ProsesAset "deleted" event.
     */
    public function deleted(ProsesAset $prosesAset): void
    {
        app(SipatService::class)->invalidateDashboardCache();
    }
}
