<?php

namespace App\Models\Elabel\Dynamic;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model ArchiveBox (Manajemen Box Fisik Arsip Dinamis)
 * 
 * @property int $id
 * @property int $archive_type_id
 * @property string $nomor_box
 * @property string|null $barcode_code
 * @property string|null $lokasi_rak
 * @property int|null $tahun
 * @property int $kapasitas_maksimal
 * @property string|null $keterangan
 * @property int|null $created_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class ArchiveBox extends Model
{
    use HasFactory;

    protected $table = 'archive_boxes';

    protected $fillable = [
        'archive_type_id',
        'nomor_box',
        'barcode_code',
        'lokasi_rak',
        'tahun',
        'kapasitas_maksimal',
        'keterangan',
        'created_by',
    ];

    public function archiveType(): BelongsTo
    {
        return $this->belongsTo(ArchiveType::class, 'archive_type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ArchiveItem::class, 'archive_box_id');
    }

    public function getItemCountAttribute(): int
    {
        return $this->items()->count();
    }
}
