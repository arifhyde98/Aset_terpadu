<?php

namespace App\Http\Controllers\Sipat;

use App\Http\Controllers\Controller;
use App\Models\OpdSipat;
use App\Models\StatusProses;
use App\Services\LaporanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    protected $laporanService;

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
                (object)['id' => 2, 'judul' => 'LAPORAN DAFTAR KIB A TANAH PEMERINTAH KABUPATEN DONGGALA'],
                (object)['id' => 3, 'judul' => 'LAPORAN PROGRES PENSERTIFIKATAN BPN ASET TANAH'],
            ]);
        }

        $query = $this->laporanService->buildQuery($filters);
        $rows = $query->get();
        $summary = $this->laporanService->buildSummary($rows, $filters);

        $queryString = http_build_query(array_filter($filters));
        $exportQueryString = $queryString ? '?' . $queryString : '';

        return view('sipat.laporan.index', compact(
            'filters',
            'opdList',
            'statusList',
            'reportTitles',
            'rows',
            'summary',
            'exportQueryString'
        ));
    }

    public function exportCsv(Request $request)
    {
        $filters = $this->laporanService->getFilters($request->all());
        $rows = $this->laporanService->buildQuery($filters)->get();

        $filename = 'Laporan_Aset_Tanah_' . date('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'No', 'Kode Aset (NIBAR)', 'Nama Aset Tanah', 'Peruntukan / Penggunaan',
                'OPD Pengelola', 'Luas (m²)', 'Harga Perolehan (Rp)', 'Tanggal Perolehan',
                'Status BPN Terkini', 'Alamat / Lokasi', 'Keterangan'
            ]);

            foreach ($rows as $index => $row) {
                fputcsv($file, [
                    $index + 1,
                    $row->kode_aset ?? '-',
                    $row->nama_aset ?? '-',
                    $row->peruntukan ?? '-',
                    $row->opd ?? 'BPKAD',
                    $row->luas ?? 0,
                    $row->harga_perolehan ?? 0,
                    $row->tanggal_perolehan ?? '-',
                    $row->latestProses->statusProses->nama_status ?? 'Belum Diurus',
                    $row->alamat ?? '-',
                    $row->keterangan ?? '-',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportXlsx(Request $request)
    {
        $filters = $this->laporanService->getFilters($request->all());
        $rows = $this->laporanService->buildQuery($filters)->get();
        $summary = $this->laporanService->buildSummary($rows, $filters);
        $kop = $this->laporanService->getKopSettings();
        
        $selectedTitle = 'Laporan Aset Tanah';
        if ($filters['title_mode'] === 'manual' && !empty($filters['manual_title'])) {
            $selectedTitle = $filters['manual_title'];
        } elseif (!empty($filters['report_title_id'])) {
            $titleRow = \Illuminate\Support\Facades\DB::table('report_titles')->where('id', $filters['report_title_id'])->first();
            if ($titleRow) {
                $selectedTitle = $titleRow->judul;
            }
        } else {
            $selectedTitle = $kop['kop_nama_laporan_aset'] ?? 'Laporan Aset Tanah';
        }

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

        $selectedTitle = 'LAPORAN REKAPITULASI ASET TANAH';
        if ($filters['title_mode'] === 'manual' && !empty($filters['manual_title'])) {
            $selectedTitle = strtoupper($filters['manual_title']);
        } elseif (!empty($filters['report_title_id'])) {
            $titleRow = DB::table('report_titles')->where('id', $filters['report_title_id'])->first();
            if ($titleRow) {
                $selectedTitle = strtoupper($titleRow->judul);
            }
        }

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
