<?php

namespace App\Models\Elabel;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk Penampungan (Staging) Hasil OCR Berkas Scan BPKB.
 *
 * @property int $id
 * @property int $bpkb_id
 * @property string|null $pdf_filename
 * @property array|null $raw_extracted_json
 * @property array|null $diff_fields_json
 * @property string $status 'pending'|'applied'|'rejected'|'failed'
 * @property string|null $error_message
 * @property int|null $reviewed_by
 * @property \Carbon\Carbon|null $reviewed_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class ElabelBpkbOcrStaging extends Model
{
    use HasFactory;

    protected $table = 'elabel_bpkb_ocr_staging';

    protected $fillable = [
        'bpkb_id',
        'pdf_filename',
        'raw_extracted_json',
        'diff_fields_json',
        'status',
        'error_message',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'raw_extracted_json' => 'array',
        'diff_fields_json'   => 'array',
        'reviewed_at'        => 'datetime',
    ];

    public function bpkb(): BelongsTo
    {
        return $this->belongsTo(ElabelBpkb::class, 'bpkb_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApplied($query)
    {
        return $query->where('status', 'applied');
    }
}
