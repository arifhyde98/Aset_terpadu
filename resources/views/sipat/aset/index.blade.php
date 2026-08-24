@extends('layouts.app')

@section('content')
<style>
    .table-container-sipat {
        border-radius: 1rem;
        border: 1px solid var(--border-color, rgba(0,0,0,0.08));
    }
    .aset-table thead th {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 0.75rem 0.85rem;
        border-bottom: 2px solid var(--border-color, rgba(0,0,0,0.08));
        white-space: nowrap;
    }
    .aset-table tbody td {
        padding: 0.65rem 0.85rem;
        vertical-align: middle;
        font-size: 0.82rem;
    }
    .badge-status {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 50rem;
        font-size: 0.72rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .bulk-floating-bar {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1050;
        background: #0f172a;
        color: #fff;
        padding: 10px 24px;
        border-radius: 50rem;
        box-shadow: 0 10px 30px rgba(15,23,42,0.3);
        display: none;
        align-items: center;
        gap: 1rem;
    }
</style>

<div class="container-fluid px-0">
    <!-- Page Header Bar -->
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary fw-semibold px-2.5 py-1 rounded-pill" style="font-size: 0.75rem;">
                    <i class="bi bi-geo-alt-fill me-1"></i> SIPAT ASET TANAH
                </span>
                <span class="text-secondary small">&bull;</span>
                <span class="text-secondary small">KIB A Pemerintah Kabupaten Donggala</span>
            </div>
            <h2 class="fw-bold mb-1">Daftar Aset Tanah</h2>
            <p class="text-secondary small mb-0">Monitoring data bidang tanah, status pensertifikatan BPN, dan pengamanan fisik</p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <!-- Dropdown Export -->
            <div class="dropdown">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2 rounded-3" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download"></i> Export Data
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    <li><a class="dropdown-item py-2" href="{{ route('sipat.aset.index') }}?export=print" target="_blank"><i class="bi bi-file-pdf text-danger me-2"></i> Pratinjau Cetak / PDF</a></li>
                    <li><a class="dropdown-item py-2" href="{{ route('sipat.aset.index') }}?export=csv"><i class="bi bi-file-earmark-spreadsheet text-success me-2"></i> Download Data CSV</a></li>
                </ul>
            </div>

            @if(in_array(auth()->user()->role->value, ['superadmin', 'admin']))
                <button type="button" class="btn btn-outline-warning d-flex align-items-center gap-2 rounded-3" id="btnCheckDuplicates">
                    <i class="bi bi-exclamation-triangle"></i> Diagnosis Ganda
                </button>
            @endif

            <a href="{{ route('sipat.aset.create') }}" class="btn btn-primary d-flex align-items-center gap-2 rounded-3">
                <i class="bi bi-plus-lg"></i> Tambah Aset Tanah
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card clean-card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('sipat.aset.index') }}" id="filterForm">
                <div class="row g-2 align-items-end">
                    <!-- 1. OPD Filter -->
                    <div class="col-12 col-sm-6 col-md-3 col-xl-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">OPD Pengelola</label>
                        <select name="opd_id" class="form-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="">-- Semua OPD --</option>
                            <option value="KOSONG" {{ request('opd_id', request('opd')) === 'KOSONG' ? 'selected' : '' }}>[Tanpa OPD / Kosong]</option>
                            @foreach($opdList as $opd)
                                <option value="{{ $opd->id }}" {{ (string) request('opd_id', request('opd')) === (string) $opd->id ? 'selected' : '' }}>{{ $opd->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 2. Multi-select Checkbox Status Filter Dropdown -->
                    <div class="col-12 col-sm-6 col-md-3 col-xl-2">
                        <label class="form-label small fw-semibold text-secondary mb-1">
                            Status BPN <span class="badge bg-warning-subtle text-body px-1.5 py-0.5 rounded-pill" style="font-size: 0.65rem;">Centang</span>
                        </label>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary bg-body border-0 text-start w-100 dropdown-toggle d-flex align-items-center justify-content-between py-2" type="button" id="dropdownStatusFilter" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                <span class="text-truncate small">
                                    @php
                                        $selectedStatuses = (array) request('status');
                                        $selectedCount = count(array_filter($selectedStatuses));
                                    @endphp
                                    @if($selectedCount > 0)
                                        <span class="badge bg-primary me-1">{{ $selectedCount }}</span> Terpilih
                                    @else
                                        Pilih Status BPN...
                                    @endif
                                </span>
                            </button>
                            <div class="dropdown-menu p-3 shadow-lg border-0 rounded-4" style="min-width: 250px; max-height: 320px; overflow-y: auto;">
                                <div class="fw-semibold small text-secondary mb-2 border-bottom pb-1">Centang Status BPN:</div>
                                @foreach($statusList as $st)
                                    <div class="form-check mb-1.5">
                                        <input class="form-check-input status-checkbox" type="checkbox" name="status[]" value="{{ $st->id_status }}" id="status_chk_{{ $st->id_status }}"
                                        {{ (is_array(request('status')) && in_array($st->id_status, request('status'))) || request('status') == $st->id_status ? 'checked' : '' }} onchange="document.getElementById('filterForm').submit()">
                                        <label class="form-check-label small fw-medium text-body cursor-pointer" for="status_chk_{{ $st->id_status }}">
                                            <span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background-color: {{ $st->warna ?? '#3b82f6' }};"></span>
                                            {{ $st->nama_status }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- 3. Tgl Perolehan -->
                    <div class="col-12 col-sm-6 col-md-2 col-xl-2">
                        <label class="form-label small fw-semibold text-secondary mb-1">Tgl Perolehan</label>
                        <input type="date" name="tanggal_perolehan" class="form-control" value="{{ request('tanggal_perolehan') }}" onchange="document.getElementById('filterForm').submit()">
                    </div>

                    <!-- 4. Per Page Limit -->
                    <div class="col-6 col-sm-3 col-md-1 col-xl-1">
                        <label class="form-label small fw-semibold text-secondary mb-1">Tampil</label>
                        <select name="per_page" class="form-select px-2" onchange="document.getElementById('filterForm').submit()">
                            <option value="15" {{ request('per_page') == '15' ? 'selected' : '' }}>15</option>
                            <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100</option>
                            <option value="all" {{ request('per_page') == 'all' ? 'selected' : '' }}>Semua</option>
                        </select>
                    </div>

                    <!-- 5. Search Bar -->
                    <div class="col-12 col-sm-9 col-md-3 col-xl-4">
                        <label class="form-label small fw-semibold text-secondary mb-1">Pencarian Cepat</label>
                        <div class="input-group">
                            <span class="input-group-text bg-body border-0 text-secondary"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Kode Aset, Nama Aset, Alamat..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary px-3">Cari</button>
                            @if(request()->hasAny(['search', 'opd_id', 'opd', 'status', 'tanggal_perolehan']))
                                <a href="{{ route('sipat.aset.index') }}" class="btn btn-outline-secondary px-3"><i class="bi bi-x-circle"></i> Reset</a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table Container -->
    <div class="card clean-card border-0 shadow-sm table-container-sipat overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 aset-table">
                <thead class="bg-body text-secondary">
                    <tr>
                        <th class="ps-3 py-3" style="width: 40px;">
                            <input type="checkbox" id="checkAll" class="form-check-input">
                        </th>
                        <th class="py-3" style="width: 50px;">NO</th>
                        <th class="py-3">NAMA ASET / PERUNTUKAN</th>
                        <th class="py-3">LUAS (M²)</th>
                        <th class="py-3">OPD PENGELOLA</th>
                        <th class="py-3">ALAMAT / LOKASI</th>
                        <th class="py-3">STATUS PROSES BPN</th>
                        <th class="text-center py-3 pe-3" style="width: 140px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($asetTanah as $index => $item)
                        <tr>
                            <td class="ps-3">
                                <input type="checkbox" class="form-check-input aset-checkbox" value="{{ $item->id_aset }}">
                            </td>
                            <td class="fw-medium text-secondary" style="font-size: 0.8rem;">
                                {{ $asetTanah->firstItem() + $index }}
                            </td>
                            <td>
                                <div class="fw-semibold text-body">{{ $item->nama_aset }}</div>
                                <small class="text-secondary">{{ $item->peruntukan ?? 'KIB A Tanah' }}</small>
                            </td>
                            <td>
                                <span class="fw-bold text-body">{{ number_format($item->luas ?? 0, 0, ',', '.') }}</span> <small class="text-secondary">m²</small>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-body-secondary fw-normal px-2.5 py-1 text-wrap" style="font-size: 0.78rem; max-width: 200px;">
                                    {{ $item->opdSipat->nama ?? $item->opd ?? '-' }}
                                </span>
                            </td>
                            <td style="max-width: 220px;">
                                <div class="text-truncate small text-secondary" title="{{ $item->alamat }}">
                                    <i class="bi bi-geo-alt me-1 text-danger"></i> {{ $item->alamat ?? 'Kabupaten Donggala' }}
                                </div>
                            </td>
                            <td>
                                @php
                                    $stObj = $item->latestProses->statusProses ?? null;
                                    $statusName = $stObj->nama_status ?? 'Belum Diurus';
                                    $colorName = $stObj->warna ?? 'secondary';
                                    
                                    $badgeClass = 'bg-secondary';
                                    if ($colorName === 'success' || str_contains(strtolower($statusName), 'sertifikat') || str_contains(strtolower($statusName), 'selesai')) {
                                        $badgeClass = 'bg-success-subtle text-success border border-success-subtle';
                                    } elseif ($colorName === 'warning' || str_contains(strtolower($statusName), 'proses') || str_contains(strtolower($statusName), 'ukur')) {
                                        $badgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                                    } elseif ($colorName === 'danger' || str_contains(strtolower($statusName), 'kendala') || str_contains(strtolower($statusName), 'masalah') || str_contains(strtolower($statusName), 'sengketa')) {
                                        $badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                                    } elseif ($colorName === 'info' || str_contains(strtolower($statusName), 'pertek')) {
                                        $badgeClass = 'bg-info-subtle text-info border border-info-subtle';
                                    }
                                @endphp
                                <span class="badge badge-status {{ $badgeClass }}">
                                    <span class="d-inline-block rounded-circle me-1" style="width: 6px; height: 6px; background-color: currentColor;"></span>
                                    {{ $statusName }}
                                </span>
                            </td>
                            <td class="text-center pe-3">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" onclick="showDetail({{ $item->id_aset }})" data-bs-toggle="tooltip" title="Lihat Detail Modal (5 Tab)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <a href="{{ route('sipat.aset.edit', $item->id_aset) }}" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Edit Aset">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('sipat.aset.destroy', $item->id_aset) }}" method="POST" class="d-inline delete-confirm">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" data-bs-toggle="tooltip" title="Hapus Aset">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-secondary">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                Tidak ada data aset tanah yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($asetTanah->hasPages())
            <div class="card-footer bg-transparent border-0 p-3 d-flex align-items-center justify-content-between">
                <span class="small text-secondary">Menampilkan {{ $asetTanah->firstItem() }} sampai {{ $asetTanah->lastItem() }} dari total {{ $asetTanah->total() }} aset</span>
                {{ $asetTanah->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Floating Bulk Action Bar -->
<div id="bulkFloatingBar" class="bulk-floating-bar">
    <span class="small"><strong id="selectedCount">0</strong> aset dipilih</span>
    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalBulkStatus">Ubah Status Massal</button>
    <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-3" id="btnClearBulk">Batal</button>
</div>

@push('modals')
<!-- Modal Ubah Status Massal -->
<div class="modal fade" id="modalBulkStatus" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header bg-primary-subtle border-bottom px-4 py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-layers-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Ubah Status Massal</h5>
                        <small class="text-primary fw-medium">Pembaruan Riwayat Status Kolektif</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('sipat.aset.bulkProses') }}" method="post" id="formBulkStatus" onsubmit="handleBulkSubmit(this)">
                @csrf
                <div id="bulkSelectedInputsContainer"></div>
                <div class="modal-body p-4">
                    <div class="alert alert-primary border-0 d-flex align-items-center gap-3 mb-3 p-3 rounded-3" style="font-size: 0.85rem;">
                        <i class="bi bi-info-circle-fill fs-4 text-primary"></i>
                        <div>Anda akan memperbarui status untuk <strong id="modalBulkCount" class="badge bg-primary fs-6 px-2 py-1 ms-1">0</strong> aset sekaligus.</div>
                    </div>

                    <div class="card bg-light border-0 rounded-3 p-3 mb-3">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Pilih Status Proses Baru <span class="text-danger">*</span></label>
                            <select name="id_status" class="form-select" required>
                                <option value="">-- Pilih Status Proses --</option>
                                @foreach ($statusList as $st)
                                    <option value="{{ $st->id_status }}">{{ $st->nama_status }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-secondary mb-1">Tanggal Proses <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_proses" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="card bg-light border-0 rounded-3 p-3 mb-3">
                        <a class="small text-decoration-none fw-semibold text-primary d-inline-flex align-items-center gap-2" data-bs-toggle="collapse" href="#collapseNibarList" role="button" aria-expanded="false">
                            <i class="bi bi-clipboard-plus fs-6"></i> + Tempel / Masukkan Daftar NIBAR Massal
                        </a>
                        <div class="collapse mt-2" id="collapseNibarList">
                            <textarea name="nibar_list" class="form-control font-monospace p-2" rows="3" style="font-size: 0.8rem;" placeholder="Tempel daftar NIBAR di sini (dipisahkan baris/koma)...&#10;Contoh:&#10;12.01.02.01.001&#10;12.01.02.01.002"></textarea>
                        </div>
                    </div>

                    <div>
                        <label class="form-label small fw-semibold text-secondary mb-1">Keterangan / Catatan</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan proses massal (opsional)...">Update status massal</textarea>
                    </div>
                </div>
                <div class="modal-footer border-top pt-2 px-4 pb-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm" id="btnSubmitBulk">Simpan Pembaruan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Remote Container (Renders at document body root level) -->
<div class="modal fade" id="modalDetailAset" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow" id="modalDetailContent">
            <div class="p-5 text-center text-secondary">
                <div class="spinner-border text-primary me-2" role="status"></div> Memuat data detail aset...
            </div>
        </div>
    </div>
</div>

@if(in_array(auth()->user()->role->value, ['superadmin', 'admin']))
<!-- DIAGNOSIS DUPLICATES MODAL -->
<x-modal id="diagnosisDuplicatesModal" title="Diagnosis & Bersihkan Data Ganda SIPAT" size="xl">
    <div class="modal-body p-4 bg-light">
        <!-- Tab Navigation -->
        <ul class="nav nav-tabs nav-fill mb-4 border-bottom" id="duplicateTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold text-navy py-3 d-flex align-items-center justify-content-center gap-2" id="asets-tab" data-bs-toggle="tab" data-bs-target="#asets-pane" type="button" role="tab" aria-controls="asets-pane" aria-selected="true">
                    <i class="bi bi-geo-alt-fill fs-5 text-warning"></i>
                    <span>Aset Tanah Ganda / Identik (<span id="aset-dup-count">0</span>)</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold text-navy py-3 d-flex align-items-center justify-content-center gap-2" id="opds-tab" data-bs-toggle="tab" data-bs-target="#opds-pane" type="button" role="tab" aria-controls="opds-pane" aria-selected="false">
                    <i class="bi bi-building-fill fs-5 text-info"></i>
                    <span>OPD Pertanahan Ganda & Mirip (<span id="opd-dup-count">0</span>)</span>
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="duplicateTabsContent">
            
            <!-- PANE 1: ASET DUPLICATES -->
            <div class="tab-pane fade show active" id="asets-pane" role="tabpanel" aria-labelledby="asets-tab" tabindex="0">
                <div class="alert alert-warning border-0 bg-warning bg-opacity-10 text-navy d-flex align-items-center mb-4 rounded-3 shadow-none">
                    <div class="fs-4 me-3 text-warning"><i class="bi bi-info-circle-fill"></i></div>
                    <div>
                        <h6 class="alert-heading fw-bold mb-1" style="font-size: 0.9rem;">Instruksi Pembersihan Aset Tanah</h6>
                        <p class="mb-0 small text-secondary">
                            Aset terdeteksi ganda berdasarkan NIB atau nama. Anda dapat:
                            <br>1. <strong>Gabungkan Data</strong>: Menyalin kelengkapan data kosong dari baris ganda ke baris asli, memindahkan riwayat proses sertifikasi, surat SKPT, dokumen lampiran, dan pengamanan fisik, lalu menghapus baris ganda.
                            <br>2. <strong>Hapus Duplikat</strong>: Menghapus baris ganda secara langsung.
                        </p>
                    </div>
                </div>
                
                <div class="table-responsive border rounded-3 bg-white shadow-sm" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="table-navy text-white text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px; position: sticky; top: 0; z-index: 2;">
                            <tr>
                                <th class="px-3 py-3" style="width: 25%;">Data Ganda (Hasil Impor/Baru)</th>
                                <th class="px-3 py-3" style="width: 25%;">Data Induk (Asli/Lama)</th>
                                <th class="px-3 py-3" style="width: 30%;">Indikasi Duplikasi</th>
                                <th class="px-3 py-3 text-center" style="width: 20%;">Aksi Resolusi</th>
                            </tr>
                        </thead>
                        <tbody id="aset-dup-list">
                            <tr>
                                <td colspan="4" class="text-center py-5 text-secondary">
                                    <div class="spinner-border text-warning mb-2" role="status"></div>
                                    <div class="small fw-medium">Sedang memindai duplikasi aset...</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PANE 2: OPD DUPLICATES -->
            <div class="tab-pane fade" id="opds-pane" role="tabpanel" aria-labelledby="opds-tab" tabindex="0">
                <div class="alert alert-info border-0 bg-info bg-opacity-10 text-navy d-flex align-items-center mb-4 rounded-3 shadow-none">
                    <div class="fs-4 me-3 text-info"><i class="bi bi-info-circle-fill"></i></div>
                    <div>
                        <h6 class="alert-heading fw-bold mb-1" style="font-size: 0.9rem;">Instruksi Konsolidasi OPD Pertanahan</h6>
                        <p class="mb-0 small text-secondary">
                            OPD terdeteksi mirip berdasarkan analisis teks.
                            <br>Menekan tombol <strong>Gabungkan Instansi</strong> akan **memindahkan seluruh data aset tanah** dari OPD duplikat (OPD B) ke OPD utama (OPD A), memperbarui pemetaan OPD, memindahkan berkas eLABEL terkait, lalu menghapus OPD duplikat kosong secara bersih.
                        </p>
                    </div>
                </div>
                
                <div class="table-responsive border rounded-3 bg-white shadow-sm" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="table-navy text-white text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px; position: sticky; top: 0; z-index: 2;">
                            <tr>
                                <th class="px-3 py-3" style="width: 35%;">OPD Utama (Dipertahankan)</th>
                                <th class="px-3 py-3" style="width: 35%;">OPD Duplikat (Akan Dihapus)</th>
                                <th class="px-3 py-3" style="width: 15%;">Indikasi</th>
                                <th class="px-3 py-3 text-center" style="width: 15%;">Aksi Konsolidasi</th>
                            </tr>
                        </thead>
                        <tbody id="opd-dup-list">
                            <tr>
                                <td colspan="4" class="text-center py-5 text-secondary">
                                    <div class="spinner-border text-info mb-2" role="status"></div>
                                    <div class="small fw-medium">Sedang memindai duplikasi OPD...</div>
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
@endif
@endpush

@push('scripts')
<script>
    let detailModalInstance = null;

    document.addEventListener("DOMContentLoaded", function() {
        const checkAll = document.getElementById('checkAll');
        const checkboxes = document.querySelectorAll('.aset-checkbox');
        const bulkBar = document.getElementById('bulkFloatingBar');
        const selectedCount = document.getElementById('selectedCount');
        const btnClearBulk = document.getElementById('btnClearBulk');

        // Pindahkan bulkBar ke body utama agar terlepas dari container yang memiliki CSS transform/overflow
        if (bulkBar && bulkBar.parentNode !== document.body) {
            document.body.appendChild(bulkBar);
        }

        console.log('Bulk init:', { checkAll: !!checkAll, cbCount: checkboxes.length, bulkBar: !!bulkBar });

        window.updateBulkBar = function() {
            const checked = document.querySelectorAll('.aset-checkbox:checked');
            console.log('checked count:', checked.length);
            if (checked.length > 0) {
                if (selectedCount) selectedCount.innerText = checked.length;
                const modalCount = document.getElementById('modalBulkCount');
                if (modalCount) modalCount.innerText = checked.length;
                if (bulkBar) {
                    bulkBar.classList.remove('d-none');
                    bulkBar.style.setProperty('display', 'flex', 'important');
                }
            } else {
                if (bulkBar) {
                    bulkBar.style.setProperty('display', 'none', 'important');
                }
            }
        }

        if (checkAll) {
            checkAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = checkAll.checked);
                updateBulkBar();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkBar);
        });

        if (btnClearBulk) {
            btnClearBulk.addEventListener('click', function() {
                checkboxes.forEach(cb => cb.checked = false);
                if (checkAll) checkAll.checked = false;
                updateBulkBar();
            });
        }

        // ==========================================
        // MAGIC BUTTON: DIAGNOSIS DUPLIKASI DATA SIPAT
        // ==========================================
        const btnCheckDuplicates = document.getElementById('btnCheckDuplicates');
        let diagnosisModal = null;
        
        if (document.getElementById('diagnosisDuplicatesModal')) {
            diagnosisModal = new bootstrap.Modal(document.getElementById('diagnosisDuplicatesModal'));
        }

        if (btnCheckDuplicates && diagnosisModal) {
            btnCheckDuplicates.addEventListener('click', function() {
                document.getElementById('aset-dup-list').innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center py-5 text-secondary">
                            <div class="spinner-border text-warning mb-2" role="status"></div>
                            <div class="small fw-medium">Sedang mendiagnosis database aset tanah ganda...</div>
                        </td>
                    </tr>
                `;
                document.getElementById('opd-dup-list').innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center py-5 text-secondary">
                            <div class="spinner-border text-info mb-2" role="status"></div>
                            <div class="small fw-medium">Sedang memindai kemiripan instansi OPD SIPAT...</div>
                        </td>
                    </tr>
                `;
                document.getElementById('aset-dup-count').textContent = '0';
                document.getElementById('opd-dup-count').textContent = '0';

                diagnosisModal.show();

                fetch("{{ route('sipat.aset.check-duplicates') }}", {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderDuplicateAsets(data.asets);
                        renderDuplicateOpds(data.opds);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Diagnosis Gagal',
                            text: data.message || 'Terjadi kesalahan saat memeriksa data.',
                            confirmButtonColor: '#1e40af',
                        });
                        diagnosisModal.hide();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error Koneksi',
                        text: 'Gagal terhubung ke database untuk memindai duplikasi.',
                        confirmButtonColor: '#1e40af',
                    });
                    diagnosisModal.hide();
                });
            });
        }
    });

    function renderDuplicateAsets(asets) {
        const listContainer = document.getElementById('aset-dup-list');
        document.getElementById('aset-dup-count').textContent = asets.length;

        if (asets.length === 0) {
            listContainer.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center py-5 text-success">
                        <i class="bi bi-patch-check-fill fs-1 text-success d-block mb-2"></i>
                        <h6 class="fw-bold mb-1">Database Bersih!</h6>
                        <p class="mb-0 small text-secondary">Tidak terdeteksi adanya NIB ganda di sistem.</p>
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        asets.forEach((item) => {
            let diffRowsHtml = '';
            item.differences.forEach(diff => {
                const rowClass = diff.is_different ? 'table-danger bg-danger bg-opacity-10' : '';
                const badgeHtml = diff.is_different 
                    ? '<span class="badge bg-danger text-white px-2 py-0.5" style="font-size: 0.65rem;">Berbeda</span>' 
                    : '<span class="badge bg-light text-secondary border px-2 py-0.5" style="font-size: 0.65rem;">Identik</span>';

                diffRowsHtml += `
                    <tr class="${rowClass}">
                        <td class="fw-bold text-navy py-2 px-3" style="width: 25%; font-size: 0.8rem;">${escapeHtml(diff.label)}</td>
                        <td class="text-secondary py-2 px-3" style="width: 35%; font-size: 0.8rem;">${escapeHtml(diff.original_val)}</td>
                        <td class="text-dark fw-bold py-2 px-3" style="width: 30%; font-size: 0.8rem;">${escapeHtml(diff.duplicate_val)}</td>
                        <td class="text-center py-2 px-3" style="width: 10%;">${badgeHtml}</td>
                    </tr>
                `;
            });

            html += `
                <tr class="align-middle border-bottom-0" id="aset-dup-row-${item.duplicate_id}">
                    <td colspan="4" class="p-0">
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between p-3 bg-white border-bottom gap-3">
                            <div class="d-flex flex-wrap align-items-center gap-3 flex-grow-1">
                                <div class="text-center bg-light border rounded px-3 py-1.5" style="min-width: 105px;">
                                    <div class="text-secondary small fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.5px;">NIB GANDA</div>
                                    <span class="badge bg-danger text-white fw-bold px-2 py-0.5" style="font-size: 0.8rem;">${escapeHtml(item.duplicate_code)}</span>
                                </div>
                                <div class="text-center bg-light border rounded px-3 py-1.5" style="min-width: 105px;">
                                    <div class="text-secondary small fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.5px;">NIB INDUK</div>
                                    <span class="badge bg-success text-white fw-bold px-2 py-0.5" style="font-size: 0.8rem;">${escapeHtml(item.original_code || '-')}</span>
                                </div>
                                <div class="ms-2">
                                    <div class="fw-bold text-navy mb-0" style="font-size: 0.9rem;">${escapeHtml(item.duplicate_nama)}</div>
                                    <div class="text-secondary small" style="font-size: 0.75rem;"><i class="bi bi-building me-1"></i>${escapeHtml(item.duplicate_opd)}</div>
                                </div>
                                <div class="ms-md-auto d-flex align-items-center gap-2">
                                    <span class="badge bg-warning text-dark px-2.5 py-1.5 d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                        <span>${escapeHtml(item.reason)}</span>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-success fw-bold d-inline-flex align-items-center gap-1 btn-resolve-aset" data-action="merge" data-original-id="${item.original_id}" data-duplicate-id="${item.duplicate_id}">
                                    <i class="bi bi-intersect"></i> <span>Gabungkan</span>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger fw-bold d-inline-flex align-items-center gap-1 btn-resolve-aset" data-action="delete" data-original-id="${item.original_id}" data-duplicate-id="${item.duplicate_id}">
                                    <i class="bi bi-trash3"></i> <span>Hapus</span>
                                </button>
                                <button class="btn btn-sm btn-light border shadow-sm ms-1 fw-medium" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-aset-diff-${item.duplicate_id}">
                                    <i class="bi bi-eye me-1 text-info"></i> Bandingkan
                                </button>
                            </div>
                        </div>
                        
                        <div class="collapse bg-light p-3 border-bottom" id="collapse-aset-diff-${item.duplicate_id}">
                            <div class="card card-body border-0 shadow-none p-0 bg-transparent">
                                <div class="table-responsive border rounded-3 bg-white">
                                    <table class="table table-hover table-bordered table-sm mb-0 align-middle">
                                        <thead class="table-secondary" style="font-size: 0.72rem; text-transform: uppercase;">
                                            <tr>
                                                <th class="py-2 px-3">Kolom / Atribut</th>
                                                <th class="py-2 px-3">Data Induk (Asli)</th>
                                                <th class="py-2 px-3">Data Ganda</th>
                                                <th class="py-2 px-3 text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${diffRowsHtml}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        });

        listContainer.innerHTML = html;
        attachAsetResolveEvents();
    }

    function renderDuplicateOpds(opds) {
        const listContainer = document.getElementById('opd-dup-list');
        document.getElementById('opd-dup-count').textContent = opds.length;

        if (opds.length === 0) {
            listContainer.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center py-5 text-success">
                        <i class="bi bi-patch-check-fill fs-1 text-success d-block mb-2"></i>
                        <h6 class="fw-bold mb-1">Database Bersih!</h6>
                        <p class="mb-0 small text-secondary">Tidak ada nama OPD yang mirip di sistem.</p>
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        opds.forEach(item => {
            html += `
                <tr class="align-middle" id="opd-dup-row-${item.opd_b_id}">
                    <td class="px-3 py-3">
                        <div class="fw-bold text-navy">${escapeHtml(item.opd_a_nama)}</div>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-0.5 mt-1 small" style="font-size: 0.7rem;">
                            Dipertahankan (${item.count_a} Aset)
                        </span>
                    </td>
                    <td class="px-3 py-3">
                        <div class="fw-bold text-danger">${escapeHtml(item.opd_b_nama)}</div>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-0.5 mt-1 small" style="font-size: 0.7rem;">
                            Akan Dihapus (${item.count_b} Aset)
                        </span>
                    </td>
                    <td class="px-3 py-3 text-secondary small">
                        ${escapeHtml(item.reason)}
                    </td>
                    <td class="px-3 py-3 text-center">
                        <button type="button" class="btn btn-primary btn-xs fw-semibold py-2 px-3 btn-resolve-opd-sipat" data-target-id="${item.opd_a_id}" data-source-id="${item.opd_b_id}">
                            <i class="bi bi-signpost-split me-1"></i> Gabungkan Instansi
                        </button>
                    </td>
                </tr>
            `;
        });

        listContainer.innerHTML = html;
        attachOpdResolveEvents();
    }

    function attachAsetResolveEvents() {
        document.querySelectorAll('.btn-resolve-aset').forEach(btn => {
            btn.addEventListener('click', function() {
                const action = this.getAttribute('data-action');
                const originalId = this.getAttribute('data-original-id');
                const duplicateId = this.getAttribute('data-duplicate-id');
                const btnElement = this;
                
                const executeResolve = (direction = 'keep_original') => {
                    const actionsCell = btnElement.closest('div');
                    const originalHtml = actionsCell.innerHTML;
                    actionsCell.innerHTML = '<span class="spinner-border spinner-border-sm text-primary" role="status"></span>';

                    fetch("{{ route('sipat.aset.resolve-duplicate-aset') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            original_id: originalId,
                            duplicate_id: duplicateId,
                            action: action,
                            direction: direction
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Sukses',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            const row = document.getElementById(`aset-dup-row-${duplicateId}`);
                            if (row) row.remove();
                            
                            const countEl = document.getElementById('aset-dup-count');
                            const currentCount = parseInt(countEl.textContent);
                            countEl.textContent = Math.max(0, currentCount - 1);
                            
                            if (currentCount - 1 <= 0) {
                                renderDuplicateAsets([]);
                            }
                        } else {
                            actionsCell.innerHTML = originalHtml;
                            attachAsetResolveEvents();
                            Swal.fire('Gagal', data.message || 'Terjadi kesalahan.', 'error');
                        }
                    })
                    .catch(error => {
                        actionsCell.innerHTML = originalHtml;
                        attachAsetResolveEvents();
                        Swal.fire('Error', 'Gagal terhubung ke server.', 'error');
                    });
                };

                if (action === 'merge') {
                    Swal.fire({
                        title: 'Pilih Data Utama',
                        text: 'Pilih data mana yang akan menjadi data utama (dipertahankan). Data lainnya akan dihapus setelah riwayat/dokumen dipindahkan.',
                        icon: 'question',
                        showDenyButton: true,
                        showCancelButton: true,
                        confirmButtonText: 'Pertahankan Data Induk',
                        denyButtonText: 'Pertahankan Data Ganda',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#1e40af',
                        denyButtonColor: '#0ea5e9'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            executeResolve('keep_original');
                        } else if (result.isDenied) {
                            executeResolve('keep_duplicate');
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Konfirmasi Penghapusan',
                        text: 'Apakah Anda yakin ingin menghapus data aset ganda ini dari database beserta riwayatnya?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            executeResolve('keep_original');
                        }
                    });
                }
            });
        });
    }

    function attachOpdResolveEvents() {
        document.querySelectorAll('.btn-resolve-opd-sipat').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target-id');
                const sourceId = this.getAttribute('data-source-id');
                const row = document.getElementById(`opd-dup-row-${sourceId}`);

                Swal.fire({
                    title: 'Gabungkan Instansi OPD SIPAT?',
                    text: 'Semua data aset tanah pada OPD duplikat akan otomatis dipindahkan ke OPD utama. Semua berkas eLABEL terkait juga akan disesuaikan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#1e40af',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Gabungkan OPD!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const actionCell = this.closest('td');
                        const originalHtml = actionCell.innerHTML;
                        actionCell.innerHTML = '<span class="spinner-border spinner-border-sm text-primary" role="status"></span>';

                        fetch("{{ route('sipat.aset.resolve-duplicate-opd') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                target_opd_id: targetId,
                                source_opd_id: sourceId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Sukses Konsolidasi',
                                    text: data.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                row.style.transition = 'all 0.5s ease';
                                row.style.opacity = '0';
                                setTimeout(() => {
                                    row.remove();
                                    const countEl = document.getElementById('opd-dup-count');
                                    const newCount = Math.max(0, parseInt(countEl.textContent) - 1);
                                    countEl.textContent = newCount;
                                    if (newCount === 0) {
                                        renderDuplicateOpds([]);
                                    }
                                }, 500);
                            } else {
                                Swal.fire('Gagal', data.message, 'error');
                                actionCell.innerHTML = originalHtml;
                            }
                        })
                        .catch(error => {
                            Swal.fire('Error', 'Gagal terhubung ke server.', 'error');
                            actionCell.innerHTML = originalHtml;
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

    function showDetail(id) {
        const modalEl = document.getElementById('modalDetailAset');
        const modalContent = document.getElementById('modalDetailContent');
        
        // Relocate modal to document.body root if it is inside any animated container
        if (modalEl && modalEl.parentNode !== document.body) {
            document.body.appendChild(modalEl);
        }

        modalContent.innerHTML = `
            <div class="p-5 text-center text-secondary">
                <div class="spinner-border text-primary me-2" role="status"></div> Memuat data detail aset...
            </div>
        `;
        
        if (!detailModalInstance) {
            detailModalInstance = new bootstrap.Modal(modalEl, {
                backdrop: true,
                keyboard: true
            });
        }
        
        detailModalInstance.show();

        fetch(`{{ url('sipat/aset') }}/${id}/modal`)
            .then(res => res.text())
            .then(html => {
                modalContent.innerHTML = html;
            })
            .catch(err => {
                modalContent.innerHTML = `
                    <div class="p-4 text-center text-danger">
                        <i class="bi bi-exclamation-triangle fs-2 d-block mb-2"></i>
                        Gagal memuat data detail aset. Silakan coba lagi.
                    </div>
                `;
            });
    }

    function handleBulkSubmit(form) {
        const checked = document.querySelectorAll('.aset-checkbox:checked');
        const container = document.getElementById('bulkSelectedInputsContainer');
        container.innerHTML = '';
        checked.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'aset_ids[]';
            input.value = cb.value;
            container.appendChild(input);
        });
        document.getElementById('btnSubmitBulk').innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...';
        document.getElementById('btnSubmitBulk').disabled = true;
    }
</script>
@endpush
@endsection
