@extends('layouts.app')

@section('title', 'Dashboard eLABEL')

@section('content')
<div class="container-fluid px-0">

    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">SIPAT Terpadu</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Dashboard eLABEL</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0">Dashboard Manajemen Arsip (eLABEL)</h4>
        </div>
        <div class="action-toolbar d-flex flex-wrap gap-2">
            @if(in_array(auth()->user()->role->value, ['superadmin', 'admin']))
                <button type="button" class="btn btn-outline-warning shadow-sm fw-medium d-flex align-items-center gap-2" id="btnCheckDuplicates">
                    <i class="bi bi-exclamation-triangle"></i> Diagnosis Duplikasi
                </button>
            @endif
            <a href="{{ route('elabel.bpkb.index') }}" class="btn btn-primary shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-card-heading"></i> Katalog BPKB
            </a>
            <a href="{{ route('elabel.sertifikat.index') }}" class="btn btn-success shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-patch-check"></i> Sertifikat Tanah
            </a>
            <a href="{{ route('elabel.peminjaman.index') }}" class="btn btn-warning text-dark shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-clock-history"></i> Peminjaman Scan
            </a>
        </div>
    </div>
    <!-- TOP STAT CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase fw-semibold text-secondary fs-7 mb-1">Total Box Fisik</div>
                        <h2 class="fw-extrabold text-navy mb-1">{{ number_format($boxCount) }}</h2>
                        <div class="small text-secondary">
                            <span class="text-primary fw-bold">{{ $bpkbBoxCount }}</span> BPKB · 
                            <span class="text-success fw-bold">{{ $sertifikatBoxCount }}</span> Sertifikat · 
                            <span class="text-info fw-bold">{{ $suratPenyerahanBoxCount }}</span> Surat
                        </div>
                    </div>
                    <div class="rounded-4 bg-primary bg-opacity-10 p-3 text-primary fs-3">
                        <i class="bi bi-archive-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase fw-semibold text-secondary fs-7 mb-1">BPKB Kendaraan</div>
                        <h2 class="fw-extrabold text-navy mb-1">{{ number_format($bpkbCount) }}</h2>
                        <div class="small text-secondary">
                            <span class="text-success fw-bold">{{ $bpkbAvailableCount }}</span> Tersedia · 
                            <span class="text-danger fw-bold">{{ $bpkbDeletedCount }}</span> Keluar
                        </div>
                    </div>
                    <div class="rounded-4 bg-info bg-opacity-10 p-3 text-info fs-3">
                        <i class="bi bi-card-heading"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase fw-semibold text-secondary fs-7 mb-1">Sertifikat Tanah</div>
                        <h2 class="fw-extrabold text-navy mb-1">{{ number_format($sertifikatCount) }}</h2>
                        <div class="small text-success fw-semibold">
                            <i class="bi bi-check-all"></i> Terarsip Lengkap
                        </div>
                    </div>
                    <div class="rounded-4 bg-success bg-opacity-10 p-3 text-success fs-3">
                        <i class="bi bi-patch-check-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase fw-semibold text-secondary fs-7 mb-1">Peminjaman / Request</div>
                        <h2 class="fw-extrabold text-navy mb-1">{{ number_format($loanCount) }}</h2>
                        <div class="small text-secondary">
                            <span class="text-warning fw-bold">{{ $loanApprovedCount }}</span> Disetujui
                        </div>
                    </div>
                    <div class="rounded-4 bg-warning bg-opacity-10 p-3 text-warning fs-3">
                        <i class="bi bi-file-earmark-pdf-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PROGRESS & METRICS SECTION -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-navy mb-0">Box Terisi</h6>
                        <span class="badge bg-primary rounded-pill">{{ $boxFilledPercent }}%</span>
                    </div>
                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar bg-primary rounded-pill" role="progressbar" style="width: {{ $boxFilledPercent }}%" aria-valuenow="{{ $boxFilledPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="small text-secondary">
                        <strong class="text-dark">{{ $filledBoxCount }}</strong> dari <strong>{{ $boxCount }}</strong> Box penyimpanan terisi dokumen.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-navy mb-0">BPKB Aktif (Tersedia)</h6>
                        <span class="badge bg-success rounded-pill">{{ $bpkbActivePercent }}%</span>
                    </div>
                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar bg-success rounded-pill" role="progressbar" style="width: {{ $bpkbActivePercent }}%" aria-valuenow="{{ $bpkbActivePercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="small text-secondary">
                        <strong class="text-dark">{{ $bpkbAvailableCount }}</strong> BPKB berada di brankas fisik dari total {{ $bpkbCount }} BPKB.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-navy mb-0">Rasio Peminjaman Scan</h6>
                        <span class="badge bg-warning text-dark rounded-pill">{{ $loanPercent }}%</span>
                    </div>
                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar bg-warning rounded-pill" role="progressbar" style="width: {{ $loanPercent }}%" aria-valuenow="{{ $loanPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="small text-secondary">
                        <strong class="text-dark">{{ $loanCount }}</strong> permohonan scan diajukan oleh pemohon/admin.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RECENT ACTIVITY LOGS -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-navy mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-clock-history text-primary"></i> Riwayat Aktivitas eLABEL Terbaru
            </h6>
            @if($oldActivity180Count > 0)
                <form action="{{ route('elabel.dashboard.cleanup-logs') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Bersihkan {{ $oldActivity180Count }} log yang berusia lebih dari 180 hari?')">
                        <i class="bi bi-trash"></i> Bersihkan Log Lama ({{ $oldActivity180Count }})
                    </button>
                </form>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3 text-secondary small fw-semibold">Pengguna</th>
                        <th class="py-3 text-secondary small fw-semibold">Aksi</th>
                        <th class="py-3 text-secondary small fw-semibold">Modul</th>
                        <th class="py-3 text-secondary small fw-semibold">Keterangan</th>
                        <th class="py-3 px-4 text-secondary small fw-semibold text-end">Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activityLogs as $log)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="fw-semibold text-navy">{{ $log->user->name ?? 'User #' . $log->user_id }}</div>
                                <div class="small text-secondary">{{ $log->user->email ?? '-' }}</div>
                            </td>
                            <td>
                                @php
                                    $badgeClass = match($log->action) {
                                        'create' => 'bg-success',
                                        'update' => 'bg-info',
                                        'delete' => 'bg-danger',
                                        'approve' => 'bg-primary',
                                        'reject' => 'bg-warning text-dark',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }} text-uppercase px-2 py-1 fs-7 rounded-2">{{ $log->action }}</span>
                            </td>
                            <td class="fw-medium text-dark">{{ $log->module }}</td>
                            <td class="text-secondary small">{{ $log->description }}</td>
                            <td class="px-4 text-end text-secondary small">
                                {{ $log->created_at ? $log->created_at->diffForHumans() : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-secondary">
                                <i class="bi bi-inbox fs-3 d-block mb-1"></i> Belum ada aktivitas tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@if(in_array(auth()->user()->role->value, ['superadmin', 'admin']))
@push('modals')
<!-- DIAGNOSIS DUPLICATES MODAL (eLABEL) -->
<x-modal id="diagnosisDuplicatesModal" title="Diagnosis & Bersihkan Arsip Ganda eLABEL" size="xl">
    <div class="modal-body p-4 bg-light">
        <!-- Tab Navigation -->
        <ul class="nav nav-tabs nav-fill mb-4 border-bottom" id="duplicateTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold text-navy py-3 d-flex align-items-center justify-content-center gap-2" id="bpkbs-tab" data-bs-toggle="tab" data-bs-target="#bpkbs-pane" type="button" role="tab" aria-controls="bpkbs-pane" aria-selected="true">
                    <i class="bi bi-card-heading fs-5 text-warning"></i>
                    <span>Arsip BPKB Ganda (<span id="bpkb-dup-count">0</span>)</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold text-navy py-3 d-flex align-items-center justify-content-center gap-2" id="sertifikats-tab" data-bs-toggle="tab" data-bs-target="#sertifikats-pane" type="button" role="tab" aria-controls="sertifikats-pane" aria-selected="false">
                    <i class="bi bi-patch-check-fill fs-5 text-success"></i>
                    <span>Arsip Sertifikat Ganda (<span id="sert-dup-count">0</span>)</span>
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="duplicateTabsContent">
            
            <!-- PANE 1: BPKB DUPLICATES -->
            <div class="tab-pane fade show active" id="bpkbs-pane" role="tabpanel" aria-labelledby="bpkbs-tab" tabindex="0">
                <div class="alert alert-warning border-0 bg-warning bg-opacity-10 text-navy d-flex align-items-center mb-4 rounded-3 shadow-none">
                    <div class="fs-4 me-3 text-warning"><i class="bi bi-info-circle-fill"></i></div>
                    <div>
                        <h6 class="alert-heading fw-bold mb-1" style="font-size: 0.9rem;">Instruksi Pembersihan Arsip BPKB</h6>
                        <p class="mb-0 small text-secondary">
                            Berkas BPKB terdeteksi memiliki nomor BPKB ganda/identik.
                            <br>1. <strong>Gabungkan Data</strong>: Lengkapi atribut kosong pada record utama, pindahkan riwayat request scan/peminjaman, lalu hapus berkas ganda.
                            <br>2. <strong>Hapus Duplikat</strong>: Hapus record ganda langsung dari database.
                        </p>
                    </div>
                </div>
                
                <div class="table-responsive border rounded-3 bg-white shadow-sm" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="table-navy text-white text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px; position: sticky; top: 0; z-index: 2;">
                            <tr>
                                <th class="px-3 py-3" style="width: 25%;">Data Ganda (Hasil Impor)</th>
                                <th class="px-3 py-3" style="width: 25%;">Data Induk (Asli)</th>
                                <th class="px-3 py-3" style="width: 30%;">Indikasi</th>
                                <th class="px-3 py-3 text-center" style="width: 20%;">Aksi Resolusi</th>
                            </tr>
                        </thead>
                        <tbody id="bpkb-dup-list">
                            <tr>
                                <td colspan="4" class="text-center py-5 text-secondary">
                                    <div class="spinner-border text-warning mb-2" role="status"></div>
                                    <div class="small fw-medium">Sedang memindai duplikasi BPKB...</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PANE 2: SERTIFIKAT DUPLICATES -->
            <div class="tab-pane fade" id="sertifikats-pane" role="tabpanel" aria-labelledby="sertifikats-tab" tabindex="0">
                <div class="alert alert-success border-0 bg-success bg-opacity-10 text-navy d-flex align-items-center mb-4 rounded-3 shadow-none">
                    <div class="fs-4 me-3 text-success"><i class="bi bi-info-circle-fill"></i></div>
                    <div>
                        <h6 class="alert-heading fw-bold mb-1" style="font-size: 0.9rem;">Instruksi Pembersihan Arsip Sertifikat</h6>
                        <p class="mb-0 small text-secondary">
                            Arsip sertifikat tanah terdeteksi memiliki nomor sertipikat yang sama persis.
                            <br>Penggabungan akan menyalin data atribut yang kosong ke record induk dan menghapus record duplikat yang kosong secara aman.
                        </p>
                    </div>
                </div>
                
                <div class="table-responsive border rounded-3 bg-white shadow-sm" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="table-navy text-white text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px; position: sticky; top: 0; z-index: 2;">
                            <tr>
                                <th class="px-3 py-3" style="width: 25%;">Data Ganda (Baru)</th>
                                <th class="px-3 py-3" style="width: 25%;">Data Induk (Asli)</th>
                                <th class="px-3 py-3" style="width: 30%;">Indikasi</th>
                                <th class="px-3 py-3 text-center" style="width: 20%;">Aksi Resolusi</th>
                            </tr>
                        </thead>
                        <tbody id="sert-dup-list">
                            <tr>
                                <td colspan="4" class="text-center py-5 text-secondary">
                                    <div class="spinner-border text-success mb-2" role="status"></div>
                                    <div class="small fw-medium">Sedang memindai duplikasi Sertifikat...</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
    <div class="modal-footer border-top bg-light px-4 py-3 rounded-bottom-4">
        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
    </div>
</x-modal>
@endpush

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const btnCheckDuplicates = document.getElementById('btnCheckDuplicates');
        let diagnosisModal = null;

        if (document.getElementById('diagnosisDuplicatesModal')) {
            diagnosisModal = new bootstrap.Modal(document.getElementById('diagnosisDuplicatesModal'));
        }

        if (btnCheckDuplicates && diagnosisModal) {
            btnCheckDuplicates.addEventListener('click', function() {
                document.getElementById('bpkb-dup-list').innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center py-5 text-secondary">
                            <div class="spinner-border text-warning mb-2" role="status"></div>
                            <div class="small fw-medium">Memindai duplikasi arsip BPKB...</div>
                        </td>
                    </tr>
                `;
                document.getElementById('sert-dup-list').innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center py-5 text-secondary">
                            <div class="spinner-border text-success mb-2" role="status"></div>
                            <div class="small fw-medium">Memindai duplikasi arsip Sertifikat Tanah...</div>
                        </td>
                    </tr>
                `;
                document.getElementById('bpkb-dup-count').textContent = '0';
                document.getElementById('sert-dup-count').textContent = '0';

                diagnosisModal.show();

                fetch("{{ route('elabel.check-duplicates') }}", {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderDuplicateBpkbs(data.bpkbs);
                        renderDuplicateSertifikats(data.sertifikats);
                    } else {
                        Swal.fire('Error', data.message || 'Gagal memindai duplikasi.', 'error');
                        diagnosisModal.hide();
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error', 'Gagal terhubung ke database.', 'error');
                    diagnosisModal.hide();
                });
            });
        }

        function renderDuplicateBpkbs(bpkbs) {
            const container = document.getElementById('bpkb-dup-list');
            document.getElementById('bpkb-dup-count').textContent = bpkbs.length;

            if (bpkbs.length === 0) {
                container.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center py-5 text-success">
                            <i class="bi bi-patch-check-fill fs-1 text-success d-block mb-2"></i>
                            <h6 class="fw-bold mb-1">Database Bersih!</h6>
                            <p class="mb-0 small text-secondary">Tidak terdeteksi adanya nomor BPKB ganda di sistem.</p>
                        </td>
                    </tr>
                `;
                return;
            }

            let html = '';
            bpkbs.forEach(item => {
                let diffHtml = '';
                item.differences.forEach(d => {
                    const rowClass = d.is_different ? 'table-danger bg-danger bg-opacity-10' : '';
                    diffHtml += `
                        <tr class="${rowClass}">
                            <td class="fw-bold py-2 px-3">${escapeHtml(d.label)}</td>
                            <td class="text-secondary py-2 px-3">${escapeHtml(d.original_val)}</td>
                            <td class="text-dark fw-bold py-2 px-3">${escapeHtml(d.duplicate_val)}</td>
                        </tr>
                    `;
                });

                html += `
                    <tr class="align-middle" id="bpkb-dup-row-${item.duplicate_id}">
                        <td colspan="4" class="p-0">
                            <div class="d-flex align-items-center justify-content-between p-3 bg-white border-bottom gap-3">
                                <div>
                                    <div class="fw-bold text-navy">BPKB: ${escapeHtml(item.duplicate_code)}</div>
                                    <span class="badge bg-secondary-subtle text-dark-emphasis small">Plat: ${escapeHtml(item.duplicate_nama)}</span>
                                </div>
                                <div class="text-secondary small">
                                    <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> ${escapeHtml(item.reason)}
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-success fw-bold btn-resolve-bpkb" data-action="merge" data-original-id="${item.original_id}" data-duplicate-id="${item.duplicate_id}">
                                        <i class="bi bi-intersect"></i> Gabungkan
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger fw-bold btn-resolve-bpkb" data-action="delete" data-original-id="${item.original_id}" data-duplicate-id="${item.duplicate_id}">
                                        <i class="bi bi-trash3"></i> Hapus
                                    </button>
                                    <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-bpkb-diff-${item.duplicate_id}">
                                        Bandingkan
                                    </button>
                                </div>
                            </div>
                            <div class="collapse bg-light p-3 border-bottom" id="collapse-bpkb-diff-${item.duplicate_id}">
                                <table class="table table-bordered table-sm mb-0 bg-white">
                                    <thead>
                                        <tr class="table-secondary">
                                            <th>Atribut</th>
                                            <th>Induk</th>
                                            <th>Ganda</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${diffHtml}
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                `;
            });
            container.innerHTML = html;
            attachBpkbEvents();
        }

        function renderDuplicateSertifikats(serts) {
            const container = document.getElementById('sert-dup-list');
            document.getElementById('sert-dup-count').textContent = serts.length;

            if (serts.length === 0) {
                container.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center py-5 text-success">
                            <i class="bi bi-patch-check-fill fs-1 text-success d-block mb-2"></i>
                            <h6 class="fw-bold mb-1">Database Bersih!</h6>
                            <p class="mb-0 small text-secondary">Tidak terdeteksi adanya nomor sertifikat ganda.</p>
                        </td>
                    </tr>
                `;
                return;
            }

            let html = '';
            serts.forEach(item => {
                let diffHtml = '';
                item.differences.forEach(d => {
                    const rowClass = d.is_different ? 'table-danger bg-danger bg-opacity-10' : '';
                    diffHtml += `
                        <tr class="${rowClass}">
                            <td class="fw-bold py-2 px-3">${escapeHtml(d.label)}</td>
                            <td class="text-secondary py-2 px-3">${escapeHtml(d.original_val)}</td>
                            <td class="text-dark fw-bold py-2 px-3">${escapeHtml(d.duplicate_val)}</td>
                        </tr>
                    `;
                });

                html += `
                    <tr class="align-middle" id="sert-dup-row-${item.duplicate_id}">
                        <td colspan="4" class="p-0">
                            <div class="d-flex align-items-center justify-content-between p-3 bg-white border-bottom gap-3">
                                <div>
                                    <div class="fw-bold text-navy">Sertifikat: ${escapeHtml(item.duplicate_code)}</div>
                                    <span class="badge bg-secondary-subtle text-dark-emphasis small">Pemilik: ${escapeHtml(item.duplicate_nama)}</span>
                                </div>
                                <div class="text-secondary small">
                                    <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> ${escapeHtml(item.reason)}
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-success fw-bold btn-resolve-sert" data-action="merge" data-original-id="${item.original_id}" data-duplicate-id="${item.duplicate_id}">
                                        <i class="bi bi-intersect"></i> Gabungkan
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger fw-bold btn-resolve-sert" data-action="delete" data-original-id="${item.original_id}" data-duplicate-id="${item.duplicate_id}">
                                        <i class="bi bi-trash3"></i> Hapus
                                    </button>
                                    <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-sert-diff-${item.duplicate_id}">
                                        Bandingkan
                                    </button>
                                </div>
                            </div>
                            <div class="collapse bg-light p-3 border-bottom" id="collapse-sert-diff-${item.duplicate_id}">
                                <table class="table table-bordered table-sm mb-0 bg-white">
                                    <thead>
                                        <tr class="table-secondary">
                                            <th>Atribut</th>
                                            <th>Induk</th>
                                            <th>Ganda</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${diffHtml}
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                `;
            });
            container.innerHTML = html;
            attachSertEvents();
        }

        function attachBpkbEvents() {
            document.querySelectorAll('.btn-resolve-bpkb').forEach(btn => {
                btn.addEventListener('click', function() {
                    const action = this.getAttribute('data-action');
                    const originalId = this.getAttribute('data-original-id');
                    const duplicateId = this.getAttribute('data-duplicate-id');
                    const btnEl = this;

                    Swal.fire({
                        title: action === 'merge' ? 'Gabungkan Berkas BPKB?' : 'Hapus Berkas Duplikat BPKB?',
                        text: action === 'merge' ? 'Seluruh riwayat permohonan scan/loan akan dipindahkan ke berkas utama.' : 'Data duplikat akan dihapus selamanya dari database.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: action === 'merge' ? '#198754' : '#dc3545',
                        confirmButtonText: 'Ya, Eksekusi!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch("{{ route('elabel.resolve-duplicate-bpkb') }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({ original_id: originalId, duplicate_id: duplicateId, action: action })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('Sukses', data.message, 'success');
                                    const row = document.getElementById(`bpkb-dup-row-${duplicateId}`);
                                    if (row) row.remove();
                                    
                                    const countEl = document.getElementById('bpkb-dup-count');
                                    const newCount = Math.max(0, parseInt(countEl.textContent) - 1);
                                    countEl.textContent = newCount;
                                    if (newCount === 0) renderDuplicateBpkbs([]);
                                } else {
                                    Swal.fire('Gagal', data.message, 'error');
                                }
                            });
                        }
                    });
                });
            });
        }

        function attachSertEvents() {
            document.querySelectorAll('.btn-resolve-sert').forEach(btn => {
                btn.addEventListener('click', function() {
                    const action = this.getAttribute('data-action');
                    const originalId = this.getAttribute('data-original-id');
                    const duplicateId = this.getAttribute('data-duplicate-id');
                    const btnEl = this;

                    Swal.fire({
                        title: action === 'merge' ? 'Gabungkan Berkas Sertifikat?' : 'Hapus Berkas Duplikat Sertifikat?',
                        text: action === 'merge' ? 'Atribut kosong pada record utama akan dilengkapi.' : 'Data duplikat akan dihapus selamanya dari database.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: action === 'merge' ? '#198754' : '#dc3545',
                        confirmButtonText: 'Ya, Eksekusi!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch("{{ route('elabel.resolve-duplicate-sertifikat') }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({ original_id: originalId, duplicate_id: duplicateId, action: action })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('Sukses', data.message, 'success');
                                    const row = document.getElementById(`sert-dup-row-${duplicateId}`);
                                    if (row) row.remove();
                                    
                                    const countEl = document.getElementById('sert-dup-count');
                                    const newCount = Math.max(0, parseInt(countEl.textContent) - 1);
                                    countEl.textContent = newCount;
                                    if (newCount === 0) renderDuplicateSertifikats([]);
                                } else {
                                    Swal.fire('Gagal', data.message, 'error');
                                }
                            });
                        }
                    });
                });
            });
        }

        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>'"]/g, function(m) { return map[m]; });
        }
    });
</script>
@endpush
@endif
@endsection
