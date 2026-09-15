<?php

namespace App\Http\Controllers\Erandis;

use App\Http\Controllers\Controller;
use App\Models\SubOpd;
use App\Models\Opd;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Controller untuk Manajemen Master Data Sub-OPD / Kuasa Pengguna Barang (KPB)
 */
class SubOpdController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:superadmin,admin', except: ['getByOpd']),
        ];
    }

    /**
     * Menampilkan daftar semua Sub-OPD / KPB.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $query = SubOpd::with(['opd'])->withCount(['vehicles', 'ebmdVehicles']);

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode_sub', 'like', "%{$search}%")
                  ->orWhere('nama_pimpinan', 'like', "%{$search}%")
                  ->orWhereHas('opd', function ($opdQ) use ($search) {
                      $opdQ->where('nama', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('opd_id')) {
            $query->where('opd_id', $request->opd_id);
        }

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        $subOpds = $query->orderBy('nama', 'asc')->paginate(15)->withQueryString();
        $opds = Opd::orderBy('nama', 'asc')->get();

        return view('sub_opds.index', compact('subOpds', 'opds'));
    }

    /**
     * Menyimpan Sub-OPD baru.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'opd_id'        => 'required|exists:opds,id',
            'nama'          => 'required|string|max:255',
            'kode_sub'      => 'nullable|string|max:100',
            'jenis'         => 'required|in:puskesmas,uptd,bagian,sekolah,rsud,lainnya',
            'alamat'        => 'nullable|string',
            'nama_pimpinan' => 'nullable|string|max:255',
            'nip_pimpinan'  => 'nullable|string|max:50',
            'aktif'         => 'nullable|boolean',
        ]);

        $validated['aktif'] = $request->has('aktif') ? (bool) $request->aktif : true;

        $subOpd = SubOpd::create($validated);

        Activity::log("Menambahkan Sub-OPD / KPB baru: {$subOpd->nama}", 'info');

        return redirect()->route('sub-opds.index')
            ->with('success', "Sub-OPD \"{$subOpd->nama}\" berhasil ditambahkan.");
    }

    /**
     * Memperbarui data Sub-OPD.
     *
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $subOpd = SubOpd::findOrFail($id);

        $validated = $request->validate([
            'opd_id'        => 'required|exists:opds,id',
            'nama'          => 'required|string|max:255',
            'kode_sub'      => 'nullable|string|max:100',
            'jenis'         => 'required|in:puskesmas,uptd,bagian,sekolah,rsud,lainnya',
            'alamat'        => 'nullable|string',
            'nama_pimpinan' => 'nullable|string|max:255',
            'nip_pimpinan'  => 'nullable|string|max:50',
            'aktif'         => 'nullable|boolean',
        ]);

        $validated['aktif'] = $request->has('aktif') ? (bool) $request->aktif : true;

        $subOpd->update($validated);

        Activity::log("Memperbarui data Sub-OPD / KPB: {$subOpd->nama}", 'info');

        return redirect()->route('sub-opds.index')
            ->with('success', "Sub-OPD \"{$subOpd->nama}\" berhasil diperbarui.");
    }

    /**
     * Menghapus data Sub-OPD.
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        $subOpd = SubOpd::findOrFail($id);
        $nama = $subOpd->nama;

        $subOpd->delete();

        Activity::log("Menghapus Sub-OPD / KPB: {$nama}", 'warning');

        return redirect()->route('sub-opds.index')
            ->with('success', "Sub-OPD \"{$nama}\" berhasil dihapus.");
    }

    /**
     * Endpoint API JSON untuk memuat Sub-OPD berdasarkan OPD Induk (Chained Dropdown).
     *
     * @param int $opdId
     * @return JsonResponse
     */
    public function getByOpd(int $opdId): JsonResponse
    {
        $subOpds = SubOpd::where('opd_id', $opdId)
            ->where('aktif', true)
            ->orderBy('nama', 'asc')
            ->get(['id', 'nama', 'jenis', 'kode_sub']);

        return response()->json($subOpds);
    }
}
