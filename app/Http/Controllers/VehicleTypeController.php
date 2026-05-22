<?php

namespace App\Http\Controllers;

use App\Models\VehicleType;
use App\Http\Requests\StoreVehicleTypeRequest;
use App\Http\Requests\UpdateVehicleTypeRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Controller untuk Manajemen Master Data Tipe Kendaraan
 */
class VehicleTypeController extends Controller implements HasMiddleware
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
        ];
    }

    /**
     * Menampilkan daftar semua tipe kendaraan beserta jumlah unit masing-masing.
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): \Illuminate\View\View
    {
        $sortBy = $request->input('sort_by');
        $sortOrder = $request->input('sort_order', 'asc');
        $allowedSorts = ['name', 'vehicles_count'];

        $query = VehicleType::withCount('vehicles');

        if ($sortBy && in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        $types = $query->get();

        return view('vehicle-types.index', compact('types'));
    }

    /**
     * Menyimpan tipe kendaraan baru ke database.
     * 
     * @param StoreVehicleTypeRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreVehicleTypeRequest $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validated();

        VehicleType::create($validated);

        return redirect()->route('vehicle-types.index')
            ->with('success', 'Jenis kendaraan berhasil ditambahkan.');
    }

    /**
     * Memperbarui data tipe kendaraan di database.
     * 
     * @param UpdateVehicleTypeRequest $request
     * @param VehicleType $vehicleType
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateVehicleTypeRequest $request, VehicleType $vehicleType): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validated();

        $vehicleType->update($validated);

        return redirect()->route('vehicle-types.index')
            ->with('success', 'Jenis kendaraan berhasil diperbarui.');
    }

    /**
     * Menghapus tipe kendaraan dari database.
     * 
     * Memastikan tidak ada kendaraan yang masih menggunakan tipe ini sebelum dihapus.
     * 
     * @param VehicleType $vehicleType
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(VehicleType $vehicleType): \Illuminate\Http\RedirectResponse
    {
        // Pastikan tidak ada kendaraan yang menggunakan jenis ini
        if ($vehicleType->vehicles()->count() > 0) {
            return back()->with('error', 'Gagal menghapus! Masih ada kendaraan yang menggunakan jenis ini.');
        }

        $vehicleType->delete();

        return redirect()->route('vehicle-types.index')
            ->with('success', 'Jenis kendaraan berhasil dihapus.');
    }

    /**
     * Membersihkan (menghapus) semua tipe kendaraan yang tidak memiliki unit kendaraan sama sekali.
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cleanup(): \Illuminate\Http\RedirectResponse
    {
        $deletedCount = VehicleType::whereDoesntHave('vehicles')->delete();

        return redirect()->route('vehicle-types.index')
            ->with('success', "$deletedCount Jenis kendaraan yang kosong berhasil dibersihkan.");
    }
}
