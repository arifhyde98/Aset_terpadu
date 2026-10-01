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

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\TenantScope);
    }

    protected $fillable = [
        'kode_aset',
        'status_pencatatan',
        'nama_aset',
        'nama_aset_id',
        'peruntukan',
        'luas',
        'alamat',
        'lat',
        'lng',
        'geojson',
        'opd_id',
        'sub_opd_id',
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

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class, 'opd_id');
    }

    public function opdRelation(): BelongsTo
    {
        return $this->belongsTo(Opd::class, 'opd_id');
    }

    /**
     * Backward-compatible alias untuk opd()
     */
    public function opdSipat(): BelongsTo
    {
        return $this->belongsTo(Opd::class, 'opd_id');
    }

    public function subOpd(): BelongsTo
    {
        return $this->belongsTo(SubOpd::class, 'sub_opd_id');
    }

    public function wilayahKecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }

    public function wilayahDesa(): BelongsTo
    {
        return $this->belongsTo(Desa::class, 'desa_id');
    }

    public function masterNamaAset(): BelongsTo
    {
        return $this->belongsTo(MasterNamaAset::class, 'nama_aset_id');
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
            $sq->where('kategori', 'LIKE', '%proses%')
               ->orWhere('kategori', 'LIKE', '%permohonan%');
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
     * Scope: Hanya aset yang status prosesnya belum diurus / belum diproses di BPN.
     */
    public function scopeBelumDiurus($query)
    {
        return $query->where(function($q) {
            $q->doesntHave('latestProses')
              ->orWhereHas('latestProses.statusProses', function($sq) {
                  $sq->where('kategori', 'LIKE', '%belum_diurus%');
              });
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
     * Scope: Filter terpadu berdasarkan parameter kategori status (Membaca KategoriProses dinamis dengan fallback aman).
     */
    public function scopeFilterKategoriStatus($query, ?string $kategori)
    {
        if (empty($kategori)) {
            return $query;
        }

        // 1. Prioritaskan Kategori Program Khusus / Pencatatan NIBAR
        if ($kategori === 'target_sertifikat') {
            return $query->whereHas('targetSertifikat');
        }
        if ($kategori === 'TERCATAT_KIB_A') {
            return $query->where('status_pencatatan', 'TERCATAT_KIB_A');
        }
        if ($kategori === 'USULAN_BELUM_TERCATAT') {
            return $query->where('status_pencatatan', 'USULAN_BELUM_TERCATAT');
        }

        // 2. Baca Konfigurasi Dinamis dari Model KategoriProses
        $katObj = \App\Models\KategoriProses::where('kode', $kategori)->where('is_active', true)->first();
        if ($katObj) {
            $statusIds = $katObj->statusProses()->pluck('status_proses.id_status')->toArray();

            $query->where(function($q) use ($statusIds, $katObj) {
                if ($katObj->includes_unprocessed) {
                    $q->doesntHave('latestProses');
                    if (!empty($statusIds)) {
                        $q->orWhereHas('latestProses', fn($sq) => $sq->whereIn('id_status', $statusIds));
                    }
                } else {
                    if (!empty($statusIds)) {
                        $q->whereHas('latestProses', fn($sq) => $sq->whereIn('id_status', $statusIds));
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                }
            });

            if ($katObj->exclude_target) {
                $query->whereDoesntHave('targetSertifikat');
            }

            return $query;
        }

        // 3. Fallback Aman ke Logika Klasik jika belum terdaftar di tabel kategori_proses
        return match ($kategori) {
            'sudah_bersertifikat'                       => $query->sudahBersertifikat(),
            'dalam_proses'                              => $query->dalamProses(),
            'belum_diurus'                              => $query->belumDiurus(),
            'belum_bersertifikat', 'belum_diproses'     => $query->belumBersertifikat(),
            'bermasalah', 'kendala'                     => $query->bermasalah(),
            default                                     => $query->whereHas('latestProses.statusProses', function($sq) use ($kategori) {
                $sq->where('kategori', 'LIKE', "%{$kategori}%");
            }),
        };
    }
}
