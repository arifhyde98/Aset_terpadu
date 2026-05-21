<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Opd;
use App\Models\Vehicle;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/**
 * Suite Pengujian Fitur AI Smart Import dan Keamanan Keberlanjutan Data Kendaraan.
 */
class VehicleImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Buat folder storage virtual
        Storage::fake('local');
    }

    /**
     * Test 1: Mengetes pratinjau impor (Preview) menghasilkan token acak ter-generate di cache.
     */
    public function test_import_preview_generates_security_token_and_caches_metadata()
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        // Buat file excel palsu
        $file = UploadedFile::fake()->create('vehicles.xlsx', 100);

        // Mocking Excel facade to return mocked sheet data for testing
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    ['Nomor Polisi', 'Jenis Kendaraan', 'Merk/Tipe', 'Nomor Mesin', 'Nomor Rangka'],
                    ['DN 1234 XX', 'Mobil', 'Toyota Avanza', '12345', '67890'],
                ]
            ]);

        $response = $this->actingAs($user)
            ->postJson(route('vehicles.import-preview'), [
                'file' => $file
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'headers',
            'samples',
            'target_columns',
            'suggested_mapping',
            'header_row_index',
            'import_token'
        ]);

        $token = $response->json('import_token');
        $this->assertNotNull($token);
        $this->assertStringStartsWith('import_', $token);

        // Pastikan metadata tersimpan dengan benar di cache
        $cachedData = Cache::get($token);
        $this->assertNotNull($cachedData);
        $this->assertEquals($user->id, $cachedData['user_id']);
        $this->assertNotNull($cachedData['file_path']);
        $this->assertGreaterThan(now()->timestamp, $cachedData['expires_at']);
    }

    /**
     * Test 2: Mengetes eksekusi impor dengan token palsu / kedaluwarsa diblokir.
     */
    public function test_import_execution_fails_with_invalid_or_expired_token()
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        // Kirim request impor final dengan token palsu
        $response = $this->actingAs($user)
            ->post(route('vehicles.import'), [
                'import_token'     => 'import_invalid_token_123',
                'mapping'          => ['no_polisi' => 'Nomor Polisi'],
                'headers'          => ['Nomor Polisi'],
                'header_row_index' => 0
            ]);

        $response->assertRedirect(route('vehicles.index'));
        $response->assertSessionHas('error', 'Sesi impor tidak valid atau sudah kedaluwarsa. Silakan unggah ulang berkas.');
    }

    /**
     * Test 3: Mengetes perlindungan multi-tenant OPD (Akun OPD dengan opd_id kosong memicu Exception).
     */
    public function test_opd_user_with_null_opd_id_throws_exception_on_import()
    {
        $user = User::factory()->create([
            'role'   => UserRole::OPD,
            'opd_id' => null // Null OPD ID
        ]);

        $this->actingAs($user);

        // Buat objek VehicleImport secara manual untuk memicu evaluasi model()
        $importer = new \App\Imports\VehicleImport(
            ['no_polisi' => 'Nomor Polisi', 'merk' => 'Merk'],
            ['Nomor Polisi', 'Merk']
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Akun OPD belum terhubung ke instansi. Impor dibatalkan.');

        $row = [
            0 => 'DN 1234 XX', // Nomor Polisi
            1 => 'Toyota Avanza', // Merk
        ];

        $importer->model($row);
    }

    /**
     * Test 3b: Mengetes kegagalan impor jika terdeteksi nomor register yang sama/duplikat dengan database.
     */
    public function test_import_throws_exception_on_duplicate_nomor_register()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        $this->actingAs($admin);

        // Buat kendaraan awal dengan nomor register terisi
        Vehicle::create([
            'no_polisi' => 'DN 1111 AA',
            'nomor_register' => 'REG-12345',
            'merk' => 'Toyota',
            'tipe' => 'Avanza',
            'jenis' => 'Mobil',
            'stnk_ada' => 'Ada',
            'bpkb_ada' => 'Ada',
            'kondisi' => 'Baik',
            'status' => 'Tersedia',
            'opd' => 'BELUM DIKETAHUI',
            'pemegang' => '-'
        ]);

        // Reset shared state agar pendaftaran nomor register baru terbaca bersih dari database
        \App\Imports\VehicleImport::resetSharedState();

        // Buat objek VehicleImport secara manual
        $importer = new \App\Imports\VehicleImport(
            ['no_polisi' => 'Nomor Polisi', 'merk' => 'Merk', 'nomor_register' => 'Nomor Register'],
            ['Nomor Polisi', 'Merk', 'Nomor Register']
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Nomor register 'REG-12345' sudah digunakan oleh kendaraan lain di sistem atau terdapat duplikat pada berkas Excel. Proses impor dibatalkan.");

        $row = [
            0 => 'DN 2222 BB', // Nomor Polisi
            1 => 'Toyota Avanza', // Merk
            2 => 'REG-12345' // Nomor Register duplikat dengan database
        ];

        $importer->model($row);
    }

    /**
     * Test 3c: Mengetes kegagalan impor jika terdeteksi nomor register duplikat di baris Excel yang sama (in-memory).
     */
    public function test_import_throws_exception_on_duplicate_nomor_register_in_excel_rows()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        $this->actingAs($admin);

        // Buat objek VehicleImport secara manual
        $importer = new \App\Imports\VehicleImport(
            ['no_polisi' => 'Nomor Polisi', 'merk' => 'Merk', 'nomor_register' => 'Nomor Register'],
            ['Nomor Polisi', 'Merk', 'Nomor Register']
        );

        // Reset shared state
        \App\Imports\VehicleImport::resetSharedState();

        // Baris pertama (sukses)
        $row1 = [
            0 => 'DN 1111 AA',
            1 => 'Toyota Avanza',
            2 => 'REG-99999'
        ];
        $importer->model($row1);

        // Baris kedua (melempar exception karena nomor register REG-99999 duplikat in-memory)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Nomor register 'REG-99999' sudah digunakan oleh kendaraan lain di sistem atau terdapat duplikat pada berkas Excel. Proses impor dibatalkan.");

        $row2 = [
            0 => 'DN 2222 BB',
            1 => 'Honda Civic',
            2 => 'REG-99999'
        ];
        $importer->model($row2);
    }

    /**
     * Test 4: Mengetes normalisasi dokumen STNK/BPKB 'ada' -> 'Ada', 'tidak' -> 'Tidak'.
     */
    public function test_stnk_and_bpkb_document_status_normalization()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        $this->actingAs($admin);

        $importer = new \App\Imports\VehicleImport(
            [
                'no_polisi' => 'Nomor Polisi',
                'stnk_ada'  => 'STNK',
                'bpkb_ada'  => 'BPKB'
            ],
            ['Nomor Polisi', 'STNK', 'BPKB']
        );

        // Refleksikan fungsi helper privat normalizeDocumentStatus agar bisa ditest langsung
        $reflection = new \ReflectionClass($importer);
        $method = $reflection->getMethod('normalizeDocumentStatus');
        $method->setAccessible(true);

        // Kasus 1: Input Positif
        $this->assertEquals('Ada', $method->invoke($importer, 'ada'));
        $this->assertEquals('Ada', $method->invoke($importer, 'YA'));
        $this->assertEquals('Ada', $method->invoke($importer, 'y'));
        $this->assertEquals('Ada', $method->invoke($importer, 'yes'));
        $this->assertEquals('Ada', $method->invoke($importer, 'lengkap'));

        // Kasus 2: Input Negatif / Kosong
        $this->assertEquals('Tidak', $method->invoke($importer, 'tidak'));
        $this->assertEquals('Tidak', $method->invoke($importer, 'tdk'));
        $this->assertEquals('Tidak', $method->invoke($importer, 'no'));
        $this->assertEquals('Tidak', $method->invoke($importer, ''));
        $this->assertEquals('Tidak', $method->invoke($importer, null));
    }

    /**
     * Test 5: Menguji impor tradisional (Legacy) sukses membuat baris data kendaraan.
     */
    public function test_legacy_import_creates_vehicles_successfully()
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        // Buat file excel palsu
        $file = UploadedFile::fake()->create('vehicles_legacy.xlsx', 100);

        // Mocking Excel facade to intercept and verify
        Excel::shouldReceive('import')
            ->once()
            ->andReturn(true);

        $response = $this->actingAs($user)
            ->post(route('vehicles.import-legacy'), [
                'file' => $file
            ]);

        $response->assertRedirect(route('vehicles.index'));
        $response->assertSessionHas('success', 'Data kendaraan berhasil diimport menggunakan format template standar.');
    }

    /**
     * Test 6: Menguji eksekusi impor pintar (Smart Import) sukses total dengan token valid.
     */
    public function test_smart_import_succeeds_with_valid_token_and_cleans_up_temp_files()
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        // Buat file temporer palsu di disk
        $tempFilePath = 'temp_imports/mock_file.xlsx';
        Storage::disk('local')->put($tempFilePath, 'mock content');

        // Simpan token ke cache
        $token = 'import_valid_token_test_999';
        Cache::put($token, [
            'file_path'  => $tempFilePath,
            'user_id'    => $user->id,
            'expires_at' => now()->addMinutes(30)->timestamp
        ], 30);

        // Mocking Excel agar membiarkan import berjalan sukses
        Excel::shouldReceive('import')
            ->once()
            ->andReturn(true);

        $response = $this->actingAs($user)
            ->post(route('vehicles.import'), [
                'import_token'     => $token,
                'mapping'          => [
                    'no_polisi' => 'Nomor Polisi',
                    'merk'      => 'Merk'
                ],
                'headers'          => ['Nomor Polisi', 'Merk'],
                'header_row_index' => 0
            ]);

        $response->assertRedirect(route('vehicles.index'));
        $response->assertSessionHas('success', 'Data kendaraan berhasil diimport menggunakan AI Smart Import.');

        // Pastikan file temporer dan cache sesi dibersihkan dari server
        $this->assertFalse(Storage::disk('local')->exists($tempFilePath));
        $this->assertNull(Cache::get($token));
    }

    /**
     * Test 7: Menguji penolakan eksekusi jika token sesi dimiliki oleh pengguna lain (Anti-Hijack).
     */
    public function test_smart_import_fails_when_token_is_owned_by_another_user()
    {
        $userA = User::factory()->create(['role' => UserRole::ADMIN]);
        $userB = User::factory()->create(['role' => UserRole::ADMIN]);

        $tempFilePath = 'temp_imports/mock_file_hijack.xlsx';
        Storage::disk('local')->put($tempFilePath, 'mock content');

        $token = 'import_token_user_a';
        Cache::put($token, [
            'file_path'  => $tempFilePath,
            'user_id'    => $userA->id, // Milik User A
            'expires_at' => now()->addMinutes(30)->timestamp
        ], 30);

        // Coba dieksekusi oleh User B
        $response = $this->actingAs($userB)
            ->post(route('vehicles.import'), [
                'import_token'     => $token,
                'mapping'          => [
                    'no_polisi' => 'Nomor Polisi',
                ],
                'headers'          => ['Nomor Polisi'],
                'header_row_index' => 0
            ]);

        $response->assertRedirect(route('vehicles.index'));
        $response->assertSessionHas('error', 'Akses ditolak: Sesi impor ini milik pengguna lain.');

        // Pastikan berkas temporer tidak terhapus (karena bukan pemilik)
        $this->assertTrue(Storage::disk('local')->exists($tempFilePath));
        
        // Cleanup manual untuk test isolation
        Storage::disk('local')->delete($tempFilePath);
        Cache::forget($token);
    }

    /**
     * Test 8: Menguji penolakan validasi server-side jika pemetaan kolom Excel bernilai ganda (Duplicate Mapping).
     */
    public function test_smart_import_fails_validation_with_duplicate_mapping()
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('vehicles.import'), [
                'import_token'     => 'import_some_token',
                'mapping'          => [
                    'no_polisi' => 'Kolom Excel Sama',
                    'merk'      => 'Kolom Excel Sama' // Duplikat pemetaan ke kolom Excel yang sama
                ],
                'headers'          => ['Kolom Excel Sama'],
                'header_row_index' => 0
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['mapping']);
    }

    /**
     * Test 9: Menguji penolakan validasi server-side jika kolom identitas (no_polisi & merk) tidak dipetakan sama sekali.
     */
    public function test_smart_import_fails_validation_without_identity_mapping()
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('vehicles.import'), [
                'import_token'     => 'import_some_token',
                'mapping'          => [
                    'jenis' => 'Kolom Jenis', // Hanya memetakan jenis (tidak ada no_polisi / merk)
                ],
                'headers'          => ['Kolom Jenis'],
                'header_row_index' => 0
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['mapping']);
    }
}
