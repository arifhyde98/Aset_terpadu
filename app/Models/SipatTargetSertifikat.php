<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SipatTargetSertifikat extends Model
{
    use HasFactory;

    protected $table = 'sipat_target_sertifikat';

    protected $fillable = [
        'tahun',
        'aset_tanah_id',
        'opd_id',
        'target_jumlah',
        'keterangan',
    ];

    /**
     * Relasi ke Aset Tanah (KIB A)
     */
    public function asetTanah(): BelongsTo
    {
        return $this->belongsTo(AsetTanah::class, 'aset_tanah_id', 'id_aset');
    }

    /**
     * Relasi ke OPD SIPAT
     */
    public function opdSipat(): BelongsTo
    {
        return $this->belongsTo(OpdSipat::class, 'opd_id', 'id');
    }
}
