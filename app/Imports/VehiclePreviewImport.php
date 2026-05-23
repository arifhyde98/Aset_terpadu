<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\HasReferencesToOtherSheets;

/**
 * Class untuk Membaca Preview Data Excel (Hanya 15 baris pertama)
 * 
 * Digunakan dalam fitur AI Smart Import untuk mendeteksi header secara dinamis.
 */
class VehiclePreviewImport implements ToArray, WithLimit, WithCalculatedFormulas, HasReferencesToOtherSheets
{
    /**
     * Memetakan baris Excel menjadi Array.
     * 
     * @param array $array
     * @return array
     */
    public function array(array $array)
    {
        return $array;
    }

    /**
     * Tentukan batas baris yang dibaca untuk kecepatan maksimal.
     * 
     * @return int
     */
    public function limit(): int
    {
        return 15; // Membaca 15 baris pertama untuk mendeteksi header secara konsisten dengan importer utama
    }
}
