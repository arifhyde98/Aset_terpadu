<?php

namespace App\Services\Erandis;

use App\Models\Vehicle;
use App\Models\EbmdVehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VehicleQueryService
{
    /**
     * Mengambil daftar kendaraan terpaginasi dengan filter, sorting, dan mapping data.
     *
     * @param array $filters
     * @return array
     */
    public function getPaginatedVehicles(array $filters): array
    {
        $sortBy = $filters['sort_by'] ?? null;
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $allowedSorts = ['no_polisi', 'jenis', 'merk', 'tahun_pembuatan', 'pemegang', 'kondisi', 'status'];

        $isEbmd = ($filters['tab'] ?? null) === 'ebmd';
        $modelClass = $isEbmd ? EbmdVehicle::class : Vehicle::class;

        $query = $modelClass::with(['user', 'vehicleType', 'opdRelation', 'subOpd']);

        if ($sortBy && in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        // Filter Pencarian Global
        if (!empty($filters['q'])) {
            $search = strtoupper(preg_replace('/\s+/', ' ', trim($filters['q'])));
            $query->where(function($q) use ($search) {
                $q->where('no_polisi', 'LIKE', "%{$search}%")
                  ->orWhere('nomor_register', 'LIKE', "%{$search}%")
                  ->orWhere('pemegang', 'LIKE', "%{$search}%")
                  ->orWhere('merk', 'LIKE', "%{$search}%")
                  ->orWhere('tipe', 'LIKE', "%{$search}%")
                  ->orWhere('no_mesin', 'LIKE', "%{$search}%")
                  ->orWhere('no_rangka', 'LIKE', "%{$search}%")
                  ->orWhere('opd', 'LIKE', "%{$search}%")
                  ->orWhereHas('opdRelation', function($sq) use ($search) {
                      $sq->where('nama', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('subOpd', function($sq) use ($search) {
                      $sq->where('nama', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Filter Berdasarkan Status Operasional
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter Berdasarkan Kondisi Fisik
        if (!empty($filters['kondisi'])) {
            $query->where('kondisi', $filters['kondisi']);
        }

        // Filter Berdasarkan Jenis Kendaraan
        if (!empty($filters['jenis'])) {
            $query->where(function($q) use ($filters) {
                $q->whereHas('vehicleType', function($sq) use ($filters) {
                    $sq->where('name', $filters['jenis']);
                })->orWhere('jenis', $filters['jenis']);
            });
        }

        // Filter Berdasarkan OPD
        if (!empty($filters['opd_id'])) {
            $query->where('opd_id', $filters['opd_id']);
        }

        // Filter Berdasarkan Sub-OPD (KPB)
        if (!empty($filters['sub_opd_id'])) {
            $query->where('sub_opd_id', $filters['sub_opd_id']);
        }

        $perPage = isset($filters['per_page']) && in_array((int)$filters['per_page'], [10, 25, 50, 100]) ? (int)$filters['per_page'] : 10;
        $vehicles = $query->paginate($perPage)->withQueryString();
        
        $statuses = $modelClass::getStatuses();
        $conditions = $modelClass::getConditions();

        $bpkbs = \App\Models\Elabel\ElabelBpkb::where('status', '!=', 'Dihapus')->get();
        $clean = fn($val) => preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string)$val)));

        $vehicleDataMap = $vehicles->getCollection()->keyBy('id')->map(function($v) use ($bpkbs, $clean) {
            $data = $v->only([
                'id', 'no_polisi', 'nomor_register', 'merk', 'tipe', 'jenis', 'opd_id', 'sub_opd_id', 'pemegang', 'status', 'kondisi',
                'vehicle_type_id', 'tahun_pembuatan', 'warna', 'stnk_ada', 'bpkb_ada', 
                'tgl_stnk', 'tgl_perolehan', 'nilai_perolehan', 'no_mesin', 'no_rangka', 
                'keterangan', 'foto_kendaraan'
            ]);
            
            // Gunakan nama OPD terbaru dari relasi untuk konsistensi Modal
            $data['opd'] = $v->opdRelation?->nama ?? $v->opd;
            $data['sub_opd_nama'] = $v->subOpd?->nama ?? null;

            // Cari BPKB yang cocok secara case-insensitive
            $matchedBpkbId = null;
            $vMesin = $clean($v->no_mesin);
            $vRangka = $clean($v->no_rangka);

            if (!empty($vMesin) && !empty($vRangka)) {
                foreach ($bpkbs as $b) {
                    if ($clean($b->no_mesin) === $vMesin && $clean($b->no_rangka) === $vRangka) {
                        $matchedBpkbId = $b->id;
                        break;
                    }
                }
            }

            $data['bpkb_id'] = $matchedBpkbId;
            
            return $data;
        });

        return [
            'vehicles' => $vehicles,
            'vehicleDataMap' => $vehicleDataMap,
            'statuses' => $statuses,
            'conditions' => $conditions,
            'isEbmd' => $isEbmd,
        ];
    }
}
