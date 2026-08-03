<?php

namespace App\Imports;

use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Models\Opd;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\HasReferencesToOtherSheets;
use Carbon\Carbon;

/**
 * Class untuk Mengimport Data Kendaraan secara Dinamis (AI Smart Import)
 * 
 * Mendukung pemetaan kolom dinamis hasil analisis AI semantik.
 */
class VehicleImport implements ToModel, WithStartRow, WithBatchInserts, WithChunkReading, WithEvents, WithCalculatedFormulas, HasReferencesToOtherSheets
{
    /** @var array Shared cache memori untuk seluruh sheet (menghindari duplikasi plat antar sheet) */
    private static $sharedExistingPlates = null;

    /** @var array Shared cache memori untuk seluruh sheet (menghindari duplikasi nomor register antar sheet) */
    private static $sharedExistingRegisters = null;

    /** @var int Shared counter untuk pembuatan ID sementara kendaraan tanpa plat */
    private static $sharedRowCount = 0;

    /** @var array Cache memori untuk Master Data */
    private $typeCache = [];
    private $opdCache = [];

    /** @var array Pemetaan kolom database => indeks kolom Excel */
    private $columnIndexes = [];
    
    /** @var array Header kolom Excel asli */
    private $headers = [];

    /** @var array Pemetaan asli dari user */
    private $mapping = [];

    /** @var int Baris awal pembacaan data */
    private $startRow = 4;

    /** @var string Target model */
    private $modelClass;

    /**
     * Reset shared state untuk proses impor baru.
     */
    public static function resetSharedState()
    {
        static::$sharedExistingPlates = null;
        static::$sharedExistingRegisters = null;
        static::$sharedRowCount = 0;
    }

    /**
     * Konstruktor untuk Inisialisasi Pemetaan Dinamis.
     * 
     * @param array $mapping Peta dari user [target_db => excel_header]
     * @param array $headers Daftar header kolom dari file Excel
     * @param int $startRow Baris mulai membaca data (1-indexed)
     * @param string $modelClass Model tujuan
     */
    public function __construct(array $mapping = [], array $headers = [], int $startRow = 4, string $modelClass = \App\Models\Vehicle::class)
    {
        $this->modelClass = $modelClass;

        // Pre-load Master Data ke Memori untuk menghindari kueri N+1
        $this->typeCache = VehicleType::pluck('id', 'name')->toArray();
        $this->opdCache = Opd::pluck('id', 'nama')->toArray();
        
        // FIX (High Risk): Gunakan withoutGlobalScopes() agar bisa melihat plat secara GLOBAL.
        if (static::$sharedExistingPlates === null) {
            static::$sharedExistingPlates = $this->modelClass::withoutGlobalScopes()->pluck('no_polisi')->flip()->toArray();
        }

        if (static::$sharedExistingRegisters === null) {
            static::$sharedExistingRegisters = $this->modelClass::withoutGlobalScopes()
                ->whereNotNull('nomor_register')
                ->where('nomor_register', '!=', '')
                ->pluck('nomor_register')
                ->flip()
                ->toArray();
        }

        // Jika pemetaan kosong (mode fallback/legacy), gunakan pemetaan standar template E-RANDIS
        if (empty($mapping)) {
            $mapping = [
                'jenis'           => 'Jenis Kendaraan',
                'merk'            => 'Merk/Tipe',
                'no_polisi'       => 'Nomor Polisi',
                'nomor_register'  => 'Nomor Register',
                'no_mesin'        => 'Nomor Mesin',
                'no_rangka'       => 'Nomor Rangka',
                'tgl_perolehan'   => 'Tanggal Perolehan (m/d/Y)',
                'nilai_perolehan' => 'Nilai Perolehan',
                'stnk_ada'        => 'STNK (Ada/Tidak)',
                'bpkb_ada'        => 'BPKB (Ada/Tidak)',
                'kondisi'         => 'Kondisi (B/RR/RB/Hilang)',
                'pemegang'        => 'Pemegang',
                'keterangan'      => 'Keterangan',
                'opd'             => 'OPD / DINAS',
            ];
        }

        $this->mapping = $mapping;
        $this->headers = $headers;
        $this->startRow = $startRow;

        // Hubungkan nama kolom target DB dengan indeks kolom Excel-nya
        foreach ($mapping as $dbColumn => $excelHeader) {
            if (empty($excelHeader)) continue;
            $index = array_search($excelHeader, $headers);
            if ($index !== false) {
                $this->columnIndexes[$dbColumn] = $index;
            }
        }
    }

