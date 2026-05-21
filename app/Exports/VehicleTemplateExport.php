<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Class untuk Membuat Template Import Excel
 * 
 * Digunakan untuk mengunduh format file Excel yang benar agar data dapat
 * diimport kembali ke sistem tanpa kesalahan format.
 */
class VehicleTemplateExport implements FromArray, WithHeadings, WithStyles
{
    /**
     * Menyediakan data contoh (dummy) untuk diletakkan di dalam template.
     * 
     * @return array
     */
    public function array(): array
    {
        // Memberikan 1 baris contoh data agar user paham cara mengisinya
        return [
            [
                'KENDARAAN DINAS RODA 4 (EMPAT)',
                'TOYOTA INNOVA ZENIX',
                'DN 1234 XY',
                'REG-1234567',
                '1TR-1234567',
                'MHFX-987654321',
                '12/31/2023',
                '450000000',
                'Ada',
                'Ada',
                'BAIK',
                'BUDI SANTOSO',
                'Kendaraan Operasional',
                'BAPENDA SULTENG'
            ]
        ];
    }

    /**
     * Menentukan struktur header multi-baris untuk template.
     * 
     * @return array<int, mixed>
     */
    public function headings(): array
    {
        return [
            [
                'TEMPLATE IMPORT DATA KENDARAAN E-RANDIS PHP'
            ],
            [
                '' // Spasi Baris 2
            ],
            [
                'Jenis Kendaraan',
                'Merk/Tipe',
                'Nomor Polisi',
                'Nomor Register',
                'Nomor Mesin',
                'Nomor Rangka',
                'Tanggal Perolehan (m/d/Y)',
                'Nilai Perolehan',
                'STNK (Ada/Tidak)',
                'BPKB (Ada/Tidak)',
                'Kondisi (B/RR/RB/Hilang)',
                'Pemegang',
                'Keterangan',
                'OPD / DINAS'
            ]
        ];
    }

    /**
     * Mengatur gaya tampilan (styling) file Excel, seperti font tebal dan warna latar.
     * 
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style untuk Judul di Baris 1
            1    => ['font' => ['bold' => true, 'size' => 14]],
            // Style untuk Header di Baris 3
            3    => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']]],
        ];
    }
}

