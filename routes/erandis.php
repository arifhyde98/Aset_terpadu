<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleTypeController;
use App\Http\Controllers\OpdController;

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
Route::resource('vehicles', VehicleController::class)->except(['create', 'edit', 'show']);

// Pemetaan OPD Terpadu (SIPAT ↔ E-RANDIS)
Route::prefix('master-data/opd-mapping')->name('master.opd-mapping.')->group(function () {
    Route::get('/', [\App\Http\Controllers\MasterOpdMappingController::class, 'index'])->name('index');
    Route::delete('/{id}', [\App\Http\Controllers\MasterOpdMappingController::class, 'destroy'])->name('destroy');
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
});
Route::resource('opds', OpdController::class)->except(['create', 'edit', 'show']);
