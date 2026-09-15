<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model untuk Sub-OPD / Kuasa Pengguna Barang (KPB)
 * 
 * Merepresentasikan unit kerja di bawah dinas induk (seperti Puskesmas, Bagian, UPTD, Sekolah, RSUD).
 * 
 * @property int $id
 * @property int $opd_id ID OPD Induk (Pengguna Barang)
 * @property string $nama Nama Sub-OPD / KPB
 * @property string|null $kode_sub Kode sub-unit aset
 * @property string $jenis Jenis unit (puskesmas, uptd, bagian, sekolah, rsud, lainnya)
 * @property string|null $alamat Alamat unit
 * @property string|null $nama_pimpinan Nama pimpinan / KPB
 * @property string|null $nip_pimpinan NIP pimpinan
 * @property bool $aktif Status keaktifan
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \App\Models\Opd $opd
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Vehicle[] $vehicles
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\EbmdVehicle[] $ebmdVehicles
 */
class SubOpd extends Model
{
    use HasFactory;

    protected $table = 'sub_opds';

    protected $fillable = [
        'opd_id',
        'nama',
        'kode_sub',
        'jenis',
        'alamat',
        'nama_pimpinan',
        'nip_pimpinan',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    /**
     * Relasi ke OPD Induk (Pengguna Barang).
     */
    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class, 'opd_id');
    }

    /**
     * Relasi ke kendaraan dinas fisik yang dialokasikan ke Sub-OPD ini.
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'sub_opd_id');
    }

    /**
     * Relasi ke kendaraan e-BMD yang dialokasikan ke Sub-OPD ini.
     */
    public function ebmdVehicles(): HasMany
    {
        return $this->hasMany(EbmdVehicle::class, 'sub_opd_id');
    }

    /**
     * Relasi ke akun pengguna / operator yang bertugas di Sub-OPD ini.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'sub_opd_id');
    }
}
