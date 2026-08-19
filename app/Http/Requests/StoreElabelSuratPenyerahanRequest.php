<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreElabelSuratPenyerahanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_surat'          => ['required', 'string', 'max:150'],
            'nibar'             => ['nullable', 'string', 'max:100'],
            'status_penggunaan' => ['nullable', 'string', 'max:150'],
            'spesifikasi'       => ['nullable', 'string', 'max:255'],
            'jenis_penyerahan'  => ['nullable', 'string', 'max:150'],
            'luas'              => ['nullable', 'numeric'],
            'tanggal_perolehan' => ['nullable', 'date'],
            'alamat'            => ['nullable', 'string', 'max:255'],
            'lokasi'            => ['nullable', 'string', 'max:255'],
            'dinas'             => ['nullable', 'string', 'max:150'],
            'pemberi_hibah'     => ['nullable', 'string', 'max:150'],
            'pdf'               => ['nullable', 'file', 'mimes:pdf', 'max:51200'],
        ];
    }

    public function messages(): array
    {
        return [
            'no_surat.required' => 'Nomor Surat wajib diisi.',
            'pdf.mimes'         => 'File dokumen harus berformat PDF.',
            'pdf.max'           => 'Ukuran file PDF maksimal 50MB.',
        ];
    }
}