    /**
     * Menentukan baris awal dimulainya pembacaan data.
     */
    public function startRow(): int
    {
        return $this->startRow;
    }

    /**
     * Tentukan ukuran batch untuk insert sekaligus.
     */
    public function batchSize(): int
    {
        return 100;
    }

    /**
     * Tentukan ukuran chunk untuk pembacaan file besar.
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Helper untuk mengambil nilai cell berdasarkan kolom database secara dinamis.
     * 
     * @param array $row Baris data Excel
     * @param string $key Kolom target database
     * @param mixed $default Nilai default jika kosong
     * @return mixed
     */
    private function getVal(array $row, string $key, $default = null)
    {
        if (isset($this->columnIndexes[$key])) {
            $idx = $this->columnIndexes[$key];
            return isset($row[$idx]) ? $row[$idx] : $default;
        }
        return $default;
    }

    /**
     * Memetakan baris Excel menjadi model Vehicle secara dinamis.
     */
    public function model(array $row)
    {
        // Lewati jika baris kosong secara fisik
        if (empty($row)) {
            return null;
        }

        // Cek apakah seluruh sel dalam baris ini bernilai NULL atau kosong
        $nonEmptyCells = array_filter($row, function($cell) {
            return !is_null($cell) && trim($cell) !== '';
        });

        // Jika baris ini tidak memiliki cukup data terisi (minimal 2 kolom terisi), lewati.
        // Mencegah baris kosong ber-style di bawah data Excel agar tidak terimport sebagai kendaraan kosong.
        if (count($nonEmptyCells) < 2) {
            return null;
        }

        // Guard 3 (Header/Metadata Guard): Jika baris ini berisi judul kolom/header itu sendiri, lewati.
        // Sangat berguna untuk menyaring baris header sheet kedua atau sheet-sheet berikutnya secara instan.
        $rowValuesString = implode(' ', array_map('strtolower', array_filter($row)));
        if (str_contains($rowValuesString, 'nomor polisi') || str_contains($rowValuesString, 'no. polisi') || str_contains($rowValuesString, 'nomor mesin') || str_contains($rowValuesString, 'nomor rangka')) {
            return null;
        }

        // Ambil nilai-nilai kritikal (Revisi: 4 Kolom Krusial)
        $raw_no_polisi = $this->getVal($row, 'no_polisi');
        $raw_nomor_register = $this->getVal($row, 'nomor_register');
        $raw_merk = $this->getVal($row, 'merk');
        $raw_no_mesin = $this->getVal($row, 'no_mesin');
        $raw_pemegang = $this->getVal($row, 'pemegang');

        // Jika keempat kolom kritikal ini kosong semua secara bersamaan, dipastikan baris sampah/kosong. Lewati!
        if (empty($raw_no_polisi) && empty($raw_merk) && empty($raw_no_mesin) && empty($raw_pemegang)) {
            return null;
        }

        // 1. Identifikasi Nomor Polisi (Dinamis)
        $vehicleService = app(\App\Services\VehicleService::class);
        $no_polisi = $vehicleService->formatPlateNumber($raw_no_polisi);

        // ATURAN TEMPLATE: Jika plat kosong, buatkan identitas urut
        if (empty($no_polisi) || in_array($no_polisi, ['NOMOR POLISI', '-', '?'])) {
            static::$sharedRowCount++;
            $no_polisi = "TANPA-PLAT-" . str_pad(static::$sharedRowCount, 3, '0', STR_PAD_LEFT);
        }

        // Cek duplikat menggunakan Cache Memori Bersama
        if (isset(static::$sharedExistingPlates[$no_polisi])) {
            $original_no_polisi = $no_polisi;
            $i = 2;
            while (isset(static::$sharedExistingPlates[$no_polisi])) {
                $no_polisi = $original_no_polisi . " (" . $i++ . ")";
            }
        }

        // Tandai plat ini sebagai 'terpakai' dalam sesi ini agar baris berikutnya tidak bentrok
        static::$sharedExistingPlates[$no_polisi] = true;

        // 1b. Identifikasi Nomor Register
        $nomor_register = trim((string)$raw_nomor_register);
        if (empty($nomor_register) || in_array($nomor_register, ['-', '?', 'null', 'NULL'])) {
            $nomor_register = null;
        } else {
            // Auto-Rename untuk Nomor Register ganda
            if (isset(static::$sharedExistingRegisters[$nomor_register])) {
                $original_nomor_register = $nomor_register;
                $i = 2;
                while (isset(static::$sharedExistingRegisters[$nomor_register])) {
                    $nomor_register = $original_nomor_register . " (" . $i++ . ")";
                }
            }
            static::$sharedExistingRegisters[$nomor_register] = true;
        }

        // 2. Proses Jenis Kendaraan menggunakan Cache Memori (Dinamis)
        $jenisName = trim($this->getVal($row, 'jenis', 'Lainnya'));
        if (empty($jenisName) || $jenisName === '-') {
            // Fallback ke kolom merk jika kolom jenis tidak diisi/tidak terdeteksi
            $jenisName = trim($this->getVal($row, 'merk', 'Lainnya'));
            if (empty($jenisName) || $jenisName === '-') {
                $jenisName = 'Lainnya';
            }
        }
        
        if (!isset($this->typeCache[$jenisName])) {
            $vt = VehicleType::firstOrCreate(
                ['name' => $jenisName],
                ['description' => 'Otomatis dibuat saat AI Smart Import Excel']
            );
            $this->typeCache[$jenisName] = $vt->id;
        }
        $typeId = $this->typeCache[$jenisName];

        // 3. Proses OPD menggunakan Cache Memori (Dinamis)
        $user = auth()->user();
        if ($user && $user->role === \App\Enums\UserRole::OPD) {
            if (empty($user->opd_id)) {
                throw new \Exception('Akun OPD belum terhubung ke instansi. Impor dibatalkan.');
            }
            $opdName = $user->opd?->nama ?? 'INSTANSI TIDAK DIKENAL';
            $opdId = $user->opd_id;
        } else {
            $opdName = trim($this->getVal($row, 'opd', 'BELUM DIKETAHUI'));
            if (empty($opdName) || in_array($opdName, ['-', '?'])) {
                $opdName = 'BELUM DIKETAHUI';
            }
            $opdName = strtoupper($opdName);

            if (!isset($this->opdCache[$opdName])) {
                $newOpd = Opd::firstOrCreate(
                    ['nama' => $opdName],
                    ['singkatan' => null, 'alamat' => null]
                );
                $this->opdCache[$opdName] = $newOpd->id;
            }
            $opdId = $this->opdCache[$opdName];
        }

        // 4. Persiapkan Data Dinamis
        $tglPerolehan = $this->transformDate($this->getVal($row, 'tgl_perolehan'));
        
        // Baca tahun pembuatan
        $rawTahun = $this->getVal($row, 'tahun_pembuatan');
        $tahunPembuatan = is_numeric($rawTahun) ? (int) $rawTahun : ($tglPerolehan ? \Carbon\Carbon::parse($tglPerolehan)->year : null);
        
        $kondisi = \App\Enums\VehicleCondition::fromImport($this->getVal($row, 'kondisi'));

        // Smart Extraction untuk Merk & Tipe (Pecah kata pertama sebagai Merk, sisanya sebagai Model/Tipe)
        $rawMerk = trim($this->getVal($row, 'merk', '-'));
        $rawTipe = trim($this->getVal($row, 'tipe', '-'));

        $finalMerk = $rawMerk;
        $finalTipe = $rawTipe;

        // Jika merk dan tipe dipetakan ke kolom gabungan yang sama (misal "Merk/Jenis" di Excel) atau salah satunya kosong
        if ($rawMerk === $rawTipe || empty($rawTipe) || $rawTipe === '-') {
            $words = explode(' ', $rawMerk);
            if (count($words) > 1) {
                $finalMerk = $words[0]; // Kata pertama (contoh: HONDA, TOYOTA, YAMAHA)
                $finalTipe = implode(' ', array_slice($words, 1)); // Sisanya (contoh: Scoopy, AVANSA (Mobil Penumpang))
            } else {
                $finalMerk = $rawMerk;
                $finalTipe = '-';
            }
        }

        return new $this->modelClass([
            'jenis'           => $jenisName,
            'vehicle_type_id' => $typeId,
            'merk'            => $finalMerk,
            'tipe'            => $finalTipe,
            'no_polisi'       => $no_polisi,
            'nomor_register'  => $nomor_register,
            'user_id'         => $user?->id,
            'no_mesin'        => $this->getVal($row, 'no_mesin'),
            'no_rangka'       => $this->getVal($row, 'no_rangka'),
            'tahun_pembuatan' => $tahunPembuatan,
            'tgl_perolehan'   => $tglPerolehan,
            'nilai_perolehan' => $this->transformCurrency($this->getVal($row, 'nilai_perolehan', 0)),
            'stnk_ada'        => $this->normalizeDocumentStatus($this->getVal($row, 'stnk_ada', 'Tidak')),
            'bpkb_ada'        => $this->normalizeDocumentStatus($this->getVal($row, 'bpkb_ada', 'Tidak')),
            'kondisi'         => $kondisi->value,
            'status'          => $kondisi->toDefaultStatus()->value,
            'pemegang'        => $this->getVal($row, 'pemegang', '-'),
            'keterangan'      => $this->getVal($row, 'keterangan'),
            'opd'             => $opdName,
            'opd_id'          => $opdId,
        ]);
    }

