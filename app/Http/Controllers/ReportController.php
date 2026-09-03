<?php

namespace App\Http\Controllers;

use App\Models\Opd;
use App\Reports\ReportRegistry;
use App\Services\ReportService;
use App\Services\ReportGenerationService;
use App\Http\Requests\ReportFilterRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Http\Request;

/**
 * Controller untuk Manajemen Laporan & Ekspor (Modul Laporan Modular)
 * 
 * Mengelola antarmuka dashboard laporan, penarikan pratinjau AJAX HTML parsial,
 * ekspor berkas Excel dinamis, dan pencetakan dokumen ramah printer.
 */
class ReportController extends Controller implements HasMiddleware
{
    protected ReportService $reportService;
    protected ReportRegistry $registry;
    protected ReportGenerationService $generationService;

    /**
     * Injeksi dependensi ReportService, ReportRegistry, dan ReportGenerationService.
     */
    public function __construct(
        ReportService $reportService,
        ReportRegistry $registry,
        ReportGenerationService $generationService
    ) {
        $this->reportService = $reportService;
        $this->registry = $registry;
        $this->generationService = $generationService;
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
        $summary = $this->reportService->getQuickSummary($opdId, 'real');
        $summaryEbmd = $this->reportService->getQuickSummary($opdId, 'ebmd');

        // 3. Persiapkan pilihan OPD khusus untuk Admin / Superadmin
        $opds = !$isOpd ? Opd::orderBy('nama')->get() : collect();

        // 4. Dapatkan daftar tipe laporan yang didukung oleh sistem
        $reportTypes = $this->registry->getSupportedTypes();

        return view('reports.index', compact('summary', 'summaryEbmd', 'opds', 'reportTypes', 'isOpd'));
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
        return $this->generationService->exportToExcel($request->validated());
    }

    /**
     * Membuka halaman pratinjau bersih khusus cetak printer / ekspor PDF ramah browser.
     *
     * @param \App\Http\Requests\ReportFilterRequest $request
     * @return \Illuminate\View\View
     */
    public function print(ReportFilterRequest $request)
    {
        $printData = $this->generationService->getPrintData($request->validated());

        return view('reports.print', $printData);
    }

    /**
     * Mengunduh berkas laporan dalam format PDF menggunakan mPDF (Server-Side).
     *
     * @param \App\Http\Requests\ReportFilterRequest $request
     * @return \Symfony\Component\HttpFoundation\Response|\Illuminate\Http\RedirectResponse
     */
    public function pdf(ReportFilterRequest $request)
    {
        return $this->generationService->generatePdfResponse($request->validated());
    }
}
