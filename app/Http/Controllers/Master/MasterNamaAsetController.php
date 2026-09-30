<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MasterNamaAset;
use App\Models\AsetTanah;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class MasterNamaAsetController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:superadmin,admin'),
        ];
    }

    public function index(Request $request)
    {
        $query = MasterNamaAset::withCount('asetTanah');

        if ($request->filled('q')) {
            $search = trim($request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode_barang', 'like', "%{$search}%")
                  ->orWhere('kelompok', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kelompok')) {
            $query->where('kelompok', $request->input('kelompok'));
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $namaAsets = $query->orderBy('urutan', 'asc')
            ->orderBy('nama', 'asc')
            ->paginate(25)
            ->withQueryString();

        $kelompokList = MasterNamaAset::select('kelompok')
            ->whereNotNull('kelompok')
            ->where('kelompok', '!=', '')
            ->distinct()
            ->orderBy('kelompok')
            ->pluck('kelompok');

        $totalActive = MasterNamaAset::where('is_active', true)->count();
        $totalAll = MasterNamaAset::count();

        return view('master.nama_aset.index', compact(
            'namaAsets',
            'kelompokList',
            'totalActive',
            'totalAll'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama'        => 'required|string|max:150|unique:master_nama_aset,nama',
            'kode_barang' => 'nullable|string|max:50',
            'kelompok'    => 'nullable|string|max:100',
            'deskripsi'   => 'nullable|string|max:255',
            'urutan'      => 'nullable|integer|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : true;
        $validated['urutan'] = $validated['urutan'] ?? 0;

        MasterNamaAset::create($validated);

        return redirect()->route('master.nama-aset.index')
            ->with('success', 'Master Nama Aset baru berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $master = MasterNamaAset::findOrFail($id);

        $validated = $request->validate([
            'nama'        => 'required|string|max:150|unique:master_nama_aset,nama,' . $master->id,
            'kode_barang' => 'nullable|string|max:50',
            'kelompok'    => 'nullable|string|max:100',
            'deskripsi'   => 'nullable|string|max:255',
            'urutan'      => 'nullable|integer|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        $oldNama = $master->nama;
        $validated['is_active'] = $request->has('is_active');
        $validated['urutan'] = $validated['urutan'] ?? 0;

        $master->update($validated);

        // Sinkronisasi otomatis ke nama_aset di aset_tanah jika nama berubah agar konsisten di laporan legacy
        if ($oldNama !== $master->nama) {
            AsetTanah::where('nama_aset_id', $master->id)
                ->update(['nama_aset' => $master->nama]);
        }

        return redirect()->route('master.nama-aset.index')
            ->with('success', 'Master Nama Aset berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $master = MasterNamaAset::withCount('asetTanah')->findOrFail($id);

        if ($master->aset_tanah_count > 0) {
            return redirect()->route('master.nama-aset.index')
                ->with('error', "Nama aset '{$master->nama}' tidak dapat dihapus karena masih digunakan oleh {$master->aset_tanah_count} data aset tanah. Anda dapat menonaktifkannya.");
        }

        $master->delete();

        return redirect()->route('master.nama-aset.index')
            ->with('success', 'Master Nama Aset berhasil dihapus.');
    }

    public function toggleStatus($id)
    {
        $master = MasterNamaAset::findOrFail($id);
        $master->is_active = !$master->is_active;
        $master->save();

        $statusText = $master->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->back()
            ->with('success', "Status master aset '{$master->nama}' berhasil {$statusText}.");
    }
}
