<?php

namespace App\Services\Bangunan;

use App\Enums\BangunanKondisi;
use App\Enums\UserRole;
use App\Models\Bangunan;
use App\Models\Opd;
use App\Models\AsetTanah;
use App\Models\Kecamatan;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BangunanService
{
    /**
     * Mengambil daftar bangunan dengan filter & pagination.
     */
    public function getPaginatedBangunan(array $filters = [], int $perPage = 15, ?User $user = null): LengthAwarePaginator
    {
        return Bangunan::with(['opdSipat', 'asetTanah', 'kecamatan', 'desa'])
            ->forUser($user)
            ->filter($filters)
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Mengambil ringkasan statistik modul bangunan.
     */
    public function getDashboardStats(?User $user = null): array
    {
        $user = $user ?? auth()->user();
        $cacheKey = ($user && $user->role === UserRole::OPD)
            ? "bangunan.stats.opd.{$user->opd_id}"
            : "bangunan.stats.global";

        return Cache::remember($cacheKey, 3600, function () use ($user) {
            $query = Bangunan::forUser($user);

            $total = (clone $query)->count();
            $nilaiPerolehan = (clone $query)->sum('harga_perolehan');
            $luasTotal = (clone $query)->sum('luas_lantai');

            $baik = (clone $query)->where('kondisi', BangunanKondisi::BAIK->value)->count();
            $kurangBaik = (clone $query)->where('kondisi', BangunanKondisi::KURANG_BAIK->value)->count();
            $rusakBerat = (clone $query)->where('kondisi', BangunanKondisi::RUSAK_BERAT->value)->count();

            // Breakdown jenis bangunan
            $jenisStats = (clone $query)
                ->select('jenis_bangunan', DB::raw('count(*) as count'))
                ->groupBy('jenis_bangunan')
                ->pluck('count', 'jenis_bangunan')
                ->toArray();

            return [
                'total'           => $total,
                'nilai_perolehan' => $nilaiPerolehan,
                'luas_total'      => $luasTotal,
                'baik'            => $baik,
                'kurang_baik'     => $kurangBaik,
                'rusak_berat'     => $rusakBerat,
                'jenis_stats'     => $jenisStats,
            ];
        });
    }

    /**
     * Merekomendasikan pemetaan kolom Excel ke kolom database berdasarkan analisis semantik.
     * Mengadopsi arsitektur E-RANDIS dengan kamus sinonim KIB C.
     */
    public function suggestColumnMapping(array $headers): array
    {
        $suggestions = [];

        // Kamus sinonim kolom target database KIB C
        $synonyms = [
            'nama_bangunan' => [
                'nama barang', 'nama aset', 'nama gedung', 'nama bangunan', 'uraian', 'uraian barang',
                'jenis gedung', 'nama barang / gedung', 'nama gedung kantor', 'nama', 'barang'
            ],
            'kode_barang' => [
                'kode barang', 'kd_brg', 'kd barang', 'kodefikasi', 'kode rekening', 'rekening',
                'kode brg', 'kd_barang', 'kode barang kib c'
            ],
            'nomor_register' => [
                'nomor register', 'no register', 'no. register', 'nomer register', 'reg', 'noreg',
                'register', 'register number', 'reg number', 'no_register', 'no reg'
            ],
            'kondisi' => [
                'kondisi', 'kondisi fisik', 'keadaan', 'status kondisi', 'condition', 'keadaan barang',
                'kondisi bangunan', 'keadaan fisik', 'baik/rusak'
            ],
            'konstruksi_tingkat' => [
                'tingkat', 'bertingkat', 'bertingkat / tidak', 'tingkat/tidak', 'jumlah lantai', 'lantai',
                'bertingkat atau tidak', 'konstruksi tingkat'
            ],
            'konstruksi_beton' => [
                'beton', 'konstruksi beton', 'beton / tidak', 'beton/tidak', 'beton bertulang', 'konstruksi',
                'beton atau tidak', 'tipe beton'
            ],
            'luas_lantai' => [
                'luas lantai', 'luas lantai m2', 'luas lantai (m2)', 'luas bangunan', 'luas total',
                'luas m2', 'luas gedung', 'luas', 'luas (m2)'
            ],
            'luas_dasar' => [
                'luas dasar', 'luas tapak', 'luas tapak bangunan', 'luas tanah terpakai', 'luas dasar bangunan'
            ],
            'alamat' => [
                'alamat', 'letak', 'lokasi', 'letak/alamat', 'posisi', 'alamat lengkap', 'lokasi aset',
                'letak alamat', 'lokasi fisik'
            ],
            'nomor_dokumen_pbg' => [
                'nomor dokumen', 'no pbg', 'no imb', 'no. dokumen', 'dokumen pbg', 'dokumen gedung',
                'nomor izin', 'no pbg/imb', 'surat pbg', 'no izin', 'dokumen'
            ],
            'tanggal_dokumen_pbg' => [
                'tanggal dokumen', 'tgl pbg', 'tgl imb', 'tgl izin', 'tgl dokumen', 'tanggal pbg',
                'tanggal izin', 'tanggal pbg/imb'
            ],
            'status_tanah_dasar' => [
                'status tanah', 'tanah dasar', 'status kepemilikan tanah', 'legalitas tanah',
                'status penguasaan tanah', 'tanah'
            ],
            'nomor_kode_tanah' => [
                'kode tanah', 'nibar', 'nomor kode tanah', 'nibar tanah', 'no sertifikat tanah',
                'kode tanah kib a', 'nibar kib a'
            ],
            'asal_usul' => [
                'asal usul', 'asal-usul', 'perolehan', 'sumber dana', 'anggaran', 'asal', 'cara perolehan'
            ],
            'tahun_pengadaan' => [
                'tahun', 'tahun perolehan', 'tahun pembangunan', 'thn perolehan', 'tahun selesai',
                'thn', 'tahun buat', 'tahun pengadaan'
            ],
            'harga_perolehan' => [
                'harga', 'nilai perolehan', 'harga perolehan', 'nilai', 'nilai aset', 'harga beli',
                'biaya perolehan', 'jumlah perolehan', 'harga bangunan', 'nilai buku'
            ],
            'keterangan' => [
                'keterangan', 'ket', 'catatan', 'note', 'notes', 'keterangan aset'
            ],
            'opd' => [
                'opd', 'skpd', 'dinas', 'instansi', 'unit kerja', 'badan', 'kantor', 'nama opd'
            ],
        ];

        foreach ($headers as $header) {
            $cleanHeader = strtolower(trim($header));
            $cleanHeader = preg_replace('/[^a-z0-9\s]/', '', $cleanHeader);
            $cleanHeader = preg_replace('/\s+/', ' ', $cleanHeader);

            $bestMatch = null;
            $highestSimilarity = 0;

            // 1. Cari kecocokan eksak
            foreach ($synonyms as $targetColumn => $list) {
                if (in_array($cleanHeader, $list)) {
                    $bestMatch = $targetColumn;
                    break;
                }
            }

            // 2. Jika tidak ada eksak, gunakan similar_text (threshold >= 65%)
            if (!$bestMatch) {
                foreach ($synonyms as $targetColumn => $list) {
                    foreach ($list as $synonym) {
                        similar_text($cleanHeader, $synonym, $percent);
                        if ($percent > $highestSimilarity && $percent >= 65) {
                            $highestSimilarity = $percent;
                            $bestMatch = $targetColumn;
                        }
                    }
                }
            }

            $suggestions[$header] = $bestMatch ?? '';
        }

        return $suggestions;
    }

    /**
     * Target kolom database untuk pemetaan impor.
     */
    public function getTargetColumns(): array
    {
        return [
            'nama_bangunan'       => 'Nama Bangunan / Gedung (*Wajib)',
            'kode_barang'         => 'Kode Barang (BMD)',
            'nomor_register'      => 'Nomor Register',
            'kondisi'             => 'Kondisi Fisik (Baik/Kurang Baik/Rusak Berat)',
            'konstruksi_tingkat'  => 'Bertingkat (Ya/Tidak)',
            'konstruksi_beton'    => 'Konstruksi Beton (Ya/Tidak)',
            'luas_lantai'         => 'Luas Lantai (m²)',
            'luas_dasar'          => 'Luas Tapak Dasar (m²)',
            'alamat'              => 'Letak / Alamat Bangunan',
            'nomor_dokumen_pbg'   => 'Nomor IMB / PBG',
            'tanggal_dokumen_pbg' => 'Tanggal IMB / PBG',
            'status_tanah_dasar'  => 'Status Tanah Tempat Berdiri',
            'nomor_kode_tanah'    => 'NIBAR / Kode Tanah KIB A',
            'asal_usul'           => 'Asal Usul Perolehan',
            'tahun_pengadaan'     => 'Tahun Pembangunan / Pengadaan',
            'harga_perolehan'     => 'Harga / Nilai Perolehan (Rp)',
            'keterangan'          => 'Keterangan Tambahan',
            'opd'                 => 'Nama OPD / Instansi Pengguna',
        ];
    }
}
