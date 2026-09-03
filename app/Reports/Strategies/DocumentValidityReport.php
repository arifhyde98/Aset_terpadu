<?php

namespace App\Reports\Strategies;

use App\Reports\Contracts\ReportStrategy;
use App\Models\Vehicle;
use App\Models\EbmdVehicle;
use Illuminate\Database\Eloquent\Builder;

/**
 * Strategi Laporan khusus untuk Kelengkapan Surat & Masa Berlaku Dokumen/STNK Kendaraan.
 * 
 * Membantu instansi mendeteksi dokumen kelengkapan yang sudah mati atau hilang.
 */
class DocumentValidityReport implements ReportStrategy
{
    /**
     * Membangun kueri basis data untuk Laporan Masa Berlaku Dokumen.
     *
     * @param array<string, mixed> $filters Kumpulan filter pencarian (kondisi, opd_id, tahun, source)
     * @return Builder Kueri Eloquent ter-eager load untuk mencegah N+1
     */
    public function query(array $filters): Builder
    {
        $modelClass = ($filters['source'] ?? 'real') === 'ebmd' ? EbmdVehicle::class : Vehicle::class;

        $query = $modelClass::query()
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
                'stnk_ada',
                'tgl_stnk',
                'bpkb_ada',
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

        return $query->latest('id');
    }

    /**
     * Mendapatkan daftar judul kolom (headers) untuk Laporan Masa Berlaku Dokumen.
     *
     * @return array<string, string> Asosiasi nama kolom ke label Bahasa Indonesia
     */
    public function headers(): array
    {
        return [
            'no_polisi'      => 'Plat Nomor',
            'nomor_register' => 'Nomor Register',
            'merk'           => 'Merek',
            'tipe'           => 'Tipe',
            'stnk_ada'       => 'STNK Ada?',
            'tgl_stnk'       => 'Masa Berlaku STNK',
            'bpkb_ada'       => 'BPKB Ada?',
            'opd'            => 'Instansi Pengelola',
            'pemegang'       => 'Pemegang / Penanggung Jawab',
        ];
    }
}
