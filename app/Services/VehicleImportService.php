<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\EbmdVehicle;
use App\Imports\VehicleMultiSheetImport;
use App\Imports\VehiclePreviewImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class VehicleImportService
{
    protected $vehicleService;

    public function __construct(VehicleService $vehicleService)
    {
        $this->vehicleService = $vehicleService;
    }

    /**
     * Mengeksekusi impor data kendaraan menggunakan hasil pemetaan AI Smart Import.
     *
     * @param string $importToken
     * @param array $mapping
     * @param array $headers
     * @param int $headerRowIndex
     * @param string $targetTable
     * @param int $userId
     * @return void
     * @throws \Exception
     */
    public function executeSmartImport(string $importToken, array $mapping, array $headers, int $headerRowIndex, string $targetTable, int $userId): void
    {
        // Ambil metadata sesi impor dari cache
        $metadata = Cache::get($importToken);

        // Validasi sesi impor
        if (!$metadata) {
            throw new \Exception('Sesi impor tidak valid atau sudah kedaluwarsa. Silakan unggah ulang berkas.');
        }

        if ($metadata['user_id'] !== $userId) {
            throw new \Exception('Akses ditolak: Sesi impor ini milik pengguna lain.');
        }

        if (now()->timestamp > $metadata['expires_at']) {
            if (Storage::disk('local')->exists($metadata['file_path'])) {
                Storage::disk('local')->delete($metadata['file_path']);
            }
            Cache::forget($importToken);
            throw new \Exception('Sesi impor sudah kedaluwarsa. Silakan lakukan proses ulang.');
        }

        $filePath = $metadata['file_path'];
        $startRow = $headerRowIndex + 2; 

        // Pastikan file fisik temp benar-benar ada di storage
        if (!Storage::disk('local')->exists($filePath)) {
            Cache::forget($importToken);
            throw new \Exception('Berkas impor temporer tidak ditemukan pada penyimpanan server.');
        }

        $fullPath = Storage::disk('local')->path($filePath);
        $modelClass = $targetTable === 'ebmd' ? EbmdVehicle::class : Vehicle::class;

        // Eksekusi impor dengan pemetaan dinamis (mendukung multi-sheet)
        $modelClass::withoutEvents(function () use ($fullPath, $mapping, $headers, $startRow, $modelClass) {
            Excel::import(
                new VehicleMultiSheetImport($mapping, $headers, $startRow, $fullPath, $modelClass), 
                $fullPath
            );
        });

        // Bersihkan file sementara dan cache sesi setelah sukses eksekusi
        Storage::disk('local')->delete($filePath);
        Cache::forget($importToken);

        // Invalidation massal seluruh OPD (Dashboard stats)
        $this->vehicleService->invalidateDashboardStats(invalidateAllOpd: true);
    }

    /**
     * Mengeksekusi impor data kendaraan menggunakan template statis tradisional (Legacy).
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $targetTable
     * @return void
     */
    public function executeLegacyImport($file, string $targetTable): void
    {
        $fullPath = $file->getRealPath();
        $modelClass = $targetTable === 'ebmd' ? EbmdVehicle::class : Vehicle::class;

        // Gunakan default mapping (legacy template) secara otomatis di konstruktor VehicleImport
        $modelClass::withoutEvents(function () use ($fullPath, $modelClass) {
            Excel::import(
                new VehicleMultiSheetImport([], [], 4, $fullPath, $modelClass), 
                $fullPath
            );
        });

        // Invalidation massal seluruh OPD (Dashboard stats)
        $this->vehicleService->invalidateDashboardStats(invalidateAllOpd: true);
    }

    /**
     * Membaca file Excel yang diunggah dan mengembalikan preview header & data sampel beserta rekomendasi mapping.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $targetTable
     * @param int $userId
     * @return array
     * @throws \Exception
     */
    public function generateImportPreview($file, string $targetTable, int $userId): array
    {
        // Baca 15 baris pertama file Excel ke dalam array
        $import = new VehiclePreviewImport;
        
        $sheets = Excel::toArray($import, $file);
        
        $sheetNames = [];
        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $sheetNames = $reader->listWorksheetNames($file->getRealPath());
        } catch (\Exception $e) {
            // Abaikan error pembacaan jika format berkas tiruan tidak dikenali (terutama saat unit testing)
        }

        $headerRowIndex = 0;
        $headers = [];
        $rows = [];
        $activeSheetName = '';
        
        foreach ($sheets as $sheetIdx => $sheetRows) {
            if (empty($sheetRows)) continue;
            
            foreach ($sheetRows as $rowIndex => $row) {
                $nonEmptyCells = array_filter($row, function($cell) {
                    return !is_null($cell) && trim($cell) !== '';
                });

                if (count($nonEmptyCells) > 2) { // Minimal memiliki 3 kolom terisi
                    $headerRowIndex = $rowIndex;
                    $headers = array_map(function($header) {
                        return trim($header);
                    }, $row);
                    $rows = $sheetRows;
                    $activeSheetName = $sheetNames[$sheetIdx] ?? "Sheet " . ($sheetIdx + 1);
                    break 2; // Pecahkan pencarian segera setelah menemukan sheet valid pertama
                }
            }
        }

        if (empty($rows) || empty($headers)) {
            throw new \Exception('File Excel kosong atau tidak terdeteksi adanya kolom header di seluruh sheet.');
        }

        // Ambil maksimal 3 sampel baris data setelah header
        $samples = [];
        $sampleCount = 0;
        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            if ($sampleCount >= 3) break;
            
            $nonEmptyCells = array_filter($rows[$i], function($cell) {
                return !is_null($cell) && trim($cell) !== '';
            });
            
            if (!empty($nonEmptyCells)) {
                $samples[] = $rows[$i];
                $sampleCount++;
            }
        }

        // Kolom Target Database E-RANDIS yang diharapkan untuk dipetakan
        $targetColumns = [
            'no_polisi' => 'Nomor Polisi (Plat)',
            'nomor_register' => 'Nomor Register',
            'jenis' => 'Jenis Kendaraan (Roda 2 / Roda 4 / dll)',
            'merk' => 'Merk / Pabrikan',
            'tipe' => 'Tipe / Model',
            'no_mesin' => 'Nomor Mesin',
            'no_rangka' => 'Nomor Rangka',
            'tahun_pembuatan' => 'Tahun Pembuatan',
            'tgl_perolehan' => 'Tanggal Perolehan Aset',
            'nilai_perolehan' => 'Harga / Nilai Perolehan',
            'stnk_ada' => 'Status STNK (Ada/Tidak)',
            'bpkb_ada' => 'Status BPKB (Ada/Tidak)',
            'kondisi' => 'Kondisi Fisik Kendaraan',
            'pemegang' => 'Nama Pemegang / Penanggung Jawab',
            'keterangan' => 'Keterangan Tambahan',
            'opd' => 'Nama OPD / Instansi (Jika Superadmin)',
        ];
        
        if ($targetTable === 'ebmd') {
            unset($targetColumns['tahun_pembuatan']);
        }

        // Analisis Semantik AI untuk mendapatkan rekomendasi pemetaan kolom
        $suggestedMapping = $this->vehicleService->suggestColumnMapping($headers);

        // Simpan file sementara di storage
        $filePath = $file->store('temp_imports', 'local');
        
        // Generate token keamanan sesi impor temporer (Berlaku 30 Menit)
        $importToken = 'import_' . Str::random(40);
        
        Cache::put($importToken, [
            'file_path'  => $filePath,
            'user_id'    => $userId,
            'expires_at' => now()->addMinutes(30)->timestamp,
        ], now()->addMinutes(30));

        return [
            'success'           => true,
            'headers'           => $headers,
            'samples'           => $samples,
            'target_columns'    => $targetColumns,
            'suggested_mapping' => $suggestedMapping,
            'header_row_index'  => $headerRowIndex,
            'import_token'      => $importToken,
            'active_sheet_name' => $activeSheetName,
        ];
    }
}
