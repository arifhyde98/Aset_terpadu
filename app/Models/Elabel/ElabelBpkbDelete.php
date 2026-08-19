<?php

namespace App\Models\Elabel;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk BPKB Keluar / Soft Delete.
 *
 * @property int $id
 * @property int $bpkb_id
 * @property int|null $box_id
 * @property string|null $box_code
 * @property int|null $year
 * @property string|null $vehicle_type
 * @property string|null $plate_number
 * @property string|null $no_bpkb
 * @property string|null $nibar
 * @property string|null $no_rangka
 * @property string|null $no_mesin
 * @property string|null $merek
 * @property string|null $tipe
 * @property string|null $isi_silinder
 * @property string|null $warna
 * @property string|null $pengguna
 * @property string|null $status
 * @property string|null $pdf_path
 * @property int|null $input_by
 * @property int $deleted_by
 * @property string|null $deleted_at
 * @property string $reason
 * @property string|null $reason_detail
 * @property string|null $support_doc_path
 */
class ElabelBpkbDelete extends Model
{
    use HasFactory;

    protected $table = 'elabel_bpkb_deletes';

    public $timestamps = false;

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    protected $fillable = [
        'bpkb_id',
        'box_id',
        'box_code',
        'year',
        'vehicle_type',
        'plate_number',
        'no_bpkb',
        'nibar',
        'no_rangka',
        'no_mesin',
        'merek',
        'tipe',
        'isi_silinder',
        'warna',
        'pengguna',
        'status',
        'pdf_path',
        'input_by',
        'deleted_by',
        'deleted_at',
        'reason',
        'reason_detail',
        'support_doc_path',
    ];

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function inputUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }
}
