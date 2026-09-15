<?php

namespace App\Http\Requests\Sipat;

use Illuminate\Foundation\Http\FormRequest;

class BulkStoreProsesRequest extends FormRequest
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

        $asetIds = (array) $this->input('aset_ids', []);
        if (!empty($asetIds)) {
            $query = \App\Models\AsetTanah::withoutGlobalScopes()->whereIn('id_aset', $asetIds);
            if ($user->role === \App\Enums\UserRole::OPD) {
                $forbidden = (clone $query)->where('opd_id', '!=', $user->opd_id)->exists();
                if ($forbidden) {
                    return false;
                }
            } elseif ($user->role === \App\Enums\UserRole::KPB) {
                $forbidden = (clone $query)->where('sub_opd_id', '!=', $user->sub_opd_id)->exists();
                if ($forbidden) {
                    return false;
                }
            }
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'aset_ids' => 'nullable|array',
            'aset_ids.*' => 'integer|exists:aset_tanah,id_aset',
            'id_status'      => 'required|integer|exists:status_proses,id_status',
            'nibar_list'     => 'nullable|string',
            'tanggal_proses' => 'nullable|date',
            'tgl_mulai'      => 'nullable|date',
            'tgl_selesai'    => 'nullable|date',
            'keterangan'     => 'nullable|string',
        ];
    }
}
