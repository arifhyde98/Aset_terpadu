<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
     * Kategori khusus / non-BPN yang mengecualikan penyaringan status BPN
     * (Menampilkan seluruh status proses tanpa disaring).
     */
    public const SPECIAL_CATEGORIES = [
        'target_sertifikat',
        'TERCATAT_KIB_A',
        'USULAN_BELUM_TERCATAT',
    ];

    /**
     * Pemetaan kategori filter UI ke kategori resmi master status_proses di database.
     */
    public const CATEGORY_MAP = [
        'sudah_bersertifikat' => ['bersertifikat'],
        'dalam_proses'        => ['proses', 'permohonan_bpn'],
        'belum_diurus'        => ['belum_diurus', 'belum_diproses'],
        'belum_bersertifikat' => ['belum_diurus', 'belum_diproses'],
        'belum_diproses'      => ['belum_diurus', 'belum_diproses'],
        'bermasalah'          => ['kendala'],
        'kendala'             => ['kendala'],
    ];

    /**
     * Memeriksa apakah status proses cocok dengan kategori filter aktif.
     */
    public static function isMatchCategory(?string $filterCategory, array $statusCategories, ?int $statusId = null): bool
    {
        if (empty($filterCategory) || in_array($filterCategory, self::SPECIAL_CATEGORIES, true)) {
            return true;
        }

        // 1. Cek relasi dinamis jika statusId diberikan
        if ($statusId) {
            $kategoriObj = KategoriProses::where('kode', $filterCategory)->first();
            if ($kategoriObj) {
                return $kategoriObj->statusProses()->where('status_proses.id_status', $statusId)->exists();
            }
        }

        // 2. Cek pemetaan legacy
        $allowed = self::CATEGORY_MAP[$filterCategory] ?? [strtolower($filterCategory)];
        $normalizedStatusCats = array_map('strtolower', $statusCategories);

        return !empty(array_intersect($allowed, $normalizedStatusCats));
    }

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

    /**
     * Relasi many-to-many ke KategoriProses dinamis.
     */
    public function kategoriProses(): BelongsToMany
    {
        return $this->belongsToMany(
            KategoriProses::class,
            'kategori_status_pivot',
            'status_id',
            'kategori_id'
        )->withTimestamps();
    }
}
