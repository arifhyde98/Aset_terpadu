<?php

namespace App\Http\Controllers\Sipat;

use App\Http\Controllers\Controller;
use App\Models\AsetTanah;
use App\Models\OpdSipat;
use App\Models\StatusProses;
use App\Models\ProsesAset;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class TanahTakTercatatController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    /**
     * Menampilkan daftar bidang tanah yang belum tercatat / belum memiliki NIBAR resmi.
     */
    public function index(Request $request): View
    {
        $opdId = $request->filled('opd_id') ? (int) $request->input('opd_id') : null;
        $search = trim((string) $request->input('search', ''));

        $opdList = OpdSipat::where('aktif', 1)->orderBy('nama', 'asc')->get();
        $statusList = StatusProses::orderBy('urutan', 'asc')->get();

        $query = AsetTanah::with(['opdSipat', 'latestProses.statusProses'])
            ->where(function ($q) {
                $q->where('kode_aset', 'LIKE', 'DRAFT-%')
                  ->orWhere('kode_aset', 'LIKE', 'BELUM-%')
                  ->orWhereNull('kode_aset')
                  ->orWhere('kode_aset', '');
            });

        if ($opdId) {
            $query->where('opd_id', $opdId);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_aset', 'LIKE', "%{$search}%")
                  ->orWhere('nama_aset', 'LIKE', "%{$search}%")
                  ->orWhere('peruntukan', 'LIKE', "%{$search}%")
                  ->orWhere('alamat', 'LIKE', "%{$search}%")
                  ->orWhere('keterangan', 'LIKE', "%{$search}%");
            });
        }

        $tanahItems = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // Statistical summaries
        $totalUnrecorded = AsetTanah::where(function ($q) {
            $q->where('kode_aset', 'LIKE', 'DRAFT-%')
              ->orWhere('kode_aset', 'LIKE', 'BELUM-%')
              ->orWhereNull('kode_aset')
              ->orWhere('kode_aset', '');
        })->count();

        $totalDraftNibar = AsetTanah::where('kode_aset', 'LIKE', 'DRAFT-%')->count();
        $totalOpdCount = OpdSipat::where('aktif', 1)->count();

        return view('sipat.tanah_tak_tercatat.index', compact(
            'tanahItems',
            'opdList',
            'statusList',
            'opdId',
            'search',
            'totalUnrecorded',
            'totalDraftNibar',
            'totalOpdCount'
        ));
    }

    /**
     * Pendaftaran cepat tanah baru yang belum memiliki NIBAR resmi.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode_aset' => 'nullable|string|max:50|unique:aset_tanah,kode_aset',
            'nama_aset' => 'required|string|max:150',
            'opd_id' => 'nullable|integer|exists:opd,id',
            'peruntukan' => 'nullable|string|max:150',
            'luas' => 'nullable|numeric|min:0',
            'alamat' => 'nullable|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'dasar_perolehan' => 'nullable|string|max:150',
            'harga_perolehan' => 'nullable|numeric|min:0',
            'tanggal_perolehan' => 'nullable|date',
            'keterangan' => 'nullable|string',
            'initial_status_id' => 'nullable|integer|exists:status_proses,id_status',
        ]);

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

        $opdObj = !empty($validated['opd_id']) ? OpdSipat::find($validated['opd_id']) : null;

        $aset = AsetTanah::create([
            'kode_aset' => $kodeAset,
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
        ]);

        if (!empty($validated['initial_status_id'])) {
            ProsesAset::create([
                'id_aset' => $aset->id_aset,
                'status_proses_id' => $validated['initial_status_id'],
                'tanggal' => date('Y-m-d'),
                'keterangan' => 'Pendaftaran tanah belum tercatat baru',
            ]);
        }

        if (class_exists(Activity::class)) {
            Activity::logSipat("Mendaftarkan tanah belum tercatat baru '{$aset->nama_aset}' [Kode: {$kodeAset}]", 'success');
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

        $aset->update([
            'kode_aset' => $newCode,
            'keterangan' => $validated['keterangan'] ?? $aset->keterangan,
        ]);

        if (class_exists(Activity::class)) {
            Activity::logSipat("Memperbarui NIBAR sementara aset '{$aset->nama_aset}' dari '{$oldCode}' menjadi NIBAR resmi '{$newCode}'", 'success');
        }

        return redirect()->route('sipat.tanah-tak-tercatat.index')
            ->with('success', "NIBAR resmi untuk '{$aset->nama_aset}' berhasil diperbarui menjadi {$newCode}.");
    }
}
