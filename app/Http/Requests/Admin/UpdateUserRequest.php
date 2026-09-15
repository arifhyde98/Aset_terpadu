<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request untuk validasi pembaruan pengguna.
 */
class UpdateUserRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna diizinkan menjalankan request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mendapatkan aturan validasi untuk pembaruan pengguna.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
            'password' => 'nullable|string|min:8',
            'role' => ['required', Rule::enum(UserRole::class)],
            'opd_id' => 'nullable|required_if:role,opd,kpb|exists:opds,id',
            'sub_opd_id' => 'nullable|required_if:role,kpb|exists:sub_opds,id',
        ];
    }
}
