<?php

namespace App\Models\Scopes;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Scope untuk membatasi akses data berdasarkan OPD pengguna yang login.
 */
class TenantScope implements Scope
{
    /**
     * Terapkan scope ke kueri Eloquent builder yang diberikan.
     * 
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Jika role adalah OPD (Pengguna Barang), batasi ke seluruh data OPD induknya
            if ($user->role === UserRole::OPD) {
                if (is_null($user->opd_id)) {
                    // Fail-safe: Jika opd_id hilang, kunci total akses (tidak boleh lihat data apa pun)
                    $builder->whereRaw('1 = 0');
                } else {
                    $builder->where('opd_id', $user->opd_id);
                }
            } elseif ($user->role === UserRole::KPB) {
                // Jika role adalah KPB (Kuasa Pengguna Barang), batasi HANYA ke unit kerjanya
                if (is_null($user->sub_opd_id)) {
                    // Fail-safe: Jika sub_opd_id belum diatur, kunci akses
                    $builder->whereRaw('1 = 0');
                } else {
                    $builder->where('sub_opd_id', $user->sub_opd_id);
                }
            }
        }
    }
}
