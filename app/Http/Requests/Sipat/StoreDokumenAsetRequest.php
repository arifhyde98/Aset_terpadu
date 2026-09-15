<?php

namespace App\Http\Requests\Sipat;

use Illuminate\Foundation\Http\FormRequest;

class StoreDokumenAsetRequest extends FormRequest
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
        return [
            'jenis_dokumen' => 'required|string|max:120',
            'status_dokumen' => 'nullable|string|max:50',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ];
    }
}
