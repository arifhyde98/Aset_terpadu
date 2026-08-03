<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\VehicleStatus;
use App\Enums\VehicleCondition;

/**
 * Model untuk Kendaraan EBMD.
 * 
 * @property int $id ID Utama
 * @property string $no_polisi Nomor Polisi / Plat
 * @property string|null $nomor_register Nomor Register Kendaraan
 * @property string $merk Merk Kendaraan
 * @property string $tipe Tipe/Model Kendaraan
 * @property string $jenis Jenis Kendaraan
 * @property int|null $vehicle_type_id ID Tipe Kendaraan (Relasi)
 * @property int|null $tahun_pembuatan Tahun Produksi
 * @property string|null $tgl_perolehan Tanggal Pengadaan
 * @property float|null $nilai_perolehan Harga Perolehan
 * @property string $stnk_ada Ketersediaan STNK
 * @property string $bpkb_ada Ketersediaan BPKB
 * @property string|null $no_rangka Nomor Rangka
 * @property string|null $no_mesin Nomor Mesin
 * @property string|null $warna Warna Kendaraan
 * @property string|null $tgl_stnk Masa Berlaku STNK
 * @property string $opd Nama OPD Penanggung Jawab
 * @property int|null $opd_id ID OPD (Relasi)
 * @property string $pemegang Nama Pemegang Kendaraan
 * @property string $status Status Operasional
 * @property string $kondisi Kondisi Fisik Kendaraan
 * @property array|null $foto_kendaraan Daftar Path Foto Kendaraan
 * @property string|null $keterangan Catatan Tambahan
 * @property int|null $user_id ID Admin Pengelola
 */
class EbmdVehicle extends Model
{
    use HasFactory;

    protected $table = 'ebmd_vehicles';

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\TenantScope);
    }

    /**
     * Kolom yang dapat diisi secara massal.
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'no_polisi', 'nomor_register', 'merk', 'tipe', 'jenis', 'vehicle_type_id', 
        'tahun_pembuatan', 'tgl_perolehan', 'nilai_perolehan', 
        'stnk_ada', 'bpkb_ada', 'no_rangka', 'no_mesin', 'warna', 
        'tgl_stnk', 'opd', 'opd_id', 'pemegang', 'status', 'kondisi', 
        'foto_kendaraan', 'keterangan', 'user_id', 'is_synced',
    ];

    /**
     * Konversi tipe data otomatis.
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'foto_kendaraan' => 'array',
    ];

    /**
     * Relasi ke model User (Admin).
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke model VehicleType (Tipe Kendaraan).
     * 
     * @return BelongsTo
     */
    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    /**
     * Relasi ke model Opd (Instansi).
     * 
     * @return BelongsTo
     */
    public function opdRelation(): BelongsTo
    {
        return $this->belongsTo(Opd::class, 'opd_id');
    }

    /**
     * Mendapatkan daftar status operasional yang tersedia.
     * 
     * @return array<string, string>
     */
    public static function getStatuses(): array
    {
        return VehicleStatus::labels();
    }

    /**
     * Mendapatkan daftar kondisi fisik yang tersedia.
     * 
     * @return array<string, string>
     */
    public static function getConditions(): array
    {
        return VehicleCondition::labels();
    }
}
