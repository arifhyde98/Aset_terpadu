<?php

namespace App\Exports;

use App\Models\Vehicle;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Class untuk Mengekspor Data Kendaraan ke Excel
 * 
 * Mengambil seluruh koleksi data kendaraan beserta relasinya dan memetakannya
 * ke kolom-kolom yang sesuai untuk file Excel hasil ekspor.
 */
class VehicleExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * Mengambil seluruh data kendaraan dari database untuk diekspor.
     * 
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Vehicle::with('vehicleType')->get();
    }

    /**
     * Menentukan header (judul kolom) untuk file Excel.
     * 
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Jenis Kendaraan',
            'Merk/Tipe',
            'Nomor Polisi',
            'Nomor Register',
            'Nomor Mesin',
            'Nomor Rangka',
            'Tgl Perolehan',
            'Nilai Perolehan',
            'STNK',
            'BPKB',
            'Kondisi Fisik',
            'Status Operasional',
            'Pemegang',
            'Keterangan',
            'OPD'
        ];
    }

    /**
     * Memetakan setiap baris model Vehicle menjadi array baris Excel.
     * 
     * @param Vehicle $vehicle
     * @return array<int, mixed>
     */
    public function map($vehicle): array
    {
        return [
            $vehicle->vehicleType->name ?? $vehicle->jenis,
            $vehicle->merk . ' ' . $vehicle->tipe,
            $vehicle->no_polisi,
            $vehicle->nomor_register,
            $vehicle->no_mesin,
            $vehicle->no_rangka,
            $vehicle->tgl_perolehan,
            $vehicle->nilai_perolehan,
            $vehicle->stnk_ada,
            $vehicle->bpkb_ada,
            $vehicle->kondisi,
            $vehicle->status,
            $vehicle->pemegang,
            $vehicle->keterangan,
            $vehicle->opd,
        ];
    }
}

