<?php

namespace App\Models\Elabel;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk Riwayat Peminjaman.
 *
 * @property int $id
 * @property int $loan_id
 * @property string $status
 * @property int|null $changed_by
 * @property string|null $changed_at
 * @property string|null $note
 */
class ElabelLoanHistory extends Model
{
    use HasFactory;

    protected $table = 'elabel_loan_histories';

    public $timestamps = false;

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    protected $fillable = [
        'loan_id',
        'status',
        'changed_by',
        'changed_at',
        'note',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(ElabelLoan::class, 'loan_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
