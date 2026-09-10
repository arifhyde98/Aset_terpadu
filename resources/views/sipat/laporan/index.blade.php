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
    .report-tabs .nav-link {
        font-size: 0.92rem;
        font-weight: 600;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 0.75rem 1.25rem;
        color: var(--bs-secondary-color, #64748b);
        background: transparent;
        transition: all 0.2s ease;
    }
    .report-tabs .nav-link:hover {
        color: #1e40af;
    }
    .report-tabs .nav-link.active {
        color: #1e40af;
        border-bottom-color: #1e40af;
        background: transparent;
    }
    .table-report-preview thead th {
        background-color: var(--bs-tertiary-bg, #f1f5f9) !important;
        position: sticky;
        top: 0;
        z-index: 2;
    }
</style>

<div class="container-fluid px-0">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-3">
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
        <div class="d-flex align-items-center gap-2">
            @if(auth()->user()?->role !== \App\Enums\UserRole::OPD)
                <a href="{{ route('master.kop-settings.index') }}" class="btn btn-outline-primary rounded-pill px-3.5" title="Atur KOP Surat & Data Pejabat Penandatangan">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Atur KOP & TTD
                </a>
            @endif
            <a href="{{ route('sipat.aset.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="bi bi-arrow-left me-1"></i> Data Aset Tanah
            </a>
        </div>
    </div>

    <!-- Navigasi Tab Laporan -->
    <div class="border-bottom mb-4">
        <ul class="nav nav-tabs report-tabs border-bottom-0">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('sipat.laporan.rekapOpd') }}">
                    <i class="bi bi-building me-1.5"></i>Rekapitulasi per OPD
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('sipat.laporan.index') }}">
                    <i class="bi bi-list-columns-reverse me-1.5"></i>Rincian Daftar Aset KIB A
                </a>
            </li>
        </ul>
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
                                <select name="opd_id" class="form-select">
                                    <option value="">-- Semua OPD --</option>
                                    <option value="KOSONG" {{ request('opd_id', request('opd')) === 'KOSONG' ? 'selected' : '' }}>[Tanpa OPD / Kosong]</option>
                                    @foreach($opdList as $opd)
                                        <option value="{{ $opd->id }}" {{ (string) request('opd_id', request('opd')) === (string) $opd->id ? 'selected' : '' }}>{{ $opd->nama }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-tag me-1"></i> Kategori Status Sertifikasi</label>
                                @php
                                    $currKat = request('kategori_status', (is_array(request('status')) ? reset(request('status')) : request('status')));
                                @endphp
                                <select name="kategori_status" class="form-select">
                                    <option value="">-- Semua Status --</option>
                                    <option value="belum_diproses" {{ $currKat === 'belum_diproses' ? 'selected' : '' }}>1. Belum Diproses</option>
                                    <option value="dalam_proses" {{ $currKat === 'dalam_proses' ? 'selected' : '' }}>2. Dalam Proses (Pengukuran / PERTEK / PKKPR)</option>
                                    <option value="sudah_bersertifikat" {{ $currKat === 'sudah_bersertifikat' ? 'selected' : '' }}>3. Sudah Bersertifikat</option>
                                    <option value="bermasalah" {{ $currKat === 'bermasalah' ? 'selected' : '' }}>4. Bermasalah / Sengketa</option>
                                    <option value="belum_bersertifikat" {{ $currKat === 'belum_bersertifikat' ? 'selected' : '' }}>5. Belum Bersertifikat (Gabungan Belum Diproses, Dalam Proses & Bermasalah)</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-geo-alt me-1"></i> Wilayah Kecamatan</label>
                                <select name="kecamatan_id" class="form-select">
                                    <option value="">-- Semua Kecamatan --</option>
                                    <option value="KOSONG" {{ request('kecamatan_id') === 'KOSONG' ? 'selected' : '' }}>[Luar Wilayah / Lainnya]</option>
                                    @if(isset($kecamatanList))
                                        @foreach($kecamatanList as $kec)
                                            <option value="{{ $kec->id }}" {{ (string) request('kecamatan_id') === (string) $kec->id ? 'selected' : '' }}>{{ $kec->nama }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-calendar-event me-1"></i> Tanggal Perolehan</label>
                                <input type="date" name="tanggal_perolehan" class="form-control" value="{{ request('tanggal_perolehan') }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-search me-1"></i> Kata Kunci Pencarian</label>
                                <input type="text" name="q" class="form-control" placeholder="Kode Aset, Nama, Alamat..." value="{{ request('q') }}">
                            </div>

                            <div class="col-12 mt-3 pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label small fw-bold text-body mb-0"><i class="bi bi-type me-1"></i> Judul Laporan Cetak (KOP PDF & Excel)</label>
                                    <span class="badge bg-light text-primary border border-primary-subtle fw-normal">Dinamis & Fleksibel</span>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-12 col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="title_mode" id="titleAuto" value="auto" {{ request('title_mode', 'auto') === 'auto' ? 'checked' : '' }} onchange="toggleTitleMode(true)">
                                            <label class="form-check-label small fw-semibold text-body" for="titleAuto">
                                                <i class="bi bi-magic me-1 text-primary"></i> Otomatis (Filter)
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="title_mode" id="titleMaster" value="master" {{ request('title_mode') === 'master' ? 'checked' : '' }} onchange="toggleTitleMode(true)">
                                            <label class="form-check-label small fw-semibold text-body" for="titleMaster">
                                                <i class="bi bi-list-check me-1 text-secondary"></i> Master Judul
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="title_mode" id="titleManual" value="manual" {{ request('title_mode') === 'manual' ? 'checked' : '' }} onchange="toggleTitleMode(false)">
                                            <label class="form-check-label small fw-semibold text-body" for="titleManual">
                                                <i class="bi bi-pencil-square me-1 text-secondary"></i> Judul Kustom
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div id="boxTitleAuto" class="mt-2 p-2.5 px-3 rounded-3 bg-light border border-primary-subtle d-flex align-items-center gap-2">
                                    <i class="bi bi-info-circle-fill text-primary flex-shrink-0 fs-5"></i>
                                    <div class="overflow-hidden">
                                        <div class="text-muted" style="font-size: 0.72rem; font-weight: 600; text-transform: uppercase;">Judul Otomatis Aktif:</div>
                                        <div class="small fw-bold text-dark text-truncate" title="{{ $selectedTitle }}">{{ $selectedTitle }}</div>
                                    </div>
                                </div>

                                <div id="boxTitleMaster" class="mt-2" style="display: none;">
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

                    <!-- 3. Export Excel (.xlsx) -->
                    <a href="{{ route('sipat.laporan.exportXlsx') }}{{ $exportQueryString }}" class="action-item-card">
                        <div class="action-icon bg-success-subtle text-success">
                            <i class="bi bi-file-earmark-excel"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-body">Unduh Laporan Excel (.xlsx)</div>
                            <small class="text-secondary">Unduh file Excel resmi ber-KOP & TTD persis sesuai tampilan PDF</small>
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

    @php
        $kat = $filters['kategori_status'] ?? '';
        $isBersertifikat = ($kat === 'sudah_bersertifikat');
        $groupHeader = 'ASET TANAH';
        if ($isBersertifikat) {
            $groupHeader = 'ASET SUDAH BERSERTIFIKAT';
        } elseif ($kat === 'belum_diproses') {
            $groupHeader = 'ASET BELUM DIPROSES';
        } elseif ($kat === 'dalam_proses') {
            $groupHeader = 'ASET DALAM PROSES';
        } elseif ($kat === 'bermasalah') {
            $groupHeader = 'ASET BERMASALAH / SENGKETA';
        } elseif ($kat === 'belum_bersertifikat') {
            $groupHeader = 'ASET BELUM BERSERTIFIKAT';
        }

        // Optimasi Performa DOM: Batasi 100 baris pertama untuk pratinjau instan di browser
        $totalDataCount = count($rows);
        $showAll = (request('show_all') === '1');
        $previewRows = ($showAll || $totalDataCount <= 100) ? $rows : $rows->take(100);
        $allLuas = $rows->sum('luas');
        $allNilai = $rows->sum('harga_perolehan');
    @endphp

    <!-- Kartu Pratinjau Tabel Laporan Aset Tanah (11 / 12 Kolom Resmi) -->
    <div class="card clean-card report-card mt-4 mb-4">
        <div class="report-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <div class="header-icon bg-success">
                    <i class="bi bi-table"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-body">Pratinjau Tabel Laporan Rekapitulasi Aset Tanah</h5>
                    <small class="text-secondary">Struktur {{ $isBersertifikat ? '12 Kolom Khusus Aset Bersertifikat' : '11 Kolom Standar' }} Format Laporan & Ekspor Pemda Kabupaten Donggala</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                @if($isBersertifikat)
                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold px-2.5 py-1 rounded-pill">
                        <i class="bi bi-patch-check-fill me-1"></i> Mode Aset Bersertifikat (12 Kolom)
                    </span>
                @endif
                <span class="badge bg-primary-subtle text-primary fw-semibold px-2.5 py-1 rounded-pill">
                    {{ number_format($totalDataCount) }} Bidang Tanah Tersaring
                </span>
            </div>
        </div>

        @if($totalDataCount > 100 && !$showAll)
            <div class="px-4 py-2.5 bg-light border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="small text-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-speedometer2 text-primary fs-6"></i>
                    <span>Menampilkan <strong>100</strong> data pratinjau pertama untuk performa browsing cepat. Seluruh <strong>{{ number_format($totalDataCount) }}</strong> bidang tanah akan diekspor lengkap dan utuh pada file PDF & Excel.</span>
                </div>
                <a href="{{ request()->fullUrlWithQuery(['show_all' => 1]) }}" class="btn btn-xs btn-outline-primary rounded-pill px-3 py-1 fw-semibold small" style="font-size: 0.78rem;">
                    <i class="bi bi-arrows-expand me-1"></i> Tampilkan Semua ({{ number_format($totalDataCount) }})
                </a>
            </div>
        @elseif($showAll && $totalDataCount > 100)
            <div class="px-4 py-2.5 bg-primary-subtle text-primary border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="small fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill text-primary"></i>
                    <span>Menampilkan seluruh <strong>{{ number_format($totalDataCount) }}</strong> baris data di browser.</span>
                </div>
                <a href="{{ request()->fullUrlWithQuery(['show_all' => null]) }}" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1 small" style="font-size: 0.78rem;">
                    <i class="bi bi-arrows-collapse me-1"></i> Batasi Pratinjau (100 Data)
                </a>
            </div>
        @endif

        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                <table class="table table-hover table-bordered align-middle mb-0 table-report-preview" style="font-size: 0.82rem;">
                    <thead class="bg-body-tertiary text-secondary sticky-top border-bottom text-center align-middle" style="z-index: 10;">
                        <tr>
                            <th rowspan="2" style="width: 45px;">NO.</th>
                            <th rowspan="2" style="min-width: 180px;">KODE ASET / NIBAR</th>
                            <th rowspan="2" style="min-width: 180px;">NAMA BARANG</th>
                            <th rowspan="2" style="min-width: 200px;">LOKASI</th>
                            <th colspan="{{ $isBersertifikat ? '4' : '3' }}" class="bg-primary-subtle text-primary">{{ $groupHeader }}</th>
                            <th rowspan="2" style="min-width: 130px;">TANGGAL PEROLEHAN</th>
                            <th rowspan="2" style="min-width: 130px;">CARA PEROLEHAN</th>
                            <th rowspan="2" style="min-width: 140px;">STATUS</th>
                            <th rowspan="2" style="min-width: 180px;">KETERANGAN</th>
                        </tr>
                        <tr>
                            <th class="bg-primary-subtle text-primary" style="min-width: 160px;">BIDANG</th>
                            @if($isBersertifikat)
                                <th class="bg-primary-subtle text-primary" style="min-width: 160px;">NO. SERTIFIKAT</th>
                            @endif
                            <th class="bg-primary-subtle text-primary" style="min-width: 100px;">LUAS (M²)</th>
                            <th class="bg-primary-subtle text-primary" style="min-width: 130px;">NILAI (RP)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($previewRows as $idx => $r)
                            @php
                                $rLuas = (float) ($r->luas ?? 0);
                                $rNilai = (float) ($r->harga_perolehan ?? 0);

                                $rKode = $r->kode_aset ?? '-';
                                $rNama = $r->nama_aset ?? '-';
                                $rLokasi = $r->alamat ?? '-';
                                $rBidang = $r->peruntukan ?? $r->nama_aset ?? '-';
                                $rNoSertifikat = !empty($r->sertifikatElabel?->no_sertipikat) ? trim($r->sertifikatElabel->no_sertipikat) : '-';
                                $rTgl = !empty($r->tanggal_perolehan) ? \Carbon\Carbon::parse($r->tanggal_perolehan)->format('d/m/Y') : '-';
                                $rCara = $r->dasar_perolehan ?? '-';
                                // Status proses pensertifikatan tanah
                                $rStatus = $r->latestProses?->statusProses?->nama_status ?? 'Belum Diproses';
                                // Kolom keterangan harus berisi keterangan riil, bukan nama barang
                                $rKet = !empty(trim((string) ($r->keterangan ?? ''))) ? trim((string) $r->keterangan) : '-';
                            @endphp
                            <tr>
                                <td class="text-center fw-medium text-secondary">{{ $idx + 1 }}</td>
                                <td class="font-monospace text-body-secondary" style="font-size: 0.76rem;">{{ $rKode }}</td>
                                <td class="fw-semibold text-body">{{ $rNama }}</td>
                                <td class="text-secondary small">{{ $rLokasi }}</td>
                                <td class="fw-medium text-body">{{ $rBidang }}</td>
                                @if($isBersertifikat)
                                    <td class="text-center">
                                        @if($rNoSertifikat !== '-')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace px-2 py-1" style="font-size: 0.75rem;">
                                                {{ $rNoSertifikat }}
                                            </span>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                @endif
                                <td class="text-end font-monospace text-body">{{ number_format($rLuas, 2, ',', '.') }}</td>
                                <td class="text-end font-monospace text-body">{{ $rNilai > 0 ? number_format($rNilai, 2, ',', '.') : '0,00' }}</td>
                                <td class="text-center text-secondary small">{{ $rTgl }}</td>
                                <td class="text-center text-secondary small">{{ $rCara }}</td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-body border px-2 py-1" style="font-size: 0.74rem;">
                                        {{ $rStatus }}
                                    </span>
                                </td>
                                <td class="text-body-secondary small">{{ $rKet }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isBersertifikat ? '12' : '11' }}" class="text-center py-5 text-secondary">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                                    Tidak ada data aset tanah yang sesuai dengan kriteria filter saat ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($totalDataCount > 0)
                        <tfoot class="bg-body-secondary fw-bold" style="border-top: 2px solid var(--border-color, #cbd5e1);">
                            <tr>
                                <td colspan="{{ $isBersertifikat ? '6' : '5' }}" class="text-center py-2.5">
                                    <span>JUMLAH / TOTAL KESELURUHAN ({{ number_format($totalDataCount) }} BIDANG)</span>
                                    @if($totalDataCount > 100 && !$showAll)
                                        <div class="small fw-normal text-muted" style="font-size: 0.72rem;">* Akumulasi total dari seluruh {{ number_format($totalDataCount) }} data hasil filter</div>
                                    @endif
                                </td>
                                <td class="text-end font-monospace py-2.5">{{ number_format($allLuas, 2, ',', '.') }}</td>
                                <td class="text-end font-monospace py-2.5">{{ number_format($allNilai, 2, ',', '.') }}</td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleTitleMode(autoSubmit = false) {
        const selectedRadio = document.querySelector('input[name="title_mode"]:checked');
        const mode = selectedRadio ? selectedRadio.value : 'auto';

        const boxAuto = document.getElementById('boxTitleAuto');
        const boxMaster = document.getElementById('boxTitleMaster');
        const boxManual = document.getElementById('boxTitleManual');

        if (boxAuto) boxAuto.style.display = (mode === 'auto') ? 'flex' : 'none';
        if (boxMaster) boxMaster.style.display = (mode === 'master') ? 'block' : 'none';
        if (boxManual) boxManual.style.display = (mode === 'manual') ? 'block' : 'none';

        if (autoSubmit && mode !== 'manual') {
            document.getElementById('filterLaporanForm').submit();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleTitleMode(false);

        const filterForm = document.getElementById('filterLaporanForm');
        if (!filterForm) return;

        // Auto-submit saat dropdown (OPD, Kategori Status, Master Judul) atau tanggal perolehan berubah
        const autoChangeElements = filterForm.querySelectorAll('select, input[type="date"]');
        autoChangeElements.forEach(el => {
            el.addEventListener('change', function() {
                filterForm.submit();
            });
        });

        // Submit form saat pengguna menekan tombol Enter pada input teks (pencarian q atau judul manual)
        const textInputs = filterForm.querySelectorAll('input[type="text"]');
        textInputs.forEach(input => {
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    filterForm.submit();
                }
            });
        });
    });
</script>
@endpush
@endsection
