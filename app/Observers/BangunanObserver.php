<?php

namespace App\Observers;

use App\Models\Activity;
use App\Models\Bangunan;
use Illuminate\Support\Facades\Cache;

class BangunanObserver
{
    /**
     * Handle the Bangunan "created" event.
     */
    public function created(Bangunan $bangunan): void
    {
        $this->invalidateCache($bangunan);

        Activity::log(
            description: "Menambahkan data bangunan baru: {$bangunan->nama_bangunan} ({$bangunan->kode_bangunan})",
            type: 'create',
            moduleId: 4,
            moduleKey: 'bangunan',
            oldData: null,
            newData: $bangunan->toArray()
        );
    }

    /**
     * Handle the Bangunan "updated" event.
     */
    public function updated(Bangunan $bangunan): void
    {
        $this->invalidateCache($bangunan);

        $changes = $bangunan->getChanges();
        unset($changes['updated_at']);

        if (!empty($changes)) {
            $old = [];
            foreach (array_keys($changes) as $key) {
                $old[$key] = $bangunan->getOriginal($key);
            }

            Activity::log(
                description: "Memperbarui data bangunan: {$bangunan->nama_bangunan} ({$bangunan->kode_bangunan})",
                type: 'update',
                moduleId: 4,
                moduleKey: 'bangunan',
                oldData: $old,
                newData: $changes
            );
        }
    }

    /**
     * Handle the Bangunan "deleted" event.
     */
    public function deleted(Bangunan $bangunan): void
    {
        $this->invalidateCache($bangunan);

        Activity::log(
            description: "Menghapus data bangunan: {$bangunan->nama_bangunan} ({$bangunan->kode_bangunan})",
            type: 'delete',
            moduleId: 4,
            moduleKey: 'bangunan',
            oldData: $bangunan->toArray(),
            newData: null
        );
    }

    /**
     * Invalidasi cache statistik bangunan secara tertarget.
     */
    protected function invalidateCache(Bangunan $bangunan): void
    {
        Cache::forget('bangunan.stats.global');
        if ($bangunan->opd_id) {
            Cache::forget("bangunan.stats.opd.{$bangunan->opd_id}");
        }
    }
}
