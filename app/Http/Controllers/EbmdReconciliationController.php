<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\AsetTanah;
use App\Models\Bangunan;
use App\Models\EbmdVehicle;
use App\Models\IntegrationAuditLog;
use App\Models\Opd;
use App\Models\Vehicle;
use App\Services\Erandis\VehicleImportService;
use App\Services\Erandis\VehicleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EbmdReconciliationController extends Controller implements HasMiddleware
{
    protected VehicleImportService $vehicleImportService;
    protected VehicleService $vehicleService;

    public function __construct(VehicleImportService $vehicleImportService, VehicleService $vehicleService)
    {
        $this->vehicleImportService = $vehicleImportService;
        $this->vehicleService = $vehicleService;
    }

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:superadmin,admin'),
        ];
    }

    /**
     * Tampilan utama Halaman Rekonsiliasi Data e-BMD Terpadu Lintas Seluruh Modul.
     */
    public function index(Request $request)
    {
        $activeCategory = $request->query('category', 'tanah');

        $categories = [
            'tanah' => [
                'name'  => 'Aset Tanah (SIPAT)',
                'icon'  => 'bi-geo-alt',
                'model' => AsetTanah::class,
            ],
            'bangunan' => [
                'name'  => 'Gedung & Bangunan (SIPAT)',
                'icon'  => 'bi-building',
                'model' => Bangunan::class,
            ],
            'vehicle' => [
                'name'  => 'Kendaraan Dinas Real (eRANDIS)',
                'icon'  => 'bi-car-front',
                'model' => Vehicle::class,
            ],
            'ebmd_vehicle' => [
                'name'  => 'Master e-BMD Kendaraan',
                'icon'  => 'bi-database',
                'model' => EbmdVehicle::class,
            ],
        ];

        $opds = Opd::orderBy('nama', 'asc')->get();
        $userOpdId = auth()->user()?->opd_id;
        $isSuperAdmin = (auth()->user()?->role === 'superadmin' || auth()->user()?->role === \App\Enums\UserRole::SUPERADMIN);

        return view('rekon-ebmd.index', compact('activeCategory', 'categories', 'opds', 'userOpdId', 'isSuperAdmin'));
    }

    /**
     * Membaca file Excel yang diunggah dan mengembalikan daftar kolom & pemetaan NIBAR sesuai kategori aset.
     */
    public function uploadPreview(Request $request): JsonResponse
    {
        $request->validate([
            'file'          => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
            'asset_category'=> 'required|in:tanah,bangunan,vehicle,ebmd_vehicle',
        ], [
            'file.required' => 'File Excel wajib dipilih.',
            'file.mimes'    => 'Format file harus berupa .xlsx, .xls, atau .csv.',
            'file.max'      => 'Ukuran file maksimal adalah 10MB.',
        ]);

        try {
            $category = $request->input('asset_category');
            $targetTable = ($category === 'ebmd_vehicle') ? 'ebmd' : 'real';

            $preview = $this->vehicleImportService->generateImportPreview(
                $request->file('file'),
                $targetTable,
                auth()->id()
            );

            // Konfigurasi kolom & matching keys (NIBAR adalah kunci mutlak)
            $config = $this->getCategoryConfig($category);
            
            // Rekomendasi mapping cerdas spesifik kategori
            $suggestedMapping = $this->suggestCategoryColumnMapping($preview['headers'] ?? [], $category);

            return response()->json(array_merge($preview, [
                'updatable_columns' => $config['updatable_columns'],
                'matching_keys'     => $config['matching_keys'],
                'suggested_mapping' => $suggestedMapping,
                'asset_category'    => $category,
                'nibar_column'      => $this->getNibarColumn($category),
            ]));
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca berkas Excel: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menganalisis perbedaan data (Diff Preview) berbasis Business Key NIBAR.
     * Logika: SIPAT.NIBAR = eBMD.NIBAR
     * Status: IDENTICAL, CHANGED, ONLY_IN_EBMD, ONLY_IN_SIPAT, DUPLICATE_KEY, INVALID
     */
    public function diffPreview(Request $request): JsonResponse
    {
        $request->validate([
            'import_token'     => 'required|string',
            'selected_columns' => 'required|array|min:1',
            'asset_category'   => 'required|in:tanah,bangunan,vehicle,ebmd_vehicle',
            'mapping'          => 'required|array',
            'opd_id'           => 'nullable|integer|exists:opds,id',
        ]);

        try {
            $tokenData = Cache::get($request->input('import_token'));
            if (!$tokenData || !Storage::disk('local')->exists($tokenData['file_path'])) {
                return response()->json(['success' => false, 'message' => 'Sesi impor tidak valid atau berkas temporer telah kadaluwarsa.'], 400);
            }

            if (isset($tokenData['user_id']) && $tokenData['user_id'] !== auth()->id() && auth()->user()?->role !== 'superadmin') {
                return response()->json(['success' => false, 'message' => 'Akses ditolak: Sesi impor ini milik pengguna lain.'], 403);
            }

            $fullPath = Storage::disk('local')->path($tokenData['file_path']);
            $category = $request->input('asset_category');
            $modelClass = $this->getModelClass($category);
            $nibarDbCol = $this->getNibarColumn($category);
            $selectedColumns = $request->input('selected_columns');
            $mapping = $request->input('mapping');
            $headerRowIndex = (int) $request->input('header_row_index', 0);

            $opdId = $request->input('opd_id');
            $opdName = null;
            if (!empty($opdId)) {
                $opdObj = Opd::find($opdId);
                $opdName = $opdObj ? ($opdObj->nama . ($opdObj->singkatan ? ' (' . $opdObj->singkatan . ')' : '')) : null;
            }

            // NIBAR index di file Excel
            $nibarExcelIdx = $mapping['nibar'] ?? $mapping[$nibarDbCol] ?? null;
            if ($nibarExcelIdx === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kolom NIBAR (Nomor Induk Barang) wajib dipetakan pada Langkah 2.',
                ], 422);
            }

            $reader = IOFactory::createReaderForFile($fullPath);
            $spreadsheet = $reader->load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $startRow = $headerRowIndex + 1;

            // 1. Kumpulkan semua NIBAR dari e-BMD untuk deteksi duplikat & validasi
            $excelRows = [];
            $excelNibarCounts = [];
            $totalEbmd = 0;

            for ($i = $startRow; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty(array_filter($row, fn($v) => !is_null($v) && trim((string)$v) !== ''))) {
                    continue;
                }

                $totalEbmd++;
                $rawNibar = isset($row[$nibarExcelIdx]) ? trim((string)$row[$nibarExcelIdx]) : '';
                
                $excelRows[] = [
                    'row_num'   => $i + 1,
                    'raw_nibar' => $rawNibar,
                    'row_data'  => $row,
                ];

                if ($rawNibar !== '' && $rawNibar !== '-') {
                    $excelNibarCounts[$rawNibar] = ($excelNibarCounts[$rawNibar] ?? 0) + 1;
                }
            }

            // 2. Kumpulkan seluruh data SIPAT secara global dan per OPD untuk active category
            $allSipatRecords = $modelClass::withoutGlobalScopes()->get();
            $allSipatByNibar = [];
            $scopedSipatByNibar = [];

            foreach ($allSipatRecords as $rec) {
                $recNibar = trim((string)($rec->$nibarDbCol ?? ''));
                if ($recNibar !== '') {
                    $allSipatByNibar[$recNibar][] = $rec;
                    if (!empty($opdId) && (int)($rec->opd_id ?? 0) === (int)$opdId) {
                        $scopedSipatByNibar[$recNibar][] = $rec;
                    }
                }
            }

            $activeSipatByNibar = !empty($opdId) ? $scopedSipatByNibar : $allSipatByNibar;

            // 3. Proses Analisis Matching e-BMD -> SIPAT
            $reconciliationItems = [];
            $columnCounts = [];
            $seenEbmdNibars = [];

            $identicalCount = 0;
            $changedCount = 0;
            $otherOpdCount = 0;
            $notFoundSipatCount = 0;
            $conflictCount = 0;
            $duplicateCount = 0;
            $invalidCount = 0;

            foreach ($excelRows as $eRow) {
                $rowNum = $eRow['row_num'];
                $nibar = $eRow['raw_nibar'];
                $row = $eRow['row_data'];

                // VALIDASI NIBAR KOSONG / NULL
                if ($nibar === '' || $nibar === '-' || is_null($nibar)) {
                    $invalidCount++;
                    $reconciliationItems[] = [
                        'row_num'          => $rowNum,
                        'nibar'            => '(KOSONG)',
                        'name'             => $this->extractRowName($row, $mapping, $category),
                        'status'           => 'INVALID',
                        'status_label'     => 'Invalid / NIBAR Kosong',
                        'status_badge'     => 'danger',
                        'opd'              => '-',
                        'sub_opd'          => '-',
                        'changed_columns'  => [],
                        'all_columns_diff' => [],
                        'notes'            => 'Baris e-BMD tidak memiliki NIBAR valid.',
                    ];
                    continue;
                }

                // VALIDASI NIBAR DUPLIKAT DI FILE e-BMD
                if (($excelNibarCounts[$nibar] ?? 0) > 1) {
                    $duplicateCount++;
                    $reconciliationItems[] = [
                        'row_num'          => $rowNum,
                        'nibar'            => $nibar,
                        'name'             => $this->extractRowName($row, $mapping, $category),
                        'status'           => 'DUPLICATE_KEY',
                        'status_label'     => 'Duplikat di e-BMD',
                        'status_badge'     => 'warning',
                        'opd'              => '-',
                        'sub_opd'          => '-',
                        'changed_columns'  => [],
                        'all_columns_diff' => [],
                        'notes'            => "NIBAR [{$nibar}] muncul {$excelNibarCounts[$nibar]} kali di berkas Excel e-BMD.",
                    ];
                    continue;
                }

                // VALIDASI NIBAR DUPLIKAT DI OPD REKONSILIASI TARGET
                if (isset($activeSipatByNibar[$nibar]) && count($activeSipatByNibar[$nibar]) > 1) {
                    $duplicateCount++;
                    $sipatDuplicatesCount = count($activeSipatByNibar[$nibar]);
                    $existingModel = $activeSipatByNibar[$nibar][0];
                    $nameAttr = $this->getModelDisplayName($existingModel);

                    $reconciliationItems[] = [
                        'row_num'          => $rowNum,
                        'nibar'            => $nibar,
                        'name'             => $nameAttr,
                        'status'           => 'DUPLICATE_KEY',
                        'status_label'     => 'Duplikat di SIPAT',
                        'status_badge'     => 'warning',
                        'opd'              => $this->getModelOpdName($existingModel),
                        'sub_opd'          => $this->getModelSubOpdName($existingModel),
                        'changed_columns'  => [],
                        'all_columns_diff' => [],
                        'notes'            => "NIBAR [{$nibar}] terdaftar {$sipatDuplicatesCount} kali di database SIPAT (Perlu Review Manual).",
                    ];
                    continue;
                }

                // TAHAP 1: NIBAR DITEMUKAN SECARA UNIK DI OPD AKTIF
                if (isset($activeSipatByNibar[$nibar]) && count($activeSipatByNibar[$nibar]) === 1) {
                    $seenEbmdNibars[$nibar] = true;
                    $existingModel = $activeSipatByNibar[$nibar][0];
                    $nameAttr = $this->getModelDisplayName($existingModel);
                    $opdInfo = $this->getModelOpdName($existingModel);
                    $subOpdInfo = $this->getModelSubOpdName($existingModel);

                    $rowDiffs = [];
                    $allColumnsDiff = [];
                    $hasDiff = false;

                    foreach ($selectedColumns as $col) {
                        if (!isset($mapping[$col])) continue;
                        $excelColIdx = $mapping[$col];
                        if (!isset($row[$excelColIdx])) continue;

                        $rawNewVal = trim((string)$row[$excelColIdx]);
                        $rawOldVal = trim((string)($existingModel->$col ?? ''));

                        $colConfig = $this->compareColumnValue($col, $rawOldVal, $rawNewVal);

                        $allColumnsDiff[] = [
                            'column'   => $col,
                            'label'    => $this->getColumnLabel($category, $col),
                            'old'      => $colConfig['formatted_old'],
                            'new'      => $colConfig['formatted_new'],
                            'is_diff'  => $colConfig['is_diff'],
                            'status'   => $colConfig['is_diff'] ? 'BERUBAH' : 'IDENTIK',
                        ];

                        if ($colConfig['is_diff']) {
                            $hasDiff = true;
                            $rowDiffs[] = $allColumnsDiff[count($allColumnsDiff) - 1];
                            $columnCounts[$col] = ($columnCounts[$col] ?? 0) + 1;
                        }
                    }

                    if ($hasDiff) {
                        $changedCount++;
                        $reconciliationItems[] = [
                            'row_num'          => $rowNum,
                            'nibar'            => $nibar,
                            'name'             => $nameAttr,
                            'status'           => 'CHANGED',
                            'status_label'     => 'Berubah',
                            'status_badge'     => 'warning',
                            'opd'              => $opdInfo,
                            'sub_opd'          => $subOpdInfo,
                            'changed_columns'  => $rowDiffs,
                            'all_columns_diff' => $allColumnsDiff,
                            'notes'            => count($rowDiffs) . ' atribut mengalami perubahan.',
                        ];
                    } else {
                        $identicalCount++;
                        $reconciliationItems[] = [
                            'row_num'          => $rowNum,
                            'nibar'            => $nibar,
                            'name'             => $nameAttr,
                            'status'           => 'IDENTICAL',
                            'status_label'     => 'Identik',
                            'status_badge'     => 'success',
                            'opd'              => $opdInfo,
                            'sub_opd'          => $subOpdInfo,
                            'changed_columns'  => [],
                            'all_columns_diff' => $allColumnsDiff,
                            'notes'            => 'Seluruh kolom terpilih identik dengan database.',
                        ];
                    }
                } else {
                    // TAHAP 2: JIKA TIDAK DITEMUKAN DI OPD AKTIF, CARI SECARA GLOBAL KE SELURUH DATA SIPAT
                    if (!empty($opdId)) {
                        $globalMatches = $allSipatByNibar[$nibar] ?? [];

                        if (count($globalMatches) === 1) {
                            // Ditemukan tepat di 1 OPD lain
                            $otherOpdCount++;
                            $otherModel = $globalMatches[0];
                            $foundOpdName = $this->getModelOpdName($otherModel);

                            $reconciliationItems[] = [
                                'row_num'          => $rowNum,
                                'nibar'            => $nibar,
                                'name'             => $this->getModelDisplayName($otherModel),
                                'status'           => 'EXISTS_OTHER_OPD',
                                'status_label'     => 'Terdaftar di OPD Lain',
                                'status_badge'     => 'purple',
                                'opd'              => $foundOpdName,
                                'sub_opd'          => $this->getModelSubOpdName($otherModel),
                                'changed_columns'  => [],
                                'all_columns_diff' => [],
                                'notes'            => "NIBAR tidak ditemukan pada {$opdName}, tetapi terdaftar pada {$foundOpdName} (ID: #{$otherModel->getKey()}). Data perlu diverifikasi/mutasi.",
                            ];
                        } elseif (count($globalMatches) > 1) {
                            // Ditemukan di lebih dari 1 OPD (Konflik Global)
                            $conflictCount++;
                            $opdListStr = collect($globalMatches)->map(fn($m) => $this->getModelOpdName($m))->unique()->implode(', ');

                            $reconciliationItems[] = [
                                'row_num'          => $rowNum,
                                'nibar'            => $nibar,
                                'name'             => $this->getModelDisplayName($globalMatches[0]),
                                'status'           => 'GLOBAL_NIBAR_CONFLICT',
                                'status_label'     => 'Konflik NIBAR Antar OPD',
                                'status_badge'     => 'danger',
                                'opd'              => $opdListStr,
                                'sub_opd'          => '-',
                                'changed_columns'  => [],
                                'all_columns_diff' => [],
                                'notes'            => "NIBAR ditemukan di " . count($globalMatches) . " OPD di SIPAT: [{$opdListStr}]. Terdeteksi konflik integritas data.",
                            ];
                        } else {
                            // Tidak ditemukan di OPD aktif dan tidak ditemukan di seluruh SIPAT
                            $notFoundSipatCount++;
                            $rowName = $this->extractRowName($row, $mapping, $category);

                            $reconciliationItems[] = [
                                'row_num'          => $rowNum,
                                'nibar'            => $nibar,
                                'name'             => $rowName,
                                'status'           => 'ONLY_IN_EBMD',
                                'status_label'     => 'Tidak Ditemukan di SIPAT',
                                'status_badge'     => 'info',
                                'opd'              => '-',
                                'sub_opd'          => '-',
                                'changed_columns'  => [],
                                'all_columns_diff' => [],
                                'notes'            => 'NIBAR tidak ditemukan di seluruh database SIPAT (Kandidat Aset Baru / Perlu Verifikasi).',
                            ];
                        }
                    } else {
                        // Mode Seluruh OPD: NIBAR tidak ditemukan di seluruh database SIPAT
                        $notFoundSipatCount++;
                        $rowName = $this->extractRowName($row, $mapping, $category);

                        $reconciliationItems[] = [
                            'row_num'          => $rowNum,
                            'nibar'            => $nibar,
                            'name'             => $rowName,
                            'status'           => 'ONLY_IN_EBMD',
                            'status_label'     => 'Tidak Ditemukan di SIPAT',
                            'status_badge'     => 'info',
                            'opd'              => '-',
                            'sub_opd'          => '-',
                            'changed_columns'  => [],
                            'all_columns_diff' => [],
                            'notes'            => 'NIBAR tidak ditemukan di database SIPAT (Kandidat Aset Baru / Perlu Verifikasi).',
                        ];
                    }
                }
            }

            // 4. Deteksi Aset Dua Arah (ONLY_IN_SIPAT - Ada di SIPAT tetapi tidak ada di e-BMD)
            $onlyInSipatCount = 0;
            foreach ($activeSipatByNibar as $sNibar => $records) {
                if (!isset($seenEbmdNibars[$sNibar]) && !isset($excelNibarCounts[$sNibar])) {
                    $onlyInSipatCount += count($records);
                    $firstRec = $records[0];
                    $reconciliationItems[] = [
                        'row_num'          => '-',
                        'nibar'            => $sNibar,
                        'name'             => $this->getModelDisplayName($firstRec),
                        'status'           => 'ONLY_IN_SIPAT',
                        'status_label'     => 'Hanya di SIPAT',
                        'status_badge'     => 'secondary',
                        'opd'              => $this->getModelOpdName($firstRec),
                        'sub_opd'          => $this->getModelSubOpdName($firstRec),
                        'changed_columns'  => [],
                        'all_columns_diff' => [],
                        'notes'            => 'Aset tercatat di SIPAT namun tidak ada dalam file e-BMD ini.',
                    ];
                }
            }

            $nibarMatched = $identicalCount + $changedCount;

            $config = $this->getCategoryConfig($category);
            $fieldBreakdown = [];
            foreach ($columnCounts as $col => $count) {
                $fieldBreakdown[] = [
                    'column' => $col,
                    'label'  => $config['updatable_columns'][$col] ?? $col,
                    'count'  => $count,
                ];
            }

            // Simpan hasil komparasi ke Cache untuk ekspor & eksekusi cepat
            $resultPayload = [
                'total_ebmd'         => $totalEbmd,
                'nibar_matched'      => $nibarMatched,
                'identical_count'    => $identicalCount,
                'changed_count'      => $changedCount,
                'other_opd_count'    => $otherOpdCount,
                'not_found_sipat'    => $notFoundSipatCount,
                'only_in_sipat'      => $onlyInSipatCount,
                'conflict_count'     => $conflictCount,
                'duplicate_count'    => $duplicateCount,
                'invalid_count'      => $invalidCount,
                'field_breakdown'    => $fieldBreakdown,
                'items'              => $reconciliationItems,
                'selected_columns'   => $selectedColumns,
                'asset_category'     => $category,
                'opd_id'             => $opdId,
                'opd_name'           => $opdName,
                'user_id'            => auth()->id(),
            ];

            Cache::put('rekon_result_' . $request->input('import_token'), $resultPayload, now()->addMinutes(30));

            return response()->json(array_merge([
                'success' => true,
            ], $resultPayload));

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses pratinjau rekonsiliasi NIBAR: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mengekspor hasil analisis rekonsiliasi ke file CSV / Excel.
     */
    public function exportPreview(Request $request): StreamedResponse|JsonResponse
    {
        $request->validate([
            'import_token' => 'required|string',
        ]);

        $cachedResult = Cache::get('rekon_result_' . $request->input('import_token'));
        if (!$cachedResult || empty($cachedResult['items'])) {
            return response()->json(['success' => false, 'message' => 'Data hasil rekonsiliasi tidak ditemukan atau sudah kadaluwarsa.'], 404);
        }

        if (isset($cachedResult['user_id']) && $cachedResult['user_id'] !== auth()->id() && auth()->user()?->role !== 'superadmin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Hasil rekonsiliasi ini milik pengguna lain.'], 403);
        }

        $items = $cachedResult['items'];
        $category = $cachedResult['asset_category'] ?? 'aset';
        $opdSlug = !empty($cachedResult['opd_name']) ? '_' . Str::slug($cachedResult['opd_name']) : '';
        $fileName = "Hasil_Rekonsiliasi_eBMD_{$category}{$opdSlug}_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$fileName}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'No',
            'NIBAR',
            'Nama Aset',
            'Status Rekonsiliasi',
            'OPD / Instansi',
            'Kolom Berubah',
            'Nilai SIPAT (Lama)',
            'Nilai e-BMD (Baru)',
            'Keterangan'
        ];

        return response()->stream(function () use ($items, $columns) {
            $file = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $columns);

            $no = 1;
            foreach ($items as $item) {
                if (!empty($item['changed_columns'])) {
                    foreach ($item['changed_columns'] as $diff) {
                        fputcsv($file, [
                            $no++,
                            " " . $item['nibar'], // Keep leading zero in Excel
                            $item['name'],
                            $item['status_label'],
                            $item['opd'],
                            $diff['label'] . " (" . $diff['column'] . ")",
                            $diff['old'],
                            $diff['new'],
                            $item['notes'],
                        ]);
                    }
                } else {
                    fputcsv($file, [
                        $no++,
                        " " . $item['nibar'],
                        $item['name'],
                        $item['status_label'],
                        $item['opd'],
                        '-',
                        '-',
                        '-',
                        $item['notes'],
                    ]);
                }
            }

            fclose($file);
        }, 200, $headers);
    }

    /**
     * Menyesuaikan/memutasikan OPD dari aset-aset yang terdeteksi EXISTS_OTHER_OPD
     * ke OPD target yang sedang direkonsiliasi.
     */
    public function adjustOpd(Request $request): JsonResponse
    {
        $request->validate([
            'import_token'     => 'required|string',
            'asset_category'   => 'required|in:tanah,bangunan,vehicle,ebmd_vehicle',
            'opd_id'           => 'required|integer|exists:opds,id',
            'mapping'          => 'required|array',
            'header_row_index' => 'nullable|integer',
        ]);

        $correlationId = 'ADJ-OPD-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        try {
            $tokenData = Cache::get($request->input('import_token'));
            if (!$tokenData || !Storage::disk('local')->exists($tokenData['file_path'])) {
                return response()->json(['success' => false, 'message' => 'Sesi impor telah kadaluwarsa. Silakan unggah kembali berkas Anda.'], 400);
            }

            if (isset($tokenData['user_id']) && $tokenData['user_id'] !== auth()->id() && auth()->user()?->role !== 'superadmin') {
                return response()->json(['success' => false, 'message' => 'Akses ditolak: Sesi impor ini milik pengguna lain.'], 403);
            }

            $fullPath = Storage::disk('local')->path($tokenData['file_path']);
            $category = $request->input('asset_category');
            $modelClass = $this->getModelClass($category);
            $nibarDbCol = $this->getNibarColumn($category);
            $mapping = $request->input('mapping');
            $headerRowIndex = (int) $request->input('header_row_index', 0);
            $targetOpdId = (int) $request->input('opd_id');

            $targetOpd = Opd::findOrFail($targetOpdId);
            $targetOpdName = $targetOpd->nama . ($targetOpd->singkatan ? ' (' . $targetOpd->singkatan . ')' : '');

            $nibarExcelIdx = $mapping['nibar'] ?? $mapping[$nibarDbCol] ?? null;
            if ($nibarExcelIdx === null) {
                return response()->json(['success' => false, 'message' => 'Kolom NIBAR wajib dipetakan.'], 422);
            }

            $reader = IOFactory::createReaderForFile($fullPath);
            $spreadsheet = $reader->load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $startRow = $headerRowIndex + 1;

            // Kumpulkan NIBAR unik dari file e-BMD
            $excelNibars = [];
            for ($i = $startRow; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty(array_filter($row, fn($v) => !is_null($v) && trim((string)$v) !== ''))) continue;
                $rawNibar = isset($row[$nibarExcelIdx]) ? trim((string)$row[$nibarExcelIdx]) : '';
                if ($rawNibar !== '' && $rawNibar !== '-') {
                    $excelNibars[$rawNibar] = true;
                }
            }

            // Kumpulkan seluruh record SIPAT
            $allSipatRecords = $modelClass::withoutGlobalScopes()->get();
            $allSipatByNibar = [];
            foreach ($allSipatRecords as $rec) {
                $recNibar = trim((string)($rec->$nibarDbCol ?? ''));
                if ($recNibar !== '') {
                    $allSipatByNibar[$recNibar][] = $rec;
                }
            }

            $adjustedCount = 0;
            $auditLogsToInsert = [];

            DB::beginTransaction();

            foreach (array_keys($excelNibars) as $nibar) {
                $matches = $allSipatByNibar[$nibar] ?? [];
                
                // Hanya proses jika ditemukan tepat 1 di SIPAT dan BUKAN di OPD target (EXISTS_OTHER_OPD)
                if (count($matches) === 1) {
                    $model = $matches[0];
                    if ((int)$model->opd_id !== $targetOpdId) {
                        $oldOpdId = $model->opd_id;
                        $oldOpdName = $this->getModelOpdName($model);

                        $updateData = ['opd_id' => $targetOpdId];
                        if (in_array('opd', $model->getFillable())) {
                            $updateData['opd'] = $targetOpdName;
                        }

                        $model->update($updateData);
                        $adjustedCount++;

                        $changesLog = [
                            'opd_id' => ['old' => $oldOpdId, 'new' => $targetOpdId],
                            'opd'    => ['old' => $oldOpdName, 'new' => $targetOpdName],
                        ];

                        $auditLogsToInsert[] = [
                            'event_id'       => (string) Str::uuid(),
                            'correlation_id' => $correlationId,
                            'nibar'          => $nibar,
                            'event_name'     => 'EBMD_RECONCILIATION_OPD_TRANSFER',
                            'source_system'  => 'EBMD',
                            'direction'      => 'INBOUND',
                            'changes'        => json_encode($changesLog),
                            'reason'         => "Penyesuaian/Mutasi OPD Rekonsiliasi NIBAR [{$nibar}] dari [{$oldOpdName}] ke [{$targetOpdName}]",
                            'sync_status'    => 'SUCCESS',
                            'created_by'     => auth()->user()?->username ?? auth()->user()?->name ?? 'system',
                            'created_at'     => now(),
                        ];
                    }
                }
            }

            if (!empty($auditLogsToInsert)) {
                IntegrationAuditLog::insert($auditLogsToInsert);
            }

            DB::commit();

            if (in_array($category, ['vehicle', 'ebmd_vehicle'])) {
                $this->vehicleService->invalidateDashboardStats(invalidateAllOpd: true);
            }

            Activity::log("Penyesuaian OPD Rekonsiliasi NIBAR [{$correlationId}] Kategori ({$category}). Total {$adjustedCount} aset dipindahkan ke {$targetOpdName}.", 'info');

            return response()->json([
                'success'        => true,
                'message'        => "Berhasil menyesuaikan OPD: {$adjustedCount} aset telah dipindahkan ke {$targetOpdName}.",
                'adjusted_count' => $adjustedCount,
                'correlation_id' => $correlationId,
                'target_opd'     => $targetOpdName,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyesuaikan OPD aset: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mengeksekusi impor selektif / update kolom yang dicentang secara atomic dengan transaksi database & audit trail.
     */
    public function execute(Request $request): JsonResponse
    {
        $request->validate([
            'import_token'     => 'required|string',
            'selected_columns' => 'required|array|min:1',
            'asset_category'   => 'required|in:tanah,bangunan,vehicle,ebmd_vehicle',
            'mapping'          => 'required|array',
            'create_new'       => 'nullable|boolean',
            'opd_id'           => 'nullable|integer|exists:opds,id',
        ]);

        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        $correlationId = 'RC-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        try {
            $tokenData = Cache::get($request->input('import_token'));
            if (!$tokenData || !Storage::disk('local')->exists($tokenData['file_path'])) {
                return response()->json(['success' => false, 'message' => 'Sesi impor telah kadaluwarsa. Silakan unggah kembali berkas Anda.'], 400);
            }

            if (isset($tokenData['user_id']) && $tokenData['user_id'] !== auth()->id() && auth()->user()?->role !== 'superadmin') {
                return response()->json(['success' => false, 'message' => 'Akses ditolak: Sesi impor ini milik pengguna lain.'], 403);
            }

            $fullPath = Storage::disk('local')->path($tokenData['file_path']);
            $category = $request->input('asset_category');
            $modelClass = $this->getModelClass($category);
            $nibarDbCol = $this->getNibarColumn($category);
            $selectedColumns = $request->input('selected_columns');
            $mapping = $request->input('mapping');
            $headerRowIndex = (int) $request->input('header_row_index', 0);
            $createNew = (bool) $request->input('create_new', false);

            $opdId = $request->input('opd_id');
            $opdName = null;
            if (!empty($opdId)) {
                $opdObj = Opd::find($opdId);
                $opdName = $opdObj ? ($opdObj->nama . ($opdObj->singkatan ? ' (' . $opdObj->singkatan . ')' : '')) : null;
            }

            $nibarExcelIdx = $mapping['nibar'] ?? $mapping[$nibarDbCol] ?? null;
            if ($nibarExcelIdx === null) {
                return response()->json(['success' => false, 'message' => 'Kolom NIBAR wajib dipetakan.'], 422);
            }

            $reader = IOFactory::createReaderForFile($fullPath);
            $spreadsheet = $reader->load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $startRow = $headerRowIndex + 1;

            // 1. Hitung frekuensi NIBAR e-BMD
            $excelNibarCounts = [];
            for ($i = $startRow; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty(array_filter($row, fn($v) => !is_null($v) && trim((string)$v) !== ''))) continue;
                $rawNibar = isset($row[$nibarExcelIdx]) ? trim((string)$row[$nibarExcelIdx]) : '';
                if ($rawNibar !== '' && $rawNibar !== '-') {
                    $excelNibarCounts[$rawNibar] = ($excelNibarCounts[$rawNibar] ?? 0) + 1;
                }
            }

            // 2. Kumpulkan seluruh record SIPAT secara Global & Per OPD
            $allSipatRecords = $modelClass::withoutGlobalScopes()->get();
            $allSipatByNibar = [];
            $scopedSipatByNibar = [];

            foreach ($allSipatRecords as $rec) {
                $recNibar = trim((string)($rec->$nibarDbCol ?? ''));
                if ($recNibar !== '') {
                    $allSipatByNibar[$recNibar][] = $rec;
                    if (!empty($opdId)) {
                        if ($rec->opd_id == $opdId) {
                            $scopedSipatByNibar[$recNibar][] = $rec;
                        }
                    } else {
                        $scopedSipatByNibar[$recNibar][] = $rec;
                    }
                }
            }

            $updatedCount = 0;
            $insertedCount = 0;
            $skippedCount = 0;
            $auditLogsToInsert = [];

            // MEMULAI TRANSAKSI DATABASE UNTUK MENJAMIN ATOMICITY
            DB::beginTransaction();

            for ($i = $startRow; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty(array_filter($row, fn($v) => !is_null($v) && trim((string)$v) !== ''))) continue;

                $rawNibar = isset($row[$nibarExcelIdx]) ? trim((string)$row[$nibarExcelIdx]) : '';
                
                // Lewati NIBAR kosong
                if ($rawNibar === '' || $rawNibar === '-') {
                    $skippedCount++;
                    continue;
                }

                // Lewati jika NIBAR duplikat di e-BMD
                if (($excelNibarCounts[$rawNibar] ?? 0) > 1) {
                    $skippedCount++;
                    continue;
                }

                // Lewati jika NIBAR duplikat di scope aktif
                if (isset($scopedSipatByNibar[$rawNibar]) && count($scopedSipatByNibar[$rawNibar]) > 1) {
                    $skippedCount++;
                    continue;
                }

                // DATA DITEMUKAN DI SCOPE AKTIF SECARA UNIK
                if (isset($scopedSipatByNibar[$rawNibar]) && count($scopedSipatByNibar[$rawNibar]) === 1) {
                    $existingModel = $scopedSipatByNibar[$rawNibar][0];
                    $updateData = [];
                    $changesLog = [];

                    foreach ($selectedColumns as $col) {
                        if (!isset($mapping[$col])) continue;
                        $excelColIdx = $mapping[$col];
                        if (!isset($row[$excelColIdx])) continue;

                        $rawVal = trim((string)$row[$excelColIdx]);

                        // PERLINDUNGAN NILAI KOSONG: Nilai kosong dari e-BMD TIDAK BOLEH menghapus data SIPAT
                        if ($rawVal === '' || $rawVal === '(Kosong)') {
                            continue;
                        }

                        $oldVal = trim((string)($existingModel->$col ?? ''));

                        if (in_array($col, ['nilai_perolehan', 'harga_perolehan', 'luas', 'luas_lantai', 'luas_dasar'])) {
                            $refVal = $this->parseNumericToDatabase($oldVal);
                            $parsedVal = $this->parseNumericToDatabase($rawVal, $refVal);
                            
                            if (abs($parsedVal - $refVal) > 0.01) {
                                $updateData[$col] = $parsedVal;
                                $changesLog[$col] = ['old' => $oldVal, 'new' => $parsedVal];
                            }
                        } else if (in_array($col, ['tanggal_perolehan', 'tgl_perolehan'])) {
                            $parsedDate = $this->parseDateToDatabase($rawVal);
                            if ($parsedDate && $parsedDate !== $oldVal) {
                                $updateData[$col] = $parsedDate;
                                $changesLog[$col] = ['old' => $oldVal, 'new' => $parsedDate];
                            }
                        } else {
                            if (strtolower($rawVal) !== strtolower($oldVal)) {
                                $updateData[$col] = $rawVal;
                                $changesLog[$col] = ['old' => $oldVal, 'new' => $rawVal];
                            }
                        }
                    }

                    if (!empty($updateData)) {
                        $existingModel->update($updateData);
                        $updatedCount++;

                        $opdScopeText = $opdName ? " [OPD: {$opdName}]" : " [Seluruh OPD]";
                        $auditLogsToInsert[] = [
                            'event_id'       => (string) Str::uuid(),
                            'correlation_id' => $correlationId,
                            'nibar'          => $rawNibar,
                            'event_name'     => 'EBMD_RECONCILIATION_UPDATE',
                            'source_system'  => 'EBMD',
                            'direction'      => 'INBOUND',
                            'changes'        => json_encode($changesLog),
                            'reason'         => "Rekonsiliasi e-BMD Kategori {$category}{$opdScopeText} (" . implode(', ', array_keys($updateData)) . ")",
                            'sync_status'    => 'SUCCESS',
                            'created_by'     => auth()->user()?->username ?? auth()->user()?->name ?? 'system',
                            'created_at'     => now(),
                        ];
                    } else {
                        $skippedCount++;
                    }
                } elseif (!empty($opdId) && isset($allSipatByNibar[$rawNibar]) && count($allSipatByNibar[$rawNibar]) > 0) {
                    // NIBAR terdaftar di OPD lain atau memiliki konflik global -> JANGAN UPDATE, JANGAN PINDAHKAN OPD, JANGAN INSERT BARU
                    $skippedCount++;
                } elseif ($createNew) {
                    // Penambahan data baru (hanya jika NIBAR benar-benar tidak terdaftar di seluruh database SIPAT)
                    $newData = [$nibarDbCol => $rawNibar];
                    if (!empty($opdId)) {
                        $newData['opd_id'] = $opdId;
                    }

                    foreach ($mapping as $col => $excelColIdx) {
                        if (isset($row[$excelColIdx])) {
                            $rawVal = trim((string)$row[$excelColIdx]);
                            if ($rawVal === '' || $rawVal === '(Kosong)') continue;

                            if (in_array($col, ['nilai_perolehan', 'harga_perolehan', 'luas', 'luas_lantai', 'luas_dasar'])) {
                                $newData[$col] = $this->parseNumericToDatabase($rawVal);
                            } else if (in_array($col, ['tanggal_perolehan', 'tgl_perolehan'])) {
                                $newData[$col] = $this->parseDateToDatabase($rawVal);
                            } else {
                                $newData[$col] = $rawVal;
                            }
                        }
                    }

                    $modelClass::create($newData);
                    $insertedCount++;

                    $opdScopeText = $opdName ? " [OPD: {$opdName}]" : "";
                    $auditLogsToInsert[] = [
                        'event_id'       => (string) Str::uuid(),
                        'correlation_id' => $correlationId,
                        'nibar'          => $rawNibar,
                        'event_name'     => 'EBMD_RECONCILIATION_INSERT',
                        'source_system'  => 'EBMD',
                        'direction'      => 'INBOUND',
                        'changes'        => json_encode($newData),
                        'reason'         => "Penambahan Aset Baru dari Rekonsiliasi e-BMD {$category}{$opdScopeText}",
                        'sync_status'    => 'SUCCESS',
                        'created_by'     => auth()->user()?->username ?? auth()->user()?->name ?? 'system',
                        'created_at'     => now(),
                    ];
                } else {
                    $skippedCount++;
                }
            }

            // Simpan batch audit logs ke database
            if (!empty($auditLogsToInsert)) {
                IntegrationAuditLog::insert($auditLogsToInsert);
            }

            // COMMIT TRANSAKSI
            DB::commit();

            // Bersihkan file temporer dan cache
            Storage::disk('local')->delete($tokenData['file_path']);
            Cache::forget($request->input('import_token'));
            Cache::forget('rekon_result_' . $request->input('import_token'));

            if (in_array($category, ['vehicle', 'ebmd_vehicle'])) {
                $this->vehicleService->invalidateDashboardStats(invalidateAllOpd: true);
            }

            $columnListStr = implode(', ', $selectedColumns);
            Activity::log("Rekonsiliasi e-BMD NIBAR [{$correlationId}] Kategori ({$category}). Diperbarui: {$updatedCount}, Ditambahkan: {$insertedCount}, Dilewati: {$skippedCount}. Kolom: [{$columnListStr}]", 'success');

            return response()->json([
                'success'        => true,
                'message'        => "Rekonsiliasi data e-BMD berhasil dieksekusi! Diperbarui: {$updatedCount} aset, Ditambahkan: {$insertedCount} aset baru.",
                'correlation_id' => $correlationId,
                'updated_count'  => $updatedCount,
                'inserted_count' => $insertedCount,
                'skipped_count'  => $skippedCount,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengeksekusi rekonsiliasi data (Transaksi dibatalkan): ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Membandingkan nilai kolom lama dan nilai kolom baru dengan normalisasi lengkap.
     */
    protected function compareColumnValue(string $col, string $oldVal, string $newVal): array
    {
        // Default: jika e-BMD kosong, lindungi SIPAT (tidak dianggap berbeda yang merusak)
        if ($newVal === '' || $newVal === '(Kosong)') {
            return [
                'is_diff'       => false,
                'formatted_old' => $oldVal ?: '(Kosong)',
                'formatted_new' => '(Kosong / Dilindungi)',
            ];
        }

        if (in_array($col, ['nilai_perolehan', 'harga_perolehan'])) {
            $oldParsed = $this->parseNumericToDatabase($oldVal);
            $newParsed = $this->parseNumericToDatabase($newVal, $oldParsed);

            $isDiff = abs($newParsed - $oldParsed) > 0.01;
            return [
                'is_diff'       => $isDiff,
                'formatted_old' => 'Rp ' . number_format($oldParsed, 0, ',', '.'),
                'formatted_new' => 'Rp ' . number_format($newParsed, 0, ',', '.'),
            ];
        }

        if (in_array($col, ['luas', 'luas_lantai', 'luas_dasar'])) {
            $oldParsed = $this->parseNumericToDatabase($oldVal);
            $newParsed = $this->parseNumericToDatabase($newVal, $oldParsed);

            $isDiff = abs($newParsed - $oldParsed) > 0.01;
            return [
                'is_diff'       => $isDiff,
                'formatted_old' => number_format($oldParsed, 0, ',', '.') . ' m²',
                'formatted_new' => number_format($newParsed, 0, ',', '.') . ' m²',
            ];
        }

        if (in_array($col, ['tanggal_perolehan', 'tgl_perolehan'])) {
            $dbOld = $this->parseDateToDatabase($oldVal);
            $dbNew = $this->parseDateToDatabase($newVal);

            $isDiff = ($dbOld !== $dbNew && strtolower($oldVal) !== strtolower($newVal));
            return [
                'is_diff'       => $isDiff,
                'formatted_old' => $this->formatDateString($oldVal) ?: ($oldVal ?: '(Kosong)'),
                'formatted_new' => $this->formatDateString($newVal) ?: ($newVal ?: '(Kosong)'),
            ];
        }

        // String / Text Normalization: trim & case-insensitive comparison
        $cleanOld = preg_replace('/\s+/', ' ', strtolower(trim($oldVal)));
        $cleanNew = preg_replace('/\s+/', ' ', strtolower(trim($newVal)));

        $isDiff = ($cleanOld !== $cleanNew);
        return [
            'is_diff'       => $isDiff,
            'formatted_old' => $oldVal ?: '(Kosong)',
            'formatted_new' => $newVal ?: '(Kosong)',
        ];
    }

    /**
     * Mengambil kolom basis data yang berfungsi sebagai NIBAR untuk kategori aset.
     */
    protected function getNibarColumn(string $category): string
    {
        return match ($category) {
            'tanah'        => 'kode_aset',
            'bangunan'     => 'kode_bangunan',
            'ebmd_vehicle' => 'nomor_register',
            default        => 'nomor_register',
        };
    }

    /**
     * Memetakan nama kategori ke kelas Model Laravel.
     */
    protected function getModelClass(string $category): string
    {
        return match ($category) {
            'ebmd_vehicle' => EbmdVehicle::class,
            'tanah'        => AsetTanah::class,
            'bangunan'     => Bangunan::class,
            default        => Vehicle::class,
        };
    }

    /**
     * Mengambil nama label tampilan model.
     */
    protected function getModelDisplayName($model): string
    {
        return $model->nama_aset 
            ?? $model->nama_bangunan 
            ?? $model->merk 
            ?? $model->pemegang 
            ?? 'Aset Data';
    }

    /**
     * Mengambil nama OPD model.
     */
    protected function getModelOpdName($model): string
    {
        return $model->opdRelation?->nama 
            ?? $model->opdRelation?->nama_opd 
            ?? $model->opd 
            ?? '-';
    }

    /**
     * Mengambil nama Sub-OPD model.
     */
    protected function getModelSubOpdName($model): string
    {
        return $model->subOpd?->nama 
            ?? $model->subOpd?->nama_sub_opd 
            ?? $model->pemegang 
            ?? '-';
    }

    /**
     * Ekstraksi nama aset dari baris Excel untuk tampilan baris tanpa record DB.
     */
    protected function extractRowName(array $row, array $mapping, string $category): string
    {
        $nameCols = match ($category) {
            'tanah'        => ['nama_aset', 'peruntukan'],
            'bangunan'     => ['nama_bangunan', 'kode_barang'],
            default        => ['merk', 'tipe', 'jenis', 'no_polisi'],
        };

        foreach ($nameCols as $col) {
            if (isset($mapping[$col]) && isset($row[$mapping[$col]]) && trim((string)$row[$mapping[$col]]) !== '') {
                return trim((string)$row[$mapping[$col]]);
            }
        }

        return 'Data e-BMD';
    }

    /**
     * Mengambil label kolom tampilan.
     */
    protected function getColumnLabel(string $category, string $col): string
    {
        $config = $this->getCategoryConfig($category);
        return $config['updatable_columns'][$col] ?? $col;
    }

    /**
     * Mendapatkan konfigurasi kolom & matching keys per kategori aset.
     */
    protected function getCategoryConfig(string $category): array
    {
        return match ($category) {
            'tanah' => [
                'matching_keys' => [
                    'nibar' => 'NIBAR (Nomor Induk Barang / Kode Aset)',
                ],
                'updatable_columns' => [
                    'nama_aset'         => 'Nama Aset',
                    'peruntukan'        => 'Peruntukan / Penggunaan',
                    'luas'              => 'Luas Tanah (m²)',
                    'alamat'            => 'Alamat / Lokasi Tanah',
                    'dasar_perolehan'   => 'Dasar Perolehan',
                    'harga_perolehan'   => 'Harga / Nilai Perolehan',
                    'tanggal_perolehan' => 'Tanggal Perolehan Aset',
                    'keterangan'        => 'Keterangan Tambahan',
                ],
            ],
            'bangunan' => [
                'matching_keys' => [
                    'nibar' => 'NIBAR (Nomor Induk Barang / Kode Bangunan)',
                ],
                'updatable_columns' => [
                    'nama_bangunan'     => 'Nama Gedung / Bangunan',
                    'kode_barang'       => 'Kode Barang',
                    'nomor_register'    => 'Nomor Register',
                    'luas_lantai'       => 'Luas Lantai (m²)',
                    'luas_dasar'        => 'Luas Dasar (m²)',
                    'jumlah_lantai'     => 'Jumlah Lantai',
                    'kondisi'           => 'Kondisi Bangunan',
                    'nama_penghuni'     => 'Nama Penghuni / Pemakai',
                    'alamat'            => 'Alamat Lokasi',
                    'nomor_dokumen_pbg' => 'Nomor Dokumen PBG / IMB',
                    'harga_perolehan'   => 'Harga / Nilai Perolehan',
                    'keterangan'        => 'Keterangan Tambahan',
                ],
            ],
            default => [
                'matching_keys' => [
                    'nibar' => 'NIBAR (Nomor Induk Barang / Register)',
                ],
                'updatable_columns' => [
                    'no_polisi'       => 'Nomor Polisi (Plat)',
                    'jenis'           => 'Jenis Kendaraan',
                    'merk'            => 'Merk / Pabrikan',
                    'tipe'            => 'Tipe / Model',
                    'no_mesin'        => 'Nomor Mesin',
                    'no_rangka'       => 'Nomor Rangka',
                    'tahun_pembuatan' => 'Tahun Pembuatan',
                    'tgl_perolehan'   => 'Tanggal Perolehan Aset',
                    'nilai_perolehan' => 'Nilai / Harga Perolehan',
                    'stnk_ada'        => 'Status STNK',
                    'bpkb_ada'        => 'Status BPKB',
                    'kondisi'         => 'Kondisi Fisik Kendaraan',
                    'pemegang'        => 'Nama Pemegang',
                    'keterangan'      => 'Keterangan',
                ],
            ],
        };
    }

    /**
     * Merekomendasikan pemetaan kolom semantik cerdas per kategori aset.
     */
    protected function suggestCategoryColumnMapping(array $headers, string $category): array
    {
        $synonyms = match ($category) {
            'tanah' => [
                'nibar'             => ['nibar', 'nomor induk barang', 'no nibar', 'no. nibar', 'kode aset', 'kode_aset', 'kode barang', 'kodefikasi', 'nomor kode barang', 'kode tanah', 'penggolongan dan kodefikasi barang', 'nib'],
                'nama_aset'         => ['nama aset', 'nama_aset', 'nama barang', 'jenis barang', 'spesifikasi nama barang', 'uraian nama barang', 'tanah'],
                'peruntukan'        => ['peruntukan', 'penggunaan', 'status peruntukan', 'fungsi', 'peruntukan tanah'],
                'luas'              => ['luas', 'luas tanah', 'luas m2', 'luas (m2)', 'luas bidang', 'luas perolehan', 'luas keseluruhan'],
                'alamat'            => ['alamat', 'lokasi', 'letak', 'letak / alamat', 'letak alamat', 'alamat tanah'],
                'dasar_perolehan'   => ['dasar perolehan', 'asal usul', 'status hak', 'dasar', 'hak tanah', 'asal usul perolehan'],
                'harga_perolehan'   => ['harga', 'harga perolehan', 'nilai perolehan', 'nilai', 'nilai aset', 'harga satuan perolehan', 'nilai perolehan rp', 'rp', 'jumlah perolehan'],
                'tanggal_perolehan' => ['tanggal perolehan', 'tgl perolehan', 'tgl beli', 'tanggal beli', 'tahun perolehan', 'tgl perolehan aset', 'acquisition date'],
                'keterangan'        => ['keterangan', 'ket', 'note', 'notes', 'keterangan aset'],
            ],
            'bangunan' => [
                'nibar'             => ['nibar', 'nomor induk barang', 'kode bangunan', 'kode_bangunan', 'kode gedung', 'id bangunan', 'kode aset', 'kode barang', 'kode_barang', 'nib'],
                'nama_bangunan'     => ['nama bangunan', 'nama gedung', 'nama aset', 'nama barang', 'spesifikasi nama barang', 'uraian nama barang'],
                'kode_barang'       => ['kode barang', 'kode_barang', 'penggolongan dan kodefikasi barang', 'nomor kode barang'],
                'nomor_register'    => ['nomor register', 'no register', 'no. register', 'register', 'noreg', 'register number', 'reg number'],
                'luas_lantai'       => ['luas lantai', 'luas lantai (m2)', 'luas lantai m2', 'luas bangunan', 'luas m2'],
                'luas_dasar'        => ['luas dasar', 'luas dasar (m2)', 'luas tapak', 'luas dasar bangunan'],
                'jumlah_lantai'     => ['jumlah lantai', 'bertingkat', 'tingkat', 'jml lantai', 'lantai'],
                'kondisi'           => ['kondisi', 'kondisi bangunan', 'keadaan', 'status kondisi', 'kondisi fisik'],
                'nama_penghuni'     => ['nama penghuni', 'penghuni', 'pemakai', 'penanggung jawab', 'nama pemakai'],
                'alamat'            => ['alamat', 'lokasi', 'letak', 'letak / alamat', 'alamat bangunan'],
                'nomor_dokumen_pbg' => ['nomor dokumen pbg', 'no pbg', 'no imb', 'nomor imb', 'dokumen pbg', 'surat izin'],
                'harga_perolehan'   => ['harga', 'harga perolehan', 'nilai perolehan', 'nilai', 'nilai aset', 'harga satuan perolehan', 'nilai perolehan rp', 'rp', 'jumlah perolehan'],
                'keterangan'        => ['keterangan', 'ket', 'note', 'notes', 'keterangan tambahan'],
            ],
            default => [
                'nibar'           => ['nibar', 'nomor induk barang', 'no nibar', 'no. nibar', 'nomor register', 'no register', 'no. register', 'nomer register', 'register', 'register number', 'reg number', 'nib'],
                'no_polisi'       => ['no polisi', 'no. polisi', 'nomor polisi', 'plat', 'no plat', 'no. plat', 'nomor plat', 'nopol', 'plat nomor', 'plate', 'plate number'],
                'jenis'           => ['jenis', 'jenis kendaraan', 'kategori', 'kategori kendaraan', 'roda', 'class', 'category', 'jenis roda'],
                'merk'            => ['merk', 'merek', 'brand', 'pabrikan', 'nama aset', 'nama kendaraan', 'make', 'spesifikasi nama barang', 'nama barang', 'spesifikasi'],
                'tipe'            => ['tipe', 'type', 'model', 'jenis tipe', 'tipe kendaraan', 'spesifikasi lainnya', 'spesifikasi barang'],
                'no_mesin'        => ['no mesin', 'no. mesin', 'nomor mesin', 'engine number', 'engine no', 'nomer mesin'],
                'no_rangka'       => ['no rangka', 'no. rangka', 'nomor rangka', 'chassis number', 'vin', 'chassis no', 'nomer rangka'],
                'tahun_pembuatan' => ['tahun', 'tahun pembuatan', 'thn', 'tahun rakit', 'tahun buat', 'year', 'thn pembuatan', 'thn buat'],
                'tgl_perolehan'   => ['tgl perolehan', 'tanggal perolehan', 'tgl beli', 'tanggal beli', 'tanggal perolehan aset', 'acquisition date', 'tgl perolehan aset'],
                'nilai_perolehan' => ['harga', 'nilai perolehan', 'harga perolehan', 'nilai', 'nilai aset', 'harga beli', 'price', 'value', 'jumlah perolehan', 'harga satuan perolehan', 'nilai perolehan rp', 'harga satuan perolehan rp', 'rp'],
                'stnk_ada'        => ['stnk', 'status stnk', 'kelengkapan stnk', 'ada stnk', 'surat stnk'],
                'bpkb_ada'        => ['bpkb', 'status bpkb', 'kelengkapan bpkb', 'ada bpkb', 'surat bpkb', 'bukti kepemilikan'],
                'kondisi'         => ['kondisi', 'kondisi fisik', 'keadaan', 'status kondisi', 'condition', 'kondisi aset', 'kondisi kendaraan', 'status penggunaan'],
                'pemegang'        => ['pemegang', 'nama pemegang', 'penanggung jawab', 'peminjam', 'user', 'driver', 'nama pemakai', 'penggunaan', 'pengguna', 'nama kepemilikan dalam dokumen'],
                'keterangan'      => ['keterangan', 'ket', 'note', 'notes', 'keterangan tambahan', 'keterangan aset'],
            ],
        };

        $suggestedMapping = [];
        $usedIndices = [];

        // 1. Exact match on synonyms
        foreach ($synonyms as $dbCol => $synList) {
            foreach ($headers as $idx => $header) {
                if (in_array($idx, $usedIndices)) continue;
                $cleanHeader = strtolower(trim((string)$header));
                $cleanHeader = preg_replace('/[^a-z0-9\s]/', '', $cleanHeader);
                $cleanHeader = preg_replace('/\s+/', ' ', $cleanHeader);

                foreach ($synList as $syn) {
                    if ($cleanHeader === $syn) {
                        $suggestedMapping[$dbCol] = $idx;
                        $usedIndices[] = $idx;
                        break 2;
                    }
                }
            }
        }

        // 2. Partial / Substring match
        foreach ($synonyms as $dbCol => $synList) {
            if (isset($suggestedMapping[$dbCol])) continue;
            foreach ($headers as $idx => $header) {
                if (in_array($idx, $usedIndices)) continue;
                $cleanHeader = strtolower(trim((string)$header));
                $cleanHeader = preg_replace('/[^a-z0-9\s]/', '', $cleanHeader);
                $cleanHeader = preg_replace('/\s+/', ' ', $cleanHeader);

                foreach ($synList as $syn) {
                    if (strlen($syn) >= 4 && (str_contains($cleanHeader, $syn) || str_contains($syn, $cleanHeader))) {
                        $suggestedMapping[$dbCol] = $idx;
                        $usedIndices[] = $idx;
                        break 2;
                    }
                }
            }
        }

        return $suggestedMapping;
    }

    /**
     * Memformat string tanggal menjadi format Indonesia.
     */
    protected function formatDateString(string $dateStr): string
    {
        if (empty($dateStr) || $dateStr === '(Kosong)') return '';

        $dbDate = $this->parseDateToDatabase($dateStr);
        if (!$dbDate) return $dateStr;

        try {
            $ts = strtotime($dbDate);
            if ($ts === false || $ts < 1) return $dateStr;

            $months = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
                9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
            ];

            $day = (int) date('j', $ts);
            $month = (int) date('n', $ts);
            $year = (int) date('Y', $ts);

            return "{$day} {$months[$month]} {$year}";
        } catch (\Throwable $e) {
            return $dateStr;
        }
    }

    /**
     * Memparsing string angka / harga / luas menjadi float murni.
     */
    protected function parseNumericToDatabase(string $rawVal, ?float $referenceVal = null): float
    {
        if (empty($rawVal) || $rawVal === '(Kosong)') return 0.0;
        
        $cleaned = preg_replace('/[^\d.,]/', '', $rawVal);
        if (empty($cleaned)) return 0.0;

        if (str_contains($cleaned, ',') && str_contains($cleaned, '.')) {
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
        } else if (str_contains($cleaned, '.')) {
            $parts = explode('.', $cleaned);
            if (count($parts) > 2 || (count($parts) == 2 && strlen($parts[1]) == 3)) {
                $cleaned = str_replace('.', '', $cleaned);
            }
        } else if (str_contains($cleaned, ',')) {
            $parts = explode(',', $cleaned);
            if (count($parts) > 2 || (count($parts) == 2 && strlen($parts[1]) == 3)) {
                $cleaned = str_replace(',', '', $cleaned);
            } else {
                $cleaned = str_replace(',', '.', $cleaned);
            }
        }

        $val = (float) $cleaned;

        // Deteksi otomatis skala Ribuan (x1.000) atau Jutaan (x1.000.000) dari ekspor e-BMD
        if ($referenceVal !== null && $referenceVal > 1000 && $val > 0 && $val < 1000000) {
            if (abs(($val * 1000) - $referenceVal) < 2) {
                return $val * 1000;
            }
            if (abs(($val * 1000000) - $referenceVal) < 2) {
                return $val * 1000000;
            }
            if ($val < 10000 && ($referenceVal / 1000) >= ($val - 1) && ($referenceVal / 1000) <= ($val + 1)) {
                return $val * 1000;
            }
            if ($val < 10000 && ($referenceVal / 1000000) >= ($val - 1) && ($referenceVal / 1000000) <= ($val + 1)) {
                return $val * 1000000;
            }
        }

        return $val;
    }

    /**
     * Memparsing string tanggal secara akurat menjadi format MySQL YYYY-MM-DD.
     */
    protected function parseDateToDatabase(string $rawVal): ?string
    {
        if (empty($rawVal) || $rawVal === '(Kosong)') return null;

        $str = trim($rawVal);

        try {
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $str, $m)) {
                $n1 = (int) $m[1];
                $n2 = (int) $m[2];
                $year = (int) $m[3];

                if ($n1 > 12) {
                    return sprintf('%04d-%02d-%02d', $year, $n2, $n1);
                } elseif ($n2 > 12) {
                    return sprintf('%04d-%02d-%02d', $year, $n1, $n2);
                } else {
                    return sprintf('%04d-%02d-%02d', $year, $n2, $n1);
                }
            }

            if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $str, $m)) {
                return sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
            }

            $ts = strtotime($str);
            if ($ts !== false && $ts > 0) {
                $year = (int) date('Y', $ts);
                if ($year >= 1900 && $year <= 2100) {
                    return date('Y-m-d', $ts);
                }
            }
        } catch (\Throwable $e) {}

        return null;
    }
}
