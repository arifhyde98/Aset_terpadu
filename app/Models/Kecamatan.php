<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kecamatan extends Model
{
    use HasFactory;

    protected $table = 'kecamatan';
    protected $fillable = ['nama'];

    public function desa(): HasMany
    {
        return $this->hasMany(Desa::class, 'kecamatan_id');
    }

    public function asetTanah(): HasMany
    {
        return $this->hasMany(AsetTanah::class, 'kecamatan_id');
    }
}
