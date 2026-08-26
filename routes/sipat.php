<?php

use Illuminate\Support\Facades\Route;

// Modul SIPAT
Route::prefix('sipat')->name('sipat.')->group(function () {
    
    // Aset Tanah
    Route::prefix('aset')->name('aset.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'store'])->name('store');
        Route::post('/bulk-proses', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'bulkStoreProses'])->name('bulkProses');
        
        // Diagnosis & Resolusi Duplikasi SIPAT
        Route::get('/check-duplicates', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'checkDuplicates'])->name('check-duplicates');
        Route::post('/resolve-duplicate-aset', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'resolveDuplicateAset'])->name('resolve-duplicate-aset');
        Route::post('/resolve-duplicate-opd', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'resolveDuplicateOpdSipat'])->name('resolve-duplicate-opd');

        Route::get('/{aset}', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'show'])->name('show');
        Route::get('/{aset}/modal', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'modal'])->name('modal');
        Route::get('/{aset}/edit', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'edit'])->name('edit');
        Route::put('/{aset}', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'update'])->name('update');
        Route::delete('/{aset}', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'destroy'])->name('destroy');
        Route::post('/{aset}/proses', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'storeProses'])->name('storeProses');
        Route::post('/{aset}/pengamanan', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'storePengamanan'])->name('storePengamanan');
        Route::post('/{aset}/dokumen', [\App\Http\Controllers\Sipat\AsetTanahController::class, 'storeDokumen'])->name('storeDokumen');
    });

    // Target Pensertifikatan Tanah Tahunan
    Route::prefix('target-pensertifikatan')->name('target-pensertifikatan.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Sipat\TargetSertifikatController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Sipat\TargetSertifikatController::class, 'store'])->name('store');
        Route::put('/{target}', [\App\Http\Controllers\Sipat\TargetSertifikatController::class, 'update'])->name('update');
        Route::delete('/{target}', [\App\Http\Controllers\Sipat\TargetSertifikatController::class, 'destroy'])->name('destroy');
        Route::get('/export-excel', [\App\Http\Controllers\Sipat\TargetSertifikatController::class, 'exportExcel'])->name('export-excel');
        Route::get('/export-pdf', [\App\Http\Controllers\Sipat\TargetSertifikatController::class, 'exportPdf'])->name('export-pdf');
    });

    // Laporan
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Sipat\LaporanController::class, 'index'])->name('index');
        Route::get('/export/csv', [\App\Http\Controllers\Sipat\LaporanController::class, 'exportCsv'])->name('exportCsv');
        Route::get('/export/xlsx', [\App\Http\Controllers\Sipat\LaporanController::class, 'exportXlsx'])->name('exportXlsx');
        Route::get('/preview-pdf', [\App\Http\Controllers\Sipat\LaporanController::class, 'previewPdf'])->name('previewPdf');
        Route::get('/download-pdf', [\App\Http\Controllers\Sipat\LaporanController::class, 'downloadPdf'])->name('downloadPdf');
    });

    // Rekonsiliasi & Peta
    Route::get('/rekonsiliasi', [\App\Http\Controllers\Sipat\RekonsiliasiController::class, 'index'])->name('rekonsiliasi.index');
    Route::prefix('peta')->name('peta.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Sipat\PetaController::class, 'index'])->name('index');
        Route::get('/data', [\App\Http\Controllers\Sipat\PetaController::class, 'data'])->name('data');
        Route::post('/import-poligon', [\App\Http\Controllers\Sipat\PetaController::class, 'importPoligon'])->name('import-poligon');
        Route::get('/export-geojson', [\App\Http\Controllers\Sipat\PetaController::class, 'exportGeojson'])->name('export-geojson');
    });

    // Surat
    Route::prefix('surat')->name('surat.')->group(function () {
        Route::get('/skpt', [\App\Http\Controllers\Sipat\SuratController::class, 'skpt'])->name('skpt');
        Route::post('/skpt', [\App\Http\Controllers\Sipat\SuratController::class, 'storeSkpt'])->name('storeSkpt');
        Route::get('/skpt/{skpt}', [\App\Http\Controllers\Sipat\SuratController::class, 'showSkpt'])->name('showSkpt');
        Route::delete('/skpt/{skpt}', [\App\Http\Controllers\Sipat\SuratController::class, 'deleteSkpt'])->name('deleteSkpt');
        Route::get('/skpt/{skpt}/print', [\App\Http\Controllers\Sipat\SuratController::class, 'printSkpt'])->name('printSkpt');
        Route::get('/skpt/{skpt}/pdf', [\App\Http\Controllers\Sipat\SuratController::class, 'pdfSkpt'])->name('pdfSkpt');
        Route::get('/skpt/{skpt}/word', [\App\Http\Controllers\Sipat\SuratController::class, 'exportWordSkpt'])->name('exportWordSkpt');
        Route::get('/pernyataan-batas', [\App\Http\Controllers\Sipat\SuratController::class, 'pernyataanBatas'])->name('pernyataanBatas');
    });
});

// Master Data SIPAT
Route::prefix('master-data')->group(function () {
    Route::resource('status-proses', \App\Http\Controllers\StatusProsesController::class)->names('status-proses');
    Route::resource('opd-sipat', \App\Http\Controllers\MasterSipatOpdController::class)->names('opd-sipat');
    Route::get('opd-sipat-list', [\App\Http\Controllers\MasterSipatOpdController::class, 'index'])->name('master.opd-sipat.index');

    // Rute dari web.php (kop-surat dan log-aktivitas)
    Route::get('kop-surat', [\App\Http\Controllers\KopSettingsController::class, 'index'])->name('master.kop-settings.index');
    Route::post('kop-surat', [\App\Http\Controllers\KopSettingsController::class, 'update'])->name('master.kop-settings.update');
    Route::get('log-aktivitas', [\App\Http\Controllers\AuditLogsController::class, 'index'])->name('master.logs.index');
    Route::get('log-aktivitas/{id}', [\App\Http\Controllers\AuditLogsController::class, 'show'])->name('master.logs.show');

    // Import Sertifikat & Status Proses
    Route::prefix('import')->name('master.import.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SipatImportController::class, 'index'])->name('index');
        Route::post('/data', [\App\Http\Controllers\SipatImportController::class, 'importData'])->name('data');
        Route::post('/update', [\App\Http\Controllers\SipatImportController::class, 'importUpdate'])->name('update');
        Route::get('/template-status', [\App\Http\Controllers\SipatImportController::class, 'downloadTemplateStatus'])->name('template-status');
        Route::get('/template-data', [\App\Http\Controllers\SipatImportController::class, 'downloadTemplateData'])->name('template-data');
    });

    // Wilayah (SIPAT)
    Route::prefix('wilayah')->name('master.')->group(function () {
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
});
