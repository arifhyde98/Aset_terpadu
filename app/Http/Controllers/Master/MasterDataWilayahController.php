<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kecamatan;
use App\Models\Desa;
use App\Models\Camat;
use App\Models\KepalaDesa;
use App\Models\Pemohon;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class MasterDataWilayahController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:superadmin,admin'),
        ];
    }

    public function index()
    {
        $kecamatan = Kecamatan::orderBy('nama', 'asc')->get();
        $desa = Desa::with('kecamatan')->orderBy('nama', 'asc')->get();
        $kades = KepalaDesa::with('desa.kecamatan')->orderBy('nama', 'asc')->get();
        $camat = Camat::with('kecamatan')->orderBy('nama', 'asc')->get();
        $pemohon = Pemohon::orderBy('nama', 'asc')->get();
        $judul = DB::table('report_titles')->orderBy('judul', 'asc')->get();

        return view('master.wilayah.index', compact(
            'kecamatan', 'desa', 'kades', 'camat', 'pemohon', 'judul'
        ));
    }

    // === KECAMATAN ===

    public function kecamatanStore(Request $request)
    {
        $validated = $request->validate(['nama' => 'required|string|max:150']);
        Kecamatan::create($validated);
        app(\App\Services\Sipat\SipatService::class)->invalidateDashboardCache();
        return redirect()->route('master.wilayah.index')->with('success', 'Kecamatan berhasil ditambahkan.')->with('active_tab', 'kecamatan');
    }

    public function kecamatanUpdate(Request $request, $id)
    {
        $validated = $request->validate(['nama' => 'required|string|max:150']);
        $row = Kecamatan::findOrFail($id);
        $row->update($validated);
        app(\App\Services\Sipat\SipatService::class)->invalidateDashboardCache();
        return redirect()->route('master.wilayah.index')->with('success', 'Kecamatan berhasil diperbarui.')->with('active_tab', 'kecamatan');
    }

    public function kecamatanDestroy($id)
    {
        $row = Kecamatan::findOrFail($id);
        if (\App\Models\AsetTanah::where('kecamatan_id', $id)->exists()) {
            return redirect()->route('master.wilayah.index')->with('error', 'Kecamatan tidak dapat dihapus karena masih digunakan oleh data aset tanah.')->with('active_tab', 'kecamatan');
        }
        if (Desa::where('kecamatan_id', $id)->exists()) {
            return redirect()->route('master.wilayah.index')->with('error', 'Kecamatan tidak dapat dihapus karena memiliki data desa terkait.')->with('active_tab', 'kecamatan');
        }
        if (Camat::where('kecamatan_id', $id)->exists()) {
            return redirect()->route('master.wilayah.index')->with('error', 'Kecamatan tidak dapat dihapus karena memiliki data Camat terkait.')->with('active_tab', 'kecamatan');
        }
        $row->delete();
        app(\App\Services\Sipat\SipatService::class)->invalidateDashboardCache();
        return redirect()->route('master.wilayah.index')->with('success', 'Kecamatan berhasil dihapus.')->with('active_tab', 'kecamatan');
    }

    // === DESA ===
    public function desaIndex()
    {
        $rows = Desa::with('kecamatan')->orderBy('nama', 'asc')->get();
        $kecamatanList = Kecamatan::orderBy('nama', 'asc')->get();
        return view('master.wilayah.desa', compact('rows', 'kecamatanList'));
    }

    public function desaStore(Request $request)
    {
        $validated = $request->validate([
            'kecamatan_id' => 'required|exists:kecamatan,id',
            'nama' => 'required|string|max:150',
            'jenis' => 'required|in:Desa,Kelurahan',
        ]);
        Desa::create($validated);
        return redirect()->route('master.wilayah.index')->with('success', 'Desa/Kelurahan berhasil ditambahkan.')->with('active_tab', 'desa');
    }

    public function desaUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'kecamatan_id' => 'required|exists:kecamatan,id',
            'nama' => 'required|string|max:150',
            'jenis' => 'required|in:Desa,Kelurahan',
        ]);
        $row = Desa::findOrFail($id);
        $row->update($validated);
        return redirect()->route('master.wilayah.index')->with('success', 'Desa/Kelurahan berhasil diperbarui.')->with('active_tab', 'desa');
    }

    public function desaDestroy($id)
    {
        $row = Desa::findOrFail($id);
        if (\App\Models\AsetTanah::where('desa_id', $id)->exists()) {
            return redirect()->route('master.wilayah.index')->with('error', 'Desa/Kelurahan tidak dapat dihapus karena masih digunakan oleh data aset tanah.')->with('active_tab', 'desa');
        }
        if (DB::table('surat_skpt')->where('desa_id', $id)->exists()) {
            return redirect()->route('master.wilayah.index')->with('error', 'Desa/Kelurahan tidak dapat dihapus karena masih digunakan pada dokumen Surat SKPT.')->with('active_tab', 'desa');
        }
        if (KepalaDesa::where('desa_id', $id)->exists()) {
            return redirect()->route('master.wilayah.index')->with('error', 'Desa/Kelurahan tidak dapat dihapus karena masih memiliki data Kepala Desa terkait.')->with('active_tab', 'desa');
        }
        $row->delete();
        return redirect()->route('master.wilayah.index')->with('success', 'Desa/Kelurahan berhasil dihapus.')->with('active_tab', 'desa');
    }

    // === KEPALA DESA ===
    public function kadesIndex()
    {
        $rows = KepalaDesa::with('desa.kecamatan')->orderBy('nama_kades', 'asc')->get();
        $desaList = Desa::orderBy('nama', 'asc')->get();
        return view('master.wilayah.kades', compact('rows', 'desaList'));
    }

    public function kadesStore(Request $request)
    {
        $validated = $request->validate([
            'desa_id' => 'required|exists:desa,id',
            'nama' => 'required|string|max:150',
            'nip' => 'nullable|string|max:50',
        ]);
        
        $validated['aktif'] = $request->has('aktif') ? 1 : 0;
        
        KepalaDesa::create($validated);
        return redirect()->route('master.wilayah.index')->with('success', 'Kepala Desa berhasil ditambahkan.')->with('active_tab', 'kades');
    }

    public function kadesUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'desa_id' => 'required|exists:desa,id',
            'nama' => 'required|string|max:150',
            'nip' => 'nullable|string|max:50',
        ]);
        $row = KepalaDesa::findOrFail($id);
        
        $validated['aktif'] = $request->has('aktif') ? 1 : 0;
        
        $row->update($validated);
        return redirect()->route('master.wilayah.index')->with('success', 'Kepala Desa berhasil diperbarui.')->with('active_tab', 'kades');
    }

    public function kadesDestroy($id)
    {
        $kades = KepalaDesa::findOrFail($id);
        if (DB::table('surat_skpt')->where('kepala_desa_id', $id)->exists()) {
            return redirect()->route('master.wilayah.index')
                ->with('error', 'Kepala Desa tidak dapat dihapus karena masih digunakan pada dokumen Surat SKPT.')
                ->with('active_tab', 'kades');
        }
        $kades->delete();
        return redirect()->route('master.wilayah.index')->with('success', 'Kepala Desa berhasil dihapus.')->with('active_tab', 'kades');
    }

    // === CAMAT ===
    public function camatIndex()
    {
        $rows = Camat::with('kecamatan')->orderBy('nama_camat', 'asc')->get();
        $kecamatanList = Kecamatan::orderBy('nama', 'asc')->get();
        return view('master.wilayah.camat', compact('rows', 'kecamatanList'));
    }

    public function camatStore(Request $request)
    {
        $validated = $request->validate([
            'kecamatan_id' => 'required|exists:kecamatan,id',
            'nama' => 'required|string|max:150',
            'nip' => 'nullable|string|max:50',
        ]);
        
        $validated['aktif'] = $request->has('aktif') ? 1 : 0;

        Camat::create($validated);
        return redirect()->route('master.wilayah.index')->with('success', 'Camat berhasil ditambahkan.')->with('active_tab', 'camat');
    }

    public function camatUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'kecamatan_id' => 'required|exists:kecamatan,id',
            'nama' => 'required|string|max:150',
            'nip' => 'nullable|string|max:50',
        ]);
        $row = Camat::findOrFail($id);
        
        $validated['aktif'] = $request->has('aktif') ? 1 : 0;

        $row->update($validated);
        return redirect()->route('master.wilayah.index')->with('success', 'Camat berhasil diperbarui.')->with('active_tab', 'camat');
    }

    public function camatDestroy($id)
    {
        $camat = Camat::findOrFail($id);
        if (DB::table('surat_skpt')->where('camat_id', $id)->exists()) {
            return redirect()->route('master.wilayah.index')
                ->with('error', 'Camat tidak dapat dihapus karena masih digunakan pada dokumen Surat SKPT.')
                ->with('active_tab', 'camat');
        }
        $camat->delete();
        return redirect()->route('master.wilayah.index')->with('success', 'Camat berhasil dihapus.')->with('active_tab', 'camat');
    }

    // === PEMOHON SKPT ===
    public function pemohonIndex()
    {
        $rows = Pemohon::orderBy('nama', 'asc')->get();
        return view('master.wilayah.pemohon', compact('rows'));
    }

    public function pemohonStore(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:150',
            'nik' => 'nullable|string|max:50',
            'ttl' => 'nullable|string|max:100',
            'umur' => 'nullable|integer',
            'warga_negara' => 'nullable|string|max:50',
            'pekerjaan' => 'nullable|string|max:100',
            'jabatan' => 'nullable|string|max:100',
            'alamat' => 'nullable|string',
        ]);
        Pemohon::create($validated);
        return redirect()->route('master.wilayah.index')->with('success', 'Pemohon SKPT berhasil ditambahkan.')->with('active_tab', 'pemohon');
    }

    public function pemohonUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:150',
            'nik' => 'nullable|string|max:50',
            'ttl' => 'nullable|string|max:100',
            'umur' => 'nullable|integer',
            'warga_negara' => 'nullable|string|max:50',
            'pekerjaan' => 'nullable|string|max:100',
            'jabatan' => 'nullable|string|max:100',
            'alamat' => 'nullable|string',
        ]);
        $row = Pemohon::findOrFail($id);
        $row->update($validated);
        return redirect()->route('master.wilayah.index')->with('success', 'Pemohon SKPT berhasil diperbarui.')->with('active_tab', 'pemohon');
    }

    public function pemohonDestroy($id)
    {
        $pemohon = Pemohon::findOrFail($id);
        if (DB::table('surat_skpt')->where('pemohon_id', $id)->exists()) {
            return redirect()->route('master.wilayah.index')
                ->with('error', 'Pemohon SKPT tidak dapat dihapus karena masih digunakan pada dokumen Surat SKPT.')
                ->with('active_tab', 'pemohon');
        }
        $pemohon->delete();
        return redirect()->route('master.wilayah.index')->with('success', 'Pemohon SKPT berhasil dihapus.')->with('active_tab', 'pemohon');
    }

    // === JUDUL LAPORAN ===
    public function judulIndex()
    {
        $rows = DB::table('report_titles')->orderBy('judul', 'asc')->get();
        return view('master.wilayah.judul', compact('rows'));
    }

    public function judulStore(Request $request)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
        ]);
        
        DB::table('report_titles')->insert([
            'judul' => $validated['judul'],
            'aktif' => $request->has('aktif') ? 1 : 0,
        ]);
        return redirect()->route('master.wilayah.index')->with('success', 'Judul laporan berhasil ditambahkan.')->with('active_tab', 'judul');
    }

    public function judulUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
        ]);
        
        DB::table('report_titles')->where('id', $id)->update([
            'judul' => $validated['judul'],
            'aktif' => $request->has('aktif') ? 1 : 0,
        ]);
        return redirect()->route('master.wilayah.index')->with('success', 'Judul laporan berhasil diperbarui.')->with('active_tab', 'judul');
    }

    public function judulDestroy($id)
    {
        DB::table('report_titles')->where('id', $id)->delete();
        return redirect()->route('master.wilayah.index')->with('success', 'Judul laporan berhasil dihapus.')->with('active_tab', 'judul');
    }
}
