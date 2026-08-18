<?php

namespace App\Http\Controllers\Sipat;

use App\Http\Controllers\Controller;
use App\Models\AsetTanah;
use App\Models\OpdSipat;
use App\Models\StatusProses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->getFilters($request);
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

        $query = $this->buildQuery($filters);
        $rows = $query->get();
        $summary = $this->buildSummary($rows, $filters);

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
        $filters = $this->getFilters($request);
        $rows = $this->buildQuery($filters)->get();

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
        // Export to Excel CSV/XLSX
        return $this->exportCsv($request);
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
        $filters = $this->getFilters($request);
        $rows = $this->buildQuery($filters)->get();
        $summary = $this->buildSummary($rows, $filters);
        $kop = $this->getKopSettings();

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

        if ($download) {
            return response($pdfView)
                ->header('Content-Type', 'text/html')
                ->header('Content-Disposition', 'inline; filename="Laporan_Aset_Tanah_' . date('Ymd_His') . '.html"');
        }

        return response($pdfView);
    }

    private function getFilters(Request $request): array
    {
        $rawStatus = $request->input('status');
        $statusIds = is_array($rawStatus) ? array_filter($rawStatus) : ($rawStatus ? [$rawStatus] : []);

        return [
            'opd' => $request->input('opd', ''),
            'status' => $statusIds,
            'tanggal_perolehan' => $request->input('tanggal_perolehan', ''),
            'q' => $request->input('q', ''),
            'title_mode' => $request->input('title_mode', 'master'),
            'report_title_id' => $request->input('report_title_id', ''),
            'manual_title' => $request->input('manual_title', ''),
        ];
    }

    private function buildQuery(array $filters)
    {
        $query = AsetTanah::with(['latestProses.statusProses']);

        if (!empty($filters['opd'])) {
            if ($filters['opd'] === 'KOSONG') {
                $query->where(function($q) {
                    $q->whereNull('opd')->orWhere('opd', '');
                });
            } else {
                $query->where('opd', $filters['opd']);
            }
        }

        if (!empty($filters['status'])) {
            $query->whereHas('latestProses', function($q) use ($filters) {
                $q->whereIn('id_status', $filters['status']);
            });
        }

        if (!empty($filters['tanggal_perolehan'])) {
            $query->whereDate('tanggal_perolehan', $filters['tanggal_perolehan']);
        }

        if (!empty($filters['q'])) {
            $search = '%' . $filters['q'] . '%';
            $query->where(function($q) use ($search) {
                $q->where('kode_aset', 'LIKE', $search)
                  ->orWhere('nama_aset', 'LIKE', $search)
                  ->orWhere('peruntukan', 'LIKE', $search)
                  ->orWhere('opd', 'LIKE', $search)
                  ->orWhere('alamat', 'LIKE', $search);
            });
        }

        return $query->orderBy('id_aset', 'desc');
    }

    private function buildSummary($rows, array $filters): array
    {
        $totalData = count($rows);
        $totalNilai = 0;
        $totalBerstatus = 0;

        foreach ($rows as $row) {
            $totalNilai += (float) ($row->harga_perolehan ?? 0);
            $stName = $row->latestProses->statusProses->nama_status ?? '';
            if (!empty($stName) && strtolower($stName) !== 'belum diurus') {
                $totalBerstatus++;
            }
        }

        $activeFilters = [];
        if (!empty($filters['opd'])) {
            $activeFilters[] = ['label' => 'OPD', 'value' => $filters['opd']];
        }
        if (!empty($filters['tanggal_perolehan'])) {
            $activeFilters[] = ['label' => 'Tanggal Perolehan', 'value' => $filters['tanggal_perolehan']];
        }
        if (!empty($filters['q'])) {
            $activeFilters[] = ['label' => 'Pencarian', 'value' => $filters['q']];
        }

        return [
            'total_data' => $totalData,
            'total_nilai' => 'Rp ' . number_format($totalNilai, 2, ',', '.'),
            'total_berstatus' => $totalBerstatus,
            'activeFilters' => $activeFilters,
        ];
    }

    private function getKopSettings(): array
    {
        $defaults = [
            'kop_nama_instansi' => 'PEMERINTAH KABUPATEN DONGGALA',
            'kop_nama_unit' => 'BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH',
            'kop_subunit' => 'Bidang Pengelolaan Aset Daerah',
            'kop_alamat' => 'Jl. Trans Sulawesi, Banawa, Kabupaten Donggala, Sulawesi Tengah',
            'kop_kontak' => 'Email: bpkad@donggalakab.go.id | Web: sipat.donggalakab.go.id',
            'kop_logo' => '',
            'kop_nama_laporan_aset' => 'LAPORAN REKAPITULASI ASET TANAH',
            'kop_footer' => 'Dokumen ini dihasilkan secara resmi oleh Aplikasi SIPAT Terpadu Kabupaten Donggala.',
            'kop_kota_ttd' => 'Banawa',
            'kop_pejabat_jabatan' => 'Kepala Bidang Pengelolaan Aset Daerah',
            'kop_pejabat_nama' => 'H. MUHAMMAD NATSIR, S.E., M.Si.',
            'kop_pejabat_nip' => '19780512 200501 1 008',
        ];

        if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
            $rows = DB::table('settings')->whereIn('key', array_keys($defaults))->get();
            foreach ($rows as $row) {
                $val = trim($row->value ?? '');
                if ($val !== '') {
                    $defaults[$row->key] = $val;
                }
            }
        }

        return $defaults;
    }
}
