<?php

namespace App\Http\Requests\Erandis;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request untuk memvalidasi parameter eksekusi AI Smart Import data kendaraan.
 */
class ExecuteSmartImportRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna diizinkan melakukan request ini.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Aturan validasi untuk parameter impor cerdas.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'import_token'     => 'required|string',
            'mapping'          => 'required|array',
            'headers'          => 'required|array',
            'header_row_index' => 'required|integer|min:0',
        ];
    }

    /**
     * Pesan kustom untuk validasi yang gagal.
     */
    public function messages(): array
    {
        return [
            'import_token.required'     => 'Token sesi impor wajib disertakan.',
            'mapping.required'          => 'Skema pemetaan kolom wajib disertakan.',
            'mapping.array'             => 'Format pemetaan kolom tidak valid.',
            'headers.required'          => 'Header asli dari berkas Excel wajib disertakan.',
            'header_row_index.required' => 'Posisi baris header wajib disertakan.',
            'header_row_index.integer'  => 'Indeks baris header harus berupa angka.',
            'header_row_index.min'      => 'Indeks baris header minimal bernilai 0.',
        ];
    }

    /**
     * Validasi tambahan pasca-input (Mapping & Duplication checks).
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $mapping = $this->input('mapping', []);
            
            // 1. Minimal Nomor Polisi (Plat) ATAU Merk harus dipetakan agar data teridentifikasi secara sahih
            $hasNoPolisi = !empty($mapping['no_polisi']);
            $hasMerk = !empty($mapping['merk']);
            
            if (!$hasNoPolisi && !$hasMerk) {
                $validator->errors()->add('mapping', 'Gagal memproses impor: Minimal kolom Nomor Polisi (Plat) atau Merk/Pabrikan harus dipetakan.');
            }

            // 2. Cegah pemetaan ganda dari kolom Excel yang sama ke field DB yang berbeda
            $mappedHeaders = array_filter($mapping, function ($val) {
                return $val !== '' && $val !== null;
            });
            
            $counts = array_count_values($mappedHeaders);
            foreach ($counts as $header => $count) {
                if ($count > 1) {
                    $validator->errors()->add('mapping', "Kolom Excel \"{$header}\" dipetakan lebih dari satu kali ke kolom database yang berbeda. Harap periksa kembali.");
                    break;
                }
            }
        });
    }
}
