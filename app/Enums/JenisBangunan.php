<?php

namespace App\Enums;

/**
 * Enum klasifikasi jenis gedung dan bangunan sesuai standar aset pemerintah daerah.
 */
enum JenisBangunan: string
{
    case GEDUNG_KANTOR = 'Gedung Kantor';
    case RUMAH_DINAS = 'Rumah Dinas / Jabatan';
    case GEDUNG_SEKOLAH = 'Gedung Sekolah / Pendidikan';
    case FASILITAS_KESEHATAN = 'Fasilitas Kesehatan (RS/Puskesmas)';
    case GEDUNG_IBADAH_SOSIAL = 'Gedung Ibadah / Sosial';
    case GUDANG_PENYIMPANAN = 'Gudang / Tempat Penyimpanan';
    case POS_KEAMANAN = 'Pos Keamanan / Pos Jaga';
    case LAINNYA = 'Bangunan Lainnya';

    public function icon(): string
    {
        return match($this) {
            self::GEDUNG_KANTOR => 'bi-building',
            self::RUMAH_DINAS => 'bi-house-door',
            self::GEDUNG_SEKOLAH => 'bi-mortarboard',
            self::FASILITAS_KESEHATAN => 'bi-hospital',
            self::GEDUNG_IBADAH_SOSIAL => 'bi-people',
            self::GUDANG_PENYIMPANAN => 'bi-box-seam',
            self::POS_KEAMANAN => 'bi-shield-check',
            self::LAINNYA => 'bi-grid',
        };
    }
}
