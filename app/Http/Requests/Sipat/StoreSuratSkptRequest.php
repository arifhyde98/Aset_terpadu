<?php

namespace App\Http\Requests\Sipat;

use App\Models\Camat;
use App\Models\Desa;
use App\Models\KepalaDesa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSuratSkptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nomor_surat' => 'nullable|string|max:150',
            'alamat_kantor' => 'nullable|string|max:255',
            'kecamatan_id' => 'required|integer|exists:kecamatan,id',
            'desa_id' => 'required|integer|exists:desa,id',
            'kepala_desa_id' => 'required|integer|exists:kepala_desa,id',
            'camat_id' => 'required|integer|exists:camat,id',
            'pemohon_id' => 'required|integer|exists:pemohon,id',
            'lokasi_tanah' => 'nullable|string|max:255',
            'jenis_tanah' => 'nullable|string|max:150',
            'status_tanah' => 'nullable|string|max:255',
            'asal_tanah' => 'nullable|string',
            'pernyataan_tanah' => 'nullable|string',
            'luas_tanah' => 'nullable|numeric',
            'dasar_perolehan' => 'nullable|string|max:150',
            'batas_utara' => 'nullable|string|max:255',
            'batas_timur' => 'nullable|string|max:255',
            'batas_selatan' => 'nullable|string|max:255',
            'batas_barat' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string',
            'tanggal_surat' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'kecamatan_id.required' => 'Kecamatan wajib dipilih.',
            'desa_id.required' => 'Desa wajib dipilih.',
            'kepala_desa_id.required' => 'Kepala desa wajib dipilih.',
            'camat_id.required' => 'Camat wajib dipilih.',
            'pemohon_id.required' => 'Pemohon wajib dipilih.',
            'tanggal_surat.required' => 'Tanggal surat wajib diisi.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $kecamatanId = (int) $this->input('kecamatan_id');
            $desaId = (int) $this->input('desa_id');
            $kepalaDesaId = (int) $this->input('kepala_desa_id');
            $camatId = (int) $this->input('camat_id');

            if ($desaId > 0 && $kecamatanId > 0) {
                $desa = Desa::find($desaId);
                if (!$desa) {
                    $validator->errors()->add('desa_id', 'Desa tidak ditemukan.');
                } elseif ((int) $desa->kecamatan_id !== $kecamatanId) {
                    $validator->errors()->add('desa_id', 'Desa tidak sesuai dengan kecamatan yang dipilih.');
                }
            }

            if ($kepalaDesaId > 0 && $desaId > 0) {
                $kepalaDesa = KepalaDesa::find($kepalaDesaId);
                if (!$kepalaDesa) {
                    $validator->errors()->add('kepala_desa_id', 'Kepala desa tidak ditemukan.');
                } elseif ((int) $kepalaDesa->desa_id !== $desaId) {
                    $validator->errors()->add('kepala_desa_id', 'Kepala desa tidak sesuai dengan desa yang dipilih.');
                }
            }

            if ($camatId > 0 && $kecamatanId > 0) {
                $camat = Camat::find($camatId);
                if (!$camat) {
                    $validator->errors()->add('camat_id', 'Camat tidak ditemukan.');
                } elseif ((int) $camat->kecamatan_id !== $kecamatanId) {
                    $validator->errors()->add('camat_id', 'Camat tidak sesuai dengan kecamatan yang dipilih.');
                }
            }
        });
    }
}
