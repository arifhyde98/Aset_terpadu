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
        'status_pencatatan',
        'nama_aset',
        'peruntukan',
        'luas',
        'alamat',
        'lat',
        'lng',
        'geojson',
        'opd_id',
        'opd',
        'kecamatan_id',
        'desa_id',
        'dasar_perolehan',
        'harga_perolehan',
        'tanggal_perolehan',
        'keterangan'
    ];

    /**
     * Memeriksa apakah aset memiliki data batas poligon spasial.
     */
    public function hasPolygon(): bool
    {
        return !empty($this->geojson);
    }

    public function opdSipat(): BelongsTo
    {
        return $this->belongsTo(OpdSipat::class, 'opd_id');
    }

    public function wilayahKecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }

    public function wilayahDesa(): BelongsTo
    {
        return $this->belongsTo(Desa::class, 'desa_id');
    }

    public function prosesAset()
    {
        return $this->hasMany(ProsesAset::class, 'id_aset', 'id_aset');
    }

    public function latestProses()
    {
        return $this->hasOne(ProsesAset::class, 'id_aset', 'id_aset')->latestOfMany('id_proses');
    }

    public function targetSertifikat()
    {
        return $this->hasMany(SipatTargetSertifikat::class, 'aset_tanah_id', 'id_aset');
    }
}
