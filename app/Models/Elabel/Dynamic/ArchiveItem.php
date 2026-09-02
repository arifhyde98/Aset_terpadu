<?php

namespace App\Models\Elabel\Dynamic;

use App\Models\Opd;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model ArchiveItem (Berkas / Dokumen Arsip Dinamis)
 * 
 * @property int $id
 * @property int $archive_type_id
 * @property int|null $archive_box_id
 * @property int|null $opd_id
 * @property string $nomor_dokumen
 * @property string $nama_dokumen
 * @property int|null $tahun_dokumen
 * @property array|null $metadata
 * @property string|null $file_scan_pdf
 * @property string $status
 * @property string|null $keterangan
 * @property int|null $input_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class ArchiveItem extends Model
{
    use HasFactory;

    protected $table = 'archive_items';

    protected $fillable = [
        'archive_type_id',
        'archive_box_id',
        'opd_id',
        'nomor_dokumen',
        'nama_dokumen',
        'tahun_dokumen',
        'metadata',
        'file_scan_pdf',
        'status',
        'keterangan',
        'input_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'tahun_dokumen' => 'integer',
    ];

    public function archiveType(): BelongsTo
    {
        return $this->belongsTo(ArchiveType::class, 'archive_type_id');
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(ArchiveBox::class, 'archive_box_id');
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class, 'opd_id');
    }

    public function inputUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ArchiveAttachment::class, 'archive_item_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(ArchiveLoan::class, 'archive_item_id');
    }

    /**
     * Scope filter pencarian teks umum
     */
    public function scopeSearch($query, ?string $keyword)
    {
        if (empty($keyword)) {
            return $query;
        }

        return $query->where(function ($q) use ($keyword) {
            $q->where('nomor_dokumen', 'LIKE', "%{$keyword}%")
              ->orWhere('nama_dokumen', 'LIKE', "%{$keyword}%")
              ->orWhere('keterangan', 'LIKE', "%{$keyword}%")
              ->orWhere('metadata', 'LIKE', "%{$keyword}%");
        });
    }
}
