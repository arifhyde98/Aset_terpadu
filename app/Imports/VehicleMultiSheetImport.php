<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Wrapper Impor Multi-Sheet Dinamis untuk Data Kendaraan.
 * 
 * Mengidentifikasi seluruh sheet di dalam file Excel secara runtime dan
 * menerapkan logika VehicleImport (AI Smart Import) secara konsisten pada setiap sheet.
 */
class VehicleMultiSheetImport implements WithMultipleSheets
{
    /** @var array Pemetaan kolom database => indeks kolom Excel */
    private $mapping;

    /** @var array Header kolom Excel asli */
    private $headers;

    /** @var int Baris awal pembacaan data */
    private $startRow;

    /** @var string Path absolut ke file Excel */
    private $filePath;

    /** @var string Model tujuan import */
    private $modelClass;

    /**
     * Konstruktor wrapper multi-sheet.
     * 
     * @param array $mapping Pemetaan kolom dari UI
     * @param array $headers Daftar header asli Excel
     * @param int $startRow Baris mulai membaca data (1-based)
     * @param string $filePath Path fisik ke berkas Excel
     * @param string $modelClass Kelas model target
     */
    public function __construct(array $mapping = [], array $headers = [], int $startRow = 4, string $filePath = '', string $modelClass = \App\Models\Vehicle::class)
    {
        $this->mapping = $mapping;
        $this->headers = $headers;
        $this->startRow = $startRow;
        $this->filePath = $filePath;
        $this->modelClass = $modelClass;

        // Reset status memori bersama antar-sheet agar penomoran TANPA-PLAT berjalan bersih untuk impor baru
        VehicleImport::resetSharedState($this->modelClass);
    }

    /**
     * Mendaftarkan kelas importir untuk setiap sheet yang ditemukan di Excel.
     * 
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];

        try {
            // Jika file path terisi dan filenya ada, baca jumlah sheet secara dinamis
            if (!empty($this->filePath) && file_exists($this->filePath)) {
                $reader = IOFactory::createReaderForFile($this->filePath);
                $reader->setReadDataOnly(true);
                
                // listWorksheetNames membaca daftar nama sheet secara instant tanpa memuat data ke memori
                $worksheetNames = $reader->listWorksheetNames($this->filePath);

                foreach ($worksheetNames as $index => $name) {
                    $sheets[$index] = new VehicleImport($this->mapping, $this->headers, $this->startRow, $this->modelClass);
                }
            }
        } catch (\Exception $e) {
            // Fallback: Jika gagal menganalisis sheet, daftarkan 10 slot sheet default
            for ($i = 0; $i < 10; $i++) {
                $sheets[$i] = new VehicleImport($this->mapping, $this->headers, $this->startRow, $this->modelClass);
            }
        }

        // Jika tidak ada sheet yang terdaftar, pastikan minimal ada 1 sheet untuk diimpor
        if (empty($sheets)) {
            $sheets[0] = new VehicleImport($this->mapping, $this->headers, $this->startRow, $this->modelClass);
        }

        return $sheets;
    }
}