    /**
     * Helper untuk memproses format tanggal dari Excel (Serial) atau String.
     * 
     * @param mixed $value
     * @return \Illuminate\Support\Carbon|null
     */
    private function transformDate($value)
    {
        if (empty($value)) return null;
        
        try {
            if (is_numeric($value)) {
                if ($value > 1900 && $value < 2100) {
                    return Carbon::createFromDate($value, 1, 1)->startOfDay();
                }
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
            }

            if (str_contains($value, '/')) {
                try {
                    return Carbon::createFromFormat('d/m/Y', $value)->startOfDay();
                } catch (\Exception $e) {
                    return Carbon::createFromFormat('m/d/Y', $value)->startOfDay();
                }
            }

            return Carbon::parse($value)->startOfDay();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Helper untuk membersihkan dan mengonversi format mata uang ke Float.
     * 
     * @param mixed $value
     * @return float
     */
    private function transformCurrency($value)
    {
        if (empty($value)) return 0;
        if (is_numeric($value)) return (float) $value;

        $clean = preg_replace('/[^0-9,.]/', '', $value);
        
        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif (str_contains($clean, ',')) {
            $clean = str_replace(',', '.', $clean);
        }

        return (float) $clean;
    }

    /**
     * Helper untuk menormalisasi nilai dokumen ke 'Ada' atau 'Tidak'.
     * 
     * @param mixed $value
     * @return string
     */
    private function normalizeDocumentStatus($value): string
    {
        if (empty($value)) {
            return 'Tidak';
        }

        $clean = strtolower(trim($value));
        
        $positivePatterns = ['ada', 'ya', 'y', 'yes', 'lengkap', 'true', '1'];
        if (in_array($clean, $positivePatterns)) {
            return 'Ada';
        }

        return 'Tidak';
    }

    /**
     * Registrasi Event Maatwebsite Excel untuk penyesuaian kolom per sheet secara dinamis.
     * 
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function(BeforeSheet $event) {
                $worksheet = $event->getSheet()->getDelegate();
                
                // Cari baris header secara dinamis (mencari baris pertama dengan 3+ kolom terisi)
                $headerRowIndex = 0;
                $sheetHeaders = [];
                
                // Ambil 15 baris pertama untuk mencari baris header
                foreach ($worksheet->getRowIterator(1, 15) as $row) {
                    $rowIndex = $row->getRowIndex();
                    $cells = [];
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    foreach ($cellIterator as $cell) {
                        $cells[] = trim($cell->getValue() ?? '');
                    }
                    
                    $nonEmptyCells = array_filter($cells);
                    if (count($nonEmptyCells) > 2) {
                        $headerRowIndex = $rowIndex;
                        $sheetHeaders = $cells;
                        break;
                    }
                }
                
                // Petakan $this->columnIndexes khusus untuk sheet ini agar kebal perubahan urutan kolom!
                if (!empty($sheetHeaders) && !empty($this->mapping)) {
                    $this->columnIndexes = [];
                    foreach ($this->mapping as $dbColumn => $excelHeader) {
                        if (empty($excelHeader)) continue;
                        
                        // Cari kecocokan case-insensitive
                        $index = false;
                        foreach ($sheetHeaders as $idx => $sh) {
                            if (strtolower(trim($sh)) === strtolower(trim($excelHeader))) {
                                $index = $idx;
                                break;
                            }
                        }
                        
                        if ($index !== false) {
                            $this->columnIndexes[$dbColumn] = $index;
                        }
                    }
                }
                
                // Sesuaikan startRow secara dinamis untuk sheet ini jika terdeteksi posisi header yang berbeda
                if ($headerRowIndex > 0) {
                    $this->startRow = $headerRowIndex + 1;
                }
            }
        ];
    }
}
