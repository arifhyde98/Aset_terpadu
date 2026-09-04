<?php

namespace App\Models\Elabel;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk Activity Log Modul E-Label.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string $module
 * @property string $description
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon|null $created_at
 */
class ElabelActivityLog extends Model
{
    use HasFactory;

    protected $table = 'elabel_activity_logs';

    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'description',
        'old_data',
        'new_data',
        'reference_type',
        'reference_id',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
