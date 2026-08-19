@extends('layouts.app')

@section('content')
<style>
    .report-shell {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) minmax(340px, 0.8fr);
        gap: 1.5rem;
    }
    @media (max-width: 991.98px) { .report-shell { grid-template-columns: 1fr; } }

    .report-card {
        border: 1px solid var(--border-color, rgba(0, 0, 0, 0.08));
        border-radius: 1.25rem;
        background: var(--bs-card-bg, #ffffff);
        box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.06);
        overflow: hidden;
    }
    .report-card-header {
        background: var(--bs-tertiary-bg, rgba(59, 130, 246, 0.05));
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, 0.08));
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .header-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: #3b82f6;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);
    }
    .report-summary-box {
        padding: 1.25rem;
        border-radius: 1rem;
        border: 1px solid var(--border-color, rgba(0,0,0,0.08));
        background: var(--bs-tertiary-bg, #f8fafc);
        position: relative;
        overflow: hidden;
    }
    .summary-label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--bs-secondary-color, #64748b);
        margin-bottom: 0.25rem;
    }
    .summary-value {
        font-size: 1.65rem;
        font-weight: 800;
        line-height: 1.2;
    }
    .action-item-card {
        border: 1px solid var(--border-color, rgba(0, 0, 0, 0.08));
        border-radius: 1rem;
        padding: 1.25rem;
        background: var(--bs-card-bg, #ffffff);
        display: flex;
        align-items: center;
        gap: 1rem;
        text-decoration: none;
        transition: all 0.25s ease;
    }
    .action-item-card:hover {
        transform: translateY(-2px);
        border-color: #3b82f6;
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.15);
    }
    .action-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }
</style>

