<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class KendaraanPinjamPakai extends Model
{
    use SoftDeletes;

    protected $table = 'kendaraan_pinjam_pakai';

    protected $fillable = [
        'vehicle_id',
        'opd_id',
        'kategori_peminjam',
        'nama_instansi_peminjam',
        'nama_penanggung_jawab',
        'nip_penanggung_jawab',
        'jabatan_penanggung_jawab',
        'kontak_penanggung_jawab',
        'nomor_nppp_bast',
        'tanggal_nppp_bast',
        'tanggal_mulai',
        'tanggal_selesai',
        'status_perjanjian',
        'file_dokumen_pdf',
        'keterangan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_nppp_bast' => 'date',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    /**
     * Relasi ke Kendaraan Dinas (eRANDIS)
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    /**
     * Relasi ke OPD Pemilik Aset
     */
    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class, 'opd_id');
    }

    /**
     * Relasi ke User pembuat data
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Accessor sisa hari sebelum jatuh tempo
     */
    public function getSisaHariAttribute(): int
    {
        if (!$this->tanggal_selesai) return 0;
        return (int) Carbon::now()->startOfDay()->diffInDays($this->tanggal_selesai, false);
    }

    /**
     * Helper badge status perjanjian
     */
    public function getStatusBadgeAttribute(): string
    {
        if ($this->status_perjanjian === 'Selesai / Dikembalikan' || $this->status_perjanjian === 'Selesai') {
            return '<span class="badge bg-secondary-subtle text-secondary px-2.5 py-1 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i> Selesai / Dikembalikan</span>';
        }

        $sisa = $this->sisa_hari;
        if ($sisa < 0) {
            return '<span class="badge bg-danger text-white px-2.5 py-1 rounded-pill"><i class="bi bi-exclamation-triangle-fill me-1"></i> Kadaluarsa (' . abs($sisa) . ' Hari)</span>';
        } elseif ($sisa <= 30) {
            return '<span class="badge bg-warning text-dark px-2.5 py-1 rounded-pill"><i class="bi bi-clock-history me-1"></i> Jatuh Tempo (' . $sisa . ' Hari Lagi)</span>';
        }

        return '<span class="badge bg-success-subtle text-success px-2.5 py-1 rounded-pill"><i class="bi bi-shield-check me-1"></i> Aktif (' . $sisa . ' Hari)</span>';
    }

    /**
     * URL berkas PDF NPPP
     */
    public function getDokumenUrlAttribute(): ?string
    {
        if (!$this->file_dokumen_pdf) return null;
        return asset('storage/' . $this->file_dokumen_pdf);
    }
}
