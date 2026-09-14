<?php

namespace App\Http\Requests\Erandis;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request untuk validasi pembaruan jenis kendaraan.
 */
class UpdateVehicleTypeRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna diizinkan menjalankan request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mendapatkan aturan validasi untuk pembaruan jenis kendaraan.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                Rule::unique('vehicle_types', 'name')->ignore($this->route('vehicle_type')),
            ],
            'description' => 'nullable|string',
        ];
    }
}
