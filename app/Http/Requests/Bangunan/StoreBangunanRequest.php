<?php

namespace App\Http\Requests\Bangunan;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreBangunanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        // Kunci instansi OPD bagi role OPD (Tenant Isolation)
        if (auth()->user()?->role === UserRole::OPD) {
            $this->merge([
                'opd_id' => auth()->user()->opd_id,
            ]);
        }

        // Generate kode bangunan unik otomatis jika tidak diisi manual
        if (!$this->filled('kode_bangunan')) {
            $prefix = 'BG-' . date('Ymd') . '-';
            $random = strtoupper(substr(uniqid(), -4));
            $this->merge([
                'kode_bangunan' => $prefix . $random,
            ]);
        }

        // Bersihkan formatting rupiah pada harga
        if ($this->has('harga_perolehan') && is_string($this->harga_perolehan)) {
            $cleanHarga = preg_replace('/[^0-9]/', '', $this->harga_perolehan);
            $this->merge(['harga_perolehan' => $cleanHarga !== '' ? (float) $cleanHarga : 0]);
        }
        if ($this->has('nilai_buku') && is_string($this->nilai_buku)) {
            $cleanNilai = preg_replace('/[^0-9]/', '', $this->nilai_buku);
            $this->merge(['nilai_buku' => $cleanNilai !== '' ? (float) $cleanNilai : null]);
        }

        // Normalisasi boolean
        $this->merge([
            'konstruksi_tingkat' => $this->boolean('konstruksi_tingkat'),
            'konstruksi_beton'   => $this->boolean('konstruksi_beton'),
        ]);
    }

    public function rules(): array
    {
        return [
            'nama_bangunan'       => 'required|string|max:255',
            'kode_bangunan'       => 'required|string|max:50|unique:aset_bangunan,kode_bangunan',
            'kode_barang'         => 'nullable|string|max:50',
            'nomor_register'      => 'nullable|string|max:50',
            'opd_id'              => 'nullable|exists:opd,id',
            'aset_tanah_id'       => 'nullable|exists:aset_tanah,id_aset',
            'status_tanah_dasar'  => 'nullable|string|max:100',
            'luas_tanah_dasar'    => 'nullable|numeric|min:0',
            'jenis_bangunan'      => 'required|string',
            'tipe_rumah_dinas'    => 'nullable|string',
            'nama_penghuni'       => 'nullable|string|max:255',
            'kondisi'             => 'required|string',
            'konstruksi_tingkat'  => 'boolean',
            'jumlah_lantai'       => 'nullable|integer|min:1',
            'konstruksi_beton'    => 'boolean',
            'tipe_konstruksi'     => 'nullable|string|max:50',
            'luas_lantai'         => 'required|numeric|min:0',
            'luas_dasar'          => 'nullable|numeric|min:0',
            'alamat'              => 'nullable|string',
            'kecamatan_id'        => 'nullable|exists:kecamatan,id',
            'desa_id'             => 'nullable|exists:desa,id',
            'lat'                 => 'nullable|numeric',
            'lng'                 => 'nullable|numeric',
            'nomor_dokumen_pbg'   => 'nullable|string|max:100',
            'tanggal_dokumen_pbg' => 'nullable|date',
            'status_psp'          => 'nullable|string|max:50',
            'asal_usul'           => 'nullable|string|max:100',
            'tahun_pengadaan'     => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'harga_perolehan'     => 'nullable|numeric|min:0',
            'nilai_buku'          => 'nullable|numeric|min:0',
            'status_penggunaan'   => 'nullable|string|max:100',
            'foto_utama'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'dokumen_pdf'         => 'nullable|file|mimes:pdf|max:10240',
            'keterangan'          => 'nullable|string',
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_bangunan'       => 'Nama Bangunan',
            'kode_bangunan'       => 'Kode Bangunan',
            'kode_barang'         => 'Kode Barang',
            'nomor_register'      => 'Nomor Register',
            'opd_id'              => 'Instansi OPD',
            'aset_tanah_id'       => 'Tanah KIB A',
            'jenis_bangunan'      => 'Jenis Bangunan',
            'kondisi'             => 'Kondisi Bangunan',
            'luas_lantai'         => 'Luas Lantai',
            'harga_perolehan'     => 'Harga Perolehan',
            'foto_utama'          => 'Foto Bangunan',
            'dokumen_pdf'         => 'Berkas Dokumen PDF',
        ];
    }

    public function messages(): array
    {
        return [
            'nama_bangunan.required' => 'Nama bangunan wajib diisi.',
            'kode_bangunan.unique'   => 'Kode bangunan ini sudah terdaftar.',
            'luas_lantai.required'   => 'Luas lantai wajib diisi.',
            'foto_utama.max'         => 'Ukuran foto bangunan maksimal 5 MB.',
            'dokumen_pdf.max'        => 'Ukuran berkas dokumen PDF maksimal 10 MB.',
        ];
    }
}
