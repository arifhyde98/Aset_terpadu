@extends('layouts.app')

@section('title', 'Pusat Rekonsiliasi e-BMD Terpadu - SIPAT')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header Page -->
    <div class="page-header-global mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-warning-subtle text-warning-emphasis fw-semibold px-2.5 py-1 rounded-pill" style="font-size: 0.75rem;">
                        <i class="bi bi-arrow-repeat me-1"></i> PUSAT REKONSILIASI TERPADU E-BMD
                    </span>
                    <span class="badge bg-primary-subtle text-primary fw-semibold px-2.5 py-1 rounded-pill" style="font-size: 0.75rem;">
                        <i class="bi bi-key-fill me-1"></i> BUSINESS KEY: NIBAR
                    </span>
                </div>
                <h2 class="fw-bold mb-1 text-navy">Rekonsiliasi & Sinkronisasi e-BMD Kemendagri</h2>
                <p class="text-secondary small mb-0">Pencocokan data aset daerah (SIPAT) terhadap data pembanding e-BMD berbasis <strong>NIBAR (Nomor Induk Barang)</strong> secara selektif & aman.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('sipat.aset.index') }}" class="btn btn-light border fw-medium shadow-sm">
                    <i class="bi bi-geo-alt me-1 text-success"></i> Aset Tanah
                </a>
                <a href="{{ route('bangunan.index') }}" class="btn btn-light border fw-medium shadow-sm">
                    <i class="bi bi-building me-1 text-danger"></i> Bangunan
                </a>
                <a href="{{ route('vehicles.index') }}" class="btn btn-light border fw-medium shadow-sm">
                    <i class="bi bi-car-front me-1 text-warning"></i> Kendaraan
                </a>
            </div>
        </div>
    </div>

    <!-- Nav Tabs Kategori Aset -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
        <div class="card-body p-2 bg-light">
            <ul class="nav nav-pills nav-fill gap-2" id="assetCategoryTabs">
                <li class="nav-item">
                    <button class="nav-item-btn nav-link fw-bold text-start py-2.5 px-3 {{ $activeCategory === 'tanah' ? 'active bg-primary text-white shadow-sm' : 'text-dark bg-white' }}" data-category="tanah">
                        <i class="bi bi-geo-alt me-2 text-success fs-5 align-middle"></i>
                        <span>Aset Tanah (SIPAT)</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-item-btn nav-link fw-bold text-start py-2.5 px-3 {{ $activeCategory === 'bangunan' ? 'active bg-primary text-white shadow-sm' : 'text-dark bg-white' }}" data-category="bangunan">
                        <i class="bi bi-building me-2 text-danger fs-5 align-middle"></i>
                        <span>Gedung & Bangunan (SIPAT)</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-item-btn nav-link fw-bold text-start py-2.5 px-3 {{ $activeCategory === 'vehicle' ? 'active bg-primary text-white shadow-sm' : 'text-dark bg-white' }}" data-category="vehicle">
                        <i class="bi bi-car-front me-2 text-warning fs-5 align-middle"></i>
                        <span>Kendaraan Real (eRANDIS)</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-item-btn nav-link fw-bold text-start py-2.5 px-3 {{ $activeCategory === 'ebmd_vehicle' ? 'active bg-primary text-white shadow-sm' : 'text-dark bg-white' }}" data-category="ebmd_vehicle">
                        <i class="bi bi-database me-2 text-info fs-5 align-middle"></i>
                        <span>Master e-BMD Kendaraan</span>
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Alert / Banner Info Perlindungan Data -->
    <div class="alert alert-info border-0 shadow-sm rounded-3 d-flex align-items-start gap-3 p-3.5 mb-4">
        <div class="rounded-circle bg-info bg-opacity-20 p-2 text-info d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
            <i class="bi bi-shield-check fs-4"></i>
        </div>
        <div>
            <h6 class="fw-bold mb-1 text-info-emphasis">Sistem Perlindungan Data & Aturan Rekonsiliasi NIBAR Aktif</h6>
            <p class="small text-secondary mb-0">
                Pencocokan dilakukan mutlak menggunakan <code>SIPAT.NIBAR = eBMD.NIBAR</code>. Data lokal penting seperti <strong>Pemetaan Sub OPD (Sekolah & Puskesmas)</strong>, <strong>Koordinat & Poligon GIS</strong>, serta <strong>Foto Fisik</strong> tidak akan tertimpa. Nilai kosong dari e-BMD tidak akan menghapus data terisi di SIPAT.
            </p>
        </div>
    </div>

    <!-- Step Progress Bar -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <div class="row text-center g-2" id="rekonStepHeader">
                <div class="col-3">
                    <div class="p-2 rounded-3 bg-primary text-white fw-semibold step-pill" id="stepPill1">
                        <span class="badge bg-white text-primary rounded-circle me-1">1</span> Upload File
                    </div>
                </div>
                <div class="col-3">
                    <div class="p-2 rounded-3 bg-light text-muted fw-semibold step-pill" id="stepPill2">
                        <span class="badge bg-secondary text-white rounded-circle me-1">2</span> Mapping & Kunci
                    </div>
                </div>
                <div class="col-3">
                    <div class="p-2 rounded-3 bg-light text-muted fw-semibold step-pill" id="stepPill3">
                        <span class="badge bg-secondary text-white rounded-circle me-1">3</span> Pilih Kolom Update
                    </div>
                </div>
                <div class="col-3">
                    <div class="p-2 rounded-3 bg-light text-muted fw-semibold step-pill" id="stepPill4">
                        <span class="badge bg-secondary text-white rounded-circle me-1">4</span> Diff & Eksekusi
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- STEP 1: UPLOAD FILE & TARGET CATEGORY -->
    <div class="card border-0 shadow-sm rounded-3 mb-4" id="sectionStep1">
        <div class="card-header bg-white py-3 border-bottom-0">
            <h5 class="fw-bold text-navy mb-0"><i class="bi bi-file-earmark-arrow-up text-primary me-2"></i>Langkah 1: Unggah Berkas e-BMD Kemendagri</h5>
        </div>
        <div class="card-body p-4">
            <form id="formUpload" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="asset_category" id="inputAssetCategory" value="{{ $activeCategory }}">

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 bg-light text-dark h-100">
                            <label class="form-label fw-bold text-navy"><i class="bi bi-tag-fill me-1 text-primary"></i> Target Modul Aset SIPAT:</label>
                            <div class="fs-5 fw-bold text-primary mb-1" id="lblTargetCategoryTitle">Aset Tanah (SIPAT)</div>
                            <p class="small text-muted mb-0">File Excel akan dicocokkan berdasarkan NIBAR ke modul database target ini.</p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">File Excel / CSV Ekspor e-BMD <span class="text-danger">*</span></label>
                        <input type="file" class="form-control form-control-lg" name="file" id="inputFile" accept=".xlsx,.xls,.csv" required>
                        <small class="text-muted mt-1 d-block"><i class="bi bi-info-circle me-1"></i>Format: .xlsx, .xls, .csv (Maksimal 10 MB)</small>
                    </div>

                    <!-- Cakupan Wilayah / Filter OPD Rekonsiliasi -->
                    <div class="col-12">
                        <div class="p-3 border rounded-3 bg-light">
                            <label class="form-label fw-bold text-navy mb-2"><i class="bi bi-diagram-3-fill me-1 text-primary"></i> Cakupan Wilayah Rekonsiliasi Data:</label>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check p-3 border rounded-3 bg-white h-100 shadow-sm">
                                        <input class="form-check-input" type="radio" name="scope_type" id="scopeAll" value="all" checked>
                                        <label class="form-check-label fw-bold cursor-pointer text-dark w-100" for="scopeAll">
                                            <i class="bi bi-globe2 text-primary me-1"></i> Seluruh OPD (Semua Data Daerah)
                                            <span class="d-block small text-muted font-normal mt-1">Pencocokan NIBAR dilakukan ke seluruh aset daerah di semua instansi / OPD secara terpadu.</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check p-3 border rounded-3 bg-white h-100 shadow-sm">
                                        <input class="form-check-input" type="radio" name="scope_type" id="scopeOpd" value="opd">
                                        <label class="form-check-label fw-bold cursor-pointer text-dark w-100" for="scopeOpd">
                                            <i class="bi bi-building text-warning-emphasis me-1"></i> Per OPD Tertentu (Filter OPD)
                                            <span class="d-block small text-muted font-normal mt-1">Pencocokan NIBAR difokuskan khusus pada aset milik OPD yang dipilih.</span>
                                        </label>
                                        <div class="mt-2.5 pt-2 border-top" id="wrapperOpdSelect" style="display: none;">
                                            <label class="form-label small fw-bold text-navy mb-1">Pilih OPD Target Rekonsiliasi:</label>
                                            <select class="form-select form-select-sm" name="opd_id" id="selectOpd">
                                                <option value="">-- Pilih OPD / Satuan Kerja --</option>
                                                @foreach($opds as $opd)
                                                    <option value="{{ $opd->id }}" {{ ($userOpdId == $opd->id) ? 'selected' : '' }}>
                                                        {{ $opd->nama }} {{ $opd->singkatan ? "({$opd->singkatan})" : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold" id="btnUpload">
                        <span>Unggah & Analisis Kolom</span> <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- STEP 2: PEMETAAN KOLOM & KUNCI PENCOCOKAN (HIDDEN INITIALLY) -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 d-none" id="sectionStep2">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom-0">
            <div>
                <h5 class="fw-bold text-navy mb-1"><i class="bi bi-key-fill text-warning me-2"></i>Langkah 2: Pemetaan Kolom e-BMD ke SIPAT</h5>
                <span class="small text-secondary">Kunci Bisnis Utama: <strong class="text-primary">NIBAR (Nomor Induk Barang)</strong></span>
            </div>
            <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill" id="badgeActiveSheet">Sheet 1</span>
        </div>
        <div class="card-body p-4">
            <!-- NIBAR Key Notice -->
            <div class="alert alert-warning border-0 shadow-sm mb-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2 bg-warning bg-opacity-25 rounded-circle text-warning-emphasis">
                        <i class="bi bi-key-fill fs-3"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-warning-emphasis mb-1">Kunci Acuan Tunggal: NIBAR (Nomor Induk Barang)</h6>
                        <p class="small text-secondary mb-0">Pastikan baris pertama pada tabel di bawah memetakan kolom Excel yang berisi <strong>NIBAR</strong> secara tepat. NIBAR akan digunakan sebagai identitas pencocokan eksak antar sistem.</p>
                    </div>
                </div>
            </div>

            <!-- Mapping Table -->
            <h6 class="fw-bold mb-3"><i class="bi bi-table me-1"></i>Pemetaan Kolom Excel ke Kolom Database SIPAT:</h6>
            <div class="table-responsive mb-4">
                <table class="table table-hover align-middle border">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 30%;">Kolom Database SIPAT</th>
                            <th style="width: 45%;">Kolom Excel e-BMD</th>
                            <th style="width: 25%;">Sampel Nilai e-BMD</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyMapping">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary px-4" id="btnBackToStep1">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </button>
                <button type="button" class="btn btn-primary px-4 py-2 fw-semibold" id="btnGoToStep3">
                    <span>Lanjut Pilih Kolom Update</span> <i class="bi bi-arrow-right ms-2"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- STEP 3: SELECTIVE COLUMN CHECKLIST (HIDDEN INITIALLY) -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 d-none" id="sectionStep3">
        <div class="card-header bg-white py-3 border-bottom-0">
            <h5 class="fw-bold text-navy mb-0"><i class="bi bi-check2-square text-success me-2"></i>Langkah 3: Pilih Kolom yang Boleh Di-update (*Selective Column Update*)</h5>
        </div>
        <div class="card-body p-4">
            <!-- Preset Buttons -->
            <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
                <span class="fw-semibold text-secondary me-2"><i class="bi bi-magic me-1"></i>Preset Pilihan Cepat:</span>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" id="presetAll">
                    <i class="bi bi-check-all me-1"></i> Pilih Semua
                </button>
                <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3" id="presetNilaiKondisi">
                    <i class="bi bi-currency-dollar me-1"></i> Nilai & Luas / Kondisi Saja
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3" id="presetIdentitas">
                    <i class="bi bi-fingerprint me-1"></i> Identitas & Dokumen Saja
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" id="presetNone">
                    <i class="bi bi-x-lg me-1"></i> Hapus Semua Pilihan
                </button>
            </div>

            <!-- Checkbox Grid -->
            <div class="row g-3 mb-4" id="gridColumns">
                <!-- Checkboxes populated dynamically -->
            </div>

            <!-- Additional Settings -->
            <div class="p-3 bg-light rounded-3 mb-4 border">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="switchCreateNew">
                    <label class="form-check-label fw-bold text-dark cursor-pointer" for="switchCreateNew">
                        Tambahkan Data Baru Jika NIBAR Tidak Ditemukan di SIPAT
                    </label>
                    <small class="d-block text-muted">Secara default tidak dicentang (Data e-BMD yang tidak ditemukan di SIPAT hanya akan dilaporkan sebagai <em>Tidak Ditemukan di SIPAT</em> tanpa dimasukkan otomatis).</small>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary px-4" id="btnBackToStep2">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </button>
                <button type="button" class="btn btn-warning px-4 py-2 fw-semibold text-dark" id="btnGoToStep4">
                    <span>Analisis Perbedaan Data (Diff Preview)</span> <i class="bi bi-search ms-2"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- STEP 4: DIFF PREVIEW & EXECUTE (HIDDEN INITIALLY) -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 d-none" id="sectionStep4">
        <div class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="fw-bold text-navy mb-0"><i class="bi bi-speedometer2 text-info me-2"></i>Langkah 4: Pratinjau Rekonsiliasi & Eksekusi</h5>
                <span class="small text-secondary">
                    Kunci Rekonsiliasi: <strong class="text-primary">NIBAR</strong> | 
                    Target Modul: <strong class="text-navy" id="lblStep4CategoryName">Aset Tanah</strong> | 
                    Cakupan: <span class="badge bg-primary-subtle text-primary fw-semibold" id="lblStep4OpdScope"><i class="bi bi-globe2 me-1"></i>Seluruh OPD</span>
                </span>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-success fw-semibold" id="btnExportCsv">
                    <i class="bi bi-download me-1"></i> Download Hasil Rekonsiliasi (.CSV)
                </button>
            </div>
        </div>
        <div class="card-body p-4">
            <!-- 7 Stats Counters Sesuai Spesifikasi -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3 col-lg">
                    <div class="card border-0 bg-secondary bg-opacity-10 rounded-3 p-3 text-center h-100">
                        <div class="small text-secondary fw-bold text-uppercase" style="font-size: 0.72rem;">TOTAL e-BMD</div>
                        <div class="fs-3 fw-bold text-dark font-monospace mt-1" id="statTotalRows">0</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg">
                    <div class="card border-0 bg-primary bg-opacity-10 rounded-3 p-3 text-center h-100">
                        <div class="small text-primary fw-bold text-uppercase" style="font-size: 0.72rem;">NIBAR DITEMUKAN</div>
                        <div class="fs-3 fw-bold text-primary font-monospace mt-1" id="statMatchedCount">0</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg">
                    <div class="card border-0 bg-success bg-opacity-10 rounded-3 p-3 text-center h-100">
                        <div class="small text-success fw-bold text-uppercase" style="font-size: 0.72rem;">🟢 IDENTIK</div>
                        <div class="fs-3 fw-bold text-success font-monospace mt-1" id="statIdenticalCount">0</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg">
                    <div class="card border-0 bg-warning bg-opacity-10 rounded-3 p-3 text-center h-100">
                        <div class="small text-warning-emphasis fw-bold text-uppercase" style="font-size: 0.72rem;">🟡 BERUBAH</div>
                        <div class="fs-3 fw-bold text-warning-emphasis font-monospace mt-1" id="statChangedCount">0</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg">
                    <div class="card border-0 bg-info bg-opacity-10 rounded-3 p-3 text-center h-100">
                        <div class="small text-info-emphasis fw-bold text-uppercase" style="font-size: 0.72rem;">🔵 TIDAK DI SIPAT</div>
                        <div class="fs-3 fw-bold text-info font-monospace mt-1" id="statNotFoundSipat">0</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg">
                    <div class="card border-0 bg-danger bg-opacity-10 rounded-3 p-3 text-center h-100">
                        <div class="small text-danger fw-bold text-uppercase" style="font-size: 0.72rem;">🟠 DUPLIKAT</div>
                        <div class="fs-3 fw-bold text-danger font-monospace mt-1" id="statDuplicateCount">0</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg">
                    <div class="card border-0 bg-dark bg-opacity-10 rounded-3 p-3 text-center h-100">
                        <div class="small text-dark fw-bold text-uppercase" style="font-size: 0.72rem;">⚠️ INVALID</div>
                        <div class="fs-3 fw-bold text-dark font-monospace mt-1" id="statInvalidCount">0</div>
                    </div>
                </div>
            </div>

            <!-- Rincian Atribut Berubah Breakdown -->
            <div class="card border-0 bg-light rounded-3 p-3.5 mb-4 border shadow-sm">
                <h6 class="fw-bold text-navy mb-2.5 d-flex align-items-center">
                    <i class="bi bi-pie-chart-fill text-warning me-2 fs-5"></i> Rincian Perubahan Data per Kolom Terpilih:
                </h6>
                <div class="d-flex align-items-center gap-2 flex-wrap" id="containerFieldBreakdown">
                    <span class="text-muted small">Memuat rincian perbedaan...</span>
                </div>
            </div>

            <!-- Filter Status & Search Bar -->
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div class="btn-group btn-group-sm flex-wrap" id="filterStatusGroup" role="group">
                    <button type="button" class="btn btn-outline-secondary active fw-bold" data-filter="ALL">Semua (<span id="cntFilterAll">0</span>)</button>
                    <button type="button" class="btn btn-outline-success fw-bold" data-filter="IDENTICAL">🟢 Identik (<span id="cntFilterIdentical">0</span>)</button>
                    <button type="button" class="btn btn-outline-warning text-dark fw-bold" data-filter="CHANGED">🟡 Berubah (<span id="cntFilterChanged">0</span>)</button>
                    <button type="button" class="btn btn-outline-info text-dark fw-bold" data-filter="ONLY_IN_EBMD">🔵 Hanya e-BMD (<span id="cntFilterOnlyEbmd">0</span>)</button>
                    <button type="button" class="btn btn-outline-secondary fw-bold" data-filter="ONLY_IN_SIPAT">🔴 Hanya SIPAT (<span id="cntFilterOnlySipat">0</span>)</button>
                    <button type="button" class="btn btn-outline-danger fw-bold" data-filter="DUPLICATE_KEY">🟠 Duplikat (<span id="cntFilterDuplicate">0</span>)</button>
                    <button type="button" class="btn btn-outline-dark fw-bold" data-filter="INVALID">⚠️ Invalid (<span id="cntFilterInvalid">0</span>)</button>
                </div>

                <div class="input-group input-group-sm" style="max-width: 320px;">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control" id="searchTable" placeholder="Cari NIBAR / Nama Aset...">
                </div>
            </div>

            <!-- Tabel Hasil Rekonsiliasi NIBAR -->
            <div class="table-responsive mb-4 border rounded-3 shadow-sm">
                <table class="table table-hover mb-0 align-middle" id="tableDiffResult">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 20%;">NIBAR (Nomor Induk Barang)</th>
                            <th style="width: 22%;">Nama Aset / OPD</th>
                            <th style="width: 15%;">Status Rekonsiliasi</th>
                            <th style="width: 43%;">Detail Perubahan (Kolom | SIPAT | e-BMD)</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyDiffSamples">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>

            <!-- Dry Run Execution Summary -->
            <div class="p-3 bg-success bg-opacity-10 border border-success border-opacity-25 rounded-3 mb-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold text-success-emphasis mb-1"><i class="bi bi-check-circle-fill me-2"></i>Rekap Tindakan Eksekusi</h6>
                        <span class="small text-secondary">
                            Akan Diperbarui: <strong class="text-success" id="summaryWillUpdate">0</strong> Aset | 
                            Identik / Tidak Diubah: <strong class="text-secondary" id="summaryWillIdentical">0</strong> Aset | 
                            Dilewati (Duplikat/Invalid/Unmatched): <strong class="text-danger" id="summaryWillSkip">0</strong> Aset
                        </span>
                    </div>
                    <div class="small text-muted font-monospace" id="summarySelectedColsText">
                        <!-- Populated dynamically -->
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary px-4" id="btnBackToStep3">
                    <i class="bi bi-arrow-left me-1"></i> Ubah Pilihan Kolom
                </button>
                <button type="button" class="btn btn-success px-5 py-3 fw-bold fs-6 shadow" id="btnExecuteSync">
                    <i class="bi bi-play-circle-fill me-2"></i> Eksekusi Rekonsiliasi & Sync Database
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let importToken = '';
    let previewData = null;
    let diffResultData = null;
    let currentFilter = 'ALL';
    let currentSearch = '';

    const categoryNames = {
        'tanah': 'Aset Tanah (SIPAT)',
        'bangunan': 'Gedung & Bangunan (SIPAT)',
        'vehicle': 'Kendaraan Real (eRANDIS)',
        'ebmd_vehicle': 'Master e-BMD Kendaraan'
    };

    // Category Tabs Switching
    const categoryTabs = document.querySelectorAll('.nav-item-btn');
    const inputAssetCategory = document.getElementById('inputAssetCategory');
    const lblTargetCategoryTitle = document.getElementById('lblTargetCategoryTitle');

    categoryTabs.forEach(btn => {
        btn.addEventListener('click', function() {
            const cat = this.dataset.category;

            categoryTabs.forEach(b => {
                b.className = 'nav-item-btn nav-link fw-bold text-start py-2.5 px-3 text-dark bg-white';
            });
            this.className = 'nav-item-btn nav-link fw-bold text-start py-2.5 px-3 active bg-primary text-white shadow-sm';

            inputAssetCategory.value = cat;
            lblTargetCategoryTitle.innerText = categoryNames[cat] || cat;

            activateStep(1);
        });
    });

    // Elements
    const formUpload = document.getElementById('formUpload');
    const btnUpload = document.getElementById('btnUpload');
    const sectionStep1 = document.getElementById('sectionStep1');
    const sectionStep2 = document.getElementById('sectionStep2');
    const sectionStep3 = document.getElementById('sectionStep3');
    const sectionStep4 = document.getElementById('sectionStep4');

    const stepPill1 = document.getElementById('stepPill1');
    const stepPill2 = document.getElementById('stepPill2');
    const stepPill3 = document.getElementById('stepPill3');
    const stepPill4 = document.getElementById('stepPill4');

    const tbodyMapping = document.getElementById('tbodyMapping');
    const gridColumns = document.getElementById('gridColumns');
    const tbodyDiffSamples = document.getElementById('tbodyDiffSamples');

    // OPD Scope Selection Elements
    const radioScopeAll = document.getElementById('scopeAll');
    const radioScopeOpd = document.getElementById('scopeOpd');
    const wrapperOpdSelect = document.getElementById('wrapperOpdSelect');
    const selectOpd = document.getElementById('selectOpd');

    function toggleOpdScope() {
        if (radioScopeOpd && radioScopeOpd.checked) {
            wrapperOpdSelect.style.display = 'block';
        } else if (wrapperOpdSelect) {
            wrapperOpdSelect.style.display = 'none';
        }
    }

    if (radioScopeAll && radioScopeOpd) {
        radioScopeAll.addEventListener('change', toggleOpdScope);
        radioScopeOpd.addEventListener('change', toggleOpdScope);
    }

    function activateStep(step) {
        [sectionStep1, sectionStep2, sectionStep3, sectionStep4].forEach((sec, idx) => {
            if (idx + 1 === step) {
                sec.classList.remove('d-none');
            } else {
                sec.classList.add('d-none');
            }
        });

        [stepPill1, stepPill2, stepPill3, stepPill4].forEach((pill, idx) => {
            if (idx + 1 === step) {
                pill.className = 'p-2 rounded-3 bg-primary text-white fw-semibold step-pill';
                pill.querySelector('.badge').className = 'badge bg-white text-primary rounded-circle me-1';
            } else if (idx + 1 < step) {
                pill.className = 'p-2 rounded-3 bg-success text-white fw-semibold step-pill';
                pill.querySelector('.badge').className = 'badge bg-white text-success rounded-circle me-1';
            } else {
                pill.className = 'p-2 rounded-3 bg-light text-muted fw-semibold step-pill';
                pill.querySelector('.badge').className = 'badge bg-secondary text-white rounded-circle me-1';
            }
        });
    }

    // Step 1: Upload Form Handling
    formUpload.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(formUpload);
        
        btnUpload.disabled = true;
        btnUpload.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Mengunggah & Membaca...';

        fetch("{{ route('rekon-ebmd.upload-preview') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btnUpload.disabled = false;
            btnUpload.innerHTML = '<span>Unggah & Analisis Kolom</span> <i class="bi bi-arrow-right ms-2"></i>';

            if (!data.success) {
                Swal.fire('Gagal!', data.message || 'Terjadi kesalahan.', 'error');
                return;
            }

            previewData = data;
            importToken = data.import_token;
            document.getElementById('badgeActiveSheet').innerText = data.active_sheet_name || 'Sheet 1';

            renderMappingTable();
            renderColumnGrid();
            activateStep(2);
        })
        .catch(err => {
            btnUpload.disabled = false;
            btnUpload.innerHTML = '<span>Unggah & Analisis Kolom</span> <i class="bi bi-arrow-right ms-2"></i>';
            Swal.fire('Error!', 'Gagal menghubungi server: ' + err.message, 'error');
        });
    });

    // Render Step 2: Mapping Table (With NIBAR prioritized as key #1)
    function renderMappingTable() {
        tbodyMapping.innerHTML = '';
        const targetCols = previewData.updatable_columns || {};
        const headers = previewData.headers || [];
        const samples = previewData.samples || [];
        const suggested = previewData.suggested_mapping || {};

        // 1. Baris NIBAR (Wajib & Prioritas Utama)
        const trNibar = document.createElement('tr');
        trNibar.className = 'table-warning';

        let nibarSelectHtml = `<select class="form-select mapping-select fw-bold border-warning" data-col="nibar">`;
        nibarSelectHtml += `<option value="">-- Wajib Pilih Kolom NIBAR --</option>`;
        headers.forEach((h, idx) => {
            const isSelected = (suggested['nibar'] === idx) ? 'selected' : '';
            nibarSelectHtml += `<option value="${idx}" ${isSelected}>${h}</option>`;
        });
        nibarSelectHtml += `</select>`;

        const nibarSampleVal = (suggested['nibar'] !== undefined && samples[0]) ? (samples[0][suggested['nibar']] || '-') : '-';

        trNibar.innerHTML = `
            <td>
                <span class="badge bg-warning text-dark me-1"><i class="bi bi-key-fill"></i> KUNCI UTAMA</span>
                <strong class="text-navy">NIBAR (Nomor Induk Barang)</strong>
                <code class="small text-muted d-block">nibar</code>
            </td>
            <td>${nibarSelectHtml}</td>
            <td><span class="fw-bold font-monospace text-primary text-break">${nibarSampleVal}</span></td>
        `;
        tbodyMapping.appendChild(trNibar);

        // 2. Baris Kolom-Kolom Lainnya
        Object.keys(targetCols).forEach(dbCol => {
            const label = targetCols[dbCol];
            const tr = document.createElement('tr');

            let selectHtml = `<select class="form-select mapping-select" data-col="${dbCol}">`;
            selectHtml += `<option value="">-- Abaikan Kolom Ini --</option>`;
            
            headers.forEach((h, idx) => {
                const isSelected = (suggested[dbCol] === idx) ? 'selected' : '';
                selectHtml += `<option value="${idx}" ${isSelected}>${h}</option>`;
            });
            selectHtml += `</select>`;

            const sampleVal = (suggested[dbCol] !== undefined && samples[0]) ? (samples[0][suggested[dbCol]] || '-') : '-';

            tr.innerHTML = `
                <td><strong class="text-navy">${label}</strong> <code class="small text-muted d-block">${dbCol}</code></td>
                <td>${selectHtml}</td>
                <td><span class="text-muted small text-break">${sampleVal}</span></td>
            `;

            tbodyMapping.appendChild(tr);
        });
    }

    // Render Step 3: Column Selection Checklist Grid
    function renderColumnGrid() {
        gridColumns.innerHTML = '';
        const updatable = previewData.updatable_columns || {};

        Object.keys(updatable).forEach(dbCol => {
            const label = updatable[dbCol];
            const colDiv = document.createElement('div');
            colDiv.className = 'col-md-4';

            const colDefaultChecked = ['harga_perolehan', 'nilai_perolehan', 'luas', 'luas_lantai', 'kondisi', 'peruntukan', 'nama_aset', 'nama_bangunan', 'merk'].includes(dbCol);

            colDiv.innerHTML = `
                <div class="form-check card-select p-3 border rounded-3 bg-white hover-shadow transition">
                    <input class="form-check-input col-checkbox" type="checkbox" value="${dbCol}" id="chk_${dbCol}" ${colDefaultChecked ? 'checked' : ''}>
                    <label class="form-check-label fw-bold d-block cursor-pointer text-dark" for="chk_${dbCol}">
                        ${label}
                        <code class="d-block small text-muted font-normal">${dbCol}</code>
                    </label>
                </div>
            `;
            gridColumns.appendChild(colDiv);
        });
    }

    // Presets Handlers
    document.getElementById('presetAll').addEventListener('click', () => {
        document.querySelectorAll('.col-checkbox').forEach(c => c.checked = true);
    });
    document.getElementById('presetNone').addEventListener('click', () => {
        document.querySelectorAll('.col-checkbox').forEach(c => c.checked = false);
    });
    document.getElementById('presetNilaiKondisi').addEventListener('click', () => {
        document.querySelectorAll('.col-checkbox').forEach(c => {
            c.checked = ['nilai_perolehan', 'harga_perolehan', 'luas', 'luas_lantai', 'luas_dasar', 'kondisi'].includes(c.value);
        });
    });
    document.getElementById('presetIdentitas').addEventListener('click', () => {
        document.querySelectorAll('.col-checkbox').forEach(c => {
            c.checked = ['nama_aset', 'nama_bangunan', 'peruntukan', 'alamat', 'dasar_perolehan', 'nomor_dokumen_pbg', 'merk', 'tipe'].includes(c.value);
        });
    });

    // Step Switch Buttons
    document.getElementById('btnGoToStep3').addEventListener('click', () => {
        const nibarSelect = document.querySelector('.mapping-select[data-col="nibar"]');
        if (!nibarSelect || nibarSelect.value === '') {
            Swal.fire('Perhatian!', 'Kolom NIBAR (Nomor Induk Barang) wajib dipilih untuk melanjutkan.', 'warning');
            return;
        }
        activateStep(3);
    });
    document.getElementById('btnBackToStep1').addEventListener('click', () => activateStep(1));
    document.getElementById('btnBackToStep2').addEventListener('click', () => activateStep(2));
    document.getElementById('btnBackToStep3').addEventListener('click', () => activateStep(3));

    // Step 3 -> 4 (Diff Preview)
    const btnGoToStep4 = document.getElementById('btnGoToStep4');
    btnGoToStep4.addEventListener('click', function() {
        const selectedCols = Array.from(document.querySelectorAll('.col-checkbox:checked')).map(c => c.value);
        if (selectedCols.length === 0) {
            Swal.fire('Perhatian!', 'Pilih minimal satu kolom yang boleh di-update.', 'warning');
            return;
        }

        const mapping = {};
        document.querySelectorAll('.mapping-select').forEach(sel => {
            if (sel.value !== '') {
                mapping[sel.dataset.col] = parseInt(sel.value);
            }
        });

        if (mapping['nibar'] === undefined) {
            Swal.fire('Perhatian!', 'Kolom NIBAR wajib dipetakan.', 'warning');
            return;
        }

        const assetCategory = inputAssetCategory.value;
        document.getElementById('lblStep4CategoryName').innerText = categoryNames[assetCategory] || assetCategory;

        let selectedOpdId = null;
        if (radioScopeOpd && radioScopeOpd.checked) {
            if (!selectOpd.value) {
                Swal.fire('Perhatian!', 'Silakan pilih OPD target pada Langkah 1 terlebih dahulu.', 'warning');
                return;
            }
            selectedOpdId = parseInt(selectOpd.value);
        }

        btnGoToStep4.disabled = true;
        btnGoToStep4.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Menganalisis Perbedaan NIBAR...';

        fetch("{{ route('rekon-ebmd.diff-preview') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({
                import_token: importToken,
                selected_columns: selectedCols,
                asset_category: assetCategory,
                mapping: mapping,
                opd_id: selectedOpdId,
                header_row_index: previewData.header_row_index || 0
            })
        })
        .then(res => res.json())
        .then(data => {
            btnGoToStep4.disabled = false;
            btnGoToStep4.innerHTML = '<span>Analisis Perbedaan Data (Diff Preview)</span> <i class="bi bi-search ms-2"></i>';

            if (!data.success) {
                Swal.fire('Gagal!', data.message || 'Gagal memproses pratinjau.', 'error');
                return;
            }

            diffResultData = data;

            // Update Cakupan OPD Badge
            const lblStep4OpdScope = document.getElementById('lblStep4OpdScope');
            if (lblStep4OpdScope) {
                if (data.opd_name) {
                    lblStep4OpdScope.className = 'badge bg-warning-subtle text-warning-emphasis fw-semibold';
                    lblStep4OpdScope.innerHTML = '<i class="bi bi-building me-1"></i>OPD: ' + data.opd_name;
                } else {
                    lblStep4OpdScope.className = 'badge bg-primary-subtle text-primary fw-semibold';
                    lblStep4OpdScope.innerHTML = '<i class="bi bi-globe2 me-1"></i>Seluruh OPD';
                }
            }

            // Render 7 Metrics
            document.getElementById('statTotalRows').innerText = data.total_ebmd;
            document.getElementById('statMatchedCount').innerText = data.nibar_matched;
            document.getElementById('statIdenticalCount').innerText = data.identical_count;
            document.getElementById('statChangedCount').innerText = data.changed_count;
            document.getElementById('statNotFoundSipat').innerText = data.not_found_sipat;
            document.getElementById('statDuplicateCount').innerText = data.duplicate_count;
            document.getElementById('statInvalidCount').innerText = data.invalid_count;

            // Update Counts in Filter Buttons
            updateFilterCounts(data);

            // Field Breakdown
            const containerBreakdown = document.getElementById('containerFieldBreakdown');
            containerBreakdown.innerHTML = '';
            if (data.field_breakdown && data.field_breakdown.length > 0) {
                data.field_breakdown.forEach(function(item) {
                    const badge = document.createElement('div');
                    badge.className = 'p-2 px-3 bg-white border rounded-3 shadow-sm d-flex align-items-center gap-2';
                    badge.innerHTML = '<span class="fw-semibold text-dark"><i class="bi bi-tag-fill me-1 text-warning"></i>' + item.label + '</span><span class="badge bg-warning text-dark font-monospace fs-6">' + item.count + ' Aset Berbeda</span>';
                    containerBreakdown.appendChild(badge);
                });
            } else {
                containerBreakdown.innerHTML = '<span class="text-muted small">Tidak ada perbedaan data pada kolom yang Anda pilih.</span>';
            }

            // Summary Execution Box
            document.getElementById('summaryWillUpdate').innerText = data.changed_count;
            document.getElementById('summaryWillIdentical').innerText = data.identical_count;
            document.getElementById('summaryWillSkip').innerText = (data.not_found_sipat + data.duplicate_count + data.invalid_count);
            document.getElementById('summarySelectedColsText').innerText = 'Kolom Update: [' + selectedCols.join(', ') + ']';

            // Render Table
            renderDiffTable();
            activateStep(4);
        })
        .catch(err => {
            btnGoToStep4.disabled = false;
            btnGoToStep4.innerHTML = '<span>Analisis Perbedaan Data (Diff Preview)</span> <i class="bi bi-search ms-2"></i>';
            Swal.fire('Error!', 'Gagal memproses pratinjau: ' + err.message, 'error');
        });
    });

    function updateFilterCounts(data) {
        const items = data.items || [];
        document.getElementById('cntFilterAll').innerText = items.length;
        document.getElementById('cntFilterIdentical').innerText = data.identical_count;
        document.getElementById('cntFilterChanged').innerText = data.changed_count;
        document.getElementById('cntFilterOnlyEbmd').innerText = data.not_found_sipat;
        document.getElementById('cntFilterOnlySipat').innerText = data.only_in_sipat;
        document.getElementById('cntFilterDuplicate').innerText = data.duplicate_count;
        document.getElementById('cntFilterInvalid').innerText = data.invalid_count;
    }

    // Filter Buttons Handler
    document.querySelectorAll('#filterStatusGroup button').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('#filterStatusGroup button').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            renderDiffTable();
        });
    });

    // Search Input Handler
    document.getElementById('searchTable').addEventListener('input', function(e) {
        currentSearch = e.target.value.toLowerCase().trim();
        renderDiffTable();
    });

    // Render Table based on Filter & Search
    function renderDiffTable() {
        if (!diffResultData || !diffResultData.items) return;

        tbodyDiffSamples.innerHTML = '';
        const items = diffResultData.items;

        const filtered = items.filter(item => {
            // Status filter
            if (currentFilter !== 'ALL' && item.status !== currentFilter) {
                return false;
            }
            // Text search
            if (currentSearch !== '') {
                const nibarMatch = item.nibar.toLowerCase().includes(currentSearch);
                const nameMatch = item.name.toLowerCase().includes(currentSearch);
                const opdMatch = (item.opd || '').toLowerCase().includes(currentSearch);
                return nibarMatch || nameMatch || opdMatch;
            }
            return true;
        });

        if (filtered.length === 0) {
            tbodyDiffSamples.innerHTML = '<tr><td colspan="4" class="text-center py-5 text-muted"><i class="bi bi-info-circle fs-2 d-block mb-2"></i>Tidak ada data yang sesuai dengan filter atau pencarian Anda.</td></tr>';
            return;
        }

        // Limit render to first 100 for smooth DOM performance
        const displayItems = filtered.slice(0, 100);

        displayItems.forEach(item => {
            const tr = document.createElement('tr');
            tr.className = 'align-top';

            // 1. NIBAR Column
            let cellNibar = `<div class="fw-bold font-monospace text-primary fs-6">${item.nibar}</div>`;
            if (item.row_num !== '-') {
                cellNibar += `<span class="badge bg-dark bg-opacity-75 font-monospace small">Baris Excel: ${item.row_num}</span>`;
            }

            // 2. Name & OPD Column
            let cellName = `<div class="fw-bold text-navy mb-1">${item.name}</div>`;
            if (item.opd && item.opd !== '-') {
                cellName += `<span class="badge bg-light text-dark border me-1"><i class="bi bi-building me-1"></i>${item.opd}</span>`;
            }
            if (item.sub_opd && item.sub_opd !== '-') {
                cellName += `<span class="badge bg-secondary-subtle text-secondary-emphasis"><i class="bi bi-geo-alt me-1"></i>${item.sub_opd}</span>`;
            }

            // 3. Status Badge Column
            let badgeClass = 'bg-secondary';
            if (item.status === 'IDENTICAL') badgeClass = 'bg-success';
            if (item.status === 'CHANGED') badgeClass = 'bg-warning text-dark';
            if (item.status === 'ONLY_IN_EBMD') badgeClass = 'bg-info text-dark';
            if (item.status === 'ONLY_IN_SIPAT') badgeClass = 'bg-secondary';
            if (item.status === 'DUPLICATE_KEY') badgeClass = 'bg-danger';
            if (item.status === 'INVALID') badgeClass = 'bg-dark';

            let cellStatus = `<span class="badge ${badgeClass} px-2.5 py-1.5 fw-semibold d-inline-block">${item.status_label}</span>`;
            if (item.notes) {
                cellStatus += `<div class="small text-muted mt-1 lh-sm">${item.notes}</div>`;
            }

            // 4. Differences Details Column
            let cellDetails = '';
            if (item.status === 'CHANGED' && item.changed_columns && item.changed_columns.length > 0) {
                cellDetails += '<div class="table-responsive border rounded-2 p-1 bg-white"><table class="table table-sm table-bordered mb-0 small">';
                cellDetails += '<thead class="table-light"><tr><th>Kolom</th><th>Nilai SIPAT</th><th>Nilai e-BMD</th></tr></thead><tbody>';
                item.changed_columns.forEach(diff => {
                    cellDetails += `<tr>
                        <td class="fw-semibold text-dark">${diff.label}</td>
                        <td class="text-danger font-monospace"><s>${diff.old}</s></td>
                        <td class="text-success fw-bold font-monospace">${diff.new}</td>
                    </tr>`;
                });
                cellDetails += '</tbody></table></div>';
            } else if (item.status === 'IDENTICAL') {
                cellDetails = '<span class="text-success small fw-medium"><i class="bi bi-check2-all me-1"></i>Seluruh kolom yang dipilih identik dengan database.</span>';
            } else if (item.status === 'ONLY_IN_EBMD') {
                cellDetails = '<span class="text-info small fw-medium"><i class="bi bi-plus-circle me-1"></i>Aset belum terdaftar di SIPAT.</span>';
            } else if (item.status === 'ONLY_IN_SIPAT') {
                cellDetails = '<span class="text-secondary small fw-medium"><i class="bi bi-eye me-1"></i>Aset hanya tercatat di database SIPAT.</span>';
            } else if (item.status === 'DUPLICATE_KEY') {
                cellDetails = '<span class="text-danger small fw-medium"><i class="bi bi-exclamation-triangle-fill me-1"></i>NIBAR ambigu / terdaftar lebih dari 1 kali.</span>';
            } else {
                cellDetails = '<span class="text-muted small">-</span>';
            }

            tr.innerHTML = `
                <td class="py-3">${cellNibar}</td>
                <td class="py-3">${cellName}</td>
                <td class="py-3">${cellStatus}</td>
                <td class="py-3">${cellDetails}</td>
            `;

            tbodyDiffSamples.appendChild(tr);
        });

        if (filtered.length > 100) {
            const trMore = document.createElement('tr');
            trMore.innerHTML = `<td colspan="4" class="text-center py-3 bg-light text-muted small"><i class="bi bi-info-circle me-1"></i>Menampilkan 100 dari ${filtered.length} baris. Gunakan pencarian atau ekspor CSV untuk melihat seluruh data.</td>`;
            tbodyDiffSamples.appendChild(trMore);
        }
    }

    // Export CSV Handler
    document.getElementById('btnExportCsv').addEventListener('click', function() {
        if (!importToken) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = "{{ route('rekon-ebmd.export-preview') }}";
        form.target = '_blank';

        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = 'import_token';
        tokenInput.value = importToken;
        form.appendChild(tokenInput);

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '{{ csrf_token() }}';
        form.appendChild(csrfInput);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });

    // Step 4: Execute Sync
    const btnExecuteSync = document.getElementById('btnExecuteSync');
    btnExecuteSync.addEventListener('click', function() {
        if (!diffResultData) return;

        const selectedCols = diffResultData.selected_columns || [];
        const mapping = {};
        document.querySelectorAll('.mapping-select').forEach(sel => {
            if (sel.value !== '') {
                mapping[sel.dataset.col] = parseInt(sel.value);
            }
        });

        const assetCategory = inputAssetCategory.value;
        const createNew = document.getElementById('switchCreateNew').checked;
        const changedCount = diffResultData.changed_count || 0;

        const opdScopeLabel = diffResultData.opd_name ? `OPD: ${diffResultData.opd_name}` : 'Seluruh OPD (Semua Data Daerah)';

        Swal.fire({
            title: 'Konfirmasi Eksekusi Rekonsiliasi',
            html: `Apakah Anda yakin ingin mengeksekusi Pembaruan Data e-BMD untuk kategori <strong>${categoryNames[assetCategory]}</strong>?<br><br>
                   <div class="text-start p-3 bg-light rounded border small">
                       <div>• Cakupan Wilayah: <strong class="text-primary">${opdScopeLabel}</strong></div>
                       <div>• Sebanyak <strong>${changedCount} aset</strong> dengan status <em>BERUBAH</em> akan diperbarui.</div>
                       <div>• Sebanyak <strong>${selectedCols.length} kolom terpilih</strong> akan di-update: <code>[${selectedCols.join(', ')}]</code>.</div>
                       <div>• NIBAR yang <em>Duplikat</em> atau <em>Invalid</em> akan otomatis dilindungi/dilewati.</div>
                       <div>• Transaksi dilindungi oleh <em>Atomic Database Transaction & Audit Trail</em>.</div>
                   </div>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Eksekusi Sekarang!',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#198754'
        }).then((result) => {
            if (result.isConfirmed) {
                btnExecuteSync.disabled = true;
                btnExecuteSync.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Memproses Transaksi Database...';

                fetch("{{ route('rekon-ebmd.execute') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        import_token: importToken,
                        selected_columns: selectedCols,
                        asset_category: assetCategory,
                        mapping: mapping,
                        create_new: createNew,
                        opd_id: (diffResultData && diffResultData.opd_id) ? diffResultData.opd_id : null,
                        header_row_index: previewData.header_row_index || 0
                    })
                })
                .then(async res => {
                    const contentType = res.headers.get('content-type') || '';
                    if (!contentType.includes('application/json')) {
                        if (res.status === 419) {
                            throw new Error('Sesi halaman telah kadaluwarsa (419). Silakan muat ulang halaman (F5) dan coba kembali.');
                        }
                        throw new Error('Server mengembalikan respon non-JSON (' + res.status + ').');
                    }
                    return res.json();
                })
                .then(data => {
                    btnExecuteSync.disabled = false;
                    btnExecuteSync.innerHTML = '<i class="bi bi-play-circle-fill me-2"></i> Eksekusi Rekonsiliasi & Sync Database';

                    if (!data.success) {
                        Swal.fire('Gagal!', data.message || 'Gagal mengeksekusi rekonsiliasi.', 'error');
                        return;
                    }

                    Swal.fire({
                        title: 'Rekonsiliasi Berhasil!',
                        html: `${data.message}<br><br><small class="text-muted">Batch Correlation ID: <code>${data.correlation_id}</code></small>`,
                        icon: 'success',
                        confirmButtonText: 'Selesai'
                    }).then(() => {
                        window.location.reload();
                    });
                })
                .catch(err => {
                    btnExecuteSync.disabled = false;
                    btnExecuteSync.innerHTML = '<i class="bi bi-play-circle-fill me-2"></i> Eksekusi Rekonsiliasi & Sync Database';
                    Swal.fire('Error!', err.message || 'Terjadi kesalahan sistem.', 'error');
                });
            }
        });
    });
});
</script>
@endpush
@endsection
