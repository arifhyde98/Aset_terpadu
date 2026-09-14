<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\HasReferencesToOtherSheets;

/**
 * Class untuk Membaca Preview Data Excel KIB C (Hanya 15 baris pertama).
 * Digunakan dalam fitur AI Smart Import untuk mendeteksi header secara dinamis.
 */
class BangunanPreviewImport implements ToArray, WithLimit, WithCalculatedFormulas, HasReferencesToOtherSheets
{
    public function array(array $array): array
    {
        return $array;
    }

    public function limit(): int
    {
        return 15;
    }
}
