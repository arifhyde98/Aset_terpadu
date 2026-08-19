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
Route::get('/elabel/dashboard', [HomeController::class, 'index'])->name('elabel.dashboard');
Route::get('/erandis/dashboard', [HomeController::class, 'index'])->name('erandis.dashboard');

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
