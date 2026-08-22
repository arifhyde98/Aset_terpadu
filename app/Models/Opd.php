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
     * Mendapatkan data akun admin yang mengelola OPD ini.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * Relasi pemetaan ke OPD SIPAT.
     */
    public function sipatOpds()
    {
        return $this->belongsToMany(
            OpdSipat::class,
            'opd_mappings',
            'erandis_opd_id',
            'sipat_opd_id'
        );
    }
}

