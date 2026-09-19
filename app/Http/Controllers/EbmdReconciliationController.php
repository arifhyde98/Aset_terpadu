<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\AsetTanah;
use App\Models\Bangunan;
use App\Models\EbmdVehicle;
use App\Models\Vehicle;
use App\Services\Erandis\VehicleImportService;
use App\Services\Erandis\VehicleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
        $activeCategory = $request->query('category', 'vehicle');

        $categories = [
            'vehicle' => [
                'name' => 'Kendaraan Dinas Real (eRANDIS)',
                'icon' => 'bi-car-front',
                'model' => Vehicle::class,
            ],
            'ebmd_vehicle' => [
                'name' => 'Master e-BMD Kendaraan',
                'icon' => 'bi-database',
                'model' => EbmdVehicle::class,
            ],
            'tanah' => [
                'name' => 'Aset Tanah (SIPAT)',
                'icon' => 'bi-geo-alt',
                'model' => AsetTanah::class,
            ],
            'bangunan' => [
                'name' => 'Gedung & Bangunan (SIPAT)',
                'icon' => 'bi-building',
                'model' => Bangunan::class,
            ],
        ];

        return view('rekon-ebmd.index', compact('activeCategory', 'categories'));
    }

    /**
     * Membaca file Excel yang diunggah dan mengembalikan daftar kolom & opsi matching key sesuai kategori aset.
     */
    public function uploadPreview(Request $request): JsonResponse
    {
        $request->validate([
            'file'          => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
            'asset_category'=> 'required|in:vehicle,ebmd_vehicle,tanah,bangunan',
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

            // Konfigurasi kolom & matching keys per kategori
            $config = $this->getCategoryConfig($category);
            
            // Rekomendasi mapping cerdas spesifik kategori
            $suggestedMapping = $this->suggestCategoryColumnMapping($preview['headers'] ?? [], $category);

            return response()->json(array_merge($preview, [
                'updatable_columns' => $config['updatable_columns'],
                'matching_keys'     => $config['matching_keys'],
                'suggested_mapping' => $suggestedMapping,
                'asset_category'    => $category,
            ]));
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca berkas Excel: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menganalisis perbedaan data (Diff Preview) sebelum eksekusi rekonsiliasi.
     */
    public function diffPreview(Request $request): JsonResponse
    {
        $request->validate([
            'import_token'     => 'required|string',
            'matching_key'     => 'required|string',
            'selected_columns' => 'required|array|min:1',
            'asset_category'   => 'required|in:vehicle,ebmd_vehicle,tanah,bangunan',
            'mapping'          => 'required|array',
        ]);

        try {
            $tokenData = Cache::get($request->input('import_token'));
            if (!$tokenData || !Storage::disk('local')->exists($tokenData['file_path'])) {
                return response()->json(['success' => false, 'message' => 'Sesi impor tidak valid atau berkas temporer telah kadaluwarsa.'], 400);
            }

            $fullPath = Storage::disk('local')->path($tokenData['file_path']);
            $category = $request->input('asset_category');
            $modelClass = $this->getModelClass($category);
            $matchingKey = $request->input('matching_key');
            $selectedColumns = $request->input('selected_columns');
            $mapping = $request->input('mapping');
            $headerRowIndex = (int) $request->input('header_row_index', 0);

            $reader = IOFactory::createReaderForFile($fullPath);
            $spreadsheet = $reader->load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $totalRows = 0;
            $matchedCount = 0;
            $changedCount = 0;
            $newCount = 0;
            $diffSamples = [];
            $columnCounts = [];

            $startRow = $headerRowIndex + 1;

            for ($i = $startRow; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty(array_filter($row))) continue;

                $totalRows++;

                $keyColIndex = $mapping[$matchingKey] ?? null;
                if ($keyColIndex === null || !isset($row[$keyColIndex])) {
                    $newCount++;
                    continue;
                }

                $keyValue = trim((string) $row[$keyColIndex]);
                if (empty($keyValue)) {
                    $newCount++;
                    continue;
                }

                // Query database tanpa global scope tenant agar rekonsiliasi lintas OPD akurat
                $query = $modelClass::withoutGlobalScopes();

                if ($matchingKey === 'no_polisi') {
                    $keyValueClean = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($keyValue));
                    $existingModel = $query->whereRaw("REPLACE(REPLACE(REPLACE(UPPER(no_polisi), ' ', ''), '.', ''), '-', '') = ?", [$keyValueClean])->first();
                } else {
                    $existingModel = $query->where($matchingKey, $keyValue)->first();
                    if (!$existingModel) {
                        $cleanKey = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($keyValue));
                        if (!empty($cleanKey)) {
                            $existingModel = $modelClass::withoutGlobalScopes()->whereRaw("REPLACE(REPLACE(REPLACE(UPPER({$matchingKey}), ' ', ''), '.', ''), '-', '') = ?", [$cleanKey])->first();
                        }
                    }
                }

                if (!$existingModel) {
                    $newCount++;
                    continue;
                }

                $matchedCount++;
                $hasDiff = false;
                $rowDiffs = [];

                foreach ($selectedColumns as $col) {
                    if (!isset($mapping[$col])) continue;
                    $excelColIdx = $mapping[$col];
                    if (!isset($row[$excelColIdx])) continue;

                    $newVal = trim((string) $row[$excelColIdx]);
                    $oldVal = trim((string) ($existingModel->$col ?? ''));

                    $isColDiff = false;

                    // Formatting & Comparison Logic
                    if (in_array($col, ['nilai_perolehan', 'harga_perolehan'])) {
                        $oldValParsed = $this->parseNumericToDatabase($oldVal);
                        $newValParsed = $this->parseNumericToDatabase($newVal, $oldValParsed);
                        if (abs($newValParsed - $oldValParsed) > 0.01) {
                            $isColDiff = true;
                            $rowDiffs[] = [
                                'column'    => $col,
                                'old'       => 'Rp ' . number_format($oldValParsed, 0, ',', '.'),
                                'new'       => 'Rp ' . number_format($newValParsed, 0, ',', '.'),
                                'raw_old'   => $oldValParsed,
                                'raw_new'   => $newValParsed,
                            ];
                        }
                    } else if (in_array($col, ['luas', 'luas_lantai', 'luas_dasar'])) {
                        $oldValParsed = $this->parseNumericToDatabase($oldVal);
                        $newValParsed = $this->parseNumericToDatabase($newVal, $oldValParsed);
                        if (abs($newValParsed - $oldValParsed) > 0.01) {
                            $isColDiff = true;
                            $rowDiffs[] = [
                                'column'    => $col,
                                'old'       => number_format($oldValParsed, 0, ',', '.') . ' m²',
                                'new'       => number_format($newValParsed, 0, ',', '.') . ' m²',
                                'raw_old'   => $oldValParsed,
                                'raw_new'   => $newValParsed,
                            ];
                        }
                    } else if (in_array($col, ['tanggal_perolehan', 'tgl_perolehan'])) {
                        $dbDateOld = $this->parseDateToDatabase($oldVal);
                        $dbDateNew = $this->parseDateToDatabase($newVal);

                        if ($dbDateOld !== $dbDateNew && strtolower($oldVal) !== strtolower($newVal)) {
                            $isColDiff = true;
                            $rowDiffs[] = [
                                'column'    => $col,
                                'old'       => $this->formatDateString($oldVal) ?: ($oldVal ?: '(Kosong)'),
                                'new'       => $this->formatDateString($newVal) ?: ($newVal ?: '(Kosong)'),
                                'raw_old'   => $oldVal,
                                'raw_new'   => $newVal,
                            ];
                        }
                    } else if (strtolower($oldVal) !== strtolower($newVal)) {
                        $isColDiff = true;
                        $rowDiffs[] = [
                            'column'    => $col,
                            'old'       => $oldVal ?: '(Kosong)',
                            'new'       => $newVal ?: '(Kosong)',
                            'raw_old'   => $oldVal,
                            'raw_new'   => $newVal,
                        ];
                    }

                    if ($isColDiff) {
                        $hasDiff = true;
                        $columnCounts[$col] = ($columnCounts[$col] ?? 0) + 1;
                    }
                }

                if ($hasDiff) {
                    $changedCount++;
                    if (count($diffSamples) < 30) {
                        $nameAttr = $existingModel->nama_aset ?? $existingModel->nama_bangunan ?? $existingModel->merk ?? $existingModel->pemegang ?? 'Aset Data';
                        $opdInfo = $existingModel->opdRelation?->nama ?? $existingModel->opdRelation?->nama_opd ?? $existingModel->opd ?? '';
                        $subOpdInfo = $existingModel->subOpd?->nama ?? $existingModel->subOpd?->nama_sub_opd ?? $existingModel->pemegang ?? '';

                        $diffSamples[] = [
                            'row'        => $i + 1,
                            'key'        => $keyValue,
                            'name'       => $nameAttr,
                            'opd'        => $opdInfo,
                            'sub_opd'    => $subOpdInfo,
                            'differences'=> $rowDiffs,
                        ];
                    }
                }
            }

            $config = $this->getCategoryConfig($category);
            $fieldBreakdown = [];
            foreach ($columnCounts as $col => $count) {
                $fieldBreakdown[] = [
                    'column' => $col,
                    'label'  => $config['updatable_columns'][$col] ?? $col,
                    'count'  => $count,
                ];
            }

            return response()->json([
                'success'         => true,
                'total_rows'      => $totalRows,
                'matched_count'   => $matchedCount,
                'changed_count'   => $changedCount,
                'new_count'       => $newCount,
                'field_breakdown' => $fieldBreakdown,
                'diff_samples'    => $diffSamples,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses pratinjau perbedaan data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mengeksekusi impor selektif / update kolom yang dicentang.
     */
    public function execute(Request $request): JsonResponse
    {
        $request->validate([
            'import_token'     => 'required|string',
            'matching_key'     => 'required|string',
            'selected_columns' => 'required|array|min:1',
            'asset_category'   => 'required|in:vehicle,ebmd_vehicle,tanah,bangunan',
            'mapping'          => 'required|array',
            'create_new'       => 'nullable|boolean',
        ]);

        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        try {
            $tokenData = Cache::get($request->input('import_token'));
            if (!$tokenData || !Storage::disk('local')->exists($tokenData['file_path'])) {
                return response()->json(['success' => false, 'message' => 'Sesi impor telah kadaluwarsa. Silakan unggah kembali berkas Anda.'], 400);
            }

            $fullPath = Storage::disk('local')->path($tokenData['file_path']);
            $category = $request->input('asset_category');
            $modelClass = $this->getModelClass($category);
            $matchingKey = $request->input('matching_key');
            $selectedColumns = $request->input('selected_columns');
            $mapping = $request->input('mapping');
            $headerRowIndex = (int) $request->input('header_row_index', 0);
            $createNew = (bool) $request->input('create_new', false);

            $reader = IOFactory::createReaderForFile($fullPath);
            $spreadsheet = $reader->load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $updatedCount = 0;
            $insertedCount = 0;
            $skippedCount = 0;

            $startRow = $headerRowIndex + 1;

            for ($i = $startRow; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty(array_filter($row))) continue;

                $keyColIndex = $mapping[$matchingKey] ?? null;
                if ($keyColIndex === null || !isset($row[$keyColIndex])) {
                    $skippedCount++;
                    continue;
                }

                $keyValue = trim((string) $row[$keyColIndex]);
                if (empty($keyValue)) {
                    $skippedCount++;
                    continue;
                }

                // Query database tanpa global scope tenant
                $query = $modelClass::withoutGlobalScopes();

                if ($matchingKey === 'no_polisi') {
                    $cleanKey = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($keyValue));
                    $existingModel = $query->whereRaw("REPLACE(REPLACE(REPLACE(UPPER(no_polisi), ' ', ''), '.', ''), '-', '') = ?", [$cleanKey])->first();
                } else {
                    $existingModel = $query->where($matchingKey, $keyValue)->first();
                    if (!$existingModel) {
                        $cleanKey = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($keyValue));
                        if (!empty($cleanKey)) {
                            $existingModel = $modelClass::withoutGlobalScopes()->whereRaw("REPLACE(REPLACE(REPLACE(UPPER({$matchingKey}), ' ', ''), '.', ''), '-', '') = ?", [$cleanKey])->first();
                        }
                    }
                }

                if ($existingModel) {
                    $updateData = [];
                    foreach ($selectedColumns as $col) {
                        if (!isset($mapping[$col])) continue;
                        $excelColIdx = $mapping[$col];
                        if (!isset($row[$excelColIdx])) continue;

                        $rawVal = trim((string) $row[$excelColIdx]);
                        if (in_array($col, ['nilai_perolehan', 'harga_perolehan', 'luas', 'luas_lantai', 'luas_dasar'])) {
                            $oldVal = trim((string) ($existingModel->$col ?? ''));
                            $refVal = $this->parseNumericToDatabase($oldVal);
                            $updateData[$col] = $this->parseNumericToDatabase($rawVal, $refVal);
                        } else if (in_array($col, ['tanggal_perolehan', 'tgl_perolehan'])) {
                            $updateData[$col] = $this->parseDateToDatabase($rawVal);
                        } else {
                            $updateData[$col] = $rawVal;
                        }
                    }

                    if (!empty($updateData)) {
                        $existingModel->update($updateData);
                        $updatedCount++;
                    }
                } else if ($createNew) {
                    $newData = [];
                    foreach ($mapping as $col => $excelColIdx) {
                        if (isset($row[$excelColIdx])) {
                            $rawVal = trim((string) $row[$excelColIdx]);
                            if (in_array($col, ['nilai_perolehan', 'harga_perolehan', 'luas', 'luas_lantai', 'luas_dasar'])) {
                                $newData[$col] = $this->parseNumericToDatabase($rawVal);
                            } else if (in_array($col, ['tanggal_perolehan', 'tgl_perolehan'])) {
                                $newData[$col] = $this->parseDateToDatabase($rawVal);
                            } else {
                                $newData[$col] = $rawVal;
                            }
                        }
                    }

                    if (!empty($newData)) {
                        $modelClass::create($newData);
                        $insertedCount++;
                    } else {
                        $skippedCount++;
                    }
                } else {
                    $skippedCount++;
                }
            }

            Storage::disk('local')->delete($tokenData['file_path']);
            Cache::forget($request->input('import_token'));

            if (in_array($category, ['vehicle', 'ebmd_vehicle'])) {
                $this->vehicleService->invalidateDashboardStats(invalidateAllOpd: true);
            }

            $columnListStr = implode(', ', $selectedColumns);
            Activity::log("Melakukan Rekonsiliasi e-BMD Kategori ({$category}) secara selektif. Diperbarui: {$updatedCount}, Ditambahkan: {$insertedCount}. Kolom: [{$columnListStr}]", 'success');

            return response()->json([
                'success' => true,
                'message' => "Proses rekonsiliasi data e-BMD selesai! Berhasil memperbarui {$updatedCount} data dan menambahkan {$insertedCount} data baru.",
                'updated_count'  => $updatedCount,
                'inserted_count' => $insertedCount,
                'skipped_count'  => $skippedCount,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengeksekusi rekonsiliasi data: ' . $e->getMessage(),
            ], 500);
        }
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
     * Mendapatkan konfigurasi kolom & matching keys per kategori aset.
     */
    protected function getCategoryConfig(string $category): array
    {
        return match ($category) {
            'tanah' => [
                'updatable_columns' => [
                    'kode_aset'         => 'Kode Aset Tanah',
                    'nama_aset'         => 'Nama Aset',
                    'peruntukan'        => 'Peruntukan / Penggunaan',
                    'luas'              => 'Luas Tanah (m²)',
                    'alamat'            => 'Alamat / Lokasi Tanah',
                    'dasar_perolehan'   => 'Dasar Perolehan',
                    'harga_perolehan'   => 'Harga / Nilai Perolehan',
                    'tanggal_perolehan' => 'Tanggal Perolehan Aset',
                    'keterangan'        => 'Keterangan Tambahan',
                ],
                'matching_keys' => [
                    'kode_aset' => 'Kode Aset Tanah',
                    'nama_aset' => 'Nama Aset',
                ],
            ],
            'bangunan' => [
                'updatable_columns' => [
                    'kode_bangunan'     => 'Kode Bangunan',
                    'kode_barang'       => 'Kode Barang',
                    'nama_bangunan'     => 'Nama Gedung / Bangunan',
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
                'matching_keys' => [
                    'kode_bangunan'  => 'Kode Bangunan',
                    'kode_barang'    => 'Kode Barang',
                    'nama_bangunan'  => 'Nama Bangunan',
                    'nomor_register' => 'Nomor Register',
                ],
            ],
            default => [
                'updatable_columns' => [
                    'no_polisi'       => 'Nomor Polisi (Plat)',
                    'nomor_register'  => 'Nomor Register',
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
                'matching_keys' => [
                    'no_polisi'      => 'Nomor Polisi (Plat)',
                    'nomor_register' => 'Nomor Register',
                    'no_rangka'      => 'Nomor Rangka',
                    'no_mesin'       => 'Nomor Mesin',
                ],
            ],
        };
    }

    /**
     * Merekomendasikan pemetaan kolom (suggested mapping) berbasis sinonim semantik per kategori aset.
     * Mengembalikan associative array [ 'db_column_name' => int_header_index ].
     */
    protected function suggestCategoryColumnMapping(array $headers, string $category): array
    {
        $synonyms = match ($category) {
            'tanah' => [
                'kode_aset'         => ['kode aset', 'kode_aset', 'kode barang', 'kodefikasi', 'nibar', 'nomor kode barang', 'kode tanah', 'penggolongan dan kodefikasi barang'],
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
                'kode_bangunan'     => ['kode bangunan', 'kode_bangunan', 'kode gedung', 'id bangunan'],
                'kode_barang'       => ['kode barang', 'kode_barang', 'penggolongan dan kodefikasi barang', 'kode aset', 'nomor kode barang'],
                'nama_bangunan'     => ['nama bangunan', 'nama gedung', 'nama aset', 'nama barang', 'spesifikasi nama barang', 'uraian nama barang'],
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
                'no_polisi'       => ['no polisi', 'no. polisi', 'nomor polisi', 'plat', 'no plat', 'no. plat', 'nomor plat', 'nopol', 'plat nomor', 'plate', 'plate number'],
                'nomor_register'  => ['nomor register', 'no register', 'no. register', 'nomer register', 'register', 'register number', 'reg number', 'no_register', 'no reg'],
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
     * Memformat string tanggal (misal: 2001-09-12 atau 12/31/1981 atau 31/12/1970) menjadi format tanggal Indonesia yang rapi.
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
     * Memparsing string angka / harga / luas menjadi float murni untuk simpan ke DB.
     * Mendukung deteksi otomatis skala Ribuan (x1.000) dan Jutaan (x1.000.000) dari ekspor e-BMD.
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
            // Uji skala Ribuan (x1.000) - contoh: 493.5 * 1000 = 493500, 575.52 * 1000 = 575520
            if (abs(($val * 1000) - $referenceVal) < 2) {
                return $val * 1000;
            }
            // Uji skala Jutaan (x1.000.000) - contoh: 2.3 * 1000000 = 2300000, 1.47 * 1000000 = 1470000
            if (abs(($val * 1000000) - $referenceVal) < 2) {
                return $val * 1000000;
            }
            // Toleransi estimasi (jika nilai e-BMD dibulatkan)
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
     * Memparsing string tanggal (US MM/DD/YYYY, ID DD/MM/YYYY, YYYY-MM-DD) secara akurat menjadi format MySQL YYYY-MM-DD.
     */
    protected function parseDateToDatabase(string $rawVal): ?string
    {
        if (empty($rawVal) || $rawVal === '(Kosong)') return null;

        $str = trim($rawVal);

        try {
            // Check numeric/slash pattern (e.g. 12/31/1981 or 31/12/1970)
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $str, $m)) {
                $n1 = (int) $m[1];
                $n2 = (int) $m[2];
                $year = (int) $m[3];

                if ($n1 > 12) {
                    // n1 is Day -> DD/MM/YYYY
                    return sprintf('%04d-%02d-%02d', $year, $n2, $n1);
                } elseif ($n2 > 12) {
                    // n2 is Day -> MM/DD/YYYY
                    return sprintf('%04d-%02d-%02d', $year, $n1, $n2);
                } else {
                    // Default to ID format DD/MM/YYYY
                    return sprintf('%04d-%02d-%02d', $year, $n2, $n1);
                }
            }

            // Check ISO pattern (e.g. 1981-12-31)
            if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $str, $m)) {
                return sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
            }

            // Textual month format (e.g. 12-Jul-1983)
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

