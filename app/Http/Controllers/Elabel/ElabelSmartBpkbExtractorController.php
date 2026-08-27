<?php

namespace App\Http\Controllers\Elabel;

use App\Http\Controllers\Controller;
use App\Models\Elabel\ElabelBpkb;
use App\Models\Elabel\ElabelActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ElabelSmartBpkbExtractorController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    /**
     * Halaman terpisah khusus Smart BPKB PDF Folder Scanner.
     */
    public function index(Request $request): View
    {
        $vehicleType = strtoupper(trim((string) $request->get('type')));
        if (!in_array($vehicleType, ['R2', 'R4'], true)) {
            $vehicleType = null;
        }

        $query = ElabelBpkb::where('status', '!=', 'Dihapus');
        if ($vehicleType) {
            $query->where(function ($q) use ($vehicleType) {
                $q->where('vehicle_type', $vehicleType)
                  ->orWhere('vehicle_type', strtolower($vehicleType))
                  ->orWhere('vehicle_type', $vehicleType === 'R4' ? 'mobil' : 'motor');
            });
        }

        $totalBpkb = (clone $query)->count();
        $bpkbWithPdf = (clone $query)->whereNotNull('pdf_path')->where('pdf_path', '!=', '')->count();
        $bpkbWithoutPdf = $totalBpkb - $bpkbWithPdf;

        $defaultFolder = storage_path('app/public/bpkb_scans');
        if (!file_exists($defaultFolder)) {
            @mkdir($defaultFolder, 0777, true);
        }

        return view('elabel.bpkb.smart_extractor', compact(
            'totalBpkb',
            'bpkbWithPdf',
            'bpkbWithoutPdf',
            'defaultFolder',
            'vehicleType'
        ));
    }

    /**
     * Memindai folder lokal server (Dry-Run Audit) terpisah untuk Motor (R2) dan Mobil (R4).
     */
    public function scan(Request $request): JsonResponse
    {
        $request->validate([
            'folder_path'  => 'required|string',
            'vehicle_type' => 'nullable|string|in:ALL,R2,R4',
        ]);

        $folderPath = trim($request->input('folder_path'));
        $vehicleType = strtoupper(trim($request->input('vehicle_type', 'ALL')));

        if (!str_starts_with($folderPath, '/')) {
            $folderPath = storage_path('app/' . ltrim($folderPath, '/'));
        }

        if (!file_exists($folderPath) || !is_dir($folderPath)) {
            return response()->json([
                'success' => false,
                'message' => "Folder tidak ditemukan di lokasi server: '{$folderPath}'. Pastikan path folder sudah benar."
            ]);
        }

        try {
            // 1. Kumpulkan seluruh file PDF dari folder lokal tersebut
            $allPdfPaths = array_unique(array_merge(
                $this->rglob(rtrim($folderPath, '/') . '/*.pdf'),
                $this->rglob(rtrim($folderPath, '/') . '/*.PDF')
            ));

            if (empty($allPdfPaths)) {
                return response()->json([
                    'success' => false,
                    'message' => "Tidak ditemukan berkas format .PDF di dalam folder: '{$folderPath}'."
                ]);
            }

            // 2. Fetch data BPKB dari DB terpisah berdasarkan jenis kendaraan (Motor R2 / Mobil R4)
            $dbQuery = ElabelBpkb::with('box')->where('status', '!=', 'Dihapus');

            if ($vehicleType === 'R2') {
                $dbQuery->where(function ($q) {
                    $q->where('vehicle_type', 'R2')
                      ->orWhere('vehicle_type', 'r2')
                      ->orWhere('vehicle_type', 'motor')
                      ->orWhere('vehicle_type', 'Motor');
                });
            } elseif ($vehicleType === 'R4') {
                $dbQuery->where(function ($q) {
                    $q->where('vehicle_type', 'R4')
                      ->orWhere('vehicle_type', 'r4')
                      ->orWhere('vehicle_type', 'mobil')
                      ->orWhere('vehicle_type', 'Mobil');
                });
            }

            $allDbBpkb = $dbQuery->get();

            $dbMapByCleanPlate = [];
            foreach ($allDbBpkb as $b) {
                $cleanP = $this->cleanString($b->plate_number);
                if ($cleanP !== '') {
                    $dbMapByCleanPlate[$cleanP][] = $b;
                }
            }

            $auditResults = [
                'valid'     => [],   // 🟢 100% Persis Nopol & Belum Memiliki PDF
                'duplicate' => [],   // 🟡 Berkas Ganda di dalam folder
                'exists'    => [],   // 🔵 Nopol Cocok tapi DB Sudah Punya PDF
                'unmatched' => [],   // 🔴 Nopol Tidak Ditemukan di DB / Tidak Terbaca
            ];

            $seenPlatesInFolder = [];

            foreach ($allPdfPaths as $pdfPath) {
                $filename = basename($pdfPath);
                $fileSize = filesize($pdfPath);

                // Pembacaan Isi Dalam PDF + Fallback Nama File
                $pdfText = $this->extractTextFromPdf($pdfPath);
                $extractedData = $this->parseBpkbText($pdfText, $filename);
                $extractedPlate = $extractedData['plate'];
                $cleanExtractedPlate = $this->cleanString($extractedPlate);

                if (empty($cleanExtractedPlate)) {
                    $auditResults['unmatched'][] = [
                        'filename'        => $filename,
                        'file_path'       => $pdfPath,
                        'file_size'       => $this->formatBytes($fileSize),
                        'extracted_plate' => 'TIDAK TERBACA',
                        'vehicle_type'    => $vehicleType,
                        'reason'          => 'Plat Nomor tidak ditemukan di dalam teks PDF maupun nama file.',
                        'status'          => 'UNMATCHED'
                    ];
                    continue;
                }

                // Cek ganda di dalam folder lokal ini
                if (isset($seenPlatesInFolder[$cleanExtractedPlate])) {
                    $auditResults['duplicate'][] = [
                        'filename'        => $filename,
                        'file_path'       => $pdfPath,
                        'file_size'       => $this->formatBytes($fileSize),
                        'extracted_plate' => $extractedPlate,
                        'vehicle_type'    => $vehicleType,
                        'reason'          => "Terdapat lebih dari 1 file PDF yang mengacu ke Nopol '{$extractedPlate}' di dalam folder ini (Konflik berkas ganda).",
                        'status'          => 'DUPLICATE'
                    ];
                    continue;
                }
                $seenPlatesInFolder[$cleanExtractedPlate] = $filename;

                // ATURAN 1 & 2: Cek di Database (Must match EXACTLY)
                $matchedRecords = $dbMapByCleanPlate[$cleanExtractedPlate] ?? [];

                if (empty($matchedRecords)) {
                    $catLabel = $vehicleType === 'R2' ? 'Motor (R2)' : ($vehicleType === 'R4' ? 'Mobil (R4)' : 'Semua');
                    $auditResults['unmatched'][] = [
                        'filename'        => $filename,
                        'file_path'       => $pdfPath,
                        'file_size'       => $this->formatBytes($fileSize),
                        'extracted_plate' => $extractedPlate,
                        'vehicle_type'    => $vehicleType,
                        'reason'          => "Nopol '{$extractedPlate}' tidak terdaftar pada kategori {$catLabel} di database eLABEL.",
                        'status'          => 'UNMATCHED'
                    ];
                } elseif (count($matchedRecords) > 1) {
                    $auditResults['duplicate'][] = [
                        'filename'        => $filename,
                        'file_path'       => $pdfPath,
                        'file_size'       => $this->formatBytes($fileSize),
                        'extracted_plate' => $extractedPlate,
                        'vehicle_type'    => $vehicleType,
                        'reason'          => "Ditemukan " . count($matchedRecords) . " data BPKB ganda dengan Nopol yang sama di database. Perlu resolusi manual.",
                        'status'          => 'DUPLICATE'
                    ];
                } else {
                    $targetBpkb = $matchedRecords[0];
                    $vLabel = strtoupper($targetBpkb->vehicle_type) === 'R2' ? '🏍️ Motor (R2)' : '🚗 Mobil (R4)';

                    // Cek apakah sudah memiliki PDF di DB
                    if (!empty($targetBpkb->pdf_path) && Storage::disk('public')->exists($targetBpkb->pdf_path)) {
                        $auditResults['exists'][] = [
                            'filename'        => $filename,
                            'file_path'       => $pdfPath,
                            'file_size'       => $this->formatBytes($fileSize),
                            'extracted_plate' => $targetBpkb->plate_number,
                            'bpkb_id'         => $targetBpkb->id,
                            'existing_pdf'    => $targetBpkb->pdf_path,
                            'box_code'        => $targetBpkb->box->box_code ?? '-',
                            'vehicle_label'   => $vLabel,
                            'reason'          => "Record BPKB {$vLabel} Nopol '{$targetBpkb->plate_number}' sudah memiliki berkas PDF di database.",
                            'status'          => 'EXISTS'
                        ];
                    } else {
                        // 🟢 VALID 100%
                        $auditResults['valid'][] = [
                            'filename'        => $filename,
                            'file_path'       => $pdfPath,
                            'file_size'       => $this->formatBytes($fileSize),
                            'extracted_plate' => $targetBpkb->plate_number,
                            'bpkb_id'         => $targetBpkb->id,
                            'box_code'        => $targetBpkb->box->box_code ?? '-',
                            'no_bpkb'         => $targetBpkb->no_bpkb ?? '-',
                            'year'            => $targetBpkb->year ?? '-',
                            'vehicle_label'   => $vLabel,
                            'reason'          => "Nopol cocok 100% dengan record {$vLabel} ID #{$targetBpkb->id} dan siap ditautkan.",
                            'status'          => 'VALID'
                        ];
                    }
                }
            }

            return response()->json([
                'success'     => true,
                'folder_path' => $folderPath,
                'vehicle_type'=> $vehicleType,
                'summary'     => [
                    'total'     => count($allPdfPaths),
                    'valid'     => count($auditResults['valid']),
                    'duplicate' => count($auditResults['duplicate']),
                    'exists'    => count($auditResults['exists']),
                    'unmatched' => count($auditResults['unmatched']),
                ],
                'results'     => $auditResults
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memindai folder PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eksekusi penautan berkas PDF dari folder lokal secara langsung.
     */
    public function execute(Request $request): JsonResponse
    {
        $request->validate([
            'valid_items' => 'required|array',
        ]);

        $validItems = $request->input('valid_items', []);

        if (empty($validItems)) {
            return response()->json(['success' => false, 'message' => 'Tidak ada berkas valid yang dipilih untuk ditautkan.']);
        }

        $linkedCount = 0;
        $failedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($validItems as $item) {
                $bpkbId = (int) ($item['bpkb_id'] ?? 0);
                $filePath = $item['file_path'] ?? '';

                if ($bpkbId <= 0 || !file_exists($filePath)) {
                    $failedCount++;
                    continue;
                }

                $bpkb = ElabelBpkb::find($bpkbId);
                if (!$bpkb || $bpkb->status === 'Dihapus') {
                    $failedCount++;
                    continue;
                }

                // Subfolder terpisah berdasarkan jenis kendaraan (motor / mobil) untuk log audit
                $subFolder = strtoupper($bpkb->vehicle_type) === 'R2' ? 'motor' : 'mobil';

                $cleanPlate = $this->filenameToken($bpkb->plate_number);
                $boxCode = $bpkb->box->box_code ?? 'BOX';
                $cleanBox = $this->filenameToken($boxCode);
                $year = $bpkb->year ?? date('Y');

                $newFilename = "{$cleanPlate}_{$year}_{$cleanBox}.pdf";
                $targetStoragePath = "elabel/bpkb/{$newFilename}";

                $counter = 2;
                while (Storage::disk('public')->exists($targetStoragePath)) {
                    $targetStoragePath = "elabel/bpkb/{$cleanPlate}_{$year}_{$cleanBox}_{$counter}.pdf";
                    $counter++;
                }

                // Copy file dari folder lokal ke public storage
                $destFullPath = storage_path('app/public/' . $targetStoragePath);
                $destDir = dirname($destFullPath);
                if (!file_exists($destDir)) {
                    @mkdir($destDir, 0777, true);
                }

                $copySuccess = @copy($filePath, $destFullPath);
                if (!$copySuccess || !file_exists($destFullPath)) {
                    $failedCount++;
                    continue;
                }

                // Update database record
                $bpkb->update([
                    'pdf_path' => $targetStoragePath,
                    'status'   => 'Tersedia',
                ]);

                // Audit Log
                ElabelActivityLog::create([
                    'user_id'        => Auth::id() ?: 1,
                    'action'         => 'update',
                    'module'         => 'BPKB_SMART_EXTRACTOR',
                    'description'    => "Smart Extractor ({$subFolder}): Menautkan berkas PDF '{$item['filename']}' dari folder lokal ke BPKB Plat '{$bpkb->plate_number}' (ID #{$bpkb->id})",
                    'reference_type' => 'bpkb',
                    'reference_id'   => $bpkb->id,
                    'ip_address'     => $request->ip(),
                    'user_agent'     => $request->userAgent(),
                    'created_at'     => now(),
                ]);

                $linkedCount++;
            }

            DB::commit();

            return response()->json([
                'success'      => true,
                'message'      => "Berhasil menautkan {$linkedCount} berkas PDF BPKB dari folder lokal secara terpisah ke database eLABEL!",
                'linked_count' => $linkedCount,
                'failed_count' => $failedCount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan penautan berkas PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    private function extractTextFromPdf(string $pdfPath): string
    {
        $text = '';

        if (function_exists('exec')) {
            $cmd = "pdftotext -q " . escapeshellarg($pdfPath) . " - 2>&1";
            exec($cmd, $out, $ret);
            if ($ret === 0 && !empty($out)) {
                $text = implode("\n", $out);
            }
        }

        if (trim($text) === '' && file_exists($pdfPath)) {
            $content = @file_get_contents($pdfPath);
            if ($content !== false) {
                if (preg_match_all('/(?:\(([^\(\)]*)\)|<([0-9a-fA-F]+)>)/s', $content, $matches)) {
                    $collected = [];
                    foreach ($matches[1] as $m) {
                        if (!empty($m)) $collected[] = $m;
                    }
                    $text = implode(' ', $collected);
                }
            }
        }

        return $text;
    }

    /**
     * Pratinjau file PDF dari folder lokal server di tab baru.
     */
    public function previewPdf(Request $request)
    {
        $filePath = $request->query('path', '');
        if (empty($filePath) || !file_exists($filePath) || !is_file($filePath) || strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) !== 'pdf') {
            abort(404, 'File PDF tidak ditemukan.');
        }
        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"',
        ]);
    }

    private function parseBpkbText(string $pdfText, string $filename): array
    {
        $plate = null;
        $prefixes = 'DN|DD|DW|DA|DB|DC|DL|DM|DT';

        if (preg_match('/(' . $prefixes . ')\s*(\d{1,4})\s*([A-Z]{1,3})/i', $pdfText, $m)) {
            $plate = strtoupper(trim($m[1])) . " " . trim($m[2]) . " " . strtoupper(trim($m[3]));
        }

        if (!$plate && preg_match('/(' . $prefixes . ')[_\s\-]*(\d{1,4})[_\s\-]*([A-Z]{1,3})/i', $filename, $m)) {
            $plate = strtoupper(trim($m[1])) . " " . trim($m[2]) . " " . strtoupper(trim($m[3]));
        }

        if (!$plate && preg_match('/((' . $prefixes . ')\d{1,4}[A-Z]{1,3})/i', $this->cleanString($filename), $m)) {
            $raw = strtoupper($m[1]);
            if (preg_match('/(' . $prefixes . ')(\d+)([A-Z]+)/', $raw, $m2)) {
                $plate = $m2[1] . " " . $m2[2] . " " . $m2[3];
            }
        }

        return [
            'plate' => $plate,
        ];
    }

    private function filenameToken(string $value): string
    {
        $value = strtolower(trim($value));
        return preg_replace('/[^a-z0-9]+/', '', $value) ?: '';
    }

    private function cleanString(?string $str): string
    {
        if ($str === null) return '';
        $s = strtoupper(trim($str));
        return preg_replace('/[^A-Z0-9]/', '', $s);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }

    private function rglob(string $pattern, int $flags = 0): array
    {
        $files = glob($pattern, $flags) ?: [];
        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, $this->rglob($dir . '/' . basename($pattern), $flags));
        }
        return $files;
    }
}
