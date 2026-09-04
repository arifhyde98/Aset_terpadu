<?php

namespace App\Observers;

use App\Models\Vehicle;
use App\Models\Activity;
use App\Enums\UserRole;

/**
 * Observer untuk Model Vehicle.
 * 
 * Mengelola audit trail dan otomatisasi data kendaraan.
 */
class VehicleObserver
{
    /**
     * Menangani event "creating" kendaraan.
     * 
     * @param Vehicle $vehicle
     * @return void
     */
    public function creating(Vehicle $vehicle): void
    {
        // Otomatis set opd_id saat create jika user adalah Admin OPD
        if (auth()->check() && auth()->user()->role === UserRole::OPD) {
            $vehicle->opd_id = auth()->user()->opd_id;
            
            // Sinkronisasi teks OPD jika tersedia
            if (auth()->user()->opd) {
                $vehicle->opd = auth()->user()->opd->nama;
            }
        }
    }

    /**
     * Menangani event "created" kendaraan.
     * 
     * @param Vehicle $vehicle
     * @return void
     */
    public function created(Vehicle $vehicle): void
    {
        Activity::log(
            "Menambahkan kendaraan baru: {$vehicle->no_polisi} ({$vehicle->merk})",
            'success',
            Activity::MODULE_ERANDIS,
            'erandis',
            null,
            $vehicle->toArray()
        );
    }

    /**
     * Menangani event "updated" kendaraan.
     * 
     * @param Vehicle $vehicle
     * @return void
     */
    public function updated(Vehicle $vehicle): void
    {
        $changes = $vehicle->getChanges();
        unset($changes['updated_at']);

        if (empty($changes)) {
            return;
        }

        $oldData = [];
        $newData = [];
        foreach ($changes as $key => $newVal) {
            $oldData[$key] = $vehicle->getOriginal($key);
            $newData[$key] = $newVal;
        }

        Activity::log(
            "Memperbarui data kendaraan: {$vehicle->no_polisi}",
            'warning',
            Activity::MODULE_ERANDIS,
            'erandis',
            $oldData,
            $newData
        );
    }

    /**
     * Menangani event "deleted" kendaraan.
     * 
     * @param Vehicle $vehicle
     * @return void
     */
    public function deleted(Vehicle $vehicle): void
    {
        Activity::log(
            "Menghapus data kendaraan: {$vehicle->no_polisi}",
            'danger',
            Activity::MODULE_ERANDIS,
            'erandis',
            $vehicle->toArray(),
            null
        );
    }
}
