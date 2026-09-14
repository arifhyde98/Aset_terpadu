<?php

namespace App\Observers;

use App\Models\AsetTanah;
use App\Services\Sipat\SipatService;

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
     * Handle the AsetTanah "saved" event.
     * Aturan Baku: Jika Aset Tanah disimpan atau diperbarui OPD-nya,
     * otomatis selaraskan OPD sertifikat di e-Label yang memiliki NIBAR identik.
     */
    public function saved(AsetTanah $asetTanah): void
    {
        if (!empty($asetTanah->kode_aset) && !empty($asetTanah->opd_id)) {
            $opdNama = $asetTanah->opdSipat?->nama ?? $asetTanah->opd;
            if (!$opdNama) {
                $opdNama = \App\Models\OpdSipat::find($asetTanah->opd_id)?->nama;
            }

            \App\Models\Elabel\ElabelSertifikat::withoutEvents(function () use ($asetTanah, $opdNama) {
                \App\Models\Elabel\ElabelSertifikat::where('nibar', $asetTanah->kode_aset)
                    ->update([
                        'sipat_opd_id' => $asetTanah->opd_id,
                        'dinas'        => $opdNama,
                    ]);
            });
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
