<?php

namespace App\Http\Requests\Sipat;

use App\Models\Opd;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAsetTanahRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $user = auth()->user();
        if (in_array($user->role, [\App\Enums\UserRole::SUPERADMIN, \App\Enums\UserRole::ADMIN])) {
            return true;
        }

        $aset = $this->route('aset');
        if (!($aset instanceof \App\Models\AsetTanah)) {
            $aset = \App\Models\AsetTanah::withoutGlobalScopes()->find($aset);
        }

        if (!$aset) {
            return false;
        }

        if ($user->role === \App\Enums\UserRole::OPD) {
            return (int)$aset->opd_id === (int)$user->opd_id;
        }

        if ($user->role === \App\Enums\UserRole::KPB) {
            return (int)$aset->sub_opd_id === (int)$user->sub_opd_id;
        }

        return false;
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
            'opd_id' => 'nullable|integer|exists:opds,id',
            'sub_opd_id' => 'nullable|integer|exists:sub_opds,id',
            'opd' => 'nullable|string|max:150',
            'alamat' => 'nullable|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'geojson' => 'nullable|string',
            'dasar_perolehan' => 'nullable|string|max:150',
            'harga_perolehan' => 'nullable|numeric',
            'tanggal_perolehan' => 'nullable|date',
            'keterangan' => 'nullable|string',
            'kecamatan_id' => 'nullable|integer|exists:kecamatan,id',
            'desa_id' => 'nullable|integer|exists:desa,id',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Kunci tenant: paksa opd_id dan nama opd sesuai user login jika rolenya adalah OPD atau KPB (Pola E-RANDIS)
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->role === \App\Enums\UserRole::OPD) {
                $this->merge([
                    'opd_id' => $user->opd_id,
                    'opd' => $user->opd?->nama,
                ]);
                return;
            } elseif ($user->role === \App\Enums\UserRole::KPB) {
                $this->merge([
                    'opd_id' => $user->opd_id,
                    'opd' => $user->opd?->nama,
                    'sub_opd_id' => $user->sub_opd_id,
                ]);
                return;
            }
        }

        if ($this->filled('opd_id')) {
            return;
        }

        $opdName = trim((string) $this->input('opd', ''));
        if ($opdName === '') {
            return;
        }

        $opd = Opd::whereRaw('LOWER(TRIM(nama)) = ?', [mb_strtolower($opdName)])->first();
        if ($opd) {
            $this->merge(['opd_id' => $opd->id]);
        }
    }
}
