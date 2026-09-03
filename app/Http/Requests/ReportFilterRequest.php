<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\VehicleCondition;

/**
 * Validasi FormRequest khusus untuk penyaringan Laporan Kendaraan Dinas di E-RANDIS.
 * 
 * Melindungi kueri laporan dari manipulasi parameter (tenant-bypass)
 * dengan memaksa pengguna OPD hanya memfilter instansinya sendiri.
 */
class ReportFilterRequest extends FormRequest
{
    /**
     * Memastikan semua pengguna terautentikasi dapat membuat permintaan ini.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $type = $this->input('type');
        $user = auth()->user();

        // Laporan duplikasi hanya boleh diakses oleh Admin BMD dan Super Admin
        if ($type === 'duplicate' && $user && $user->role === \App\Enums\UserRole::OPD) {
            return false;
        }

        return true;
    }

    /**
     * Aturan validasi ketat yang berlaku untuk parameter filter laporan.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type'    => ['required', 'string', Rule::in(['status', 'opd', 'document', 'duplicate'])],
            'source'  => ['nullable', 'string', Rule::in(['real', 'ebmd'])],
            'kondisi' => ['nullable', 'string', Rule::enum(VehicleCondition::class)],
            'opd_id'  => ['nullable', 'integer', 'exists:opds,id'],
            'tahun'   => ['nullable', 'integer', 'min:1950', 'max:' . (now()->year + 1)],
            'sort_by' => ['nullable', 'string', Rule::in([
                'no_polisi', 'nomor_register', 'merk', 'tipe', 'jenis', 'status', 'kondisi', 
                'opd', 'pemegang', 'nilai_perolehan', 'stnk_ada', 'tgl_stnk', 
                'bpkb_ada', 'no_mesin', 'no_rangka'
            ])],
            'sort_order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    /**
     * Kustomisasi pesan kesalahan validasi dalam Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required'  => 'Jenis Laporan wajib dipilih.',
            'type.in'        => 'Jenis Laporan tidak dikenali di sistem.',
            'kondisi.enum'   => 'Pilihan Kondisi Aset tidak valid.',
            'opd_id.exists'  => 'Instansi pengelola (OPD) tidak ditemukan.',
            'tahun.integer'  => 'Format Tahun tidak valid.',
            'tahun.min'      => 'Tahun minimal adalah 1950.',
            'tahun.max'      => 'Tahun maksimal tidak boleh melebihi tahun depan.',
            'sort_by.in'     => 'Kolom pengurutan laporan tidak valid.',
            'sort_order.in'  => 'Arah pengurutan laporan tidak valid.',
        ];
    }

    /**
     * Melakukan pembersihan data dan penguncian OPD sebelum validasi dieksekusi.
     * 
     * Khusus untuk akun OPD, parameter 'opd_id' dipaksa menunjuk ke instansi milik sendiri
     * demi menjaga keamanan isolasi data tenant secara mutlak.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $user = auth()->user();
        $merges = [];

        if ($user && $user->role->value === 'opd') {
            $merges['opd_id'] = $user->opd_id;
        }

        if (!$this->filled('source')) {
            $merges['source'] = 'real';
        }

        if (!empty($merges)) {
            $this->merge($merges);
        }
    }
}
