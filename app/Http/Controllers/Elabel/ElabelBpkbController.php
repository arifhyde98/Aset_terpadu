<?php

namespace App\Http\Controllers\Elabel;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreElabelBpkbRequest;
use App\Http\Requests\UpdateElabelBpkbRequest;
use App\Models\Elabel\ElabelActivityLog;
use App\Models\Elabel\ElabelBox;
use App\Models\Elabel\ElabelBoxYear;
use App\Models\Elabel\ElabelBpkb;
use App\Models\Elabel\ElabelBpkbDelete;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ElabelBpkbController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function index(Request $request, ?string $type = null): View
    {
        $vehicleType = $this->normalizeVehicleType($type ?: $request->get('type'));
        $vehicleLabel = $this->vehicleLabel($vehicleType);
        $query = trim((string) $request->get('q'));

        $builder = ElabelBpkb::with(['box', 'inputUser'])
            ->where('status', '!=', 'Dihapus');

        if ($vehicleType !== null) {
            $builder->where(function ($q) use ($vehicleType) {
                $q->where('vehicle_type', $vehicleType)
                  ->orWhere('vehicle_type', strtolower($vehicleType))
                  ->orWhere('vehicle_type', $vehicleType === 'R4' ? 'mobil' : 'motor');
            });
        }

        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('plate_number', 'LIKE', "%{$query}%")
                  ->orWhere('no_bpkb', 'LIKE', "%{$query}%")
                  ->orWhere('nibar', 'LIKE', "%{$query}%")
                  ->orWhere('no_rangka', 'LIKE', "%{$query}%")
                  ->orWhere('no_mesin', 'LIKE', "%{$query}%")
                  ->orWhere('merek', 'LIKE', "%{$query}%")
                  ->orWhere('tipe', 'LIKE', "%{$query}%")
                  ->orWhere('isi_silinder', 'LIKE', "%{$query}%")
                  ->orWhere('warna', 'LIKE', "%{$query}%")
                  ->orWhere('pengguna', 'LIKE', "%{$query}%")
                  ->orWhereHas('box', function ($bq) use ($query) {
                      $bq->where('box_code', 'LIKE', "%{$query}%");
                  });
            });
        }

        $items = $builder->orderBy('year', 'desc')
            ->orderBy('plate_number', 'asc')
            ->get();

        $yearsBuilder = ElabelBoxYear::select('elabel_box_years.year')
            ->distinct()
            ->join('elabel_boxes', 'elabel_boxes.id', '=', 'elabel_box_years.box_id');

        if ($vehicleType !== null) {
            $yearsBuilder->where(function ($q) use ($vehicleType) {
                $q->where('elabel_boxes.vehicle_type', $vehicleType)
                  ->orWhere('elabel_boxes.vehicle_type', strtolower($vehicleType))
                  ->orWhere('elabel_boxes.vehicle_type', $vehicleType === 'R4' ? 'mobil' : 'motor');
            });
        }

        $years = $yearsBuilder->orderBy('elabel_box_years.year', 'desc')->pluck('year')->toArray();

        return view('elabel.bpkb.index', [
            'items'        => $items,
            'years'        => $years,
            'vehicleType'  => $vehicleType,
            'vehicleLabel' => $vehicleLabel,
            'vehicleRoute' => $vehicleType ? $this->routeSegment($vehicleType) : null,
            'activeMenu'   => $vehicleType === 'R2' ? 'bpkb_motor' : ($vehicleType === 'R4' ? 'bpkb_mobil' : 'bpkb'),
            'searchQuery'  => $query,
        ]);
    }

    public function create(Request $request, ?string $type = null): View
    {
        $vehicleType = $this->normalizeVehicleType($type ?: $request->get('type'));
        $vehicleLabel = $this->vehicleLabel($vehicleType);
        $boxes = ElabelBox::orderBy('box_code', 'asc')->get();
        $years = $this->availableYears($vehicleType);
        $opds = \App\Models\OpdSipat::where('aktif', 1)->orderBy('nama', 'asc')->get();

        return view('elabel.bpkb.create', [
            'boxes'        => $boxes,
            'years'        => $years,
            'opds'         => $opds,
            'vehicleType'  => $vehicleType,
            'vehicleLabel' => $vehicleLabel,
            'vehicleRoute' => $vehicleType ? $this->routeSegment($vehicleType) : null,
            'activeMenu'   => $vehicleType === 'R2' ? 'bpkb_motor' : ($vehicleType === 'R4' ? 'bpkb_mobil' : 'bpkb'),
        ]);
    }

    public function store(StoreElabelBpkbRequest $request): RedirectResponse
    {
        $year = (int) $request->get('year');
        $vehicleType = $this->normalizeVehicleType((string) $request->get('vehicle_type')) ?: 'R4';
        $identity = $this->normalizeBpkbIdentity([
            'plate_number' => (string) $request->get('plate_number'),
            'no_bpkb'      => (string) $request->get('no_bpkb'),
            'nibar'        => (string) $request->get('nibar'),
            'no_rangka'    => (string) $request->get('no_rangka'),
            'no_mesin'     => (string) $request->get('no_mesin'),
        ]);

        $duplicate = $this->findDuplicateBpkb($identity, $year);
        if ($duplicate !== null) {
            return redirect()->back()->withInput()->with('error', $this->duplicateBpkbMessage($duplicate));
        }

        if (!$this->isYearAvailable($year, $vehicleType)) {
            return redirect()->back()->withInput()->with('error', 'Tahun dokumen belum tersedia di data box.');
        }

        $boxId = $this->resolveBoxForYear($year, $vehicleType);
        if (!$boxId) {
            return redirect()->back()->withInput()->with('error', 'Box untuk tahun tersebut belum tersedia.');
        }

        $pdfPath = null;
        if ($request->hasFile('pdf') && $request->file('pdf')->isValid()) {
            $box = ElabelBox::find($boxId);
            $pdfPath = $this->storeBpkbPdf(
                $request->file('pdf'),
                $identity['plate_number'],
                $year,
                (string) ($box->box_code ?? '')
            );
        }

        $bpkb = ElabelBpkb::create([
            'box_id'       => $boxId,
            'year'         => $year,
            'vehicle_type' => $vehicleType,
            'plate_number' => $identity['plate_number'],
            'no_bpkb'      => $identity['no_bpkb'],
            'nibar'        => $identity['nibar'],
            'no_rangka'    => $identity['no_rangka'],
            'no_mesin'     => $identity['no_mesin'],
            'merek'        => $this->normalizeTextField((string) $request->get('merek')),
            'tipe'         => $this->normalizeTextField((string) $request->get('tipe')),
            'isi_silinder' => $this->normalizeTextField((string) $request->get('isi_silinder')),
            'warna'        => $this->normalizeTextField((string) $request->get('warna')),
            'pengguna'     => $this->normalizeTextField((string) $request->get('pengguna')),
            'status'       => 'Tersedia',
            'pdf_path'     => $pdfPath,
            'input_by'     => Auth::id() ?: 1,
            'sipat_opd_id' => $request->get('sipat_opd_id'),
        ]);

        $this->logActivity('create', 'BPKB', 'Menambahkan BPKB ' . $identity['plate_number'] . ' tahun ' . $year . '.', 'bpkb', $bpkb->id);

        $redirectRoute = $vehicleType === 'R2' ? route('elabel.bpkb.index', ['type' => 'r2']) : route('elabel.bpkb.index', ['type' => 'r4']);
        return redirect($redirectRoute)->with('success', 'Data BPKB berhasil ditambahkan.');
    }

    public function edit(int $id): View|RedirectResponse
    {
        $item = ElabelBpkb::with('box')->find($id);
        if (!$item || $item->status === 'Dihapus') {
            return redirect()->route('elabel.bpkb.index')->with('error', 'Data BPKB tidak ditemukan.');
        }

        $vehicleType = $this->normalizeVehicleType($item->vehicle_type);
        $opds = \App\Models\OpdSipat::where('aktif', 1)->orderBy('nama', 'asc')->get();

        return view('elabel.bpkb.edit', [
            'item'         => $item,
            'years'        => $this->availableYears(null),
            'opds'         => $opds,
            'vehicleType'  => $vehicleType,
            'vehicleLabel' => $this->vehicleLabel($vehicleType),
            'vehicleRoute' => $vehicleType ? $this->routeSegment($vehicleType) : null,
            'activeMenu'   => $vehicleType === 'R2' ? 'bpkb_motor' : 'bpkb_mobil',
        ]);
    }

    public function show(int $id): View|RedirectResponse
    {
        $item = ElabelBpkb::with(['box', 'inputUser'])->find($id);
        if (!$item) {
            return redirect()->route('elabel.bpkb.index')->with('error', 'Data BPKB tidak ditemukan.');
        }

        return view('elabel.bpkb.show', [
            'item'       => $item,
            'activeMenu' => $this->normalizeVehicleType($item->vehicle_type) === 'R2' ? 'bpkb_motor' : 'bpkb_mobil',
        ]);
    }

    public function update(UpdateElabelBpkbRequest $request, int $id): RedirectResponse
    {
        $item = ElabelBpkb::find($id);
        if (!$item || $item->status === 'Dihapus') {
            return redirect()->route('elabel.bpkb.index')->with('error', 'Data BPKB tidak ditemukan.');
        }

        $year = (int) $request->get('year');
        $vehicleType = $this->normalizeVehicleType((string) $request->get('vehicle_type')) ?: 'R4';
        $identity = $this->normalizeBpkbIdentity([
            'plate_number' => (string) $request->get('plate_number'),
            'no_bpkb'      => (string) $request->get('no_bpkb'),
            'nibar'        => (string) $request->get('nibar'),
            'no_rangka'    => (string) $request->get('no_rangka'),
            'no_mesin'     => (string) $request->get('no_mesin'),
        ]);

        $duplicate = $this->findDuplicateBpkb($identity, $year, $id);
        if ($duplicate !== null) {
            return redirect()->back()->withInput()->with('error', $this->duplicateBpkbMessage($duplicate));
        }

        if (!$this->isYearAvailable($year, $vehicleType)) {
            return redirect()->back()->withInput()->with('error', 'Tahun dokumen belum tersedia di data box.');
        }

        $boxId = $this->resolveBoxForYear($year, $vehicleType, $id);
        if (!$boxId) {
            return redirect()->back()->withInput()->with('error', 'Box untuk tahun tersebut belum tersedia.');
        }

        $pdfPath = $item->pdf_path;
        if ($request->hasFile('pdf') && $request->file('pdf')->isValid()) {
            $box = ElabelBox::find($boxId);
            $newPdfPath = $this->storeBpkbPdf(
                $request->file('pdf'),
                $identity['plate_number'],
                $year,
                (string) ($box->box_code ?? '')
            );

            if ($pdfPath && $pdfPath !== $newPdfPath && Storage::disk('public')->exists($pdfPath)) {
                Storage::disk('public')->delete($pdfPath);
            }
            $pdfPath = $newPdfPath;
        }

        $item->update([
            'box_id'       => $boxId,
            'year'         => $year,
            'vehicle_type' => $vehicleType,
            'plate_number' => $identity['plate_number'],
            'no_bpkb'      => $identity['no_bpkb'],
            'nibar'        => $identity['nibar'],
            'no_rangka'    => $identity['no_rangka'],
            'no_mesin'     => $identity['no_mesin'],
            'merek'        => $this->normalizeTextField((string) $request->get('merek')),
            'tipe'         => $this->normalizeTextField((string) $request->get('tipe')),
            'isi_silinder' => $this->normalizeTextField((string) $request->get('isi_silinder')),
            'warna'        => $this->normalizeTextField((string) $request->get('warna')),
            'pengguna'     => $this->normalizeTextField((string) $request->get('pengguna')),
            'pdf_path'     => $pdfPath,
            'sipat_opd_id' => $request->get('sipat_opd_id'),
        ]);

        $this->logActivity('update', 'BPKB', 'Mengubah BPKB ' . $identity['plate_number'] . ' tahun ' . $year . '.', 'bpkb', $id);

        $redirectRoute = $vehicleType === 'R2' ? route('elabel.bpkb.index', ['type' => 'r2']) : route('elabel.bpkb.index', ['type' => 'r4']);
        return redirect($redirectRoute)->with('success', 'Data BPKB berhasil diperbarui.');
    }

    public function viewPdf(int $id)
    {
        $item = ElabelBpkb::find($id);
        if (!$item || !$item->pdf_path) {
            return redirect()->back()->with('error', 'File PDF tidak ditemukan.');
        }

        if (str_starts_with($item->pdf_path, 'tg:')) {
            $tgStorage = new \App\Services\TelegramStorageService();
            return $tgStorage->streamToBrowser($item->pdf_path, 'bpkb-' . $id . '.pdf');
        }

        if (!Storage::disk('public')->exists($item->pdf_path)) {
            return redirect()->back()->with('error', 'File PDF tidak tersedia di storage.');
        }

        $fullPath = storage_path('app/public/' . $item->pdf_path);
        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="bpkb-' . $id . '.pdf"',
        ]);
    }

    public function delete(Request $request, int $id): RedirectResponse
    {
        $item = ElabelBpkb::find($id);
        if (!$item || $item->status === 'Dihapus') {
            return redirect()->route('elabel.bpkb.index')->with('error', 'Data BPKB tidak ditemukan.');
        }

        $deletePassword = (string) $request->get('delete_password');
        if ($deletePassword === '' || !Hash::check($deletePassword, Auth::user()->password)) {
            return redirect()->back()->with('error', 'Password login tidak valid. Data BPKB tidak dihapus.');
        }

        $reason = (string) $request->get('reason');
        $detail = (string) $request->get('reason_detail');
        $allowed = ['Di pinjam', 'Penjualan', 'Dihibahkan', 'Kendaraan hilang', 'Kendaraan tidak ditemukan', 'Lainnya'];

        if (!in_array($reason, $allowed, true)) {
            return redirect()->back()->with('error', 'Alasan penghapusan tidak valid.');
        }

        if ($reason === 'Lainnya' && $detail === '') {
            return redirect()->back()->with('error', 'Keterangan tambahan wajib diisi.');
        }

        $supportPath = null;
        if ($request->hasFile('support_doc') && $request->file('support_doc')->isValid()) {
            $ext = strtolower($request->file('support_doc')->getClientOriginalExtension());
            if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                return redirect()->back()->with('error', 'Dokumen pendukung harus PDF/JPG/PNG.');
            }
            $supportPath = $request->file('support_doc')->store('elabel/bpkb_delete', 'public');
        }

        $pdfPath = $this->moveBpkbPdfToDeletedFolder($item->pdf_path, $reason);
        $box = ElabelBox::find($item->box_id);

        ElabelBpkbDelete::where('bpkb_id', $id)->delete();
        ElabelBpkbDelete::create([
            'bpkb_id'          => $id,
            'box_id'           => $item->box_id,
            'box_code'         => $box->box_code ?? null,
            'year'             => $item->year,
            'vehicle_type'     => $item->vehicle_type,
            'plate_number'     => $item->plate_number,
            'no_bpkb'          => $item->no_bpkb,
            'nibar'            => $item->nibar,
            'no_rangka'        => $item->no_rangka,
            'no_mesin'         => $item->no_mesin,
            'merek'            => $item->merek,
            'tipe'             => $item->tipe,
            'isi_silinder'     => $item->isi_silinder,
            'warna'            => $item->warna,
            'pengguna'         => $item->pengguna,
            'status'           => $item->status,
            'pdf_path'         => $pdfPath,
            'input_by'         => $item->input_by,
            'deleted_by'       => Auth::id() ?: 1,
            'deleted_at'       => now(),
            'reason'           => $reason,
            'reason_detail'    => $detail ?: null,
            'support_doc_path' => $supportPath,
        ]);

        $item->delete();
        $this->logActivity('delete', 'BPKB', 'Memindahkan BPKB ' . ($item->plate_number ?? '-') . ' ke BPKB keluar. Alasan: ' . $reason . '.', 'bpkb', $id);

        return redirect()->route('elabel.bpkb-deleted.index')->with('success', 'Data BPKB berhasil dipindahkan ke BPKB keluar.');
    }

    public function export(Request $request): StreamedResponse
    {
        $type = (string) $request->get('type');
        $vehicleType = $this->normalizeVehicleType($type !== '' ? $type : null);

        $builder = ElabelBpkb::with('box')->where('status', '!=', 'Dihapus');
        if ($vehicleType !== null) {
            $builder->where('vehicle_type', $vehicleType);
        }
        $items = $builder->orderBy('year', 'desc')->orderBy('plate_number', 'asc')->get();

        $label = $vehicleType ? strtolower($vehicleType) : 'semua';
        $filename = 'bpkb-' . $label . '-' . date('Ymd') . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data BPKB');
        $lastColumn = 'O';
        $headerRow = 3;

        $sheet->mergeCells('A1:' . $lastColumn . '1');
        $sheet->setCellValue('A1', 'DATA BPKB ' . strtoupper($vehicleType ?? 'SEMUA'));
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 15, 'color' => ['argb' => 'FFFFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF1D4ED8']],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->fromArray([
            ['No', 'No. Polisi', 'No. BPKB', 'Nibar', 'No. Rangka', 'No. Mesin', 'Merek', 'Tipe', 'Isi Silinder', 'Warna', 'Pengguna', 'Tahun', 'Jenis', 'Box', 'Status'],
        ], null, 'A' . $headerRow);

        $rowIndex = $headerRow + 1;
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
                $row->isi_silinder ?? '',
                $row->warna ?? '',
                $row->pengguna ?? '',
                $row->year ?? '',
                $row->vehicle_type ?? '',
                $row->box->box_code ?? '',
                $row->status ?? '',
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

    public function downloadImportTemplate(Request $request): StreamedResponse
    {
        $type = (string) $request->get('type');
        $vehicleType = $this->normalizeVehicleType($type !== '' ? $type : null);
        $label = $vehicleType ? strtolower($vehicleType) : 'semua';
        $filename = 'format-import-bpkb-' . $label . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import BPKB');
        $sheet->fromArray([
            ['No', 'No. Polisi', 'No. BPKB', 'Nibar', 'No. Rangka', 'No. Mesin', 'Merek', 'Tipe', 'Isi Silinder', 'Warna', 'Pengguna', 'Tahun', 'Jenis'],
            [1, 'DN 1234 AB', 'BPKB001', 'NIBAR001', 'RANGKA001', 'MESIN001', 'Toyota', 'Avanza', '1500 CC', 'Hitam', 'Sekretariat', '2024', $vehicleType ?? 'R4'],
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

    private function resolveBoxForYear(int $year, ?string $vehicleType, ?int $excludeBpkbId = null): ?int
    {
        if ($year <= 0) return null;

        $boxYears = ElabelBoxYear::select('elabel_box_years.box_id', 'elabel_boxes.box_code', 'elabel_boxes.location')
            ->join('elabel_boxes', 'elabel_boxes.id', '=', 'elabel_box_years.box_id')
            ->where('elabel_box_years.year', $year)
            ->when($vehicleType !== null, function ($q) use ($vehicleType) {
                $q->where('elabel_boxes.vehicle_type', $vehicleType);
            })
            ->orderBy('elabel_box_years.box_id', 'asc')
            ->get();

        if ($boxYears->isEmpty()) {
            $typeLabel = $vehicleType ?: 'R4';
            $newCode = "BOX-{$year}-{$typeLabel}";
            
            $counter = 1;
            $checkCode = $newCode;
            while (ElabelBox::where('box_code', $checkCode)->exists()) {
                $checkCode = $newCode . '-' . $counter;
                $counter++;
            }

            $newBox = ElabelBox::create([
                'box_code'     => $checkCode,
                'location'     => 'Lemari Arsip Utama',
                'vehicle_type' => $typeLabel,
                'created_by'   => Auth::id() ?: 1,
            ]);

            ElabelBoxYear::create([
                'box_id' => $newBox->id,
                'year'   => $year,
            ]);

            return (int) $newBox->id;
        }

        foreach ($boxYears as $row) {
            $countQuery = ElabelBpkb::where('box_id', $row->box_id)->where('status', '!=', 'Dihapus');
            if ($excludeBpkbId !== null) {
                $countQuery->where('id', '!=', $excludeBpkbId);
            }
            if ($countQuery->count() < 55) {
                return (int) $row->box_id;
            }
        }

        // All boxes full, create new box
        $baseCode = (string) $boxYears[0]->box_code;
        $location = $boxYears[0]->location ?? null;
        $newCode = $this->nextBoxCodeSuffix($baseCode);

        $newBox = ElabelBox::create([
            'box_code'     => $newCode,
            'location'     => $location,
            'vehicle_type' => $vehicleType ?? 'R4',
            'created_by'   => Auth::id() ?: 1,
        ]);

        ElabelBoxYear::create([
            'box_id' => $newBox->id,
            'year'   => $year,
        ]);

        return (int) $newBox->id;
    }

    private function nextBoxCodeSuffix(string $baseCode): string
    {
        $existing = ElabelBox::select('box_code')
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

    private function moveBpkbPdfToDeletedFolder(?string $currentPath, string $reason): ?string
    {
        if (empty($currentPath)) return null;

        $folders = [
            'Di pinjam'                 => 'bpkb_dipinjam',
            'Penjualan'                 => 'bpkb_penjualan',
            'Dihibahkan'                => 'bpkb_dihibahkan',
            'Kendaraan hilang'          => 'bpkb_kendaraan_hilang',
            'Kendaraan tidak ditemukan' => 'bpkb_kendaraan_tidak_ditemukan',
            'Lainnya'                   => 'bpkb_lainnya',
        ];

        $targetFolder = $folders[$reason] ?? 'bpkb_lainnya';
        $filename = basename($currentPath);
        $newPath = 'elabel/' . $targetFolder . '/' . $filename;

        if (Storage::disk('public')->exists($currentPath)) {
            Storage::disk('public')->move($currentPath, $newPath);
            return $newPath;
        }

        return $currentPath;
    }

    private function storeBpkbPdf($file, string $plateNumber, int $year, string $boxCode): string
    {
        $tgStorage = new \App\Services\TelegramStorageService();
        if ($tgStorage->isConfigured()) {
            $caption = "📄 *SCAN BPKB {$plateNumber}*\nTahun: {$year} | Box: {$boxCode}";
            $uploaded = $tgStorage->uploadFile($file, $caption);
            if ($uploaded && !empty($uploaded['tg_path'])) {
                return $uploaded['tg_path'];
            }
        }

        $extension = strtolower($file->getClientOriginalExtension()) ?: 'pdf';
        $baseName = $this->filenameToken($plateNumber) . '_' . $year . '_' . strtoupper($this->filenameToken($boxCode));
        $newName = $baseName . '.' . $extension;

        $path = 'elabel/bpkb/' . $newName;
        $counter = 2;
        while (Storage::disk('public')->exists($path)) {
            $path = 'elabel/bpkb/' . $baseName . '_' . $counter . '.' . $extension;
            $counter++;
        }

        $file->storeAs('elabel/bpkb', basename($path), 'public');
        return $path;
    }

    private function filenameToken(string $value): string
    {
        $value = strtolower(trim($value));
        return preg_replace('/[^a-z0-9]+/', '', $value) ?: '';
    }

    private function normalizeVehicleType(?string $type): ?string
    {
        if ($type === null) return null;
        $type = strtolower(trim($type));
        if (in_array($type, ['motor', 'r2'], true)) return 'R2';
        if (in_array($type, ['mobil', 'r4'], true)) return 'R4';
        return strtoupper($type);
    }

    private function vehicleLabel(?string $type): string
    {
        return match($type) {
            'R2' => 'R2 (Motor)',
            'R4' => 'R4 (Mobil)',
            default => 'Semua Kendaraan',
        };
    }

    private function routeSegment(?string $type): string
    {
        return $type === 'R2' ? 'r2' : 'r4';
    }

    private function availableYears(?string $vehicleType): array
    {
        $existingYears = ElabelBoxYear::select('elabel_box_years.year')
            ->distinct()
            ->join('elabel_boxes', 'elabel_boxes.id', '=', 'elabel_box_years.box_id')
            ->when($vehicleType !== null, function ($q) use ($vehicleType) {
                $q->where('elabel_boxes.vehicle_type', $vehicleType);
            })
            ->pluck('year')
            ->toArray();

        $defaultRange = range((int) date('Y') + 1, 1990);
        $allYears = array_unique(array_merge($defaultRange, $existingYears));
        rsort($allYears);

        return $allYears;
    }

    private function isYearAvailable(int $year, ?string $vehicleType): bool
    {
        return $year >= 1900 && $year <= 2100;
    }

    private function normalizeBpkbIdentity(array $data): array
    {
        $norm = static fn($v) => ($v = strtoupper(trim((string)$v))) === '' || $v === '-' || $v === '0' ? null : $v;
        return [
            'plate_number' => strtoupper(trim((string) ($data['plate_number'] ?? ''))),
            'no_bpkb'      => $norm($data['no_bpkb'] ?? null),
            'nibar'        => $norm($data['nibar'] ?? null),
            'no_rangka'    => $norm($data['no_rangka'] ?? null),
            'no_mesin'     => $norm($data['no_mesin'] ?? null),
        ];
    }

    private function findDuplicateBpkb(array $identity, int $year, ?int $excludeId = null): ?array
    {
        $plateNumber = trim((string) ($identity['plate_number'] ?? ''));
        if ($plateNumber === '' || $year <= 0) return null;

        $query = ElabelBpkb::where('plate_number', $plateNumber)->where('year', $year);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $duplicate = $query->first();
        if ($duplicate === null) return null;

        return [
            'field'  => 'plate_number',
            'value'  => $plateNumber,
            'year'   => $year,
            'record' => $duplicate->toArray(),
        ];
    }

    private function duplicateBpkbMessage(array $duplicate): string
    {
        $plate = (string) ($duplicate['record']['plate_number'] ?? '-');
        $year = (string) ($duplicate['record']['year'] ?? '-');
        return 'No. Polisi "' . $duplicate['value'] . '" tahun ' . $year . ' sudah terdaftar pada data BPKB.';
    }

    private function normalizeTextField(?string $value): ?string
    {
        $v = trim((string) $value);
        return $v === '' ? null : $v;
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
