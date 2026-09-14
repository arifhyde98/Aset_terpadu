<?php

namespace App\Http\Controllers\Bangunan;

use App\Enums\BangunanKondisi;
use App\Enums\JenisBangunan;
use App\Enums\TipeRumahDinas;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bangunan\StoreBangunanRequest;
use App\Http\Requests\Bangunan\UpdateBangunanRequest;
use App\Models\AsetTanah;
use App\Models\Bangunan;
use App\Models\Kecamatan;
use App\Models\OpdSipat;
use App\Services\Bangunan\BangunanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BangunanController extends Controller implements HasMiddleware
{
    protected BangunanService $bangunanService;

    public function __construct(BangunanService $bangunanService)
    {
        $this->bangunanService = $bangunanService;
    }

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:superadmin,admin', only: ['destroy']),
        ];
    }

    /**
     * Menampilkan katalog daftar bangunan KIB C.
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'opd_id', 'kondisi', 'jenis_bangunan', 'kecamatan_id', 'tahun']);
        $bangunans = $this->bangunanService->getPaginatedBangunan($filters, 15, auth()->user());
        $stats = $this->bangunanService->getDashboardStats(auth()->user());

        $opds = OpdSipat::orderBy('nama')->get();
        $kecamatans = Kecamatan::orderBy('nama')->get();

        return view('bangunan.index', compact('bangunans', 'stats', 'opds', 'kecamatans', 'filters'));
    }

    /**
     * Menampilkan form tambah data bangunan baru.
     */
    public function create(): View
    {
        $opds = OpdSipat::orderBy('nama')->get();
        $kecamatans = Kecamatan::with('desa')->orderBy('nama')->get();
        
        // Ambil data Tanah KIB A untuk ditautkan
        $tanahQuery = AsetTanah::select('id_aset', 'kode_aset', 'nama_aset', 'alamat', 'luas');
        if (auth()->user()->role === \App\Enums\UserRole::OPD && auth()->user()->opd_id) {
            $tanahQuery->where('opd_id', auth()->user()->opd_id);
        }
        $tanahs = $tanahQuery->orderBy('nama_aset')->limit(200)->get();

        $kondisis = BangunanKondisi::cases();
        $jenisList = JenisBangunan::cases();
        $tipeRumahList = TipeRumahDinas::cases();

        return view('bangunan.create', compact('opds', 'kecamatans', 'tanahs', 'kondisis', 'jenisList', 'tipeRumahList'));
    }

    /**
     * Menyimpan data bangunan baru ke database.
     */
    public function store(StoreBangunanRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        // Upload foto utama jika ada
        if ($request->hasFile('foto_utama')) {
            $data['foto_utama'] = $request->file('foto_utama')->store('bangunan/foto', 'public');
        }

        // Upload dokumen PDF jika ada
        if ($request->hasFile('dokumen_pdf')) {
            $data['dokumen_pdf'] = $request->file('dokumen_pdf')->store('bangunan/dokumen', 'public');
        }

        $bangunan = Bangunan::create($data);

        return redirect()->route('bangunan.show', $bangunan)
            ->with('success', "Data bangunan \"{$bangunan->nama_bangunan}\" berhasil ditambahkan.");
    }

    /**
     * Menampilkan rincian detail profil bangunan.
     */
    public function show(Bangunan $bangunan): View
    {
        $bangunan->load(['opdSipat', 'asetTanah', 'kecamatan', 'desa', 'creator', 'updater']);

        return view('bangunan.show', compact('bangunan'));
    }

    /**
     * Menampilkan form edit data bangunan.
     */
    public function edit(Bangunan $bangunan): View
    {
        // Pemeriksaan kepemilikan bagi role OPD
        if (auth()->user()->role === \App\Enums\UserRole::OPD && $bangunan->opd_id !== auth()->user()->opd_id) {
            abort(403, 'Akses ditolak: Anda tidak memiliki izin untuk mengedit aset instansi lain.');
        }

        $opds = OpdSipat::orderBy('nama')->get();
        $kecamatans = Kecamatan::with('desa')->orderBy('nama')->get();

        $tanahQuery = AsetTanah::select('id_aset', 'kode_aset', 'nama_aset', 'alamat', 'luas');
        if (auth()->user()->role === \App\Enums\UserRole::OPD && auth()->user()->opd_id) {
            $tanahQuery->where('opd_id', auth()->user()->opd_id);
        }
        $tanahs = $tanahQuery->orderBy('nama_aset')->limit(200)->get();

        $kondisis = BangunanKondisi::cases();
        $jenisList = JenisBangunan::cases();
        $tipeRumahList = TipeRumahDinas::cases();

        return view('bangunan.edit', compact('bangunan', 'opds', 'kecamatans', 'tanahs', 'kondisis', 'jenisList', 'tipeRumahList'));
    }

    /**
     * Memperbarui data bangunan.
     */
    public function update(UpdateBangunanRequest $request, Bangunan $bangunan): RedirectResponse
    {
        if (auth()->user()->role === \App\Enums\UserRole::OPD && $bangunan->opd_id !== auth()->user()->opd_id) {
            abort(403, 'Akses ditolak.');
        }

        $data = $request->validated();
        $data['updated_by'] = auth()->id();

        // Ganti foto utama jika diunggah baru
        if ($request->hasFile('foto_utama')) {
            if ($bangunan->foto_utama && Storage::disk('public')->exists($bangunan->foto_utama)) {
                Storage::disk('public')->delete($bangunan->foto_utama);
            }
            $data['foto_utama'] = $request->file('foto_utama')->store('bangunan/foto', 'public');
        }

        // Ganti dokumen PDF jika diunggah baru
        if ($request->hasFile('dokumen_pdf')) {
            if ($bangunan->dokumen_pdf && Storage::disk('public')->exists($bangunan->dokumen_pdf)) {
                Storage::disk('public')->delete($bangunan->dokumen_pdf);
            }
            $data['dokumen_pdf'] = $request->file('dokumen_pdf')->store('bangunan/dokumen', 'public');
        }

        $bangunan->update($data);

        return redirect()->route('bangunan.show', $bangunan)
            ->with('success', "Data bangunan \"{$bangunan->nama_bangunan}\" berhasil diperbarui.");
    }

    /**
     * Menghapus data bangunan.
     */
    public function destroy(Bangunan $bangunan): RedirectResponse
    {
        $nama = $bangunan->nama_bangunan;

        if ($bangunan->foto_utama && Storage::disk('public')->exists($bangunan->foto_utama)) {
            Storage::disk('public')->delete($bangunan->foto_utama);
        }
        if ($bangunan->dokumen_pdf && Storage::disk('public')->exists($bangunan->dokumen_pdf)) {
            Storage::disk('public')->delete($bangunan->dokumen_pdf);
        }

        $bangunan->delete();

        return redirect()->route('bangunan.index')
            ->with('success', "Data bangunan \"{$nama}\" berhasil dihapus.");
    }
}
