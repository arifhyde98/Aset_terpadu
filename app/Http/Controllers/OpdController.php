<?php

namespace App\Http\Controllers;

use App\Models\Opd;
use App\Http\Requests\StoreOpdRequest;
use App\Http\Requests\UpdateOpdRequest;
use Illuminate\Http\Request;
use App\Services\VehicleService;
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
        $query = Opd::query()->with('user');

        if ($request->filled('q')) {
            $query->where('nama', 'like', '%' . $request->q . '%')
                  ->orWhere('singkatan', 'like', '%' . $request->q . '%');
        }

        $sortBy = $request->input('sort_by');
        $sortOrder = $request->input('sort_order', 'asc');
        $allowedSorts = ['nama', 'singkatan'];

        if ($sortBy && in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('nama'); // Default
        }

        $opds = $query->paginate(15)->withQueryString();
        
        return view('opds.index', compact('opds'));
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
        // Untuk saat ini langsung hapus (Master Data)
        $opd->delete();

        // Invalidation massal karena penghapusan OPD memicu penghapusan kendaraan (Cascade)
        $this->vehicleService->invalidateDashboardStats(invalidateAllOpd: true);

        return redirect()->route('opds.index')->with('success', 'Data OPD berhasil dihapus.');
    }

    /**
     * Mengosongkan data OPD yang tidak memiliki kendaraan (Master Data).
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function truncate(): \Illuminate\Http\RedirectResponse
    {
        // Hanya ambil OPD yang tidak memiliki kendaraan (Real/e-BMD) dan tidak terhubung ke pemetaan SIPAT
        $opdsToDelete = \App\Models\Opd::whereDoesntHave('vehicles')
            ->whereDoesntHave('ebmdVehicles')
            ->whereDoesntHave('sipatOpds')
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
