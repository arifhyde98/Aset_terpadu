<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\VehicleTypeController;
use App\Http\Controllers\OpdController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\HealthCheckController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Di sini adalah tempat pendaftaran rute web untuk aplikasi.
| Middleware kini dikelola langsung di dalam masing-masing Controller 
| melalui interface HasMiddleware (Laravel 11/12 standard).
|
*/

// Akses Publik (Landing Page)
Route::get('/', [VehicleController::class, 'search'])->name('landing');
Route::get('/vehicle-search', [VehicleController::class, 'searchLandingVehicle'])->name('landing.vehicle-search');

// Otentikasi (Bawaan Laravel UI/Fortify)
Auth::routes();

// Rute Dashboard & Internal (Middleware dikelola di Controller)
Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/sipat/dashboard', [\App\Http\Controllers\SipatDashboardController::class, 'index'])->name('sipat.dashboard');
Route::get('/erandis/dashboard', [HomeController::class, 'erandisDashboard'])->name('erandis.dashboard');

// ===== MODUL ELABEL =====
Route::prefix('elabel')->name('elabel.')->group(function () {
    // Dashboard
    Route::get('dashboard', [\App\Http\Controllers\Elabel\ElabelDashboardController::class, 'index'])->name('dashboard');
    Route::post('dashboard/cleanup-logs', [\App\Http\Controllers\Elabel\ElabelDashboardController::class, 'cleanupActivityLogs'])->name('dashboard.cleanup-logs');

    // BPKB Kendaraan (R4 / R2)
    Route::get('bpkb', [\App\Http\Controllers\Elabel\ElabelBpkbController::class, 'index'])->name('bpkb.index');
    Route::get('bpkb/create', [\App\Http\Controllers\Elabel\ElabelBpkbController::class, 'create'])->name('bpkb.create');
    Route::post('bpkb', [\App\Http\Controllers\Elabel\ElabelBpkbController::class, 'store'])->name('bpkb.store');
    Route::get('bpkb/export', [\App\Http\Controllers\Elabel\ElabelBpkbController::class, 'export'])->name('bpkb.export');
    Route::post('bpkb/import', [\App\Http\Controllers\Elabel\ElabelBpkbController::class, 'import'])->name('bpkb.import');
    Route::get('bpkb/template', [\App\Http\Controllers\Elabel\ElabelBpkbController::class, 'downloadImportTemplate'])->name('bpkb.template');
    Route::get('bpkb/{id}', [\App\Http\Controllers\Elabel\ElabelBpkbController::class, 'show'])->name('bpkb.show');
    Route::get('bpkb/{id}/edit', [\App\Http\Controllers\Elabel\ElabelBpkbController::class, 'edit'])->name('bpkb.edit');
    Route::post('bpkb/{id}/update', [\App\Http\Controllers\Elabel\ElabelBpkbController::class, 'update'])->name('bpkb.update');
    Route::post('bpkb/{id}/delete', [\App\Http\Controllers\Elabel\ElabelBpkbController::class, 'delete'])->name('bpkb.delete');
    Route::get('bpkb/{id}/view-pdf', [\App\Http\Controllers\Elabel\ElabelBpkbController::class, 'viewPdf'])->name('bpkb.view-pdf');

    // BPKB Keluar (Soft Delete)
    Route::get('bpkb-deleted', [\App\Http\Controllers\Elabel\ElabelBpkbDeletedController::class, 'index'])->name('bpkb-deleted.index');
    Route::get('bpkb-deleted/export', [\App\Http\Controllers\Elabel\ElabelBpkbDeletedController::class, 'export'])->name('bpkb-deleted.export');
    Route::get('bpkb-deleted/{id}', [\App\Http\Controllers\Elabel\ElabelBpkbDeletedController::class, 'show'])->name('bpkb-deleted.show');
    Route::post('bpkb-deleted/{id}/restore', [\App\Http\Controllers\Elabel\ElabelBpkbDeletedController::class, 'restore'])->name('bpkb-deleted.restore');
    Route::delete('bpkb-deleted/{id}', [\App\Http\Controllers\Elabel\ElabelBpkbDeletedController::class, 'destroy'])->name('bpkb-deleted.destroy');
    Route::get('bpkb-deleted/{id}/view-pdf', [\App\Http\Controllers\Elabel\ElabelBpkbDeletedController::class, 'viewPdf'])->name('bpkb-deleted.view-pdf');
    Route::get('bpkb-deleted/{id}/view-doc', [\App\Http\Controllers\Elabel\ElabelBpkbDeletedController::class, 'viewSupportDoc'])->name('bpkb-deleted.view-doc');

    // Box BPKB
    Route::get('boxes', [\App\Http\Controllers\Elabel\ElabelBoxController::class, 'index'])->name('boxes.index');
    Route::get('boxes/create', [\App\Http\Controllers\Elabel\ElabelBoxController::class, 'create'])->name('boxes.create');
    Route::post('boxes', [\App\Http\Controllers\Elabel\ElabelBoxController::class, 'store'])->name('boxes.store');
    Route::get('boxes/{id}', [\App\Http\Controllers\Elabel\ElabelBoxController::class, 'show'])->name('boxes.show');
    Route::post('boxes/{id}/merge', [\App\Http\Controllers\Elabel\ElabelBoxController::class, 'merge'])->name('boxes.merge');
    Route::get('boxes/{id}/label', [\App\Http\Controllers\Elabel\ElabelBoxController::class, 'label'])->name('boxes.label');
    Route::delete('boxes/{id}', [\App\Http\Controllers\Elabel\ElabelBoxController::class, 'destroy'])->name('boxes.destroy');

    // Sertifikat Tanah
    Route::get('sertifikat', [\App\Http\Controllers\Elabel\ElabelSertifikatController::class, 'index'])->name('sertifikat.index');
    Route::get('sertifikat/create', [\App\Http\Controllers\Elabel\ElabelSertifikatController::class, 'create'])->name('sertifikat.create');
    Route::post('sertifikat', [\App\Http\Controllers\Elabel\ElabelSertifikatController::class, 'store'])->name('sertifikat.store');
    Route::get('sertifikat/export', [\App\Http\Controllers\Elabel\ElabelSertifikatController::class, 'export'])->name('sertifikat.export');
    Route::get('sertifikat/template', [\App\Http\Controllers\Elabel\ElabelSertifikatController::class, 'downloadImportTemplate'])->name('sertifikat.template');
    Route::get('sertifikat/{id}', [\App\Http\Controllers\Elabel\ElabelSertifikatController::class, 'show'])->name('sertifikat.show');
    Route::get('sertifikat/{id}/edit', [\App\Http\Controllers\Elabel\ElabelSertifikatController::class, 'edit'])->name('sertifikat.edit');
    Route::put('sertifikat/{id}', [\App\Http\Controllers\Elabel\ElabelSertifikatController::class, 'update'])->name('sertifikat.update');
    Route::delete('sertifikat/{id}', [\App\Http\Controllers\Elabel\ElabelSertifikatController::class, 'destroy'])->name('sertifikat.destroy');
    Route::get('sertifikat/{id}/view-pdf', [\App\Http\Controllers\Elabel\ElabelSertifikatController::class, 'viewPdf'])->name('sertifikat.view-pdf');

    // Box Sertifikat Tanah
    Route::get('sertifikat-boxes', [\App\Http\Controllers\Elabel\ElabelSertifikatBoxController::class, 'index'])->name('sertifikat-boxes.index');
    Route::post('sertifikat-boxes', [\App\Http\Controllers\Elabel\ElabelSertifikatBoxController::class, 'store'])->name('sertifikat-boxes.store');
    Route::get('sertifikat-boxes/{id}', [\App\Http\Controllers\Elabel\ElabelSertifikatBoxController::class, 'show'])->name('sertifikat-boxes.show');
    Route::get('sertifikat-boxes/{id}/label', [\App\Http\Controllers\Elabel\ElabelSertifikatBoxController::class, 'label'])->name('sertifikat-boxes.label');
    Route::post('sertifikat-boxes/{id}/merge', [\App\Http\Controllers\Elabel\ElabelSertifikatBoxController::class, 'merge'])->name('sertifikat-boxes.merge');
    Route::post('sertifikat-boxes/{id}/split', [\App\Http\Controllers\Elabel\ElabelSertifikatBoxController::class, 'split'])->name('sertifikat-boxes.split');
    Route::delete('sertifikat-boxes/{id}', [\App\Http\Controllers\Elabel\ElabelSertifikatBoxController::class, 'destroy'])->name('sertifikat-boxes.destroy');



    // Surat Penyerahan
    Route::get('surat-penyerahan', [\App\Http\Controllers\Elabel\ElabelSuratPenyerahanController::class, 'index'])->name('surat-penyerahan.index');
    Route::get('surat-penyerahan/create', [\App\Http\Controllers\Elabel\ElabelSuratPenyerahanController::class, 'create'])->name('surat-penyerahan.create');
    Route::post('surat-penyerahan', [\App\Http\Controllers\Elabel\ElabelSuratPenyerahanController::class, 'store'])->name('surat-penyerahan.store');
    Route::get('surat-penyerahan/export', [\App\Http\Controllers\Elabel\ElabelSuratPenyerahanController::class, 'export'])->name('surat-penyerahan.export');
    Route::get('surat-penyerahan/template', [\App\Http\Controllers\Elabel\ElabelSuratPenyerahanController::class, 'downloadImportTemplate'])->name('surat-penyerahan.template');
    Route::get('surat-penyerahan/{id}', [\App\Http\Controllers\Elabel\ElabelSuratPenyerahanController::class, 'show'])->name('surat-penyerahan.show');
    Route::get('surat-penyerahan/{id}/edit', [\App\Http\Controllers\Elabel\ElabelSuratPenyerahanController::class, 'edit'])->name('surat-penyerahan.edit');
    Route::put('surat-penyerahan/{id}', [\App\Http\Controllers\Elabel\ElabelSuratPenyerahanController::class, 'update'])->name('surat-penyerahan.update');
    Route::delete('surat-penyerahan/{id}', [\App\Http\Controllers\Elabel\ElabelSuratPenyerahanController::class, 'destroy'])->name('surat-penyerahan.destroy');
    Route::get('surat-penyerahan/{id}/pdf', [\App\Http\Controllers\Elabel\ElabelSuratPenyerahanController::class, 'pdf'])->name('surat-penyerahan.pdf');

    // Box Surat Penyerahan
    Route::get('surat-penyerahan-boxes', [\App\Http\Controllers\Elabel\ElabelSuratPenyerahanBoxController::class, 'index'])->name('surat-penyerahan-boxes.index');
    Route::post('surat-penyerahan-boxes', [\App\Http\Controllers\Elabel\ElabelSuratPenyerahanBoxController::class, 'store'])->name('surat-penyerahan-boxes.store');
    Route::get('surat-penyerahan-boxes/{id}', [\App\Http\Controllers\Elabel\ElabelSuratPenyerahanBoxController::class, 'show'])->name('surat-penyerahan-boxes.show');
    Route::delete('surat-penyerahan-boxes/{id}', [\App\Http\Controllers\Elabel\ElabelSuratPenyerahanBoxController::class, 'destroy'])->name('surat-penyerahan-boxes.destroy');

    // Peminjaman / Scan Request
    Route::get('peminjaman', [\App\Http\Controllers\Elabel\ElabelLoanController::class, 'index'])->name('peminjaman.index');
    Route::post('peminjaman/store-manual', [\App\Http\Controllers\Elabel\ElabelLoanController::class, 'storeManual'])->name('peminjaman.store-manual');
    Route::post('peminjaman/{id}/approve', [\App\Http\Controllers\Elabel\ElabelLoanController::class, 'approve'])->name('peminjaman.approve');
    Route::post('peminjaman/{id}/reject', [\App\Http\Controllers\Elabel\ElabelLoanController::class, 'reject'])->name('peminjaman.reject');
    Route::delete('peminjaman/{id}', [\App\Http\Controllers\Elabel\ElabelLoanController::class, 'destroy'])->name('peminjaman.destroy');
    Route::get('peminjaman/{id}/download', [\App\Http\Controllers\Elabel\ElabelLoanController::class, 'download'])->name('peminjaman.download');
});


