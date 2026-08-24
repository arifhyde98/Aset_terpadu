@extends('layouts.app')

@section('title', 'Manajemen Backup Sistem')

@section('content')
<div class="container-fluid px-0">
    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('settings.index') }}" class="text-decoration-none text-secondary">Pengaturan</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Backup</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-navy mb-0">Manajemen Backup Sistem</h3>
            <p class="text-secondary small mb-0">Kelola berkas cadangan database dan file unggahan sistem secara manual atau otomatis</p>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('settings.backups.create') }}" method="POST" class="d-inline" id="backupForm">
                @csrf
                <input type="hidden" name="option" value="all">
                <button type="submit" class="btn btn-primary shadow-sm fw-medium d-flex align-items-center gap-2 btn-backup-trigger" id="btnRunBackup">
                    <i class="bi bi-cloud-arrow-up"></i> Buat Backup Penuh
                </button>
            </form>
            <form action="{{ route('settings.backups.create') }}" method="POST" class="d-inline" id="backupDbForm">
                @csrf
                <input type="hidden" name="option" value="db">
                <button type="submit" class="btn btn-outline-primary bg-white shadow-sm fw-medium d-flex align-items-center gap-2 btn-backup-trigger" id="btnRunDbBackup">
                    <i class="bi bi-database"></i> Backup DB Saja
                </button>
            </form>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- LEFT COLUMN: SYSTEM DISK INFO & LIST BACKUPS -->
        <div class="col-lg-8">
            <!-- DISK STORAGE STATE -->
            <div class="admin-card p-4 mb-4">
                <h5 class="fw-bold text-navy mb-3"><i class="bi bi-hdd-network me-2 text-primary"></i> Kapasitas Penyimpanan Server</h5>
                <div class="row align-items-center">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3 me-3">
                                <i class="bi bi-pie-chart fs-3"></i>
                            </div>
                            <div>
                                <small class="text-secondary d-block">Terpakai / Total</small>
                                <span class="fw-bold text-dark fs-5">{{ $diskInfo['used'] }} / {{ $diskInfo['total'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-secondary fw-semibold">Garis Batas Disk</span>
                            <span class="fw-bold text-primary">{{ $diskInfo['percent'] }}% Terpakai</span>
                        </div>
                        <div class="progress rounded-pill" style="height: 10px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                 role="progressbar" 
                                 style="width: {{ $diskInfo['percent'] }}%;" 
                                 aria-valuenow="{{ $diskInfo['percent'] }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100"></div>
                        </div>
                        <small class="text-secondary d-block mt-2 italic">Kapasitas penyimpanan server tersisa: <strong>{{ $diskInfo['free'] }}</strong></small>
                    </div>
                </div>
            </div>

            <!-- BACKUPS TABLE -->
            <div class="admin-card p-0 overflow-hidden">
                <div class="p-4 bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-navy mb-0"><i class="bi bi-file-earmark-zip me-2 text-success"></i> Berkas Cadangan Terkini</h5>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-1.5 rounded-pill">{{ count($backups) }} Berkas</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3 px-4 text-center" style="width: 60px;">No</th>
                                <th class="py-3">Nama Berkas</th>
                                <th class="py-3">Ukuran</th>
                                <th class="py-3">Tanggal Dibuat</th>
                                <th class="py-3 text-center" style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($backups as $idx => $b)
                                <tr>
                                    <td class="px-4 text-center fw-medium text-secondary">{{ $idx + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-file-zip-fill text-warning fs-4"></i>
                                            <div>
                                                <strong class="text-dark font-monospace" style="font-size: 0.9rem;">
                                                    {{ basename($b['file_name']) }}
                                                </strong>
                                                <div class="small text-secondary italic">Lokasi: {{ dirname($b['file_name']) }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="fw-bold text-dark">{{ $b['file_size'] }}</span></td>
                                    <td><span class="text-secondary small">{{ $b['last_modified'] }}</span></td>
                                    <td class="text-center px-4">
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="{{ route('settings.backups.download', ['fileName' => $b['file_name']]) }}" 
                                               class="btn btn-sm btn-light border shadow-none text-success" 
                                               data-bs-toggle="tooltip" 
                                               title="Unduh Berkas">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <form action="{{ route('settings.backups.destroy', ['fileName' => $b['file_name']]) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus berkas backup ini dari server secara permanen?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-light border shadow-none text-danger" data-bs-toggle="tooltip" title="Hapus Berkas">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-secondary">
                                        <i class="bi bi-cloud-slash fs-1 mb-2 d-block text-secondary opacity-50"></i>
                                        <h6 class="fw-bold text-dark mb-1">Belum Ada Berkas Backup</h6>
                                        <p class="small text-muted mb-0">Gunakan tombol di atas untuk membuat pencadangan data pertama Anda.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: DOCUMENTATION / RESTORE GUIDE (THEME AWARE) -->
        <div class="col-lg-4">
            <div class="admin-card p-4 h-100 shadow-sm border-0">
                <h5 class="fw-bold text-navy mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-book-half text-primary"></i> Dokumentasi & Panduan Restore
                </h5>
                <hr class="my-3">
                
                <div class="accordion accordion-flush" id="docAccordion">


                    <!-- GUIDE 2: RESTORE DATABASE MANUAL -->
                    <div class="accordion-item border-0 mb-3 bg-transparent">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed bg-transparent text-primary fw-bold px-0 shadow-none d-flex align-items-center gap-2" 
                                    type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                <i class="bi bi-database-fill-down"></i> 1. Cara Restore Database
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#docAccordion">
                            <div class="accordion-body px-0 pt-2 pb-0 small text-secondary" style="font-size: 0.82rem; line-height: 1.6;">
                                Untuk memulihkan (restore) database dari berkas SQL dump:
                                <div class="p-2.5 bg-dark text-warning font-monospace rounded-3 my-2 border shadow-sm" style="font-size: 0.75rem; word-break: break-all;">
                                    mysql -u bpkad.aset -p db_sipat_terpadu &lt; [nama_dump].sql
                                </div>
                                <span class="d-block mt-2">Atau jika menggunakan <strong>phpMyAdmin</strong>, masuk ke tab <strong>Import</strong> dan pilih file database <code class="px-1.5 py-0.5 rounded bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 font-monospace">.sql</code> dari file zip cadangan.</span>
                            </div>
                        </div>
                    </div>

                    <!-- GUIDE 3: JADWAL OTOMATIS -->
                    <div class="accordion-item border-0 mb-3 bg-transparent">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed bg-transparent text-primary fw-bold px-0 shadow-none d-flex align-items-center gap-2" 
                                    type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                                <i class="bi bi-clock-history"></i> 2. Jadwal Backup Otomatis
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#docAccordion">
                            <div class="accordion-body px-0 pt-2 pb-0 small text-secondary" style="font-size: 0.82rem; line-height: 1.6;">
                                Server dikonfigurasi untuk menjalankan tugas otomatis (crontab scheduler):
                                <ul class="ps-3 mt-2 mb-0">
                                    <li class="mb-1"><strong class="text-dark">backup:run</strong> berjalan pukul <strong>00:00 WITA</strong> (membuat backup database dan file unggahan).</li>
                                    <li class="mb-0"><strong class="text-dark">backup:clean</strong> berjalan pukul <strong>01:00 WITA</strong> (membersihkan berkas backup lama > 7 hari di disk server).</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- GUIDE 4: PINDAH LAPTOP -->
                    <div class="accordion-item border-0 mb-3 bg-transparent">
                        <h2 class="accordion-header" id="headingFour">
                            <button class="accordion-button collapsed bg-transparent text-primary fw-bold px-0 shadow-none d-flex align-items-center gap-2" 
                                    type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour">
                                <i class="bi bi-laptop"></i> 3. Pindah Proyek ke Laptop Lain
                            </button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#docAccordion">
                            <div class="accordion-body px-0 pt-2 pb-0 small text-secondary" style="font-size: 0.82rem; line-height: 1.6;">
                                Jika Anda ingin memasang proyek ini di laptop baru secara utuh:
                                <ol class="ps-3 mt-2 mb-0">
                                    <li class="mb-1.5">Copy direktori folder <code class="px-1.5 py-0.5 rounded bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 font-monospace">SIPAT_Terpadu</code> ke laptop baru.</li>
                                    <li class="mb-1.5">Buat database baru bernama <code class="px-1.5 py-0.5 rounded bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 font-monospace">db_sipat_terpadu</code> di laptop baru Anda.</li>
                                    <li class="mb-0">Impor berkas <code class="px-1.5 py-0.5 rounded bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 font-monospace">.sql</code> hasil backup ke database baru Anda.</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PROGRESS DIALOG POPUP (REAL-TIME OVERLAY) -->
<div class="modal fade" id="backupProgressModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg p-3">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-navy d-flex align-items-center gap-2" id="modalProgressTitle">
                    <span class="spinner-border spinner-border-sm text-primary me-1" id="modalSpinner" role="status"></span>
                    <span id="modalProgressTitleText">Proses Pencadangan Berjalan di Background...</span>
                </h5>
                <button type="button" class="btn-close d-none" id="btnModalCloseHeader" data-bs-dismiss="modal" aria-label="Close" onclick="window.location.reload()"></button>
            </div>
            <div class="modal-body p-3">
                <p class="text-secondary small mb-2" id="modalProgressStep">Menginisialisasi proses pencadangan...</p>
                
                <div class="progress rounded-pill mb-3" style="height: 16px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary fw-bold" 
                         role="progressbar" 
                         id="modalProgressBar" 
                         style="width: 10%; font-size: 0.75rem;">10%</div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-secondary fw-semibold"><i class="bi bi-terminal me-1"></i> Live Output Console Terminal:</small>
                </div>
                <div class="p-3 bg-dark text-light rounded-3 font-monospace small overflow-auto" 
                     id="modalTerminalConsole" 
                     style="height: 220px; font-size: 0.78rem; line-height: 1.6; white-space: pre-wrap;">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 d-none" id="modalFooterActions">
                <button type="button" class="btn btn-success fw-semibold px-4 rounded-pill shadow-sm" onclick="window.location.reload()">
                    <i class="bi bi-check-circle me-1"></i> Selesai & Refresh Halaman
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('backupProgressModal');
        let progressModal = null;
        if (modalEl && typeof bootstrap !== 'undefined') {
            progressModal = new bootstrap.Modal(modalEl);
        }
        
        const modalProgressBar = document.getElementById('modalProgressBar');
        const modalProgressStep = document.getElementById('modalProgressStep');
        const modalProgressTitleText = document.getElementById('modalProgressTitleText');
        const modalSpinner = document.getElementById('modalSpinner');
        const modalTerminalConsole = document.getElementById('modalTerminalConsole');
        const modalFooterActions = document.getElementById('modalFooterActions');
        const btnModalCloseHeader = document.getElementById('btnModalCloseHeader');

        const btnBackupTriggers = document.querySelectorAll('.btn-backup-trigger');
        let intervalId = null;

        function showModal() {
            if (progressModal) {
                progressModal.show();
            } else if (modalEl && typeof bootstrap !== 'undefined') {
                progressModal = new bootstrap.Modal(modalEl);
                progressModal.show();
            }
        }

        function updateModalUI(data) {
            if (!data || !data.status) return;

            // Terminal log update
            if (data.log) {
                modalTerminalConsole.innerText = data.log;
                modalTerminalConsole.scrollTop = modalTerminalConsole.scrollHeight;
            }

            if (data.status === 'running') {
                showModal();
                
                modalSpinner.classList.remove('d-none');
                modalProgressTitleText.innerText = "Proses Pencadangan Berjalan di Background...";
                
                modalProgressBar.className = "progress-bar progress-bar-striped progress-bar-animated bg-primary fw-bold";
                const pct = data.percentage || 15;
                modalProgressBar.style.width = pct + '%';
                modalProgressBar.innerText = pct + '%';
                
                modalProgressStep.innerHTML = `<span class="spinner-border spinner-border-sm text-primary me-2"></span>${data.step}`;
                modalFooterActions.classList.add('d-none');
                btnModalCloseHeader.classList.add('d-none');

                btnBackupTriggers.forEach(btn => btn.disabled = true);

                if (!intervalId) {
                    intervalId = setInterval(checkBackupStatus, 1500);
                }
            } else if (data.status === 'success') {
                showModal();

                modalSpinner.classList.add('d-none');
                modalProgressTitleText.innerHTML = `<i class="bi bi-check-circle-fill text-success me-2"></i> Pencadangan Berhasil Selesai!`;
                
                modalProgressBar.className = "progress-bar bg-success fw-bold";
                modalProgressBar.style.width = '100%';
                modalProgressBar.innerText = '100%';
                
                modalProgressStep.innerHTML = `<strong class="text-success">${data.step}</strong>`;
                modalFooterActions.classList.remove('d-none');
                btnModalCloseHeader.classList.remove('d-none');

                btnBackupTriggers.forEach(btn => btn.disabled = false);

                if (intervalId) {
                    clearInterval(intervalId);
                    intervalId = null;
                }
            } else if (data.status === 'failed') {
                showModal();

                modalSpinner.classList.add('d-none');
                modalProgressTitleText.innerHTML = `<i class="bi bi-exclamation-triangle-fill text-danger me-2"></i> Pencadangan Gagal!`;
                
                modalProgressBar.className = "progress-bar bg-danger fw-bold";
                modalProgressBar.style.width = '100%';
                modalProgressBar.innerText = 'Gagal';
                
                modalProgressStep.innerHTML = `<strong class="text-danger">${data.step}</strong>`;
                modalFooterActions.classList.remove('d-none');
                btnModalCloseHeader.classList.remove('d-none');

                btnBackupTriggers.forEach(btn => btn.disabled = false);

                if (intervalId) {
                    clearInterval(intervalId);
                    intervalId = null;
                }
            }
        }

        // Fungsi polling status
        function checkBackupStatus() {
            fetch("/settings/backups/status")
                .then(response => response.json())
                .then(data => {
                    if (data && (data.status === 'running' || data.status === 'success' || data.status === 'failed')) {
                        updateModalUI(data);
                    }
                })
                .catch(error => console.error("Gagal mengecek status backup:", error));
        }

        // Intercept form submission with AJAX
        const forms = ['backupForm', 'backupDbForm'];
        forms.forEach(formId => {
            const form = document.getElementById(formId);
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault(); // Mencegah reload halaman

                    // Buka modal secara instan
                    showModal();
                    modalSpinner.classList.remove('d-none');
                    modalProgressTitleText.innerText = "Memulai Pencadangan di Background...";
                    modalProgressBar.className = "progress-bar progress-bar-striped progress-bar-animated bg-primary fw-bold";
                    modalProgressBar.style.width = '10%';
                    modalProgressBar.innerText = '10%';
                    modalProgressStep.innerText = "Meminta server memulai proses backup...";
                    modalTerminalConsole.innerText = "Mengirim permintaan ke server...\n";
                    modalFooterActions.classList.add('d-none');
                    btnModalCloseHeader.classList.add('d-none');

                    // Kirim AJAX POST
                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: new FormData(form)
                    })
                    .then(res => res.json())
                    .then(data => {
                        // Mulai polling status
                        setTimeout(checkBackupStatus, 800);
                    })
                    .catch(err => {
                        console.error("AJAX Error:", err);
                        setTimeout(checkBackupStatus, 1000);
                    });
                });
            }
        });

        // Tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
</script>
@endpush
