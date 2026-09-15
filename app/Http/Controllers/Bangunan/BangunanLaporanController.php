<?php

namespace App\Http\Controllers\Bangunan;

use App\Http\Controllers\Controller;
use App\Models\Bangunan;
use App\Models\Opd;
use App\Models\ReportSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class BangunanLaporanController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    /**
     * Halaman pusat pelaporan inventaris KIB C.
     */
    public function index(Request $request): View
    {
        $opds = Opd::orderBy('nama')->get();

        $query = Bangunan::with(['opdSipat', 'asetTanah', 'kecamatan', 'desa'])
            ->forUser(auth()->user());

        if ($request->filled('opd_id')) {
            $query->where('opd_id', $request->opd_id);
        }

        if ($request->filled('kondisi')) {
            $query->where('kondisi', $request->kondisi);
        }

        if ($request->filled('tahun')) {
            $query->where('tahun_pengadaan', $request->tahun);
        }

        $bangunans = (clone $query)->orderBy('kode_barang')->orderBy('kode_bangunan')->paginate(25)->withQueryString();

        $totalUnit = (clone $query)->count();
        $totalNilai = (clone $query)->sum('harga_perolehan');
        $totalLuas = (clone $query)->sum('luas_lantai');

        return view('bangunan.laporan.index', compact(
            'opds',
            'bangunans',
            'totalUnit',
            'totalNilai',
            'totalLuas'
        ));
    }

    /**
     * Ekspor Laporan Resmi KIB C format mPDF (Landscape A4, 18 Kolom).
     */
    public function exportPdf(Request $request): Response
    {
        $query = Bangunan::with(['opdSipat', 'asetTanah', 'kecamatan', 'desa'])
            ->forUser(auth()->user());

        $selectedOpd = null;
        if ($request->filled('opd_id')) {
            $query->where('opd_id', $request->opd_id);
            $selectedOpd = Opd::find($request->opd_id);
        }

        if ($request->filled('kondisi')) {
            $query->where('kondisi', $request->kondisi);
        }

        if ($request->filled('tahun')) {
            $query->where('tahun_pengadaan', $request->tahun);
        }

        $items = $query->orderBy('kode_barang')->orderBy('kode_bangunan')->get();

        $totalNilai = $items->sum('harga_perolehan');
        $totalLuas = $items->sum('luas_lantai');

        // Ambil pengaturan Kop & Penandatangan Laporan
        $kop = [
            'kop_line1' => 'PEMERINTAH KABUPATEN DONGGALA',
            'kop_line2' => $selectedOpd ? strtoupper($selectedOpd->nama) : 'BADAN PENGELOLA KEUANGAN DAN ASET DAERAH',
            'kop_line3' => 'KARTU INVENTARIS BARANG (KIB) C - GEDUNG DAN BANGUNAN',
            'kop_footer'=> 'Sistem Informasi Pertanahan Aset Terpadu (SIPAT) Kabupaten Donggala',
        ];

        $signatories = [
            'kadis_nama'  => 'Drs. H. KEPALA DINAS, M.Si',
            'kadis_nip'   => '19700101 199503 1 001',
            'kadis_jabatan'=> $selectedOpd ? "Kepala {$selectedOpd->nama}" : "Kepala BPKAD Kab. Donggala",
            'pengurus_nama'=> 'PENGURUS BARANG PENGGUNA',
            'pengurus_nip' => '19850505 201001 1 005',
        ];

        $pdfView = view('bangunan.laporan.pdf_kib_c', compact(
            'items',
            'selectedOpd',
            'totalNilai',
            'totalLuas',
            'kop',
            'signatories'
        ))->render();

        if (!class_exists(\Mpdf\Mpdf::class)) {
            return response($pdfView, 200, ['Content-Type' => 'text/html']);
        }

        $pdfTempDir = storage_path('framework/cache/mpdf');
        if (!is_dir($pdfTempDir)) {
            mkdir($pdfTempDir, 0775, true);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4-L', // Landscape A4
            'tempDir'       => $pdfTempDir,
            'margin_left'   => 8,
            'margin_right'  => 8,
            'margin_top'    => 10,
            'margin_bottom' => 12,
            'default_font'  => 'sans-serif',
        ]);

        $filename = 'Laporan_KIB_C_Gedung_Bangunan_' . date('Ymd_His') . '.pdf';
        $mpdf->SetTitle('Laporan KIB C - Gedung dan Bangunan');
        $mpdf->SetHTMLFooter('<div style="font-size:8pt;color:#64748b;border-top:1px solid #dbe3ef;padding-top:4px;text-align:center;">Halaman {PAGENO} dari {nbpg} | SIPAT Terpadu Kab. Donggala</div>');
        $mpdf->WriteHTML($pdfView);

        return response($mpdf->Output($filename, 'I'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }
}