// Modul SIPAT (Aset Tanah)
Route::get('/sipat/aset', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'index'])->name('sipat.aset.index');
Route::get('/sipat/aset/create', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'create'])->name('sipat.aset.create');
Route::post('/sipat/aset', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'store'])->name('sipat.aset.store');
Route::post('/sipat/aset/bulk-proses', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'bulkStoreProses'])->name('sipat.aset.bulkProses');
Route::get('/sipat/aset/{id}', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'show'])->name('sipat.aset.show');
Route::get('/sipat/aset/{id}/modal', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'modal'])->name('sipat.aset.modal');
Route::get('/sipat/aset/{id}/edit', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'edit'])->name('sipat.aset.edit');
Route::put('/sipat/aset/{id}', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'update'])->name('sipat.aset.update');
Route::delete('/sipat/aset/{id}', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'destroy'])->name('sipat.aset.destroy');
Route::post('/sipat/aset/{id}/proses', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'storeProses'])->name('sipat.aset.storeProses');
Route::post('/sipat/aset/{id}/pengamanan', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'storePengamanan'])->name('sipat.aset.storePengamanan');
Route::post('/sipat/aset/{id}/dokumen', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'storeDokumen'])->name('sipat.aset.storeDokumen');

// Modul SIPAT (Laporan)
Route::get('/sipat/laporan', [\App\Http\Controllers\Sipat\LaporanController::class, 'index'])->name('sipat.laporan.index');
Route::get('/sipat/laporan/export/csv', [\App\Http\Controllers\Sipat\LaporanController::class, 'exportCsv'])->name('sipat.laporan.exportCsv');
Route::get('/sipat/laporan/export/xlsx', [\App\Http\Controllers\Sipat\LaporanController::class, 'exportXlsx'])->name('sipat.laporan.exportXlsx');
Route::get('/sipat/laporan/preview-pdf', [\App\Http\Controllers\Sipat\LaporanController::class, 'previewPdf'])->name('sipat.laporan.previewPdf');
Route::get('/sipat/laporan/download-pdf', [\App\Http\Controllers\Sipat\LaporanController::class, 'downloadPdf'])->name('sipat.laporan.downloadPdf');

