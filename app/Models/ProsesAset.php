<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProsesAset extends Model
{
    use HasFactory;

    protected $table = 'proses_aset';
    protected $primaryKey = 'id_proses';

    protected $fillable = [
        'id_aset',
        'id_status',
        'tgl_mulai',
        'tgl_selesai',
        'keterangan',
        'durasi_hari'
    ];

    public function aset()
    {
        return $this->belongsTo(AsetTanah::class, 'id_aset', 'id_aset');
    }

    public function statusProses()
    {
        return $this->belongsTo(StatusProses::class, 'id_status', 'id_status');
    }
}
