<?php

namespace App\Http\Requests\Sipat;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\Sipat\AsetTanahService;

/**
 * Request untuk validasi resolusi aset tanah ganda.
 */
class StoreResolveDuplicateAset extends FormRequest
{
    protected $asetService;

    public function __construct(AsetTanahService $asetService)
    {
        parent::__construct();
        $this->asetService = $asetService;
    }

    /**
     * Tentukan apakah pengguna diizinkan melakukan request ini.
     */
    public function authorize(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, [\App\Enums\UserRole::SUPERADMIN, \App\Enums\UserRole::ADMIN]);
    }

    /**
     * Aturan validasi untuk data input resolusi aset tanah ganda.
     */
    public function rules(): array
    {
        return [
            'original_id'  => ['required', 'integer', 'exists:aset_tanah,id_aset'],
            'duplicate_id' => ['required', 'integer', 'exists:aset_tanah,id_aset'],
            'action'       => ['required', 'string', 'in:merge,delete'],
            'direction'    => ['nullable', 'string', 'in:keep_original,keep_duplicate'],
        ];
    }

    /**
     * Validasi tambahan pasca-aturan untuk menjamin keabsahan hubungan pasangan duplikat.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $originalId = (int)$this->input('original_id');
            $duplicateId = (int)$this->input('duplicate_id');

            // Dapatkan daftar pasangan ganda sah menurut logika database service
            $duplicates = $this->asetService->getDuplicateAsetList();

            $isValidPair = collect($duplicates)->contains(function ($item) use ($originalId, $duplicateId) {
                return $item['original_aset']->id_aset === $originalId && $item['duplicate_aset']->id_aset === $duplicateId;
            });

            if (!$isValidPair) {
                $validator->errors()->add(
                    'duplicate_id',
                    'ID Aset yang diajukan bukan merupakan pasangan duplikasi kode/nama yang sah di database. Aksi dibatalkan.'
                );
            }
        });
    }

    /**
     * Pesan kesalahan kustom.
     */
    public function messages(): array
    {
        return [
            'original_id.required'  => 'ID aset induk wajib disertakan.',
            'original_id.exists'    => 'ID aset induk tidak terdaftar.',
            'duplicate_id.required' => 'ID aset ganda wajib disertakan.',
            'duplicate_id.exists'   => 'ID aset ganda tidak terdaftar.',
            'action.required'       => 'Aksi penyelesaian wajib dipilih.',
            'action.in'             => 'Aksi penyelesaian tidak valid. Hanya menerima merge atau delete.',
        ];
    }
}
