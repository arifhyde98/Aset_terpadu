<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\Sipat\SipatService;

class SyncOpdSertifikatCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sipat:sync-opd-sertifikat {--dry-run : Cek audit selisih OPD tanpa melakukan update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menyelaraskan OPD sertifikat tanah di e-Label agar 100% mengacu pada OPD di Master Aset Tanah (SIPAT/KIB A)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memeriksa keselarasan OPD sertifikat tanah vs Aset Tanah KIB A...');

        $differing = DB::table('elabel_sertifikat_tanah as s')
            ->join('aset_tanah as a', 's.nibar', '=', 'a.kode_aset')
            ->leftJoin('opd as o_tanah', 'a.opd_id', '=', 'o_tanah.id')
            ->leftJoin('opd as o_sertifikat', 's.sipat_opd_id', '=', 'o_sertifikat.id')
            ->whereNotNull('s.nibar')
            ->where('s.nibar', '!=', '')
            ->whereNotNull('a.opd_id')
            ->whereRaw('COALESCE(s.sipat_opd_id, 0) != a.opd_id')
            ->select([
                's.id as sertifikat_id',
                's.nibar',
                's.no_sertipikat',
                's.sipat_opd_id as old_opd_id',
                DB::raw('COALESCE(o_sertifikat.nama, s.dinas, "-") as old_opd_nama'),
                'a.opd_id as new_opd_id',
                DB::raw('COALESCE(o_tanah.nama, a.opd, "-") as new_opd_nama'),
            ])
            ->get();

        if ($differing->isEmpty()) {
            $this->info('✅ SEMUA DATA SUDAH SESUAI! Seluruh sertifikat dengan NIBAR di KIB A telah memiliki OPD yang sama.');
            return 0;
        }

        $this->warn('Ditemukan ' . $differing->count() . ' sertifikat dengan selisih OPD dari Aset Tanah:');
        $rows = [];
        foreach ($differing as $item) {
            $rows[] = [
                $item->sertifikat_id,
                $item->nibar,
                $item->no_sertipikat,
                "[$item->old_opd_id] $item->old_opd_nama",
                "[$item->new_opd_id] $item->new_opd_nama",
            ];
        }

        $this->table(['ID Sertifikat', 'NIBAR', 'No. Sertifikat', 'OPD Sertifikat (Saat Ini)', 'OPD Aset Tanah (Tujuan)'], $rows);

        if ($this->option('dry-run')) {
            $this->info('Mode dry-run aktif. Tidak ada perubahan yang disimpan.');
            return 0;
        }

        $updated = DB::update('
            UPDATE elabel_sertifikat_tanah s
            INNER JOIN aset_tanah a ON s.nibar = a.kode_aset
            LEFT JOIN opd o ON a.opd_id = o.id
            SET s.sipat_opd_id = a.opd_id,
                s.dinas = COALESCE(o.nama, a.opd)
            WHERE s.nibar IS NOT NULL 
              AND s.nibar != ""
              AND a.opd_id IS NOT NULL
              AND COALESCE(s.sipat_opd_id, 0) != a.opd_id
        ');

        app(SipatService::class)->invalidateDashboardCache();

        $this->info("✅ Berhasil menyinkronkan {$updated} sertifikat mengikuti OPD Aset Tanah KIB A.");
        return 0;
    }
}