<div class="container-fluid px-0">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary fw-semibold px-2.5 py-1 rounded-pill" style="font-size: 0.75rem;">
                    <i class="bi bi-file-earmark-bar-graph me-1"></i> MODUL LAPORAN SIPAT
                </span>
                <span class="text-secondary small">&bull;</span>
                <span class="text-secondary small">Rekapitulasi KIB A Aset Tanah Pemda</span>
            </div>
            <h2 class="fw-bold mb-1">Pusat Laporan Aset Tanah</h2>
            <p class="text-secondary mb-0 small">Atur kriteria filter dan cetak laporan resmi ber-KOP Pemda Kabupaten Donggala dalam berbagai format</p>
        </div>
        <a href="{{ route('sipat.aset.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-1"></i> Data Aset Tanah
        </a>
    </div>

    <div class="report-shell">
        <!-- Kolom Kiri: Filter Laporan & Summary -->
        <div class="d-flex flex-column gap-4">
            <div class="card clean-card report-card">
                <div class="report-card-header">
                    <div class="header-icon">
                        <i class="bi bi-funnel"></i>
                    </div>
                    <h5 class="fw-bold mb-0 text-body">Filter Laporan Aset</h5>
                </div>
                <div class="card-body p-4">
                    <form method="GET" action="{{ route('sipat.laporan.index') }}" id="filterLaporanForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-building me-1"></i> OPD Pengelola</label>
                                <select name="opd" class="form-select">
                                    <option value="">-- Semua OPD --</option>
                                    <option value="KOSONG" {{ request('opd') === 'KOSONG' ? 'selected' : '' }}>[Tanpa OPD / Kosong]</option>
                                    @foreach($opdList as $opd)
                                        <option value="{{ $opd->nama }}" {{ request('opd') == $opd->nama ? 'selected' : '' }}>{{ $opd->nama }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-tag me-1"></i> Status Sertifikasi BPN</label>
                                <select name="status[]" class="form-select">
                                    <option value="">-- Semua Status --</option>
                                    @foreach($statusList as $st)
                                        <option value="{{ $st->id_status }}" {{ (is_array(request('status')) && in_array($st->id_status, request('status'))) || request('status') == $st->id_status ? 'selected' : '' }}>
                                            {{ $st->nama_status }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-calendar-event me-1"></i> Tanggal Perolehan</label>
                                <input type="date" name="tanggal_perolehan" class="form-control" value="{{ request('tanggal_perolehan') }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-search me-1"></i> Kata Kunci Pencarian</label>
                                <input type="text" name="q" class="form-control" placeholder="Kode Aset, Nama, Alamat..." value="{{ request('q') }}">
                            </div>

                            <div class="col-12 mt-3 pt-3 border-top">
                                <label class="form-label small fw-bold text-body mb-2"><i class="bi bi-type me-1"></i> Judul Laporan Cetak (KOP PDF)</label>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="title_mode" id="titleMaster" value="master" {{ request('title_mode', 'master') !== 'manual' ? 'checked' : '' }} onchange="toggleTitleMode()">
                                            <label class="form-check-label small fw-semibold text-body" for="titleMaster">Pilih dari Master Judul</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="title_mode" id="titleManual" value="manual" {{ request('title_mode') === 'manual' ? 'checked' : '' }} onchange="toggleTitleMode()">
                                            <label class="form-check-label small fw-semibold text-body" for="titleManual">Ketik Judul Kustom</label>
                                        </div>
                                    </div>
                                </div>

                                <div id="boxTitleMaster" class="mt-2">
                                    <select name="report_title_id" class="form-select">
                                        @foreach($reportTitles as $rt)
                                            <option value="{{ $rt->id }}" {{ request('report_title_id') == $rt->id ? 'selected' : '' }}>{{ $rt->judul }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div id="boxTitleManual" class="mt-2" style="display: none;">
                                    <input type="text" name="manual_title" class="form-control" placeholder="Contoh: LAPORAN REKAPITULASI ASET TANAH DINAS PENDIDIKAN" value="{{ request('manual_title') }}">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4 pt-2">
                            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                                <i class="bi bi-funnel-fill me-1"></i> Terapkan Filter
                            </button>
                            <a href="{{ route('sipat.laporan.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Ringkasan Hasil Filter -->
            <div class="card clean-card report-card p-4">
                <h6 class="fw-bold text-body mb-3">Ringkasan Hasil Filter Data</h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="report-summary-box">
                            <div class="summary-label">Total Bidang Tanah</div>
                            <div class="summary-value text-body font-monospace">{{ number_format($summary['total_data']) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="report-summary-box" style="border-left: 4px solid #10b981;">
                            <div class="summary-label text-success">Total Nilai Perolehan</div>
                            <div class="summary-value text-success font-monospace fs-5">{{ $summary['total_nilai'] }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="report-summary-box" style="border-left: 4px solid #3b82f6;">
                            <div class="summary-label text-primary">Sudah Berstatus BPN</div>
                            <div class="summary-value text-primary font-monospace">{{ number_format($summary['total_berstatus']) }}</div>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-body rounded-3 border">
                    <small class="fw-bold text-secondary text-uppercase d-block mb-1" style="font-size: 0.72rem;">Filter Aktif Saat Ini:</small>
                    <div class="d-flex flex-wrap gap-1.5">
                        @forelse($summary['activeFilters'] as $f)
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1">
                                {{ $f['label'] }}: {{ $f['value'] }}
                            </span>
                        @empty
                            <span class="badge bg-secondary-subtle text-body-secondary px-2.5 py-1">
                                Menampilkan Seluruh Aset Tanah KIB A
                            </span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Aksi Download & Print -->
        <div class="d-flex flex-column gap-3">
            <div class="card clean-card report-card p-4">
                <div class="d-flex align-items-center gap-2 mb-3 border-bottom pb-2">
                    <i class="bi bi-cloud-download text-primary fs-4"></i>
                    <h5 class="fw-bold text-body mb-0">Aksi Cetak & Unduh</h5>
                </div>

                <div class="d-flex flex-column gap-3">
                    <!-- 1. Pratinjau Cetak / PDF -->
                    <a href="{{ route('sipat.laporan.previewPdf') }}{{ $exportQueryString }}" target="_blank" class="action-item-card">
                        <div class="action-icon bg-primary-subtle text-primary">
                            <i class="bi bi-printer"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-body">Pratinjau Cetak / PDF</div>
                            <small class="text-secondary">Pratinjau dokumen cetak ber-KOP resmi di tab baru browser</small>
                        </div>
                    </a>

                    <!-- 2. Download Dokumen PDF -->
                    <a href="{{ route('sipat.laporan.downloadPdf') }}{{ $exportQueryString }}" class="action-item-card">
                        <div class="action-icon bg-danger-subtle text-danger">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-body">Unduh Laporan PDF</div>
                            <small class="text-secondary">Unduh file PDF resmi lengkap dengan KOP & lembar pengesahan TTD</small>
                        </div>
                    </a>

                    <!-- 3. Export CSV -->
                    <a href="{{ route('sipat.laporan.exportCsv') }}{{ $exportQueryString }}" class="action-item-card">
                        <div class="action-icon bg-success-subtle text-success">
                            <i class="bi bi-file-earmark-excel"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-body">Unduh Data CSV / Excel</div>
                            <small class="text-secondary">Unduh format file CSV rapi untuk pengolahan spreadsheet data</small>
                        </div>
                    </a>
                </div>

                <div class="alert alert-info border-0 rounded-4 mt-4 mb-0 d-flex align-items-start gap-2.5 small">
                    <i class="bi bi-info-circle-fill fs-5 text-info flex-shrink-0"></i>
                    <span>Hasil unduhan file akan <strong>otomatis menyesuaikan</strong> dengan kriteria filter yang aktif di panel sebelah kiri.</span>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleTitleMode() {
        const isManual = document.getElementById('titleManual').checked;
        document.getElementById('boxTitleMaster').style.display = isManual ? 'none' : 'block';
        document.getElementById('boxTitleManual').style.display = isManual ? 'block' : 'none';
    }
    document.addEventListener('DOMContentLoaded', toggleTitleMode);
</script>
@endpush
@endsection
