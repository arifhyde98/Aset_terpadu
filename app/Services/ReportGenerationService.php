<?php

namespace App\Services;

use App\Reports\ReportRegistry;
use App\Services\ReportService;
use App\Services\ReportDocumentSettingService;
use App\Exports\DynamicQueryReportExport;
use App\Exports\DynamicCollectionReportExport;
use App\Reports\Contracts\PostProcessesReportRows;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\RedirectResponse;
use Mpdf\Mpdf;

class ReportGenerationService
{
    protected $reportService;
    protected $registry;
    protected $docSettingService;

    public function __construct(
        ReportService $reportService,
        ReportRegistry $registry,
        ReportDocumentSettingService $docSettingService
    ) {
        $this->reportService = $reportService;
        $this->registry = $registry;
        $this->docSettingService = $docSettingService;
    }

    /**
     * Mengekspor laporan dinamis ke format Excel (.xlsx).
     *
     * @param array $filters
     * @return BinaryFileResponse
     */
    public function exportToExcel(array $filters): BinaryFileResponse
    {
        $type = $filters['type'] ?? 'status';

        // 1. Selesaikan strategi laporan via registry
        $strategy = $this->registry->resolve($type);

        $source = $filters['source'] ?? 'real';
        $prefix = $source === 'ebmd' ? 'laporan_ebmd_' : 'laporan_';

        // 2. Susun nama berkas unduhan yang bersih
        $filename = $prefix . $type . '_' . now()->format('Ymd_His') . '.xlsx';

        // 3. Ambil judul laporan pendukung
        $reportTitle = $this->registry->getSupportedTypes()[$type] ?? 'Laporan Kendaraan';

        // 4. Muat konfigurasi dokumen dinamis dari database
        $docSettings = $this->docSettingService->getSettingsForReportType($type);

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
     * Mengambil data yang dipersiapkan untuk pratinjau cetak printer.
     *
     * @param array $filters
     * @return array
     */
    public function getPrintData(array $filters): array
    {
        $type = $filters['type'] ?? 'status';

        $strategy = $this->registry->resolve($type);

        $query = $strategy->query($filters);
        $query = $this->reportService->applySorting($query, $filters);
        $data = $query->get();

        if ($strategy instanceof PostProcessesReportRows) {
            $refQuery = method_exists($strategy, 'referenceQuery')
                ? $strategy->referenceQuery($filters)
                : $strategy->query($filters);
            $refQuery = $this->reportService->applySorting($refQuery, $filters);
            $referenceRows = $refQuery->get();

            $strategy->postProcess($data, $referenceRows);
        }

        $reportTitle = $this->registry->getSupportedTypes()[$type] ?? 'Laporan Kendaraan';
        $docSettings = $this->docSettingService->getSettingsForReportType($type);

        return [
            'data'        => $data,
            'headers'     => $strategy->headers(),
            'reportTitle' => $reportTitle,
            'filters'     => $filters,
            'docSettings' => $docSettings,
        ];
    }

    /**
     * Menghasilkan file PDF atau mengalihkan dengan pesan error jika melampaui batas baris.
     *
     * @param array $filters
     * @return Response|RedirectResponse
     */
    public function generatePdfResponse(array $filters)
    {
        $type = $filters['type'] ?? 'status';

        $strategy = $this->registry->resolve($type);

        ini_set('memory_limit', '1024M');
        set_time_limit(300);
        ini_set('pcre.backtrack_limit', '10000000');

        // 2. Mencegah overload memori produksi (Data Guard > 3500 baris)
        $query = $strategy->query($filters);
        $query = $this->reportService->applySorting($query, $filters);
        $count = $query->count();
        if ($count > 3500) {
            return redirect()->route('reports.index')->with('error', 'Jumlah data mencapai ' . number_format($count) . ' baris. Demi menjaga stabilitas server, ekspor lebih dari 3.500 data wajib menggunakan format Excel.');
        }

        $data = $query->get();

        if ($strategy instanceof PostProcessesReportRows) {
            $refQuery = method_exists($strategy, 'referenceQuery')
                ? $strategy->referenceQuery($filters)
                : $strategy->query($filters);
            $refQuery = $this->reportService->applySorting($refQuery, $filters);
            $referenceRows = $refQuery->get();

            $strategy->postProcess($data, $referenceRows);
        }

        $reportTitle = $this->registry->getSupportedTypes()[$type] ?? 'Laporan Kendaraan';
        $docSettings = $this->docSettingService->getSettingsForReportType($type);

        $tempDir = storage_path('app/public/mpdf_temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $paperSize = $docSettings['settings']['paper_size'] ?? 'A4';
        $orientation = $docSettings['settings']['orientation'] ?? 'L';
        $mpdfPaperSize = $paperSize === 'F4' ? 'FOLIO' : $paperSize;
        $mpdfFormat = $mpdfPaperSize . ($orientation === 'L' ? '-L' : '');

        $mpdf = new Mpdf([
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

        $html = view('reports.pdf', [
            'data'        => $data,
            'headers'     => $strategy->headers(),
            'reportTitle' => $reportTitle,
            'filters'     => $filters,
            'docSettings' => $docSettings,
        ])->render();

        $mpdf->WriteHTML($html);

        $source = $filters['source'] ?? 'real';
        $prefix = $source === 'ebmd' ? 'laporan_ebmd_' : 'laporan_';
        $filename = $prefix . $type . '_' . now()->format('Ymd_His') . '.pdf';

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
