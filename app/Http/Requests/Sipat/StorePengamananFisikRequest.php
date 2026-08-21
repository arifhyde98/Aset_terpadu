<?php

namespace App\Http\Requests\Sipat;

use Illuminate\Foundation\Http\FormRequest;

class StorePengamananFisikRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sertifikat_ada' => 'nullable',
            'papan_nama' => 'nullable',
            'pagar' => 'nullable',
            'dikuasai_pihak_lain' => 'nullable',
            'tgl_cek' => 'nullable|date',
            'catatan' => 'nullable|string',
        ];
    }
}
