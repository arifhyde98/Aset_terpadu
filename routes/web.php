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
