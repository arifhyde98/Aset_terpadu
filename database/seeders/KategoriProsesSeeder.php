<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KategoriProses;
use App\Models\StatusProses;

class KategoriProsesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultCategories = [
            [
                'kode'                 => 'sudah_bersertifikat',
                'nama'                 => 'Sudah Bersertifikat',
                'deskripsi'            => 'Tanah yang sudah terbit sertifikat resminya dari BPN.',
                'exclude_target'       => false,
                'includes_unprocessed' => false,
                'warna'                => 'success',
                'urutan'               => 1,
                'is_system'            => true,
                'is_active'            => true,
                'match_raw_categories' => ['bersertifikat'],
            ],
            [
                'kode'                 => 'dalam_proses',
                'nama'                 => 'Dalam Proses BPN',
                'deskripsi'            => 'Tanah yang sedang dalam tahapan pengurusan sertifikat BPN.',
                'exclude_target'       => false,
                'includes_unprocessed' => false,
                'warna'                => 'info',
                'urutan'               => 2,
                'is_system'            => true,
                'is_active'            => true,
                'match_raw_categories' => ['proses', 'permohonan_bpn'],
            ],
            [
                'kode'                 => 'belum_diurus',
                'nama'                 => 'Belum Diurus',
                'deskripsi'            => 'Seluruh tanah yang belum diproses di BPN (termasuk yang masuk kuota target tahunan).',
                'exclude_target'       => false,
                'includes_unprocessed' => true,
                'warna'                => 'secondary',
                'urutan'               => 3,
                'is_system'            => true,
                'is_active'            => true,
                'match_raw_categories' => ['belum_diurus', 'belum_diproses'],
            ],
            [
                'kode'                 => 'belum_bersertifikat',
                'nama'                 => 'Belum Bersertifikat (Tanpa Target)',
                'deskripsi'            => 'Tanah belum bersertifikat murni yang berada di luar kuota Target Pensertifikatan tahun berjalan.',
                'exclude_target'       => true,
                'includes_unprocessed' => true,
                'warna'                => 'warning',
                'urutan'               => 4,
                'is_system'            => true,
                'is_active'            => true,
                'match_raw_categories' => ['belum_diurus', 'belum_diproses'],
            ],
            [
                'kode'                 => 'bermasalah',
                'nama'                 => 'Bermasalah / Sengketa',
                'deskripsi'            => 'Tanah yang memiliki kendala yuridis, fisik, atau bersengketa hukum.',
                'exclude_target'       => false,
                'includes_unprocessed' => false,
                'warna'                => 'danger',
                'urutan'               => 5,
                'is_system'            => true,
                'is_active'            => true,
                'match_raw_categories' => ['kendala'],
            ],
        ];

        $allStatuses = StatusProses::all();

        foreach ($defaultCategories as $catData) {
            $matchCats = $catData['match_raw_categories'];
            unset($catData['match_raw_categories']);

            $kategori = KategoriProses::updateOrCreate(
                ['kode' => $catData['kode']],
                $catData
            );

            // Cari status_proses yang cocok dengan match_raw_categories
            $matchingStatusIds = [];
            foreach ($allStatuses as $st) {
                $stCategories = array_map('strtolower', $st->categories);
                if (!empty(array_intersect($matchCats, $stCategories))) {
                    $matchingStatusIds[] = $st->id_status;
                }
            }

            if (!empty($matchingStatusIds)) {
                $kategori->statusProses()->syncWithoutDetaching($matchingStatusIds);
            }
        }
    }
}