// Modul SIPAT (Rekonsiliasi, Peta, Surat)
Route::get('/sipat/rekonsiliasi', [\App\Http\Controllers\Sipat\RekonsiliasiController::class, 'index'])->name('sipat.rekonsiliasi.index');
Route::get('/sipat/peta', [\App\Http\Controllers\Sipat\PetaController::class, 'index'])->name('sipat.peta.index');

Route::get('/sipat/surat/skpt', [\App\Http\Controllers\Sipat\SuratController::class, 'skpt'])->name('sipat.surat.skpt');
Route::post('/sipat/surat/skpt', [\App\Http\Controllers\Sipat\SuratController::class, 'storeSkpt'])->name('sipat.surat.storeSkpt');
Route::get('/sipat/surat/skpt/{id}', [\App\Http\Controllers\Sipat\SuratController::class, 'showSkpt'])->name('sipat.surat.showSkpt');
Route::delete('/sipat/surat/skpt/{id}', [\App\Http\Controllers\Sipat\SuratController::class, 'deleteSkpt'])->name('sipat.surat.deleteSkpt');
Route::get('/sipat/surat/skpt/{id}/print', [\App\Http\Controllers\Sipat\SuratController::class, 'printSkpt'])->name('sipat.surat.printSkpt');
Route::get('/sipat/surat/skpt/{id}/pdf', [\App\Http\Controllers\Sipat\SuratController::class, 'pdfSkpt'])->name('sipat.surat.pdfSkpt');
Route::get('/sipat/surat/skpt/{id}/word', [\App\Http\Controllers\Sipat\SuratController::class, 'exportWordSkpt'])->name('sipat.surat.exportWordSkpt');
Route::get('/sipat/surat/pernyataan-batas', [\App\Http\Controllers\Sipat\SuratController::class, 'pernyataanBatas'])->name('sipat.surat.pernyataanBatas');
Route::resource('master-data/status-proses', \App\Http\Controllers\StatusProsesController::class)->names('status-proses');
Route::resource('master-data/opd-sipat', \App\Http\Controllers\MasterSipatOpdController::class)->names('opd-sipat');
Route::get('master-data/opd-sipat-list', [\App\Http\Controllers\MasterSipatOpdController::class, 'index'])->name('master.opd-sipat.index');
Route::get('master-data/kop-surat', [\App\Http\Controllers\KopSettingsController::class, 'index'])->name('master.kop-settings.index');
Route::post('master-data/kop-surat', [\App\Http\Controllers\KopSettingsController::class, 'update'])->name('master.kop-settings.update');
Route::get('master-data/log-aktivitas', [\App\Http\Controllers\AuditLogsController::class, 'index'])->name('master.logs.index');
Route::get('master-data/log-aktivitas/{id}', [\App\Http\Controllers\AuditLogsController::class, 'show'])->name('master.logs.show');

