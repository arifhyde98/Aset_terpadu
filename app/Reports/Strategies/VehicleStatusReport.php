<?php

namespace App\Reports\Strategies;

use App\Reports\Contracts\ReportStrategy;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;

/**
 * Strategi Laporan khusus untuk Status dan Kondisi Fisik Kendaraan Dinas.
 * 
 * Menyaring data kendaraan berdasarkan kondisi fisik, instansi OPD, dan tahun perolehan.
 */
class VehicleStatusReport implements ReportStrategy
{
    /**
     * Membangun kueri basis data untuk Laporan Status & Kondisi.
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
                'status',
                'kondisi',
                'opd_id',
                'opd',
                'pemegang',
                'nilai_perolehan',
            ]);

        // Terapkan Filter Kondisi (menggunakan Enum value di database)
        if (!empty($filters['kondisi'])) {
            $query->where('kondisi', $filters['kondisi']);
        }

        // Terapkan Filter Instansi OPD
        if (!empty($filters['opd_id'])) {
            $query->where('opd_id', $filters['opd_id']);
        }

        // Terapkan Filter Tahun Perolehan Kendaraan
        if (!empty($filters['tahun'])) {
            $query->whereYear('tgl_perolehan', $filters['tahun']);
        }

        return $query->latest('id');
    }

    /**
     * Mendapatkan daftar judul kolom (headers) untuk Laporan Status & Kondisi.
     *
     * @return array<string, string> Asosiasi nama kolom ke label Bahasa Indonesia
     */
    public function headers(): array
    {
        return [
            'no_polisi'       => 'Plat Nomor',
            'nomor_register'  => 'Nomor Register',
            'merk'            => 'Merek',
            'tipe'            => 'Tipe',
            'kondisi'         => 'Kondisi Fisik',
            'status'          => 'Status Operasional',
            'opd'             => 'Instansi Pengelola',
            'pemegang'        => 'Pemegang / Penanggung Jawab',
            'nilai_perolehan' => 'Nilai Perolehan',
        ];
    }
}
