<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Camat extends Model
{
    use HasFactory;

    protected $table = 'camat';

    protected $fillable = [
        'kecamatan_id',
        'nama',
        'nip',
        'aktif',
    ];

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }
}
