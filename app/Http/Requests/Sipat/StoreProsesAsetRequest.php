<?php

namespace App\Http\Requests\Sipat;

use Illuminate\Foundation\Http\FormRequest;

class StoreProsesAsetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_status'      => 'required|integer|exists:status_proses,id_status',
            'tanggal_proses' => 'nullable|date',
            'tgl_mulai'      => 'nullable|date',
            'tgl_selesai'    => 'nullable|date',
            'keterangan'     => 'nullable|string',
        ];
    }
}
