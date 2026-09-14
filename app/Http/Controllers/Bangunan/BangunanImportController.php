<?php

namespace App\Http\Controllers\Bangunan;

use App\Http\Controllers\Controller;
use App\Services\Bangunan\BangunanImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class BangunanImportController extends Controller implements HasMiddleware
{
    protected BangunanImportService $importService;

    public function __construct(BangunanImportService $importService)
    {
        $this->importService = $importService;
    }

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    /**
     * Membaca file Excel yang diunggah dan mengembalikan preview pemetaan kolom AI.
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ], [
            'file.required' => 'Pilih berkas Excel terlebih dahulu.',
            'file.mimes'    => 'Format berkas harus .xlsx, .xls, atau .csv.',
            'file.max'      => 'Ukuran berkas maksimal 20 MB.',
        ]);

        try {
            $preview = $this->importService->generateImportPreview(
                $request->file('file'),
                auth()->user()
            );

            return response()->json($preview);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca berkas Excel: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Menjalankan proses impor massal berdasarkan pemetaan yang disetujui pengguna.
     */
    public function execute(Request $request): JsonResponse
    {
        $request->validate([
            'import_token'     => 'required|string',
            'mapping'          => 'required|array',
            'headers'          => 'required|array',
            'header_row_index' => 'required|integer',
        ]);

        try {
            $this->importService->executeSmartImport(
                $request->input('import_token'),
                $request->input('mapping'),
                $request->input('headers'),
                (int) $request->input('header_row_index'),
                auth()->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Data bangunan berhasil diimpor ke dalam sistem!',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimpor data: ' . $e->getMessage(),
            ], 422);
        }
    }
}
