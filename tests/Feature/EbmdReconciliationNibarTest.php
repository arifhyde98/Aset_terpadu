<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AsetTanah;
use App\Models\IntegrationAuditLog;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
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
                // Set as explicit string to preserve leading zeroes
                $sheet->setCellValueExplicitByColumnAndRow($colIndex + 1, $rowIndex + 1, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'rekon_test_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return new UploadedFile($tempPath, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    /**
     * Test: Rekonsiliasi NIBAR matching, identik vs berubah, duplikat, dan invalid.
     */
    public function test_nibar_reconciliation_diff_and_selective_execution(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
        ]);

        $nibar1 = '00' . rand(10000000, 99999999);
        $nibar2 = '00' . rand(10000000, 99999999);
        $nibarNew = '00' . rand(10000000, 99999999);

        // Buat data eksisting di SIPAT
        $tanah1 = AsetTanah::withoutEvents(function() use ($nibar1) {
            return AsetTanah::create([
                'kode_aset'         => $nibar1,
                'nama_aset'         => 'Tanah Kantor Bappeda',
                'luas'              => '1200.00',
                'harga_perolehan'   => '500000000',
                'alamat'            => 'Jl. Merdeka No. 1',
                'status_pencatatan' => 'TERCATAT_KIB_A',
            ]);
        });

        $tanah2 = AsetTanah::withoutEvents(function() use ($nibar2) {
            return AsetTanah::create([
                'kode_aset'         => $nibar2,
                'nama_aset'         => 'Tanah Puskesmas Pusat',
                'luas'              => '850.00',
                'harga_perolehan'   => '300000000',
                'alamat'            => 'Jl. Sehat No. 10',
                'status_pencatatan' => 'TERCATAT_KIB_A',
            ]);
        });

        // 1. Upload file Excel e-BMD:
        // Baris 1: Header
        // Baris 2: $nibar1 -> Luas 1250 (BERUBAH)
        // Baris 3: $nibar2 -> Luas 850 (IDENTIK)
        // Baris 4: $nibarNew -> Tidak ada di SIPAT (ONLY_IN_EBMD)
        // Baris 5: NIBAR Kosong "" -> INVALID
        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Luas M2', 'Harga Perolehan'],
            [$nibar1, 'Tanah Kantor Bappeda', '1250', '500000000'],
            [$nibar2, 'Tanah Puskesmas Pusat', '850', '300000000'],
            [$nibarNew, 'Tanah Lapangan Baru', '2000', '150000000'],
            ['', 'Tanah Tanpa NIBAR', '500', '50000000'],
        ]);

        // POST upload-preview
        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);

        $uploadRes->assertStatus(200);
        $importToken = $uploadRes->json('import_token');
        $this->assertNotEmpty($importToken);

        // 2. POST diff-preview
        $diffRes = $this->actingAs($user)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['luas', 'harga_perolehan'],
            'mapping'          => [
                'nibar'           => 0,
                'nama_aset'       => 1,
                'luas'            => 2,
                'harga_perolehan' => 3,
            ],
            'header_row_index' => 0,
        ]);

        $diffRes->assertStatus(200);
        $diffData = $diffRes->json();

        // Validasi Statistik Diff
        $this->assertEquals(4, $diffData['total_ebmd']);
        $this->assertEquals(2, $diffData['nibar_matched']);
        $this->assertEquals(1, $diffData['identical_count']);
        $this->assertEquals(1, $diffData['changed_count']);
        $this->assertEquals(1, $diffData['not_found_sipat']);
        $this->assertEquals(1, $diffData['invalid_count']);

        // 3. POST execute (Selective Update Luas saja)
        $execRes = $this->actingAs($user)->postJson('/rekon-ebmd/execute', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['luas'],
            'mapping'          => [
                'nibar'           => 0,
                'nama_aset'       => 1,
                'luas'            => 2,
                'harga_perolehan' => 3,
            ],
            'create_new'       => false,
            'header_row_index' => 0,
        ]);

        $execRes->assertStatus(200);
        $execData = $execRes->json();
        $this->assertTrue($execData['success']);
        $this->assertEquals(1, $execData['updated_count']);

        // Verifikasi database SIPAT:
        // Tanah 1 luas harus terupdate menjadi 1250.00
        $tanah1Updated = AsetTanah::withoutGlobalScopes()->find($tanah1->id_aset);
        $this->assertEquals(1250, (float)$tanah1Updated->luas);

        // Verifikasi audit log di integration_audit_logs
        $auditLog = IntegrationAuditLog::where('nibar', $nibar1)->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals('EBMD_RECONCILIATION_UPDATE', $auditLog->event_name);

        // Bersihkan file lokal & data test
        if ($tanah1) $tanah1->delete();
        if ($tanah2) $tanah2->delete();
        if ($auditLog) $auditLog->delete();
    }

    /**
     * Test: e-BMD value kosong TIDAK BOLEH menghapus value di SIPAT (Empty Value Protection).
     */
    public function test_empty_ebmd_value_protects_existing_sipat_value(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
        ]);

        $nibar = '00' . rand(10000000, 99999999);

        $tanah = AsetTanah::withoutEvents(function() use ($nibar) {
            return AsetTanah::create([
                'kode_aset'         => $nibar,
                'nama_aset'         => 'Tanah Berharga',
                'luas'              => '500.00',
                'alamat'            => 'Jl. Asli No. 99',
                'status_pencatatan' => 'TERCATAT_KIB_A',
            ]);
        });

        // File e-BMD dengan alamat KOSONG
        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Alamat'],
            [$nibar, 'Tanah Berharga', ''],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $importToken = $uploadRes->json('import_token');

        // Diff preview: should be IDENTICAL (not diff) because empty e-BMD is protected
        $diffRes = $this->actingAs($user)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['alamat'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'alamat' => 2],
            'header_row_index' => 0,
        ]);

        $this->assertEquals(1, $diffRes->json('identical_count'));
        $this->assertEquals(0, $diffRes->json('changed_count'));

        // Execute: should NOT overwrite alamat with null or empty
        $this->actingAs($user)->postJson('/rekon-ebmd/execute', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['alamat'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'alamat' => 2],
            'create_new'       => false,
            'header_row_index' => 0,
        ]);

        $tanahRefreshed = AsetTanah::withoutGlobalScopes()->find($tanah->id_aset);
        $this->assertEquals('Jl. Asli No. 99', $tanahRefreshed->alamat);

        if ($tanah) $tanah->delete();
    }

    /**
     * Test: Rekonsiliasi Per OPD Tertentu hanya mencocokkan aset milik OPD tersebut.
     */
    public function test_opd_scoping_reconciliation(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
        ]);

        $opd1 = \App\Models\Opd::firstOrCreate(['nama' => 'Dinas Pendidikan'], ['singkatan' => 'Disdik', 'aktif' => 1]);
        $opd2 = \App\Models\Opd::firstOrCreate(['nama' => 'Dinas Kesehatan'], ['singkatan' => 'Dinkes', 'aktif' => 1]);

        $nibarDisdik = '00' . rand(10000000, 99999999);
        $nibarDinkes = '00' . rand(10000000, 99999999);

        // Aset 1 milik Disdik
        $tanahDisdik = AsetTanah::withoutEvents(function() use ($nibarDisdik, $opd1) {
            return AsetTanah::create([
                'kode_aset'         => $nibarDisdik,
                'nama_aset'         => 'Tanah SMP 1',
                'luas'              => '2000.00',
                'harga_perolehan'   => '100000000',
                'opd_id'            => $opd1->id,
                'status_pencatatan' => 'TERCATAT_KIB_A',
            ]);
        });

        // Aset 2 milik Dinkes
        $tanahDinkes = AsetTanah::withoutEvents(function() use ($nibarDinkes, $opd2) {
            return AsetTanah::create([
                'kode_aset'         => $nibarDinkes,
                'nama_aset'         => 'Tanah Puskesmas',
                'luas'              => '1000.00',
                'harga_perolehan'   => '80000000',
                'opd_id'            => $opd2->id,
                'status_pencatatan' => 'TERCATAT_KIB_A',
            ]);
        });

        // File Excel berisi kedua NIBAR dengan luas baru
        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Luas M2'],
            [$nibarDisdik, 'Tanah SMP 1', '2500'],
            [$nibarDinkes, 'Tanah Puskesmas', '1200'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $importToken = $uploadRes->json('import_token');

        // Kasus 1: Filter khusus OPD 1 (Disdik)
        // Disdik (nibarDisdik) harus MATCHED (CHANGED)
        // Dinkes (nibarDinkes) TIDAK ada di OPD 1, jadi statusnya ONLY_IN_EBMD
        $diffRes = $this->actingAs($user)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2],
            'opd_id'           => $opd1->id,
            'header_row_index' => 0,
        ]);

        $diffRes->assertStatus(200);
        $this->assertEquals(1, $diffRes->json('nibar_matched'));
        $this->assertEquals(1, $diffRes->json('changed_count'));
        $this->assertEquals(1, $diffRes->json('not_found_sipat'));
        $this->assertEquals($opd1->id, $diffRes->json('opd_id'));

        // Eksekusi khusus OPD 1 (Disdik)
        $execRes = $this->actingAs($user)->postJson('/rekon-ebmd/execute', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['luas'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1, 'luas' => 2],
            'opd_id'           => $opd1->id,
            'create_new'       => false,
            'header_row_index' => 0,
        ]);
        $execRes->assertStatus(200);

        // Cek: Disdik terupdate menjadi 2500, sedangkan Dinkes TETAP 1000 (tidak tersentuh)
        $this->assertEquals('2500.00', AsetTanah::withoutGlobalScopes()->find($tanahDisdik->id_aset)->luas);
        $this->assertEquals('1000.00', AsetTanah::withoutGlobalScopes()->find($tanahDinkes->id_aset)->luas);

        if ($tanahDisdik) $tanahDisdik->delete();
        if ($tanahDinkes) $tanahDinkes->delete();
    }

    /**
     * Test: Export preview returns valid downloadable CSV stream.
     */
    public function test_export_preview_streams_csv_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
        ]);

        $nibar = '00' . rand(10000000, 99999999);

        $excelFile = $this->createExcelFile([
            ['NIBAR', 'Nama Barang', 'Luas M2'],
            [$nibar, 'Tanah Sample Export', '1000'],
        ]);

        $uploadRes = $this->actingAs($user)->post('/rekon-ebmd/upload-preview', [
            'file'           => $excelFile,
            'asset_category' => 'tanah',
        ]);
        $uploadRes->assertStatus(200);
        $importToken = $uploadRes->json('import_token');
        $this->assertNotEmpty($importToken);

        // Run diff-preview to populate cache
        $diffRes = $this->actingAs($user)->postJson('/rekon-ebmd/diff-preview', [
            'import_token'     => $importToken,
            'asset_category'   => 'tanah',
            'selected_columns' => ['nama_aset'],
            'mapping'          => ['nibar' => 0, 'nama_aset' => 1],
            'header_row_index' => 0,
        ]);
        $diffRes->assertStatus(200);

        // Post export-preview
        $exportRes = $this->actingAs($user)->post('/rekon-ebmd/export-preview', [
            'import_token' => $importToken,
        ]);

        $exportRes->assertStatus(200);
        $this->assertTrue(str_contains($exportRes->headers->get('content-type'), 'text/csv'));
    }
}

