<?php

namespace App\Models\Elabel;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model untuk Peminjaman / Request Scan.
 *
 * @property int $id
 * @property int $bpkb_id
 * @property int|null $requester_id
 * @property string|null $requester_name
 * @property string|null $requester_phone
 * @property string|null $requester_email
 * @property string|null $requester_org
 * @property string|null $requester_address
 * @property string|null $requester_note
 * @property string|null $requested_at
 * @property int|null $approved_by
 * @property string|null $approved_at
 * @property string $status
 * @property string|null $note
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class ElabelLoan extends Model
{
    use HasFactory;

    protected $table = 'elabel_loans';

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected $fillable = [
        'bpkb_id',
        'requester_id',
        'requester_name',
        'requester_phone',
        'requester_email',
        'requester_org',
        'requester_address',
        'requester_note',
        'requested_at',
        'approved_by',
        'approved_at',
        'status',
        'note',
    ];

    public function bpkb(): BelongsTo
    {
        return $this->belongsTo(ElabelBpkb::class, 'bpkb_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ElabelLoanHistory::class, 'loan_id');
    }
}
