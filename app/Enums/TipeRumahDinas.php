<?php

namespace App\Enums;

/**
 * Enum tipe rumah jabatan / rumah dinas pemerintah daerah.
 */
enum TipeRumahDinas: string
{
    case TIPE_KHUSUS = 'Tipe Khusus';
    case TIPE_A = 'Tipe A';
    case TIPE_B = 'Tipe B';
    case TIPE_C = 'Tipe C';
    case TIPE_D = 'Tipe D';
    case TIPE_E = 'Tipe E';

    /**
     * Deskripsi peruntukan jabatan dan batasan standar luas bangunan & tanah.
     */
    public function description(): string
    {
        return match($this) {
            self::TIPE_KHUSUS => 'Kepala Daerah / Wakil Kepala Daerah (Maks. Bangunan 500 m², Tanah 1.000 m²)',
            self::TIPE_A => 'Sekretaris Daerah (Maks. Bangunan 300 m², Tanah 500 m²)',
            self::TIPE_B => 'Pejabat Eselon II / Kadis / Kaban (Maks. Bangunan 200 m², Tanah 400 m²)',
            self::TIPE_C => 'Pejabat Eselon III / Camat (Maks. Bangunan 120 m², Tanah 300 m²)',
            self::TIPE_D => 'Pejabat Eselon IV (Maks. Bangunan 70 m², Tanah 200 m²)',
            self::TIPE_E => 'Pejabat Fungsional / Staf (Maks. Bangunan 50 m², Tanah 150 m²)',
        };
    }
}
