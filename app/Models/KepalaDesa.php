<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KepalaDesa extends Model
{
    use HasFactory;

    protected $table = 'kepala_desa';

    protected $fillable = [
        'desa_id',
        'nama',
        'nip',
        'aktif',
    ];

    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class, 'desa_id');
    }
}
