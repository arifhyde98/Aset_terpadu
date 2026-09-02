<?php

namespace App\Http\Controllers\Elabel\Dynamic;

use App\Http\Controllers\Controller;
use App\Models\Elabel\Dynamic\ArchiveBox;
use App\Models\Elabel\Dynamic\ArchiveItem;
use App\Models\Elabel\Dynamic\ArchiveType;
use App\Models\Opd;
use App\Services\Elabel\DynamicArchiveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArchiveItemController extends Controller implements HasMiddleware
{
    protected DynamicArchiveService $archiveService;

    public function __construct(DynamicArchiveService $archiveService)
    {
        $this->archiveService = $archiveService;
    }

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function index(Request $request): View
    {
        $typeId  = $request->get('type_id');
        $opdId   = $request->get('opd_id');
        $year    = $request->get('year');
        $status  = $request->get('status');
        $query   = trim((string) $request->get('q'));

        $types = ArchiveType::active()->orderBy('nama', 'asc')->get();
        $opds  = Opd::orderBy('nama', 'asc')->get();

        $builder = ArchiveItem::with(['archiveType', 'box', 'opd', 'attachments'])->orderBy('id', 'desc');

        if (!empty($typeId)) {
            $builder->where('archive_type_id', $typeId);
        }

        if (!empty($opdId)) {
            $builder->where('opd_id', $opdId);
        }

        if (!empty($year)) {
            $builder->where('tahun_dokumen', $year);
        }

        if (!empty($status)) {
            $builder->where('status', $status);
        }

        if ($query !== '') {
            $builder->search($query);
        }

        $items = $builder->paginate(20)->withQueryString();

        // Selected type object for custom columns if filtered by type
        $currentType = !empty($typeId) ? ArchiveType::find($typeId) : null;

        return view('elabel.dynamic.items.index', [
            'items'        => $items,
            'types'        => $types,
            'opds'         => $opds,
            'selectedType' => $typeId,
            'selectedOpd'  => $opdId,
            'selectedYear' => $year,
            'selectedStatus'=> $status,
            'searchQuery'  => $query,
            'currentType'  => $currentType,
            'activeMenu'   => 'dynamic_items',
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $types = ArchiveType::active()->orderBy('nama', 'asc')->get();
        if ($types->isEmpty()) {
            return redirect()->route('elabel.dynamic.types.create')
                ->with('warning', 'Silakan buat minimal 1 Jenis Arsip terlebih dahulu sebelum menginput dokumen.');
        }

        $typeId = $request->get('type_id') ?: $types->first()->id;
        $selectedType = ArchiveType::find($typeId) ?: $types->first();

        $boxes = ArchiveBox::where('archive_type_id', $selectedType->id)->orderBy('nomor_box', 'asc')->get();
        $opds  = Opd::orderBy('nama', 'asc')->get();

        return view('elabel.dynamic.items.create', [
            'types'        => $types,
            'selectedType' => $selectedType,
            'boxes'        => $boxes,
            'opds'         => $opds,
            'activeMenu'   => 'dynamic_items',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'archive_type_id' => 'required|exists:archive_types,id',
            'nomor_dokumen'   => 'required|string|max:150',
            'nama_dokumen'    => 'required|string|max:255',
            'tahun_dokumen'   => 'nullable|integer|min:1900|max:2100',
            'opd_id'          => 'nullable|exists:opds,id',
            'archive_box_id'  => 'nullable|exists:archive_boxes,id',
            'status'          => 'nullable|string|max:50',
            'keterangan'      => 'nullable|string',
            'file_scan_pdf'   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480', // max 20MB
        ]);

        $type = ArchiveType::findOrFail($request->archive_type_id);

        // Validasi dan susun dynamic metadata dari input request
        $metadata = [];
        $dynamicAttachments = [];
        $schemaFields = $type->schema_fields ?? [];

        foreach ($schemaFields as $field) {
            $fieldName = $field['name'] ?? null;
            if (!$fieldName) continue;

            if (($field['type'] ?? '') === 'file') {
                if ($request->hasFile("meta_{$fieldName}")) {
                    $dynamicAttachments[$fieldName] = $request->file("meta_{$fieldName}");
                }
            } else {
                $value = $request->input("meta_{$fieldName}");
                if ($field['required'] && ($value === null || $value === '')) {
                    return redirect()->back()->withInput()->with('error', "Kolom '{$field['label']}' wajib diisi.");
                }
                $metadata[$fieldName] = $value;
            }
        }

        $data = [
            'archive_box_id' => $request->archive_box_id,
            'opd_id'         => $request->opd_id,
            'nomor_dokumen'  => $request->nomor_dokumen,
            'nama_dokumen'   => $request->nama_dokumen,
            'tahun_dokumen'  => $request->tahun_dokumen,
            'metadata'       => $metadata,
            'status'         => $request->status ?: 'Tersedia',
            'keterangan'     => $request->keterangan,
        ];

        $pdfFile = $request->file('file_scan_pdf');

        $item = $this->archiveService->createItem($type, $data, $pdfFile, $dynamicAttachments);

        return redirect()->route('elabel.dynamic.items.show', $item->id)
            ->with('success', "Dokumen '{$item->nomor_dokumen}' berhasil ditambahkan ke kategori {$type->nama}!");
    }

    public function show(int $id): View|RedirectResponse
    {
        $item = ArchiveItem::with(['archiveType', 'box', 'opd', 'inputUser', 'attachments', 'loans.user', 'loans.opd'])->find($id);
        if (!$item) {
            return redirect()->route('elabel.dynamic.items.index')->with('error', 'Dokumen arsip tidak ditemukan.');
        }

        return view('elabel.dynamic.items.show', [
            'item'       => $item,
            'activeMenu' => 'dynamic_items',
        ]);
    }

    public function edit(int $id): View|RedirectResponse
    {
        $item = ArchiveItem::with(['archiveType', 'box', 'opd', 'attachments'])->find($id);
        if (!$item) {
            return redirect()->route('elabel.dynamic.items.index')->with('error', 'Dokumen arsip tidak ditemukan.');
        }

        $boxes = ArchiveBox::where('archive_type_id', $item->archive_type_id)->orderBy('nomor_box', 'asc')->get();
        $opds  = Opd::orderBy('nama', 'asc')->get();

        return view('elabel.dynamic.items.edit', [
            'item'       => $item,
            'boxes'      => $boxes,
            'opds'       => $opds,
            'activeMenu' => 'dynamic_items',
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $item = ArchiveItem::with('archiveType')->find($id);
        if (!$item) {
            return redirect()->route('elabel.dynamic.items.index')->with('error', 'Dokumen arsip tidak ditemukan.');
        }

        $request->validate([
            'nomor_dokumen' => 'required|string|max:150',
            'nama_dokumen'  => 'required|string|max:255',
            'tahun_dokumen' => 'nullable|integer|min:1900|max:2100',
            'opd_id'        => 'nullable|exists:opds,id',
            'archive_box_id'=> 'nullable|exists:archive_boxes,id',
            'status'        => 'nullable|string|max:50',
            'keterangan'    => 'nullable|string',
            'file_scan_pdf' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
        ]);

        $type = $item->archiveType;
        $metadata = $item->metadata ?? [];
        $dynamicAttachments = [];
        $schemaFields = $type->schema_fields ?? [];

        foreach ($schemaFields as $field) {
            $fieldName = $field['name'] ?? null;
            if (!$fieldName) continue;

            if (($field['type'] ?? '') === 'file') {
                if ($request->hasFile("meta_{$fieldName}")) {
                    $dynamicAttachments[$fieldName] = $request->file("meta_{$fieldName}");
                }
            } else {
                $value = $request->input("meta_{$fieldName}");
                if ($field['required'] && ($value === null || $value === '')) {
                    return redirect()->back()->withInput()->with('error', "Kolom '{$field['label']}' wajib diisi.");
                }
                $metadata[$fieldName] = $value;
            }
        }

        $data = [
            'archive_box_id' => $request->archive_box_id,
            'opd_id'         => $request->opd_id,
            'nomor_dokumen'  => $request->nomor_dokumen,
            'nama_dokumen'   => $request->nama_dokumen,
            'tahun_dokumen'  => $request->tahun_dokumen,
            'metadata'       => $metadata,
            'status'         => $request->status ?: $item->status,
            'keterangan'     => $request->keterangan,
        ];

        $pdfFile = $request->file('file_scan_pdf');

        $this->archiveService->updateItem($item, $data, $pdfFile, $dynamicAttachments);

        return redirect()->route('elabel.dynamic.items.show', $item->id)
            ->with('success', "Dokumen '{$item->nomor_dokumen}' berhasil diperbarui!");
    }

    public function destroy(int $id): RedirectResponse
    {
        $item = ArchiveItem::with(['archiveType', 'attachments'])->find($id);
        if (!$item) {
            return redirect()->route('elabel.dynamic.items.index')->with('error', 'Dokumen arsip tidak ditemukan.');
        }

        $nomor = $item->nomor_dokumen;
        $this->archiveService->deleteItem($item);

        return redirect()->route('elabel.dynamic.items.index')
            ->with('success', "Dokumen '{$nomor}' berhasil dihapus.");
    }

    public function viewPdf(int $id)
    {
        $item = ArchiveItem::find($id);
        if (!$item || empty($item->file_scan_pdf) || !Storage::disk('public')->exists($item->file_scan_pdf)) {
            abort(404, 'Berkas scan tidak ditemukan di storage server.');
        }

        $filePath = Storage::disk('public')->path($item->file_scan_pdf);
        $mimeType = mime_content_type($filePath) ?: 'application/pdf';

        return response()->file($filePath, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"',
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $typeId = $request->get('type_id');
        $query  = trim((string) $request->get('q'));

        $builder = ArchiveItem::with(['archiveType', 'box', 'opd'])->orderBy('id', 'desc');
        if (!empty($typeId)) {
            $builder->where('archive_type_id', $typeId);
        }
        if ($query !== '') {
            $builder->search($query);
        }

        $items = $builder->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Katalog Arsip');

        // Headers
        $headers = ['No', 'Jenis Arsip', 'Nomor Dokumen', 'Nama Dokumen', 'Tahun', 'OPD / Unit Pengolah', 'Nomor Box', 'Lokasi Rak', 'Status', 'Keterangan'];
        $sheet->fromArray($headers, null, 'A1');

        // Style header row
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

        $rowNum = 2;
        foreach ($items as $idx => $doc) {
            $sheet->fromArray([
                $idx + 1,
                $doc->archiveType->nama ?? '-',
                $doc->nomor_dokumen,
                $doc->nama_dokumen,
                $doc->tahun_dokumen ?? '-',
                $doc->opd->nama ?? '-',
                $doc->box->nomor_box ?? 'Belum Di-box',
                $doc->box->lokasi_rak ?? '-',
                $doc->status,
                $doc->keterangan ?? '-',
            ], null, 'A' . $rowNum);
            $rowNum++;
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'Katalog_Arsip_Dinamis_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
