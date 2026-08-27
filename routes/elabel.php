<?php

use Illuminate\Support\Facades\Route;

// ===== MODUL ELABEL =====
Route::prefix('elabel')->name('elabel.')->group(function () {
    // Dashboard
    Route::get('dashboard', [\App\Http\Controllers\Elabel\ElabelDashboardController::class, 'index'])->name('dashboard');
    Route::post('dashboard/cleanup-logs', [\App\Http\Controllers\Elabel\ElabelDashboardController::class, 'cleanupActivityLogs'])->name('dashboard.cleanup-logs');
    
    // Diagnosis & Resolusi Duplikasi eLABEL
    Route::get('check-duplicates', [\App\Http\Controllers\Elabel\ElabelDashboardController::class, 'checkDuplicates'])->name('check-duplicates');
    Route::post('resolve-duplicate-bpkb', [\App\Http\Controllers\Elabel\ElabelDashboardController::class, 'resolveDuplicateBpkb'])->name('resolve-duplicate-bpkb');
    Route::post('resolve-duplicate-sertifikat', [\App\Http\Controllers\Elabel\ElabelDashboardController::class, 'resolveDuplicateSertifikat'])->name('resolve-duplicate-sertifikat');

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

    // Smart BPKB Extractor (Halaman Terpisah)
    Route::get('bpkb-smart-extractor', [\App\Http\Controllers\Elabel\ElabelSmartBpkbExtractorController::class, 'index'])->name('bpkb.smart-extractor.index');
    Route::post('bpkb-smart-extractor/scan', [\App\Http\Controllers\Elabel\ElabelSmartBpkbExtractorController::class, 'scan'])->name('bpkb.smart-extractor.scan');
    Route::post('bpkb-smart-extractor/execute', [\App\Http\Controllers\Elabel\ElabelSmartBpkbExtractorController::class, 'execute'])->name('bpkb.smart-extractor.execute');
    Route::get('bpkb-smart-extractor/preview', [\App\Http\Controllers\Elabel\ElabelSmartBpkbExtractorController::class, 'previewPdf'])->name('bpkb.smart-extractor.preview');

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
