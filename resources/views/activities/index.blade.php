@extends('layouts.app')

@section('title', 'Riwayat Aktivitas Terpadu')

@section('content')
<div class="container-fluid px-0">
    <!-- Header Halaman -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary fw-semibold px-2.5 py-1 rounded-pill small">
                    <i class="bi bi-shield-check me-1"></i> AUDIT TRAIL TERPADU
                </span>
                <span class="text-secondary small">&bull;</span>
                <span class="text-secondary small">Rekam Jejak Lintas Modul</span>
            </div>
            <h3 class="fw-bold text-navy mb-1">Log Aktivitas Sistem</h3>
            <p class="text-secondary mb-0 small">Memantau jejak perubahan data, entri baru, dan riwayat operasional E-RANDIS, SIPAT, dan eLABEL.</p>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('activities.clear') }}" method="POST" class="clear-logs-confirm">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger d-flex align-items-center gap-2 rounded-pill px-3 shadow-sm btn-sm">
                    <i class="bi bi-trash3"></i> Bersihkan Log
                </button>
            </form>
        </div>
    </div>

    <!-- Filter Toolbar: Tab Modul & Pencarian Cepat -->
    <div class="admin-card p-3 mb-3">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <!-- Navigasi Tab Modul Cepat -->
            <div class="d-flex flex-wrap gap-1.5 align-items-center">
                <a href="{{ route('activities.index', array_merge(request()->query(), ['module' => 'all'])) }}" 
                   class="btn btn-sm rounded-pill px-3 fw-medium {{ $selectedModule === 'all' ? 'btn-primary shadow-sm' : 'btn-light border text-secondary' }}">
                    Semua Modul <span class="badge {{ $selectedModule === 'all' ? 'bg-white text-primary' : 'bg-secondary-subtle text-secondary' }} rounded-pill ms-1">{{ number_format($counts['all'] ?? 0) }}</span>
                </a>
                <a href="{{ route('activities.index', array_merge(request()->query(), ['module' => 'erandis'])) }}" 
                   class="btn btn-sm rounded-pill px-3 fw-medium {{ $selectedModule === 'erandis' ? 'btn-warning text-dark shadow-sm' : 'btn-light border text-secondary' }}">
                    <i class="bi bi-car-front me-1"></i> E-RANDIS <span class="badge {{ $selectedModule === 'erandis' ? 'bg-dark text-white' : 'bg-secondary-subtle text-secondary' }} rounded-pill ms-1">{{ number_format($counts['erandis'] ?? 0) }}</span>
                </a>
                <a href="{{ route('activities.index', array_merge(request()->query(), ['module' => 'sipat'])) }}" 
                   class="btn btn-sm rounded-pill px-3 fw-medium {{ $selectedModule === 'sipat' ? 'btn-primary shadow-sm' : 'btn-light border text-secondary' }}">
                    <i class="bi bi-geo-alt me-1"></i> SIPAT <span class="badge {{ $selectedModule === 'sipat' ? 'bg-white text-primary' : 'bg-secondary-subtle text-secondary' }} rounded-pill ms-1">{{ number_format($counts['sipat'] ?? 0) }}</span>
                </a>
                <a href="{{ route('activities.index', array_merge(request()->query(), ['module' => 'elabel'])) }}" 
                   class="btn btn-sm rounded-pill px-3 fw-medium {{ $selectedModule === 'elabel' ? 'btn-info text-dark shadow-sm' : 'btn-light border text-secondary' }}">
                    <i class="bi bi-archive me-1"></i> eLABEL <span class="badge {{ $selectedModule === 'elabel' ? 'bg-dark text-white' : 'bg-secondary-subtle text-secondary' }} rounded-pill ms-1">{{ number_format($counts['elabel'] ?? 0) }}</span>
                </a>
            </div>

            <!-- Pencarian Cepat Teks -->
            <form method="GET" action="{{ route('activities.index') }}" class="d-flex align-items-center gap-2 m-0" style="min-width: 280px;">
                <input type="hidden" name="module" value="{{ $selectedModule }}">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-secondary">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           value="{{ $search ?? '' }}" 
                           class="form-control border-start-0 ps-0" 
                           placeholder="Cari aktivitas, nama pengguna...">
                    @if(!empty($search))
                        <a href="{{ route('activities.index', ['module' => $selectedModule]) }}" class="btn btn-outline-secondary" title="Hapus Pencarian">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                    <button type="submit" class="btn btn-primary px-3">Cari</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Aktivitas Ramping & Informatif -->
    <div class="admin-card overflow-hidden shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 activity-table">
                <thead class="bg-light text-secondary small">
                    <tr>
                        <th class="py-3 px-3" style="width: 150px;">Modul & Aksi</th>
                        <th class="py-3 px-3">Aktivitas & Perubahan Data</th>
                        <th class="py-3 px-3" style="width: 190px;">Pengguna</th>
                        <th class="py-3 px-3" style="width: 170px;">Waktu</th>
                        <th class="py-3 pe-3 text-end" style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activities as $activity)
                        @php
                            $hasBefore = !empty($activity->before_data);
                            $hasAfter = !empty($activity->after_data);

                            $moduleStyle = match($activity->module_key) {
                                'sipat' => ['badge' => 'bg-primary-subtle text-primary border-primary-subtle', 'icon' => 'bi-geo-alt-fill'],
                                'elabel' => ['badge' => 'bg-info-subtle text-info-emphasis border-info-subtle', 'icon' => 'bi-archive-fill'],
                                default => ['badge' => 'bg-warning-subtle text-dark border-warning-subtle', 'icon' => 'bi-car-front-fill'],
                            };

                            $actionDot = match($activity->type) {
                                'success' => 'text-success',
                                'danger' => 'text-danger',
                                'warning' => 'text-warning',
                                default => 'text-info',
                            };
                        @endphp
                        <tr class="activity-row"
                            style="cursor: pointer;"
                            data-bs-toggle="modal"
                            data-bs-target="#activityDetailModal"
                            data-source="{{ $activity->source }}"
                            data-module="{{ $activity->module_label }}"
                            data-description="{{ $activity->description }}"
                            data-user="{{ $activity->user->name ?? 'Sistem' }}"
                            data-created-at="{{ $activity->created_at->translatedFormat('d F Y H:i:s') }}"
                            data-before="{{ json_encode($activity->before_data) }}"
                            data-after="{{ json_encode($activity->after_data) }}"
                        >
                            <!-- Kolom 1: Modul & Aksi Terpadu -->
                            <td class="px-3">
                                <span class="badge border rounded-pill px-2.5 py-1.5 d-inline-flex align-items-center gap-1.5 font-monospace {{ $moduleStyle['badge'] }}" style="font-size: 0.72rem;">
                                    <i class="bi bi-circle-fill {{ $actionDot }}" style="font-size: 0.45rem;"></i>
                                    <i class="bi {{ $moduleStyle['icon'] }}"></i>
                                    <span>{{ $activity->module_label }}</span>
                                </span>
                            </td>

                            <!-- Kolom 2: Aktivitas + Indikator Payload Perubahan -->
                            <td class="px-3 py-2.5">
                                <div class="fw-semibold text-dark mb-1" style="font-size: 0.88rem; line-height: 1.35;">
                                    {{ $activity->description }}
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-1.5">
                                    @if($hasBefore && $hasAfter)
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill py-0.5 px-2" style="font-size: 0.68rem;">
                                            <i class="bi bi-arrow-left-right me-1"></i>Perubahan Data
                                        </span>
                                    @elseif($hasAfter)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill py-0.5 px-2" style="font-size: 0.68rem;">
                                            <i class="bi bi-plus-circle me-1"></i>Data Baru
                                        </span>
                                    @elseif($hasBefore)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill py-0.5 px-2" style="font-size: 0.68rem;">
                                            <i class="bi bi-dash-circle me-1"></i>Data Dihapus
                                        </span>
                                    @else
                                        <span class="text-secondary opacity-50" style="font-size: 0.68rem;">
                                            <i class="bi bi-chat-left-text me-1"></i>Pesan Log
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Kolom 3: Pengguna Ringkas -->
                            <td class="px-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center text-secondary flex-shrink-0" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                    <div class="overflow-hidden" style="max-width: 140px;">
                                        <div class="fw-semibold small text-truncate text-dark">{{ $activity->user->name ?? 'Sistem' }}</div>
                                        <div class="text-secondary text-truncate font-monospace" style="font-size: 0.7rem;">{{ $activity->user->email ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Kolom 4: Waktu Terformat -->
                            <td class="px-3">
                                <div class="fw-medium text-dark font-monospace" style="font-size: 0.78rem;">
                                    {{ $activity->created_at->translatedFormat('d M Y, H:i') }}
                                </div>
                                <div class="text-secondary" style="font-size: 0.72rem;">
                                    {{ $activity->created_at->diffForHumans() }}
                                </div>
                            </td>

                            <!-- Kolom 5: Aksi Panah Minimalis -->
                            <td class="pe-3 text-end">
                                <span class="btn btn-sm btn-light border rounded-circle p-0 d-inline-flex align-items-center justify-content-center shadow-none" style="width: 30px; height: 30px;">
                                    <i class="bi bi-chevron-right text-secondary small"></i>
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="py-4">
                                    <i class="bi bi-journal-x fs-1 text-secondary opacity-50"></i>
                                    <p class="text-secondary mt-2 mb-0">Belum ada riwayat aktivitas yang tercatat untuk filter ini.</p>
                                    @if(!empty($search) || $selectedModule !== 'all')
                                        <a href="{{ route('activities.index') }}" class="btn btn-sm btn-outline-primary rounded-pill mt-3 px-3">
                                            <i class="bi bi-arrow-clockwise me-1"></i> Reset Seluruh Filter
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($activities->hasPages())
            <div class="px-4 py-3 bg-light border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="small text-secondary">
                    Menampilkan <strong>{{ $activities->firstItem() ?? 0 }}</strong> sampai <strong>{{ $activities->lastItem() ?? 0 }}</strong> dari <strong>{{ $activities->total() }}</strong> aktivitas
                </span>
                <div>
                    {{ $activities->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Modal Detail Aktivitas dengan Dual-Mode (Tabel Diff & Raw JSON) -->
<div class="modal fade" id="activityDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom px-4 py-3 bg-light">
                <div>
                    <h5 class="modal-title fw-bold text-dark mb-0">Detail Jejak Aktivitas</h5>
                    <small class="text-secondary font-monospace" id="activityDetailMeta"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Deskripsi Utama -->
                <div class="p-3 bg-light rounded-3 border mb-3">
                    <div class="text-secondary small fw-semibold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Deskripsi Aktivitas</div>
                    <div class="fw-semibold text-dark fs-6" id="activityDetailDescription">-</div>
                </div>

                <!-- Metadata Singkat -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-4">
                        <small class="text-secondary d-block fw-semibold" style="font-size: 0.72rem;">MODUL</small>
                        <span class="fw-bold text-dark" id="activityDetailModule">-</span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-secondary d-block fw-semibold" style="font-size: 0.72rem;">DILAKUKAN OLEH</small>
                        <span class="fw-bold text-dark" id="activityDetailUser">-</span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-secondary d-block fw-semibold" style="font-size: 0.72rem;">WAKTU OPERASI</small>
                        <span class="fw-bold text-dark font-monospace small" id="activityDetailTime">-</span>
                    </div>
                </div>

                <!-- Tabs Mode Tampilan: Tabel Perbandingan vs JSON Mentah -->
                <ul class="nav nav-pills mb-3 p-1 bg-light rounded-pill border d-inline-flex" id="payloadTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-pill px-3 py-1 small fw-medium" id="diff-tab" data-bs-toggle="pill" data-bs-target="#diff-tab-pane" type="button" role="tab">
                            <i class="bi bi-table me-1"></i> Tabel Perbandingan
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill px-3 py-1 small fw-medium" id="json-tab" data-bs-toggle="pill" data-bs-target="#json-tab-pane" type="button" role="tab">
                            <i class="bi bi-code-slash me-1"></i> JSON Mentah
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="payloadTabContent">
                    <!-- Tab 1: Visual Table Diff -->
                    <div class="tab-pane fade show active" id="diff-tab-pane" role="tabpanel" tabindex="0">
                        <div id="diffTableContainer" class="border rounded-3 overflow-hidden">
                            <!-- Di-render dinamis oleh JavaScript -->
                        </div>
                    </div>

                    <!-- Tab 2: Raw JSON Dual Columns -->
                    <div class="tab-pane fade" id="json-tab-pane" role="tabpanel" tabindex="0">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3 h-100 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-semibold text-danger small"><i class="bi bi-arrow-left-circle me-1"></i> Data Sebelum</span>
                                        <button type="button" class="btn btn-link btn-sm p-0 text-secondary text-decoration-none small" onclick="copyJson('activityDetailBefore')">
                                            <i class="bi bi-clipboard me-1"></i>Salin
                                        </button>
                                    </div>
                                    <pre class="mb-0 text-dark activity-detail-code" id="activityDetailBefore">(Tidak ada data)</pre>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3 h-100 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-semibold text-success small"><i class="bi bi-arrow-right-circle me-1"></i> Data Sesudah</span>
                                        <button type="button" class="btn btn-link btn-sm p-0 text-secondary text-decoration-none small" onclick="copyJson('activityDetailAfter')">
                                            <i class="bi bi-clipboard me-1"></i>Salin
                                        </button>
                                    </div>
                                    <pre class="mb-0 text-dark activity-detail-code" id="activityDetailAfter">(Tidak ada data)</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-2.5 bg-light">
                <button type="button" class="btn btn-secondary rounded-pill px-4 btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<style>
.activity-table tbody tr {
    transition: background-color 0.15s ease-in-out;
}
.activity-table tbody tr:hover {
    background-color: rgba(var(--bs-primary-rgb, 13, 110, 253), 0.04) !important;
}
.activity-detail-code {
    white-space: pre-wrap;
    word-break: break-word;
    font-family: var(--bs-font-monospace, monospace);
    max-height: 280px;
    overflow-y: auto;
    font-size: 0.78rem;
    line-height: 1.45;
}
</style>

@endsection

@push('scripts')
<script>
function copyJson(elementId) {
    const el = document.getElementById(elementId);
    if (!el) return;
    navigator.clipboard.writeText(el.textContent).then(() => {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'JSON disalin ke clipboard',
                showConfirmButton: false,
                timer: 1500
            });
        } else {
            alert('JSON disalin ke clipboard');
        }
    });
}

(function initActivityModal() {
    const parseJSON = (value) => {
        if (!value || value === 'null' || value === 'undefined') return null;
        if (typeof value === 'object') return value;
        try {
            return JSON.parse(value);
        } catch (e) {
            return null;
        }
    };

    const formatValue = (value) => {
        if (value === null || value === undefined || value === '') return '(Tidak ada data)';
        if (typeof value === 'object') {
            if (Object.keys(value).length === 0) return '(Tidak ada data)';
            return JSON.stringify(value, null, 2);
        }
        if (typeof value === 'string') {
            try {
                const parsed = JSON.parse(value);
                if (parsed && typeof parsed === 'object') {
                    if (Object.keys(parsed).length === 0) return '(Tidak ada data)';
                    return JSON.stringify(parsed, null, 2);
                }
            } catch (e) {}
            return value;
        }
        return String(value);
    };

    function renderDiffTable(before, after) {
        const container = document.getElementById('diffTableContainer');
        if (!container) return;

        const isObject = (val) => val !== null && typeof val === 'object' && !Array.isArray(val);

        if (!before && !after) {
            container.innerHTML = `
                <div class="p-4 text-center text-secondary">
                    <i class="bi bi-chat-square-text fs-2 d-block mb-2 opacity-50"></i>
                    <p class="mb-0 small fw-medium">Aktivitas ini hanya mencatat pesan log status tanpa rekaman perubahan data kolom.</p>
                </div>
            `;
            return;
        }

        const beforeObj = isObject(before) ? before : {};
        const afterObj = isObject(after) ? after : {};
        const allKeys = Array.from(new Set([...Object.keys(beforeObj), ...Object.keys(afterObj)]));

        if (allKeys.length === 0) {
            container.innerHTML = `
                <div class="p-3 text-center text-secondary small">
                    Tidak ada atribut terstruktur yang dapat dibandingkan.
                </div>
            `;
            return;
        }

        let html = `
            <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 0.8rem;">
                <thead class="bg-light text-secondary">
                    <tr>
                        <th class="ps-3 py-2" style="width: 30%;">Atribut / Kolom</th>
                        <th class="py-2 text-danger" style="width: 35%;"><i class="bi bi-arrow-left-circle me-1"></i>Nilai Sebelum</th>
                        <th class="py-2 text-success" style="width: 35%;"><i class="bi bi-arrow-right-circle me-1"></i>Nilai Sesudah</th>
                    </tr>
                </thead>
                <tbody>
        `;

        allKeys.forEach(key => {
            const valBefore = beforeObj[key] !== undefined ? String(beforeObj[key]) : '<span class="text-secondary opacity-50">(Kosong)</span>';
            const valAfter = afterObj[key] !== undefined ? String(afterObj[key]) : '<span class="text-secondary opacity-50">(Kosong)</span>';
            const isChanged = beforeObj[key] !== afterObj[key];

            const rowClass = isChanged ? 'bg-warning-subtle bg-opacity-10' : '';
            const keyLabel = key.replace(/_/g, ' ').toUpperCase();

            html += `
                <tr class="${rowClass}">
                    <td class="ps-3 py-2 fw-semibold text-dark font-monospace">${keyLabel}</td>
                    <td class="py-2 text-break font-monospace ${isChanged && beforeObj[key] !== undefined ? 'text-danger fw-medium' : 'text-secondary'}">${valBefore}</td>
                    <td class="py-2 text-break font-monospace ${isChanged && afterObj[key] !== undefined ? 'text-success fw-bold' : 'text-secondary'}">${valAfter}</td>
                </tr>
            `;
        });

        html += `</tbody></table>`;
        container.innerHTML = html;
    }

    function populateModal(triggerEl) {
        if (!triggerEl) return;
        const row = triggerEl.closest('.activity-row') || triggerEl;
        if (!row || !row.dataset) return;

        const ds = row.dataset;
        const metaEl = document.getElementById('activityDetailMeta');
        const descEl = document.getElementById('activityDetailDescription');
        const modEl  = document.getElementById('activityDetailModule');
        const userEl = document.getElementById('activityDetailUser');
        const timeEl = document.getElementById('activityDetailTime');

        if (metaEl) metaEl.textContent = `${ds.source || 'activities'} • ${ds.createdAt || '-'}`;
        if (descEl) descEl.textContent = ds.description || '-';
        if (modEl)  modEl.textContent  = ds.module || '-';
        if (userEl) userEl.textContent = ds.user || '-';
        if (timeEl) timeEl.textContent = ds.createdAt || '-';

        const before = parseJSON(ds.before);
        const after  = parseJSON(ds.after);

        const beforeEl = document.getElementById('activityDetailBefore');
        const afterEl  = document.getElementById('activityDetailAfter');

        if (beforeEl) beforeEl.textContent = formatValue(before);
        if (afterEl)  afterEl.textContent  = formatValue(after);

        renderDiffTable(before, after);

        // Reset tab to Tab 1 (Tabel Perbandingan)
        const diffTabBtn = document.getElementById('diff-tab');
        if (diffTabBtn && window.bootstrap && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(diffTabBtn).show();
        }
    }

    // Bind using standard Bootstrap 5 show.bs.modal
    const modalEl = document.getElementById('activityDetailModal');
    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', function (event) {
            populateModal(event.relatedTarget);
        });
    }

    // Fallback: Bind click on rows
    document.addEventListener('click', function (event) {
        const row = event.target.closest('.activity-row');
        if (row) {
            populateModal(row);
        }
    });

    // Clear Logs Confirmation with Checklist Popup
    const clearLogsForm = document.querySelector('.clear-logs-confirm');
    if (clearLogsForm) {
        clearLogsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const root = document.getElementById('theme-root');
            const theme = root ? root.getAttribute('data-theme') : 'light';
            
            Swal.fire({
                title: 'Bersihkan Log Aktivitas',
                html: `
                    <p class="text-start mb-3 small text-secondary">Pilih modul log aktivitas yang ingin Anda bersihkan:</p>
                    <div class="text-start px-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="clear_erandis" checked>
                            <label class="form-check-label fw-medium" for="clear_erandis">
                                E-RANDIS (Log Sistem/Kendaraan)
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="clear_sipat">
                            <label class="form-check-label fw-medium" for="clear_sipat">
                                SIPAT (Log Audit Tanah)
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="clear_elabel">
                            <label class="form-check-label fw-medium" for="clear_elabel">
                                eLABEL (Log QR Code Aset)
                            </label>
                        </div>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Bersihkan!',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                background: theme === 'dark' ? '#1e293b' : '#ffffff',
                color: theme === 'dark' ? '#f1f5f9' : '#1e293b',
                preConfirm: () => {
                    const erandis = document.getElementById('clear_erandis').checked;
                    const sipat = document.getElementById('clear_sipat').checked;
                    const elabel = document.getElementById('clear_elabel').checked;

                    if (!erandis && !sipat && !elabel) {
                        Swal.showValidationMessage('Harap pilih minimal satu modul!');
                        return false;
                    }

                    return { erandis, sipat, elabel };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const data = result.value;
                    Object.keys(data).forEach(key => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `modules[${key}]`;
                        input.value = data[key] ? '1' : '0';
                        clearLogsForm.appendChild(input);
                    });

                    clearLogsForm.submit();
                }
            });
        });
    }
})();
</script>
@endpush