// Master Data Import Sertifikat & Status Proses (2 Tab Khas SIPAT)
Route::get('master-data/import', [\App\Http\Controllers\SipatImportController::class, 'index'])->name('master.import.index');
Route::post('master-data/import/data', [\App\Http\Controllers\SipatImportController::class, 'importData'])->name('master.import.data');
Route::post('master-data/import/update', [\App\Http\Controllers\SipatImportController::class, 'importUpdate'])->name('master.import.update');
Route::get('master-data/import/template-status', [\App\Http\Controllers\SipatImportController::class, 'downloadTemplateStatus'])->name('master.import.template-status');
Route::get('master-data/import/template-data', [\App\Http\Controllers\SipatImportController::class, 'downloadTemplateData'])->name('master.import.template-data');




// Profil Pengguna
Route::get('profile', [ProfileController::class, 'index'])->name('profile.index');
Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

// Manajemen Kendaraan
Route::get('vehicles/export', [VehicleController::class, 'export'])->name('vehicles.export');
Route::get('vehicles/template', [VehicleController::class, 'downloadTemplate'])->name('vehicles.template');
Route::post('vehicles/import', [VehicleController::class, 'import'])->name('vehicles.import');
Route::post('vehicles/import-legacy', [VehicleController::class, 'importLegacy'])->name('vehicles.import-legacy');
Route::post('vehicles/import-preview', [VehicleController::class, 'importPreview'])->name('vehicles.import-preview');
Route::post('vehicles/truncate', [VehicleController::class, 'truncate'])->name('vehicles.truncate');
Route::get('vehicles/check-duplicates', [VehicleController::class, 'checkDuplicates'])->name('vehicles.check-duplicates');
Route::post('vehicles/resolve-duplicate-vehicle', [VehicleController::class, 'resolveDuplicateVehicle'])->name('vehicles.resolve-duplicate-vehicle');
Route::post('vehicles/resolve-duplicate-opd', [VehicleController::class, 'resolveDuplicateOpd'])->name('vehicles.resolve-duplicate-opd');
Route::post('vehicles/sanitize-identifiers', [VehicleController::class, 'sanitizeIdentifiers'])->name('vehicles.sanitize-identifiers');
Route::post('vehicles/sanitize-swapped-identifiers', [VehicleController::class, 'sanitizeSwappedIdentifiers'])->name('vehicles.sanitize-swapped-identifiers');
Route::post('vehicles/{vehicle}/sync-to-real', [VehicleController::class, 'syncToReal'])->name('vehicles.sync-to-real');
Route::resource('vehicles', VehicleController::class)->except(['create', 'edit', 'show']);

