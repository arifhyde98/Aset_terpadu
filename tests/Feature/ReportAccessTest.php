<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Opd;
use App\Models\Vehicle;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/**
 * Suite Pengujian Otorisasi, Keamanan Tenant-Isolation, dan Pemfilteran Modul Laporan di E-RANDIS.
 * 
 * Melakukan verifikasi keamanan tingkat tinggi (QA) sesuai standardisasi PM.
 */
class ReportAccessTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        cache()->flush();
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
     * Test 1: Pengguna tamu (Guest) wajib diarahkan ke halaman login.
     */
    public function test_guest_is_redirected_to_login()
    {
        $response = $this->get(route('reports.index'));
        $response->assertRedirect('/login');

        $responsePreview = $this->get(route('reports.preview'));
        $responsePreview->assertRedirect('/login');
    }

    /**
     * Test 2: Admin OPD hanya dapat melihat aset kendaraan milik OPD mereka sendiri.
     */
    public function test_opd_user_only_sees_their_own_vehicles()
    {
        $opdA = Opd::create(['nama' => 'Dinas A', 'singkatan' => 'DA']);
        $opdB = Opd::create(['nama' => 'Dinas B', 'singkatan' => 'DB']);

        $userOpdA = User::factory()->create([
            'role'   => UserRole::OPD,
            'opd_id' => $opdA->id
        ]);

        // Buat kendaraan untuk Dinas A
        $vehicleA = $this->createVehicle([
            'no_polisi' => 'DN 1111 AA',
            'opd_id'    => $opdA->id,
            'opd'       => $opdA->nama,
            'kondisi'   => 'Baik',
        ]);

        // Buat kendaraan untuk Dinas B
        $vehicleB = $this->createVehicle([
            'no_polisi' => 'DN 2222 BB',
            'opd_id'    => $opdB->id,
            'opd'       => $opdB->nama,
            'kondisi'   => 'Baik',
        ]);

        // Masuk sebagai User OPD A
        $response = $this->actingAs($userOpdA)
            ->get(route('reports.preview', [
                'type' => 'status'
            ]), ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        
        // Pastikan kendaraan Dinas A terdeteksi, dan Dinas B tersembunyi
        $response->assertSee($vehicleA->no_polisi);
        $response->assertDontSee($vehicleB->no_polisi);
    }

    /**
     * Test 3: Pengguna OPD tidak boleh membocorkan data OPD lain meskipun mencoba menembak 'opd_id' OPD lain secara manual.
     */
    public function test_opd_user_cannot_hijack_and_see_other_opd_data()
    {
        $opdA = Opd::create(['nama' => 'Dinas A', 'singkatan' => 'DA']);
        $opdB = Opd::create(['nama' => 'Dinas B', 'singkatan' => 'DB']);

        $userOpdA = User::factory()->create([
            'role'   => UserRole::OPD,
            'opd_id' => $opdA->id
        ]);

        $vehicleB = $this->createVehicle([
            'no_polisi' => 'DN 2222 BB',
            'opd_id'    => $opdB->id,
            'opd'       => $opdB->nama,
            'kondisi'   => 'Baik',
        ]);

        // Kirim parameter filter opd_id Dinas B secara manual
        $response = $this->actingAs($userOpdA)
            ->get(route('reports.preview', [
                'type'   => 'status',
                'opd_id' => $opdB->id // Mencoba meretas parameter
            ]), ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        
        // Tetap tidak boleh melihat kendaraan Dinas B
        $response->assertDontSee($vehicleB->no_polisi);
    }

    /**
     * Test 4: Pengguna OPD tidak boleh memindahkan kendaraan miliknya ke OPD lain saat memperbarui data (Update).
     */
    public function test_opd_user_cannot_move_vehicle_to_another_opd_on_update()
    {
        $opdA = Opd::create(['nama' => 'Dinas A', 'singkatan' => 'DA']);
        $opdB = Opd::create(['nama' => 'Dinas B', 'singkatan' => 'DB']);

        $userOpdA = User::factory()->create([
            'role'   => UserRole::OPD,
            'opd_id' => $opdA->id
        ]);

        // Buat kendaraan awal untuk Dinas A
        $vehicle = $this->createVehicle([
            'no_polisi' => 'DN 1111 AA',
            'opd_id'    => $opdA->id,
            'opd'       => $opdA->nama,
            'kondisi'   => \App\Enums\VehicleCondition::BAIK->value,
        ]);

        // Coba kirim update request dengan opd_id milik Dinas B secara manual
        $response = $this->actingAs($userOpdA)->put(route('vehicles.update', $vehicle->id), [
            'no_polisi' => 'DN 1111 AA',
            'merk' => 'Toyota',
            'tipe' => 'Avanza',
            'jenis' => 'Mobil',
            'stnk_ada' => 'Ada',
            'bpkb_ada' => 'Ada',
            'opd' => 'Dinas B',
            'opd_id' => $opdB->id, // Mencoba memindahkan kepemilikan kendaraan ke Dinas B
            'pemegang' => 'Budi',
            'status' => \App\Enums\VehicleStatus::TERSEDIA->value,
            'kondisi' => \App\Enums\VehicleCondition::BAIK->value,
        ]);

        $response->assertRedirect(route('vehicles.index'));

        // Muat ulang data kendaraan dari database
        $vehicle->refresh();

        // Pastikan kendaraan tetap terikat pada Dinas A (opd_id tidak berubah ke Dinas B)
        $this->assertEquals($opdA->id, $vehicle->opd_id, 'Celah Keamanan Terdeteksi! User OPD berhasil memindahkan kendaraannya ke OPD lain.');
        $this->assertEquals($opdA->nama, $vehicle->opd, 'Celah Keamanan Terdeteksi! Nama teks OPD berhasil diubah oleh User OPD.');
    }

    /**
     * Test 4: Akun OPD dengan opd_id kosong (null) tidak menerima data sama sekali (Lock total).
     */
    public function test_opd_user_with_null_opd_id_receives_no_data()
    {
        $userOpdNull = User::factory()->create([
            'role'   => UserRole::OPD,
            'opd_id' => null // Null OPD ID
        ]);

        $vehicle = $this->createVehicle([
            'no_polisi' => 'DN 5555 XX',
            'kondisi'   => 'Baik',
        ]);

        $response = $this->actingAs($userOpdNull)
            ->get(route('reports.preview', [
                'type' => 'status'
            ]), ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        // Sesuai isolasi data E-RANDIS, data tidak boleh bocor sama sekali
        $response->assertStatus(200);
        $response->assertDontSee($vehicle->no_polisi);
    }

    /**
     * Test 5: Admin dan Superadmin dapat melihat data global dari seluruh OPD.
     */
    public function test_admin_can_see_global_data_across_all_opd()
    {
        $opdA = Opd::create(['nama' => 'Dinas A', 'singkatan' => 'DA']);
        $opdB = Opd::create(['nama' => 'Dinas B', 'singkatan' => 'DB']);

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        $vehicleA = $this->createVehicle([
            'no_polisi' => 'DN 1111 AA',
            'opd_id'    => $opdA->id,
            'opd'       => $opdA->nama,
        ]);

        $vehicleB = $this->createVehicle([
            'no_polisi' => 'DN 2222 BB',
            'opd_id'    => $opdB->id,
            'opd'       => $opdB->nama,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('reports.preview', [
                'type' => 'status'
            ]), ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $response->assertSee($vehicleA->no_polisi);
        $response->assertSee($vehicleB->no_polisi);
    }

    /**
     * Test 6: Pemfilteran jenis laporan, kondisi fisik, dan tahun di-validasi ketat oleh server.
     */
    public function test_validation_rules_for_filters()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        // 1. Tipe laporan tidak valid
        $response1 = $this->actingAs($admin)
            ->getJson(route('reports.preview', [
                'type' => 'invalid-type'
            ]));
        $response1->assertStatus(422);
        $response1->assertJsonValidationErrors(['type']);

        // 2. Kondisi tidak valid
        $response2 = $this->actingAs($admin)
            ->getJson(route('reports.preview', [
                'type'    => 'status',
                'kondisi' => 'Kondisi Palsu'
            ]));
        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors(['kondisi']);

        // 3. Tahun tidak valid
        $response3 = $this->actingAs($admin)
            ->getJson(route('reports.preview', [
                'type'  => 'status',
                'tahun' => 99999
            ]));
        $response3->assertStatus(422);
        $response3->assertJsonValidationErrors(['tahun']);
    }

    /**
     * Test 7: Isolasi Cache Ringkasan antara OPD (tenant) dan Admin (global) terjamin aman tanpa pencemaran.
     */
    public function test_summary_cache_is_isolated_between_roles_and_tenants()
    {
        $opdA = Opd::create(['nama' => 'Dinas A', 'singkatan' => 'DA']);
        $userOpdA = User::factory()->create([
            'role'   => UserRole::OPD,
            'opd_id' => $opdA->id
        ]);
        
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        $this->createVehicle([
            'no_polisi' => 'DN 1111 AA',
            'opd_id'    => $opdA->id,
            'opd'       => $opdA->nama,
            'kondisi'   => 'Baik'
        ]);

        // 1. Request summary sebagai Admin (harus global, 1 unit)
        $this->actingAs($admin);
        $summaryAdmin = app(\App\Services\Erandis\ReportService::class)->getQuickSummary();
        $this->assertEquals(1, $summaryAdmin['total_unit']);

        // 2. Request summary sebagai OPD User A (harus tersaring ke OPD A saja, 1 unit)
        $summaryOpdA = $this->actingAs($userOpdA);
        $summaryOpdAData = app(\App\Services\Erandis\ReportService::class)->getQuickSummary($opdA->id);
        $this->assertEquals(1, $summaryOpdAData['total_unit']);

        // 3. Simulasikan user OPD dengan null opd_id (tidak boleh mencemari global admin summary cache)
        $userOpdNull = User::factory()->create([
            'role'   => UserRole::OPD,
            'opd_id' => null
        ]);
        
        $summaryOpdNullData = $this->actingAs($userOpdNull);
        // Panggil summary untuk null OPD - harusnya mengembalikan 0 karena di-lock
        $summaryNullData = app(\App\Services\Erandis\ReportService::class)->getQuickSummary(null);
        $this->assertEquals(
            0,
            $summaryNullData['total_unit'],
            'Akun OPD tanpa opd_id seharusnya tidak menerima data apa pun.'
        );
        
        // Reset sesi kembali ke Admin untuk memastikan cache Admin global tidak tercemar dan tetap aman
        $this->actingAs($admin);
        $summaryAdminRetry = app(\App\Services\Erandis\ReportService::class)->getQuickSummary();
        $this->assertEquals(1, $summaryAdminRetry['total_unit'], 'Bug Pencemaran Cache Terjadi! Summary Admin global tertimpa oleh summary OPD null.');
    }

    /**
     * Test 8: Cache ringkasan (summary) laporan di-invalidasi secara otomatis saat data kendaraan bertambah via POST request.
     */
    public function test_summary_cache_is_automatically_invalidated_on_vehicle_crud()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        $opdName = 'DINAS KESEHATAN ' . rand(10000, 99999);
        $opd = Opd::create(['nama' => $opdName, 'singkatan' => 'DK']);

        // Cek summary awal (harus 0)
        $this->actingAs($admin);
        $summaryInitial = app(\App\Services\Erandis\ReportService::class)->getQuickSummary();
        $this->assertEquals(0, $summaryInitial['total_unit']);

        // Tambah kendaraan dinas baru via endpoint POST nyata untuk memicu invalidasi cache otomatis di Controller
        $response = $this->actingAs($admin)->post(route('vehicles.store'), [
            'no_polisi' => 'DN 3333 CC',
            'merk' => 'Toyota',
            'tipe' => 'Avanza',
            'jenis' => 'Mobil',
            'stnk_ada' => 'Ada',
            'bpkb_ada' => 'Ada',
            'opd' => $opdName,
            'opd_id' => $opd->id,
            'pemegang' => 'Budi',
            'status' => \App\Enums\VehicleStatus::TERSEDIA->value,
            'kondisi' => \App\Enums\VehicleCondition::BAIK->value,
        ]);

        $response->assertRedirect(route('vehicles.index'));

        // Ambil summary baru (harus ter-update menjadi 1 karena cache berhasil dibersihkan secara real-time via Store controller event)
        $this->actingAs($admin);
        $summaryNew = app(\App\Services\Erandis\ReportService::class)->getQuickSummary();
        $this->assertEquals(1, $summaryNew['total_unit'], 'Gagal meng-invalidasi cache summary laporan setelah data kendaraan bertambah via POST request.');
    }

    /**
     * Test 9: Cache ringkasan (summary) laporan di-invalidasi secara otomatis saat data kendaraan di-update via PUT request.
     */
    public function test_summary_cache_is_invalidated_on_vehicle_update()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        $opdName = 'DINAS KESEHATAN ' . rand(10000, 99999);
        $opd = Opd::create(['nama' => $opdName, 'singkatan' => 'DK']);

        // Buat kendaraan awal
        $vehicle = $this->createVehicle([
            'no_polisi' => 'DN 4444 DD',
            'opd_id'    => $opd->id,
            'opd'       => $opd->nama,
            'kondisi'   => \App\Enums\VehicleCondition::BAIK->value,
        ]);

        // Cek summary awal (total = 1, layak_jalan = 1)
        $this->actingAs($admin);
        $summaryInitial = app(\App\Services\Erandis\ReportService::class)->getQuickSummary();
        $this->assertEquals(1, $summaryInitial['total_unit']);
        $this->assertEquals(1, $summaryInitial['layak_jalan']);

        // Update kondisi kendaraan menjadi Rusak Berat (sehingga layak_jalan berkurang menjadi 0) via PUT request
        $response = $this->actingAs($admin)->put(route('vehicles.update', $vehicle->id), [
            'no_polisi' => 'DN 4444 DD',
            'merk' => 'Toyota',
            'tipe' => 'Avanza',
            'jenis' => 'Mobil',
            'stnk_ada' => 'Ada',
            'bpkb_ada' => 'Ada',
            'opd' => $opdName,
            'opd_id' => $opd->id,
            'pemegang' => 'Budi Baru',
            'status' => \App\Enums\VehicleStatus::TERSEDIA->value,
            'kondisi' => \App\Enums\VehicleCondition::RUSAK_BERAT->value,
        ]);

        $response->assertRedirect(route('vehicles.index'));

        // Cek summary baru (layak_jalan harusnya berkurang menjadi 0 karena cache ter-invalidasi)
        $this->actingAs($admin);
        $summaryNew = app(\App\Services\Erandis\ReportService::class)->getQuickSummary();
        $this->assertEquals(1, $summaryNew['total_unit']);
        $this->assertEquals(0, $summaryNew['layak_jalan'], 'Gagal meng-invalidasi cache summary laporan setelah data kendaraan diperbarui via PUT request.');
    }

    /**
     * Test 10: Cache ringkasan (summary) laporan di-invalidasi secara otomatis saat data kendaraan di-delete via DELETE request.
     */
    public function test_summary_cache_is_invalidated_on_vehicle_delete()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        $opdName = 'DINAS KESEHATAN ' . rand(10000, 99999);
        $opd = Opd::create(['nama' => $opdName, 'singkatan' => 'DK']);

        // Buat kendaraan awal
        $vehicle = $this->createVehicle([
            'no_polisi' => 'DN 5555 EE',
            'opd_id'    => $opd->id,
            'opd'       => $opdName,
            'kondisi'   => \App\Enums\VehicleCondition::BAIK->value,
        ]);

        // Cek summary awal (total = 1)
        $this->actingAs($admin);
        $summaryInitial = app(\App\Services\Erandis\ReportService::class)->getQuickSummary();
        $this->assertEquals(1, $summaryInitial['total_unit']);

        // Hapus kendaraan dinas via DELETE request
        $response = $this->actingAs($admin)->delete(route('vehicles.destroy', $vehicle->id));
        $response->assertRedirect(route('vehicles.index'));

        // Cek summary baru (total harusnya kembali menjadi 0 karena cache ter-invalidasi)
        $this->actingAs($admin);
        $summaryNew = app(\App\Services\Erandis\ReportService::class)->getQuickSummary();
        $this->assertEquals(0, $summaryNew['total_unit'], 'Gagal meng-invalidasi cache summary laporan setelah data kendaraan dihapus via DELETE request.');
    }

    /**
     * Test 11: Pengguna OPD tidak diperbolehkan mengakses pratinjau (preview) laporan duplikasi (403 Forbidden).
     */
    public function test_opd_user_cannot_access_duplicate_report_preview()
    {
        $opd = Opd::create(['nama' => 'Dinas A ' . rand(10000, 99999), 'singkatan' => 'DA']);
        $userOpd = User::factory()->create([
            'role'   => UserRole::OPD,
            'opd_id' => $opd->id
        ]);

        $response = $this->actingAs($userOpd)
            ->getJson(route('reports.preview', [
                'type' => 'duplicate'
            ]));

        $response->assertStatus(403);
    }

    /**
     * Test 12: Pengguna OPD tidak diperbolehkan mencetak (print) laporan duplikasi (403 Forbidden).
     */
    public function test_opd_user_cannot_access_duplicate_report_print()
    {
        $opd = Opd::create(['nama' => 'Dinas A ' . rand(10000, 99999), 'singkatan' => 'DA']);
        $userOpd = User::factory()->create([
            'role'   => UserRole::OPD,
            'opd_id' => $opd->id
        ]);

        $response = $this->actingAs($userOpd)
            ->get(route('reports.print', [
                'type' => 'duplicate'
            ]));

        $response->assertStatus(403);
    }

    /**
     * Test 13: Pengguna OPD tidak diperbolehkan mengekspor (export) laporan duplikasi (403 Forbidden).
     */
    public function test_opd_user_cannot_access_duplicate_report_export()
    {
        $opd = Opd::create(['nama' => 'Dinas A ' . rand(10000, 99999), 'singkatan' => 'DA']);
        $userOpd = User::factory()->create([
            'role'   => UserRole::OPD,
            'opd_id' => $opd->id
        ]);

        $response = $this->actingAs($userOpd)
            ->get(route('reports.export', [
                'type' => 'duplicate'
            ]));

        $response->assertStatus(403);
    }

    /**
     * Test 14: Admin dapat mengakses pratinjau laporan duplikasi dengan sukses.
     */
    public function test_admin_user_can_access_duplicate_report_preview()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        $response = $this->actingAs($admin)
            ->get(route('reports.preview', [
                'type' => 'duplicate'
            ]), ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $response->assertSee('Hasil Pratinjau Laporan');
        $response->assertSee('Analisis Identik');
    }

    /**
     * Test 15: Memastikan laporan biasa (standard report) diekspor menggunakan DynamicQueryReportExport (Streaming).
     */
    public function test_standard_report_uses_query_based_exporter()
    {
        Excel::fake();
        \Carbon\Carbon::setTestNow(now());

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        $response = $this->actingAs($admin)
            ->get(route('reports.export', [
                'type' => 'status'
            ]));

        $response->assertStatus(200);

        $filename = 'laporan_status_' . date('Ymd_His') . '.xlsx';

        Excel::assertDownloaded($filename, function (\App\Exports\DynamicQueryReportExport $export) {
            return true;
        });

        \Carbon\Carbon::setTestNow(); // Reset
    }

    /**
     * Test 16: Memastikan laporan duplikasi diekspor menggunakan DynamicCollectionReportExport (Collection).
     */
    public function test_duplicate_report_uses_collection_based_exporter()
    {
        Excel::fake();
        \Carbon\Carbon::setTestNow(now());

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        $response = $this->actingAs($admin)
            ->get(route('reports.export', [
                'type' => 'duplicate'
            ]));

        $response->assertStatus(200);

        $filename = 'laporan_duplicate_' . date('Ymd_His') . '.xlsx';

        Excel::assertDownloaded($filename, function (\App\Exports\DynamicCollectionReportExport $export) {
            return true;
        });

        \Carbon\Carbon::setTestNow(); // Reset
    }

    /**
     * Test 17: Laporan duplikasi wajib memakai referensi eksplisit yang segar pada setiap pemanggilan.
     */
    public function test_duplicate_report_does_not_reuse_stale_reference_rows()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        $oldOriginal = $this->createVehicle(['no_polisi' => 'DN 7100 AA']);
        $oldDuplicate = $this->createVehicle(['no_polisi' => 'DN 7100 AA (2)']);

        $this->actingAs($admin);
        $firstPreview = app(\App\Services\Erandis\ReportService::class)->generatePreview([
            'type' => 'duplicate',
        ]);

        $firstAnalysis = $firstPreview['data']->getCollection()->pluck('keterangan_duplikat')->implode(' | ');
        $this->assertStringContainsString('DN 7100 AA', $firstAnalysis);

        $oldDuplicate->delete();
        $oldOriginal->delete();

        $this->createVehicle(['no_polisi' => 'DN 7200 BB']);
        $this->createVehicle(['no_polisi' => 'DN 7200 BB (2)']);

        $secondPreview = app(\App\Services\Erandis\ReportService::class)->generatePreview([
            'type' => 'duplicate',
        ]);

        $secondAnalysis = $secondPreview['data']->getCollection()->pluck('keterangan_duplikat')->implode(' | ');
        $this->assertStringContainsString('DN 7200 BB', $secondAnalysis);
        $this->assertStringNotContainsString('DN 7100 AA', $secondAnalysis);
    }

    /**
     * Test 18: Laporan duplikasi dengan filter OPD wajib mencocokkan data referensi secara global lintas OPD.
     */
    public function test_duplicate_report_opd_filter_uses_global_reference_rows()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $opdA = Opd::create(['nama' => 'Dinas A', 'singkatan' => 'DA']);
        $opdB = Opd::create(['nama' => 'Dinas B', 'singkatan' => 'DB']);

        $this->createVehicle([
            'no_polisi' => 'DN 8000 AA',
            'opd_id'    => $opdA->id,
            'opd'       => $opdA->nama,
        ]);

        $this->createVehicle([
            'no_polisi' => 'DN 8000 AA (2)',
            'opd_id'    => $opdB->id,
            'opd'       => $opdB->nama,
        ]);

        $this->actingAs($admin);

        $preview = app(\App\Services\Erandis\ReportService::class)->generatePreview([
            'type'   => 'duplicate',
            'opd_id' => $opdA->id,
        ]);

        $rows = $preview['data']->getCollection();

        $this->assertCount(1, $rows);
        $this->assertSame('DN 8000 AA', $rows->first()->no_polisi);
        $this->assertStringContainsString('DN 8000 AA (2)', $rows->first()->keterangan_duplikat);
        $this->assertStringNotContainsString('Tidak terdeteksi duplikasi', $rows->first()->keterangan_duplikat);
    }

    /**
     * Test 19: Pengguna OPD tidak diperbolehkan mengunduh PDF laporan duplikasi (403 Forbidden).
     */
    public function test_opd_user_cannot_access_duplicate_report_pdf()
    {
        $opd = Opd::create(['nama' => 'Dinas A ' . rand(10000, 99999), 'singkatan' => 'DA']);
        $userOpd = User::factory()->create([
            'role'   => UserRole::OPD,
            'opd_id' => $opd->id
        ]);

        $response = $this->actingAs($userOpd)
            ->get(route('reports.pdf', [
                'type' => 'duplicate'
            ]));

        $response->assertStatus(403);
    }

    /**
     * Test 20: Ekspor PDF berhasil diproduksi (Status 200 & Header PDF) untuk data di bawah batas pengaman RAM.
     */
    public function test_pdf_export_produces_downloadable_pdf()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        $this->createVehicle([
            'no_polisi' => 'DN 1234 XX',
            'kondisi' => 'Baik',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('reports.pdf', [
                'type' => 'status'
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}


