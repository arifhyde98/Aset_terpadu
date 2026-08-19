<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateElabelSertifikatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_sertipikat'     => ['required', 'string', 'max:100'],
            'nibar'             => ['nullable', 'string', 'max:100'],
            'status_penggunaan' => ['nullable', 'string', 'max:100'],
            'spesifikasi'       => ['nullable', 'string', 'max:255'],
            'luas'              => ['nullable', 'numeric'],
            'tanggal_perolehan' => ['nullable', 'date'],
            'nilai_perolehan'   => ['nullable', 'numeric'],
            'nama_pemilik'      => ['nullable', 'string', 'max:150'],
            'cara_perolehan'    => ['nullable', 'string', 'max:150'],
            'alamat'            => ['nullable', 'string', 'max:255'],
            'lokasi'            => ['nullable', 'string', 'max:255'],
            'dinas'             => ['nullable', 'string', 'max:150'],
            'pdf'               => ['nullable', 'file', 'mimes:pdf', 'max:51200'],
        ];
    }

    public function messages(): array
    {
        return [
            'no_sertipikat.required' => 'Nomor Sertipikat wajib diisi.',
            'pdf.mimes'              => 'File dokumen harus berformat PDF.',
            'pdf.max'                => 'Ukuran file PDF maksimal 50MB.',
        ];
    }
}
