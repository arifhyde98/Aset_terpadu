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

    /**
     * Mengambil array kategori (mendukung JSON array, comma-separated string, atau single string).
     */
    public function getCategoriesAttribute(): array
    {
        $raw = $this->kategori;
        if (empty($raw)) {
            return ['proses'];
        }

        if (is_array($raw)) {
            return array_values(array_filter($raw));
        }

        // Cek jika JSON array
        $decoded = json_decode((string) $raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_values(array_filter($decoded));
        }

        // Cek jika comma-separated
        if (str_contains((string) $raw, ',')) {
            return array_values(array_filter(array_map('trim', explode(',', (string) $raw))));
        }

        return [trim((string) $raw)];
    }

    /**
     * Memeriksa apakah status proses ini termasuk ke dalam kategori tertentu.
     */
    public function hasCategory(string $targetCategory): bool
    {
        $target = strtolower(trim($targetCategory));
        return in_array($target, array_map('strtolower', $this->categories), true);
    }
}
