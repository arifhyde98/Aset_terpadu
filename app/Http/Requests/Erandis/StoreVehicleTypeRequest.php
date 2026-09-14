<?php

namespace App\Http\Requests\Erandis;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi penambahan jenis kendaraan.
 */
class StoreVehicleTypeRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna diizinkan menjalankan request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mendapatkan aturan validasi untuk penambahan jenis kendaraan.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|unique:vehicle_types,name',
            'description' => 'nullable|string',
        ];
    }
}