// Master Data Hub
Route::get('master-data', [MasterDataController::class, 'index'])->name('master-data.index');

// Pemetaan OPD Terpadu (SIPAT ↔ E-RANDIS)
Route::get('master-data/opd-mapping', [\App\Http\Controllers\MasterOpdMappingController::class, 'index'])->name('master.opd-mapping.index');
Route::delete('master-data/opd-mapping/{id}', [\App\Http\Controllers\MasterOpdMappingController::class, 'destroy'])->name('master.opd-mapping.destroy');

// Master Data Wilayah (SIPAT)
Route::prefix('master-data/wilayah')->name('master.')->group(function () {
    Route::get('/', [\App\Http\Controllers\MasterDataWilayahController::class, 'index'])->name('wilayah.index');
    
    Route::post('kecamatan', [\App\Http\Controllers\MasterDataWilayahController::class, 'kecamatanStore'])->name('kecamatan.store');
    Route::put('kecamatan/{id}', [\App\Http\Controllers\MasterDataWilayahController::class, 'kecamatanUpdate'])->name('kecamatan.update');
    Route::delete('kecamatan/{id}', [\App\Http\Controllers\MasterDataWilayahController::class, 'kecamatanDestroy'])->name('kecamatan.destroy');

    Route::post('desa', [\App\Http\Controllers\MasterDataWilayahController::class, 'desaStore'])->name('desa.store');
    Route::put('desa/{id}', [\App\Http\Controllers\MasterDataWilayahController::class, 'desaUpdate'])->name('desa.update');
    Route::delete('desa/{id}', [\App\Http\Controllers\MasterDataWilayahController::class, 'desaDestroy'])->name('desa.destroy');

    Route::post('kepala-desa', [\App\Http\Controllers\MasterDataWilayahController::class, 'kadesStore'])->name('kades.store');
    Route::put('kepala-desa/{id}', [\App\Http\Controllers\MasterDataWilayahController::class, 'kadesUpdate'])->name('kades.update');
    Route::delete('kepala-desa/{id}', [\App\Http\Controllers\MasterDataWilayahController::class, 'kadesDestroy'])->name('kades.destroy');

    Route::post('camat', [\App\Http\Controllers\MasterDataWilayahController::class, 'camatStore'])->name('camat.store');
    Route::put('camat/{id}', [\App\Http\Controllers\MasterDataWilayahController::class, 'camatUpdate'])->name('camat.update');
    Route::delete('camat/{id}', [\App\Http\Controllers\MasterDataWilayahController::class, 'camatDestroy'])->name('camat.destroy');

    Route::post('pemohon', [\App\Http\Controllers\MasterDataWilayahController::class, 'pemohonStore'])->name('pemohon.store');
    Route::put('pemohon/{id}', [\App\Http\Controllers\MasterDataWilayahController::class, 'pemohonUpdate'])->name('pemohon.update');
    Route::delete('pemohon/{id}', [\App\Http\Controllers\MasterDataWilayahController::class, 'pemohonDestroy'])->name('pemohon.destroy');

    Route::post('judul-laporan', [\App\Http\Controllers\MasterDataWilayahController::class, 'judulStore'])->name('judul.store');
    Route::put('judul-laporan/{id}', [\App\Http\Controllers\MasterDataWilayahController::class, 'judulUpdate'])->name('judul.update');
    Route::delete('judul-laporan/{id}', [\App\Http\Controllers\MasterDataWilayahController::class, 'judulDestroy'])->name('judul.destroy');
});
Route::post('vehicle-types/cleanup', [VehicleTypeController::class, 'cleanup'])->name('vehicle-types.cleanup');
Route::post('vehicle-types/merge', [VehicleTypeController::class, 'merge'])->name('vehicle-types.merge');
Route::resource('vehicle-types', VehicleTypeController::class)->except(['create', 'edit', 'show']);
Route::delete('opds/truncate', [OpdController::class, 'truncate'])->name('opds.truncate');
Route::resource('opds', OpdController::class)->except(['create', 'edit', 'show']);

