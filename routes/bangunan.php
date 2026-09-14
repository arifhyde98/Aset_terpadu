<?php

use App\Http\Controllers\Bangunan\BangunanController;
use App\Http\Controllers\Bangunan\BangunanImportController;
use App\Http\Controllers\Bangunan\BangunanLaporanController;
use App\Http\Controllers\Bangunan\BangunanPetaController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix('bangunan')->name('bangunan.')->group(function () {
    // Pusat Pelaporan KIB C
    Route::get('laporan', [BangunanLaporanController::class, 'index'])->name('laporan.index');
    Route::get('laporan/pdf', [BangunanLaporanController::class, 'exportPdf'])->name('laporan.pdf');

    // Peta Spasial GIS Gedung
    Route::get('peta', [BangunanPetaController::class, 'index'])->name('peta.index');
    Route::get('peta/geojson', [BangunanPetaController::class, 'geojson'])->name('peta.geojson');

    // AI Smart Semantic Import
    Route::post('import/preview', [BangunanImportController::class, 'preview'])->name('import.preview');
    Route::post('import/execute', [BangunanImportController::class, 'execute'])->name('import.execute');

    // CRUD Katalog Bangunan
    Route::resource('/', BangunanController::class)->parameters(['' => 'bangunan']);
});
