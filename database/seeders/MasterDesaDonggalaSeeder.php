<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kecamatan;
use App\Models\Desa;
use App\Services\Sipat\SipatService;

class MasterDesaDonggalaSeeder extends Seeder
{
    /**
     * Data Master 167 Desa dan Kelurahan se-Kabupaten Donggala
     * Berdasarkan Permendagri No. 137 Tahun 2017 jo. Permendagri No. 72 Tahun 2019
     * Serta data mutakhir BPS & Administrasi Pertanahan Pemkab Donggala.
     */
    public function run(): void
    {
        $data = [
            'Balaesang' => [
                ['nama' => 'Kampung Baru Sibayu', 'jenis' => 'Desa'],
                ['nama' => 'Labean', 'jenis' => 'Desa'],
                ['nama' => 'Lombonga', 'jenis' => 'Desa'],
                ['nama' => 'Malino', 'jenis' => 'Desa'],
                ['nama' => 'Mapane Tambu', 'jenis' => 'Desa'],
                ['nama' => 'Meli', 'jenis' => 'Desa'],
                ['nama' => 'Sibayu', 'jenis' => 'Desa'],
                ['nama' => 'Sibualong', 'jenis' => 'Desa'],
                ['nama' => 'Simagaya', 'jenis' => 'Desa'],
                ['nama' => 'Sipure', 'jenis' => 'Desa'],
                ['nama' => 'Siweli', 'jenis' => 'Desa'],
                ['nama' => 'Tambu', 'jenis' => 'Desa'],
                ['nama' => 'Tovia Tambu', 'jenis' => 'Desa'],
            ],
            'Balaesang Tanjung' => [
                ['nama' => 'Kamal', 'jenis' => 'Desa'],
                ['nama' => 'Kamal Muara', 'jenis' => 'Desa'],
                ['nama' => 'Kamonji', 'jenis' => 'Desa'],
                ['nama' => 'Ketong', 'jenis' => 'Desa'],
                ['nama' => 'Malei', 'jenis' => 'Desa'],
                ['nama' => 'Manimbaya', 'jenis' => 'Desa'],
                ['nama' => 'PalattaE', 'jenis' => 'Desa'],
                ['nama' => 'Palau', 'jenis' => 'Desa'],
                ['nama' => 'Pomolulu', 'jenis' => 'Desa'],
                ['nama' => 'Rano', 'jenis' => 'Desa'],
                ['nama' => 'Walandano', 'jenis' => 'Desa'],
            ],
            'Banawa' => [
                // 9 Kelurahan Resmi
                ['nama' => 'Boneoge', 'jenis' => 'Kelurahan'],
                ['nama' => 'Boya', 'jenis' => 'Kelurahan'],
                ['nama' => 'Ganti', 'jenis' => 'Kelurahan'],
                ['nama' => 'Gunung Bale', 'jenis' => 'Kelurahan'],
                ['nama' => 'Kabonga Besar', 'jenis' => 'Kelurahan'],
                ['nama' => 'Kabonga Kecil', 'jenis' => 'Kelurahan'],
                ['nama' => 'Labuan Bajo', 'jenis' => 'Kelurahan'],
                ['nama' => 'Maleni', 'jenis' => 'Kelurahan'],
                ['nama' => 'Tanjung Batu', 'jenis' => 'Kelurahan'],
                // 5 Desa
                ['nama' => 'Loli Dondo', 'jenis' => 'Desa'],
                ['nama' => 'Loli Oge', 'jenis' => 'Desa'],
                ['nama' => 'Loli Pesua', 'jenis' => 'Desa'],
                ['nama' => 'Loli Saluran', 'jenis' => 'Desa'],
                ['nama' => 'Loli Tasiburi', 'jenis' => 'Desa'],
            ],
            'Banawa Selatan' => [
                ['nama' => 'Bambarimi', 'jenis' => 'Desa'],
                ['nama' => 'Lalombi', 'jenis' => 'Desa'],
                ['nama' => 'Lembasada', 'jenis' => 'Desa'],
                ['nama' => 'Lumbulama', 'jenis' => 'Desa'],
                ['nama' => 'Lumbumamara', 'jenis' => 'Desa'],
                ['nama' => 'Lumbutarombo', 'jenis' => 'Desa'],
                ['nama' => 'Malino', 'jenis' => 'Desa'],
                ['nama' => 'Mbuwu', 'jenis' => 'Desa'],
                ['nama' => 'Ongulara', 'jenis' => 'Desa'],
                ['nama' => 'Salumpaku', 'jenis' => 'Desa'],
                ['nama' => 'Salungkaenu', 'jenis' => 'Desa'],
                ['nama' => 'Salusumbu', 'jenis' => 'Desa'],
                ['nama' => 'Sarombaya', 'jenis' => 'Desa'],
                ['nama' => 'Surumana', 'jenis' => 'Desa'],
                ['nama' => 'Tanah Mea', 'jenis' => 'Desa'],
                ['nama' => 'Tanampuru', 'jenis' => 'Desa'],
                ['nama' => 'Tolongano', 'jenis' => 'Desa'],
                ['nama' => 'Tosale', 'jenis' => 'Desa'],
                ['nama' => 'Watatu', 'jenis' => 'Desa'],
            ],
            'Banawa Tengah' => [
                ['nama' => 'Kola-Kola', 'jenis' => 'Desa'],
                ['nama' => 'Lampo', 'jenis' => 'Desa'],
                ['nama' => 'Lembobatu', 'jenis' => 'Desa'],
                ['nama' => 'Limboro', 'jenis' => 'Desa'],
                ['nama' => 'Limboro Makmur', 'jenis' => 'Desa'],
                ['nama' => 'Lumbudolo', 'jenis' => 'Desa'],
                ['nama' => 'Mekar Baru', 'jenis' => 'Desa'],
                ['nama' => 'Powelua', 'jenis' => 'Desa'],
                ['nama' => 'Salubomba', 'jenis' => 'Desa'],
                ['nama' => 'Towale', 'jenis' => 'Desa'],
            ],
            'Dampelas' => [
                ['nama' => 'Budi Mukti', 'jenis' => 'Desa'],
                ['nama' => 'Kambayang', 'jenis' => 'Desa'],
                ['nama' => 'Kambolangi', 'jenis' => 'Desa'],
                ['nama' => 'Karya Mukti', 'jenis' => 'Desa'],
                ['nama' => 'Lembah Mukti', 'jenis' => 'Desa'],
                ['nama' => 'Long', 'jenis' => 'Desa'],
                ['nama' => 'Malonas', 'jenis' => 'Desa'],
                ['nama' => "Pani'i", 'jenis' => 'Desa'],
                ['nama' => 'Parisan Agung', 'jenis' => 'Desa'],
                ['nama' => 'Ponggerang', 'jenis' => 'Desa'],
                ['nama' => 'Rerang', 'jenis' => 'Desa'],
                ['nama' => 'Sabang', 'jenis' => 'Desa'],
                ['nama' => 'Sioyong', 'jenis' => 'Desa'],
                ['nama' => 'Talaga', 'jenis' => 'Desa'],
            ],
            'Labuan' => [
                ['nama' => 'Labuan', 'jenis' => 'Desa'],
                ['nama' => 'Labuan Kungguma', 'jenis' => 'Desa'],
                ['nama' => 'Labuan Lelea', 'jenis' => 'Desa'],
                ['nama' => 'Labuan Lumbubaka', 'jenis' => 'Desa'],
                ['nama' => 'Labuan Panimba', 'jenis' => 'Desa'],
                ['nama' => 'Labuan Salumbone', 'jenis' => 'Desa'],
                ['nama' => 'Labuan Toposo', 'jenis' => 'Desa'],
            ],
            'Pinembani' => [
                ['nama' => 'Bambakaenu', 'jenis' => 'Desa'],
                ['nama' => 'Bambakanini', 'jenis' => 'Desa'],
                ['nama' => 'Bambapula', 'jenis' => 'Desa'],
                ['nama' => "Dangara'a", 'jenis' => 'Desa'],
                ['nama' => 'Dangia', 'jenis' => 'Desa'],
                ['nama' => 'Gimpubia', 'jenis' => 'Desa'],
                ['nama' => 'Kanagalongga', 'jenis' => 'Desa'],
                ['nama' => 'Karampe', 'jenis' => 'Desa'],
                ['nama' => 'Karavia', 'jenis' => 'Desa'],
                ['nama' => 'Nunuton', 'jenis' => 'Desa'],
                ['nama' => 'Palintuma', 'jenis' => 'Desa'],
                ['nama' => 'Tavanggeli', 'jenis' => 'Desa'],
                ['nama' => 'Tomodo', 'jenis' => 'Desa'],
            ],
            'Rio Pakava' => [
                ['nama' => 'Bonemarawa', 'jenis' => 'Desa'],
                ['nama' => 'Bukit Indah', 'jenis' => 'Desa'],
                ['nama' => 'Lalundu', 'jenis' => 'Desa'],
                ['nama' => 'Mbulawa', 'jenis' => 'Desa'],
                ['nama' => 'Minti Makmur', 'jenis' => 'Desa'],
                ['nama' => 'Ngovi', 'jenis' => 'Desa'],
                ['nama' => 'Pakava', 'jenis' => 'Desa'],
                ['nama' => 'Panca Mukti', 'jenis' => 'Desa'],
                ['nama' => 'Pantolobete', 'jenis' => 'Desa'],
                ['nama' => 'Polando Jaya', 'jenis' => 'Desa'],
                ['nama' => 'Polanto Jaya', 'jenis' => 'Desa'],
                ['nama' => 'Rio Mukti', 'jenis' => 'Desa'],
                ['nama' => 'Tawiora', 'jenis' => 'Desa'],
                ['nama' => 'Tinauka', 'jenis' => 'Desa'],
                ['nama' => 'Towiora', 'jenis' => 'Desa'],
            ],
            'Sindue' => [
                ['nama' => 'Amal', 'jenis' => 'Desa'],
                ['nama' => 'Ambesia', 'jenis' => 'Desa'],
                ['nama' => 'Dalaka', 'jenis' => 'Desa'],
                ['nama' => 'Enu', 'jenis' => 'Desa'],
                ['nama' => 'Kavaya', 'jenis' => 'Desa'],
                ['nama' => 'Kumbasa', 'jenis' => 'Desa'],
                ['nama' => 'Lero', 'jenis' => 'Desa'],
                ['nama' => 'Lero Tatari', 'jenis' => 'Desa'],
                ['nama' => 'Marana', 'jenis' => 'Desa'],
                ['nama' => 'Masaingi', 'jenis' => 'Desa'],
                ['nama' => 'Sumari', 'jenis' => 'Desa'],
                ['nama' => 'Taripa', 'jenis' => 'Desa'],
                ['nama' => 'Toaya', 'jenis' => 'Desa'],
                ['nama' => 'Toaya Vunta', 'jenis' => 'Desa'],
            ],
            'Sindue Tobata' => [
                ['nama' => 'Alindau', 'jenis' => 'Desa'],
                ['nama' => 'Oti', 'jenis' => 'Desa'],
                ['nama' => 'Sikara', 'jenis' => 'Desa'],
                ['nama' => 'Sikara Tobata', 'jenis' => 'Desa'],
                ['nama' => 'Sindosa', 'jenis' => 'Desa'],
                ['nama' => 'Sipeso', 'jenis' => 'Desa'],
                ['nama' => 'Tamarenja', 'jenis' => 'Desa'],
                ['nama' => 'Tobata', 'jenis' => 'Desa'],
            ],
            'Sindue Tombusabora' => [
                ['nama' => 'Batusuya', 'jenis' => 'Desa'],
                ['nama' => "Batusuya Go'o", 'jenis' => 'Desa'],
                ['nama' => 'Kaliburu', 'jenis' => 'Desa'],
                ['nama' => 'Kaliburu Kata', 'jenis' => 'Desa'],
                ['nama' => 'Saloya', 'jenis' => 'Desa'],
                ['nama' => 'Tibo', 'jenis' => 'Desa'],
            ],
            'Sirenja' => [
                ['nama' => 'Aloon', 'jenis' => 'Desa'],
                ['nama' => 'Balintuma', 'jenis' => 'Desa'],
                ['nama' => 'Damapal', 'jenis' => 'Desa'],
                ['nama' => 'Dampal', 'jenis' => 'Desa'],
                ['nama' => 'Jonooge', 'jenis' => 'Desa'],
                ['nama' => 'Jono Oge', 'jenis' => 'Desa'],
                ['nama' => 'Lende', 'jenis' => 'Desa'],
                ['nama' => 'Lende Tovea', 'jenis' => 'Desa'],
                ['nama' => 'Lompia', 'jenis' => 'Desa'],
                ['nama' => 'Lompio', 'jenis' => 'Desa'],
                ['nama' => 'Ombo', 'jenis' => 'Desa'],
                ['nama' => 'Sibado', 'jenis' => 'Desa'],
                ['nama' => 'Sipi', 'jenis' => 'Desa'],
                ['nama' => 'Tanjung Padang', 'jenis' => 'Desa'],
                ['nama' => 'Tompe', 'jenis' => 'Desa'],
                ['nama' => 'Tondo', 'jenis' => 'Desa'],
                ['nama' => 'Ujumbou', 'jenis' => 'Desa'],
            ],
            'Sojol' => [
                ['nama' => 'Balukang', 'jenis' => 'Desa'],
                ['nama' => 'Balukang II', 'jenis' => 'Desa'],
                ['nama' => 'Bou', 'jenis' => 'Desa'],
                ['nama' => 'Bukit Harapan', 'jenis' => 'Desa'],
                ['nama' => 'Panggalasiang', 'jenis' => 'Desa'],
                ['nama' => 'Samalili', 'jenis' => 'Desa'],
                ['nama' => 'Siboang', 'jenis' => 'Desa'],
                ['nama' => 'Siwelempu', 'jenis' => 'Desa'],
                ['nama' => 'Tonggolobibi', 'jenis' => 'Desa'],
            ],
            'Sojol Utara' => [
                ['nama' => 'Bengkel', 'jenis' => 'Desa'],
                ['nama' => 'Bengkoli', 'jenis' => 'Desa'],
                ['nama' => 'Lenju', 'jenis' => 'Desa'],
                ['nama' => 'Ogoamas I', 'jenis' => 'Desa'],
                ['nama' => 'Ogoamas II', 'jenis' => 'Desa'],
                ['nama' => 'Pesik', 'jenis' => 'Desa'],
            ],
            'Tanantovea' => [
                ['nama' => 'Bale', 'jenis' => 'Desa'],
                ['nama' => 'Ganti', 'jenis' => 'Desa'],
                ['nama' => 'Guntarano', 'jenis' => 'Desa'],
                ['nama' => 'Nupa Bomba', 'jenis' => 'Desa'],
                ['nama' => 'Wani I', 'jenis' => 'Desa'],
                ['nama' => 'Wani II', 'jenis' => 'Desa'],
                ['nama' => 'Wani III', 'jenis' => 'Desa'],
                ['nama' => 'Wani Dua', 'jenis' => 'Desa'],
                ['nama' => 'Wani Lumbumpetigo', 'jenis' => 'Desa'],
                ['nama' => 'Wani Satu', 'jenis' => 'Desa'],
                ['nama' => 'Wani Tiga', 'jenis' => 'Desa'],
                ['nama' => 'Wombo', 'jenis' => 'Desa'],
                ['nama' => 'Wombo Kalonggo', 'jenis' => 'Desa'],
                ['nama' => 'Wombo Mpanau', 'jenis' => 'Desa'],
            ],
        ];

        // 1. Tangani ejaan lama & relasi tertukar
        // - Pindahkan Lumbudolo ke Banawa Tengah jika masih di Banawa Selatan
        $kecBanawaTengah = Kecamatan::where('nama', 'Banawa Tengah')->first();
        if ($kecBanawaTengah) {
            Desa::where('nama', 'Lumbudolo')->update(['kecamatan_id' => $kecBanawaTengah->id]);
        }

        // - Update ejaan Tibabo -> Tibo jika ada di Sindue Tombusabora
        $kecTombusabora = Kecamatan::where('nama', 'Sindue Tombusabora')->first();
        if ($kecTombusabora) {
            Desa::where('kecamatan_id', $kecTombusabora->id)->where('nama', 'Tibabo')->update(['nama' => 'Tibo']);
        }

        // - Update Boyabouge -> Boya (Kelurahan) di Banawa
        $kecBanawa = Kecamatan::where('nama', 'Banawa')->first();
        if ($kecBanawa) {
            $boyabouge = Desa::where('kecamatan_id', $kecBanawa->id)->where('nama', 'Boyabouge')->first();
            if ($boyabouge) {
                $boyabouge->update(['nama' => 'Boya', 'jenis' => 'Kelurahan']);
            }
        }

        // - Update Tambo -> Tambu di Balaesang jika hanya ada Tambo
        $kecBalaesang = Kecamatan::where('nama', 'Balaesang')->first();
        if ($kecBalaesang) {
            $tambo = Desa::where('kecamatan_id', $kecBalaesang->id)->where('nama', 'Tambo')->first();
            if ($tambo && !Desa::where('kecamatan_id', $kecBalaesang->id)->where('nama', 'Tambu')->exists()) {
                $tambo->update(['nama' => 'Tambu']);
            }
        }

        // 2. Insert or update semua desa dan kelurahan
        $inserted = 0;
        $updated = 0;

        foreach ($data as $kecNama => $desaList) {
            $kec = Kecamatan::where('nama', $kecNama)->first();
            if (!$kec) continue;

            foreach ($desaList as $d) {
                $desa = Desa::where('kecamatan_id', $kec->id)
                    ->where('nama', $d['nama'])
                    ->first();

                if ($desa) {
                    if ($desa->jenis !== $d['jenis']) {
                        $desa->update(['jenis' => $d['jenis']]);
                        $updated++;
                    }
                } else {
                    Desa::create([
                        'kecamatan_id' => $kec->id,
                        'nama'         => $d['nama'],
                        'jenis'        => $d['jenis'],
                    ]);
                    $inserted++;
                }
            }
        }

        // 3. Pastikan 9 Kelurahan di Banawa memiliki jenis 'Kelurahan'
        if ($kecBanawa) {
            $kelurahanNames = ['Boneoge', 'Boya', 'Ganti', 'Gunung Bale', 'Kabonga Besar', 'Kabonga Kecil', 'Labuan Bajo', 'Maleni', 'Tanjung Batu'];
            Desa::where('kecamatan_id', $kecBanawa->id)
                ->whereIn('nama', $kelurahanNames)
                ->update(['jenis' => 'Kelurahan']);
        }

        // Invalidate cache statistik SIPAT
        app(SipatService::class)->invalidateDashboardCache();

        $totalSekarang = Desa::count();
        $this->command->info("Pemutakhiran Master Desa Selesai! (+{$inserted} baru, ~{$updated} diperbarui). Total Desa/Kelurahan: {$totalSekarang}");
    }
}
