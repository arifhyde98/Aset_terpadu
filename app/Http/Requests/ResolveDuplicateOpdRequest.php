<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\VehicleService;

/**
 * Request untuk validasi resolusi OPD duplikat.
 */
class ResolveDuplicateOpdRequest extends FormRequest
{
    protected $vehicleService;

    /**
     * Konstruktor untuk mencuplik VehicleService secara otomatis.
     */
    public function __construct(VehicleService $vehicleService)
    {
        parent::__construct();
        $this->vehicleService = $vehicleService;
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
     * Aturan validasi untuk data input resolusi OPD duplikat.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'target_opd_id' => ['required', 'integer', 'exists:opds,id'],
            'source_opd_id' => ['required', 'integer', 'exists:opds,id'],
            'target_table'  => ['nullable', 'string', 'in:real,ebmd'],
        ];
    }

    /**
     * Validasi tambahan pasca-aturan untuk menjamin keabsahan hubungan pasangan duplikat (Rekomendasi PM #3).
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $targetId = (int)$this->input('target_opd_id');
            $sourceId = (int)$this->input('source_opd_id');
            $targetTable = $this->input('target_table', 'real');

            if ($targetId === $sourceId) {
                $validator->errors()->add(
                    'source_opd_id',
                    'OPD target dan OPD sumber tidak boleh sama.'
                );
                return;
            }

            // Dapatkan daftar OPD ganda/mirip yang sah dari service
            $duplicates = $this->vehicleService->getDuplicateOpdsList($targetTable);

            $isValidPair = collect($duplicates)->contains(function ($item) use ($targetId, $sourceId) {
                return ($item['opd_a']->id === $targetId && $item['opd_b']->id === $sourceId) ||
                       ($item['opd_a']->id === $sourceId && $item['opd_b']->id === $targetId);
            });

            if (!$isValidPair) {
                $validator->errors()->add(
                    'source_opd_id',
                    'Pasangan OPD yang diajukan bukan merupakan pasangan OPD ganda/mirip yang sah di sistem. Aksi dibatalkan demi keamanan data.'
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
            'target_opd_id.required' => 'ID OPD utama (target) wajib disertakan.',
            'target_opd_id.exists'   => 'ID OPD utama tidak terdaftar.',
            'source_opd_id.required' => 'ID OPD duplikat (sumber) wajib disertakan.',
            'source_opd_id.exists'   => 'ID OPD duplikat tidak terdaftar.',
        ];
    }
}
