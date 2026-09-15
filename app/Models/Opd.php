<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model untuk OPD (Organisasi Perangkat Daerah)
 * 
 * @property int $id
 * @property string $nama Nama lengkap instansi/OPD
 * @property string|null $singkatan Singkatan nama instansi
 * @property string|null $alamat Alamat kantor instansi
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Vehicle[] $vehicles
 */
class Opd extends Model
{
    protected $table = 'opds';

    protected $fillable = ['nama', 'singkatan', 'alamat'];



    /**
     * Mendapatkan daftar semua kendaraan yang dimiliki oleh OPD ini.
     * 
     * @return HasMany
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(\App\Models\Vehicle::class, 'opd_id');
    }

    /**
     * Mendapatkan daftar semua kendaraan EBMD yang dimiliki oleh OPD ini.
     * 
     * @return HasMany
     */
    public function ebmdVehicles(): HasMany
    {
        return $this->hasMany(\App\Models\EbmdVehicle::class, 'opd_id');
    }

    /**
     * Mendapatkan daftar Sub-OPD / Kuasa Pengguna Barang (KPB) di bawah OPD ini.
     * 
     * @return HasMany
     */
    public function subOpds(): HasMany
    {
        return $this->hasMany(SubOpd::class, 'opd_id');
    }


    /**
     * Mendapatkan data akun admin yang mengelola OPD ini.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * Mendapatkan daftar Aset Tanah (SIPAT / KIB A) milik OPD ini.
     */
    public function asetTanahs(): HasMany
    {
        return $this->hasMany(\App\Models\AsetTanah::class, 'opd_id');
    }

    /**
     * Mendapatkan daftar Aset Bangunan (KIB C) milik OPD ini.
     */
    public function bangunans(): HasMany
    {
        return $this->hasMany(\App\Models\Bangunan::class, 'opd_id');
    }

    /**
     * Mendapatkan daftar Sertifikat Tanah e-Label milik OPD ini.
     */
    public function elabelSertifikats(): HasMany
    {
        return $this->hasMany(\App\Models\Elabel\ElabelSertifikat::class, 'sipat_opd_id');
    }

    /**
     * Mendapatkan daftar BPKB e-Label milik OPD ini.
     */
    public function elabelBpkbs(): HasMany
    {
        return $this->hasMany(\App\Models\Elabel\ElabelBpkb::class, 'sipat_opd_id');
    }
}

