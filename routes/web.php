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

use App\Http\Controllers\LandingPageController;

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

// Akses Publik (Landing Page & Unified Asset Search)
Route::get('/', [LandingPageController::class, 'index'])->name('landing');
Route::get('/search/vehicles', [LandingPageController::class, 'searchVehicles'])->name('landing.search.vehicles');
Route::get('/search/land', [LandingPageController::class, 'searchLand'])->name('landing.search.land');
Route::get('/search/archives', [LandingPageController::class, 'searchArchives'])->name('landing.search.archives');
Route::get('/api/public/stats', [LandingPageController::class, 'getStats'])->name('landing.api.stats');

// Backward Compatibility Aliases
Route::get('/vehicle-search', [LandingPageController::class, 'searchVehicles'])->name('landing.vehicle-search');
Route::get('/api/public/search/vehicles', [LandingPageController::class, 'searchVehicles']);
Route::get('/api/public/search/land', [LandingPageController::class, 'searchLand']);
Route::get('/api/public/search/archives', [LandingPageController::class, 'searchArchives']);

// Otentikasi (Bawaan Laravel UI/Fortify)
Auth::routes();

// Rute Dashboard & Internal (Middleware dikelola di Controller)
Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/sipat/dashboard', [\App\Http\Controllers\SipatDashboardController::class, 'index'])->name('sipat.dashboard');
Route::get('/erandis/dashboard', [HomeController::class, 'erandisDashboard'])->name('erandis.dashboard');



// Profil Pengguna
Route::get('profile', [ProfileController::class, 'index'])->name('profile.index');
Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

// Master Data Hub
Route::get('master-data', [MasterDataController::class, 'index'])->name('master-data.index');

// Pengaturan & Manajemen User
Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

// Manajemen Backup (Spatie Backup)
Route::get('settings/backups', [\App\Http\Controllers\BackupController::class, 'index'])->name('settings.backups.index');
Route::post('settings/backups', [\App\Http\Controllers\BackupController::class, 'create'])->name('settings.backups.create');
Route::get('settings/backups/status', [\App\Http\Controllers\BackupController::class, 'status'])->name('settings.backups.status');
Route::get('settings/backups/download/{fileName}', [\App\Http\Controllers\BackupController::class, 'download'])->name('settings.backups.download')->where('fileName', '.*');
Route::delete('settings/backups/{fileName}', [\App\Http\Controllers\BackupController::class, 'destroy'])->name('settings.backups.destroy')->where('fileName', '.*');
Route::post('settings/backups/sync-db', [\App\Http\Controllers\BackupController::class, 'syncDb'])->name('settings.backups.sync-db');
Route::get('settings/backups/sync-db-status', [\App\Http\Controllers\BackupController::class, 'syncDbStatus'])->name('settings.backups.sync-db-status');
Route::get('settings/backups/sync-db-stream', [\App\Http\Controllers\BackupController::class, 'syncDbStream'])->name('settings.backups.sync-db-stream');
Route::post('settings/backups/restore-sql', [\App\Http\Controllers\BackupController::class, 'restoreSql'])->name('settings.backups.restore-sql');

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

// Asisten Pintar AI (Ollama Integration)
Route::prefix('ai')->name('ai.')->group(function () {
    Route::get('/status', [\App\Http\Controllers\AiAssistantController::class, 'status'])->name('status');
    Route::post('/ask', [\App\Http\Controllers\AiAssistantController::class, 'ask'])->name('ask');
    Route::post('/generate-summary', [\App\Http\Controllers\AiAssistantController::class, 'generateSummary'])->name('generate-summary');
});
