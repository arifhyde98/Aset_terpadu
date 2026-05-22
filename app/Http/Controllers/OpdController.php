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
     * Mengosongkan seluruh data OPD (Master Data).
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function truncate(): \Illuminate\Http\RedirectResponse
    {
        // Gunakan get()->each->delete() agar event 'deleting' terpanggil 
        // (untuk hapus avatar user via cascade dan observer)
        \App\Models\Opd::all()->each(function($opd) {
            $opd->delete();
        });

        // Invalidation massal seluruh statistik dashboard
        $this->vehicleService->invalidateDashboardStats(invalidateAllOpd: true);

        return redirect()->route('opds.index')->with('success', 'Seluruh data Master OPD berhasil dikosongkan.');
    }
}
