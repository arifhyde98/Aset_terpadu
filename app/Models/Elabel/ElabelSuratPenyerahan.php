<?php

namespace App\Models\Elabel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk Surat Penyerahan Arsip.
 *
 * @property int $id
 * @property string|null $nibar
 * @property string $no_surat
 * @property string|null $status_penggunaan
 * @property string|null $spesifikasi
 * @property string|null $jenis_penyerahan
 * @property float|null $luas
 * @property string|null $tanggal_perolehan
 * @property string|null $alamat
 * @property string|null $lokasi
 * @property string|null $dinas
 * @property string|null $pemberi_hibah
 * @property string|null $pdf_path
 * @property int|null $box_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class ElabelSuratPenyerahan extends Model
{
    use HasFactory;

    protected $table = 'elabel_surat_penyerahan';

    protected $casts = [
        'luas' => 'decimal:2',
        'tanggal_perolehan' => 'date',
    ];

    protected $fillable = [
        'nibar',
        'no_surat',
        'status_penggunaan',
        'spesifikasi',
        'jenis_penyerahan',
        'luas',
        'tanggal_perolehan',
        'alamat',
        'lokasi',
        'dinas',
        'pemberi_hibah',
        'pdf_path',
        'box_id',
    ];

    public function box(): BelongsTo
    {
        return $this->belongsTo(ElabelSuratPenyerahanBox::class, 'box_id');
    }
}
