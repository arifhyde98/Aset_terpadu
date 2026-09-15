<?php

namespace App\Observers;

use App\Models\Elabel\ElabelSertifikat;
use App\Models\AsetTanah;
use App\Services\Sipat\SipatService;

class ElabelSertifikatObserver
{
    /**
     * Handle the ElabelSertifikat "saving" event.
     * Aturan Baku: Khusus sertifikat yang NIBAR-nya ada di ASET TANAH,
     * OPD sertifikat (sipat_opd_id & dinas) wajib mengacu dan mengikuti OPD di Aset Tanah SIPAT.
     */
    public function saving(ElabelSertifikat $sertifikat): void
    {
        if (!empty($sertifikat->nibar)) {
            $asetTanah = AsetTanah::with('opdRelation')
                ->where('kode_aset', $sertifikat->nibar)
                ->first();

            if ($asetTanah && !empty($asetTanah->opd_id)) {
                $sertifikat->sipat_opd_id = $asetTanah->opd_id;
                $sertifikat->dinas = $asetTanah->opdRelation?->nama ?? $asetTanah->opd ?? $sertifikat->dinas;
            }
        }
    }

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
