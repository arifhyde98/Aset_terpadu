<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpdSipat extends Model
{
    use HasFactory;

    protected $table = 'opd';
    protected $primaryKey = 'id';

    protected $fillable = [
        'nama',
        'aktif'
    ];

    /**
     * Relasi pemetaan ke OPD E-RANDIS.
     */
    public function erandisOpds()
    {
        return $this->belongsToMany(
            Opd::class,
            'opd_mappings',
            'sipat_opd_id',
            'erandis_opd_id'
        );
    }
}
