<?php

namespace App\Observers;

use App\Models\AsetTanah;
use App\Services\SipatService;

class AsetTanahObserver
{
    /**
     * Handle the AsetTanah "saving" event.
     * Aturan Baku: Luas tanah bersertifikat wajib mengikuti luas resmi di sertifikat (e-Label).
     */
    public function saving(AsetTanah $asetTanah): void
    {
        if (!empty($asetTanah->kode_aset)) {
            $sertifikat = \Illuminate\Support\Facades\DB::table('elabel_sertifikat_tanah')
                ->where('nibar', $asetTanah->kode_aset)
                ->where('luas', '>', 0)
                ->first();

            if ($sertifikat && (float) $sertifikat->luas > 0) {
                $asetTanah->luas = $sertifikat->luas;
            }
        }
    }

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
