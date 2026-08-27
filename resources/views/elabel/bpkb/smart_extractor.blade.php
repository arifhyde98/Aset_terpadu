@extends('layouts.app')

@section('title', 'Smart BPKB PDF Extractor (Local Folder Scanner)')

@section('content')
<div class="container-fluid px-0">
    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.bpkb.index') }}" class="text-decoration-none text-secondary">BPKB</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Folder Scanner</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-navy mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-folder2-open text-primary"></i> Smart BPKB PDF Folder Scanner
            </h3>
            <p class="text-secondary small mb-0">Pemindaian langsung isi folder PDF lokal server/PC dengan pemisahan kategori Motor (R2) & Mobil (R4)</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('elabel.bpkb.index') }}" class="btn btn-outline-secondary shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Kembali ke Data BPKB
            </a>
        </div>
    </div>

    <!-- METRICS OVERVIEW CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="admin-card p-3 d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3">
                    <i class="bi bi-journal-bookmark-fill fs-3"></i>
                </div>
                <div>
                    <small class="text-secondary d-block">Total Record BPKB</small>
                    <h4 class="fw-bold text-dark mb-0">{{ number_format($totalBpkb) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="admin-card p-3 d-flex align-items-center gap-3">
                <div class="bg-success bg-opacity-10 text-success p-3 rounded-3">
                    <i class="bi bi-file-earmark-check-fill fs-3"></i>
                </div>
                <div>
                    <small class="text-secondary d-block">BPKB Sudah Punya PDF</small>
                    <h4 class="fw-bold text-success mb-0">{{ number_format($bpkbWithPdf) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="admin-card p-3 d-flex align-items-center gap-3">
                <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-3">
                    <i class="bi bi-file-earmark-x-fill fs-3"></i>
                </div>
                <div>
                    <small class="text-secondary d-block">BPKB Belum Ada PDF</small>
                    <h4 class="fw-bold text-warning mb-0">{{ number_format($bpkbWithoutPdf) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- DIRECTORY PATH SCANNER PANEL WITH MOTOR / MOBIL SELECTOR -->
    <div class="admin-card p-4 mb-4 shadow-sm border-0">
        <h5 class="fw-bold text-navy mb-3"><i class="bi bi-hdd-rack-fill me-2 text-primary"></i> Pindai Folder PDF di Komputer / Server</h5>
        
        <form id="scanForm">
            @csrf
            <div class="row align-items-end g-3">
                <div class="col-lg-3 col-md-4">
                    <label class="form-label small fw-bold text-body">Kategori Kendaraan <span class="text-danger">*</span></label>
                    <select name="vehicle_type" id="vehicleTypeSelect" class="form-select fw-semibold">
                        <option value="ALL" {{ $vehicleType === null ? 'selected' : '' }}>🚙 Semua Jenis (Motor & Mobil)</option>
                        <option value="R4" {{ $vehicleType === 'R4' ? 'selected' : '' }}>🚗 Mobil (R4)</option>
                        <option value="R2" {{ $vehicleType === 'R2' ? 'selected' : '' }}>🏍️ Motor (R2)</option>
                    </select>
                </div>
                <div class="col-lg-6 col-md-8">
                    <label class="form-label small fw-bold text-body">Lokasi Jalur Folder PDF Scan BPKB <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-folder-symlink-fill text-secondary"></i></span>
                        <input type="text" name="folder_path" id="folderPathInput" class="form-control font-monospace" value="{{ $defaultFolder }}" required>
                    </div>
                </div>
                <div class="col-lg-3 col-md-12 text-lg-end">
                    <button type="submit" class="btn btn-primary px-4 py-2.5 rounded-3 fw-bold shadow-sm d-inline-flex align-items-center gap-2 w-100 justify-content-center" id="btnScanTrigger">
                        <i class="bi bi-search"></i> Pindai & Audit Folder
                    </button>
                </div>
            </div>
            <div class="form-text small mt-2">Masukkan lokasi jalur folder lokal (Contoh: <code>{{ $defaultFolder }}</code> atau <code>/home/arif/Dokumen/BPKB/</code>).</div>
        </form>

        <!-- SCAN PROGRESS LOADING OVERLAY -->
        <div class="d-none text-center py-4" id="scanLoadingState">
            <div class="spinner-border text-primary mb-2" role="status" style="width: 2.5rem; height: 2.5rem;"></div>
            <h6 class="fw-bold text-navy mb-1">Sedang Membaca Isi Berkas PDF di Folder Lokal...</h6>
            <p class="text-secondary small mb-0">Mengekstrak Plat Nomor dari dalam dokumen, mengecek presisi 100%, dan memverifikasi aturan ganda...</p>
            <p class="text-muted small mt-2 mb-0 fw-medium" id="scanElapsedTimer">Waktu berjalan: 0 detik</p>
        </div>
    </div>

    <!-- AUDIT RESULT CONTAINER -->
    <div class="d-none" id="auditResultContainer">
        <!-- AUDIT SUMMARY BAR -->
        <div class="admin-card p-4 mb-4 border-primary border-opacity-25 bg-primary bg-opacity-10">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold text-navy mb-1"><i class="bi bi-clipboard-data-fill text-primary me-2"></i> Hasil Audit Pratinjau Pemindaian Folder</h5>
                    <p class="text-secondary small mb-0">Total <strong id="sumTotalCount">0</strong> file PDF ditemukan di folder. Periksa daftar berikut sebelum menyimpan ke database.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-secondary px-3 py-2 rounded-pill fw-bold d-inline-flex align-items-center gap-1 shadow-sm" id="btnExportCSV" onclick="exportAuditToCSV()">
                        <i class="bi bi-download"></i> Export Audit (CSV)
                    </button>
                    <button type="button" class="btn btn-outline-danger px-3 py-2 rounded-pill fw-bold d-inline-flex align-items-center gap-1 shadow-sm" id="btnResetAudit">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset Hasil
                    </button>
                    <button type="button" class="btn btn-success px-4 py-2 rounded-pill fw-bold shadow-sm d-inline-flex align-items-center gap-2" id="btnExecuteTrigger">
                        <i class="bi bi-check-circle-fill"></i> Simpan Penautan <span id="btnValidCountBadge" class="badge bg-white text-success rounded-pill ms-1">0</span> Berkas Valid
                    </button>
                </div>
            </div>

            <!-- TABS STATS BADGES -->
            <div class="d-flex gap-2 flex-wrap mt-3">
                <button class="btn btn-sm btn-success fw-bold px-3 py-1.5 rounded-pill shadow-none active" id="tabValidBtn" onclick="showTab('valid')">
                    🟢 Valid & Siap Ditautkan (<span id="countValid">0</span>)
                </button>
                <button class="btn btn-sm btn-outline-warning fw-bold px-3 py-1.5 rounded-pill shadow-none" id="tabDuplicateBtn" onclick="showTab('duplicate')">
                    🟡 Berkas Ganda (<span id="countDuplicate">0</span>)
                </button>
                <button class="btn btn-sm btn-outline-info fw-bold px-3 py-1.5 rounded-pill shadow-none" id="tabExistsBtn" onclick="showTab('exists')">
                    🔵 Sudah Punya PDF di DB (<span id="countExists">0</span>)
                </button>
                <button class="btn btn-sm btn-outline-danger fw-bold px-3 py-1.5 rounded-pill shadow-none" id="tabUnmatchedBtn" onclick="showTab('unmatched')">
                    🔴 Nopol Tidak Ditemukan / Tidak Terbaca (<span id="countUnmatched">0</span>)
                </button>
            </div>
        </div>

        <!-- AUDIT TABLES -->
        <div class="admin-card p-0 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="auditTable">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3 text-center" style="width: 40px;" id="thCheckboxCol">
                                <input type="checkbox" id="selectAllValid" class="form-check-input" checked>
                            </th>
                            <th class="py-3 px-3 text-center" style="width: 50px;">No</th>
                            <th class="py-3">Nama Berkas PDF</th>
                            <th class="py-3">Plat Nomor Terbaca</th>
                            <th class="py-3">Kategori</th>
                            <th class="py-3">No. Box / Record DB</th>
                            <th class="py-3">Ukuran File</th>
                            <th class="py-3">Keterangan Audit</th>
                            <th class="py-3 text-center" style="width: 100px;">Status</th>
                            <th class="py-3 text-center" style="width: 70px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="auditTableBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentAuditData = null;
    let activeTab = 'valid';
    let scanTimerInterval = null;
    let scanStartTime = null;

    function startScanTimer() {
        scanStartTime = Date.now();
        const timerElem = document.getElementById('scanElapsedTimer');
        if (timerElem) timerElem.innerText = 'Waktu berjalan: 0 detik';
        if (scanTimerInterval) clearInterval(scanTimerInterval);
        scanTimerInterval = setInterval(() => {
            const elapsed = Math.floor((Date.now() - scanStartTime) / 1000);
            if (timerElem) timerElem.innerText = `Waktu berjalan: ${elapsed} detik`;
        }, 1000);
    }

    function stopScanTimer() {
        if (scanTimerInterval) clearInterval(scanTimerInterval);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const scanForm = document.getElementById('scanForm');
        const scanLoadingState = document.getElementById('scanLoadingState');
        const btnScanTrigger = document.getElementById('btnScanTrigger');
        const auditResultContainer = document.getElementById('auditResultContainer');
        const btnExecuteTrigger = document.getElementById('btnExecuteTrigger');
        const btnResetAudit = document.getElementById('btnResetAudit');
        const selectAllValid = document.getElementById('selectAllValid');

        // Submit form pindaian folder via AJAX
        scanForm.addEventListener('submit', function (e) {
            e.preventDefault();

            scanLoadingState.classList.remove('d-none');
            btnScanTrigger.disabled = true;
            auditResultContainer.classList.add('d-none');
            startScanTimer();

            const formData = new FormData(scanForm);

            fetch("{{ route('elabel.bpkb.smart-extractor.scan') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                stopScanTimer();
                scanLoadingState.classList.add('d-none');
                btnScanTrigger.disabled = false;

                if (!data.success) {
                    alert('Gagal memindai: ' + data.message);
                    return;
                }

                currentAuditData = data;
                renderAuditUI(data);
                auditResultContainer.classList.remove('d-none');
            })
            .catch(err => {
                stopScanTimer();
                scanLoadingState.classList.add('d-none');
                btnScanTrigger.disabled = false;
                console.error("Error scan:", err);
                alert("Terjadi kesalahan saat memproses pemindaian folder.");
            });
        });

        // Event Select All Checkbox
        if (selectAllValid) {
            selectAllValid.addEventListener('change', function () {
                document.querySelectorAll('.valid-item-check').forEach(cb => cb.checked = this.checked);
                updateExecuteButtonCount();
            });
        }

        // Event Reset Audit
        if (btnResetAudit) {
            btnResetAudit.addEventListener('click', function () {
                currentAuditData = null;
                auditResultContainer.classList.add('d-none');
                document.getElementById('auditTableBody').innerHTML = '';
                stopScanTimer();
            });
        }

        // Eksekusi penautan
        btnExecuteTrigger.addEventListener('click', function () {
            if (!currentAuditData || !currentAuditData.results.valid || currentAuditData.results.valid.length === 0) {
                alert("Tidak ada berkas valid yang siap ditautkan.");
                return;
            }

            const checkedBoxes = document.querySelectorAll('.valid-item-check:checked');
            if (checkedBoxes.length === 0) {
                alert("Tidak ada berkas valid yang dicentang untuk ditautkan.");
                return;
            }

            const selectedItems = Array.from(checkedBoxes).map(cb => {
                const idx = parseInt(cb.dataset.index);
                return currentAuditData.results.valid[idx];
            });

            if (!confirm(`Apakah Anda yakin ingin menyimpan penautan ${selectedItems.length} berkas PDF BPKB valid yang dipilih ke database eLABEL?`)) {
                return;
            }

            btnExecuteTrigger.disabled = true;
            btnExecuteTrigger.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...`;

            fetch("{{ route('elabel.bpkb.smart-extractor.execute') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    valid_items: selectedItems,
                })
            })
            .then(res => res.json())
            .then(data => {
                btnExecuteTrigger.disabled = false;
                btnExecuteTrigger.innerHTML = `<i class="bi bi-check-circle-fill"></i> Simpan Penautan`;

                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert("Gagal: " + data.message);
                }
            })
            .catch(err => {
                btnExecuteTrigger.disabled = false;
                btnExecuteTrigger.innerHTML = `<i class="bi bi-check-circle-fill"></i> Simpan Penautan`;
                console.error("Error execute:", err);
                alert("Terjadi kesalahan saat menyimpan penautan.");
            });
        });
    });

    function updateExecuteButtonCount() {
        const checkedCount = document.querySelectorAll('.valid-item-check:checked').length;
        const badge = document.getElementById('btnValidCountBadge');
        if (badge) badge.innerText = checkedCount;
    }

    function renderAuditUI(data) {
        document.getElementById('sumTotalCount').innerText = data.summary.total;
        document.getElementById('countValid').innerText = data.summary.valid;
        document.getElementById('countDuplicate').innerText = data.summary.duplicate;
        document.getElementById('countExists').innerText = data.summary.exists;
        document.getElementById('countUnmatched').innerText = data.summary.unmatched;
        document.getElementById('btnValidCountBadge').innerText = data.summary.valid;

        const selectAllValid = document.getElementById('selectAllValid');
        if (selectAllValid) selectAllValid.checked = true;

        showTab('valid');
    }

    function showTab(tabName) {
        activeTab = tabName;
        const btnMap = {
            'valid': 'tabValidBtn',
            'duplicate': 'tabDuplicateBtn',
            'exists': 'tabExistsBtn',
            'unmatched': 'tabUnmatchedBtn'
        };

        Object.keys(btnMap).forEach(key => {
            const btn = document.getElementById(btnMap[key]);
            if (key === tabName) {
                btn.className = `btn btn-sm btn-${getTabTheme(key)} fw-bold px-3 py-1.5 rounded-pill shadow-none active`;
            } else {
                btn.className = `btn btn-sm btn-outline-${getTabTheme(key)} fw-bold px-3 py-1.5 rounded-pill shadow-none`;
            }
        });

        const selectAllValid = document.getElementById('selectAllValid');
        if (selectAllValid) {
            selectAllValid.disabled = (tabName !== 'valid');
        }

        renderTableRows(tabName);
    }

    function getTabTheme(tab) {
        return tab === 'valid' ? 'success' : (tab === 'duplicate' ? 'warning' : (tab === 'exists' ? 'info' : 'danger'));
    }

    function renderTableRows(tabName) {
        const tbody = document.getElementById('auditTableBody');
        tbody.innerHTML = '';

        if (!currentAuditData || !currentAuditData.results || !currentAuditData.results[tabName]) return;

        const items = currentAuditData.results[tabName];

        if (items.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" class="text-center py-4 text-secondary">
                        <i class="bi bi-inbox fs-3 d-block text-muted mb-1 opacity-50"></i>
                        <small>Tidak ada berkas pada kategori ini.</small>
                    </td>
                </tr>
            `;
            return;
        }

        items.forEach((item, idx) => {
            const tr = document.createElement('tr');
            let badgeHtml = '';
            
            if (tabName === 'valid') {
                badgeHtml = `<span class="badge bg-success bg-opacity-10 text-success border border-success px-2.5 py-1">VALID 100%</span>`;
            } else if (tabName === 'duplicate') {
                badgeHtml = `<span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-2.5 py-1">GANDA</span>`;
            } else if (tabName === 'exists') {
                badgeHtml = `<span class="badge bg-info bg-opacity-10 text-info border border-info px-2.5 py-1">SUDAH ADA</span>`;
            } else {
                badgeHtml = `<span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2.5 py-1">TIDAK COCOK</span>`;
            }

            const checkboxHtml = tabName === 'valid'
                ? `<input type="checkbox" class="form-check-input valid-item-check" data-index="${idx}" checked onchange="updateExecuteButtonCount()">`
                : `<input type="checkbox" class="form-check-input" disabled>`;

            const previewUrl = `{{ route('elabel.bpkb.smart-extractor.preview') }}?path=${encodeURIComponent(item.file_path)}`;
            const actionHtml = `<a href="${previewUrl}" target="_blank" class="btn btn-sm btn-outline-primary rounded-circle" title="Lihat PDF"><i class="bi bi-eye"></i></a>`;

            tr.innerHTML = `
                <td class="text-center">${checkboxHtml}</td>
                <td class="text-center px-3 fw-medium text-secondary">${idx + 1}</td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-pdf-fill text-danger fs-5"></i>
                        <strong class="font-monospace text-dark" style="font-size: 0.88rem;">${item.filename}</strong>
                    </div>
                </td>
                <td><span class="fw-bold text-navy font-monospace">${item.extracted_plate || '-'}</span></td>
                <td><span class="badge bg-light text-navy border">${item.vehicle_label || 'Semua'}</span></td>
                <td><span class="badge bg-light text-dark border font-monospace">${item.box_code || '-'}</span></td>
                <td><span class="small text-secondary">${item.file_size || '-'}</span></td>
                <td><small class="text-secondary">${item.reason || '-'}</small></td>
                <td class="text-center">${badgeHtml}</td>
                <td class="text-center">${actionHtml}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    function exportAuditToCSV() {
        if (!currentAuditData || !currentAuditData.results) {
            alert('Tidak ada data audit untuk diexport.');
            return;
        }
        let csv = 'No,Status,Nama File,Plat Nomor,Kategori,Box,Ukuran,Keterangan\n';
        let no = 1;
        ['valid', 'duplicate', 'exists', 'unmatched'].forEach(tab => {
            (currentAuditData.results[tab] || []).forEach(item => {
                const cleanPlate = (item.extracted_plate || '').replace(/"/g, '""');
                const filename = (item.filename || '').replace(/"/g, '""');
                const cat = (item.vehicle_label || '').replace(/"/g, '""');
                const box = (item.box_code || '').replace(/"/g, '""');
                const size = (item.file_size || '').replace(/"/g, '""');
                const reason = (item.reason || '').replace(/"/g, '""');
                csv += `${no++},"${item.status}","${filename}","${cleanPlate}","${cat}","${box}","${size}","${reason}"\n`;
            });
        });
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `audit_bpkb_scanner_${new Date().toISOString().slice(0, 10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
    }
</script>
@endpush
