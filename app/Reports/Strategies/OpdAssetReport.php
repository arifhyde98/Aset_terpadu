<?php

namespace App\Reports\Strategies;

use App\Reports\Contracts\ReportStrategy;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;

/**
 * Strategi Laporan khusus untuk Distribusi Aset per Organisasi Perangkat Daerah (OPD).
 * 
 * Mengelompokkan dan menyaring aset berdasarkan pengelola instansi OPD secara teratur.
 */
class OpdAssetReport implements ReportStrategy
{
    /**
     * Membangun kueri basis data untuk Laporan Distribusi Aset per OPD.
     *
     * @param array<string, mixed> $filters Kumpulan filter pencarian (kondisi, opd_id, tahun)
     * @return Builder Kueri Eloquent ter-eager load untuk mencegah N+1
     */
    public function query(array $filters): Builder
    {
        $query = Vehicle::query()
            ->with(['opdRelation', 'vehicleType'])
            ->select([
                'id',
                'no_polisi',
                'nomor_register',
                'merk',
                'tipe',
                'opd_id',
                'opd',
                'pemegang',
                'nilai_perolehan',
                'kondisi',
                'status',
            ]);

        // Terapkan Filter Kondisi
        if (!empty($filters['kondisi'])) {
            $query->where('kondisi', $filters['kondisi']);
        }

        // Terapkan Filter Instansi OPD
        if (!empty($filters['opd_id'])) {
            $query->where('opd_id', $filters['opd_id']);
        }

        // Terapkan Filter Tahun Perolehan
        if (!empty($filters['tahun'])) {
            $query->whereYear('tgl_perolehan', $filters['tahun']);
        }

        // Urutkan berdasarkan OPD agar distribusi data tertata rapi
        return $query->orderBy('opd_id')->latest('id');
    }

    /**
     * Mendapatkan daftar judul kolom (headers) untuk Laporan Distribusi Aset.
     *
     * @return array<string, string> Asosiasi nama kolom ke label Bahasa Indonesia
     */
    public function headers(): array
    {
        return [
            'opd'             => 'Instansi Pengelola',
            'no_polisi'       => 'Plat Nomor',
            'nomor_register'  => 'Nomor Register',
            'merk'            => 'Merek',
            'tipe'            => 'Tipe',
            'pemegang'        => 'Pemegang / Penanggung Jawab',
            'kondisi'         => 'Kondisi Fisik',
            'status'          => 'Status Operasional',
            'nilai_perolehan' => 'Nilai Perolehan',
        ];
    }
}
