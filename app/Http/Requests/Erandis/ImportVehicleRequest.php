<?php

namespace App\Http\Requests\Erandis;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request untuk validasi import data kendaraan dari Excel.
 */
class ImportVehicleRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna diizinkan melakukan request ini.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Aturan validasi untuk file import.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:5120', // Maksimal 5MB
            ],
        ];
    }

    /**
     * Pesan kustom untuk validasi yang gagal.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Silakan pilih file Excel yang akan diimport.',
            'file.mimes' => 'Format file tidak didukung. Gunakan format .xlsx, .xls, atau .csv.',
            'file.max' => 'Ukuran file terlalu besar. Maksimal ukuran adalah 5MB.',
        ];
    }
}
