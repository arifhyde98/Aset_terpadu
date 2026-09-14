<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi penambahan data OPD.
 */
class StoreOpdRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna diizinkan menjalankan request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mendapatkan aturan validasi untuk penambahan data OPD.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => 'required|string|unique:opds,nama',
            'singkatan' => 'nullable|string',
            'alamat' => 'nullable|string',
        ];
    }
}
