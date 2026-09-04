<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\AsetTanah;
use App\Models\Kecamatan;
use App\Services\SipatService;

class PopulateAsetTanahKecamatanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sipat:populate-kecamatan {--dry-run : Menampilkan preview pencocokan tanpa menyimpan ke database} {--sync-legacy : Periksa dan selaraskan data lama yang memiliki ketidaksesuaian nama kecamatan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mendeteksi dan mengisi atau menyelaraskan kecamatan_id pada aset tanah berdasarkan nama peruntukan atau nama aset';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $syncLegacy = $this->option('sync-legacy');
        $isDryRun = $this->option('dry-run');

        // Urutkan berdasarkan panjang nama terpanjang lebih dahulu untuk mencegah false-positive (contoh: Banawa Selatan sebelum Banawa)
        $kecamatans = Kecamatan::orderByRaw('LENGTH(nama) DESC')->get();

        // 1. Tangani aset yang belum terisi kecamatan
        $this->info('Memeriksa data aset tanah yang belum memiliki kecamatan...');
        $unassigned = AsetTanah::whereNull('kecamatan_id')
            ->orWhere('kecamatan_id', 0)
            ->get();

        $totalUnassigned = $unassigned->count();
        $this->line("Ditemukan {$totalUnassigned} aset tanah tanpa kecamatan.");

        $matched = [];
        $unmatched = [];
        $breakdown = [];

        foreach ($unassigned as $aset) {
            $text = strtolower(($aset->nama_aset ?? '') . ' ' . ($aset->peruntukan ?? ''));
            $foundKec = null;

            foreach ($kecamatans as $kec) {
                $pattern = preg_replace('/\s+/', '\s*', preg_quote(strtolower($kec->nama), '/'));
                if ($kec->nama === 'Labuan') {
                    $pattern = 'labuan(?!\s+bajo)';
                }

                if (preg_match('/\b' . $pattern . '\b/i', $text) || preg_match('/' . $pattern . '/i', $text)) {
                    $foundKec = $kec;
                    break;
                }
            }

            if ($foundKec) {
                $matched[] = [
                    'id_aset' => $aset->id_aset,
                    'nama_aset' => $aset->nama_aset,
                    'peruntukan' => $aset->peruntukan,
                    'kecamatan_id' => $foundKec->id,
                    'kecamatan_nama' => $foundKec->nama,
                ];

                $breakdown[$foundKec->nama] = ($breakdown[$foundKec->nama] ?? 0) + 1;
            } else {
                $unmatched[] = $aset;
            }
        }

        if (count($matched) > 0) {
            $this->newLine();
            $this->info("Hasil Deteksi Aset Tanpa Kecamatan: " . count($matched) . " cocok, " . count($unmatched) . " belum teridentifikasi.");
            $this->line('<fg=cyan;options=bold>Rincian per Kecamatan:</>');
            $breakdownRows = [];
            foreach ($breakdown as $nama => $count) {
                $breakdownRows[] = [$nama, $count];
            }
            $this->table(['Kecamatan', 'Jumlah Aset Terdeteksi'], $breakdownRows);
        }

        // 2. Tangani data lama yang memiliki ketidaksesuaian (jika opsi --sync-legacy aktif)
        $legacyMatched = [];
        if ($syncLegacy) {
            $this->newLine();
            $this->info('Memeriksa data lama dengan kemungkinan ketidaksesuaian nama kecamatan...');

            $assignedAssets = AsetTanah::whereNotNull('kecamatan_id')
                ->where('kecamatan_id', '>', 0)
                ->get();

            foreach ($assignedAssets as $aset) {
                $text = strtolower(($aset->nama_aset ?? '') . ' ' . ($aset->peruntukan ?? ''));
                $foundKec = null;

                foreach ($kecamatans as $kec) {
                    $pattern = preg_replace('/\s+/', '\s*', preg_quote(strtolower($kec->nama), '/'));
                    if ($kec->nama === 'Labuan') {
                        $pattern = 'labuan(?!\s+bajo)';
                    }

                    if (preg_match('/\b' . $pattern . '\b/i', $text) || preg_match('/' . $pattern . '/i', $text)) {
                        $foundKec = $kec;
                        break;
                    }
                }

                if ($foundKec && $aset->kecamatan_id != $foundKec->id) {
                    // Pengecualian Labuan Bajo di Banawa
                    if ($foundKec->nama === 'Labuan' && stripos($text, 'bajo') !== false) {
                        continue;
                    }

                    $currentKec = $kecamatans->firstWhere('id', $aset->kecamatan_id);
                    $legacyMatched[] = [
                        'id_aset' => $aset->id_aset,
                        'nama_aset' => $aset->nama_aset,
                        'peruntukan' => $aset->peruntukan,
                        'current_id' => $aset->kecamatan_id,
                        'current_name' => $currentKec ? $currentKec->nama : 'N/A',
                        'target_id' => $foundKec->id,
                        'target_name' => $foundKec->nama,
                    ];
                }
            }

            $this->line("Ditemukan " . count($legacyMatched) . " aset lama dengan ketidaksesuaian kecamatan:");
            $legacyRows = [];
            foreach ($legacyMatched as $leg) {
                $legacyRows[] = [
                    $leg['id_aset'],
                    $leg['nama_aset'],
                    $leg['peruntukan'],
                    $leg['current_name'],
                    $leg['target_name'],
                ];
            }
            $this->table(['ID Aset', 'Nama Aset', 'Peruntukan', 'Kecamatan Lama', 'Kecamatan Seharusnya'], $legacyRows);
        }

        $totalToUpdate = count($matched) + count($legacyMatched);
        if ($totalToUpdate === 0) {
            $this->info('Tidak ada data yang perlu diperbarui.');
            return 0;
        }

        if ($isDryRun) {
            $this->warn('Mode --dry-run aktif: Perubahan TIDAK disimpan ke database.');
            return 0;
        }

        $this->newLine();
        $this->line('Menyimpan pembaruan ke database...');

        DB::transaction(function () use ($matched, $legacyMatched) {
            foreach ($matched as $item) {
                DB::table('aset_tanah')
                    ->where('id_aset', $item['id_aset'])
                    ->update([
                        'kecamatan_id' => $item['kecamatan_id'],
                        'updated_at' => now(),
                    ]);
            }

            foreach ($legacyMatched as $item) {
                DB::table('aset_tanah')
                    ->where('id_aset', $item['id_aset'])
                    ->update([
                        'kecamatan_id' => $item['target_id'],
                        'updated_at' => now(),
                    ]);
            }
        });

        // Invalidate cache dashboard agar statistik langsung ter-update
        app(SipatService::class)->invalidateDashboardCache();

        $this->info("✅ Berhasil menyelaraskan {$totalToUpdate} aset tanah ke kecamatan yang sesuai!");
        return 0;
    }
}
