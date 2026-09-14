<!-- Modal AI Smart Semantic Import Bangunan -->
<div class="modal fade" id="modalSmartImport" tabindex="-1" aria-labelledby="modalSmartImportLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem;">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 48px; height: 48px; background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 100%); color: white;">
                        <i class="bi bi-file-earmark-spreadsheet fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="modalSmartImportLabel">AI Smart Import Excel (KIB C)</h5>
                        <p class="text-muted small mb-0">Impor data massal gedung dan bangunan dengan pencocokan semantik kolom otomatis.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body px-4 py-3">
                <!-- STEP 1: Upload File -->
                <div id="importStepUpload">
                    <div class="p-4 border-2 border-dashed rounded-4 text-center my-3 bg-light" id="dropZone" style="border-style: dashed; cursor: pointer;">
                        <input type="file" id="excelFileInput" accept=".xlsx, .xls, .csv" class="d-none">
                        <i class="bi bi-cloud-arrow-up text-primary display-4 mb-3 d-block"></i>
                        <h6 class="fw-bold text-dark">Klik atau Seret Berkas Excel / CSV ke Sini</h6>
                        <p class="text-muted small mb-2">Mendukung berkas ekspor Simda BMD, SIPD, atau format tabel dinas (.xlsx, .xls, .csv - Maks. 20 MB)</p>
                        <button type="button" class="btn btn-sm btn-primary px-3 rounded-pill" onclick="document.getElementById('excelFileInput').click()">
                            <i class="bi bi-folder2-open me-1"></i> Telusuri Berkas
                        </button>
                    </div>
                    <div id="uploadSpinner" class="text-center py-4 d-none">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted small mt-2 mb-0">Menganalisis struktur kolom dan sampel data secara semantik...</p>
                    </div>
                </div>

                <!-- STEP 2: Preview & Dynamic Mapping -->
                <div id="importStepMapping" class="d-none">
                    <div class="alert alert-info border-0 rounded-3 d-flex align-items-center gap-2 py-2 px-3 small mb-3">
                        <i class="bi bi-info-circle-fill text-info fs-5"></i>
                        <div>
                            Periksa hasil rekomendasi pemetaan kolom di bawah. Anda dapat menyesuaikan pasangan kolom target sesuai kebutuhan.
                        </div>
                    </div>

                    <div class="table-responsive rounded-3 border mb-3" style="max-height: 380px;">
                        <table class="table table-sm table-hover align-middle mb-0" id="tableMappingPreview">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 25%;">Kolom di Berkas Excel</th>
                                    <th style="width: 35%;">Sampel Baris Data</th>
                                    <th style="width: 40%;">Petakan ke Kolom Database KIB C</th>
                                </tr>
                            </thead>
                            <tbody id="mappingRows"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 d-none" id="btnBackToUpload">
                    <i class="bi bi-arrow-left me-1"></i> Ganti Berkas
                </button>
                <button type="button" class="btn btn-primary rounded-pill px-4 d-none" id="btnExecuteImport">
                    <i class="bi bi-cloud-check-fill me-1"></i> Mulai Impor Data
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('excelFileInput');
    const dropZone = document.getElementById('dropZone');
    const uploadSpinner = document.getElementById('uploadSpinner');
    const stepUpload = document.getElementById('importStepUpload');
    const stepMapping = document.getElementById('importStepMapping');
    const btnBack = document.getElementById('btnBackToUpload');
    const btnExecute = document.getElementById('btnExecuteImport');
    const mappingRows = document.getElementById('mappingRows');

    let currentImportToken = '';
    let currentHeaders = [];
    let currentHeaderRowIndex = 0;
    let targetColumns = {};

    dropZone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            handleFileUpload(e.target.files[0]);
        }
    });

    function handleFileUpload(file) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('_token', '{{ csrf_token() }}');

        dropZone.classList.add('d-none');
        uploadSpinner.classList.remove('d-none');

        fetch('{{ route("bangunan.import.preview") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            uploadSpinner.classList.add('d-none');
            dropZone.classList.remove('d-none');

            if (!data.success) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Membaca Berkas',
                    text: data.message || 'Format berkas tidak valid.'
                });
                return;
            }

            currentImportToken = data.import_token;
            currentHeaders = data.headers;
            currentHeaderRowIndex = data.header_row_index;
            targetColumns = data.target_columns;

            renderMappingTable(data.headers, data.samples, data.suggested_mapping, data.target_columns);

            stepUpload.classList.add('d-none');
            stepMapping.classList.remove('d-none');
            btnBack.classList.remove('d-none');
            btnExecute.classList.remove('d-none');
        })
        .catch(err => {
            uploadSpinner.classList.add('d-none');
            dropZone.classList.remove('d-none');
            Swal.fire({
                icon: 'error',
                title: 'Terjadi Kesalahan',
                text: 'Gagal mengunggah berkas ke server.'
            });
        });
    }

    function renderMappingTable(headers, samples, suggestions, targets) {
        mappingRows.innerHTML = '';

        headers.forEach((header, idx) => {
            const tr = document.createElement('tr');

            // Sampel teks 3 baris
            let sampleText = '';
            samples.forEach((row) => {
                if (row[idx] !== undefined && row[idx] !== null && String(row[idx]).trim() !== '') {
                    sampleText += `<div class="text-truncate text-muted small" style="max-width: 250px;">• ${row[idx]}</div>`;
                }
            });
            if (!sampleText) sampleText = '<span class="text-muted fst-italic small">Kosong</span>';

            // Select Dropdown
            let options = '<option value="">-- Jangan Impor Kolom Ini --</option>';
            for (const [key, label] of Object.entries(targets)) {
                const isSelected = (suggestions[header] === key) ? 'selected' : '';
                options += `<option value="${key}" ${isSelected}>${label}</option>`;
            }

            const matchBadge = suggestions[header] 
                ? '<span class="badge bg-success-subtle text-success ms-2"><i class="bi bi-stars"></i> Terpetakan</span>'
                : '';

            tr.innerHTML = `
                <td class="fw-semibold text-dark">
                    ${header}
                    ${matchBadge}
                </td>
                <td>${sampleText}</td>
                <td>
                    <select class="form-select form-select-sm mapping-select" data-header="${header}">
                        ${options}
                    </select>
                </td>
            `;
            mappingRows.appendChild(tr);
        });
    }

    btnBack.addEventListener('click', function() {
        stepMapping.classList.add('d-none');
        stepUpload.classList.remove('d-none');
        btnBack.classList.add('d-none');
        btnExecute.classList.add('d-none');
        fileInput.value = '';
    });

    btnExecute.addEventListener('click', function() {
        const selects = document.querySelectorAll('.mapping-select');
        const mapping = {};

        selects.forEach(select => {
            const target = select.value;
            const header = select.getAttribute('data-header');
            if (target) {
                mapping[target] = header;
            }
        });

        if (!mapping['nama_bangunan']) {
            Swal.fire({
                icon: 'warning',
                title: 'Kolom Wajib Belum Dipetakan',
                text: 'Kolom "Nama Bangunan / Gedung" wajib dipetakan agar data dapat tersimpan.'
            });
            return;
        }

        btnExecute.disabled = true;
        btnExecute.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

        fetch('{{ route("bangunan.import.execute") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                import_token: currentImportToken,
                mapping: mapping,
                headers: currentHeaders,
                header_row_index: currentHeaderRowIndex
            })
        })
        .then(res => res.json())
        .then(data => {
            btnExecute.disabled = false;
            btnExecute.innerHTML = '<i class="bi bi-cloud-check-fill me-1"></i> Mulai Impor Data';

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Impor Berhasil!',
                    text: data.message,
                    confirmButtonColor: '#1E40AF'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Impor Data',
                    text: data.message
                });
            }
        })
        .catch(err => {
            btnExecute.disabled = false;
            btnExecute.innerHTML = '<i class="bi bi-cloud-check-fill me-1"></i> Mulai Impor Data';
            Swal.fire({
                icon: 'error',
                title: 'Terjadi Kesalahan',
                text: 'Gagal menyelesaikan proses impor data.'
            });
        });
    });
});
</script>
