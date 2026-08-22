<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateElabelBpkbRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year'         => ['required', 'integer', 'min:1900', 'max:2100'],
            'vehicle_type' => ['required', 'string', 'in:R4,R2,mobil,motor'],
            'plate_number' => ['required', 'string', 'max:20'],
            'no_bpkb'      => ['nullable', 'string', 'max:50'],
            'nibar'        => ['nullable', 'string', 'max:100'],
            'no_rangka'    => ['nullable', 'string', 'max:50'],
            'no_mesin'     => ['nullable', 'string', 'max:50'],
            'merek'        => ['nullable', 'string', 'max:100'],
            'tipe'         => ['nullable', 'string', 'max:100'],
            'isi_silinder' => ['nullable', 'string', 'max:50'],
            'pengguna'     => ['nullable', 'string', 'max:100'],
            'pdf'          => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'sipat_opd_id' => ['nullable', 'integer', 'exists:opd,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'year.required'         => 'Tahun dokumen wajib diisi.',
            'vehicle_type.required' => 'Jenis kendaraan wajib dipilih.',
            'plate_number.required' => 'Nomor Polisi wajib diisi.',
            'pdf.mimes'             => 'File dokumen harus berformat PDF.',
            'pdf.max'               => 'Ukuran file PDF maksimal 5MB.',
        ];
    }
}
