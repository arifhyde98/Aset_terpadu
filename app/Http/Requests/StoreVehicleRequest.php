<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk Validasi Penambahan Kendaraan Baru
 */
class StoreVehicleRequest extends FormRequest
{
    /**
     * Menentukan apakah user diizinkan untuk membuat request ini.
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Menyiapkan data untuk divalidasi (Pre-processing).
     * 
     * Melakukan pembersihan format nomor polisi sebelum masuk ke aturan validasi.
     */
    protected function prepareForValidation()
    {
        if ($this->has('no_polisi')) {
            $this->merge([
                'no_polisi' => strtoupper(str_replace('.', '', preg_replace('/\s+/', ' ', trim($this->no_polisi))))
            ]);
        }

        // Kunci tenant: paksa opd_id dan nama opd sesuai user login jika rolenya adalah OPD
        if (auth()->check() && auth()->user()->role === \App\Enums\UserRole::OPD) {
            $this->merge([
                'opd_id' => auth()->user()->opd_id,
                'opd' => auth()->user()->opd?->nama,
            ]);
        }
    }

    /**
     * Mendapatkan aturan validasi yang berlaku untuk request ini.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'no_polisi' => 'required',
            'nomor_register' => 'nullable|string|max:255',
            'merk' => 'required',
            'tipe' => 'required',
            'jenis' => 'required',
            'vehicle_type_id' => 'nullable|exists:vehicle_types,id',
            'tahun_pembuatan' => 'nullable|integer',
            'tgl_perolehan' => 'nullable|date',
            'nilai_perolehan' => 'nullable|numeric',
            'stnk_ada' => 'required',
            'bpkb_ada' => 'required',
            'no_rangka' => 'nullable',
            'no_mesin' => 'nullable',
            'warna' => 'nullable',
            'tgl_stnk' => 'nullable|date',
            'opd' => 'required',
            'pemegang' => 'required',
            'status' => ['required', \Illuminate\Validation\Rule::enum(\App\Enums\VehicleStatus::class)],
            'kondisi' => ['required', \Illuminate\Validation\Rule::enum(\App\Enums\VehicleCondition::class)],
            'foto_kendaraan' => 'nullable|array|max:4',
            'foto_kendaraan.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            'keterangan' => 'nullable',
            'user_id' => 'nullable|exists:users,id',
            'opd_id' => 'nullable|exists:opds,id',
        ];

        if ($this->input('target_table') === 'ebmd') {
            $rules['tgl_perolehan'] = 'required|date';
            $rules['no_polisi'] = 'required|unique:ebmd_vehicles,no_polisi';
            $rules['nomor_register'] = 'nullable|string|max:255|unique:ebmd_vehicles,nomor_register';
        } else {
            $rules['no_polisi'] = 'required|unique:vehicles,no_polisi';
            $rules['nomor_register'] = 'nullable|string|max:255|unique:vehicles,nomor_register';
        }

        return $rules;
    }

    /**
     * Mendapatkan pesan kustom untuk aturan validasi.
     */
    public function messages(): array
    {
        return [
            'status.Illuminate\Validation\Rules\Enum' => 'Status yang dipilih tidak valid.',
            'kondisi.Illuminate\Validation\Rules\Enum' => 'Kondisi yang dipilih tidak valid.',
            'foto_kendaraan.max' => 'Maksimal foto yang dapat diunggah adalah 4 foto.',
            'foto_kendaraan.*.image' => 'File harus berupa gambar.',
            'foto_kendaraan.*.mimes' => 'Format gambar harus jpeg, png, jpg, atau webp.',
            'foto_kendaraan.*.max' => 'Ukuran setiap foto tidak boleh lebih dari 2MB.',
            'nomor_register.unique' => 'Nomor register sudah digunakan oleh kendaraan lain.',
        ];
    }

    /**
     * Mendapatkan nama atribut yang lebih ramah pengguna.
     */
    public function attributes(): array
    {
        return [
            'foto_kendaraan' => 'Foto Kendaraan',
            'nomor_register' => 'Nomor Register',
        ];
    }
}

