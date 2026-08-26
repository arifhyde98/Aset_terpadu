@extends('layouts.app')

@section('content')
<style>
    .target-card {
        border: 1px solid var(--border-color, rgba(0, 0, 0, 0.08));
        border-radius: 1.25rem;
        background: var(--bs-card-bg, #ffffff);
        box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }
    .target-card-header {
        background: var(--bs-tertiary-bg, rgba(59, 130, 246, 0.04));
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, 0.08));
    }
    .metric-box {
        padding: 1.25rem;
        border-radius: 1rem;
        border: 1px solid var(--border-color, rgba(0, 0, 0, 0.08));
        background: var(--bs-tertiary-bg, #f8fafc);
        position: relative;
        transition: transform 0.2s ease;
    }
    .metric-box:hover {
        transform: translateY(-2px);
    }
    .candidate-list-container {
        max-height: 350px;
        overflow-y: auto;
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 0.75rem;
    }
</style>

<div class="container-fluid px-0">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary fw-semibold px-2.5 py-1 rounded-pill" style="font-size: 0.75rem;">
                    <i class="bi bi-crosshair me-1"></i> MODUL SIPAT
                </span>
                <span class="text-secondary small">&bull;</span>
                <span class="text-secondary small">Target Pensertifikatan Tanah KIB A</span>
            </div>
            <h2 class="fw-bold mb-1">Target Pensertifikatan Tanah Tahunan</h2>
            <p class="text-secondary mb-0 small">Penetapan target KPI tahunan dan pemantauan realisasi penerbitan sertifikat BPN per OPD</p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button" class="btn btn-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalSetTarget">
                <i class="bi bi-plus-lg me-1"></i> Penetapan Target Baru
            </button>

            <a href="{{ route('sipat.target-pensertifikatan.export-excel', ['tahun' => $tahun, 'opd_id' => $opdId]) }}" class="btn btn-outline-success rounded-pill px-3">
                <i class="bi bi-file-earmark-excel me-1"></i> Excel (.xlsx)
            </a>

            <a href="{{ route('sipat.target-pensertifikatan.export-pdf', ['tahun' => $tahun, 'opd_id' => $opdId]) }}" target="_blank" class="btn btn-outline-danger rounded-pill px-3">
                <i class="bi bi-file-earmark-pdf me-1"></i> Cetak PDF
            </a>
        </div>
    </div>

    <!-- Filter Header -->
    <div class="card target-card mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('sipat.target-pensertifikatan.index') }}" class="row g-2 align-items-center">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small fw-bold text-secondary mb-1"><i class="bi bi-calendar-event me-1"></i> Tahun Anggaran</label>
                    <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 col-sm-6">
                    <label class="form-label small fw-bold text-secondary mb-1"><i class="bi bi-building me-1"></i> Filter OPD Pengelola</label>
                    <select name="opd_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Semua OPD --</option>
                        @foreach($opdList as $opd)
                            <option value="{{ $opd->id }}" {{ (string)$opdId === (string)$opd->id ? 'selected' : '' }}>{{ $opd->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 col-sm-6 align-self-end">
                    <button type="submit" class="btn btn-sm btn-secondary rounded-pill px-3 me-1">
                        <i class="bi bi-funnel me-1"></i> Terapkan
                    </button>
                    @if($opdId)
                        <a href="{{ route('sipat.target-pensertifikatan.index', ['tahun' => $tahun]) }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                            Reset OPD
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards & Progress Bar -->
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-sm-6">
            <div class="metric-box">
                <div class="text-secondary small fw-bold text-uppercase">Total Target Bidang</div>
                <div class="fs-2 fw-extrabold text-dark font-monospace mt-1">{{ number_format($totalTarget) }}</div>
                <div class="text-secondary small">Tahun Anggaran {{ $tahun }}</div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <div class="metric-box" style="border-left: 4px solid #10b981;">
                <div class="text-success small fw-bold text-uppercase">Realisasi (Tercapai)</div>
                <div class="fs-2 fw-extrabold text-success font-monospace mt-1">{{ number_format($totalRealisasi) }}</div>
                <div class="text-secondary small">Sertifikat Terbit BPN</div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <div class="metric-box" style="border-left: 4px solid #f59e0b;">
                <div class="text-warning small fw-bold text-uppercase">Dalam Proses</div>
                <div class="fs-2 fw-extrabold text-warning font-monospace mt-1">{{ number_format($totalProses) }}</div>
                <div class="text-secondary small">Pengurusan / Belum Selesai</div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <div class="metric-box" style="border-left: 4px solid var(--bs-{{ $progressColor }});">
                <div class="text-{{ $progressColor }} small fw-bold text-uppercase">Capaian Target Pemda</div>
                <div class="fs-2 fw-extrabold text-{{ $progressColor }} font-monospace mt-1">{{ $persentaseCapaian }}%</div>
                <span class="badge bg-{{ $progressColor }}-subtle text-{{ $progressColor }} fw-bold px-2 py-0.5 rounded-pill" style="font-size: 0.7rem;">
                    Status: {{ $progressBadge }}
                </span>
            </div>
        </div>
    </div>

    <!-- Visual Progress Bar Card -->
    <div class="card target-card mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-bold text-body">
                    <i class="bi bi-speedometer2 text-primary me-1"></i> Progres Capaian Target Pensertifikatan Tahun {{ $tahun }}
                </div>
                <div class="fw-bold font-monospace text-{{ $progressColor }} fs-5">
                    {{ $totalRealisasi }} / {{ $totalTarget }} Bidang ({{ $persentaseCapaian }}%)
                </div>
            </div>
            <div class="progress" style="height: 18px; border-radius: 10px; background-color: #e2e8f0;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-{{ $progressColor }}" 
                     role="progressbar" 
                     style="width: {{ min($persentaseCapaian, 100) }}%;" 
                     aria-valuenow="{{ $persentaseCapaian }}" 
                     aria-valuemin="0" 
                     aria-valuemax="100">
                     {{ $persentaseCapaian }}%
                </div>
            </div>
        </div>
    </div>

    <!-- Layout Grid: Tab / Tables -->
    <ul class="nav nav-pills mb-3 gap-2" id="targetTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill px-4" id="daftar-tab" data-bs-toggle="tab" data-bs-target="#daftarAsetPane" type="button" role="tab">
                <i class="bi bi-list-check me-1"></i> Daftar Bidang Tanah Target ({{ count($targetItems) }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4" id="rekap-tab" data-bs-toggle="tab" data-bs-target="#rekapOpdPane" type="button" role="tab">
                <i class="bi bi-building-check me-1"></i> Rekapitulasi Kinerja per OPD ({{ count($opdSummaries) }})
            </button>
        </li>
    </ul>

    <div class="tab-content" id="targetTabContent">
        <!-- Tab 1: Daftar Bidang Tanah Target -->
        <div class="tab-pane fade show active" id="daftarAsetPane" role="tabpanel">
            <div class="card target-card">
                <div class="target-card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-body">Daftar Bidang Tanah Target Pensertifikatan Tahun {{ $tahun }}</h5>
                    <input type="text" id="searchTargetTable" class="form-control form-control-sm w-auto" placeholder="Cari NIBAR / Nama Aset...">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="targetTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" style="width: 50px;">No</th>
                                    <th>Kode Aset (NIBAR)</th>
                                    <th>Nama Aset Tanah / Peruntukan</th>
                                    <th>OPD Pengelola</th>
                                    <th>Status BPN Terakhir</th>
                                    <th>Indikator Capaian</th>
                                    <th>Catatan / Ket. Target</th>
                                    <th class="text-end pe-4" style="width: 100px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($targetItems as $index => $item)
                                    <tr data-search="{{ strtolower(($item->asetTanah->kode_aset ?? '') . ' ' . ($item->asetTanah->nama_aset ?? '') . ' ' . ($item->asetTanah->peruntukan ?? '') . ' ' . ($item->opdSipat->nama ?? $item->asetTanah->opdSipat->nama ?? $item->asetTanah->opd ?? '')) }}">
                                        <td class="ps-4 fw-semibold text-secondary">{{ $index + 1 }}</td>
                                        <td>
                                            <span class="font-monospace fw-bold text-primary">{{ $item->asetTanah->kode_aset ?? '-' }}</span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-body">{{ $item->asetTanah->nama_aset ?? 'Tanpa Nama' }}</div>
                                            <small class="text-secondary">{{ $item->asetTanah->peruntukan ?? '-' }} &bull; Luas: {{ number_format($item->asetTanah->luas ?? 0, 0, ',', '.') }} m²</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                {{ $item->opdSipat->nama ?? $item->asetTanah->opdSipat->nama ?? $item->asetTanah->opd ?? '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($item->computed_status_name === 'Belum Diurus')
                                                <span class="badge bg-secondary-subtle text-secondary px-2.5 py-1">Belum Diurus</span>
                                            @elseif($item->is_achieved)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1">
                                                    <i class="bi bi-check-circle-fill me-1"></i> {{ $item->computed_status_name }}
                                                </span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1">
                                                    <i class="bi bi-hourglass-split me-1"></i> {{ $item->computed_status_name }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->is_achieved)
                                                <span class="badge bg-success px-2.5 py-1 rounded-pill">
                                                    <i class="bi bi-check-lg me-1"></i> TERCAPAI
                                                </span>
                                            @else
                                                <span class="badge bg-warning text-dark px-2.5 py-1 rounded-pill">
                                                    <i class="bi bi-clock me-1"></i> Dalam Proses
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-secondary">{{ $item->keterangan ?? '-' }}</small>
                                        </td>
                                        <td class="text-end pe-4">
                                            <form action="{{ route('sipat.target-pensertifikatan.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus bidang tanah ini dari target tahun {{ $tahun }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0 rounded-circle" data-bs-toggle="tooltip" title="Hapus dari Target">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-secondary">
                                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary-subtle"></i>
                                            Belum ada bidang tanah yang ditetapkan sebagai target pensertifikatan untuk tahun {{ $tahun }}.
                                            <div class="mt-2">
                                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalSetTarget">
                                                    <i class="bi bi-plus-lg me-1"></i> Penetapan Target Baru
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Rekapitulasi per OPD -->
        <div class="tab-pane fade" id="rekapOpdPane" role="tabpanel">
            <div class="card target-card">
                <div class="target-card-header">
                    <h5 class="fw-bold mb-0 text-body">Ringkasan Kinerja Pensertifikatan per OPD (Tahun {{ $tahun }})</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" style="width: 50px;">No</th>
                                    <th>Nama OPD / Pengelola Aset</th>
                                    <th class="text-center">Total Target Bidang</th>
                                    <th class="text-center">Realisasi (Terbit Sertifikat)</th>
                                    <th class="text-center">Dalam Pengurusan</th>
                                    <th class="text-center">Persentase Capaian</th>
                                    <th class="text-center pe-4">Status Kinerja</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($opdSummaries as $idx => $opdSum)
                                    <tr>
                                        <td class="ps-4 fw-semibold text-secondary">{{ $idx + 1 }}</td>
                                        <td class="fw-bold text-body">{{ $opdSum['nama'] }}</td>
                                        <td class="text-center font-monospace fw-bold">{{ $opdSum['total'] }}</td>
                                        <td class="text-center font-monospace text-success fw-bold">{{ $opdSum['realisasi'] }}</td>
                                        <td class="text-center font-monospace text-warning fw-bold">{{ $opdSum['proses'] }}</td>
                                        <td class="text-center">
                                            <div class="d-flex align-items-center justify-content-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 8px; max-width: 100px;">
                                                    <div class="progress-bar {{ $opdSum['badge_class'] }}" style="width: {{ min($opdSum['persentase'], 100) }}%;"></div>
                                                </div>
                                                <span class="font-monospace fw-bold small">{{ $opdSum['persentase'] }}%</span>
                                            </div>
                                        </td>
                                        <td class="text-center pe-4">
                                            <span class="badge {{ $opdSum['badge_class'] }} px-2.5 py-1 rounded-pill">
                                                {{ $opdSum['status_label'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-secondary">
                                            Belum ada data rekapitulasi target per OPD.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Penetapan Target Tahunan -->
<div class="modal fade" id="modalSetTarget" tabindex="-1" aria-labelledby="modalSetTargetLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-primary text-white p-3">
                <h5 class="modal-title fw-bold" id="modalSetTargetLabel">
                    <i class="bi bi-plus-circle me-1"></i> Penetapan Target Pensertifikatan Tanah
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('sipat.target-pensertifikatan.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-body">Tahun Anggaran Target <span class="text-danger">*</span></label>
                            <select name="tahun" class="form-select" required>
                                @foreach($availableYears as $y)
                                    <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-body">Catatan / Keterangan Penetapan</label>
                            <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Target Prioritas Pemda / Aksi KPK 2026">
                        </div>
                    </div>

                    <div class="mb-2 d-flex justify-content-between align-items-center">
                        <label class="form-label small fw-bold text-body mb-0">
                            Pilih Bidang Tanah KIB A (Master Aset) <span class="text-danger">*</span>
                        </label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" id="filterCandidateInput" class="form-control form-control-sm" placeholder="Cari NIBAR / nama aset..." style="width: 240px;">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectAllCandidate">Pilih Semua</button>
                        </div>
                    </div>

                    <div class="candidate-list-container p-2">
                        <table class="table table-sm table-hover align-middle mb-0" id="candidateTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;" class="text-center">#</th>
                                    <th>Kode Aset / NIBAR</th>
                                    <th>Nama Aset & Luas</th>
                                    <th>OPD Pengelola</th>
                                    <th>Status BPN Saat Ini</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($candidateAsets as $cand)
                                    <tr class="candidate-row" data-search="{{ strtolower(($cand->kode_aset ?? '') . ' ' . $cand->nama_aset . ' ' . ($cand->peruntukan ?? '') . ' ' . ($cand->opdSipat->nama ?? $cand->opd ?? '')) }}">
                                        <td class="text-center">
                                            <input class="form-check-input candidate-checkbox" type="checkbox" name="aset_ids[]" value="{{ $cand->id_aset }}" id="cand_{{ $cand->id_aset }}">
                                        </td>
                                        <td>
                                            <label for="cand_{{ $cand->id_aset }}" class="form-check-label font-monospace fw-bold text-primary mb-0" style="cursor: pointer;">
                                                {{ $cand->kode_aset ?? '-' }}
                                            </label>
                                        </td>
                                        <td>
                                            <label for="cand_{{ $cand->id_aset }}" class="form-check-label mb-0" style="cursor: pointer;">
                                                <div class="fw-semibold text-body">{{ $cand->nama_aset }}</div>
                                                <small class="text-secondary">{{ $cand->peruntukan ?? '-' }} &bull; {{ number_format($cand->luas ?? 0, 0, ',', '.') }} m²</small>
                                            </label>
                                        </td>
                                        <td>
                                            <small class="text-body">{{ $cand->opdSipat->nama ?? $cand->opd ?? '-' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                {{ $cand->latestProses->statusProses->nama_status ?? 'Belum Diurus' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-secondary">
                                            Seluruh bidang tanah KIB A sudah terdaftar di target tahun ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="bi bi-save me-1"></i> Simpan Target Tahunan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // High performance search filter for main target table
        const searchInput = document.getElementById('searchTargetTable');
        if (searchInput) {
            let debounceTimer;
            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                const filter = this.value.toLowerCase().trim();
                debounceTimer = setTimeout(() => {
                    const rows = document.querySelectorAll('#targetTable tbody tr');
                    rows.forEach(row => {
                        if (row.children.length === 1) return;
                        const searchData = row.getAttribute('data-search') || row.innerText.toLowerCase();
                        row.style.display = (filter === '' || searchData.includes(filter)) ? '' : 'none';
                    });
                }, 100);
            });
        }

        // Ultra-fast search filter for candidate modal (No Layout Thrashing!)
        const filterCandInput = document.getElementById('filterCandidateInput');
        if (filterCandInput) {
            let debounceTimer;
            filterCandInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                const filter = this.value.toLowerCase().trim();
                debounceTimer = setTimeout(() => {
                    const rows = document.querySelectorAll('#candidateTable tbody tr.candidate-row');
                    rows.forEach(row => {
                        const searchData = row.getAttribute('data-search') || '';
                        row.style.display = (filter === '' || searchData.includes(filter)) ? '' : 'none';
                    });
                }, 100);
            });
        }

        // Select All button in candidate modal
        const btnSelectAll = document.getElementById('btnSelectAllCandidate');
        if (btnSelectAll) {
            let isAllSelected = false;
            btnSelectAll.addEventListener('click', function () {
                const checkboxes = document.querySelectorAll('.candidate-checkbox');
                isAllSelected = !isAllSelected;
                checkboxes.forEach(cb => {
                    const row = cb.closest('tr');
                    if (row && row.style.display !== 'none') {
                        cb.checked = isAllSelected;
                    }
                });
                btnSelectAll.textContent = isAllSelected ? 'Batal Pilih' : 'Pilih Semua';
            });
        }
    });
</script>
@endpush
@endsection
