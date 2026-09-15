<?php

namespace App\Http\Controllers\Erandis;

use App\Http\Controllers\Controller;
use App\Models\Opd;
use App\Http\Requests\Erandis\StoreOpdRequest;
use App\Http\Requests\Erandis\UpdateOpdRequest;
use Illuminate\Http\Request;
use App\Services\Erandis\VehicleService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Controller untuk Manajemen Master Data OPD (Organisasi Perangkat Daerah)
 */
class OpdController extends Controller implements HasMiddleware
{
    /**
     * Mendapatkan middleware yang ditugaskan ke controller ini.
     * 
     * @return array
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:superadmin,admin'),
            new Middleware('role:superadmin', only: ['truncate']),
        ];
    }

    protected $accountService;
    protected $vehicleService;

    public function __construct(\App\Services\AccountService $accountService, VehicleService $vehicleService)
    {
        $this->accountService = $accountService;
        $this->vehicleService = $vehicleService;
    }

    /**
     * Menampilkan daftar semua OPD dengan fitur pencarian dan paginasi.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): \Illuminate\View\View
    {
        $query = Opd::query()->with('user')
            ->withCount(['vehicles', 'ebmdVehicles', 'subOpds', 'asetTanahs', 'bangunans']);

        if ($request->filled('q')) {
            $query->where('nama', 'like', '%' . $request->q . '%')
                  ->orWhere('singkatan', 'like', '%' . $request->q . '%');
        }

        $sortBy = $request->input('sort_by');
        $sortOrder = $request->input('sort_order', 'asc');
        $allowedSorts = ['nama', 'singkatan', 'vehicles_count', 'ebmd_vehicles_count', 'sub_opds_count', 'aset_tanahs_count', 'bangunans_count'];

        if ($sortBy && in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('nama'); // Default
        }

        $opds = $query->paginate(15)->withQueryString();
        $allOpds = Opd::orderBy('nama')->get(['id', 'nama', 'singkatan']);
        
        return view('opds.index', compact('opds', 'allOpds'));
    }

    /**
     * Menggabungkan beberapa instansi OPD ke satu instansi tujuan.
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function merge(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'target_id' => 'required|exists:opds,id',
            'source_ids' => 'required|array|min:1',
            'source_ids.*' => 'exists:opds,id',
        ]);

        $targetId = (int) $request->input('target_id');
        $sourceIds = $request->input('source_ids');

        try {
            $result = $this->vehicleService->mergeMultipleOpds($targetId, $sourceIds);

            return redirect()->route('opds.index')
                ->with('success', "Berhasil menggabungkan {$result['merged_opds_count']} instansi OPD ke dalam {$result['target_name']}. Seluruh kendaraan Real, e-BMD, Sub-OPD, dan akun terkait telah disatukan.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menggabungkan OPD: ' . $e->getMessage());
        }
    }

    /**
     * Mengonversi satu atau beberapa OPD menjadi Sub-OPD (Kuasa Pengguna Barang) di bawah OPD Induk.
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function convertToSubOpd(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'parent_opd_id' => 'required|exists:opds,id',
            'source_ids' => 'required|array|min:1',
            'source_ids.*' => 'exists:opds,id',
            'jenis' => 'nullable|string|in:puskesmas,uptd,bagian,sekolah,rsud,lainnya',
            'nama' => 'nullable|string|max:255',
            'kode_sub' => 'nullable|string|max:50',
        ]);

        $parentOpdId = (int) $request->input('parent_opd_id');
        $sourceIds = $request->input('source_ids');
        $jenis = $request->input('jenis');
        $nama = $request->input('nama');
        $kodeSub = $request->input('kode_sub');

        try {
            $result = $this->vehicleService->convertOpdsToSubOpd(
                $parentOpdId, 
                $sourceIds, 
                $jenis, 
                $nama, 
                $kodeSub
            );

            $subOpdNames = implode(', ', $result['sub_opd_names']);
            return redirect()->route('opds.index')
                ->with('success', "Berhasil mengonversi {$result['converted_count']} OPD ({$subOpdNames}) menjadi Sub-OPD di bawah \"{$result['parent_name']}\". Total {$result['moved_vehicles_real']} kendaraan Real dan {$result['moved_vehicles_ebmd']} kendaraan e-BMD telah dialokasikan ke Sub-OPD ini.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengonversi OPD menjadi Sub-OPD: ' . $e->getMessage());
        }
    }

    /**
     * Menyimpan data OPD baru ke database.
     * 
     * @param StoreOpdRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreOpdRequest $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validated();

        $opd = Opd::create($validated);
        
        return redirect()->route('opds.index')->with('success', "Data OPD {$opd->nama} berhasil ditambahkan.");
    }

    /**
     * Memperbarui data OPD di database.
     * 
     * @param UpdateOpdRequest $request
     * @param Opd $opd
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateOpdRequest $request, Opd $opd): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validated();

        $opd->update($validated);

        return redirect()->route('opds.index')->with('success', 'Data OPD berhasil diperbarui.');
    }

    /**
     * Menghapus data OPD dari database.
     * 
     * @param Opd $opd
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Opd $opd): \Illuminate\Http\RedirectResponse
    {
        // Proteksi: jangan hapus OPD jika masih memiliki keterkaitan data aset, arsip, sub-opd, atau pengguna
        if (
            $opd->asetTanahs()->exists() || 
            $opd->bangunans()->exists() || 
            $opd->vehicles()->exists() || 
            $opd->ebmdVehicles()->exists() ||
            $opd->elabelSertifikats()->exists() ||
            $opd->elabelBpkbs()->exists() ||
            $opd->subOpds()->exists() ||
            $opd->users()->exists()
        ) {
            return redirect()->route('opds.index')
                ->with('error', 'OPD tidak dapat dihapus karena masih memiliki data Aset (Tanah/Bangunan/Kendaraan), Dokumen Arsip, Sub-OPD, atau Akun Pengguna.');
        }

        $opd->delete();

        // Invalidation massal karena penghapusan OPD memicu penghapusan kendaraan (Cascade)
        $this->vehicleService->invalidateDashboardStats(invalidateAllOpd: true);

        return redirect()->route('opds.index')->with('success', 'Data OPD berhasil dihapus.');
    }

    /**
     * Mengosongkan data OPD yang tidak memiliki kendaraan, tanah, atau bangunan (Master Data).
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function truncate(): \Illuminate\Http\RedirectResponse
    {
        // Hanya ambil OPD yang benar-benar kosong (tanpa aset tanah, bangunan, kendaraan, sertifikat, bpkb, sub-opd, atau user)
        $opdsToDelete = \App\Models\Opd::whereDoesntHave('vehicles')
            ->whereDoesntHave('ebmdVehicles')
            ->whereDoesntHave('asetTanahs')
            ->whereDoesntHave('bangunans')
            ->whereDoesntHave('elabelSertifikats')
            ->whereDoesntHave('elabelBpkbs')
            ->whereDoesntHave('subOpds')
            ->whereDoesntHave('users')
            ->get();

        $count = $opdsToDelete->count();

        if ($count === 0) {
            return redirect()->route('opds.index')
                ->with('info', 'Tidak ada data Master OPD kosong (tanpa kendaraan) yang perlu dihapus.');
        }

        // Kumpulkan detail OPD sebelum dihapus
        $deletedOpdsDetail = $opdsToDelete->map(fn($o) => [
            'Nama OPD' => $o->nama,
            'Singkatan' => $o->singkatan ?? '-',
            'Alamat' => $o->alamat ?? '-',
            'Email Admin' => $o->user->email ?? '-'
        ])->toArray();

        // Gunakan get()->each->delete() dalam block withoutEvents agar observer tidak memicu log individual
        \App\Models\Opd::withoutEvents(function () use ($opdsToDelete) {
            \App\Models\User::withoutEvents(function () use ($opdsToDelete) {
                \Illuminate\Support\Facades\DB::transaction(function () use ($opdsToDelete) {
                    $opdsToDelete->each(function($opd) {
                        if ($opd->user) {
                            // Hapus avatar user secara manual karena observer dimatikan
                            if ($opd->user->avatar) {
                                \Illuminate\Support\Facades\Storage::disk('public')->delete($opd->user->avatar);
                            }
                            $opd->user->delete();
                        }
                        $opd->delete();
                    });
                });
            });
        });

        // Catat 1 entri log aktivitas massal terpadu dengan detail
        \App\Models\Activity::log(
            "Melakukan penghapusan OPD secara massal", 
            'danger', 
            \App\Models\Activity::MODULE_ERANDIS, 
            'erandis', 
            $deletedOpdsDetail
        );

        // Invalidation massal seluruh statistik dashboard
        $this->vehicleService->invalidateDashboardStats(invalidateAllOpd: true);

        return redirect()->route('opds.index')
            ->with('success', "Sebanyak {$count} data Master OPD yang tidak memiliki kendaraan berhasil dikosongkan.");
    }
}
