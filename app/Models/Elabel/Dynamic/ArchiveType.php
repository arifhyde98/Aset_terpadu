<?php

namespace App\Models\Elabel\Dynamic;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model ArchiveType (Jenis / Master Kategori Arsip Dinamis)
 * 
 * @property int $id
 * @property string $kode
 * @property string $nama
 * @property string|null $deskripsi
 * @property string $icon
 * @property string $warna_badge
 * @property array|null $schema_fields
 * @property bool $is_active
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class ArchiveType extends Model
{
    use HasFactory;

    protected $table = 'archive_types';

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'icon',
        'warna_badge',
        'schema_fields',
        'is_active',
    ];

    protected $casts = [
        'schema_fields' => 'array',
        'is_active' => 'boolean',
    ];

    public function boxes(): HasMany
    {
        return $this->hasMany(ArchiveBox::class, 'archive_type_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ArchiveItem::class, 'archive_type_id');
    }

    /**
     * Scope untuk tipe arsip yang aktif saja
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
