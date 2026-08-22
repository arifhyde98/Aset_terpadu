<?php

namespace App\Http\Requests\Sipat;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request untuk validasi resolusi OPD SIPAT ganda.
 */
class StoreResolveDuplicateOpdSipat extends FormRequest
{
    /**
     * Tentukan apakah pengguna diizinkan melakukan request ini.
     */
    public function authorize(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, [\App\Enums\UserRole::SUPERADMIN, \App\Enums\UserRole::ADMIN]);
    }

    /**
     * Aturan validasi untuk data input resolusi OPD ganda.
     */
    public function rules(): array
    {
        return [
            'target_opd_id' => ['required', 'integer', 'exists:opd,id'],
            'source_opd_id' => ['required', 'integer', 'exists:opd,id', 'different:target_opd_id'],
        ];
    }

    /**
     * Pesan kesalahan kustom.
     */
    public function messages(): array
    {
        return [
            'target_opd_id.required' => 'ID OPD target utama wajib disertakan.',
            'target_opd_id.exists'   => 'ID OPD target tidak terdaftar.',
            'source_opd_id.required' => 'ID OPD duplikat wajib disertakan.',
            'source_opd_id.exists'   => 'ID OPD duplikat tidak terdaftar.',
            'source_opd_id.different'=> 'OPD duplikat harus berbeda dengan OPD utama.',
        ];
    }
}
