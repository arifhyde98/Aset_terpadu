<?php

namespace App\Http\Requests\Sipat;

use App\Models\OpdSipat;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAsetTanahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $aset = $this->route('aset');
        $id = is_object($aset) ? ($aset->id_aset ?? null) : $aset;
        
        return [
            'kode_aset' => 'required|string|max:50|unique:aset_tanah,kode_aset,' . $id . ',id_aset',
            'nama_aset' => 'required|string|max:150',
            'peruntukan' => 'nullable|string|max:150',
            'luas' => 'nullable|numeric',
            'opd_id' => 'nullable|integer|exists:opd,id',
            'opd' => 'nullable|string|max:150',
            'alamat' => 'nullable|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'geojson' => 'nullable|string',
            'dasar_perolehan' => 'nullable|string|max:150',
            'harga_perolehan' => 'nullable|numeric',
            'tanggal_perolehan' => 'nullable|date',
            'keterangan' => 'nullable|string',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('opd_id')) {
            return;
        }

        $opdName = trim((string) $this->input('opd', ''));
        if ($opdName === '') {
            return;
        }

        $opd = OpdSipat::whereRaw('LOWER(TRIM(nama)) = ?', [mb_strtolower($opdName)])->first();
        if ($opd) {
            $this->merge(['opd_id' => $opd->id]);
        }
    }
}
