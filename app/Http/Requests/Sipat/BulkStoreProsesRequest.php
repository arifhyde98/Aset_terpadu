<?php

namespace App\Http\Requests\Sipat;

use Illuminate\Foundation\Http\FormRequest;

class BulkStoreProsesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'aset_ids' => 'nullable|array',
            'aset_ids.*' => 'integer|exists:aset_tanah,id_aset',
            'id_status' => 'required|integer|exists:status_proses,id_status',
            'nibar_list' => 'nullable|string',
            'tgl_mulai' => 'nullable|date',
            'tgl_selesai' => 'nullable|date',
            'keterangan' => 'nullable|string',
        ];
    }
}
