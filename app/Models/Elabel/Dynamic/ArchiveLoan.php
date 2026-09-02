<?php

namespace App\Models\Elabel\Dynamic;

use App\Models\Opd;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model ArchiveLoan (Permohonan & Riwayat Peminjaman Berkas Dinamis)
 * 
 * @property int $id
 * @property int $archive_item_id
 * @property int|null $user_id
 * @property int|null $opd_id
 * @property string|null $requester_name
 * @property string|null $requester_phone
 * @property string|null $requester_email
 * @property string|null $requester_org
 * @property string $jenis_layanan
 * @property \Carbon\Carbon|null $tanggal_pinjam
 * @property \Carbon\Carbon|null $tanggal_kembali
 * @property string $status_persetujuan
 * @property string|null $keperluan
 * @property string|null $catatan_admin
 * @property int|null $approved_by
 * @property \Carbon\Carbon|null $approved_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class ArchiveLoan extends Model
{
    use HasFactory;

    protected $table = 'archive_loans';

    protected $fillable = [
        'archive_item_id',
        'user_id',
        'opd_id',
        'requester_name',
        'requester_phone',
        'requester_email',
        'requester_org',
        'jenis_layanan',
        'tanggal_pinjam',
        'tanggal_kembali',
        'status_persetujuan',
        'keperluan',
        'catatan_admin',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'tanggal_pinjam' => 'date',
        'tanggal_kembali' => 'date',
        'approved_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ArchiveItem::class, 'archive_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class, 'opd_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
