<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi pembaruan pengaturan aplikasi.
 */
class UpdateSettingRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna diizinkan menjalankan request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mendapatkan aturan validasi untuk pembaruan pengaturan aplikasi.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'settings' => ['nullable', 'array'],
        ];

        foreach (Setting::all() as $setting) {
            if ($setting->type === 'image') {
                if ($this->hasFile("settings.{$setting->key}")) {
                    $rules["settings.{$setting->key}"] = ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:5120'];
                }
            } else {
                $rules["settings.{$setting->key}"] = ['nullable', 'string'];
            }
        }

        return $rules;
    }

    /**
     * Mendapatkan pesan validasi khusus untuk pengaturan gambar.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'settings.*.image' => 'File pengaturan gambar harus berupa gambar.',
            'settings.*.mimes' => 'Format gambar harus jpeg, png, jpg, webp, atau svg.',
            'settings.*.max' => 'Ukuran setiap gambar tidak boleh lebih dari 5MB.',
        ];
    }
}
