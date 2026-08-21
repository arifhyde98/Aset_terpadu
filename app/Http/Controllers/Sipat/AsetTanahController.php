<?php

namespace App\Http\Controllers\Sipat;

use App\Http\Controllers\Controller;
use App\Models\AsetTanah;
use App\Models\OpdSipat;
use App\Models\ProsesAset;
use App\Models\StatusProses;
use App\Services\SipatService;
use App\Http\Requests\Sipat\StoreAsetTanahRequest;
use App\Http\Requests\Sipat\UpdateAsetTanahRequest;
use App\Http\Requests\Sipat\StoreProsesAsetRequest;
use App\Http\Requests\Sipat\BulkStoreProsesRequest;
use App\Http\Requests\Sipat\StoreDokumenAsetRequest;
use App\Http\Requests\Sipat\StorePengamananFisikRequest;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AsetTanahController extends Controller
{
    protected $sipatService;

    public function __construct(SipatService $sipatService)
    {
        $this->sipatService = $sipatService;
    }

    public function index(Request $request)
    {
        $query = AsetTanah::with(['latestProses.statusProses']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('kode_aset', 'LIKE', "%{$search}%")
                  ->orWhere('nama_aset', 'LIKE', "%{$search}%")
                  ->orWhere('opd', 'LIKE', "%{$search}%")
                  ->orWhere('peruntukan', 'LIKE', "%{$search}%")
                  ->orWhere('alamat', 'LIKE', "%{$search}%")
                  ->orWhereExists(function($sub) use ($search) {
                      $sub->select(DB::raw(1))
                          ->from('elabel_sertifikat_tanah')
                          ->whereColumn('elabel_sertifikat_tanah.nibar', 'aset_tanah.kode_aset')
                          ->where(function($sub2) use ($search) {
                              $sub2->where('nama_pemilik', 'LIKE', "%{$search}%")
                                   ->orWhere('status_penggunaan', 'LIKE', "%{$search}%");
                          });
                  });
            });
        }

        if ($request->filled('opd')) {
            if ($request->input('opd') === 'KOSONG') {
                $query->where(function($q) {
                    $q->whereNull('opd')->orWhere('opd', '');
                });
            } else {
                $query->where('opd', $request->input('opd'));
            }
        }

        if ($request->has('status') && !empty($request->input('status'))) {
            $statusInput = (array) $request->input('status');
            $statusIds = array_filter($statusInput);
            if (!empty($statusIds)) {
                $query->whereHas('latestProses', function($q) use ($statusIds) {
                    $q->whereIn('id_status', $statusIds);
                });
            }
        }

        if ($request->filled('tanggal_perolehan')) {
            $query->whereDate('tanggal_perolehan', $request->input('tanggal_perolehan'));
        }

        $perPage = $request->input('per_page', 15);
        if ($perPage === 'all') {
            $asetTanah = $query->orderBy('id_aset', 'desc')->paginate(1000)->withQueryString();
        } else {
            $asetTanah = $query->orderBy('id_aset', 'desc')->paginate((int)$perPage)->withQueryString();
        }

        $opdList = OpdSipat::where('aktif', 1)->orderBy('nama', 'asc')->get();
        $statusList = StatusProses::orderBy('urutan', 'asc')->get();

        return view('sipat.aset.index', compact('asetTanah', 'opdList', 'statusList'));
    }

    public function create()
    {
        $opdList = OpdSipat::where('aktif', 1)->orderBy('nama', 'asc')->get();
        $statusList = StatusProses::orderBy('urutan', 'asc')->get();
        return view('sipat.aset.create', compact('opdList', 'statusList'));
    }

    public function store(StoreAsetTanahRequest $request)
    {
        $aset = AsetTanah::create($request->validated());

        if ($request->filled('initial_status_id')) {
            ProsesAset::create([
                'id_aset' => $aset->id_aset,
                'id_status' => $request->input('initial_status_id'),
                'tgl_mulai' => $request->input('tanggal_perolehan') ?? date('Y-m-d'),
                'keterangan' => 'Status awal pensertifikatan saat pendaftaran aset'
            ]);
        }

        Activity::logSipat("Menambahkan data aset tanah baru: {$aset->nama_aset} (NIB: {$aset->kode_aset})", 'success');

        return redirect()->route('sipat.aset.index')->with('success', 'Data Aset Tanah berhasil ditambahkan.');
    }

    public function show($id)
    {
        $aset = AsetTanah::with(['prosesAset.statusProses', 'latestProses.statusProses'])->findOrFail($id);
        return response()->json($aset);
    }

    public function modal($id)
    {
        $aset = AsetTanah::findOrFail($id);
        $prosesList = ProsesAset::with('statusProses')
            ->where('id_aset', $id)
            ->orderBy('id_proses', 'desc')
            ->get();
        $statusList = StatusProses::orderBy('urutan', 'asc')->get();
        $pengamanan = DB::table('pengamanan_fisik')->where('id_aset', $id)->first();
        $dokumenList = DB::table('dokumen_aset')->where('id_aset', $id)->orderBy('uploaded_at', 'desc')->get();

        $elabelSertifikat = null;
        if (!empty($aset->no_sertifikat)) {
            $cleanNo = trim($aset->no_sertifikat);
            $elabelSertifikat = \App\Models\Elabel\ElabelSertifikat::with('box')
                ->where('no_sertipikat', $cleanNo)
                ->orWhere('no_sertipikat', 'LIKE', '%' . $cleanNo . '%')
                ->first();
        }

        if (!$elabelSertifikat && (!empty($aset->kode_aset) || !empty($aset->nama_aset))) {
            $elabelSertifikat = \App\Models\Elabel\ElabelSertifikat::with('box')
                ->where(function ($q) use ($aset) {
                    if (!empty($aset->kode_aset)) {
                        $q->where('nibar', $aset->kode_aset);
                    }
                    if (!empty($aset->nama_aset)) {
                        $q->orWhere('nama_pemilik', 'LIKE', '%' . $aset->nama_aset . '%');
                    }
                })
                ->first();
        }

        return view('sipat.aset.modal', compact('aset', 'prosesList', 'statusList', 'pengamanan', 'dokumenList', 'elabelSertifikat'));
    }

    public function edit($id)
    {
        $aset = AsetTanah::findOrFail($id);
        $opdList = OpdSipat::where('aktif', 1)->orderBy('nama', 'asc')->get();
        $statusList = StatusProses::orderBy('urutan', 'asc')->get();
        return view('sipat.aset.edit', compact('aset', 'opdList', 'statusList'));
    }

    public function update(UpdateAsetTanahRequest $request, $id)
    {
        $aset = AsetTanah::findOrFail($id);
        $aset->update($request->validated());

        $konteks = $aset->peruntukan
            ? "Peruntukan: {$aset->peruntukan}"
            : "Peruntukan: -";

        Activity::logSipat("Memperbarui informasi aset tanah: {$aset->nama_aset} ({$konteks})", 'info');

        return redirect()->route('sipat.aset.index')->with('success', 'Data Aset Tanah berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $aset = AsetTanah::findOrFail($id);
        $kodeAset = $aset->kode_aset;
        $namaAset = $aset->nama_aset;
        ProsesAset::where('id_aset', $id)->delete();
        $aset->delete();

        Activity::logSipat("Menghapus data aset tanah secara permanen: {$namaAset} (NIB: {$kodeAset})", 'danger');

        return redirect()->route('sipat.aset.index')->with('success', 'Data Aset Tanah berhasil dihapus.');
    }

    public function storeProses(StoreProsesAsetRequest $request, $id)
    {
        $aset = AsetTanah::findOrFail($id);
        $validated = $request->validated();
        
        $durasi = $this->sipatService->calculateDuration($validated['tgl_mulai'] ?? null, $validated['tgl_selesai'] ?? null);

        $proses = ProsesAset::create([
            'id_aset'     => $aset->id_aset,
            'id_status'   => $validated['id_status'],
            'tgl_mulai'   => $validated['tgl_mulai'] ?? null,
            'tgl_selesai' => $validated['tgl_selesai'] ?? null,
            'keterangan'  => $validated['keterangan'] ?? null,
            'durasi_hari' => $durasi,
        ]);

        Activity::logSipat("Memperbarui status pengurusan BPN (Status baru: {$proses->statusProses->nama_status}) untuk aset tanah: {$aset->nama_aset}", 'success');

        return redirect()->route('sipat.aset.index')->with('success', 'Riwayat Proses BPN berhasil ditambahkan.');
    }

    public function bulkStoreProses(BulkStoreProsesRequest $request)
    {
        $validated = $request->validated();
        
        $asetIds = $validated['aset_ids'] ?? [];
        $idStatus = $validated['id_status'];
        $nibarListRaw = $validated['nibar_list'] ?? '';
        
        if (empty($asetIds) && empty($nibarListRaw)) {
            return redirect()->back()->with('error', 'Pilih minimal satu aset tanah atau masukkan NIBAR yang valid untuk diperbarui statusnya.');
        }

        $insertedCount = $this->sipatService->bulkUpdateStatus(
            $asetIds,
            $nibarListRaw,
            $idStatus,
            $validated['tgl_mulai'] ?? null,
            $validated['tgl_selesai'] ?? null,
            $validated['keterangan'] ?? null
        );

        if ($insertedCount === 0) {
            return redirect()->back()->with('error', 'Tidak ada data aset yang ditemukan untuk diproses.');
        }

        $statusName = \App\Models\StatusProses::find($idStatus)->nama_status ?? 'Unknown';
        Activity::logSipat("Memperbarui status pengurusan BPN secara massal menjadi '{$statusName}' untuk {$insertedCount} aset tanah", 'success');

        return redirect()->route('sipat.aset.index')->with('success', "Berhasil memperbarui status untuk {$insertedCount} aset.");
    }

    public function storePengamanan(StorePengamananFisikRequest $request, $id)
    {
        $aset = AsetTanah::findOrFail($id);
        $validated = $request->validated();

        DB::table('pengamanan_fisik')->updateOrInsert(
            ['id_aset' => $id],
            [
                'sertifikat_ada' => isset($validated['sertifikat_ada']) ? 1 : 0,
                'papan_nama' => isset($validated['papan_nama']) ? 1 : 0,
                'pagar' => isset($validated['pagar']) ? 1 : 0,
                'dikuasai_pihak_lain' => isset($validated['dikuasai_pihak_lain']) ? 1 : 0,
                'tgl_cek' => $validated['tgl_cek'] ?? date('Y-m-d'),
                'catatan' => $validated['catatan'] ?? null,
                'updated_at' => now(),
            ]
        );

        Activity::logSipat("Mencatat laporan pengamanan fisik lapangan untuk aset tanah: {$aset->nama_aset}", 'info');

        return redirect()->route('sipat.aset.index')->with('success', 'Status pengamanan fisik aset berhasil diperbarui.');
    }

    public function storeDokumen(StoreDokumenAsetRequest $request, $id)
    {
        $aset = AsetTanah::findOrFail($id);
        $validated = $request->validated();

        $path = null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('dokumen_aset', 'public');
        }

        DB::table('dokumen_aset')->insert([
            'id_aset' => $id,
            'jenis_dokumen' => $validated['jenis_dokumen'],
            'status_dokumen' => $validated['status_dokumen'] ?? 'Asli',
            'file_path' => $path,
            'uploaded_at' => now(),
        ]);

        Activity::logSipat("Mengunggah dokumen pendukung ({$validated['jenis_dokumen']}) untuk aset tanah: {$aset->nama_aset}", 'success');

        return redirect()->route('sipat.aset.index')->with('success', 'Dokumen lampiran aset berhasil diunggah.');
    }
}
