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
                </div>
                <h2 class="fw-bold mb-1 text-navy">Rekonsiliasi & Sinkronisasi e-BMD Kemendagri</h2>
                <p class="text-secondary small mb-0">Impor massal & pembaruan kolom terpilih (*Selective Column Update*) untuk seluruh kategori aset daerah.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('sipat.aset.index') }}" class="btn btn-light border fw-medium shadow-sm">
                    <i class="bi bi-geo-alt me-1"></i> Aset Tanah
                </a>
                <a href="{{ route('vehicles.index') }}" class="btn btn-light border fw-medium shadow-sm">
                    <i class="bi bi-car-front me-1"></i> Kendaraan
                </a>
            </div>
        </div>
    </div>

    <!-- Nav Tabs Kategori Aset -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
        <div class="card-body p-2 bg-light">
            <ul class="nav nav-pills nav-fill gap-2" id="assetCategoryTabs">
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
            </ul>
        </div>
    </div>

    <!-- Alert / Banner Info Perlindungan Data -->
    <div class="alert alert-info border-0 shadow-sm rounded-3 d-flex align-items-start gap-3 p-3.5 mb-4">
        <div class="rounded-circle bg-info bg-opacity-20 p-2 text-info d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
            <i class="bi bi-shield-check fs-4"></i>
        </div>
        <div>
            <h6 class="fw-bold mb-1 text-info-emphasis">Sistem Perlindungan Data Lokal Terpadu Active</h6>
            <p class="small text-secondary mb-0">
                Fitur ini memungkinkan Anda memilih secara spesifik kolom mana saja dari e-BMD Kemendagri yang ingin diperbarui pada kategori <strong id="lblActiveCategoryName">Kendaraan Dinas</strong>. 
                Data lokal seperti <strong>Pemetaan Sub OPD (Sekolah & Puskesmas)</strong>, <strong>Koordinat & Poligon Peta GIS</strong>, <strong>Foto Aset</strong>, dan <strong>Catatan Internal</strong> tidak akan pernah tersentuh/tertimpa selama kolom tersebut tidak dicentang.
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
                            <label class="form-label fw-bold text-navy"><i class="bi bi-tag-fill me-1 text-primary"></i> Target Modul Aset Selected:</label>
                            <div class="fs-5 fw-bold text-primary mb-1" id="lblTargetCategoryTitle">Kendaraan Real (eRANDIS)</div>
                            <p class="small text-muted mb-0">File Excel akan dicocokkan dan diperbarui secara selektif ke modul database target ini.</p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">File Excel / CSV Ekspor e-BMD <span class="text-danger">*</span></label>
                        <input type="file" class="form-control form-control-lg" name="file" id="inputFile" accept=".xlsx,.xls,.csv" required>
                        <small class="text-muted mt-1 d-block"><i class="bi bi-info-circle me-1"></i>Format yang didukung: .xlsx, .xls, .csv (Maksimal 10 MB)</small>
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
            <h5 class="fw-bold text-navy mb-0"><i class="bi bi-key-fill text-warning me-2"></i>Langkah 2: Kunci Acuan & Pemetaan Kolom</h5>
            <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill" id="badgeActiveSheet">Sheet 1</span>
        </div>
        <div class="card-body p-4">
            <!-- Choice of Matching Key -->
            <div class="alert alert-warning border-0 shadow-sm mb-4">
                <div class="row align-items-center g-3">
                    <div class="col-md-7">
                        <h6 class="fw-bold text-warning-emphasis mb-1"><i class="bi bi-key me-1"></i>Tentukan Kolom Kunci Acuan (*Unique Identifier*)</h6>
                        <p class="small text-secondary mb-0">Kolom ini digunakan oleh sistem untuk menemukan data aset yang sama antara file Excel dan Database SIPAT.</p>
                    </div>
                    <div class="col-md-5">
                        <select class="form-select form-select-lg fw-bold border-warning" id="selectMatchingKey">
                            <!-- Dynamic options -->
                        </select>
                    </div>
                </div>
            </div>

            <!-- Mapping Table -->
            <h6 class="fw-bold mb-3"><i class="bi bi-table me-1"></i>Pemetaan Kolom Excel ke Database Target:</h6>
            <div class="table-responsive mb-4">
                <table class="table table-hover align-middle border">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 30%;">Kolom Database Target</th>
                            <th style="width: 45%;">Kolom Excel Asli</th>
                            <th style="width: 25%;">Contoh Nilai Data</th>
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
                    <i class="bi bi-fingerprint me-1"></i> Identitas & Kode Saja
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
                    <input class="form-check-input" type="checkbox" id="switchCreateNew" checked>
                    <label class="form-check-label fw-bold text-dark cursor-pointer" for="switchCreateNew">
                        Tambahkan Data Baru Jika Kunci Acuan Tidak Ditemukan di Database
                    </label>
                    <small class="d-block text-muted">Jika dicentang, baris Excel yang tidak cocok dengan data di database akan dimasukkan sebagai data baru.</small>
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
        <div class="card-header bg-white py-3 border-bottom-0">
            <h5 class="fw-bold text-navy mb-0"><i class="bi bi-speedometer2 text-info me-2"></i>Langkah 4: Pratinjau Perbedaan Data & Eksekusi</h5>
        </div>
        <div class="card-body p-4">
            <!-- Stats Counters -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 bg-primary bg-opacity-10 rounded-3 p-3">
                        <div class="small text-primary fw-bold text-uppercase">Total Baris Excel</div>
                        <div class="fs-2 fw-bold text-primary font-monospace" id="statTotalRows">0</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-success bg-opacity-10 rounded-3 p-3">
                        <div class="small text-success fw-bold text-uppercase">Cocok di Database</div>
                        <div class="fs-2 fw-bold text-success font-monospace" id="statMatchedCount">0</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-warning bg-opacity-10 rounded-3 p-3">
                        <div class="small text-warning-emphasis fw-bold text-uppercase">Mengalami Perubahan Data</div>
                        <div class="fs-2 fw-bold text-warning-emphasis font-monospace" id="statChangedCount">0</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-info bg-opacity-10 rounded-3 p-3">
                        <div class="small text-info fw-bold text-uppercase">Data Baru (Unmatched)</div>
                        <div class="fs-2 fw-bold text-info font-monospace" id="statNewCount">0</div>
                    </div>
                </div>
            </div>

            <!-- Rincian Utama Perbedaan per Atribut/Kolom -->
            <div class="card border-0 bg-light rounded-3 p-3.5 mb-4 border shadow-sm">
                <h6 class="fw-bold text-navy mb-2.5 d-flex align-items-center">
                    <i class="bi bi-pie-chart-fill text-warning me-2 fs-5"></i> Rincian Utama Perbedaan Data per Atribut / Kolom:
                </h6>
                <div class="d-flex align-items-center gap-2 flex-wrap" id="containerFieldBreakdown">
                    <span class="text-muted small">Memuat rincian perbedaan...</span>
                </div>
            </div>

            <!-- Diff Preview Samples Table -->
            <h6 class="fw-bold mb-3"><i class="bi bi-list-nested me-1"></i>Sampel Perubahan Data (Nilai Lama vs Nilai Baru):</h6>
            <div class="table-responsive mb-4 border rounded-3">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 8%;">Baris</th>
                            <th style="width: 22%;">Kunci / Nama Aset</th>
                            <th style="width: 20%;">Kolom Terpilih</th>
                            <th style="width: 25%;">Nilai Lama (SIPAT)</th>
                            <th style="width: 25%;">Nilai Baru (e-BMD)</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyDiffSamples">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary px-4" id="btnBackToStep3">
                    <i class="bi bi-arrow-left me-1"></i> Ubah Pilihan Kolom
                </button>
                <button type="button" class="btn btn-success px-5 py-3 fw-bold fs-6 shadow" id="btnExecuteSync">
                    <i class="bi bi-play-circle-fill me-2"></i> Eksekusi Rekonsiliasi & Sync Data
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

    const categoryNames = {
        'vehicle': 'Kendaraan Real (eRANDIS)',
        'ebmd_vehicle': 'Master e-BMD Kendaraan',
        'tanah': 'Aset Tanah (SIPAT)',
        'bangunan': 'Gedung & Bangunan (SIPAT)'
    };

    // Category Tabs Switching
    const categoryTabs = document.querySelectorAll('.nav-item-btn');
    const inputAssetCategory = document.getElementById('inputAssetCategory');
    const lblTargetCategoryTitle = document.getElementById('lblTargetCategoryTitle');
    const lblActiveCategoryName = document.getElementById('lblActiveCategoryName');

    categoryTabs.forEach(btn => {
        btn.addEventListener('click', function() {
            const cat = this.dataset.category;

            categoryTabs.forEach(b => {
                b.className = 'nav-item-btn nav-link fw-bold text-start py-2.5 px-3 text-dark bg-white';
            });
            this.className = 'nav-item-btn nav-link fw-bold text-start py-2.5 px-3 active bg-primary text-white shadow-sm';

            inputAssetCategory.value = cat;
            lblTargetCategoryTitle.innerText = categoryNames[cat] || cat;
            lblActiveCategoryName.innerText = categoryNames[cat] || cat;

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

    const selectMatchingKey = document.getElementById('selectMatchingKey');
    const tbodyMapping = document.getElementById('tbodyMapping');
    const gridColumns = document.getElementById('gridColumns');
    const tbodyDiffSamples = document.getElementById('tbodyDiffSamples');

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

            renderMatchingKeys();
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

    // Render Matching Keys Dropdown
    function renderMatchingKeys() {
        selectMatchingKey.innerHTML = '';
        const keys = previewData.matching_keys || {};
        Object.keys(keys).forEach(k => {
            const opt = document.createElement('option');
            opt.value = k;
            opt.innerText = keys[k];
            selectMatchingKey.appendChild(opt);
        });
    }

    // Render Step 2: Mapping Table
    function renderMappingTable() {
        tbodyMapping.innerHTML = '';
        const targetCols = previewData.updatable_columns || {};
        const headers = previewData.headers || [];
        const samples = previewData.samples || [];
        const suggested = previewData.suggested_mapping || {};

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

            const colDefaultChecked = ['nilai_perolehan', 'harga_perolehan', 'luas', 'kondisi', 'merk', 'tipe', 'pemegang', 'luas_lantai'].includes(dbCol);

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
            c.checked = ['nilai_perolehan', 'harga_perolehan', 'luas', 'luas_lantai', 'kondisi'].includes(c.value);
        });
    });
    document.getElementById('presetIdentitas').addEventListener('click', () => {
        document.querySelectorAll('.col-checkbox').forEach(c => {
            c.checked = ['kode_barang', 'kode_aset', 'kode_bangunan', 'nama_aset', 'nama_bangunan', 'merk', 'tipe', 'nibar'].includes(c.value);
        });
    });

    // Step Switch Buttons
    document.getElementById('btnGoToStep3').addEventListener('click', () => activateStep(3));
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

        const matchingKey = selectMatchingKey.value;
        const assetCategory = inputAssetCategory.value;

        btnGoToStep4.disabled = true;
        btnGoToStep4.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Menganalisis Perbedaan Data...';

        fetch("{{ route('rekon-ebmd.diff-preview') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({
                import_token: importToken,
                matching_key: matchingKey,
                selected_columns: selectedCols,
                asset_category: assetCategory,
                mapping: mapping,
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

            document.getElementById('statTotalRows').innerText = data.total_rows;
            document.getElementById('statMatchedCount').innerText = data.matched_count;
            document.getElementById('statChangedCount').innerText = data.changed_count;
            document.getElementById('statNewCount').innerText = data.new_count;

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
                containerBreakdown.innerHTML = '<span class="text-muted small">Tidak ada rincian perbedaan atribut.</span>';
            }

            tbodyDiffSamples.innerHTML = '';
            if (!data.diff_samples || data.diff_samples.length === 0) {
                tbodyDiffSamples.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted"><i class="bi bi-check-circle-fill text-success fs-1 d-block mb-2"></i><strong>Tidak Ditemukan Perbedaan Data</strong><div class="small">Seluruh kolom yang Anda pilih pada file e-BMD sudah cocok persis dengan database SIPAT.</div></td></tr>';
            } else {
                const colLabels = previewData.updatable_columns || {};
                data.diff_samples.forEach(function(sample) {
                    if (sample.differences && sample.differences.length > 0) {
                        sample.differences.forEach(function(diff, dIdx) {
                            const tr = document.createElement('tr');
                            
                            const cellRow = (dIdx === 0) ? '<span class="badge bg-dark bg-opacity-75 font-monospace px-2.5 py-1">Row ' + sample.row + '</span>' : '';
                            
                            let cellName = '';
                            if (dIdx === 0) {
                                cellName = '<div class="fw-bold text-navy mb-1.5 fs-6">' + sample.name + '</div>';
                                cellName += '<div class="d-flex align-items-center gap-1.5 flex-wrap">';
                                cellName += '<code class="small text-muted bg-light px-2 py-0.5 rounded border me-1"><i class="bi bi-key me-1"></i>' + sample.key + '</code>';
                                if (sample.opd) {
                                    cellName += '<span class="badge bg-info-subtle text-info-emphasis me-1"><i class="bi bi-building me-1"></i>' + sample.opd + '</span>';
                                }
                                if (sample.sub_opd) {
                                    cellName += '<span class="badge bg-secondary-subtle text-secondary-emphasis"><i class="bi bi-geo-alt me-1"></i>' + sample.sub_opd + '</span>';
                                }
                                cellName += '</div>';
                            }

                            const colTitle = colLabels[diff.column] || diff.column;
                            const cellCol = '<div class="fw-semibold text-dark mb-0.5">' + colTitle + '</div><code class="small text-muted font-normal">' + diff.column + '</code>';
                            const cellOld = '<div class="p-2 bg-danger bg-opacity-10 rounded-3 border border-danger border-opacity-25 text-danger font-monospace small"><i class="bi bi-x-circle me-1"></i><s>' + diff.old + '</s></div>';
                            const cellNew = '<div class="p-2 bg-success bg-opacity-10 rounded-3 border border-success border-opacity-25 text-success fw-bold font-monospace small"><i class="bi bi-check-circle me-1"></i>' + diff.new + '</div>';

                            if (dIdx === 0) {
                                tr.style.borderTop = '2px solid #dee2e6';
                            }
                            tr.className = 'align-middle';
                            tr.innerHTML = '<td class="align-top py-3">' + cellRow + '</td>' +
                                           '<td class="align-top py-3">' + cellName + '</td>' +
                                           '<td class="align-middle py-3">' + cellCol + '</td>' +
                                           '<td class="align-middle py-3">' + cellOld + '</td>' +
                                           '<td class="align-middle py-3">' + cellNew + '</td>';
                            tbodyDiffSamples.appendChild(tr);
                        });
                    }
                });
            }

            activateStep(4);
        })
        .catch(err => {
            btnGoToStep4.disabled = false;
            btnGoToStep4.innerHTML = '<span>Analisis Perbedaan Data (Diff Preview)</span> <i class="bi bi-search ms-2"></i>';
            Swal.fire('Error!', 'Gagal memproses pratinjau: ' + err.message, 'error');
        });
    });

    // Step 4: Execute Sync
    const btnExecuteSync = document.getElementById('btnExecuteSync');
    btnExecuteSync.addEventListener('click', function() {
        const selectedCols = Array.from(document.querySelectorAll('.col-checkbox:checked')).map(c => c.value);
        const mapping = {};
        document.querySelectorAll('.mapping-select').forEach(sel => {
            if (sel.value !== '') {
                mapping[sel.dataset.col] = parseInt(sel.value);
            }
        });

        const matchingKey = selectMatchingKey.value;
        const assetCategory = inputAssetCategory.value;
        const createNew = document.getElementById('switchCreateNew').checked;

        Swal.fire({
            title: 'Konfirmasi Eksekusi',
            html: `Apakah Anda yakin ingin mengeksekusi Pembaruan Selektif e-BMD untuk kategori <strong>${categoryNames[assetCategory]}</strong>?<br><br><strong>${selectedCols.length} Kolom</strong> akan diperbarui pada database. Kolom lainnya tetap utuh.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Eksekusi Sekarang!',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#198754'
        }).then((result) => {
            if (result.isConfirmed) {
                btnExecuteSync.disabled = true;
                btnExecuteSync.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Memproses Update Database...';

                fetch("{{ route('rekon-ebmd.execute') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        import_token: importToken,
                        matching_key: matchingKey,
                        selected_columns: selectedCols,
                        asset_category: assetCategory,
                        mapping: mapping,
                        create_new: createNew,
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
                    btnExecuteSync.innerHTML = '<i class="bi bi-play-circle-fill me-2"></i> Eksekusi Rekonsiliasi & Sync Data';

                    if (!data.success) {
                        const errMsg = (typeof data.message === 'string' && data.message.length > 0)
                            ? data.message
                            : 'Sesi impor telah kadaluwarsa. Silakan muat ulang halaman (F5) dan unggah kembali berkas Excel Anda.';
                        Swal.fire('Perhatian', errMsg, 'warning');
                        return;
                    }

                    Swal.fire({
                        title: 'Berhasil!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'Selesai'
                    }).then(() => {
                        window.location.reload();
                    });
                })
                .catch(err => {
                    btnExecuteSync.disabled = false;
                    btnExecuteSync.innerHTML = '<i class="bi bi-play-circle-fill me-2"></i> Eksekusi Rekonsiliasi & Sync Data';
                    Swal.fire('Error!', err.message || 'Terjadi kesalahan sistem.', 'error');
                });
            }
        });
    });
});
</script>
@endpush
@endsection

