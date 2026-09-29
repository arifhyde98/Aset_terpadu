<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class KategoriProses extends Model
{
    use HasFactory;

    protected $table = 'kategori_proses';

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'exclude_target',
        'includes_unprocessed',
        'warna',
        'urutan',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'exclude_target'       => 'boolean',
        'includes_unprocessed' => 'boolean',
        'is_system'            => 'boolean',
        'is_active'            => 'boolean',
        'urutan'               => 'integer',
    ];

    /**
     * Relasi ke status proses yang termasuk ke dalam kategori ini.
     */
    public function statusProses(): BelongsToMany
    {
        return $this->belongsToMany(
            StatusProses::class,
            'kategori_status_pivot',
            'kategori_id',
            'status_id'
        )->withTimestamps();
    }

    /**
     * Scope: Hanya kategori yang aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Urutkan berdasarkan urutan dan ID.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('urutan', 'asc')->orderBy('id', 'asc');
    }
}
