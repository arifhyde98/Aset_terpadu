<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pemohon extends Model
{
    use HasFactory;

    protected $table = 'pemohon';

    protected $fillable = [
        'nama',
        'nik',
        'ttl',
        'umur',
        'jenis_kelamin',
        'warga_negara',
        'agama',
        'pekerjaan',
        'jabatan',
        'alamat',
    ];
}
