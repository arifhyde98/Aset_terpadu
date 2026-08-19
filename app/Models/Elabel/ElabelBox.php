<?php

namespace App\Models\Elabel;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model untuk Box BPKB Kendaraan.
 *
 * @property int $id
 * @property int $created_by
 * @property string $box_code
 * @property string|null $location
 * @property string $vehicle_type
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class ElabelBox extends Model
{
    use HasFactory;

    protected $table = 'elabel_boxes';

    protected $fillable = [
        'created_by',
        'box_code',
        'location',
        'vehicle_type',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function years(): HasMany
    {
        return $this->hasMany(ElabelBoxYear::class, 'box_id');
    }

    public function bpkbs(): HasMany
    {
        return $this->hasMany(ElabelBpkb::class, 'box_id');
    }
}
