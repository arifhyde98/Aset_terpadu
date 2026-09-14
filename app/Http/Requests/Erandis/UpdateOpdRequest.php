<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request untuk validasi pembaruan data OPD.
 */
class UpdateOpdRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna diizinkan menjalankan request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mendapatkan aturan validasi untuk pembaruan data OPD.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => [
                'required',
                'string',
                Rule::unique('opds', 'nama')->ignore($this->route('opd')),
            ],
            'singkatan' => 'nullable|string',
            'alamat' => 'nullable|string',
        ];
    }
}
