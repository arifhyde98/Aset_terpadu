<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Opd;
use App\Models\Vehicle;
use App\Enums\UserRole;
use App\Services\Erandis\VehicleService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Suite Pengujian Khusus Fitur Diagnosis dan Resolusi Duplikasi Data Kendaraan & OPD.
 */
class VehicleDuplicationTest extends TestCase
{
    use DatabaseTransactions;

    protected $vehicleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vehicleService = app(VehicleService::class);
    }

    /**
     * Helper untuk membuat objek kendaraan dengan data default valid tanpa menggunakan factory.
     */
    protected function createVehicle(array $attributes = []): Vehicle
    {
        $defaults = [
            'no_polisi' => 'DN 9999 XX',
            'merk'      => 'Toyota',
            'tipe'      => 'Avanza',
            'jenis'     => 'Mobil',
            'stnk_ada'  => 'Ada',
            'bpkb_ada'  => 'Ada',
            'opd'       => 'DINAS KESEHATAN',
            'pemegang'  => 'Budi',
            'status'    => \App\Enums\VehicleStatus::TERSEDIA->value,
            'kondisi'   => \App\Enums\VehicleCondition::BAIK->value,
        ];

        return Vehicle::withoutGlobalScopes()->create(array_merge($defaults, $attributes));
    }

    /**
     * Test 1: OPD User tidak boleh memiliki hak akses ke endpoint duplikasi (Rekomendasi PM #1).
     */
    public function test_opd_user_cannot_access_duplication_endpoints()
    {
        // Buat OPD agar user OPD tidak null opd_id-nya
        $opd = Opd::create(['nama' => 'DINAS KESEHATAN ' . rand(1000, 9999)]);
        $user = User::factory()->create([
            'role'   => UserRole::OPD,
            'opd_id' => $opd->id
        ]);

        // Coba akses check-duplicates
        $response1 = $this->actingAs($user)->getJson(route('vehicles.check-duplicates'));
        $response1->assertRedirect(route('home'));

        // Coba akses resolve-duplicate-vehicle
        $response2 = $this->actingAs($user)->postJson(route('vehicles.resolve-duplicate-vehicle'), [
            'original_id'  => 1,
            'duplicate_id' => 2,
            'action'       => 'merge'
        ]);
        $response2->assertRedirect(route('home'));

        // Coba akses resolve-duplicate-opd
        $response3 = $this->actingAs($user)->postJson(route('vehicles.resolve-duplicate-opd'), [
            'target_opd_id' => 1,
            'source_opd_id' => 2
        ]);
        $response3->assertRedirect(route('home'));
    }

    /**
     * Test 2: Admin & Superadmin diizinkan mengakses rute diagnosis (Rekomendasi PM #1).
     */
    public function test_admin_user_can_access_duplication_endpoints()
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        $response = $this->actingAs($user)->getJson(route('vehicles.check-duplicates'));
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'vehicles', 'opds']);
    }

    /**
     * Test 3: Pasangan kendaraan ganda tidak valid/sembarangan wajib ditolak (Rekomendasi PM #3).
     */
    public function test_invalid_duplicate_vehicle_pair_is_rejected()
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        // Buat dua kendaraan acak yang TIDAK ganda
        $vehicle1 = $this->createVehicle(['no_polisi' => 'DN 1000 AA']);
        $vehicle2 = $this->createVehicle(['no_polisi' => 'DN 2000 BB']);

        $response = $this->actingAs($user)->postJson(route('vehicles.resolve-duplicate-vehicle'), [
            'original_id'  => $vehicle1->id,
            'duplicate_id' => $vehicle2->id,
            'action'       => 'merge'
        ]);

        $response->assertStatus(422); // Unprocessable Entity (Validasi FormRequest Gagal)
        $response->assertJsonValidationErrors(['duplicate_id']);
    }

    /**
     * Test 4: Penggabungan kendaraan duplikat valid berhasil & mengisi kolom kosong (Rekomendasi PM #5).
     */
    public function test_merge_valid_duplicate_vehicle_succeeds()
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        // Buat pasangan kendaraan duplikat sah
        $original = $this->createVehicle([
            'no_polisi' => 'DN 5555 XY',
            'no_mesin'  => null,
            'no_rangka' => null
        ]);

        $duplicate = $this->createVehicle([
            'no_polisi' => 'DN 5555 XY (2)',
            'no_mesin'  => 'MESIN123',
            'no_rangka' => 'RANGKA456'
        ]);

        // Kirim request merge
        $response = $this->actingAs($user)->postJson(route('vehicles.resolve-duplicate-vehicle'), [
            'original_id'  => $original->id,
            'duplicate_id' => $duplicate->id,
            'action'       => 'merge'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Cek apakah data kosong pada induk telah terisi dari data duplikat
        $original->refresh();
        $this->assertEquals('MESIN123', $original->no_mesin);
        $this->assertEquals('RANGKA456', $original->no_rangka);

        // Pastikan kendaraan duplikat telah dihapus dari database
        $this->assertDatabaseMissing('vehicles', ['id' => $duplicate->id]);
    }

    /**
     * Test 5: Aksi hapus (delete) hanya diperbolehkan untuk kendaraan duplikat sah (Rekomendasi PM #4).
     */
    public function test_delete_only_allowed_for_valid_duplicate_vehicles()
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        // Skenario 1: Coba hapus kendaraan sembarang -> Wajib Gagal
        $v1 = $this->createVehicle(['no_polisi' => 'DN 1111 X']);
        $v2 = $this->createVehicle(['no_polisi' => 'DN 2222 Y']);

        $response1 = $this->actingAs($user)->postJson(route('vehicles.resolve-duplicate-vehicle'), [
            'original_id'  => $v1->id,
            'duplicate_id' => $v2->id,
            'action'       => 'delete'
        ]);
        $response1->assertStatus(422);

        // Skenario 2: Hapus kendaraan duplikat sah -> Wajib Sukses
        $orig = $this->createVehicle(['no_polisi' => 'DN 3333 Z']);
        $dup = $this->createVehicle(['no_polisi' => 'DN 3333 Z (2)']);

        $response2 = $this->actingAs($user)->postJson(route('vehicles.resolve-duplicate-vehicle'), [
            'original_id'  => $orig->id,
            'duplicate_id' => $dup->id,
            'action'       => 'delete'
        ]);
        $response2->assertStatus(200);
        $this->assertDatabaseMissing('vehicles', ['id' => $dup->id]);
        $this->assertDatabaseHas('vehicles', ['id' => $orig->id]);
    }

    /**
     * Test 6: Penggabungan OPD duplikat memindahkan kendaraan & sinkronkan field opd_id serta opd teks (Rekomendasi PM #6).
     */
    public function test_merge_opd_migrates_vehicles_and_syncs_opd_fields()
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        $targetName = 'DINAS KESEHATAN ' . rand(1000, 9999);
        $sourceName = $targetName . ' KAB';

        // Buat dua OPD yang terindikasi mirip secara manual
        $targetOpd = Opd::create(['nama' => $targetName]);
        $sourceOpd = Opd::create(['nama' => $sourceName]);

        // Buat kendaraan pada OPD sumber
        $vehicle = $this->createVehicle([
            'opd_id' => $sourceOpd->id,
            'opd'    => $sourceName
        ]);

        // Kirim request merge OPD
        $response = $this->actingAs($user)->postJson(route('vehicles.resolve-duplicate-opd'), [
            'target_opd_id' => $targetOpd->id,
            'source_opd_id' => $sourceOpd->id
        ]);

        $response->assertStatus(200);

        // Pastikan kendaraan sekarang terikat dengan OPD Utama & teks 'opd' tersinkronisasi
        $vehicle->refresh();
        $this->assertEquals($targetOpd->id, $vehicle->opd_id);
        $this->assertEquals($targetName, $vehicle->opd);

        // Pastikan OPD sumber (duplikat) yang kosong sudah terhapus bersih
        $this->assertDatabaseMissing('opds', ['id' => $sourceOpd->id]);
    }

    /**
     * Test 7: Pencarian kendaraan asli wajib pencocokan plat eksak (Exact Match), bukan awalan/prefix (Rekomendasi PM #7).
     */
    public function test_original_plate_requires_exact_match()
    {
        $vIndukLain = $this->createVehicle(['no_polisi' => 'DN 1234 A']);
        $vGanda = $this->createVehicle(['no_polisi' => 'DN 1234 AB (2)']);

        // Jalankan pindaian duplikasi
        $duplicates = $this->vehicleService->getDuplicateVehiclesList();

        // Cari apakah ada entri di mana vGanda berpasangan dengan vIndukLain
        $hasMismatch = collect($duplicates)->contains(function ($item) use ($vGanda, $vIndukLain) {
            return $item['duplicate_vehicle']->id === $vGanda->id &&
                   $item['original_vehicle']->id === $vIndukLain->id;
        });

        // Wajib bernilai FALSE karena plat induk 'DN 1234 AB' tidak ada di database (sehingga bukan pasangan duplikat sah)
        $this->assertFalse($hasMismatch, 'Terjadi mis-match prefix plat nomor. Pencarian original plate wajib menggunakan exact match.');
    }
}
