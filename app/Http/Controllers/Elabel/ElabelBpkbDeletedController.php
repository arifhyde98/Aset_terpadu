<?php

namespace App\Http\Controllers\Elabel;

use App\Http\Controllers\Controller;
use App\Models\Elabel\ElabelActivityLog;
use App\Models\Elabel\ElabelBpkb;
use App\Models\Elabel\ElabelBpkbDelete;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ElabelBpkbDeletedController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function index(Request $request): View
    {
        $query = trim((string) $request->get('q'));
        $reasonFilter = (string) $request->get('reason');

        $builder = ElabelBpkbDelete::with(['deleter', 'inputUser']);

        if ($reasonFilter !== '') {
            $builder->where('reason', $reasonFilter);
        }

        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('plate_number', 'LIKE', "%{$query}%")
                  ->orWhere('no_bpkb', 'LIKE', "%{$query}%")
                  ->orWhere('nibar', 'LIKE', "%{$query}%")
                  ->orWhere('no_rangka', 'LIKE', "%{$query}%")
                  ->orWhere('no_mesin', 'LIKE', "%{$query}%")
                  ->orWhere('box_code', 'LIKE', "%{$query}%")
                  ->orWhere('reason', 'LIKE', "%{$query}%")
                  ->orWhere('reason_detail', 'LIKE', "%{$query}%");
            });
        }

        $items = $builder->orderBy('deleted_at', 'desc')->get();

        return view('elabel.bpkb_deleted.index', [
            'items'        => $items,
            'reasonFilter' => $reasonFilter,
            'searchQuery'  => $query,
            'activeMenu'   => 'bpkb_deleted',
        ]);
    }

    public function show(int $id): View|RedirectResponse
    {
        $item = ElabelBpkbDelete::with(['deleter', 'inputUser'])->find($id);
        if (!$item) {
            return redirect()->route('elabel.bpkb-deleted.index')->with('error', 'Data BPKB keluar tidak ditemukan.');
        }

        return view('elabel.bpkb_deleted.show', [
            'item'       => $item,
            'activeMenu' => 'bpkb_deleted',
        ]);
    }

    public function restore(int $id): RedirectResponse
    {
        $item = ElabelBpkbDelete::find($id);
        if (!$item) {
            return redirect()->route('elabel.bpkb-deleted.index')->with('error', 'Data BPKB keluar tidak ditemukan.');
        }

        // Restore to main BPKB table
        ElabelBpkb::create([
            'id'           => $item->bpkb_id,
            'box_id'       => $item->box_id ?: 1,
            'year'         => $item->year,
            'vehicle_type' => $item->vehicle_type ?: 'R4',
            'plate_number' => $item->plate_number,
            'no_bpkb'      => $item->no_bpkb,
            'nibar'        => $item->nibar,
            'no_rangka'    => $item->no_rangka,
            'no_mesin'     => $item->no_mesin,
            'merek'        => $item->merek,
            'tipe'         => $item->tipe,
            'isi_silinder' => $item->isi_silinder,
            'warna'        => $item->warna,
            'pengguna'     => $item->pengguna,
            'status'       => 'Tersedia',
            'pdf_path'     => $item->pdf_path,
            'input_by'     => $item->input_by ?: (Auth::id() ?: 1),
        ]);

        $item->delete();

        ElabelActivityLog::create([
            'user_id'     => Auth::id() ?: 1,
            'action'      => 'restore',
            'module'      => 'BPKB Keluar',
            'description' => 'Mengembalikan BPKB ' . ($item->plate_number ?? '-') . ' dari BPKB keluar ke katalog BPKB aktif.',
            'created_at'  => now(),
        ]);

        return redirect()->route('elabel.bpkb.index')->with('success', 'Data BPKB berhasil dikembalikan ke katalog aktif.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $item = ElabelBpkbDelete::find($id);
        if (!$item) {
            return redirect()->route('elabel.bpkb-deleted.index')->with('error', 'Data BPKB keluar tidak ditemukan.');
        }

        if ($item->pdf_path && Storage::disk('public')->exists($item->pdf_path)) {
            Storage::disk('public')->delete($item->pdf_path);
        }

        if ($item->support_doc_path && Storage::disk('public')->exists($item->support_doc_path)) {
            Storage::disk('public')->delete($item->support_doc_path);
        }

        $item->delete();

        ElabelActivityLog::create([
            'user_id'     => Auth::id() ?: 1,
            'action'      => 'delete',
            'module'      => 'BPKB Keluar',
            'description' => 'Menghapus permanen riwayat BPKB keluar ' . ($item->plate_number ?? '-') . '.',
            'created_at'  => now(),
        ]);

        return redirect()->route('elabel.bpkb-deleted.index')->with('success', 'Data BPKB keluar berhasil dihapus permanen.');
    }

    public function viewPdf(int $id)
    {
        $item = ElabelBpkbDelete::find($id);
        if (!$item || !$item->pdf_path) {
            return redirect()->back()->with('error', 'File PDF tidak ditemukan.');
        }

        if (str_starts_with($item->pdf_path, 'tg:')) {
            $tgStorage = new \App\Services\TelegramStorageService();
            return $tgStorage->streamToBrowser($item->pdf_path, 'bpkb-keluar-' . $id . '.pdf');
        }

        if (!Storage::disk('public')->exists($item->pdf_path)) {
            return redirect()->back()->with('error', 'File PDF tidak ditemukan.');
        }

        return response()->file(storage_path('app/public/' . $item->pdf_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="bpkb-keluar-' . $id . '.pdf"',
        ]);
    }

    public function viewSupportDoc(int $id)
    {
        $item = ElabelBpkbDelete::find($id);
        if (!$item || !$item->support_doc_path) {
            return redirect()->back()->with('error', 'Dokumen pendukung tidak ditemukan.');
        }

        if (str_starts_with($item->support_doc_path, 'tg:')) {
            $tgStorage = new \App\Services\TelegramStorageService();
            return $tgStorage->streamToBrowser($item->support_doc_path, 'dokumen-pendukung-' . $id . '.pdf');
        }

        if (!Storage::disk('public')->exists($item->support_doc_path)) {
            return redirect()->back()->with('error', 'Dokumen pendukung tidak ditemukan.');
        }

        $ext = strtolower(pathinfo($item->support_doc_path, PATHINFO_EXTENSION));
        $mime = match($ext) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/octet-stream',
        };

        return response()->file(storage_path('app/public/' . $item->support_doc_path), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="dokumen-pendukung-' . $id . '.' . $ext . '"',
        ]);
    }

    public function export(): StreamedResponse
    {
        $items = ElabelBpkbDelete::with('deleter')->orderBy('deleted_at', 'desc')->get();
        $filename = 'bpkb-keluar-' . date('Ymd') . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('BPKB Keluar');

        $sheet->fromArray([
            ['No', 'No. Polisi', 'No. BPKB', 'Nibar', 'No. Rangka', 'No. Mesin', 'Merek', 'Tipe', 'Tahun', 'Box Asli', 'Alasan Keluar', 'Detail Alasan', 'Dihapus Oleh', 'Tanggal Keluar'],
        ], null, 'A1');

        $rowIndex = 2;
        $i = 1;
        foreach ($items as $row) {
            $sheet->fromArray([[
                $i++,
                $row->plate_number ?? '',
                $row->no_bpkb ?? '',
                $row->nibar ?? '',
                $row->no_rangka ?? '',
                $row->no_mesin ?? '',
                $row->merek ?? '',
                $row->tipe ?? '',
                $row->year ?? '',
                $row->box_code ?? '',
                $row->reason ?? '',
                $row->reason_detail ?? '',
                $row->deleter->name ?? '-',
                $row->deleted_at ? $row->deleted_at->format('Y-m-d H:i') : '',
            ]], null, 'A' . $rowIndex);
            $rowIndex++;
        }

        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
