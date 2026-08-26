<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AsetTanah;
use App\Models\OpdSipat;
use App\Models\StatusProses;
use App\Models\ProsesAset;

class UnrecordedLandSeeder extends Seeder
{
    public function run(): void
    {
        $opd = OpdSipat::where('nama', 'LIKE', '%Pendidikan%')->first();
        $opdId = $opd?->id ?? 1;
        $opdNama = $opd?->nama ?? 'Dinas Pendidikan dan Kebudayaan';

        $statusBelum = StatusProses::where('nama_status', 'LIKE', '%Belum%')->first();
        $statusId = $statusBelum?->id_status ?? 1;

        $items = [
            // Rio Pakava & Banawa Selatan
            ['nama' => 'Tanah Bangunan SDN 17 RIO PAKAVA', 'lokasi' => 'Kec. Rio Pakava'],
            ['nama' => 'Tanah Bangunan SDN 21 RIO PAKAVA', 'lokasi' => 'Kec. Rio Pakava'],
            ['nama' => 'Tanah Bangunan SDN 26 RIO PAKAVA', 'lokasi' => 'Kec. Rio Pakava'],
            ['nama' => 'Tanah Bangunan SMPN 7 RIO PAKAVA', 'lokasi' => 'Kec. Rio Pakava'],
            ['nama' => 'Tanah Bangunan SDN 35 BANAWA SELATAN', 'lokasi' => 'Kec. Banawa Selatan'],

            // Tanantovea, Sirenja & Dampelas
            ['nama' => 'Tanah Bangunan SDN 8 TANANTOVEA', 'lokasi' => 'Kec. Tanantovea'],
            ['nama' => 'Tanah Bangunan SMPN 5 SIRENJA', 'lokasi' => 'Kec. Sirenja'],
            ['nama' => 'Tanah Bangunan TK NEGERI PEMBINA SIRENJA', 'lokasi' => 'Kec. Sirenja'],
            ['nama' => 'Tanah Bangunan SMPN SATAP 7 DAMPELAS', 'lokasi' => 'Kec. Dampelas'],

            // Sindue, Tombusabora & Nupabomba
            ['nama' => 'Tanah Bangunan SDN 24 SINDUE', 'lokasi' => 'Kec. Sindue'],
            ['nama' => 'Tanah Bangunan TK NEGERI PEMBINA SINDUE Toaya Vunta', 'lokasi' => 'Kec. Sindue / Toaya Vunta'],
            ['nama' => 'Tanah Bangunan SMPN SATAP 3 SINDUE TOMBUSABORA', 'lokasi' => 'Kec. Sindue Tombusabora'],
            ['nama' => 'Tanah Bangunan TK Negeri Berlian Nupabomba', 'lokasi' => 'Kec. Tanantovea / Nupabomba'],

            // Balaesang, Balaesang Tanjung & Sojol Utara
            ['nama' => 'Tanah Bangunan SDN 28 BALAESANG', 'lokasi' => 'Kec. Balaesang'],
            ['nama' => 'Tanah Bangunan SMPN 1 BALAESANG TANJUNG', 'lokasi' => 'Kec. Balaesang Tanjung'],
            ['nama' => 'Tanah Bangunan SMPN SATAP 6 BALAESANG TANJUNG', 'lokasi' => 'Kec. Balaesang Tanjung'],
            ['nama' => 'Tanah Bangunan SMPN 1 SOJOL UTARA', 'lokasi' => 'Kec. Sojol Utara'],
        ];

        $insertedCount = 0;
        $datePrefix = 'DRAFT-' . date('Ymd') . '-';
        $startCounter = 1;

        foreach ($items as $item) {
            // Check if asset already exists by name
            $exists = AsetTanah::where('nama_aset', 'LIKE', "%{$item['nama']}%")->first();
            if ($exists) {
                $this->command?->line("Aset '{$item['nama']}' sudah ada di database (Kode: {$exists->kode_aset}). SKIPPED.");
                continue;
            }

            // Generate unique draft NIBAR
            do {
                $candidateCode = $datePrefix . str_pad((string) $startCounter, 4, '0', STR_PAD_LEFT);
                $codeExists = AsetTanah::where('kode_aset', $candidateCode)->exists();
                $startCounter++;
            } while ($codeExists);

            $aset = AsetTanah::create([
                'kode_aset' => $candidateCode,
                'status_pencatatan' => 'USULAN_BELUM_TERCATAT',
                'nama_aset' => $item['nama'],
                'peruntukan' => 'Gedung Sekolah / Bangunan Pendidikan',
                'opd_id' => $opdId,
                'opd' => $opdNama,
                'luas' => 0,
                'alamat' => $item['lokasi'],
                'keterangan' => 'Belum Ada di Master Data KIB A (Usulan Belum Tercatat)',
            ]);

            // Assign initial status
            ProsesAset::create([
                'id_aset' => $aset->id_aset,
                'id_status' => $statusId,
                'tanggal_proses' => date('Y-m-d'),
                'keterangan' => 'Pendaftaran awal tanah belum tercatat KIB A',
            ]);

            $insertedCount++;
            $this->command?->info("✓ Berhasil mendaftarkan: {$item['nama']} [NIBAR Draft: {$candidateCode}]");
        }

        $this->command?->info("Total {$insertedCount} tanah belum tercatat berhasil didaftarkan ke sistem.");
    }
}
