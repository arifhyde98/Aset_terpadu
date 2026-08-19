<?php

namespace App\Models\Elabel;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model untuk Box Surat Penyerahan.
 *
 * @property int $id
 * @property string $box_code
 * @property string $lokasi
 * @property int|null $created_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class ElabelSuratPenyerahanBox extends Model
{
    use HasFactory;

    protected $table = 'elabel_surat_penyerahan_boxes';

    protected $fillable = [
        'box_code',
        'lokasi',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function surats(): HasMany
    {
        return $this->hasMany(ElabelSuratPenyerahan::class, 'box_id');
    }
}
