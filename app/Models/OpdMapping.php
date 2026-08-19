<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpdMapping extends Model
{
    use HasFactory;

    protected $table = 'opd_mappings';

    protected $fillable = [
        'sipat_opd_id',
        'erandis_opd_id',
        'status_verifikasi'
    ];

    /**
     * Relasi ke OPD SIPAT.
     */
    public function sipatOpd(): BelongsTo
    {
        return $this->belongsTo(OpdSipat::class, 'sipat_opd_id');
    }

    /**
     * Relasi ke OPD E-RANDIS.
     */
    public function erandisOpd(): BelongsTo
    {
        return $this->belongsTo(Opd::class, 'erandis_opd_id');
    }
}
