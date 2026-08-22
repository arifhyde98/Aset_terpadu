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
        $request->validate(['nama' => 'required|string|max:150']);
        Kecamatan::create($request->all());
        return redirect()->route('master.wilayah.index')->with('success', 'Kecamatan berhasil ditambahkan.')->with('active_tab', 'kecamatan');
    }

    public function kecamatanUpdate(Request $request, $id)
    {
        $request->validate(['nama' => 'required|string|max:150']);
        $row = Kecamatan::findOrFail($id);
        $row->update($request->all());
        return redirect()->route('master.wilayah.index')->with('success', 'Kecamatan berhasil diperbarui.')->with('active_tab', 'kecamatan');
    }

    public function kecamatanDestroy($id)
    {
        Kecamatan::findOrFail($id)->delete();
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
        $request->validate([
            'kecamatan_id' => 'required|exists:kecamatan,id',
            'nama' => 'required|string|max:150',
            'jenis' => 'required|in:Desa,Kelurahan',
        ]);
        Desa::create($request->all());
        return redirect()->route('master.wilayah.index')->with('success', 'Desa/Kelurahan berhasil ditambahkan.')->with('active_tab', 'desa');
    }

    public function desaUpdate(Request $request, $id)
    {
        $request->validate([
            'kecamatan_id' => 'required|exists:kecamatan,id',
            'nama' => 'required|string|max:150',
            'jenis' => 'required|in:Desa,Kelurahan',
        ]);
        $row = Desa::findOrFail($id);
        $row->update($request->all());
        return redirect()->route('master.wilayah.index')->with('success', 'Desa/Kelurahan berhasil diperbarui.')->with('active_tab', 'desa');
    }

    public function desaDestroy($id)
    {
        Desa::findOrFail($id)->delete();
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
        $request->validate([
            'desa_id' => 'required|exists:desa,id',
            'nama' => 'required|string|max:150',
            'nip' => 'nullable|string|max:50',
        ]);
        
        $data = $request->all();
        $data['aktif'] = $request->has('aktif') ? 1 : 0;
        
        KepalaDesa::create($data);
        return redirect()->route('master.wilayah.index')->with('success', 'Kepala Desa berhasil ditambahkan.')->with('active_tab', 'kades');
    }

    public function kadesUpdate(Request $request, $id)
    {
        $request->validate([
            'desa_id' => 'required|exists:desa,id',
            'nama' => 'required|string|max:150',
            'nip' => 'nullable|string|max:50',
        ]);
        $row = KepalaDesa::findOrFail($id);
        
        $data = $request->all();
        $data['aktif'] = $request->has('aktif') ? 1 : 0;
        
        $row->update($data);
        return redirect()->route('master.wilayah.index')->with('success', 'Kepala Desa berhasil diperbarui.')->with('active_tab', 'kades');
    }

    public function kadesDestroy($id)
    {
        KepalaDesa::findOrFail($id)->delete();
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
        $request->validate([
            'kecamatan_id' => 'required|exists:kecamatan,id',
            'nama' => 'required|string|max:150',
            'nip' => 'nullable|string|max:50',
        ]);
        
        $data = $request->all();
        $data['aktif'] = $request->has('aktif') ? 1 : 0;

        Camat::create($data);
        return redirect()->route('master.wilayah.index')->with('success', 'Camat berhasil ditambahkan.')->with('active_tab', 'camat');
    }

    public function camatUpdate(Request $request, $id)
    {
        $request->validate([
            'kecamatan_id' => 'required|exists:kecamatan,id',
            'nama' => 'required|string|max:150',
            'nip' => 'nullable|string|max:50',
        ]);
        $row = Camat::findOrFail($id);
        
        $data = $request->all();
        $data['aktif'] = $request->has('aktif') ? 1 : 0;

        $row->update($data);
        return redirect()->route('master.wilayah.index')->with('success', 'Camat berhasil diperbarui.')->with('active_tab', 'camat');
    }

    public function camatDestroy($id)
    {
        Camat::findOrFail($id)->delete();
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
        $request->validate([
            'nama' => 'required|string|max:150',
            'nik' => 'nullable|string|max:50',
            'ttl' => 'nullable|string|max:100',
            'umur' => 'nullable|integer',
            'warga_negara' => 'nullable|string|max:50',
            'pekerjaan' => 'nullable|string|max:100',
            'jabatan' => 'nullable|string|max:100',
            'alamat' => 'nullable|string',
        ]);
        Pemohon::create($request->all());
        return redirect()->route('master.wilayah.index')->with('success', 'Pemohon SKPT berhasil ditambahkan.')->with('active_tab', 'pemohon');
    }

    public function pemohonUpdate(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required|string|max:150',
            'nik' => 'nullable|string|max:50',
        ]);
        $row = Pemohon::findOrFail($id);
        $row->update($request->all());
        return redirect()->route('master.wilayah.index')->with('success', 'Pemohon SKPT berhasil diperbarui.')->with('active_tab', 'pemohon');
    }

    public function pemohonDestroy($id)
    {
        Pemohon::findOrFail($id)->delete();
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
        $request->validate([
            'judul' => 'required|string|max:255',
        ]);
        
        DB::table('report_titles')->insert([
            'judul' => $request->judul,
            'aktif' => $request->has('aktif') ? 1 : 0,
        ]);
        return redirect()->route('master.wilayah.index')->with('success', 'Judul laporan berhasil ditambahkan.')->with('active_tab', 'judul');
    }

    public function judulUpdate(Request $request, $id)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
        ]);
        
        DB::table('report_titles')->where('id', $id)->update([
            'judul' => $request->judul,
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
