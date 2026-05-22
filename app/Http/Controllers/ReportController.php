<?php

namespace App\Http\Controllers;

use App\Models\Opd;
use App\Enums\UserRole;
use App\Reports\ReportRegistry;
use App\Services\ReportService;
use App\Http\Requests\ReportFilterRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Exports\DynamicQueryReportExport;
use App\Exports\DynamicCollectionReportExport;
use App\Reports\Contracts\PostProcessesReportRows;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller untuk Manajemen Laporan & Ekspor (Modul Laporan Modular)
 * 
 * Mengelola antarmuka dashboard laporan, penarikan pratinjau AJAX HTML parsial,
 * ekspor berkas Excel dinamis, dan pencetakan dokumen ramah printer.
 */
class ReportController extends Controller implements HasMiddleware
{
    /**
     * Layanan pemrosesan bisnis laporan.
     */
    protected ReportService $reportService;

    /**
     * Registry untuk tipe strategi laporan.
     */
    protected ReportRegistry $registry;

    /**
     * Injeksi dependensi ReportService dan ReportRegistry.
     */
    public function __construct(ReportService $reportService, ReportRegistry $registry)
    {
        $this->reportService = $reportService;
        $this->registry = $registry;
    }

    /**
     * Pendaftaran middleware Laravel 12 terstandarisasi untuk proteksi akses.
     * 
     * @return array
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    /**
     * Menampilkan dashboard utama Modul Laporan lengkap dengan ringkasan metrik.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $isOpd = $user->role->value === 'opd';

        // 1. Dapatkan OPD ID (Kunci bagi OPD, Null untuk global bagi Admin/Superadmin)
        $opdId = $isOpd ? $user->opd_id : null;

        // 2. Tarik ringkasan statistik (Tunggal & Ter-cache)
        $summary = $this->reportService->getQuickSummary($opdId);

        // 3. Persiapkan pilihan OPD khusus untuk Admin / Superadmin
        $opds = !$isOpd ? Opd::orderBy('nama')->get() : collect();

        // 4. Dapatkan daftar tipe laporan yang didukung oleh sistem
        $reportTypes = $this->registry->getSupportedTypes();

        return view('reports.index', compact('summary', 'opds', 'reportTypes', 'isOpd'));
    }

    /**
     * Menampilkan pratinjau (preview) laporan secara dinamis (mengembalikan parsial HTML via AJAX).
     *
     * @param \App\Http\Requests\ReportFilterRequest $request
     * @return \Illuminate\View\View|\Illuminate\Http\Response
     */
    public function preview(ReportFilterRequest $request)
    {
        // Jalankan logika penarikan data terpaginasi
        $previewData = $this->reportService->generatePreview($request->validated());

        // Jika request menginginkan Ajax/Parsial HTML
        if ($request->ajax() || $request->wantsJson()) {
            return view('reports.partials.preview-table', $previewData);
        }

        // Fallback jika diakses langsung (seperti non-AJAX)
        return view('reports.partials.preview-table', $previewData);
    }

    /**
     * Mengekspor laporan dinamis ke format Excel (.xlsx).
     *
     * @param \App\Http\Requests\ReportFilterRequest $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(ReportFilterRequest $request)
    {
        $filters = $request->validated();
        $type = $filters['type'] ?? 'status';

        // 1. Selesaikan strategi laporan via registry
        $strategy = $this->registry->resolve($type);

        // 2. Susun nama berkas unduhan yang bersih
        $filename = 'laporan_' . $type . '_' . now()->format('Ymd_His') . '.xlsx';

        // 3. Ambil judul laporan pendukung
        $reportTitle = $this->registry->getSupportedTypes()[$type] ?? 'Laporan Kendaraan';

        // 4. Muat konfigurasi dokumen dinamis dari database
        $docSettingService = app(\App\Services\ReportDocumentSettingService::class);
        $docSettings = $docSettingService->getSettingsForReportType($type);

        // 5. Jika strategi mengimplementasikan pengayaan data (PostProcessesReportRows), gunakan ekspor berbasis Koleksi
        if ($strategy instanceof PostProcessesReportRows) {
            $query = $strategy->query($filters);
            $query = $this->reportService->applySorting($query, $filters);
            $data = $query->get();

            $refQuery = method_exists($strategy, 'referenceQuery')
                ? $strategy->referenceQuery($filters)
                : $strategy->query($filters);
            $refQuery = $this->reportService->applySorting($refQuery, $filters);
            $referenceRows = $refQuery->get();

            $strategy->postProcess($data, $referenceRows);

            return Excel::download(
                new DynamicCollectionReportExport($data, $strategy->headers(), $filters, $reportTitle, $docSettings),
                $filename
            );
        }

        // 6. Jika strategi standar, gunakan kueri streaming (FromQuery) hemat memori untuk data besar
        $query = $strategy->query($filters);
        $query = $this->reportService->applySorting($query, $filters);
        return Excel::download(
            new DynamicQueryReportExport($query, $strategy->headers(), $filters, $reportTitle, $docSettings),
            $filename
        );
    }

    /**
     * Membuka halaman pratinjau bersih khusus cetak printer / ekspor PDF ramah browser.
     *
     * @param \App\Http\Requests\ReportFilterRequest $request
     * @return \Illuminate\View\View
     */
    public function print(ReportFilterRequest $request)
    {
        $filters = $request->validated();
        $type = $filters['type'] ?? 'status';

        // 1. Selesaikan strategi
        $strategy = $this->registry->resolve($type);

        // 2. Tarik data (tanpa paginasi untuk cetak, agar seluruh data keluar di kertas)
        $query = $strategy->query($filters);
        $query = $this->reportService->applySorting($query, $filters);
        $data = $query->get();

        // 3. Jalankan pengayaan data jika strategi mengimplementasikan PostProcessesReportRows
        if ($strategy instanceof PostProcessesReportRows) {
            $refQuery = method_exists($strategy, 'referenceQuery')
                ? $strategy->referenceQuery($filters)
                : $strategy->query($filters);
            $refQuery = $this->reportService->applySorting($refQuery, $filters);
            $referenceRows = $refQuery->get();

            $strategy->postProcess($data, $referenceRows);
        }

        // 4. Deskripsi tipe laporan
        $reportTitle = $this->registry->getSupportedTypes()[$type] ?? 'Laporan Kendaraan';

        // 5. Muat konfigurasi dokumen dinamis
        $docSettingService = app(\App\Services\ReportDocumentSettingService::class);
        $docSettings = $docSettingService->getSettingsForReportType($type);

        return view('reports.print', [
            'data'        => $data,
            'headers'     => $strategy->headers(),
            'reportTitle' => $reportTitle,
            'filters'     => $filters,
            'docSettings' => $docSettings,
        ]);
    }

