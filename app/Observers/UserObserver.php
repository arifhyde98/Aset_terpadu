<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Activity;
use Illuminate\Support\Facades\Storage;

/**
 * Observer untuk Model User.
 * 
 * Mengelola pembersihan file fisik (avatar) dan log aktivitas akun.
 */
class UserObserver
{
    /**
     * Menangani event "created" User.
     * 
     * @param User $user
     * @return void
     */
    public function created(User $user): void
    {
        // Mendapatkan value role karena menggunakan Enum
        $roleName = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;
        Activity::log(
            "Membuat akun baru: {$user->email} ({$roleName})",
            'info',
            Activity::MODULE_ERANDIS,
            'erandis',
            null,
            $this->sanitizeUserData($user->toArray())
        );
    }

    /**
     * Menangani event "updated" User.
     * 
     * @param User $user
     * @return void
     */
    public function updated(User $user): void
    {
        $changes = $user->getChanges();
        unset($changes['updated_at'], $changes['remember_token']);

        if (empty($changes)) {
            return;
        }

        $oldData = [];
        $newData = [];
        foreach ($changes as $key => $newVal) {
            $oldData[$key] = $user->getOriginal($key);
            $newData[$key] = $newVal;
        }

        Activity::log(
            "Memperbarui akun pengguna: {$user->email}",
            'warning',
            Activity::MODULE_ERANDIS,
            'erandis',
            $this->sanitizeUserData($oldData),
            $this->sanitizeUserData($newData)
        );
    }

    /**
     * Menangani event "deleting" User.
     * 
     * @param User $user
     * @return void
     */
    public function deleting(User $user): void
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        
        Activity::log(
            "Menghapus akun pengguna: {$user->email}",
            'danger',
            Activity::MODULE_ERANDIS,
            'erandis',
            $this->sanitizeUserData($user->toArray()),
            null
        );
    }

    /**
     * Menyaring atribut sensitif agar tidak tercatat pada log aktivitas.
     * 
     * @param array $data
     * @return array
     */
    private function sanitizeUserData(array $data): array
    {
        unset($data['password'], $data['plain_password'], $data['remember_token']);
        return $data;
    }
}
