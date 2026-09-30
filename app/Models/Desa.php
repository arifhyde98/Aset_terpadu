<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Desa extends Model
{
    use HasFactory;

    protected $table = 'desa';
    protected $fillable = ['kecamatan_id', 'nama', 'jenis'];

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }

    public function asetTanah(): HasMany
    {
        return $this->hasMany(AsetTanah::class, 'desa_id');
    }

    public function bangunan(): HasMany
    {
        return $this->hasMany(Bangunan::class, 'desa_id');
    }

    /**
     * Hitung total aset (tanah + bangunan) yang terhubung ke desa ini.
     */
    public function getTotalAsetCountAttribute(): int
    {
        $tanah = isset($this->aset_tanah_count) ? $this->aset_tanah_count : $this->asetTanah()->count();
        $bangunan = isset($this->bangunan_count) ? $this->bangunan_count : $this->bangunan()->count();
        return (int)$tanah + (int)$bangunan;
    }

    /**
     * Cek apakah desa terkait dengan aset manapun.
     */
    public function getHasAsetAttribute(): bool
    {
        return $this->total_aset_count > 0;
    }
}
