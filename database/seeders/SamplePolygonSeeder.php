<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AsetTanah;

class SamplePolygonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $targetNibar = '120172030100000001002719521310101040020000001';

        // Cari aset berdasarkan NIBAR spesifik
        $aset = AsetTanah::where('kode_aset', $targetNibar)
            ->orWhere('kode_aset', ltrim($targetNibar, '0'))
            ->orWhere('nama_aset', 'LIKE', '%SMP%1%Banawa%')
            ->first();

        if (!$aset) {
            $this->command?->warn("Aset dengan NIBAR {$targetNibar} tidak ditemukan di tabel aset_tanah.");
            return;
        }

        $latCentroid = $aset->lat ? (float) $aset->lat : -0.676615;
        $lngCentroid = $aset->lng ? (float) $aset->lng : 119.743495;

        // Hitung luas target dari database aset tanah
        $targetLuas = (float) ($aset->luas > 0 ? $aset->luas : 5420);

        // Hitung dimensi panjang dan lebar dalam meter agar Luas Spasial = targetLuas m²
        $ratio = 1.3; // rasio aspek panjang : lebar
        $heightMeters = sqrt($targetLuas / $ratio);
        $widthMeters = $heightMeters * $ratio;

        // Konversi meter ke derajat koordinat spasial
        $dLat = ($heightMeters / 2) / 111320;
        $dLng = ($widthMeters / 2) / (111320 * cos(deg2rad($latCentroid)));

        // Poligon bidang tanah persil yang luas spasialnya presisi sesuai luas aset tanah
        $polygonGeojson = [
            'type' => 'Feature',
            'properties' => [
                'nibar' => $aset->kode_aset,
                'nama_aset' => $aset->nama_aset,
                'peruntukan' => $aset->peruntukan ?? 'Gedung Sekolah / Fasilitas Publik',
                'luas' => $targetLuas,
            ],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [
                    [
                        [round($lngCentroid - $dLng, 7), round($latCentroid + $dLat, 7)],
                        [round($lngCentroid + $dLng, 7), round($latCentroid + $dLat, 7)],
                        [round($lngCentroid + $dLng, 7), round($latCentroid - $dLat, 7)],
                        [round($lngCentroid - $dLng, 7), round($latCentroid - $dLat, 7)],
                        [round($lngCentroid - $dLng, 7), round($latCentroid + $dLat, 7)],
                    ]
                ]
            ]
        ];

        $aset->update([
            'geojson' => json_encode($polygonGeojson),
            'lat' => $latCentroid,
            'lng' => $lngCentroid,
        ]);

        $this->command?->info("✓ Berhasil memperbarui poligon GIS sesuai luas aset tanah:");
        $this->command?->line("  - NIBAR / Kode : {$aset->kode_aset}");
        $this->command?->line("  - Nama Aset    : {$aset->nama_aset}");
        $this->command?->line("  - Luas Aset    : " . number_format($targetLuas, 2) . " m²");
        $this->command?->line("  - Koordinat    : {$latCentroid}, {$lngCentroid}");
    }
}

