<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratSkpt extends Model
{
    use HasFactory;

    protected $table = 'surat_skpt';

    protected $fillable = [
        'nomor_surat',
        'alamat_kantor',
        'desa_id',
        'kepala_desa_id',
        'camat_id',
        'pemohon_id',
        'lokasi_tanah',
        'jenis_tanah',
        'status_tanah',
        'asal_tanah',
        'pernyataan_tanah',
        'luas_tanah',
        'dasar_perolehan',
        'batas_utara',
        'batas_timur',
        'batas_selatan',
        'batas_barat',
        'keterangan',
        'tanggal_surat',
    ];

    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class, 'desa_id');
    }

    public function kepalaDesa(): BelongsTo
    {
        return $this->belongsTo(KepalaDesa::class, 'kepala_desa_id');
    }

    public function camat(): BelongsTo
    {
        return $this->belongsTo(Camat::class, 'camat_id');
    }

    public function pemohon(): BelongsTo
    {
        return $this->belongsTo(Pemohon::class, 'pemohon_id');
    }
}
