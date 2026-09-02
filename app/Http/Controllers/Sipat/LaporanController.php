<?php

namespace App\Http\Controllers\Sipat;

use App\Http\Controllers\Controller;
use App\Models\OpdSipat;
use App\Models\StatusProses;
use App\Services\LaporanService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller implements HasMiddleware
{
    protected $laporanService;

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function __construct(LaporanService $laporanService)
    {
        $this->laporanService = $laporanService;
    }

    public function index(Request $request)
    {
        $filters = $this->laporanService->getFilters($request->all());
        $opdList = OpdSipat::where('aktif', 1)->orderBy('nama', 'asc')->get();
        $statusList = StatusProses::orderBy('urutan', 'asc')->get();

        $reportTitles = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('report_titles')) {
            $reportTitles = DB::table('report_titles')
                ->where('aktif', 1)
                ->orderBy('judul', 'asc')
                ->get();
        }

        if ($reportTitles->isEmpty()) {
            $reportTitles = collect([
                (object)['id' => 1, 'judul' => 'LAPORAN REKAPITULASI ASET TANAH KABUPATEN DONGGALA'],
                (object)['id' => 2, 'judul' => 'LAPORAN ASET TANAH BELUM DIPROSES PENSERTIFIKATAN'],
                (object)['id' => 3, 'judul' => 'LAPORAN ASET TANAH DALAM PROSES PENSERTIFIKATAN BPN'],
                (object)['id' => 4, 'judul' => 'LAPORAN ASET TANAH SUDAH BERSERTIFIKAT'],
                (object)['id' => 5, 'judul' => 'LAPORAN ASET TANAH BERMASALAH / SENGKETA'],
                (object)['id' => 6, 'judul' => 'LAPORAN REKAPITULASI ASET TANAH BELUM BERSERTIFIKAT (GABUNGAN)'],
            ]);
        }

        $query = $this->laporanService->buildQuery($filters);
        $rows = $query->get();
        $summary = $this->laporanService->buildSummary($rows, $filters);

        $queryString = http_build_query(array_filter($filters));
        $exportQueryString = $queryString ? '?' . $queryString : '';

        $kecamatanList = \App\Models\Kecamatan::orderBy('nama', 'asc')->get();

        return view('sipat.laporan.index', compact(
            'filters',
            'opdList',
            'kecamatanList',
            'statusList',
            'reportTitles',
            'rows',
            'summary',
            'exportQueryString'
        ));
    }

    public function exportCsv(Request $request)
    {
        return $this->exportXlsx($request);
    }

    public function exportXlsx(Request $request)
    {
        $filters = $this->laporanService->getFilters($request->all());
        $rows = $this->laporanService->buildQuery($filters)->get();
        $summary = $this->laporanService->buildSummary($rows, $filters);
        $kop = $this->laporanService->getKopSettings();
        $selectedTitle = $this->laporanService->resolveReportTitle($filters);

        return $this->laporanService->exportExcel($rows, $filters, $summary, $kop, $selectedTitle);
    }

    public function previewPdf(Request $request)
    {
        return $this->renderReportPdf($request, false);
    }

    public function downloadPdf(Request $request)
    {
        return $this->renderReportPdf($request, true);
    }

    private function renderReportPdf(Request $request, bool $download)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        $filters = $this->laporanService->getFilters($request->all());
        $rows = $this->laporanService->buildQuery($filters)->get();
        $summary = $this->laporanService->buildSummary($rows, $filters);
        $kop = $this->laporanService->getKopSettings();
        $selectedTitle = $this->laporanService->resolveReportTitle($filters);

        $pdfView = view('sipat.laporan.print_pdf', compact('rows', 'filters', 'summary', 'kop', 'selectedTitle'))->render();
        
        if (!class_exists(\Mpdf\Mpdf::class)) {
            // Fallback to HTML if mPDF is not installed
            if ($download) {
                return response($pdfView)
                    ->header('Content-Type', 'text/html')
                    ->header('Content-Disposition', 'attachment; filename="Laporan_Aset_Tanah_' . date('Ymd_His') . '.html"');
            }
            return response($pdfView);
        }

        $pdfTempDir = storage_path('framework/cache/mpdf');
        if (!is_dir($pdfTempDir)) {
            @mkdir($pdfTempDir, 0775, true);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L', // Landscape for wide tables
            'margin_top' => 10,
            'margin_bottom' => 26,
            'margin_left' => 10,
            'margin_right' => 10,
            'tempDir' => $pdfTempDir,
        ]);
        
        $mpdf->SetTitle($selectedTitle);
        $mpdf->SetHTMLFooter('<div style="font-size:9pt;color:#64748b;border-top:1px solid #dbe3ef;padding-top:6px;text-align:center;">Halaman {PAGENO} dari {nbpg} | ' . htmlspecialchars((string) ($kop['kop_footer'] ?? ''), ENT_QUOTES, 'UTF-8') . '</div>');
        $mpdf->WriteHTML($pdfView);
        
        $filename = 'Laporan_Aset_Tanah_' . date('Ymd_His') . '.pdf';
        
        if ($download) {
            return response($mpdf->Output($filename, 'S'))
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        }
        
        return response($mpdf->Output($filename, 'S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }
}
