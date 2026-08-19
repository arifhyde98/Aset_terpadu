<?php

namespace App\Models\Elabel;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model untuk Katalog BPKB Kendaraan.
 *
 * @property int $id
 * @property int $box_id
 * @property int|null $year
 * @property string $vehicle_type
 * @property string $plate_number
 * @property string|null $no_bpkb
 * @property string|null $nibar
 * @property string|null $no_rangka
 * @property string|null $no_mesin
 * @property string|null $merek
 * @property string|null $tipe
 * @property string|null $isi_silinder
 * @property string|null $warna
 * @property string|null $pengguna
 * @property string $status
 * @property string|null $pdf_path
 * @property int $input_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class ElabelBpkb extends Model
{
    use HasFactory;

    protected $table = 'elabel_bpkb';

    protected $fillable = [
        'box_id',
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
    ];

    public function box(): BelongsTo
    {
        return $this->belongsTo(ElabelBox::class, 'box_id');
    }

    public function inputUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(ElabelLoan::class, 'bpkb_id');
    }
}
