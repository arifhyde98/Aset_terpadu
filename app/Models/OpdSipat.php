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
}
