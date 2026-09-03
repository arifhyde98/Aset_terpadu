<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\SipatService;

class SyncLuasSertifikatCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sipat:sync-luas-sertifikat {--dry-run : Cek audit selisih tanpa melakukan update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menyelaraskan luas aset tanah di SIPAT agar sesuai 100% dengan luas di sertifikat (e-Label)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memeriksa keselarasan luas tanah bersertifikat (SIPAT vs e-Label)...');

        $differing = DB::table('aset_tanah as a')
            ->join('elabel_sertifikat_tanah as s', 'a.kode_aset', '=', 's.nibar')
            ->whereRaw('s.luas > 0 AND ABS(COALESCE(a.luas,0) - s.luas) >= 0.01')
            ->select([
                'a.id_aset',
                'a.kode_aset',
                'a.nama_aset',
                's.no_sertipikat',
                'a.luas as luas_kiba',
                's.luas as luas_sertifikat',
            ])
            ->get();

        if ($differing->isEmpty()) {
            $this->info('✅ SEMUA DATA SUDAH SESUAI! Seluruh aset tanah bersertifikat memiliki luas yang sama dengan sertifikat.');
            return 0;
        }

        $this->warn('Ditemukan ' . $differing->count() . ' aset dengan selisih luas:');
        $rows = [];
        foreach ($differing as $item) {
            $rows[] = [
                $item->id_aset,
                $item->kode_aset,
                $item->nama_aset,
                $item->no_sertipikat,
                number_format($item->luas_kiba, 2),
                number_format($item->luas_sertifikat, 2),
                number_format($item->luas_sertifikat - $item->luas_kiba, 2),
            ];
        }

        $this->table(['ID', 'NIBAR', 'Nama Aset', 'No. Sertifikat', 'Luas KIB A', 'Luas Sertifikat', 'Selisih'], $rows);

        if ($this->option('dry-run')) {
            $this->info('Mode dry-run aktif. Tidak ada perubahan yang disimpan.');
            return 0;
        }

        $updated = DB::update('
            UPDATE aset_tanah a
            INNER JOIN elabel_sertifikat_tanah s ON a.kode_aset = s.nibar
            SET a.luas = s.luas
            WHERE s.luas > 0 AND ABS(COALESCE(a.luas,0) - s.luas) >= 0.01
        ');

        app(SipatService::class)->invalidateDashboardCache();

        $this->info("✅ Berhasil menyinkronkan {$updated} aset tanah mengikuti luas sertifikat.");
        return 0;
    }
}
