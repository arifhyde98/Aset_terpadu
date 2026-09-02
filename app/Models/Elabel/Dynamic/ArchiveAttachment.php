<?php

namespace App\Models\Elabel\Dynamic;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model ArchiveAttachment (Berkas Lampiran Pendukung)
 * 
 * @property int $id
 * @property int $archive_item_id
 * @property string|null $field_name
 * @property string $file_title
 * @property string $file_path
 * @property string|null $file_type
 * @property int|null $file_size
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class ArchiveAttachment extends Model
{
    use HasFactory;

    protected $table = 'archive_attachments';

    protected $fillable = [
        'archive_item_id',
        'field_name',
        'file_title',
        'file_path',
        'file_type',
        'file_size',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ArchiveItem::class, 'archive_item_id');
    }

    public function getFormattedSizeAttribute(): string
    {
        if (!$this->file_size) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = floor(log($this->file_size, 1024));
        return round($this->file_size / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}
