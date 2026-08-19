<?php

namespace App\Models\Elabel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk Katalog Sertifikat Tanah.
 *
 * @property int $id
 * @property string $no_sertipikat
 * @property string|null $nibar
 * @property string|null $status_penggunaan
 * @property string|null $spesifikasi
 * @property float|null $luas
 * @property string|null $tanggal_perolehan
 * @property float|null $nilai_perolehan
 * @property string|null $nama_pemilik
 * @property string|null $cara_perolehan
 * @property string|null $alamat
 * @property string|null $lokasi
 * @property string|null $dinas
 * @property string $sync_status
 * @property int $data_version
 * @property int|null $box_id
 * @property string|null $pdf_path
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class ElabelSertifikat extends Model
{
    use HasFactory;

    protected $table = 'elabel_sertifikat_tanah';

    protected $casts = [
        'luas' => 'decimal:2',
        'nilai_perolehan' => 'decimal:2',
        'tanggal_perolehan' => 'date',
    ];

    protected $fillable = [
        'no_sertipikat',
        'nibar',
        'status_penggunaan',
        'spesifikasi',
        'luas',
        'tanggal_perolehan',
        'nilai_perolehan',
        'nama_pemilik',
        'cara_perolehan',
        'alamat',
        'lokasi',
        'dinas',
        'sync_status',
        'data_version',
        'box_id',
        'pdf_path',
    ];

    public function box(): BelongsTo
    {
        return $this->belongsTo(ElabelSertifikatBox::class, 'box_id');
    }
}
