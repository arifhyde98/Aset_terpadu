<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Opd;
use App\Models\Vehicle;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Suite Pengujian Khusus Fitur Pembersihan Karakter Spesial Nomor Rangka & Nomor Mesin (Sanitize Identifiers).
 */
class VehicleSanitizeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper untuk membuat objek kendaraan dengan data default valid.
     */
    protected function createVehicle(array $attributes = []): Vehicle
    {
        $defaults = [
            'no_polisi' => 'DN 8888 ZZ',
            'merk'      => 'Toyota',
            'tipe'      => 'Hilux',
            'jenis'     => 'Mobil',
            'stnk_ada'  => 'Ada',
            'bpkb_ada'  => 'Ada',
            'opd'       => 'DINAS KESEHATAN',
            'pemegang'  => 'Agus',
            'status'    => \App\Enums\VehicleStatus::TERSEDIA->value,
            'kondisi'   => \App\Enums\VehicleCondition::BAIK->value,
        ];

        return Vehicle::withoutGlobalScopes()->create(array_merge($defaults, $attributes));
    }

    /**
     * Test 1: Superadmin dapat melakukan pembersihan massal secara global.
     */
    public function test_superadmin_can_sanitize_vehicle_identifiers()
    {
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN
        ]);

        // Buat kendaraan dengan nomor rangka & mesin kotor (mengandung spasi, strip, titik, dll)
        $vehicle1 = $this->createVehicle([
            'no_polisi' => 'DN 1234 XY',
            'no_rangka' => 'MH3-12 34.A',
            'no_mesin'  => '2TR_FE-56.7'
        ]);

        $vehicle2 = $this->createVehicle([
            'no_polisi' => 'DN 5678 YZ',
            'no_rangka' => ' MH3/5678-B ',
            'no_mesin'  => ' 1NZ.FE_890 '
        ]);

        // Kirim request POST sanitize sebagai Superadmin
        $response = $this->actingAs($superadmin)->post(route('vehicles.sanitize-identifiers'));

        // Harus dialihkan kembali ke daftar kendaraan dengan pesan sukses
        $response->assertRedirect(route('vehicles.index'));
        $response->assertSessionHas('success');

        // Segarkan data dari database
        $vehicle1->refresh();
        $vehicle2->refresh();

        // Pastikan seluruh karakter selain huruf dan angka telah dibersihkan sepenuhnya
        $this->assertEquals('MH31234A', $vehicle1->no_rangka);
        $this->assertEquals('2TRFE567', $vehicle1->no_mesin);

        $this->assertEquals('MH35678B', $vehicle2->no_rangka);
        $this->assertEquals('1NZFE890', $vehicle2->no_mesin);
    }

    /**
     * Test 2: Admin BMD ditolak mengakses rute pembersihan (Redirect ke Home).
     */
    public function test_admin_bmd_cannot_access_sanitize_route()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        $response = $this->actingAs($admin)->post(route('vehicles.sanitize-identifiers'));

        // Harus ditolak dan dialihkan ke home oleh middleware
        $response->assertRedirect(route('home'));
    }

    /**
     * Test 3: Admin OPD ditolak mengakses rute pembersihan (Redirect ke Home).
     */
    public function test_opd_user_cannot_access_sanitize_route()
    {
        $opd = Opd::create(['nama' => 'DINAS KESEHATAN']);
        $opdUser = User::factory()->create([
            'role'   => UserRole::OPD,
            'opd_id' => $opd->id
        ]);

        $response = $this->actingAs($opdUser)->post(route('vehicles.sanitize-identifiers'));

        // Harus ditolak dan dialihkan ke home oleh middleware
        $response->assertRedirect(route('home'));
    }
}
