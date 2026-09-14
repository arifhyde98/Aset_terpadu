<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsetTanah extends Model
{
    use HasFactory;

    protected $table = 'aset_tanah';
    protected $primaryKey = 'id_aset';

    protected $fillable = [
        'kode_aset',
        'status_pencatatan',
        'nama_aset',
        'peruntukan',
        'luas',
        'alamat',
        'lat',
        'lng',
        'geojson',
        'opd_id',
        'opd',
        'kecamatan_id',
        'desa_id',
        'dasar_perolehan',
        'harga_perolehan',
        'tanggal_perolehan',
        'keterangan'
    ];

    /**
     * Memeriksa apakah aset memiliki data batas poligon spasial.
     */
    public function hasPolygon(): bool
    {
        return !empty($this->geojson);
    }

    public function opdSipat(): BelongsTo
    {
        return $this->belongsTo(OpdSipat::class, 'opd_id');
    }

    public function wilayahKecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }

    public function wilayahDesa(): BelongsTo
    {
        return $this->belongsTo(Desa::class, 'desa_id');
    }

    public function prosesAset()
    {
        return $this->hasMany(ProsesAset::class, 'id_aset', 'id_aset');
    }

    public function latestProses()
    {
        return $this->hasOne(ProsesAset::class, 'id_aset', 'id_aset')->latestOfMany('id_proses');
    }

    public function targetSertifikat()
    {
        return $this->hasMany(SipatTargetSertifikat::class, 'aset_tanah_id', 'id_aset');
    }

    public function latestTarget()
    {
        return $this->hasOne(SipatTargetSertifikat::class, 'aset_tanah_id', 'id_aset')->latestOfMany('id');
    }

    public function isTarget(): bool
    {
        return $this->relationLoaded('targetSertifikat') 
            ? $this->targetSertifikat->isNotEmpty() 
            : $this->targetSertifikat()->exists();
    }

    /**
     * Relasi ke sertifikat fisik di modul e-Label (berdasarkan NIBAR / kode_aset).
     */
    public function sertifikatElabel(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Elabel\ElabelSertifikat::class, 'nibar', 'kode_aset');
    }

    /**
     * Relasi ke Gedung dan Bangunan (KIB C) yang berdiri di atas bidang tanah ini.
     */
    public function bangunan(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Bangunan::class, 'aset_tanah_id');
    }

    /**
     * Scope: Hanya aset yang sudah bersertifikat resmi.
     */
    public function scopeSudahBersertifikat($query)
    {
        return $query->whereHas('latestProses.statusProses', function($sq) {
            $sq->where('kategori', 'LIKE', '%bersertifikat%');
        });
    }

    /**
     * Scope: Hanya aset yang sedang dalam proses pensertifikatan BPN.
     */
    public function scopeDalamProses($query)
    {
        return $query->whereHas('latestProses.statusProses', function($sq) {
            $sq->where('kategori', 'LIKE', '%proses%');
        });
    }

    /**
     * Scope: Hanya aset yang berkendala / bersengketa / bermasalah.
     */
    public function scopeBermasalah($query)
    {
        return $query->whereHas('latestProses.statusProses', function($sq) {
            $sq->where('kategori', 'LIKE', '%kendala%');
        });
    }

    /**
     * Scope: Aset tanah belum bersertifikat (Total Tanah - Bersertifikat - Kendala - Target).
     * Selaras 100% dengan metrik Dashboard Utama SIPAT.
     */
    public function scopeBelumBersertifikat($query)
    {
        return $query->whereDoesntHave('targetSertifikat')
            ->where(function($q) {
                $q->doesntHave('latestProses')
                  ->orWhereHas('latestProses.statusProses', function($sq) {
                      $sq->where('kategori', 'NOT LIKE', '%bersertifikat%')
                        ->where('kategori', 'NOT LIKE', '%kendala%');
                  });
            });
    }

    /**
     * Scope: Filter terpadu berdasarkan parameter kategori status.
     */
    public function scopeFilterKategoriStatus($query, ?string $kategori)
    {
        if (empty($kategori)) {
            return $query;
        }

        return match ($kategori) {
            'target_sertifikat'                         => $query->whereHas('targetSertifikat'),
            'sudah_bersertifikat'                       => $query->sudahBersertifikat(),
            'dalam_proses'                              => $query->dalamProses(),
            'belum_bersertifikat', 'belum_diproses'     => $query->belumBersertifikat(),
            'bermasalah', 'kendala'                     => $query->bermasalah(),
            'TERCATAT_KIB_A'                            => $query->where('status_pencatatan', 'TERCATAT_KIB_A'),
            'USULAN_BELUM_TERCATAT'                     => $query->where('status_pencatatan', 'USULAN_BELUM_TERCATAT'),
            default                                     => $query->whereHas('latestProses.statusProses', function($sq) use ($kategori) {
                $sq->where('kategori', 'LIKE', "%{$kategori}%");
            }),
        };
    }
}