    /**
     * Mengunduh berkas laporan dalam format PDF menggunakan mPDF (Server-Side).
     *
     * @param \App\Http\Requests\ReportFilterRequest $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\RedirectResponse
     */
    public function pdf(ReportFilterRequest $request)
    {
        $filters = $request->validated();
        $type = $filters['type'] ?? 'status';

        // 1. Selesaikan strategi
        $strategy = $this->registry->resolve($type);

        // 2. Mencegah overload memori produksi (Data Guard > 1000 baris)
        $query = $strategy->query($filters);
        $query = $this->reportService->applySorting($query, $filters);
        $count = $query->count();
        if ($count > 1000) {
            return redirect()->route('reports.index')->with('error', 'Jumlah data mencapai ' . number_format($count) . ' baris. Demi menjaga stabilitas server, ekspor lebih dari 1.000 data wajib menggunakan format Excel.');
        }

        // 3. Tarik data kueri
        $data = $query->get();

        // 4. Jalankan pengayaan data jika strategi mengimplementasikan PostProcessesReportRows
        if ($strategy instanceof PostProcessesReportRows) {
            $refQuery = method_exists($strategy, 'referenceQuery')
                ? $strategy->referenceQuery($filters)
                : $strategy->query($filters);
            $refQuery = $this->reportService->applySorting($refQuery, $filters);
            $referenceRows = $refQuery->get();

            $strategy->postProcess($data, $referenceRows);
        }

        // 5. Deskripsi tipe laporan
        $reportTitle = $this->registry->getSupportedTypes()[$type] ?? 'Laporan Kendaraan';

        // 6. Muat konfigurasi dokumen dinamis dari database
        $docSettingService = app(\App\Services\ReportDocumentSettingService::class);
        $docSettings = $docSettingService->getSettingsForReportType($type);

        // 7. Alokasi memori dinamis dan batas waktu untuk keamanan proses mPDF
        ini_set('memory_limit', '512M');
        set_time_limit(120);
        ini_set('pcre.backtrack_limit', '10000000');

        // 8. Siapkan berkas temporer mPDF
        $tempDir = storage_path('app/public/mpdf_temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // 9. Inisiasi Engine mPDF dengan optimasi RAM produksi
        $paperSize = $docSettings['settings']['paper_size'] ?? 'A4';
        $orientation = $docSettings['settings']['orientation'] ?? 'L';
        $mpdfPaperSize = $paperSize === 'F4' ? 'FOLIO' : $paperSize;
        $mpdfFormat = $mpdfPaperSize . ($orientation === 'L' ? '-L' : '');

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => $mpdfFormat,
            'margin_top' => 12,
            'margin_bottom' => 12,
            'margin_left' => 12,
            'margin_right' => 12,
            'tempDir' => $tempDir,
            'simpleTables' => true,
            'packTableData' => true,
        ]);

        // 10. Render tampilan Blade khusus PDF menjadi HTML string
        $html = view('reports.pdf', [
            'data'        => $data,
            'headers'     => $strategy->headers(),
            'reportTitle' => $reportTitle,
            'filters'     => $filters,
            'docSettings' => $docSettings,
        ])->render();

        // 11. Konversi HTML ke dokumen PDF
        $mpdf->WriteHTML($html);

        // 12. Susun nama file
        $filename = 'laporan_' . $type . '_' . now()->format('Ymd_His') . '.pdf';

        // 13. Tampilkan PDF secara inline di browser (untuk pratinjau sebelum diunduh/dicetak)
        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
