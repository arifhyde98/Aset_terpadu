<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterNamaAset extends Model
{
    use HasFactory;

    protected $table = 'master_nama_aset';

    protected $fillable = [
        'kode_barang',
        'nama',
        'kelompok',
        'deskripsi',
        'urutan',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'urutan'    => 'integer',
    ];

    /**
     * Relasi ke data aset tanah yang menggunakan nama aset ini.
     */
    public function asetTanah(): HasMany
    {
        return $this->hasMany(AsetTanah::class, 'nama_aset_id');
    }

    /**
     * Scope: Hanya nama aset yang aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Pengurutan standar berdasarkan urutan lalu abjad nama.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('urutan', 'asc')->orderBy('nama', 'asc');
    }
}
