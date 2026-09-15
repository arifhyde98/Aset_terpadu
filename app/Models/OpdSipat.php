<?php

namespace App\Models;

/**
 * Class OpdSipat
 * 
 * Proxy & Backward-Compatibility Wrapper untuk Opd terpadu.
 * Memastikan semua pemanggilan warisan App\Models\OpdSipat tetap berjalan 100%
 * di atas tabel tunggal `opds`.
 */
class OpdSipat extends Opd
{
    protected $table = 'opds';
}
