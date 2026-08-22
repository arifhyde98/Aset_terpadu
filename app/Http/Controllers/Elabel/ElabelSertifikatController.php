<?php

namespace App\Http\Controllers\Elabel;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreElabelSertifikatRequest;
use App\Http\Requests\UpdateElabelSertifikatRequest;
use App\Models\Elabel\ElabelActivityLog;
use App\Models\Elabel\ElabelSertifikat;
use App\Models\Elabel\ElabelSertifikatBox;
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

class ElabelSertifikatController extends Controller implements HasMiddleware
{
    private const MAX_SERTIFIKAT_PER_BOX = 40;

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function index(Request $request): View
    {
        $query = trim((string) $request->get('q'));
        $builder = ElabelSertifikat::with('box')->orderBy('id', 'desc');

        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('no_sertipikat', 'LIKE', "%{$query}%")
                  ->orWhere('nibar', 'LIKE', "%{$query}%")
                  ->orWhere('nama_pemilik', 'LIKE', "%{$query}%")
                  ->orWhere('lokasi', 'LIKE', "%{$query}%")
                  ->orWhere('dinas', 'LIKE', "%{$query}%")
                  ->orWhere('status_penggunaan', 'LIKE', "%{$query}%");
            });
        }

        $items = $builder->get();

        return view('elabel.sertifikat.index', [
            'items'       => $items,
            'searchQuery' => $query,
            'activeMenu'  => 'sertifikat',
        ]);
    }

    public function create(Request $request): View
    {
        $item = [
            'nibar'             => $request->get('nibar'),
            'spesifikasi'       => $request->get('nama'),
            'dinas'             => $request->get('opd'),
            'nama_pemilik'      => $request->get('opd'),
            'luas'              => $request->get('luas'),
            'tanggal_perolehan' => $request->get('tanggal_perolehan'),
            'nilai_perolehan'   => $request->get('nilai_perolehan'),
            'cara_perolehan'    => $request->get('cara_perolehan'),
            'alamat'            => $request->get('alamat'),
            'lokasi'            => $request->get('alamat'),
            'status_penggunaan' => $request->get('peruntukan'),
        ];

        $opds = \App\Models\OpdSipat::where('aktif', 1)->orderBy('nama', 'asc')->get();

        return view('elabel.sertifikat.create', [
            'item'       => $item,
            'opds'       => $opds,
            'activeMenu' => 'sertifikat',
        ]);
    }

    public function store(StoreElabelSertifikatRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $duplicate = $this->findDuplicateSertifikat($payload);
        if ($duplicate !== null) {
            return redirect()->back()->withInput()->with('error', $this->duplicateSertifikatMessage($duplicate));
        }

        $payload['box_id'] = $this->resolveSertifikatBoxId($payload['lokasi'] ?? null);

        if ($request->hasFile('pdf') && $request->file('pdf')->isValid()) {
            $payload['pdf_path'] = $this->storeUploadedPdf($request->file('pdf'), $payload);
        }

        $sertifikat = ElabelSertifikat::create($payload);

        $this->logActivity('create', 'Sertipikat Tanah', 'Menambahkan sertipikat ' . ($sertifikat->no_sertipikat ?? '-') . '.', 'sertifikat_tanah', $sertifikat->id);

        return redirect()->route('elabel.sertifikat.index')->with('success', 'Data sertipikat berhasil ditambahkan.');
    }

    public function edit(int $id): View|RedirectResponse
    {
        $item = ElabelSertifikat::with('box')->find($id);
        if (!$item) {
            return redirect()->route('elabel.sertifikat.index')->with('error', 'Data sertipikat tidak ditemukan.');
        }

        $opds = \App\Models\OpdSipat::where('aktif', 1)->orderBy('nama', 'asc')->get();

        return view('elabel.sertifikat.edit', [
            'item'       => $item,
            'opds'       => $opds,
            'activeMenu' => 'sertifikat',
        ]);
    }

    public function show(int $id): View|RedirectResponse
    {
        $item = ElabelSertifikat::with('box')->find($id);
        if (!$item) {
            return redirect()->route('elabel.sertifikat.index')->with('error', 'Data sertipikat tidak ditemukan.');
        }

        return view('elabel.sertifikat.show', [
            'item'       => $item,
            'activeMenu' => 'sertifikat',
        ]);
    }

    public function update(UpdateElabelSertifikatRequest $request, int $id): RedirectResponse
    {
        $item = ElabelSertifikat::find($id);
        if (!$item) {
            return redirect()->route('elabel.sertifikat.index')->with('error', 'Data sertipikat tidak ditemukan.');
        }

        $payload = $request->validated();
        $duplicate = $this->findDuplicateSertifikat($payload, $id);
        if ($duplicate !== null) {
            return redirect()->back()->withInput()->with('error', $this->duplicateSertifikatMessage($duplicate));
        }

        $payload['box_id'] = $this->resolveSertifikatBoxId($payload['lokasi'] ?? null, $id, $item->box_id);

        if ($request->hasFile('pdf') && $request->file('pdf')->isValid()) {
            if ($item->pdf_path && Storage::disk('public')->exists($item->pdf_path)) {
                Storage::disk('public')->delete($item->pdf_path);
            }
            $payload['pdf_path'] = $this->storeUploadedPdf($request->file('pdf'), $payload);
        }

        $item->update($payload);

        $this->logActivity('update', 'Sertipikat Tanah', 'Mengubah sertipikat ' . ($item->no_sertipikat ?? '-') . '.', 'sertifikat_tanah', $id);

        return redirect()->route('elabel.sertifikat.index')->with('success', 'Data sertipikat berhasil diperbarui.');
    }

    public function viewPdf(int $id): \Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse
    {
        $item = ElabelSertifikat::find($id);
        if (!$item || !$item->pdf_path || !Storage::disk('public')->exists($item->pdf_path)) {
            return redirect()->back()->with('error', 'File PDF sertipikat tidak ditemukan.');
        }

        return response()->file(storage_path('app/public/' . $item->pdf_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="sertipikat-' . $id . '.pdf"',
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        $item = ElabelSertifikat::find($id);
        if (!$item) {
            return redirect()->route('elabel.sertifikat.index')->with('error', 'Data sertipikat tidak ditemukan.');
        }

        if ($item->pdf_path && Storage::disk('public')->exists($item->pdf_path)) {
            Storage::disk('public')->delete($item->pdf_path);
        }

        $noSert = $item->no_sertipikat;
        $item->delete();

        $this->logActivity('delete', 'Sertipikat Tanah', 'Menghapus sertipikat ' . ($noSert ?? '-') . '.', 'sertifikat_tanah', $id);

        return redirect()->route('elabel.sertifikat.index')->with('success', 'Data sertipikat berhasil dihapus.');
    }

    public function export(): StreamedResponse
    {
        $items = ElabelSertifikat::orderBy('id', 'desc')->get();
        $filename = 'sertifikat-tanah-' . date('Ymd') . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Sertipikat');

        $sheet->fromArray([[
            'No', 'No. Sertipikat', 'NIBAR', 'Status Penggunaan', 'Spesifikasi', 'Luas',
            'Tanggal Perolehan', 'Nilai Perolehan', 'Nama Pemilik', 'Cara Perolehan', 'Alamat', 'Lokasi', 'Dinas',
        ]], null, 'A1');

        $rowIndex = 2;
        $i = 1;
        foreach ($items as $item) {
            $sheet->fromArray([[
                $i++,
                $item->no_sertipikat ?? '',
                $item->nibar ?? '',
                $item->status_penggunaan ?? '',
                $item->spesifikasi ?? '',
                $item->luas ?? '',
                $item->tanggal_perolehan ? $item->tanggal_perolehan->format('Y-m-d') : '',
                $item->nilai_perolehan ?? '',
                $item->nama_pemilik ?? '',
                $item->cara_perolehan ?? '',
                $item->alamat ?? '',
                $item->lokasi ?? '',
                $item->dinas ?? '',
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
        $filename = 'format-import-sertifikat-tanah.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import Sertipikat');
        $sheet->fromArray([
            ['No', 'No. Sertipikat', 'NIBAR', 'Status Penggunaan', 'Spesifikasi', 'Luas', 'Tanggal Perolehan', 'Nilai Perolehan', 'Nama Pemilik', 'Cara Perolehan', 'Alamat', 'Lokasi', 'Dinas'],
            [1, '123/ABC/2024', 'NBR-001', 'Dipakai', 'Hak Pakai', '250.50', '2024-01-15', '150000000', 'Pemerintah Kabupaten Donggala', 'Pembelian', 'Jl. Contoh No.1', 'Donggala', 'BPKAD'],
        ], null, 'A1');

        foreach (range('A', 'M') as $column) {
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

    private function resolveSertifikatBoxId(?string $lokasi, ?int $excludeSertifikatId = null, ?int $preferredBoxId = null): ?int
    {
        $lokasi = trim((string) $lokasi);
        if ($lokasi === '') return null;

        $boxes = ElabelSertifikatBox::where('lokasi', 'LIKE', "%{$lokasi}%")->orderBy('id', 'asc')->get();

        if ($preferredBoxId !== null) {
            $prefBox = $boxes->firstWhere('id', $preferredBoxId);
            if ($prefBox) {
                $count = ElabelSertifikat::where('box_id', $preferredBoxId)
                    ->when($excludeSertifikatId !== null, fn($q) => $q->where('id', '!=', $excludeSertifikatId))
                    ->count();
                if ($count < self::MAX_SERTIFIKAT_PER_BOX) {
                    return $preferredBoxId;
                }
            }
        }

        foreach ($boxes as $box) {
            $count = ElabelSertifikat::where('box_id', $box->id)
                ->when($excludeSertifikatId !== null, fn($q) => $q->where('id', '!=', $excludeSertifikatId))
                ->count();
            if ($count < self::MAX_SERTIFIKAT_PER_BOX) {
                return $box->id;
            }
        }

        // Create new box
        $baseCode = $boxes->isNotEmpty() ? $boxes->first()->box_code : 'BOX-SERT-01';
        $newCode = $boxes->isNotEmpty() ? $this->nextBoxCodeSuffix($baseCode) : 'BOX-SERT-01';

        $newBox = ElabelSertifikatBox::create([
            'box_code'   => $newCode,
            'lokasi'     => $lokasi,
            'created_by' => Auth::id() ?: 1,
        ]);

        return $newBox->id;
    }

    private function nextBoxCodeSuffix(string $baseCode): string
    {
        $baseCode = preg_replace('/ \(\d+\)$/', '', trim($baseCode)) ?: trim($baseCode);
        $existing = ElabelSertifikatBox::select('box_code')
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
        $baseName = $this->filenameToken((string) ($data['no_sertipikat'] ?? 'sertifikat'));
        $newName = $baseName . '.' . $extension;

        $path = 'elabel/sertifikat/' . $newName;
        $counter = 2;
        while (Storage::disk('public')->exists($path)) {
            $path = 'elabel/sertifikat/' . $baseName . '-' . $counter . '.' . $extension;
            $counter++;
        }

        $file->storeAs('elabel/sertifikat', basename($path), 'public');
        return $path;
    }

    private function filenameToken(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/[^A-Za-z0-9]+/', '-', $value) ?: '';
        return substr(trim($value, '-'), 0, 80);
    }

    private function findDuplicateSertifikat(array $identity, ?int $excludeId = null): ?array
    {
        $noSert = strtoupper(trim((string) ($identity['no_sertipikat'] ?? '')));
        if ($noSert !== '') {
            $query = ElabelSertifikat::where('no_sertipikat', $noSert);
            if ($excludeId !== null) $query->where('id', '!=', $excludeId);
            if ($duplicate = $query->first()) {
                return ['field' => 'no_sertipikat', 'value' => $noSert];
            }
        }
        return null;
    }

    private function duplicateSertifikatMessage(array $duplicate): string
    {
        return 'No. Sertipikat "' . $duplicate['value'] . '" sudah terdaftar pada data Sertifikat Tanah.';
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
