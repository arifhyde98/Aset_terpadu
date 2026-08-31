<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\VehicleService;

/**
 * Request untuk validasi resolusi kendaraan ganda.
 */
class ResolveDuplicateVehicleRequest extends FormRequest
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
     * Aturan validasi untuk data input resolusi kendaraan ganda.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $targetTable = $this->input('target_table', 'real');
        $tableName = $targetTable === 'ebmd' ? 'ebmd_vehicles' : 'vehicles';

        return [
            'original_id'  => ['required', 'integer', 'exists:' . $tableName . ',id'],
            'duplicate_id' => ['required', 'integer', 'exists:' . $tableName . ',id'],
            'action'       => ['required', 'string', 'in:merge,delete'],
            'direction'    => ['nullable', 'string', 'in:keep_original,keep_duplicate'],
            'target_table' => ['nullable', 'string', 'in:real,ebmd'],
        ];
    }

    /**
     * Validasi tambahan pasca-aturan untuk menjamin keabsahan hubungan pasangan duplikat (Rekomendasi PM #3 & #4).
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $originalId = (int)$this->input('original_id');
            $duplicateId = (int)$this->input('duplicate_id');
            $targetTable = $this->input('target_table', 'real');

            // Dapatkan daftar pasangan ganda sah menurut logika database service
            $duplicates = $this->vehicleService->getDuplicateVehiclesList($targetTable);

            $isValidPair = collect($duplicates)->contains(function ($item) use ($originalId, $duplicateId) {
                return $item['original_vehicle']->id === $originalId && $item['duplicate_vehicle']->id === $duplicateId;
            });

            if (!$isValidPair) {
                $validator->errors()->add(
                    'duplicate_id',
                    'ID Kendaraan yang diajukan bukan merupakan pasangan duplikasi plat/mesin yang sah di database. Aksi dibatalkan demi keamanan data.'
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
            'original_id.required'  => 'ID kendaraan induk wajib disertakan.',
            'original_id.exists'    => 'ID kendaraan induk tidak terdaftar.',
            'duplicate_id.required' => 'ID kendaraan ganda wajib disertakan.',
            'duplicate_id.exists'   => 'ID kendaraan ganda tidak terdaftar.',
            'action.required'       => 'Aksi penyelesaian wajib dipilih.',
            'action.in'             => 'Aksi penyelesaian tidak valid. Hanya menerima merge atau delete.',
        ];
    }
}
