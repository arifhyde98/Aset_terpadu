<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Erandis\VehicleController;
use App\Http\Controllers\Erandis\VehicleTypeController;
use App\Http\Controllers\Erandis\OpdController;

// Manajemen Kendaraan
Route::prefix('vehicles')->name('vehicles.')->group(function () {
    Route::get('export', [VehicleController::class, 'export'])->name('export');
    Route::get('template', [VehicleController::class, 'downloadTemplate'])->name('template');
    Route::post('import', [VehicleController::class, 'import'])->name('import');
    Route::post('import-legacy', [VehicleController::class, 'importLegacy'])->name('import-legacy');
    Route::post('import-preview', [VehicleController::class, 'importPreview'])->name('import-preview');
    Route::post('truncate', [VehicleController::class, 'truncate'])->name('truncate');
    Route::get('check-duplicates', [VehicleController::class, 'checkDuplicates'])->name('check-duplicates');
    Route::post('resolve-duplicate-vehicle', [VehicleController::class, 'resolveDuplicateVehicle'])->name('resolve-duplicate-vehicle');
    Route::post('resolve-duplicate-opd', [VehicleController::class, 'resolveDuplicateOpd'])->name('resolve-duplicate-opd');
    Route::post('sanitize-identifiers', [VehicleController::class, 'sanitizeIdentifiers'])->name('sanitize-identifiers');
    Route::post('sanitize-swapped-identifiers', [VehicleController::class, 'sanitizeSwappedIdentifiers'])->name('sanitize-swapped-identifiers');
    Route::post('{vehicle}/sync-to-real', [VehicleController::class, 'syncToReal'])->name('sync-to-real');
    Route::get('rekon-bpkb', [VehicleController::class, 'rekonBpkb'])->name('rekon-bpkb');
});
Route::resource('vehicles', VehicleController::class);

// Pemetaan OPD Terpadu (SIPAT ↔ E-RANDIS)
Route::prefix('master-data/opd-mapping')->name('master.opd-mapping.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Master\MasterOpdMappingController::class, 'index'])->name('index');
    Route::post('/refresh', [\App\Http\Controllers\Master\MasterOpdMappingController::class, 'refresh'])->name('refresh');
    Route::delete('/{id}', [\App\Http\Controllers\Master\MasterOpdMappingController::class, 'destroy'])->name('destroy');
});

// Jenis Kendaraan
Route::prefix('vehicle-types')->name('vehicle-types.')->group(function () {
    Route::post('cleanup', [VehicleTypeController::class, 'cleanup'])->name('cleanup');
    Route::post('merge', [VehicleTypeController::class, 'merge'])->name('merge');
});
Route::resource('vehicle-types', VehicleTypeController::class)->except(['create', 'edit', 'show']);

// OPD / Instansi
Route::prefix('opds')->name('opds.')->group(function () {
    Route::delete('truncate', [OpdController::class, 'truncate'])->name('truncate');
    Route::post('merge', [OpdController::class, 'merge'])->name('merge');
    Route::post('convert-to-sub-opd', [OpdController::class, 'convertToSubOpd'])->name('convert-to-sub-opd');
});
Route::resource('opds', OpdController::class)->except(['create', 'edit', 'show']);

// Sub-OPD / Kuasa Pengguna Barang (KPB)
Route::prefix('sub-opds')->name('sub-opds.')->group(function () {
    Route::get('by-opd/{opdId}', [\App\Http\Controllers\Erandis\SubOpdController::class, 'getByOpd'])->name('by-opd');
});
Route::resource('sub-opds', \App\Http\Controllers\Erandis\SubOpdController::class)->except(['create', 'edit', 'show']);

