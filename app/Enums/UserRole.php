<?php

namespace App\Enums;

/**
 * Enum untuk peran pengguna dalam sistem E-RANDIS.
 * 
 * Superadmin: Developer/Admin Utama (Akses Penuh).
 * Admin: Pengelola BMD/Bidang Aset (Akses Global Data).
 * OPD: Admin Instansi (Akses Terbatas Data Sendiri).
 */
enum UserRole: string
{
    case SUPERADMIN = 'superadmin';
    case ADMIN = 'admin';
    case OPD = 'opd';
    case KPB = 'kpb'; // Kuasa Pengguna Barang (Sub-OPD / Puskesmas / UPTD)

    /**
     * Mendapatkan label human-readable untuk role.
     * 
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::SUPERADMIN => 'Super Admin',
            self::ADMIN => 'Admin Aset (BMD)',
            self::OPD => 'Admin OPD (Pengguna Barang)',
            self::KPB => 'Operator KPB (Sub-OPD)',
        };
    }
}
