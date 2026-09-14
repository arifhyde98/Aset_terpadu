<?php

namespace App\Services\Bangunan;

use App\Imports\BangunanMultiSheetImport;
use App\Imports\BangunanPreviewImport;
use App\Models\Bangunan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BangunanImportService
{
    protected BangunanService $bangunanService;

    public function __construct(BangunanService $bangunanService)
    {
        $this->bangunanService = $bangunanService;
    }

    /**
     * Membaca file Excel dan mengembalikan pratinjau header, 3 sampel data, dan rekomendasi pemetaan AI.
     */
    public function generateImportPreview(UploadedFile $file, User $user): array
    {
        $import = new BangunanPreviewImport;
        $sheets = Excel::toArray($import, $file);

        $sheetNames = [];
        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $sheetNames = $reader->listWorksheetNames($file->getRealPath());
        } catch (\Throwable $e) {
            // Abaikan error format
        }

        $headerRowIndex = 0;
        $headers = [];
        $rows = [];
        $activeSheetName = '';

        foreach ($sheets as $sheetIdx => $sheetRows) {
            if (empty($sheetRows)) continue;

            foreach ($sheetRows as $rowIndex => $row) {
                $nonEmptyCells = array_filter($row, function ($cell) {
                    return !is_null($cell) && trim((string) $cell) !== '';
                });

                if (count($nonEmptyCells) >= 3) {
                    $headerRowIndex = $rowIndex;
                    $headers = array_map(function ($h) {
                        return trim((string) $h);
                    }, $row);
                    $rows = $sheetRows;
                    $activeSheetName = $sheetNames[$sheetIdx] ?? "Sheet " . ($sheetIdx + 1);
                    break 2;
                }
            }
        }

        if (empty($rows) || empty($headers)) {
            throw new \Exception('File Excel kosong atau baris header tidak dapat terdeteksi.');
        }

        // Ambil maksimal 3 sampel baris data
        $samples = [];
        $sampleCount = 0;
        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            if ($sampleCount >= 3) break;

            $nonEmptyCells = array_filter($rows[$i], function ($cell) {
                return !is_null($cell) && trim((string) $cell) !== '';
            });

            if (!empty($nonEmptyCells)) {
                $samples[] = $rows[$i];
                $sampleCount++;
            }
        }

        // Analisis Semantik untuk rekomendasi pemetaan kolom
        $suggestedMapping = $this->bangunanService->suggestColumnMapping($headers);
        $targetColumns = $this->bangunanService->getTargetColumns();

        // Simpan file sementara (30 Menit)
        $filePath = $file->store('temp_imports', 'local');
        $importToken = 'import_bng_' . Str::random(40);

        Cache::put($importToken, [
            'file_path'  => $filePath,
            'user_id'    => $user->id,
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

    /**
     * Eksekusi impor massal menggunakan pemetaan dinamis yang telah diverifikasi user.
     */
    public function executeSmartImport(string $importToken, array $mapping, array $headers, int $headerRowIndex, User $user): void
    {
        $metadata = Cache::get($importToken);

        if (!$metadata) {
            throw new \Exception('Sesi impor tidak valid atau sudah kedaluwarsa. Silakan unggah kembali berkas.');
        }

        if ($metadata['user_id'] !== $user->id) {
            throw new \Exception('Akses ditolak: Sesi impor ini milik pengguna lain.');
        }

        $filePath = $metadata['file_path'];
        if (!Storage::disk('local')->exists($filePath)) {
            Cache::forget($importToken);
            throw new \Exception('Berkas temporer di server tidak ditemukan.');
        }

        $fullPath = Storage::disk('local')->path($filePath);
        $startRow = $headerRowIndex + 2; // Baris setelah header

        Bangunan::withoutEvents(function () use ($fullPath, $mapping, $headers, $startRow, $user) {
            Excel::import(
                new BangunanMultiSheetImport(
                    $mapping,
                    $headers,
                    $startRow,
                    $user->id,
                    $user->opd_id,
                    $user->role
                ),
                $fullPath
            );
        });

        // Bersihkan berkas dan token
        Storage::disk('local')->delete($filePath);
        Cache::forget($importToken);

        // Invalidate cache statistik bangunan
        Cache::forget('bangunan.stats.global');
        if ($user->opd_id) {
            Cache::forget("bangunan.stats.opd.{$user->opd_id}");
        }
    }
}
