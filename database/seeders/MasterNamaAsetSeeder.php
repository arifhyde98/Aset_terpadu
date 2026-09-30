<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\MasterNamaAset;
use App\Models\AsetTanah;

class MasterNamaAsetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ekstrak seluruh variasi nama_aset unik dari aset_tanah
        $rawNames = DB::table('aset_tanah')
            ->whereNotNull('nama_aset')
            ->where('nama_aset', '!=', '')
            ->distinct()
            ->pluck('nama_aset');

        $cleanedMapping = []; // rawName => cleanName

        foreach ($rawNames as $raw) {
            $clean = trim(preg_replace('/\s+/', ' ', str_replace(["\r", "\n"], ' ', $raw)));
            
            // Koreksi typo umum
            if ($clean === 'Tanah Banguan Pendidikan dan Latihan') {
                $clean = 'Tanah Bangunan Pendidikan Dan Latihan';
            }

            $cleanedMapping[$raw] = $clean;
        }

        // 2. Simpan master data unik ke tabel master_nama_aset
        $uniqueCleanNames = array_unique(array_values($cleanedMapping));
        sort($uniqueCleanNames);

        $insertedCount = 0;
        foreach ($uniqueCleanNames as $index => $cleanName) {
            // Tentukan kelompok sederhana berdasarkan awalan
            $kelompok = 'Lain-Lain';
            if (str_starts_with($cleanName, 'Tanah Bangunan') || str_starts_with($cleanName, 'Bangunan')) {
                $kelompok = 'Tanah Bangunan Gedung';
            } elseif (str_starts_with($cleanName, 'Tanah Untuk Jalan')) {
                $kelompok = 'Tanah Jalan & Transportasi';
            } elseif (str_starts_with($cleanName, 'Tanah Untuk Bangunan')) {
                $kelompok = 'Tanah Prasarana & Jaringan';
            } elseif (str_starts_with($cleanName, 'Tanah Lapangan')) {
                $kelompok = 'Tanah Terbuka & Lapangan';
            } elseif (str_starts_with($cleanName, 'Tanah Sawah') || str_starts_with($cleanName, 'Tanah Tambak') || str_starts_with($cleanName, 'Tanah Usaha')) {
                $kelompok = 'Tanah Pertanian & Usaha';
            } elseif (str_starts_with($cleanName, 'Tanah Kosong') || str_starts_with($cleanName, 'Tanah Kampung')) {
                $kelompok = 'Tanah Kosong / Perkampungan';
            }

            $master = MasterNamaAset::firstOrCreate(
                ['nama' => $cleanName],
                [
                    'kelompok'  => $kelompok,
                    'is_active' => true,
                    'urutan'    => $index + 1,
                ]
            );

            if ($master->wasRecentlyCreated) {
                $insertedCount++;
            }
        }

        // 3. Update aset_tanah.nama_aset_id secara presisi
        $updatedRows = 0;
        foreach ($cleanedMapping as $raw => $clean) {
            $master = MasterNamaAset::where('nama', $clean)->first();
            if ($master) {
                $affected = DB::table('aset_tanah')
                    ->where('nama_aset', $raw)
                    ->whereNull('nama_aset_id')
                    ->update([
                        'nama_aset_id' => $master->id,
                        'nama_aset'    => $clean, // Sekaligus standarisasi nama bersih
                    ]);
                $updatedRows += $affected;
            }
        }

        $this->command?->info("MasterNamaAsetSeeder: {$insertedCount} master baru dibuat, {$updatedRows} data aset tanah berhasil ditautkan.");
    }
}
