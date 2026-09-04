<?php

namespace App\Observers;

use App\Models\Opd;
use App\Models\Activity;
use App\Services\AccountService;

/**
 * Observer untuk Model Opd.
 * 
 * Mengelola otomatisasi pembuatan akun admin saat OPD baru didaftarkan.
 */
class OpdObserver
{
    /**
     * Menangani event "created" OPD.
     * 
     * @param Opd $opd
     * @return void
     */
    public function created(Opd $opd): void
    {
        // Auto-generate akun setiap kali OPD baru dibuat (Form/Import/Seeder)
        $result = app(AccountService::class)->createOpdAccount($opd);

        // Jika dalam konteks request web, flash password ke session 
        // agar bisa ditampilkan di UI SweetAlert
        if (request()->hasSession()) {
            session()->flash('new_account', [
                'opd_nama' => $opd->nama,
                'email' => $result['user']->email,
                'password' => $result['password']
            ]);
        }

        Activity::log(
            "Menambahkan Master Data OPD: {$opd->nama}",
            'success',
            Activity::MODULE_ERANDIS,
            'erandis',
            null,
            $opd->toArray()
        );
    }

    /**
     * Menangani event "updated" OPD.
     * 
     * @param Opd $opd
     * @return void
     */
    public function updated(Opd $opd): void
    {
        $changes = $opd->getChanges();
        unset($changes['updated_at']);

        if (empty($changes)) {
            return;
        }

        $oldData = [];
        $newData = [];
        foreach ($changes as $key => $newVal) {
            $oldData[$key] = $opd->getOriginal($key);
            $newData[$key] = $newVal;
        }

        Activity::log(
            "Memperbarui Master Data OPD: {$opd->nama}",
            'warning',
            Activity::MODULE_ERANDIS,
            'erandis',
            $oldData,
            $newData
        );
    }

    /**
     * Menangani event "deleted" OPD.
     * 
     * @param Opd $opd
     * @return void
     */
    public function deleted(Opd $opd): void
    {
        Activity::log(
            "Menghapus Master Data OPD: {$opd->nama}",
            'danger',
            Activity::MODULE_ERANDIS,
            'erandis',
            $opd->toArray(),
            null
        );
    }
}
