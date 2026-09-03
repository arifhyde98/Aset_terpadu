<?php

namespace App\Observers;

use App\Models\Elabel\ElabelSertifikat;
use App\Models\AsetTanah;
use App\Services\SipatService;

class ElabelSertifikatObserver
{
    /**
     * Handle the ElabelSertifikat "saved" event.
     * Aturan Baku: Setiap kali data sertifikat disimpan atau diperbarui,
     * jika memiliki NIBAR dan luas > 0, otomatis selaraskan luas di aset_tanah SIPAT.
     */
    public function saved(ElabelSertifikat $sertifikat): void
    {
        if (!empty($sertifikat->nibar) && (float) $sertifikat->luas > 0) {
            AsetTanah::withoutEvents(function () use ($sertifikat) {
                AsetTanah::where('kode_aset', $sertifikat->nibar)
                    ->update(['luas' => $sertifikat->luas]);
            });
            app(SipatService::class)->invalidateDashboardCache();
        }
    }

    /**
     * Handle the ElabelSertifikat "deleted" event.
     */
    public function deleted(ElabelSertifikat $sertifikat): void
    {
        app(SipatService::class)->invalidateDashboardCache();
    }
}
