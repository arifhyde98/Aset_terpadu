<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model untuk Pengguna (Admin)
 * 
 * @property int $id
 * @property string $name Nama lengkap pengguna
 * @property string $email Alamat email pengguna
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password Password (terenkripsi)
 * @property string $role Peran pengguna (superadmin, admin, opd)
 * @property int|null $opd_id ID OPD (jika role adalah opd)
 * @property \Illuminate\Support\Carbon|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \App\Models\Opd|null $opd
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Vehicle[] $vehicles
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var list<string>
     */


    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'opd_id',
        'avatar',
    ];

    /**
     * Mendapatkan URL foto profil.
     * 
     * @return string
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar);
        }

        // Fallback ke UI-Avatars jika tidak ada foto
        return "https://ui-avatars.com/api/?name=" . urlencode($this->name) . "&background=1e40af&color=fff";
    }

    /**
     * Atribut yang harus disembunyikan untuk serialisasi.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Mendapatkan atribut yang harus dikonversi tipe datanya.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => \App\Enums\UserRole::class,
        ];
    }

    /**
     * Mendapatkan daftar kendaraan yang dikelola oleh user ini.
     * 
     * @return HasMany
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Mendapatkan data OPD yang terkait dengan user ini.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function opd(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }
}

