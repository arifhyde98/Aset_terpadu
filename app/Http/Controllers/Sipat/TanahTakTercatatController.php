<?php

namespace App\Http\Controllers\Sipat;

use App\Http\Controllers\Controller;
use App\Models\AsetTanah;
use App\Models\Opd;
use App\Models\StatusProses;
use App\Models\ProsesAset;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TanahTakTercatatController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:superadmin,admin', only: ['updateNibar']),
        ];
    }

    /**
     * Menampilkan daftar bidang tanah yang belum tercatat / belum memiliki NIBAR resmi.
     */
    public function index(Request $request): View
    {
        $opdId = $request->filled('opd_id') ? (int) $request->input('opd_id') : null;
        $kecamatanId = $request->filled('kecamatan_id') ? (int) $request->input('kecamatan_id') : null;
        $desaId = $request->input('desa_id');
        $search = trim((string) $request->input('search', ''));

        $user = auth()->user();
        if ($user && in_array($user->role, [UserRole::OPD, UserRole::KPB])) {
            $opdId = (int) $user->opd_id;
            $opdList = Opd::where('id', $user->opd_id)->get();
        } else {
            $opdList = Opd::where('aktif', 1)->orderBy('nama', 'asc')->get();
        }
        $statusList = StatusProses::orderBy('urutan', 'asc')->get();
        $kecamatanList = \App\Models\Kecamatan::orderBy('nama', 'asc')->get();
        $desaList = \App\Models\Desa::with('kecamatan')->orderBy('nama', 'asc')->get();

        $query = AsetTanah::with(['opdSipat', 'latestProses.statusProses', 'masterNamaAset', 'wilayahKecamatan', 'wilayahDesa'])
            ->where('status_pencatatan', 'USULAN_BELUM_TERCATAT');

        if ($opdId) {
            $query->where('opd_id', $opdId);
        }

        if ($kecamatanId) {
            $query->where('kecamatan_id', $kecamatanId);
        }

        if ($desaId === 'tanpa_desa') {
            $query->whereNull('desa_id');
        } elseif (!empty($desaId)) {
            $query->where('desa_id', (int) $desaId);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_aset', 'LIKE', "%{$search}%")
                  ->orWhere('nama_aset', 'LIKE', "%{$search}%")
                  ->orWhere('peruntukan', 'LIKE', "%{$search}%")
                  ->orWhere('alamat', 'LIKE', "%{$search}%")
                  ->orWhere('keterangan', 'LIKE', "%{$search}%")
                  ->orWhereHas('wilayahDesa', function ($d) use ($search) {
                      $d->where('nama', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('wilayahKecamatan', function ($k) use ($search) {
                      $k->where('nama', 'LIKE', "%{$search}%");
                  });
            });
        }

        $tanahItems = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // Statistical summaries
        $totalUnrecorded = AsetTanah::where('status_pencatatan', 'USULAN_BELUM_TERCATAT')->count();
        $totalDraftNibar = AsetTanah::where('status_pencatatan', 'USULAN_BELUM_TERCATAT')->where('kode_aset', 'LIKE', 'DRAFT-%')->count();
        $totalOpdCount = Opd::where('aktif', 1)->count();
        $masterNamaAsetList = \App\Models\MasterNamaAset::active()->ordered()->get();

        return view('sipat.tanah_tak_tercatat.index', compact(
            'tanahItems',
            'opdList',
            'statusList',
            'kecamatanList',
            'desaList',
            'opdId',
            'kecamatanId',
            'desaId',
            'search',
            'totalUnrecorded',
            'totalDraftNibar',
            'totalOpdCount',
            'masterNamaAsetList'
        ));
    }

    /**
     * Pendaftaran cepat tanah baru yang belum memiliki NIBAR resmi.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode_aset' => 'nullable|string|max:50|unique:aset_tanah,kode_aset',
            'nama_aset_id' => 'nullable|integer|exists:master_nama_aset,id',
            'nama_aset' => 'required_without:nama_aset_id|nullable|string|max:150',
            'opd_id' => 'nullable|integer|exists:opds,id',
            'peruntukan' => 'nullable|string|max:150',
            'luas' => 'nullable|numeric|min:0',
            'alamat' => 'nullable|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'dasar_perolehan' => 'nullable|string|max:150',
            'harga_perolehan' => 'nullable|numeric|min:0',
            'tanggal_perolehan' => 'nullable|date',
            'keterangan' => 'nullable|string',
            'kecamatan_id' => 'nullable|integer|exists:kecamatan,id',
            'desa_id' => 'nullable|integer|exists:desa,id',
            'initial_status_id' => 'nullable|integer|exists:status_proses,id_status',
        ]);

        // Penyelarasan dua arah nama_aset_id & nama_aset
        if (!empty($validated['nama_aset_id'])) {
            $master = \App\Models\MasterNamaAset::find($validated['nama_aset_id']);
            if ($master) {
                $validated['nama_aset'] = $master->nama;
            }
        } elseif (!empty($validated['nama_aset'])) {
            $cleanName = trim(preg_replace('/\s+/', ' ', str_replace(["\r", "\n"], ' ', $validated['nama_aset'])));
            $master = \App\Models\MasterNamaAset::firstOrCreate(
                ['nama' => $cleanName],
                ['is_active' => true, 'kelompok' => 'Lain-Lain']
            );
            $validated['nama_aset_id'] = $master->id;
            $validated['nama_aset'] = $cleanName;
        }

        // Generate NIBAR sementara otomatis jika kosong
        $kodeAset = $validated['kode_aset'] ?? null;
        if (empty($kodeAset)) {
            $prefix = 'DRAFT-' . date('Ymd') . '-';
            $counter = 1;
            do {
                $candidateCode = $prefix . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
                $exists = AsetTanah::where('kode_aset', $candidateCode)->exists();
                $counter++;
            } while ($exists);
            $kodeAset = $candidateCode;
        }

        $user = auth()->user();
        if ($user && in_array($user->role, [UserRole::OPD, UserRole::KPB])) {
            $validated['opd_id'] = $user->opd_id;
        }

        $opdObj = !empty($validated['opd_id']) ? Opd::find($validated['opd_id']) : null;

        $aset = DB::transaction(function () use ($validated, $kodeAset, $opdObj) {
            $aset = AsetTanah::create([
                'kode_aset' => $kodeAset,
                'status_pencatatan' => 'USULAN_BELUM_TERCATAT',
                'nama_aset_id' => $validated['nama_aset_id'] ?? null,
                'nama_aset' => $validated['nama_aset'],
                'opd_id' => $validated['opd_id'] ?? null,
                'opd' => $opdObj?->nama ?? null,
                'peruntukan' => $validated['peruntukan'] ?? null,
                'luas' => $validated['luas'] ?? 0,
                'alamat' => $validated['alamat'] ?? null,
                'lat' => $validated['lat'] ?? null,
                'lng' => $validated['lng'] ?? null,
                'dasar_perolehan' => $validated['dasar_perolehan'] ?? null,
                'harga_perolehan' => $validated['harga_perolehan'] ?? null,
                'tanggal_perolehan' => $validated['tanggal_perolehan'] ?? null,
                'keterangan' => $validated['keterangan'] ?? 'Tanah belum tercatat di KIB A (NIBAR Draft)',
                'kecamatan_id' => $validated['kecamatan_id'] ?? null,
                'desa_id' => $validated['desa_id'] ?? null,
            ]);

            if (!empty($validated['initial_status_id'])) {
                ProsesAset::create([
                    'id_aset' => $aset->id_aset,
                    'id_status' => (int) $validated['initial_status_id'],
                    'tanggal_proses' => now()->toDateString(),
                    'tgl_mulai' => now()->toDateString(),
                    'keterangan' => 'Pendaftaran tanah belum tercatat baru',
                ]);
            }

            return $aset;
        });

        if (class_exists(Activity::class)) {
            $newPayload = array_merge(['nibar' => $aset->kode_aset, 'nama_aset' => $aset->nama_aset], $aset->toArray());
            Activity::logSipat("Mendaftarkan tanah belum tercatat baru '{$aset->nama_aset}' [Kode: {$kodeAset}]", 'success', null, $newPayload);
        }

        return redirect()->route('sipat.tanah-tak-tercatat.index')
            ->with('success', "Berhasil mendaftarkan aset tanah baru dengan Kode Sementara: {$kodeAset}.");
    }

    /**
     * Memperbarui NIBAR sementara menjadi NIBAR resmi KIB A dari BPKAD.
     */
    public function updateNibar(Request $request, AsetTanah $aset): RedirectResponse
    {
        $validated = $request->validate([
            'kode_aset' => [
                'required',
                'string',
                'max:50',
                Rule::unique('aset_tanah', 'kode_aset')->ignore($aset->id_aset, 'id_aset'),
            ],
            'keterangan' => 'nullable|string',
        ], [
            'kode_aset.required' => 'NIBAR / Kode Aset resmi wajib diisi.',
            'kode_aset.unique' => 'NIBAR / Kode Aset ini sudah digunakan oleh aset tanah lain.',
        ]);

        $oldCode = $aset->kode_aset;
        $newCode = trim($validated['kode_aset']);

        $oldPayload = ['nibar' => $oldCode, 'kode_aset' => $oldCode, 'nama_aset' => $aset->nama_aset, 'status_pencatatan' => $aset->status_pencatatan];

        $aset->update([
            'kode_aset' => $newCode,
            'status_pencatatan' => 'TERCATAT_KIB_A',
            'keterangan' => $validated['keterangan'] ?? $aset->keterangan,
        ]);

        $newPayload = ['nibar' => $newCode, 'kode_aset' => $newCode, 'nama_aset' => $aset->nama_aset, 'status_pencatatan' => 'TERCATAT_KIB_A'];

        if (class_exists(Activity::class)) {
            Activity::logSipat("Memperbarui NIBAR sementara aset '{$aset->nama_aset}' dari '{$oldCode}' menjadi NIBAR resmi '{$newCode}'", 'success', $oldPayload, $newPayload);
        }

        return redirect()->route('sipat.tanah-tak-tercatat.index')
            ->with('success', "NIBAR resmi untuk '{$aset->nama_aset}' berhasil diperbarui menjadi {$newCode}.");
    }
}
