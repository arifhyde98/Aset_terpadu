<?php

namespace App\Http\Controllers\Elabel;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreElabelSuratPenyerahanRequest;
use App\Http\Requests\UpdateElabelSuratPenyerahanRequest;
use App\Models\Elabel\ElabelActivityLog;
use App\Models\Elabel\ElabelSuratPenyerahan;
use App\Models\Elabel\ElabelSuratPenyerahanBox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ElabelSuratPenyerahanController extends Controller implements HasMiddleware
{
    private const MAX_SURAT_PER_BOX = 40;

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function index(Request $request): View
    {
        $query = trim((string) $request->get('q'));
        $builder = ElabelSuratPenyerahan::with('box')->orderBy('id', 'desc');

        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('no_surat', 'LIKE', "%{$query}%")
                  ->orWhere('nibar', 'LIKE', "%{$query}%")
                  ->orWhere('jenis_penyerahan', 'LIKE', "%{$query}%")
                  ->orWhere('lokasi', 'LIKE', "%{$query}%")
                  ->orWhere('dinas', 'LIKE', "%{$query}%")
                  ->orWhere('pemberi_hibah', 'LIKE', "%{$query}%");
            });
        }

        $items = $builder->get();

        return view('elabel.surat_penyerahan.index', [
            'items'       => $items,
            'searchQuery' => $query,
            'activeMenu'  => 'surat_penyerahan',
        ]);
    }

    public function create(): View
    {
        return view('elabel.surat_penyerahan.create', [
            'activeMenu' => 'surat_penyerahan',
        ]);
    }

    public function store(StoreElabelSuratPenyerahanRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['box_id'] = $this->resolveBoxId($payload['lokasi'] ?? null);

        if ($request->hasFile('pdf') && $request->file('pdf')->isValid()) {
            $payload['pdf_path'] = $this->storeUploadedPdf($request->file('pdf'), $payload);
        }

        $surat = ElabelSuratPenyerahan::create($payload);

        $this->logActivity('create', 'Surat Penyerahan', 'Menambahkan surat penyerahan ' . ($surat->no_surat ?? '-') . '.', 'surat_penyerahan', $surat->id);

        return redirect()->route('elabel.surat-penyerahan.index')->with('success', 'Data surat penyerahan berhasil ditambahkan.');
    }

    public function edit(int $id): View|RedirectResponse
    {
        $item = ElabelSuratPenyerahan::with('box')->find($id);
        if (!$item) {
            return redirect()->route('elabel.surat-penyerahan.index')->with('error', 'Data surat penyerahan tidak ditemukan.');
        }

        return view('elabel.surat_penyerahan.edit', [
            'item'       => $item,
            'activeMenu' => 'surat_penyerahan',
        ]);
    }

    public function show(int $id): View|RedirectResponse
    {
        $item = ElabelSuratPenyerahan::with('box')->find($id);
        if (!$item) {
            return redirect()->route('elabel.surat-penyerahan.index')->with('error', 'Data surat penyerahan tidak ditemukan.');
        }

        return view('elabel.surat_penyerahan.show', [
            'item'       => $item,
            'activeMenu' => 'surat_penyerahan',
        ]);
    }

    public function update(UpdateElabelSuratPenyerahanRequest $request, int $id): RedirectResponse
    {
        $item = ElabelSuratPenyerahan::find($id);
        if (!$item) {
            return redirect()->route('elabel.surat-penyerahan.index')->with('error', 'Data surat penyerahan tidak ditemukan.');
        }

        $payload = $request->validated();
        $payload['box_id'] = $this->resolveBoxId($payload['lokasi'] ?? null, $id, $item->box_id);

        if ($request->hasFile('pdf') && $request->file('pdf')->isValid()) {
            if ($item->pdf_path && Storage::disk('public')->exists($item->pdf_path)) {
                Storage::disk('public')->delete($item->pdf_path);
            }
            $payload['pdf_path'] = $this->storeUploadedPdf($request->file('pdf'), $payload);
        }

        $item->update($payload);

        $this->logActivity('update', 'Surat Penyerahan', 'Mengubah surat penyerahan ' . ($item->no_surat ?? '-') . '.', 'surat_penyerahan', $id);

        return redirect()->route('elabel.surat-penyerahan.index')->with('success', 'Data surat penyerahan berhasil diperbarui.');
    }

    public function pdf(int $id): \Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse
    {
        $item = ElabelSuratPenyerahan::find($id);
        if (!$item || !$item->pdf_path || !Storage::disk('public')->exists($item->pdf_path)) {
            return redirect()->back()->with('error', 'Dokumen PDF surat penyerahan tidak ditemukan.');
        }

        return response()->file(storage_path('app/public/' . $item->pdf_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="surat-penyerahan-' . $id . '.pdf"',
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        $item = ElabelSuratPenyerahan::find($id);
        if (!$item) {
            return redirect()->route('elabel.surat-penyerahan.index')->with('error', 'Data surat penyerahan tidak ditemukan.');
        }

        if ($item->pdf_path && Storage::disk('public')->exists($item->pdf_path)) {
            Storage::disk('public')->delete($item->pdf_path);
        }

        $noSurat = $item->no_surat;
        $item->delete();

        $this->logActivity('delete', 'Surat Penyerahan', 'Menghapus surat penyerahan ' . ($noSurat ?? '-') . '.', 'surat_penyerahan', $id);

        return redirect()->route('elabel.surat-penyerahan.index')->with('success', 'Data surat penyerahan berhasil dihapus.');
    }

    public function export(): StreamedResponse
    {
        $items = ElabelSuratPenyerahan::orderBy('id', 'desc')->get();
        $filename = 'surat-penyerahan-' . date('Ymd') . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Surat Penyerahan');

        $sheet->fromArray([[
            'No', 'NIBAR', 'No. Surat', 'Status Penggunaan', 'Spesifikasi', 'Jenis Penyerahan',
            'Luas', 'Tanggal Perolehan', 'Alamat', 'Lokasi', 'Dinas', 'Pemberi Hibah',
        ]], null, 'A1');

        $rowIndex = 2;
        $i = 1;
        foreach ($items as $item) {
            $sheet->fromArray([[
                $i++,
                $item->nibar ?? '',
                $item->no_surat ?? '',
                $item->status_penggunaan ?? '',
                $item->spesifikasi ?? '',
                $item->jenis_penyerahan ?? '',
                $item->luas ?? '',
                $item->tanggal_perolehan ? $item->tanggal_perolehan->format('Y-m-d') : '',
                $item->alamat ?? '',
                $item->lokasi ?? '',
                $item->dinas ?? '',
                $item->pemberi_hibah ?? '',
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

    public function downloadImportTemplate(): StreamedResponse
    {
        $filename = 'format-import-surat-penyerahan.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import Surat Penyerahan');
        $sheet->fromArray([
            ['No', 'NIBAR', 'No. Surat', 'Status Penggunaan', 'Spesifikasi', 'Jenis Penyerahan', 'Luas', 'Tanggal Perolehan', 'Alamat', 'Lokasi', 'Dinas', 'Pemberi Hibah'],
            [1, 'NBR-001', '593/001/BPKAD/2026', 'Dipakai', 'Tanah kantor', 'Hibah', '250.50', '2026-08-13', 'Jl. Contoh No. 1', 'Donggala', 'BPKAD', 'Nama Pemberi Hibah'],
        ], null, 'A1');

        foreach (range('A', 'L') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function resolveBoxId(?string $lokasi, ?int $excludeId = null, ?int $preferredBoxId = null): ?int
    {
        $lokasi = trim((string) $lokasi);
        if ($lokasi === '') return null;

        $boxes = ElabelSuratPenyerahanBox::where('lokasi', 'LIKE', "%{$lokasi}%")->orderBy('id', 'asc')->get();

        if ($preferredBoxId !== null) {
            $prefBox = $boxes->firstWhere('id', $preferredBoxId);
            if ($prefBox) {
                $count = ElabelSuratPenyerahan::where('box_id', $preferredBoxId)
                    ->when($excludeId !== null, fn($q) => $q->where('id', '!=', $excludeId))
                    ->count();
                if ($count < self::MAX_SURAT_PER_BOX) {
                    return $preferredBoxId;
                }
            }
        }

        foreach ($boxes as $box) {
            $count = ElabelSuratPenyerahan::where('box_id', $box->id)
                ->when($excludeId !== null, fn($q) => $q->where('id', '!=', $excludeId))
                ->count();
            if ($count < self::MAX_SURAT_PER_BOX) {
                return $box->id;
            }
        }

        // Create new box
        $newCode = $boxes->isNotEmpty() ? $this->nextBoxCodeSuffix($boxes->first()->box_code) : 'SP-01';

        $newBox = ElabelSuratPenyerahanBox::create([
            'box_code'   => $newCode,
            'lokasi'     => $lokasi,
            'created_by' => Auth::id() ?: 1,
        ]);

        return $newBox->id;
    }

    private function nextBoxCodeSuffix(string $baseCode): string
    {
        $baseCode = preg_replace('/ \(\d+\)$/', '', trim($baseCode)) ?: trim($baseCode);
        $existing = ElabelSuratPenyerahanBox::select('box_code')
            ->where('box_code', 'LIKE', $baseCode . '%')
            ->orderBy('box_code', 'asc')
            ->get();

        $max = 1;
        foreach ($existing as $row) {
            $code = (string) $row->box_code;
            if (preg_match('/^' . preg_quote($baseCode, '/') . ' \((\d+)\)$/', $code, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }
        return $baseCode . ' (' . ($max + 1) . ')';
    }

    private function storeUploadedPdf($file, array $data): string
    {
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'pdf';
        $baseName = $this->filenameToken((string) ($data['no_surat'] ?? 'surat-penyerahan'));
        $newName = $baseName . '.' . $extension;

        $path = 'elabel/surat_penyerahan/' . $newName;
        $counter = 2;
        while (Storage::disk('public')->exists($path)) {
            $path = 'elabel/surat_penyerahan/' . $baseName . '-' . $counter . '.' . $extension;
            $counter++;
        }

        $file->storeAs('elabel/surat_penyerahan', basename($path), 'public');
        return $path;
    }

    private function filenameToken(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/[^A-Za-z0-9]+/', '-', $value) ?: '';
        return substr(trim($value, '-'), 0, 80);
    }

    private function logActivity(string $action, string $module, string $description, ?string $refType = null, ?int $refId = null): void
    {
        ElabelActivityLog::create([
            'user_id'        => Auth::id() ?: 1,
            'action'         => $action,
            'module'         => $module,
            'description'    => $description,
            'reference_type' => $refType,
            'reference_id'   => $refId,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'created_at'     => now(),
        ]);
    }
}
