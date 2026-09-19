<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AsetTanah;
use App\Models\Bangunan;
use App\Models\IntegrationAuditLog;
use App\Models\Opd;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class EbmdReconciliationNibarTest extends TestCase
{
    protected function createExcelFile(array $rows, string $filename = 'test_ebmd.xlsx'): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                // Set as explicit string to preserve leading zeroes and large numeric strings
                $sheet->setCellValueExplicitByColumnAndRow($colIndex + 1, $rowIndex + 1, (string)$value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'rekon_test_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return new UploadedFile($tempPath, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    /**
     * 1. IDENTICAL NIBAR: NIBAR ditemukan dan seluruh field comparison yang dipilih identik.
     */
    public function test_01_identical_nibar(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $nibar = '00' . rand(10000000, 99999999);
        $tanah = AsetTanah::withoutEvents(fn() => AsetTanah::create([
            'kode_aset'         => $nibar,
            'nama_aset'         => 'Tanah Kantor Bappeda',
            'luas'              => '1200.00',
            'harga_perolehan'   => '500000000',
            'status_pencatatan' => 'TERCATAT_KIB_A',
        ]));

        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Luas M2', 'Harga Perolehan'],
            [$nibar, 'Tanah Kantor Bappeda', '1200', '500000000'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $importToken = $uploadRes->json('import_token');

        $diffRes = $this->actingAs($user)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['luas', 'harga_perolehan'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2, 'harga_perolehan' => 3],
            'header_row_index' => 0,
        ]);

        $diffRes->assertStatus(200);
        $this->assertEquals(1, $diffRes->json('total_ebmd'));
        $this->assertEquals(1, $diffRes->json('nibar_matched'));
        $this->assertEquals(1, $diffRes->json('identical_count'));
        $this->assertEquals(0, $diffRes->json('changed_count'));
        $this->assertEquals('IDENTICAL', $diffRes->json('items.0.status'));

        $tanah->delete();
    }

    /**
     * 2. CHANGED NIBAR: NIBAR ditemukan tetapi minimal satu field yang dipilih berbeda.
     */
    public function test_02_changed_nibar(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $nibar = '00' . rand(10000000, 99999999);
        $tanah = AsetTanah::withoutEvents(fn() => AsetTanah::create([
            'kode_aset'         => $nibar,
            'nama_aset'         => 'Tanah Kantor Bappeda',
            'luas'              => '1200.00',
            'harga_perolehan'   => '500000000',
            'status_pencatatan' => 'TERCATAT_KIB_A',
        ]));

        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Luas M2', 'Harga Perolehan'],
            [$nibar, 'Tanah Kantor Bappeda', '1500', '500000000'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $importToken = $uploadRes->json('import_token');

        $diffRes = $this->actingAs($user)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2, 'harga_perolehan' => 3],
            'header_row_index' => 0,
        ]);

        $diffRes->assertStatus(200);
        $this->assertEquals(1, $diffRes->json('nibar_matched'));
        $this->assertEquals(1, $diffRes->json('changed_count'));
        $this->assertEquals(0, $diffRes->json('identical_count'));
        $this->assertEquals('CHANGED', $diffRes->json('items.0.status'));
        $this->assertCount(1, $diffRes->json('items.0.changed_columns'));

        $tanah->delete();
    }

    /**
     * 3. NIBAR NOT FOUND (ONLY_IN_EBMD): NIBAR ada di e-BMD tetapi tidak ada di database SIPAT.
     */
    public function test_03_nibar_not_found_in_sipat(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $nibarBaru = '00' . rand(10000000, 99999999);

        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Luas M2'],
            [$nibarBaru, 'Tanah Lapangan Baru', '3000'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $importToken = $uploadRes->json('import_token');

        $diffRes = $this->actingAs($user)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2],
            'header_row_index' => 0,
        ]);

        $diffRes->assertStatus(200);
        $this->assertEquals(0, $diffRes->json('nibar_matched'));
        $this->assertEquals(1, $diffRes->json('not_found_sipat'));
        $this->assertEquals('ONLY_IN_EBMD', $diffRes->json('items.0.status'));
    }

    /**
     * 4. BLANK / INVALID NIBAR: Baris Excel tanpa NIBAR valid dilaporkan INVALID dan dilewati.
     */
    public function test_04_blank_or_invalid_nibar(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Luas M2'],
            ['', 'Tanah Blank', '500'],
            ['-', 'Tanah Dash', '600'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $importToken = $uploadRes->json('import_token');

        $diffRes = $this->actingAs($user)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2],
            'header_row_index' => 0,
        ]);

        $diffRes->assertStatus(200);
        $this->assertEquals(2, $diffRes->json('invalid_count'));
        $this->assertEquals('INVALID', $diffRes->json('items.0.status'));
        $this->assertEquals('INVALID', $diffRes->json('items.1.status'));
    }

    /**
     * 5. DUPLICATE NIBAR IN E-BMD: Jika NIBAR muncul > 1 kali di berkas Excel, tidak boleh update otomatis.
     */
    public function test_05_duplicate_nibar_in_ebmd(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $nibar = '00' . rand(10000000, 99999999);
        $tanah = AsetTanah::withoutEvents(fn() => AsetTanah::create([
            'kode_aset'         => $nibar,
            'nama_aset'         => 'Tanah Asli',
            'luas'              => '1000.00',
            'status_pencatatan' => 'TERCATAT_KIB_A',
        ]));

        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Luas M2'],
            [$nibar, 'Tanah Duplikat 1', '1200'],
            [$nibar, 'Tanah Duplikat 2', '1300'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $importToken = $uploadRes->json('import_token');

        $diffRes = $this->actingAs($user)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2],
            'header_row_index' => 0,
        ]);

        $diffRes->assertStatus(200);
        $this->assertEquals(2, $diffRes->json('duplicate_count'));
        $this->assertEquals('DUPLICATE_KEY', $diffRes->json('items.0.status'));
        $this->assertEquals('DUPLICATE_KEY', $diffRes->json('items.1.status'));

        // Execute: database TIDAK boleh berubah karena duplikat
        $this->actingAs($user)->postJson('/rekon-ebmd/execute', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2],
            'create_new'       => false,
            'header_row_index' => 0,
        ]);

        $tanahRefreshed = AsetTanah::withoutGlobalScopes()->find($tanah->id_aset);
        $this->assertEquals('1000.00', $tanahRefreshed->luas);

        $tanah->delete();
    }

    /**
     * 6. DUPLICATE NIBAR IN SIPAT: Jika NIBAR terdaftar > 1 kali di database SIPAT, tidak boleh update otomatis.
     */
    public function test_06_duplicate_nibar_in_sipat(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $nibar = 'BGN-' . rand(10000000, 99999999);
        $b1 = \App\Models\Bangunan::withoutEvents(fn() => \App\Models\Bangunan::create([
            'kode_bangunan'   => $nibar,
            'nama_bangunan'   => 'Gedung A1',
            'luas_lantai'     => '500.00',
            'harga_perolehan' => '300000000',
        ]));
        $b2 = \App\Models\Bangunan::withoutEvents(fn() => \App\Models\Bangunan::create([
            'kode_bangunan'   => $nibar,
            'nama_bangunan'   => 'Gedung A2',
            'luas_lantai'     => '600.00',
            'harga_perolehan' => '400000000',
        ]));

        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Bangunan', 'Luas Lantai'],
            [$nibar, 'Gedung e-BMD Baru', '750'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'bangunan',
        ]);
        $importToken = $uploadRes->json('import_token');

        $diffRes = $this->actingAs($user)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $importToken,
            'asset_category'   => 'bangunan',
            'selected_columns' => ['luas_lantai'],
            'mapping'          => ['nibar' => 0, 'nama_bangunan' => 1, 'luas_lantai' => 2],
            'header_row_index' => 0,
        ]);

        $diffRes->assertStatus(200);
        $this->assertEquals(1, $diffRes->json('duplicate_count'));
        $this->assertEquals('DUPLICATE_KEY', $diffRes->json('items.0.status'));

        // Execute: tidak boleh mengupdate salah satu secara acak
        $this->actingAs($user)->postJson('/rekon-ebmd/execute', [
            'import_token'     => $importToken,
            'asset_category'   => 'bangunan',
            'selected_columns' => ['luas_lantai'],
            'mapping'          => ['nibar' => 0, 'nama_bangunan' => 1, 'luas_lantai' => 2],
            'create_new'       => false,
            'header_row_index' => 0,
        ]);

        $this->assertEquals('500.00', \App\Models\Bangunan::withoutGlobalScopes()->find($b1->id)->luas_lantai);
        $this->assertEquals('600.00', \App\Models\Bangunan::withoutGlobalScopes()->find($b2->id)->luas_lantai);

        $b1->delete();
        $b2->delete();
    }

    /**
     * 7. NULL PROTECTION: Nilai kosong/null/"  " dari e-BMD TIDAK BOLEH menghapus/mengosongkan data SIPAT yang sudah ada.
     */
    public function test_07_null_protection_preserves_sipat_data(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $nibar = '00' . rand(10000000, 99999999);
        $tanah = AsetTanah::withoutEvents(fn() => AsetTanah::create([
            'kode_aset'         => $nibar,
            'nama_aset'         => 'Tanah Asli',
            'alamat'            => 'Jl. Pahlawan No. 123',
            'dasar_perolehan'   => 'Sertifikat Hak Pakai No. 45',
            'status_pencatatan' => 'TERCATAT_KIB_A',
        ]));

        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Alamat', 'Dasar Perolehan'],
            [$nibar, 'Tanah Asli', '', '   '],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $importToken = $uploadRes->json('import_token');

        $diffRes = $this->actingAs($user)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['alamat', 'dasar_perolehan'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'alamat' => 2, 'dasar_perolehan' => 3],
            'header_row_index' => 0,
        ]);

        $diffRes->assertStatus(200);
        $this->assertEquals(1, $diffRes->json('identical_count'));
        $this->assertEquals(0, $diffRes->json('changed_count'));

        // Eksekusi: Data SIPAT tetap utuh
        $this->actingAs($user)->postJson('/rekon-ebmd/execute', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['alamat', 'dasar_perolehan'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'alamat' => 2, 'dasar_perolehan' => 3],
            'create_new'       => false,
            'header_row_index' => 0,
        ]);

        $tanahRefreshed = AsetTanah::withoutGlobalScopes()->find($tanah->id_aset);
        $this->assertEquals('Jl. Pahlawan No. 123', $tanahRefreshed->alamat);
        $this->assertEquals('Sertifikat Hak Pakai No. 45', $tanahRefreshed->dasar_perolehan);

        $tanah->delete();
    }

    /**
     * 8. SELECTIVE COLUMN UPDATE: Hanya kolom yang dicentang yang diperbarui; kolom lain TIDAK tersentuh.
     */
    public function test_08_selective_column_update(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $nibar = '00' . rand(10000000, 99999999);
        $tanah = AsetTanah::withoutEvents(fn() => AsetTanah::create([
            'kode_aset'         => $nibar,
            'nama_aset'         => 'Nama SIPAT Lama',
            'luas'              => '1000.00',
            'alamat'            => 'Alamat SIPAT Asli',
            'harga_perolehan'   => '500000000',
            'status_pencatatan' => 'TERCATAT_KIB_A',
        ]));

        // File e-BMD memiliki perubahan di luas (1200), alamat (Alamat Baru), harga (600000000)
        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Luas M2', 'Alamat', 'Harga Perolehan'],
            [$nibar, 'Nama e-BMD Baru', '1200', 'Alamat e-BMD Baru', '600000000'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $importToken = $uploadRes->json('import_token');

        // User HANYA memilih kolom 'luas'
        $execRes = $this->actingAs($user)->postJson('/rekon-ebmd/execute', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2, 'alamat' => 3, 'harga_perolehan' => 4],
            'create_new'       => false,
            'header_row_index' => 0,
        ]);
        $execRes->assertStatus(200);

        $tanahRefreshed = AsetTanah::withoutGlobalScopes()->find($tanah->id_aset);
        // Luas berubah ke 1200
        $this->assertEquals('1200.00', $tanahRefreshed->luas);
        // Nama, Alamat, dan Harga TETAP tidak berubah
        $this->assertEquals('Nama SIPAT Lama', $tanahRefreshed->nama_aset);
        $this->assertEquals('Alamat SIPAT Asli', $tanahRefreshed->alamat);
        $this->assertEquals('500000000.00', $tanahRefreshed->harga_perolehan);

        $tanah->delete();
    }

    /**
     * 9. ONLY_IN_SIPAT: Aset di SIPAT yang tidak ada di e-BMD terdeteksi & TIDAK BOLEH dihapus.
     */
    public function test_09_only_in_sipat_detected_and_no_deletion(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $nibarInEbmd = '00' . rand(10000000, 99999999);
        $nibarOnlySipat = '00' . rand(10000000, 99999999);

        $tanah1 = AsetTanah::withoutEvents(fn() => AsetTanah::create([
            'kode_aset'         => $nibarInEbmd,
            'nama_aset'         => 'Tanah Bersama',
            'luas'              => '1000.00',
            'status_pencatatan' => 'TERCATAT_KIB_A',
        ]));
        $tanah2 = AsetTanah::withoutEvents(fn() => AsetTanah::create([
            'kode_aset'         => $nibarOnlySipat,
            'nama_aset'         => 'Tanah Hanya di SIPAT',
            'luas'              => '1500.00',
            'status_pencatatan' => 'TERCATAT_KIB_A',
        ]));

        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Luas M2'],
            [$nibarInEbmd, 'Tanah Bersama', '1000'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $importToken = $uploadRes->json('import_token');

        $diffRes = $this->actingAs($user)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['nama_aset'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2],
            'header_row_index' => 0,
        ]);

        $diffRes->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $diffRes->json('only_in_sipat'));

        // Pastikan tanah2 masih ADA di database (tidak terhapus)
        $this->assertDatabaseHas('aset_tanah', ['kode_aset' => $nibarOnlySipat]);

        $tanah1->delete();
        $tanah2->delete();
    }

    /**
     * 10. TRANSACTION ROLLBACK: Jika terjadi kegagalan di tengah eksekusi, seluruh transaksi di-rollback secara atomic.
     */
    public function test_10_transaction_rollback_on_failure(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $nibar = '00' . rand(10000000, 99999999);
        $tanah = AsetTanah::withoutEvents(fn() => AsetTanah::create([
            'kode_aset'         => $nibar,
            'nama_aset'         => 'Tanah Asli',
            'luas'              => '1000.00',
            'status_pencatatan' => 'TERCATAT_KIB_A',
        ]));

        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Luas M2'],
            [$nibar, 'Tanah Asli', '2000'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $importToken = $uploadRes->json('import_token');

        // Simulasikan force error dengan melempar Exception pada model event saving
        \App\Models\AsetTanah::saving(function ($model) {
            if ($model->luas == 2000) {
                throw new \RuntimeException('Simulated Database Error During Execution');
            }
        });

        $execRes = $this->actingAs($user)->postJson('/rekon-ebmd/execute', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2],
            'create_new'       => false,
            'header_row_index' => 0,
        ]);

        $execRes->assertStatus(500);

        // Bersihkan event listener
        \App\Models\AsetTanah::flushEventListeners();

        // Verifikasi data di database TIDAK berubah (kembali ke nilai awal)
        $tanahRefreshed = AsetTanah::withoutGlobalScopes()->find($tanah->id_aset);
        $this->assertEquals('1000.00', $tanahRefreshed->luas);

        $tanah->delete();
    }

    /**
     * 11. AUDIT LOG: Menyimpan batch ID (correlation_id), NIBAR, changes, reason, user, dan timestamp.
     */
    public function test_11_audit_log_records_batch_nibar_and_changes(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $nibar = '00' . rand(10000000, 99999999);
        $tanah = AsetTanah::withoutEvents(fn() => AsetTanah::create([
            'kode_aset'         => $nibar,
            'nama_aset'         => 'Tanah Kantor',
            'luas'              => '1000.00',
            'status_pencatatan' => 'TERCATAT_KIB_A',
        ]));

        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Luas M2'],
            [$nibar, 'Tanah Kantor', '1250'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $importToken = $uploadRes->json('import_token');

        $execRes = $this->actingAs($user)->postJson('/rekon-ebmd/execute', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2],
            'create_new'       => false,
            'header_row_index' => 0,
        ]);
        $execRes->assertStatus(200);

        $correlationId = $execRes->json('correlation_id');
        $this->assertNotEmpty($correlationId);

        // Verifikasi audit log di database
        $auditLog = IntegrationAuditLog::where('correlation_id', $correlationId)
            ->where('nibar', $nibar)
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals('EBMD_RECONCILIATION_UPDATE', $auditLog->event_name);
        $this->assertEquals('SUCCESS', $auditLog->sync_status);
        $this->assertEquals('EBMD', $auditLog->source_system);
        $this->assertNotNull($auditLog->created_at);

        $tanah->delete();
        $auditLog->delete();
    }

    /**
     * 12. EXPORT: Menghasilkan CSV dengan format UTF-8 BOM yang memuat hasil rekonsiliasi aktual.
     */
    public function test_12_export_preview_csv_format(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $nibar = '00' . rand(10000000, 99999999);
        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Luas M2'],
            [$nibar, 'Tanah Ekspor Test', '1000'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $importToken = $uploadRes->json('import_token');

        $this->actingAs($user)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['nama_aset'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2],
            'header_row_index' => 0,
        ]);

        $exportRes = $this->actingAs($user)->post('/rekon-ebmd/export-preview', [
            'import_token' => $importToken,
        ]);

        $exportRes->assertStatus(200);
        $this->assertTrue(str_contains($exportRes->headers->get('content-type'), 'text/csv'));
        $this->assertTrue(str_contains($exportRes->headers->get('content-disposition'), 'attachment; filename=Hasil_Rekonsiliasi_eBMD_tanah_'));
    }

    /**
     * 13. AUTHORIZATION: User tanpa login atau role tidak berhak diblokir dari endpoint execute & export.
     */
    public function test_13_authorization_and_permission(): void
    {
        // Unauthenticated access -> dialihkan atau 401
        $resUnauth = $this->postJson('/rekon-ebmd/execute', [
            'import_token'     => 'fake_token',
            'asset_category'   => 'tanah',
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0],
        ]);
        $this->assertTrue(in_array($resUnauth->status(), [401, 302]));

        // Unauthorized user role (e.g. opd / normal user) -> dialihkan 302 oleh CheckRole middleware
        $normalUser = User::factory()->create(['role' => 'opd']);
        $resForbidden = $this->actingAs($normalUser)->postJson('/rekon-ebmd/execute', [
            'import_token'     => 'fake_token',
            'asset_category'   => 'tanah',
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0],
        ]);
        $this->assertTrue(in_array($resForbidden->status(), [403, 302]));

        // User lain mengakses token impor milik user berbeda -> 403 Forbidden
        $super1 = User::factory()->create(['role' => UserRole::ADMIN]);
        $super2 = User::factory()->create(['role' => UserRole::ADMIN]);

        Storage::disk('local')->put('fake_path.xlsx', 'dummy content');
        $tokenOther = 'token_test_' . rand(1000, 9999);
        Cache::put($tokenOther, [
            'file_path'  => 'fake_path.xlsx',
            'user_id'    => $super1->id,
            'expires_at' => now()->addMinutes(30)->timestamp,
        ]);

        $resTokenAuth = $this->actingAs($super2)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $tokenOther,
            'asset_category'   => 'tanah',
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0],
        ]);
        $resTokenAuth->assertStatus(403);
    }

    /**
     * 14. LEADING ZERO PRESERVATION: NIBAR '001234' tidak kehilangan leading zero.
     */
    public function test_14_nibar_preserves_leading_zero(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $nibarWithZeros = '0001234567';
        $tanah = AsetTanah::withoutEvents(fn() => AsetTanah::create([
            'kode_aset'         => $nibarWithZeros,
            'nama_aset'         => 'Tanah Leading Zero',
            'luas'              => '1000.00',
            'status_pencatatan' => 'TERCATAT_KIB_A',
        ]));

        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Luas M2'],
            [$nibarWithZeros, 'Tanah Leading Zero', '1100'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $importToken = $uploadRes->json('import_token');

        $diffRes = $this->actingAs($user)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2],
            'header_row_index' => 0,
        ]);

        $diffRes->assertStatus(200);
        $this->assertEquals(1, $diffRes->json('nibar_matched'));
        $this->assertEquals('0001234567', $diffRes->json('items.0.nibar'));

        $this->actingAs($user)->postJson('/rekon-ebmd/execute', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2],
            'create_new'       => false,
            'header_row_index' => 0,
        ]);

        $tanahRefreshed = AsetTanah::withoutGlobalScopes()->find($tanah->id_aset);
        $this->assertEquals('0001234567', $tanahRefreshed->kode_aset);
        $this->assertEquals('1100.00', $tanahRefreshed->luas);

        $tanah->delete();
    }

    /**
     * 15. NIBAR AS STRING: NIBAR panjang (misal 24 digit) diperlakukan sebagai string murni tanpa kehilangan presisi.
     */
    public function test_15_nibar_strict_string_precision(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $longNibar = '112233445566778899001122';
        $tanah = AsetTanah::withoutEvents(fn() => AsetTanah::create([
            'kode_aset'         => $longNibar,
            'nama_aset'         => 'Tanah Long NIBAR',
            'luas'              => '1000.00',
            'status_pencatatan' => 'TERCATAT_KIB_A',
        ]));

        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Luas M2'],
            [$longNibar, 'Tanah Long NIBAR', '1500'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $importToken = $uploadRes->json('import_token');

        $diffRes = $this->actingAs($user)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2],
            'header_row_index' => 0,
        ]);

        $diffRes->assertStatus(200);
        $this->assertEquals(1, $diffRes->json('nibar_matched'));
        $this->assertEquals($longNibar, $diffRes->json('items.0.nibar'));

        $tanah->delete();
    }

    /**
     * 16. NORMALIZATION COMPARISON: Whitespace dan case-insensitive comparison dinormalisasi dengan benar.
     */
    public function test_16_normalization_comparison(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $nibar = '00' . rand(10000000, 99999999);
        $tanah = AsetTanah::withoutEvents(fn() => AsetTanah::create([
            'kode_aset'         => $nibar,
            'nama_aset'         => 'Tanah Kantor Bappeda',
            'alamat'            => 'Jl.   Merdeka   No. 1',
            'status_pencatatan' => 'TERCATAT_KIB_A',
        ]));

        // e-BMD memiliki perbedaan spasi/huruf kapital minor yang secara semantik identik
        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Alamat'],
            [$nibar, 'TANAH KANTOR BAPPEDA', 'Jl. Merdeka No. 1'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $importToken = $uploadRes->json('import_token');

        $diffRes = $this->actingAs($user)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['nama_aset', 'alamat'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'alamat' => 2],
            'header_row_index' => 0,
        ]);

        $diffRes->assertStatus(200);
        $this->assertEquals(1, $diffRes->json('identical_count'));
        $this->assertEquals(0, $diffRes->json('changed_count'));

        $tanah->delete();
    }

    /**
     * 17. OPD SCOPE IDENTICAL: NIBAR ada di OPD A dan seluruh kolom terpilih identik -> IDENTICAL.
     */
    public function test_17_opd_scope_nibar_in_active_opd_identical(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $opdA = Opd::create(['nama' => 'Dinas Pendidikan ' . rand(10000, 99999)]);

        $nibar = '00' . rand(10000000, 99999999);
        $tanah = AsetTanah::withoutEvents(fn() => AsetTanah::create([
            'kode_aset'         => $nibar,
            'nama_aset'         => 'Tanah SDN 1',
            'luas'              => '1000.00',
            'opd_id'            => $opdA->id,
            'status_pencatatan' => 'TERCATAT_KIB_A',
        ]));

        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Luas M2'],
            [$nibar, 'Tanah SDN 1', '1000'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $importToken = $uploadRes->json('import_token');

        $diffRes = $this->actingAs($user)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'opd_id'           => $opdA->id,
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2],
            'header_row_index' => 0,
        ]);

        $diffRes->assertStatus(200);
        $this->assertEquals(1, $diffRes->json('identical_count'));
        $this->assertEquals(0, $diffRes->json('changed_count'));
        $this->assertEquals('IDENTICAL', $diffRes->json('items.0.status'));

        $tanah->delete();
        $opdA->delete();
    }

    /**
     * 18. OPD SCOPE CHANGED: NIBAR ada di OPD A dan memiliki perubahan data -> CHANGED.
     */
    public function test_18_opd_scope_nibar_in_active_opd_changed(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $opdA = Opd::create(['nama' => 'Dinas Pendidikan ' . rand(10000, 99999)]);

        $nibar = '00' . rand(10000000, 99999999);
        $tanah = AsetTanah::withoutEvents(fn() => AsetTanah::create([
            'kode_aset'         => $nibar,
            'nama_aset'         => 'Tanah SDN 1',
            'luas'              => '1000.00',
            'opd_id'            => $opdA->id,
            'status_pencatatan' => 'TERCATAT_KIB_A',
        ]));

        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Luas M2'],
            [$nibar, 'Tanah SDN 1', '1500'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $importToken = $uploadRes->json('import_token');

        $diffRes = $this->actingAs($user)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'opd_id'           => $opdA->id,
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2],
            'header_row_index' => 0,
        ]);

        $diffRes->assertStatus(200);
        $this->assertEquals(1, $diffRes->json('changed_count'));
        $this->assertEquals(0, $diffRes->json('identical_count'));
        $this->assertEquals('CHANGED', $diffRes->json('items.0.status'));

        $tanah->delete();
        $opdA->delete();
    }

    /**
     * 19. OPD SCOPE EXISTS OTHER OPD: NIBAR tidak di OPD A, tetapi ada di OPD B -> EXISTS_OTHER_OPD.
     */
    public function test_19_opd_scope_nibar_not_in_active_opd_but_in_other_opd_exists_other_opd(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $opdA = Opd::create(['nama' => 'Dinas Pendidikan ' . rand(10000, 99999)]);
        $opdB = Opd::create(['nama' => 'Dinas Kesehatan ' . rand(10000, 99999)]);

        $nibar = '00' . rand(10000000, 99999999);
        $tanahB = AsetTanah::withoutEvents(fn() => AsetTanah::create([
            'kode_aset'         => $nibar,
            'nama_aset'         => 'Puskesmas Rawat Inap',
            'luas'              => '2000.00',
            'opd_id'            => $opdB->id,
            'status_pencatatan' => 'TERCATAT_KIB_A',
        ]));

        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Luas M2'],
            [$nibar, 'Puskesmas Rawat Inap', '2000'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $importToken = $uploadRes->json('import_token');

        // Rekonsiliasi dengan target OPD A
        $diffRes = $this->actingAs($user)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'opd_id'           => $opdA->id,
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2],
            'header_row_index' => 0,
        ]);

        $diffRes->assertStatus(200);
        $this->assertEquals(0, $diffRes->json('nibar_matched'));
        $this->assertEquals(1, $diffRes->json('other_opd_count'));
        $this->assertEquals(0, $diffRes->json('not_found_sipat'));
        $this->assertEquals('EXISTS_OTHER_OPD', $diffRes->json('items.0.status'));
        $this->assertStringContainsString('Dinas Kesehatan', $diffRes->json('items.0.opd'));

        $tanahB->delete();
        $opdA->delete();
        $opdB->delete();
    }

    /**
     * 20. OPD SCOPE ONLY IN EBMD: NIBAR tidak ada di OPD A dan tidak ada di seluruh SIPAT -> ONLY_IN_EBMD.
     */
    public function test_20_opd_scope_nibar_not_in_active_opd_and_not_in_sipat_only_in_ebmd(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $opdA = Opd::create(['nama' => 'Dinas Pendidikan ' . rand(10000, 99999)]);

        $nibarGhaib = '00' . rand(10000000, 99999999);

        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Luas M2'],
            [$nibarGhaib, 'Tanah Tak Bertuan', '500'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $importToken = $uploadRes->json('import_token');

        $diffRes = $this->actingAs($user)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'opd_id'           => $opdA->id,
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2],
            'header_row_index' => 0,
        ]);

        $diffRes->assertStatus(200);
        $this->assertEquals(0, $diffRes->json('nibar_matched'));
        $this->assertEquals(0, $diffRes->json('other_opd_count'));
        $this->assertEquals(1, $diffRes->json('not_found_sipat'));
        $this->assertEquals('ONLY_IN_EBMD', $diffRes->json('items.0.status'));

        $opdA->delete();
    }

    /**
     * 21. OPD SCOPE GLOBAL CONFLICT: NIBAR ditemukan di > 1 OPD lain di SIPAT -> GLOBAL_NIBAR_CONFLICT.
     */
    public function test_21_opd_scope_nibar_in_multiple_other_opds_global_conflict(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $opdA = Opd::create(['nama' => 'Dinas Pendidikan ' . rand(10000, 99999)]);
        $opdB = Opd::create(['nama' => 'Dinas Kesehatan ' . rand(10000, 99999)]);
        $opdC = Opd::create(['nama' => 'Dinas Pekerjaan Umum ' . rand(10000, 99999)]);

        $nibar = '00' . rand(10000000, 99999999);

        // Gunakan model Bangunan yang tidak memiliki UNIQUE constraint pada kode_bangunan di level database
        $bgnB = Bangunan::withoutEvents(fn() => Bangunan::create([
            'kode_bangunan'     => $nibar,
            'nama_bangunan'     => 'Gedung Lab',
            'opd_id'            => $opdB->id,
            'status_pencatatan' => 'TERCATAT_KIB_C',
        ]));
        $bgnC = Bangunan::withoutEvents(fn() => Bangunan::create([
            'kode_bangunan'     => $nibar,
            'nama_bangunan'     => 'Gedung Workshop',
            'opd_id'            => $opdC->id,
            'status_pencatatan' => 'TERCATAT_KIB_C',
        ]));

        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Gedung', 'Kondisi'],
            [$nibar, 'Gedung Bersama', 'Baik'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'bangunan',
        ]);
        $importToken = $uploadRes->json('import_token');

        $diffRes = $this->actingAs($user)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $importToken,
            'asset_category'   => 'bangunan',
            'opd_id'           => $opdA->id,
            'selected_columns' => ['nama_bangunan'],
            'mapping'          => ['nibar' => 0, 'nama_bangunan' => 1],
            'header_row_index' => 0,
        ]);

        $diffRes->assertStatus(200);
        $this->assertEquals(0, $diffRes->json('nibar_matched'));
        $this->assertEquals(0, $diffRes->json('other_opd_count'));
        $this->assertEquals(1, $diffRes->json('conflict_count'));
        $this->assertEquals('GLOBAL_NIBAR_CONFLICT', $diffRes->json('items.0.status'));

        $bgnB->delete();
        $bgnC->delete();
        $opdA->delete();
        $opdB->delete();
        $opdC->delete();
    }

    /**
     * 22. SAFETY: EXISTS_OTHER_OPD tidak boleh menyebabkan UPDATE terhadap aset OPD lain.
     */
    public function test_22_opd_scope_exists_other_opd_does_not_update_asset(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $opdA = Opd::create(['nama' => 'Dinas Pendidikan ' . rand(10000, 99999)]);
        $opdB = Opd::create(['nama' => 'Dinas Kesehatan ' . rand(10000, 99999)]);

        $nibar = '00' . rand(10000000, 99999999);
        $tanahB = AsetTanah::withoutEvents(fn() => AsetTanah::create([
            'kode_aset'         => $nibar,
            'nama_aset'         => 'Puskesmas Asli',
            'luas'              => '1000.00',
            'opd_id'            => $opdB->id,
            'status_pencatatan' => 'TERCATAT_KIB_A',
        ]));

        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Luas M2'],
            [$nibar, 'Puskesmas Diubah', '9999'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $importToken = $uploadRes->json('import_token');

        // Eksekusi rekonsiliasi pada OPD A
        $execRes = $this->actingAs($user)->postJson('/rekon-ebmd/execute', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'opd_id'           => $opdA->id,
            'selected_columns' => ['luas', 'nama_aset'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2],
            'create_new'       => false,
            'header_row_index' => 0,
        ]);

        $execRes->assertStatus(200);
        $this->assertEquals(0, $execRes->json('updated_count'));
        $this->assertEquals(1, $execRes->json('skipped_count'));

        // Pastikan tanah milik OPD B sama sekali TIDAK berubah
        $tanahRefreshed = AsetTanah::withoutGlobalScopes()->find($tanahB->id_aset);
        $this->assertEquals('1000.00', $tanahRefreshed->luas);
        $this->assertEquals('Puskesmas Asli', $tanahRefreshed->nama_aset);
        $this->assertEquals($opdB->id, $tanahRefreshed->opd_id);

        $tanahB->delete();
        $opdA->delete();
        $opdB->delete();
    }

    /**
     * 23. SAFETY: EXISTS_OTHER_OPD tidak boleh di-INSERT sebagai aset baru ke OPD A meski create_new diaktifkan.
     */
    public function test_23_opd_scope_exists_other_opd_does_not_insert_asset_even_if_create_new_true(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $opdA = Opd::create(['nama' => 'Dinas Pendidikan ' . rand(10000, 99999)]);
        $opdB = Opd::create(['nama' => 'Dinas Kesehatan ' . rand(10000, 99999)]);

        $nibar = '00' . rand(10000000, 99999999);
        $tanahB = AsetTanah::withoutEvents(fn() => AsetTanah::create([
            'kode_aset'         => $nibar,
            'nama_aset'         => 'Puskesmas Asli',
            'luas'              => '1000.00',
            'opd_id'            => $opdB->id,
            'status_pencatatan' => 'TERCATAT_KIB_A',
        ]));

        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Luas M2'],
            [$nibar, 'Puskesmas Asli', '1000'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $importToken = $uploadRes->json('import_token');

        // Eksekusi rekonsiliasi pada OPD A dengan create_new = true
        $execRes = $this->actingAs($user)->postJson('/rekon-ebmd/execute', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'opd_id'           => $opdA->id,
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2],
            'create_new'       => true,
            'header_row_index' => 0,
        ]);

        $execRes->assertStatus(200);
        $this->assertEquals(0, $execRes->json('inserted_count'));
        $this->assertEquals(1, $execRes->json('skipped_count'));

        // Pastikan tidak ada data baru yang dibuat di OPD A dengan NIBAR tersebut
        $countTanahA = AsetTanah::withoutGlobalScopes()->where('kode_aset', $nibar)->where('opd_id', $opdA->id)->count();
        $this->assertEquals(0, $countTanahA);

        $tanahB->delete();
        $opdA->delete();
        $opdB->delete();
    }

    /**
     * 24. SAFETY: GLOBAL_NIBAR_CONFLICT tidak boleh di-UPDATE maupun di-INSERT.
     */
    public function test_24_opd_scope_global_conflict_does_not_update_or_insert(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $opdA = Opd::create(['nama' => 'Dinas Pendidikan ' . rand(10000, 99999)]);
        $opdB = Opd::create(['nama' => 'Dinas Kesehatan ' . rand(10000, 99999)]);
        $opdC = Opd::create(['nama' => 'Dinas Pekerjaan Umum ' . rand(10000, 99999)]);

        $nibar = '00' . rand(10000000, 99999999);

        $bgnB = Bangunan::withoutEvents(fn() => Bangunan::create([
            'kode_bangunan'     => $nibar,
            'nama_bangunan'     => 'Gedung B Origin',
            'opd_id'            => $opdB->id,
            'status_pencatatan' => 'TERCATAT_KIB_C',
        ]));
        $bgnC = Bangunan::withoutEvents(fn() => Bangunan::create([
            'kode_bangunan'     => $nibar,
            'nama_bangunan'     => 'Gedung C Origin',
            'opd_id'            => $opdC->id,
            'status_pencatatan' => 'TERCATAT_KIB_C',
        ]));

        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Gedung', 'Kondisi'],
            [$nibar, 'Gedung Diubah', 'Baik'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'bangunan',
        ]);
        $importToken = $uploadRes->json('import_token');

        $execRes = $this->actingAs($user)->postJson('/rekon-ebmd/execute', [
            'import_token'     => $importToken,
            'asset_category'   => 'bangunan',
            'opd_id'           => $opdA->id,
            'selected_columns' => ['nama_bangunan'],
            'mapping'          => ['nibar' => 0, 'nama_bangunan' => 1, 'kondisi' => 2],
            'create_new'       => true,
            'header_row_index' => 0,
        ]);

        $execRes->assertStatus(200);
        $this->assertEquals(0, $execRes->json('updated_count'));
        $this->assertEquals(0, $execRes->json('inserted_count'));
        $this->assertEquals(1, $execRes->json('skipped_count'));

        $this->assertEquals('Gedung B Origin', $bgnB->fresh()->nama_bangunan);
        $this->assertEquals('Gedung C Origin', $bgnC->fresh()->nama_bangunan);
        $this->assertEquals(0, Bangunan::withoutGlobalScopes()->where('kode_bangunan', $nibar)->where('opd_id', $opdA->id)->count());

        $bgnB->delete();
        $bgnC->delete();
        $opdA->delete();
        $opdB->delete();
        $opdC->delete();
    }

    /**
     * 25. ADJUST OPD: Menyesuaikan OPD memindahkan aset ke OPD aktif dan memungkinkan eksekusi rekonsiliasi berikutnya.
     */
    public function test_25_adjust_opd_transfers_asset_to_active_opd_and_allows_reconciliation(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $opdA = Opd::create(['nama' => 'Dinas Pendidikan ' . rand(10000, 99999)]);
        $opdB = Opd::create(['nama' => 'Dinas Kesehatan ' . rand(10000, 99999)]);

        $nibar = '00' . rand(10000000, 99999999);
        $tanahB = AsetTanah::withoutEvents(fn() => AsetTanah::create([
            'kode_aset'         => $nibar,
            'nama_aset'         => 'Puskesmas Lama',
            'luas'              => '1000.00',
            'opd_id'            => $opdB->id,
            'status_pencatatan' => 'TERCATAT_KIB_A',
        ]));

        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Luas M2'],
            [$nibar, 'Puskesmas Baru', '1500'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $importToken = $uploadRes->json('import_token');

        // 1. Diff Preview Awal -> Terdeteksi EXISTS_OTHER_OPD
        $diffRes1 = $this->actingAs($user)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'opd_id'           => $opdA->id,
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2],
            'header_row_index' => 0,
        ]);

        $diffRes1->assertStatus(200);
        $this->assertEquals(1, $diffRes1->json('other_opd_count'));
        $this->assertEquals(0, $diffRes1->json('nibar_matched'));
        $this->assertEquals('EXISTS_OTHER_OPD', $diffRes1->json('items.0.status'));

        // 2. Eksekusi Sesuaikan OPD
        $adjustRes = $this->actingAs($user)->postJson('/rekon-ebmd/adjust-opd', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'opd_id'           => $opdA->id,
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2],
            'header_row_index' => 0,
        ]);

        $adjustRes->assertStatus(200);
        $this->assertTrue($adjustRes->json('success'));
        $this->assertEquals(1, $adjustRes->json('adjusted_count'));

        // Pastikan aset telah berpindah ke OPD A di database
        $tanahRefreshed = AsetTanah::withoutGlobalScopes()->find($tanahB->id_aset);
        $this->assertEquals($opdA->id, $tanahRefreshed->opd_id);

        // 3. Diff Preview Ulang -> Aset sekarang masuk ke OPD A dan berstatus CHANGED
        $diffRes2 = $this->actingAs($user)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'opd_id'           => $opdA->id,
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2],
            'header_row_index' => 0,
        ]);

        $diffRes2->assertStatus(200);
        $this->assertEquals(0, $diffRes2->json('other_opd_count'));
        $this->assertEquals(1, $diffRes2->json('nibar_matched'));
        $this->assertEquals(1, $diffRes2->json('changed_count'));
        $this->assertEquals('CHANGED', $diffRes2->json('items.0.status'));

        // 4. Lanjutkan Eksekusi Sinkronisasi (Tombol Hijau)
        $execRes = $this->actingAs($user)->postJson('/rekon-ebmd/execute', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'opd_id'           => $opdA->id,
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2],
            'create_new'       => false,
            'header_row_index' => 0,
        ]);

        $execRes->assertStatus(200);
        $this->assertEquals(1, $execRes->json('updated_count'));
        $this->assertEquals('1500.00', $tanahRefreshed->fresh()->luas);

        $tanahB->delete();
        $opdA->delete();
        $opdB->delete();
    }
}
