<?php

namespace App\Http\Controllers\Elabel\Dynamic;

use App\Http\Controllers\Controller;
use App\Models\Elabel\Dynamic\ArchiveBox;
use App\Models\Elabel\Dynamic\ArchiveType;
use App\Services\Elabel\DynamicArchiveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ArchiveBoxController extends Controller implements HasMiddleware
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
        $typeId = $request->get('type_id');
        $query = trim((string) $request->get('q'));

        $types = ArchiveType::active()->orderBy('nama', 'asc')->get();

        $builder = ArchiveBox::with(['archiveType', 'creator'])->withCount('items')->orderBy('id', 'desc');

        if (!empty($typeId)) {
            $builder->where('archive_type_id', $typeId);
        }

        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('nomor_box', 'LIKE', "%{$query}%")
                  ->orWhere('barcode_code', 'LIKE', "%{$query}%")
                  ->orWhere('lokasi_rak', 'LIKE', "%{$query}%")
                  ->orWhere('keterangan', 'LIKE', "%{$query}%");
            });
        }

        $boxes = $builder->paginate(20)->withQueryString();

        return view('elabel.dynamic.boxes.index', [
            'boxes'       => $boxes,
            'types'       => $types,
            'selectedType'=> $typeId,
            'searchQuery' => $query,
            'activeMenu'  => 'dynamic_boxes',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'archive_type_id'    => 'required|exists:archive_types,id',
            'nomor_box'          => 'nullable|string|max:100',
            'lokasi_rak'         => 'nullable|string|max:255',
            'tahun'              => 'nullable|integer|min:1900|max:2100',
            'kapasitas_maksimal' => 'nullable|integer|min:1|max:1000',
            'keterangan'         => 'nullable|string',
        ]);

        $type = ArchiveType::findOrFail($request->archive_type_id);

        $nomorBox = trim((string) $request->nomor_box);
        if (empty($nomorBox)) {
            $nomorBox = $this->archiveService->generateNextBoxCode($type);
        }

        $box = ArchiveBox::create([
            'archive_type_id'    => $type->id,
            'nomor_box'          => $nomorBox,
            'barcode_code'       => $nomorBox,
            'lokasi_rak'         => $request->lokasi_rak,
            'tahun'              => $request->tahun,
            'kapasitas_maksimal' => $request->kapasitas_maksimal ?: 100,
            'keterangan'         => $request->keterangan,
            'created_by'         => Auth::id(),
        ]);

        $this->archiveService->logActivity('create', 'Manajemen Box Dinamis', "Membuat box baru {$box->nomor_box} untuk kategori {$type->nama}", 'archive_box', $box->id);

        return redirect()->back()->with('success', "Box {$box->nomor_box} berhasil dibuat!");
    }

    public function show(int $id): View|RedirectResponse
    {
        $box = ArchiveBox::with(['archiveType', 'items.opd', 'items.attachments'])->find($id);
        if (!$box) {
            return redirect()->route('elabel.dynamic.boxes.index')->with('error', 'Box arsip tidak ditemukan.');
        }

        return view('elabel.dynamic.boxes.show', [
            'box'        => $box,
            'activeMenu' => 'dynamic_boxes',
        ]);
    }

    public function label(int $id): View|RedirectResponse
    {
        $box = ArchiveBox::with(['archiveType', 'items.opd'])->find($id);
        if (!$box) {
            return redirect()->route('elabel.dynamic.boxes.index')->with('error', 'Box arsip tidak ditemukan.');
        }

        return view('elabel.dynamic.boxes.label', [
            'box' => $box,
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        $box = ArchiveBox::withCount('items')->find($id);
        if (!$box) {
            return redirect()->route('elabel.dynamic.boxes.index')->with('error', 'Box arsip tidak ditemukan.');
        }

        if ($box->items_count > 0) {
            return redirect()->route('elabel.dynamic.boxes.index')->with('error', "Gagal menghapus box {$box->nomor_box} karena masih berisi {$box->items_count} berkas arsip.");
        }

        $nomor = $box->nomor_box;
        $box->delete();

        $this->archiveService->logActivity('delete', 'Manajemen Box Dinamis', "Menghapus box: {$nomor}", 'archive_box', $id);

        return redirect()->route('elabel.dynamic.boxes.index')->with('success', "Box {$nomor} berhasil dihapus.");
    }
}
