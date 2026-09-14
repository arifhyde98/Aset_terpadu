<?php

namespace App\Enums;

/**
 * Enum kondisi fisik bangunan sesuai Permendagri No. 19/2016.
 */
enum BangunanKondisi: string
{
    case BAIK = 'Baik';
    case KURANG_BAIK = 'Kurang Baik';
    case RUSAK_BERAT = 'Rusak Berat';

    public function badgeClass(): string
    {
        return match($this) {
            self::BAIK => 'bg-success',
            self::KURANG_BAIK => 'bg-warning text-dark',
            self::RUSAK_BERAT => 'bg-danger',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::BAIK => 'bi-check-circle-fill',
            self::KURANG_BAIK => 'bi-exclamation-triangle-fill',
            self::RUSAK_BERAT => 'bi-x-circle-fill',
        };
    }
}
