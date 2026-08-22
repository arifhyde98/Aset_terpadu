<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsetTanah extends Model
{
    use HasFactory;

    protected $table = 'aset_tanah';
    protected $primaryKey = 'id_aset';

    protected $fillable = [
        'kode_aset',
        'nama_aset',
        'peruntukan',
        'luas',
        'alamat',
        'lat',
        'lng',
        'opd_id',
        'opd',
        'dasar_perolehan',
        'harga_perolehan',
        'tanggal_perolehan',
        'keterangan'
    ];

    public function opdSipat(): BelongsTo
    {
        return $this->belongsTo(OpdSipat::class, 'opd_id');
    }

    public function prosesAset()
    {
        return $this->hasMany(ProsesAset::class, 'id_aset', 'id_aset');
    }

    public function latestProses()
    {
        return $this->hasOne(ProsesAset::class, 'id_aset', 'id_aset')->latestOfMany('id_proses');
    }
}
