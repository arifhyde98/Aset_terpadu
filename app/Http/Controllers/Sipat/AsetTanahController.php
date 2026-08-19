<?php

namespace App\Http\Controllers\Sipat;

use App\Http\Controllers\Controller;
use App\Models\AsetTanah;
use App\Models\OpdSipat;
use App\Models\ProsesAset;
use App\Models\StatusProses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AsetTanahController extends Controller
{
    public function index(Request $request)
    {
        $query = AsetTanah::with(['latestProses.statusProses']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('kode_aset', 'LIKE', "%{$search}%")
                  ->orWhere('nama_aset', 'LIKE', "%{$search}%")
                  ->orWhere('opd', 'LIKE', "%{$search}%")
                  ->orWhere('alamat', 'LIKE', "%{$search}%");
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

    public function store(Request $request)
    {
        $request->validate([
            'kode_aset' => 'required|string|max:50|unique:aset_tanah,kode_aset',
            'nama_aset' => 'required|string|max:150',
            'peruntukan' => 'nullable|string|max:150',
            'luas' => 'nullable|numeric',
            'opd' => 'nullable|string|max:150',
            'alamat' => 'nullable|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'dasar_perolehan' => 'nullable|string|max:150',
            'harga_perolehan' => 'nullable|numeric',
            'tanggal_perolehan' => 'nullable|date',
            'keterangan' => 'nullable|string',
        ]);

        $aset = AsetTanah::create($request->all());

        // Save initial status if provided
        if ($request->filled('initial_status_id')) {
            ProsesAset::create([
                'id_aset' => $aset->id_aset,
                'id_status' => $request->input('initial_status_id'),
                'tgl_mulai' => $request->input('tanggal_perolehan') ?? date('Y-m-d'),
                'keterangan' => 'Status awal pensertifikatan saat pendaftaran aset'
            ]);
        }

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

        // Integrasi Real Model eLabel (ElabelSertifikat & ElabelSertifikatBox)
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

    public function update(Request $request, $id)
    {
        $aset = AsetTanah::findOrFail($id);

        $request->validate([
            'kode_aset' => 'required|string|max:50|unique:aset_tanah,kode_aset,' . $id . ',id_aset',
            'nama_aset' => 'required|string|max:150',
            'peruntukan' => 'nullable|string|max:150',
            'luas' => 'nullable|numeric',
            'opd' => 'nullable|string|max:150',
            'alamat' => 'nullable|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'dasar_perolehan' => 'nullable|string|max:150',
            'harga_perolehan' => 'nullable|numeric',
            'tanggal_perolehan' => 'nullable|date',
            'keterangan' => 'nullable|string',
        ]);

        $aset->update($request->all());

        return redirect()->route('sipat.aset.index')->with('success', 'Data Aset Tanah berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $aset = AsetTanah::findOrFail($id);
        
        // Delete related processes
        ProsesAset::where('id_aset', $id)->delete();
        $aset->delete();

        return redirect()->route('sipat.aset.index')->with('success', 'Data Aset Tanah berhasil dihapus.');
    }

    public function storeProses(Request $request, $id)
    {
        $aset = AsetTanah::findOrFail($id);

        $request->validate([
            'id_status' => 'required|integer|exists:status_proses,id_status',
            'tgl_mulai' => 'nullable|date',
            'tgl_selesai' => 'nullable|date',
            'keterangan' => 'nullable|string',
        ]);

        $durasi = null;
        if ($request->filled('tgl_mulai') && $request->filled('tgl_selesai')) {
            $durasi = (int) floor((strtotime($request->tgl_selesai) - strtotime($request->tgl_mulai)) / 86400);
            if ($durasi < 0) $durasi = null;
        }

        ProsesAset::create([
            'id_aset' => $aset->id_aset,
            'id_status' => $request->id_status,
            'tgl_mulai' => $request->tgl_mulai,
            'tgl_selesai' => $request->tgl_selesai,
            'keterangan' => $request->keterangan,
            'durasi_hari' => $durasi,
        ]);

        return redirect()->route('sipat.aset.index')->with('success', 'Riwayat Proses BPN berhasil ditambahkan.');
    }

    public function bulkStoreProses(Request $request)
    {
        $asetIds = (array) ($request->input('aset_ids') ?? []);
        $idStatus = $request->input('id_status');
        $nibarListRaw = (string) $request->input('nibar_list');

        if (trim($nibarListRaw) !== '') {
            $nibarItems = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $nibarListRaw))));
            if (!empty($nibarItems)) {
                $rows = DB::table('aset_tanah')
                    ->select('id_aset')
                    ->whereIn('kode_aset', $nibarItems)
                    ->get();
                foreach ($rows as $r) {
                    $asetIds[] = (int) $r->id_aset;
                }
            }
        }

        $asetIds = array_values(array_unique(array_filter(array_map('intval', $asetIds))));

        if (empty($asetIds)) {
            return redirect()->back()->with('error', 'Pilih minimal satu aset tanah atau masukkan NIBAR yang valid untuk diperbarui statusnya.');
        }

        if (empty($idStatus)) {
            return redirect()->back()->with('error', 'Status proses wajib dipilih.');
        }

        $tglMulai = $request->input('tgl_mulai');
        $tglSelesai = $request->input('tgl_selesai');
        $keterangan = $request->input('keterangan');
        $durasi = null;

        if (!empty($tglMulai) && !empty($tglSelesai)) {
            $durasi = (int) floor((strtotime($tglSelesai) - strtotime($tglMulai)) / 86400);
            if ($durasi < 0) {
                $durasi = null;
            }
        }

        $insertedCount = 0;

        foreach ($asetIds as $idAset) {
            $idAset = (int) $idAset;
            if ($idAset <= 0) continue;

            ProsesAset::create([
                'id_aset'     => $idAset,
                'id_status'   => $idStatus,
                'tgl_mulai'   => $tglMulai ?: null,
                'tgl_selesai' => $tglSelesai ?: null,
                'keterangan'  => $keterangan ?: 'Update status massal',
                'durasi_hari' => $durasi,
            ]);
            $insertedCount++;
        }

        return redirect()->route('sipat.aset.index')->with('success', "Berhasil memperbarui status untuk {$insertedCount} aset.");
    }

    public function storePengamanan(Request $request, $id)
    {
        $aset = AsetTanah::findOrFail($id);

        DB::table('pengamanan_fisik')->updateOrInsert(
            ['id_aset' => $id],
            [
                'sertifikat_ada' => $request->has('sertifikat_ada') ? 1 : 0,
                'papan_nama' => $request->has('papan_nama') ? 1 : 0,
                'pagar' => $request->has('pagar') ? 1 : 0,
                'dikuasai_pihak_lain' => $request->has('dikuasai_pihak_lain') ? 1 : 0,
                'tgl_cek' => $request->input('tgl_cek') ?: date('Y-m-d'),
                'catatan' => $request->input('catatan'),
                'updated_at' => now(),
            ]
        );

        return redirect()->route('sipat.aset.index')->with('success', 'Status pengamanan fisik aset berhasil diperbarui.');
    }

    public function storeDokumen(Request $request, $id)
    {
        $aset = AsetTanah::findOrFail($id);

        $request->validate([
            'jenis_dokumen' => 'required|string|max:120',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $path = null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('dokumen_aset', 'public');
        }

        DB::table('dokumen_aset')->insert([
            'id_aset' => $id,
            'jenis_dokumen' => $request->input('jenis_dokumen'),
            'status_dokumen' => $request->input('status_dokumen', 'Asli'),
            'file_path' => $path,
            'uploaded_at' => now(),
        ]);

        return redirect()->route('sipat.aset.index')->with('success', 'Dokumen lampiran aset berhasil diunggah.');
    }
}