// Pengaturan & Manajemen User
Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
Route::post('users/generate-opd-accounts', [UserController::class, 'generateAllOpdAccounts'])->name('users.generate-opd-accounts');
Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
Route::resource('users', UserController::class)->except(['create', 'edit', 'show']);

// Manajemen Aktivitas (Audit Log)
Route::get('activities', [ActivityController::class, 'index'])->name('activities.index');
Route::delete('activities/clear', [ActivityController::class, 'clear'])->name('activities.clear');

// Modul Laporan Modular
Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('reports/preview', [ReportController::class, 'preview'])->name('reports.preview');
Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
Route::get('reports/print', [ReportController::class, 'print'])->name('reports.print');
Route::get('reports/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');

// Pengaturan Cetak & Dokumen Laporan (Kop & TTD)
Route::get('reports/settings', [\App\Http\Controllers\ReportSettingController::class, 'index'])->name('reports.settings.index');
Route::post('reports/settings/letterhead', [\App\Http\Controllers\ReportSettingController::class, 'updateLetterhead'])->name('reports.settings.letterhead');
Route::post('reports/settings/signatory', [\App\Http\Controllers\ReportSettingController::class, 'updateSignatory'])->name('reports.settings.signatory');
Route::post('reports/settings/export', [\App\Http\Controllers\ReportSettingController::class, 'updateExportSetting'])->name('reports.settings.export');

// Modul Maintenance Placeholder (Akan Datang)
Route::get('maintenance', function () {
    return view('maintenance.index');
})->name('maintenance.index');

// Monitoring API (Spoke)
Route::get('api/health-check', [HealthCheckController::class, 'check'])->name('api.health-check');
