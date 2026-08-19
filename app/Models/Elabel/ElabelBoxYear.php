<?php

namespace App\Models\Elabel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk Tahun per Box BPKB.
 *
 * @property int $id
 * @property int $box_id
 * @property int $year
 */
class ElabelBoxYear extends Model
{
    use HasFactory;

    protected $table = 'elabel_box_years';

    public $timestamps = false;

    protected $fillable = [
        'box_id',
        'year',
    ];

    public function box(): BelongsTo
    {
        return $this->belongsTo(ElabelBox::class, 'box_id');
    }
}
