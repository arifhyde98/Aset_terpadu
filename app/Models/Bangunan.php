<?php

namespace App\Models;

use App\Enums\BangunanKondisi;
use App\Enums\JenisBangunan;
use App\Enums\TipeRumahDinas;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk pengelolaan data Gedung dan Bangunan (KIB C).
 * Sesuai Permendagri No. 19 Tahun 2016 dan Permendagri No. 7 Tahun 2006.
 */
class Bangunan extends Model
{
    use HasFactory;

    protected $table = 'aset_bangunan';

    protected $fillable = [
        'kode_bangunan',
        'kode_barang',
        'nama_bangunan',
        'nomor_register',
        'opd_id',
        'aset_tanah_id',
        'status_tanah_dasar',
        'luas_tanah_dasar',
        'jenis_bangunan',
        'tipe_rumah_dinas',
        'nama_penghuni',
        'kondisi',
        'konstruksi_tingkat',
        'jumlah_lantai',
        'konstruksi_beton',
        'tipe_konstruksi',
        'luas_lantai',
        'luas_dasar',
        'alamat',
        'kecamatan_id',
        'desa_id',
        'lat',
        'lng',
        'geojson',
        'nomor_dokumen_pbg',
        'tanggal_dokumen_pbg',
        'status_psp',
        'foto_utama',
        'dokumen_pdf',
        'asal_usul',
        'tahun_pengadaan',
        'harga_perolehan',
        'nilai_buku',
        'status_penggunaan',
        'keterangan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'kondisi'            => BangunanKondisi::class,
        'jenis_bangunan'     => JenisBangunan::class,
        'tipe_rumah_dinas'   => TipeRumahDinas::class,
        'konstruksi_tingkat' => 'boolean',
        'konstruksi_beton'   => 'boolean',
        'jumlah_lantai'      => 'integer',
        'luas_lantai'        => 'float',
        'luas_dasar'         => 'float',
        'luas_tanah_dasar'   => 'float',
        'tanggal_dokumen_pbg'=> 'date',
        'tahun_pengadaan'    => 'integer',
        'harga_perolehan'    => 'float',
        'nilai_buku'         => 'float',
        'geojson'            => 'array',
        'lat'                => 'float',
        'lng'                => 'float',
    ];

    /**
     * Relasi ke Instansi OPD (SIPAT).
     */
    public function opdSipat(): BelongsTo
    {
        return $this->belongsTo(OpdSipat::class, 'opd_id');
    }

    /**
     * Alias relasi opd.
     */
    public function opd(): BelongsTo
    {
        return $this->belongsTo(OpdSipat::class, 'opd_id');
    }

    /**
     * Relasi ke Tanah KIB A (SIPAT).
     */
    public function asetTanah(): BelongsTo
    {
        return $this->belongsTo(AsetTanah::class, 'aset_tanah_id');
    }

    /**
     * Relasi ke Kecamatan.
     */
    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }

    /**
     * Relasi ke Desa/Kelurahan.
     */
    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class, 'desa_id');
    }

    /**
     * Relasi ke User pembuat.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke User pembaru.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope isolasi data multi-tenancy berdasarkan peran pengguna.
     */
    public function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->role === UserRole::OPD) {
            if (empty($user->opd_id)) {
                return $query->whereRaw('1 = 0'); // Fail-safe
            }
            return $query->where('opd_id', $user->opd_id);
        }

        return $query;
    }

    /**
     * Scope filter pencarian katalog.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (!empty($filters['q'])) {
            $q = trim($filters['q']);
            $query->where(function ($sub) use ($q) {
                $sub->where('nama_bangunan', 'like', "%{$q}%")
                    ->orWhere('kode_bangunan', 'like', "%{$q}%")
                    ->orWhere('kode_barang', 'like', "%{$q}%")
                    ->orWhere('nomor_register', 'like', "%{$q}%")
                    ->orWhere('nomor_dokumen_pbg', 'like', "%{$q}%")
                    ->orWhere('alamat', 'like', "%{$q}%");
            });
        }

        if (!empty($filters['opd_id'])) {
            $query->where('opd_id', $filters['opd_id']);
        }

        if (!empty($filters['kondisi'])) {
            $query->where('kondisi', $filters['kondisi']);
        }

        if (!empty($filters['jenis_bangunan'])) {
            $query->where('jenis_bangunan', $filters['jenis_bangunan']);
        }

        if (!empty($filters['kecamatan_id'])) {
            $query->where('kecamatan_id', $filters['kecamatan_id']);
        }

        if (!empty($filters['tahun'])) {
            $query->where('tahun_pengadaan', $filters['tahun']);
        }

        return $query;
    }
}
