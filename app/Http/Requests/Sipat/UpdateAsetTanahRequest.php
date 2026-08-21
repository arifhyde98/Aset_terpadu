<?php

namespace App\Http\Requests\Sipat;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAsetTanahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        
        return [
            'kode_aset' => 'required|string|max:50|unique:aset_tanah,kode_aset,' . $id . ',id_aset',
            'nama_aset' => 'required|string|max:150',
            'peruntukan' => 'nullable|string|max:150',
            'luas' => 'nullable|numeric',
            'opd' => 'nullable|string|max:150',
            'alamat' => 'nullable|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'dasar_perolehan' => 'nullable|string|max:150',
            'harga_perolehan' => 'nullable|numeric',
            'tanggal_perolehan' => 'nullable|date',
            'keterangan' => 'nullable|string',
        ];
    }
}
