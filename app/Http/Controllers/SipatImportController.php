<?php

namespace App\Http\Controllers;

use App\Imports\SipatDataImport;
use App\Imports\SipatUpdateImport;
use App\Models\Activity;
use App\Models\StatusProses;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Controller untuk Manajemen Impor Data & Status Proses SIPAT
 */
class SipatImportController extends Controller implements HasMiddleware
{
    /**
     * Middleware controller.
     *
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:superadmin,admin'),
        ];
    }

    /**
     * Menampilkan halaman Impor Master Data SIPAT (2 Tab khas SIPAT).
     */
    public function index()
    {
        $statusList = StatusProses::orderBy('urutan', 'asc')->get();
        return view('master.import.index', compact('statusList'));
    }

    /**
     * Memproses impor status pensertifikatan / update sertifikat (Tab 1: NIBAR + Status).
     */
    public function importUpdate(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ], [
            'file.required' => 'File Excel atau CSV wajib dipilih.',
            'file.mimes'    => 'Format file harus berupa .xlsx, .xls, atau .csv.',
            'file.max'      => 'Ukuran file maksimal adalah 10MB.',
        ]);

        try {
            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());
            $rows = $this->readFileRows($file->getPathname(), $extension);

            if (empty($rows)) {
                return redirect()->route('master.import.index')
                    ->with('active_tab', 'status')
                    ->with('error', 'File yang diunggah kosong.');
            }

            $importer = new SipatUpdateImport();
            $result = $importer->processRows($rows);

            $updated = $result['updated'];
            $notFound = $result['notFound'];

            $msg = "Impor status proses selesai. Berhasil: {$updated} data aset diperbarui.";
            if ($notFound > 0) {
                $msg .= " Dilewati: {$notFound} data (Kode Aset / NIBAR tidak ditemukan / status tidak valid).";
            }

            Activity::logSipat("Berhasil memperbarui status BPN secara massal untuk {$updated} aset tanah.", 'success');

            return redirect()->route('master.import.index')
                ->with('active_tab', 'status')
                ->with('success', $msg);
        } catch (\Exception $e) {
            return redirect()->route('master.import.index')
                ->with('active_tab', 'status')
                ->with('error', 'Gagal memproses file impor: ' . $e->getMessage());
        }
    }

    /**
     * Memproses impor data sertifikat/aset baru (Tab 2: Import Aset Tanah SIPAT).
     */
    public function importData(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ], [
            'file.required' => 'File Excel atau CSV wajib dipilih.',
            'file.mimes'    => 'Format file harus berupa .xlsx, .xls, atau .csv.',
            'file.max'      => 'Ukuran file maksimal adalah 10MB.',
        ]);

        try {
            $import = new SipatDataImport();
            Excel::import($import, $request->file('file'));

            $imported = $import->getImportedCount();
            $skipped = $import->getSkippedCount();

            $msg = "Impor data aset baru selesai. Berhasil: {$imported} data aset baru ditambahkan.";
            if ($skipped > 0) {
                $msg .= " Dilewati: {$skipped} baris (duplikasi Kode Aset / data kosong).";
            }

            Activity::logSipat("Berhasil menambahkan {$imported} data aset tanah dan sertifikat baru secara massal.", 'success');

            return redirect()->route('master.import.index')
                ->with('active_tab', 'aset')
                ->with('success', $msg);
        } catch (\Exception $e) {
            return redirect()->route('master.import.index')
                ->with('active_tab', 'aset')
                ->with('error', 'Gagal memproses impor data aset baru: ' . $e->getMessage());
        }
    }

    /**
     * Mendownload template file CSV/Excel untuk status proses (Tab 1).
     */
    public function downloadTemplateStatus()
    {
        $filename = "template_import_status_proses.csv";
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($file, ['nibar', 'status_proses', 'tgl_mulai', 'tgl_selesai', 'keterangan']);
            fputcsv($file, ['12.01.02.01.001', 'Sertifikat', date('Y-m-d'), '', 'Update status via Excel']);
            fputcsv($file, ['12.01.02.01.002', 'Proses BPN', date('Y-m-d'), '', 'Update status via Excel']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Mendownload template file CSV/Excel untuk data aset baru (Tab 2).
     */
    public function downloadTemplateData()
    {
        $filename = "template_import_aset_baru.csv";
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'kode_aset', 'nama_aset', 'peruntukan', 'luas', 'opd', 
            'alamat', 'lat', 'lng', 'dasar_perolehan', 'harga_perolehan', 
            'tanggal_perolehan', 'keterangan', 'status_proses'
        ];

        $exampleRow = [
            '12.01.02.01.099', 'Tanah Kantor OPD Baru', 'Fasilitas Umum', '750', 'DINAS KESEHATAN',
            'Jl. Trans Sulawesi No. 45', '-0.898', '119.878', 'Pembelian', '150000000',
            '2024-02-15', 'KIB A Aset Daerah', 'Sertifikat'
        ];

        $callback = function () use ($columns, $exampleRow) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($file, $columns);
            fputcsv($file, $exampleRow);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Helper membaca baris file Excel atau CSV
     */
    private function readFileRows(string $filePath, string $extension): array
    {
        $rows = [];
        if (in_array($extension, ['xlsx', 'xls'], true)) {
            $spreadsheet = IOFactory::load($filePath);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            $rows = $sheetData;
        } else {
            $handle = fopen($filePath, 'r');
            if ($handle !== false) {
                while (($line = fgetcsv($handle)) !== false) {
                    $rows[] = $line;
                }
                fclose($handle);
            }
        }
        return $rows;
    }
}
