<?php

namespace App\Http\Requests\Sipat;

use App\Models\Opd;
use Illuminate\Foundation\Http\FormRequest;

class StoreAsetTanahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kode_aset' => 'nullable|string|max:50|unique:aset_tanah,kode_aset',
            'status_pencatatan' => 'nullable|string|in:TERCATAT_KIB_A,USULAN_BELUM_TERCATAT',
            'nama_aset' => 'required_without:nama_aset_id|nullable|string|max:150',
            'nama_aset_id' => 'nullable|integer|exists:master_nama_aset,id',
            'peruntukan' => 'nullable|string|max:150',
            'luas' => 'nullable|numeric',
            'opd_id' => 'nullable|integer|exists:opds,id',
            'sub_opd_id' => 'nullable|integer|exists:sub_opds,id',
            'opd' => 'nullable|string|max:150',
            'alamat' => 'nullable|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'geojson' => 'nullable|string',
            'dasar_perolehan' => 'nullable|string|max:150',
            'harga_perolehan' => 'nullable|numeric',
            'tanggal_perolehan' => 'nullable|date',
            'keterangan' => 'nullable|string',
            'kecamatan_id' => 'nullable|integer|exists:kecamatan,id',
            'desa_id' => 'nullable|integer|exists:desa,id',
            'initial_status_id' => 'nullable|integer|exists:status_proses,id_status',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Status pencatatan default
        $statusPencatatan = $this->input('status_pencatatan');
        $kodeAset = trim((string) $this->input('kode_aset'));

        if (empty($statusPencatatan)) {
            $statusPencatatan = empty($kodeAset) ? 'USULAN_BELUM_TERCATAT' : 'TERCATAT_KIB_A';
            $this->merge(['status_pencatatan' => $statusPencatatan]);
        }

        // Jika tanah belum tercatat dan kode aset kosong, buat NIBAR sementara otomatis
        if ($statusPencatatan === 'USULAN_BELUM_TERCATAT' && empty($kodeAset)) {
            $prefix = 'DRAFT-' . date('Ymd') . '-';
            $counter = 1;
            do {
                $candidateCode = $prefix . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
                $exists = \App\Models\AsetTanah::where('kode_aset', $candidateCode)->exists();
                $counter++;
            } while ($exists);
            $this->merge(['kode_aset' => $candidateCode]);
        } elseif (empty($kodeAset)) {
            $this->merge(['status_pencatatan' => 'USULAN_BELUM_TERCATAT']);
            $prefix = 'DRAFT-' . date('Ymd') . '-';
            $counter = 1;
            do {
                $candidateCode = $prefix . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
                $exists = \App\Models\AsetTanah::where('kode_aset', $candidateCode)->exists();
                $counter++;
            } while ($exists);
            $this->merge(['kode_aset' => $candidateCode]);
        }

        // Sinkronisasi dua arah nama_aset string dan nama_aset_id
        if ($this->filled('nama_aset_id')) {
            $master = \App\Models\MasterNamaAset::find($this->input('nama_aset_id'));
            if ($master) {
                $this->merge(['nama_aset' => $master->nama]);
            }
        } elseif ($this->filled('nama_aset')) {
            $master = \App\Models\MasterNamaAset::firstOrCreate(
                ['nama' => trim($this->input('nama_aset'))],
                ['is_active' => true]
            );
            $this->merge(['nama_aset_id' => $master->id]);
        }

        // Kunci tenant: paksa opd_id dan nama opd sesuai user login jika rolenya adalah OPD atau KPB (Pola E-RANDIS)
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->role === \App\Enums\UserRole::OPD) {
                $this->merge([
                    'opd_id' => $user->opd_id,
                    'opd' => $user->opd?->nama,
                ]);
                return;
            } elseif ($user->role === \App\Enums\UserRole::KPB) {
                $this->merge([
                    'opd_id' => $user->opd_id,
                    'opd' => $user->opd?->nama,
                    'sub_opd_id' => $user->sub_opd_id,
                ]);
                return;
            }
        }

        if ($this->filled('opd_id')) {
            return;
        }

        $opdName = trim((string) $this->input('opd', ''));
        if ($opdName === '') {
            return;
        }

        $opd = Opd::whereRaw('LOWER(TRIM(nama)) = ?', [mb_strtolower($opdName)])->first();
        if ($opd) {
            $this->merge(['opd_id' => $opd->id]);
        }
    }
}
