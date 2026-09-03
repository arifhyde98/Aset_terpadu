<?php

namespace App\Services;

use App\Models\Opd;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

/**
 * Service untuk manajemen akun pengguna.
 */
class AccountService
{
    /**
     * Membuat akun admin untuk OPD tertentu secara otomatis.
     * 
     * @param Opd $opd
     * @return array{user: User, password: string}
     */
    public function createOpdAccount(Opd $opd): array
    {
        // 1. Generate Username / Email prefix
        $identifier = Str::slug($opd->singkatan ?: $opd->nama, '.');
        $baseEmail = "admin.{$identifier}";
        $domain = "@e-randis.id";
        $email = $baseEmail . $domain;

        // 2. Pastikan Email Unik (Jika sudah ada, tambahkan ID OPD)
        if (User::where('email', $email)->exists()) {
            $email = $baseEmail . '.' . $opd->id . $domain;
        }

        // 3. Generate Password Acak (DGL-XXXX)
        $rawPassword = 'DGL-' . Str::upper(Str::random(4));

        // 4. Simpan User ke Database
        $user = User::create([
            'name' => "Admin " . ($opd->singkatan ?: $opd->nama),
            'email' => $email,
            'password' => $rawPassword, // hashed via model cast
            'plain_password' => $rawPassword, // encrypted via model cast
            'role' => UserRole::OPD,
            'opd_id' => $opd->id,
        ]);

        return [
            'user' => $user,
            'password' => $rawPassword,
        ];
    }
}
