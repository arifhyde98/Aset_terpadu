<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusProses extends Model
{
    use HasFactory;

    protected $table = 'status_proses';
    protected $primaryKey = 'id_status';
    public $timestamps = false;

    protected $fillable = [
        'nama_status',
        'urutan',
        'warna',
        'kategori'
    ];
}
