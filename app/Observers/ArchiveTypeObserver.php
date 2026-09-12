<?php

namespace App\Observers;

use App\Models\Elabel\Dynamic\ArchiveType;
use App\Services\Elabel\DynamicArchiveService;

class ArchiveTypeObserver
{
    /**
     * Handle the ArchiveType "created" event.
     */
    public function created(ArchiveType $archiveType): void
    {
        app(DynamicArchiveService::class)->invalidateSidebarCache();
    }

    /**
     * Handle the ArchiveType "updated" event.
     */
    public function updated(ArchiveType $archiveType): void
    {
        app(DynamicArchiveService::class)->invalidateSidebarCache();
    }

    /**
     * Handle the ArchiveType "deleted" event.
     */
    public function deleted(ArchiveType $archiveType): void
    {
        app(DynamicArchiveService::class)->invalidateSidebarCache();
    }
}
