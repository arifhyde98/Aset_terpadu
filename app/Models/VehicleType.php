<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model untuk Tipe Kendaraan
 * 
 * @property int $id
 * @property string $name Nama tipe/kategori kendaraan
 * @property string|null $description Deskripsi atau keterangan tipe
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Vehicle[] $vehicles
 */
class VehicleType extends Model
{
    protected $fillable = ['name', 'description'];

    /**
     * Mendapatkan daftar kendaraan yang memiliki tipe ini.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Mendapatkan daftar kendaraan e-BMD yang memiliki tipe ini.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ebmdVehicles()
    {
        return $this->hasMany(EbmdVehicle::class);
    }
}

