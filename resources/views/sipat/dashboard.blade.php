@extends('layouts.app')

@section('content')
<style>
    .dashboard-hero-card {
        background: linear-gradient(135deg, rgba(30, 94, 255, 0.05) 0%, rgba(59, 130, 246, 0.02) 100%);
        border: 1px solid var(--bs-border-color-translucent, rgba(0, 0, 0, 0.08));
        border-radius: 1.25rem;
    }
    .sipat-stat-card {
        border-radius: 1.25rem;
        border: 1px solid var(--bs-border-color-translucent, rgba(0, 0, 0, 0.08));
        background: var(--bs-card-bg, #ffffff);
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .sipat-stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 28px -6px rgba(0, 0, 0, 0.08) !important;
    }
    .stat-icon-circle {
        width: 48px;
        height: 48px;
        border-radius: 0.85rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }
    .stat-value-lg {
        font-size: 1.85rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.1;
    }
    .card-breakdown-box {
        background-color: var(--bs-tertiary-bg, rgba(248, 250, 252, 0.6));
        border-radius: 0.85rem;
        padding: 0.65rem 0.85rem;
        border: 1px dashed var(--bs-border-color, rgba(148, 163, 184, 0.25));
    }
    .breakdown-row {
        font-size: 0.76rem;
        padding: 0.25rem 0;
    }
    .breakdown-row:not(:last-child) {
        border-bottom: 1px solid var(--bs-border-color-translucent, rgba(0, 0, 0, 0.04));
    }
    .progress-bar-custom {
        height: 6px;
        border-radius: 3px;
        background-color: var(--bs-secondary-bg, rgba(148, 163, 184, 0.18));
        overflow: hidden;
    }
    .progress-bar-custom .progress-fill {
        height: 100%;
        border-radius: 3px;
        transition: width 0.6s ease-in-out;
    }
    .opd-legend-item {
        font-size: 0.8rem;
        padding: 0.35rem 0.5rem;
        border-radius: 0.5rem;
        transition: background-color 0.2s ease;
    }
    .opd-legend-item:hover {
        background-color: var(--bs-tertiary-bg, rgba(0, 0, 0, 0.03));
    }
    .opd-card-item {
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .opd-card-item:hover {
        background-color: var(--bs-body-bg, #ffffff) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        transform: translateY(-1px);
    }
    .hover-text-primary:hover {
        color: #1e40af !important;
    }
    .activity-timeline-item {
        position: relative;
        padding-left: 0.5rem;
    }
</style>

<div class="container-fluid px-0">
    <!-- Header Banner -->
    <div class="dashboard-hero-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1.5">
                    <span class="badge bg-primary text-white font-monospace px-2.5 py-1 rounded-pill" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                        <i class="bi bi-geo-alt-fill me-1"></i> MODUL SIPAT TERPADU
                    </span>
                    <span class="text-secondary small">&bull;</span>
                    <span class="text-secondary small fw-medium">Sistem Informasi Pengamanan Aset Tanah</span>
                </div>
                <h2 class="fw-bold mb-1 text-body">Dashboard Monitoring Aset Tanah</h2>
                <p class="text-secondary mb-0 small">
                    Rekapitulasi status pensertifikatan BPN, sebaran OPD, dan log pembaruan tanah KIB A Kabupaten Donggala.
                </p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="bg-body border px-3 py-2 rounded-3 shadow-sm d-flex align-items-center gap-2">
                    <i class="bi bi-calendar3 text-primary fs-5"></i>
                    <span class="small font-monospace text-body fw-semibold" style="line-height: 1.2;">
                        {{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}
                    </span>
                </div>
                <a href="{{ route('sipat.tanah-tak-tercatat.index') }}" class="btn btn-outline-warning text-dark d-flex align-items-center gap-2 rounded-3 px-3 py-2">
                    <i class="bi bi-geo-alt-fill text-warning"></i>
                    <span class="fw-semibold">Tanah Belum Tercatat</span>
                    @if(isset($totalTanahTakTercatat) && $totalTanahTakTercatat > 0)
                        <span class="badge bg-danger rounded-pill px-2 py-0.5 font-monospace fw-bold" style="font-size: 0.75rem;">{{ $totalTanahTakTercatat }}</span>
                    @endif
                </a>
                <a href="{{ route('sipat.aset.index') }}" class="btn btn-outline-primary d-flex align-items-center gap-2 rounded-3 px-3 py-2">
                    <i class="bi bi-table"></i> Data Aset
                </a>
                <a href="{{ route('sipat.aset.create') }}" class="btn btn-primary d-flex align-items-center gap-2 rounded-3 px-3 py-2 shadow-sm">
                    <i class="bi bi-plus-circle-fill"></i> Tambah Aset
                </a>
            </div>
        </div>
    </div>

    <!-- Baris 1: 4 Cards Utama dengan Height Seragam -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Total Aset Tanah Keseluruhan & Tercatat KIB A -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card sipat-stat-card h-100 p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="stat-icon-circle bg-primary-subtle text-primary">
                            <i class="bi bi-box-seam-fill"></i>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 font-monospace" style="font-size: 0.72rem;">
                            KESELURUHAN
                        </span>
                    </div>
                    <div class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.05em;">Tanah Keseluruhan</div>
                    <div class="stat-value-lg text-body mb-3">{{ number_format($totalAset, 0, ',', '.') }} <span class="fs-6 fw-normal text-secondary">Bidang</span></div>
                </div>

                <div class="card-breakdown-box">
                    <div class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom">
                        <span class="text-secondary fw-semibold small d-flex align-items-center gap-1">
                            <i class="bi bi-building-check text-primary"></i> Tercatat KIB A
                        </span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5 font-monospace fw-bold">{{ number_format($totalTanahTercatat ?? 1188, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom">
                        <a href="{{ route('sipat.tanah-tak-tercatat.index') }}" class="text-decoration-none text-warning-emphasis fw-bold small d-flex align-items-center gap-1">
                            <i class="bi bi-exclamation-circle-fill text-warning"></i> Belum Tercatat
                        </a>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-0.5 font-monospace fw-bold">{{ number_format($totalTanahTakTercatat ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-secondary small text-truncate me-2">&bull; Belum Bersertifikat (Murni)</span>
                        <span class="fw-semibold text-body font-monospace small">{{ number_format($totalBelumBersertifikat ?? 672, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Sudah Bersertifikat -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card sipat-stat-card h-100 p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="stat-icon-circle bg-success-subtle text-success">
                            <i class="bi bi-shield-check-fill"></i>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 font-monospace" style="font-size: 0.75rem;">
                            {{ $pctBersertifikat }}%
                        </span>
                    </div>
                    <div class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.05em;">Sudah Bersertifikat</div>
                    <div class="stat-value-lg text-success mb-2">{{ number_format($asetBersertifikat, 0, ',', '.') }} <span class="fs-6 fw-normal text-secondary">Bidang</span></div>
                    <div class="progress-bar-custom mb-3">
                        <div class="progress-fill bg-success" style="width: {{ $pctBersertifikat }}%;"></div>
                    </div>
                </div>

                <div class="card-breakdown-box">
                    @php $cnt = 0; @endphp
                    @foreach(($statusBreakdowns['bersertifikat'] ?? []) as $stName => $val)
                        @if($cnt < 2)
                            <div class="breakdown-row d-flex justify-content-between align-items-center text-secondary">
                                <span class="text-truncate me-2" title="{{ $stName }}">&bull; {{ $stName }}</span>
                                <span class="fw-semibold text-success font-monospace">{{ number_format($val) }}</span>
                            </div>
                            @php $cnt++; @endphp
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Card 3: Dalam Proses BPN -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card sipat-stat-card h-100 p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="stat-icon-circle bg-warning-subtle text-warning">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1 font-monospace" style="font-size: 0.75rem;">
                            {{ $pctProses }}%
                        </span>
                    </div>
                    <div class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.05em;">Dalam Proses BPN</div>
                    <div class="stat-value-lg text-warning-emphasis mb-2">{{ number_format($asetProses, 0, ',', '.') }} <span class="fs-6 fw-normal text-secondary">Bidang</span></div>
                    <div class="progress-bar-custom mb-3">
                        <div class="progress-fill bg-warning" style="width: {{ $pctProses }}%;"></div>
                    </div>
                </div>

                <div class="card-breakdown-box">
                    @php $cnt = 0; @endphp
                    @foreach(($statusBreakdowns['proses'] ?? []) as $stName => $val)
                        @if($cnt < 2)
                            <div class="breakdown-row d-flex justify-content-between align-items-center text-secondary">
                                <span class="text-truncate me-2" title="{{ $stName }}">&bull; {{ $stName }}</span>
                                <span class="fw-semibold text-warning-emphasis font-monospace">{{ number_format($val) }}</span>
                            </div>
                            @php $cnt++; @endphp
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Card 4: Ada Kendala / Sengketa -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card sipat-stat-card h-100 p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="stat-icon-circle bg-danger-subtle text-danger">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1 font-monospace" style="font-size: 0.75rem;">
                            {{ $pctKendala }}%
                        </span>
                    </div>
                    <div class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.05em;">Ada Kendala / Sengketa</div>
                    <div class="stat-value-lg text-danger mb-2">{{ number_format($asetKendala, 0, ',', '.') }} <span class="fs-6 fw-normal text-secondary">Bidang</span></div>
                    <div class="progress-bar-custom mb-3">
                        <div class="progress-fill bg-danger" style="width: {{ $pctKendala }}%;"></div>
                    </div>
                </div>

                <div class="card-breakdown-box">
                    @php $cnt = 0; @endphp
                    @foreach(($statusBreakdowns['kendala'] ?? []) as $stName => $val)
                        @if($cnt < 2)
                            <div class="breakdown-row d-flex justify-content-between align-items-center text-secondary">
                                <span class="text-truncate me-2" title="{{ $stName }}">&bull; {{ $stName }}</span>
                                <span class="fw-semibold text-danger font-monospace">{{ number_format($val) }}</span>
                            </div>
                            @php $cnt++; @endphp
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Baris 2: Line Chart & Doughnut Chart -->
    <div class="row g-3 mb-4">
        <!-- Chart Left: Progres Bulanan -->
        <div class="col-12 col-xl-7">
            <div class="card sipat-stat-card h-100 p-4">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-0 text-body">Progres Pensertifikatan Bulanan</h6>
                        <small class="text-secondary">Akumulasi tren status pensertifikatan tanah (Jan - Des {{ $chartYear }})</small>
                    </div>
                    <span class="badge bg-body text-body border px-2.5 py-1 small">Tahun {{ $chartYear }}</span>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-center gap-3 mb-3 small text-secondary">
                    <div class="d-flex align-items-center gap-1.5">
                        <span class="d-inline-block rounded-circle bg-success" style="width: 10px; height: 10px;"></span> Sertifikat Selesai
                    </div>
                    <div class="d-flex align-items-center gap-1.5">
                        <span class="d-inline-block rounded-circle bg-warning" style="width: 10px; height: 10px;"></span> Dalam Proses
                    </div>
                    <div class="d-flex align-items-center gap-1.5">
                        <span class="d-inline-block rounded-circle bg-primary" style="width: 10px; height: 10px;"></span> Belum Diurus
                    </div>
                </div>

                <div style="height: 290px; position: relative;" class="w-100">
                    <canvas id="sipatProgressChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Chart Right: Doughnut Chart OPD -->
        <div class="col-12 col-xl-5">
            <div class="card sipat-stat-card h-100 p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <div>
                            <h6 class="fw-bold mb-0 text-body">Distribusi Aset per OPD</h6>
                            <small class="text-secondary">5 OPD dengan bidang tanah terbanyak & progres sertifikasi</small>
                        </div>
                        <a href="{{ route('sipat.laporan.rekapOpd') }}" class="btn btn-xs btn-outline-primary rounded-pill px-2.5 py-1 text-decoration-none fw-medium" title="Lihat rekapitulasi data seluruh OPD">
                            <i class="bi bi-list-columns-reverse me-1"></i> Rekap Semua OPD
                        </a>
                    </div>

                    <div class="row align-items-center g-3 my-1">
                        <div class="col-12 col-md-5 col-lg-5">
                            <div style="height: 190px; position: relative;" class="d-flex align-items-center justify-content-center">
                                <canvas id="sipatOpdChart"></canvas>
                                <div class="position-absolute top-50 start-50 translate-middle text-center" style="pointer-events: none;">
                                    <div class="fw-bold text-navy fs-5 font-monospace lh-1">{{ number_format($totalAset, 0, ',', '.') }}</div>
                                    <div class="text-secondary small" style="font-size: 0.65rem;">Total Bidang</div>
                                </div>
                            </div>
                            <div class="text-center mt-2">
                                <span class="badge bg-light text-secondary border px-2 py-1 small" style="font-size: 0.72rem;">
                                    <i class="bi bi-pie-chart-fill text-primary me-1"></i> Top 5: <strong>{{ number_format(is_array($opdStats) && isset(reset($opdStats)['total']) ? array_sum(array_column($opdStats, 'total')) : array_sum((array)$opdStats), 0, ',', '.') }}</strong> Bidang
                                </span>
                            </div>
                        </div>

                        <div class="col-12 col-md-7 col-lg-7">
                            <div class="d-flex flex-column gap-2">
                                @php
                                    $colors = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444'];
                                    $i = 0;
                                @endphp
                                @foreach($opdStats as $key => $opd)
                                    @php
                                        $color = $colors[$i % count($colors)];
                                        $opdName = is_array($opd) ? ($opd['nama'] ?? $key) : $key;
                                        $totalCount = is_array($opd) ? ($opd['total'] ?? 0) : $opd;
                                        $bersertifikatCount = is_array($opd) ? ($opd['bersertifikat'] ?? 0) : 0;
                                        $prosesCount = is_array($opd) ? ($opd['proses'] ?? 0) : 0;
                                        $belumCount = is_array($opd) ? ($opd['belum_diproses'] ?? 0) : 0;
                                        $pctTotal = is_array($opd) ? ($opd['pct_of_total'] ?? 0) : ($totalAset > 0 ? round(($totalCount / $totalAset) * 100, 1) : 0);
                                        $pctSertif = is_array($opd) ? ($opd['pct_bersertifikat'] ?? 0) : ($totalCount > 0 ? round(($bersertifikatCount / $totalCount) * 100, 1) : 0);
                                        $pctProses = is_array($opd) ? ($opd['pct_proses'] ?? 0) : ($totalCount > 0 ? round(($prosesCount / $totalCount) * 100, 1) : 0);
                                        $pctBelum = is_array($opd) ? ($opd['pct_belum_diproses'] ?? 0) : ($totalCount > 0 ? round(($belumCount / $totalCount) * 100, 1) : 0);
                                        $opdId = is_array($opd) ? ($opd['opd_id'] ?? null) : null;

                                        $linkOpd = !empty($opdId)
                                            ? route('sipat.aset.index', ['opd_id' => $opdId])
                                            : route('sipat.aset.index', ['opd' => $opdName]);
                                        $linkBersertifikat = !empty($opdId)
                                            ? route('sipat.aset.index', ['opd_id' => $opdId, 'kategori_status' => 'sudah_bersertifikat'])
                                            : route('sipat.aset.index', ['opd' => $opdName, 'kategori_status' => 'sudah_bersertifikat']);
                                        $linkProses = !empty($opdId)
                                            ? route('sipat.aset.index', ['opd_id' => $opdId, 'kategori_status' => 'dalam_proses'])
                                            : route('sipat.aset.index', ['opd' => $opdName, 'kategori_status' => 'dalam_proses']);
                                        $linkBelum = !empty($opdId)
                                            ? route('sipat.aset.index', ['opd_id' => $opdId, 'kategori_status' => 'belum_diproses'])
                                            : route('sipat.aset.index', ['opd' => $opdName, 'kategori_status' => 'belum_diproses']);
                                    @endphp
                                    <div class="opd-card-item p-2 rounded-3 border bg-body-tertiary">
                                        <!-- Header OPD: Titik Warna, Nama OPD & Total Bidang -->
                                        <div class="d-flex align-items-center justify-content-between mb-1.5">
                                            <div class="d-flex align-items-center gap-2 min-width-0 me-2">
                                                <span class="d-inline-block rounded-circle flex-shrink-0" style="width: 10px; height: 10px; background-color: {{ $color }};"></span>
                                                <a href="{{ $linkOpd }}" class="text-body fw-bold text-truncate text-decoration-none small hover-text-primary" title="Filter seluruh aset: {{ $opdName }}">
                                                    {{ $opdName }}
                                                </a>
                                            </div>
                                            <div class="text-end flex-shrink-0 font-monospace">
                                                <span class="fw-bold text-navy small">{{ number_format($totalCount, 0, ',', '.') }}</span>
                                                <span class="text-secondary opacity-75" style="font-size: 0.72rem;">({{ $pctTotal }}%)</span>
                                            </div>
                                        </div>

                                        <!-- Multi-Segment Progress Bar -->
                                        <div class="progress mb-1.5" style="height: 5px; border-radius: 4px; background-color: rgba(148, 163, 184, 0.2);">
                                            <div class="progress-bar bg-success" style="width: {{ $pctSertif }}%;" title="Bersertifikat: {{ $bersertifikatCount }} bidang ({{ $pctSertif }}%)"></div>
                                            <div class="progress-bar bg-warning" style="width: {{ $pctProses }}%;" title="Proses: {{ $prosesCount }} bidang ({{ $pctProses }}%)"></div>
                                            <div class="progress-bar bg-secondary" style="width: {{ $pctBelum }}%;" title="Belum Diproses: {{ $belumCount }} bidang ({{ $pctBelum }}%)"></div>
                                        </div>

                                        <!-- Rincian Status Pensertifikatan: Bersertifikat, Proses, Belum Diproses -->
                                        <div class="d-flex align-items-center justify-content-between gap-1 flex-wrap" style="font-size: 0.72rem;">
                                            <a href="{{ $linkBersertifikat }}" class="badge bg-success-subtle text-success border border-success-subtle text-decoration-none px-1.5 py-0.5 rounded-pill font-monospace" title="{{ $bersertifikatCount }} Bidang Sudah Bersertifikat">
                                                <i class="bi bi-check-circle-fill me-0.5"></i> {{ number_format($bersertifikatCount, 0, ',', '.') }} Bersertifikat
                                            </a>
                                            <a href="{{ $linkProses }}" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle text-decoration-none px-1.5 py-0.5 rounded-pill font-monospace" title="{{ $prosesCount }} Bidang Dalam Proses BPN">
                                                <i class="bi bi-hourglass-split me-0.5"></i> {{ number_format($prosesCount, 0, ',', '.') }} Proses
                                            </a>
                                            <a href="{{ $linkBelum }}" class="badge bg-secondary-subtle text-secondary border border-secondary-subtle text-decoration-none px-1.5 py-0.5 rounded-pill font-monospace" title="{{ $belumCount }} Bidang Belum Diproses BPN">
                                                <i class="bi bi-dash-circle me-0.5"></i> {{ number_format($belumCount, 0, ',', '.') }} Belum
                                            </a>
                                        </div>
                                    </div>
                                    @php $i++; @endphp
                                @endforeach

                                @if(isset($opdLainnyaCount) && $opdLainnyaCount > 0)
                                    <div class="d-flex align-items-center justify-content-between px-2 py-1 small text-secondary">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="d-inline-block rounded-circle flex-shrink-0" style="width: 8px; height: 8px; background-color: #94a3b8;"></span>
                                            <span>OPD Lainnya (Sisa Keseluruhan)</span>
                                        </div>
                                        <span class="font-monospace fw-semibold">{{ number_format($opdLainnyaCount, 0, ',', '.') }} Bidang ({{ $opdLainnyaPct }}%)</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-breakdown-box d-flex align-items-center justify-content-between mt-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-database-fill text-primary fs-5"></i>
                        <span class="small fw-semibold text-body">Total Aset Terdata</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace px-2.5 py-1">
                            {{ number_format($totalTanahTercatat ?? 1188, 0, ',', '.') }} KIB A
                        </span>
                        <span class="fw-bold text-primary fs-6 font-monospace">{{ number_format($totalAset, 0, ',', '.') }} Bidang</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Baris Baru: Sebaran Aset Tanah & Progres Sertifikasi per OPD -->
    @if(isset($opdTableStats) && count($opdTableStats) > 0)
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card sipat-stat-card p-4">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-0.5 small fw-semibold">
                                <i class="bi bi-buildings-fill me-1"></i> REKAPITULASI INSTANSI
                            </span>
                            <span class="text-secondary small">&bull;</span>
                            <span class="text-secondary small fw-medium">{{ count($opdTableStats) }} Organisasi Perangkat Daerah Terdata</span>
                        </div>
                        <h5 class="fw-bold mb-0 text-body">Sebaran Aset Tanah & Progres Sertifikasi per OPD</h5>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="input-group input-group-sm" style="max-width: 260px;">
                            <span class="input-group-text bg-body-tertiary border-end-0">
                                <i class="bi bi-search text-secondary"></i>
                            </span>
                            <input type="text" id="searchOpdTable" class="form-control border-start-0" placeholder="Cari nama instansi OPD..." onkeyup="filterOpdDashboardTable()">
                        </div>
                        <a href="{{ route('sipat.laporan.rekapOpd') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            <i class="bi bi-file-earmark-text me-1"></i> Rekap Lengkap OPD
                        </a>
                        <a href="{{ route('sipat.aset.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                            <i class="bi bi-table me-1"></i> Semua Aset
                        </a>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 540px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0" id="tableSebaranOpd">
                        <thead class="bg-body-tertiary text-secondary small fw-semibold text-uppercase sticky-top" style="z-index: 2;">
                            <tr>
                                <th class="ps-3 py-2.5 text-center" style="width: 50px;">NO.</th>
                                <th class="py-2.5">ORGANISASI PERANGKAT DAERAH (OPD)</th>
                                <th class="text-center py-2.5">TOTAL BIDANG</th>
                                <th class="text-end py-2.5">TOTAL LUAS (M²)</th>
                                <th class="py-2.5" style="min-width: 190px;">PROGRES SERTIPIKAT</th>
                                <th class="text-center py-2.5">PROSES BPN</th>
                                <th class="text-center py-2.5">BELUM DIPROSES</th>
                                <th class="text-center py-2.5">KENDALA</th>
                                <th class="text-center pe-3 py-2.5">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @php $noOpd = 1; @endphp
                            @foreach($opdTableStats as $opdNameKey => $opdItem)
                                @php
                                    $opdId = $opdItem['opd_id'] ?? null;
                                    $linkOpd = !empty($opdId)
                                        ? route('sipat.aset.index', ['opd_id' => $opdId])
                                        : route('sipat.aset.index', ['opd' => $opdItem['nama']]);
                                    $linkSertif = !empty($opdId)
                                        ? route('sipat.aset.index', ['opd_id' => $opdId, 'kategori_status' => 'sudah_bersertifikat'])
                                        : route('sipat.aset.index', ['opd' => $opdItem['nama'], 'kategori_status' => 'sudah_bersertifikat']);
                                    $linkProses = !empty($opdId)
                                        ? route('sipat.aset.index', ['opd_id' => $opdId, 'kategori_status' => 'dalam_proses'])
                                        : route('sipat.aset.index', ['opd' => $opdItem['nama'], 'kategori_status' => 'dalam_proses']);
                                    $linkBelum = !empty($opdId)
                                        ? route('sipat.aset.index', ['opd_id' => $opdId, 'kategori_status' => 'belum_diproses'])
                                        : route('sipat.aset.index', ['opd' => $opdItem['nama'], 'kategori_status' => 'belum_diproses']);
                                    $pctSertif = $opdItem['persen_bersertifikat'] ?? 0;
                                @endphp
                                <tr class="opd-row-item" data-opd-name="{{ strtolower($opdItem['nama']) }}">
                                    <td class="ps-3 text-center text-secondary font-monospace">{{ $noOpd++ }}</td>
                                    <td class="fw-bold text-body">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-buildings text-primary flex-shrink-0"></i>
                                            <a href="{{ $linkOpd }}" class="text-body text-decoration-none hover-text-primary" title="Klik untuk filter aset: {{ $opdItem['nama'] }}">
                                                {{ $opdItem['nama'] }}
                                            </a>
                                        </div>
                                    </td>
                                    <td class="text-center font-monospace fw-bold fs-6 text-primary">
                                        {{ number_format($opdItem['total'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-end font-monospace text-secondary">
                                        {{ number_format($opdItem['luas'], 0, ',', '.') }} m²
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <a href="{{ $linkSertif }}" class="fw-semibold text-success font-monospace text-decoration-none" title="Lihat {{ $opdItem['bersertifikat'] }} bidang bersertifikat">
                                                {{ number_format($opdItem['bersertifikat']) }} <small class="text-secondary fw-normal">Selesai</small>
                                            </a>
                                            <span class="badge bg-success-subtle text-success font-monospace" style="font-size: 0.7rem;">{{ $pctSertif }}%</span>
                                        </div>
                                        <div class="progress-bar-custom" style="height: 6px;">
                                            <div class="progress-fill bg-success" style="width: {{ $pctSertif }}%;"></div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($opdItem['proses'] > 0)
                                            <a href="{{ $linkProses }}" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace px-2 py-0.5 rounded-pill text-decoration-none" title="Lihat aset dalam proses BPN">{{ $opdItem['proses'] }}</a>
                                        @else
                                            <span class="text-secondary font-monospace">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($opdItem['belum_diproses'] > 0)
                                            <a href="{{ $linkBelum }}" class="badge bg-secondary-subtle text-secondary font-monospace px-2 py-0.5 rounded-pill text-decoration-none" title="Lihat aset belum diproses">{{ $opdItem['belum_diproses'] }}</a>
                                        @else
                                            <span class="text-secondary font-monospace">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($opdItem['kendala'] > 0)
                                            <span class="badge bg-danger-subtle text-danger font-monospace px-2 py-0.5 rounded-pill" title="Ada kendala/masalah">{{ $opdItem['kendala'] }}</span>
                                        @else
                                            <span class="text-secondary font-monospace">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center pe-3">
                                        <a href="{{ $linkOpd }}" class="btn btn-xs btn-outline-primary rounded-pill px-2.5 py-1 small fw-semibold" title="Lihat aset di {{ $opdItem['nama'] }}">
                                            <i class="bi bi-funnel-fill me-1"></i> Filter
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            <tr id="emptyOpdSearchRow" style="display: none;">
                                <td colspan="9" class="text-center py-4 text-secondary">
                                    <i class="bi bi-search fs-3 mb-2 d-block opacity-50"></i>
                                    <span>Tidak ada OPD yang sesuai dengan kata kunci pencarian.</span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-body-secondary fw-bold small text-body sticky-bottom" style="z-index: 1;">
                            <tr>
                                <td colspan="2" class="ps-3 py-2.5 text-uppercase">TOTAL KESELURUHAN ({{ count($opdTableStats) }} OPD)</td>
                                <td class="text-center font-monospace fs-6 text-primary">{{ number_format($totalAset, 0, ',', '.') }}</td>
                                <td class="text-end font-monospace">{{ number_format($totalLuas, 0, ',', '.') }} m²</td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="text-success font-monospace">{{ number_format($asetBersertifikat, 0, ',', '.') }} Selesai</span>
                                        <span class="badge bg-success-subtle text-success font-monospace" style="font-size: 0.7rem;">{{ $pctBersertifikat }}%</span>
                                    </div>
                                    <div class="progress-bar-custom" style="height: 6px;">
                                        <div class="progress-fill bg-success" style="width: {{ $pctBersertifikat }}%;"></div>
                                    </div>
                                </td>
                                <td class="text-center font-monospace text-warning-emphasis">{{ number_format($asetProses, 0, ',', '.') }}</td>
                                <td class="text-center font-monospace text-secondary">{{ number_format($asetBelumDiurus, 0, ',', '.') }}</td>
                                <td class="text-center font-monospace text-danger">{{ number_format($asetKendala, 0, ',', '.') }}</td>
                                <td class="text-center pe-3">
                                    <a href="{{ route('sipat.aset.index') }}" class="btn btn-xs btn-primary rounded-pill px-2.5 py-1 small fw-semibold">Semua</a>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Baris Baru: Sebaran Wilayah Kecamatan & Progres Pensertifikatan -->
    @if(isset($kecamatanStats) && count($kecamatanStats) > 0)
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card sipat-stat-card p-4">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-0.5 small fw-semibold">
                                <i class="bi bi-geo-alt-fill me-1"></i> MASTER WILAYAH
                            </span>
                            <span class="text-secondary small">&bull;</span>
                            <span class="text-secondary small fw-medium">16 Kecamatan Kabupaten Donggala</span>
                        </div>
                        <h5 class="fw-bold mb-0 text-body">Sebaran Aset Tanah & Progres Sertifikasi per Kecamatan</h5>
                    </div>
                    <a href="{{ route('sipat.aset.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        <i class="bi bi-table me-1"></i> Lihat Seluruh Data Aset
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-body-tertiary text-secondary small fw-semibold text-uppercase">
                            <tr>
                                <th class="ps-3 py-2.5">WILAYAH KECAMATAN</th>
                                <th class="text-center py-2.5">TOTAL BIDANG</th>
                                <th class="text-end py-2.5">TOTAL LUAS (M²)</th>
                                <th class="py-2.5" style="min-width: 180px;">PROGRES SERTIPIKAT</th>
                                <th class="text-center py-2.5">PROSES BPN</th>
                                <th class="text-center py-2.5">BELUM DIURUS</th>
                                <th class="text-center py-2.5">KENDALA</th>
                                <th class="text-center pe-3 py-2.5">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @foreach($kecamatanStats as $kecName => $kStat)
                                @php
                                    $kId = $kStat['id'] ?? 0;
                                    $linkKec = $kId > 0 
                                        ? route('sipat.aset.index', ['kecamatan_id' => $kId]) 
                                        : route('sipat.aset.index', ['kecamatan_id' => 'KOSONG']);
                                    $pctSertif = $kStat['persen_bersertifikat'] ?? 0;
                                @endphp
                                <tr>
                                    <td class="ps-3 fw-bold text-body">
                                        <div class="d-flex align-items-center gap-2">
                                            @if($kId > 0)
                                                <i class="bi bi-geo-alt-fill text-primary"></i>
                                                <span class="text-body">{{ $kecName }}</span>
                                            @else
                                                <i class="bi bi-globe text-secondary"></i>
                                                <span class="text-secondary italic">{{ $kecName }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center font-monospace fw-bold fs-6 text-primary">
                                        {{ number_format($kStat['total'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-end font-monospace text-secondary">
                                        {{ number_format($kStat['luas'], 0, ',', '.') }} m²
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span class="fw-semibold text-success font-monospace">{{ number_format($kStat['bersertifikat']) }} <small class="text-secondary fw-normal">Selesai</small></span>
                                            <span class="badge bg-success-subtle text-success font-monospace" style="font-size: 0.7rem;">{{ $pctSertif }}%</span>
                                        </div>
                                        <div class="progress-bar-custom" style="height: 6px;">
                                            <div class="progress-fill bg-success" style="width: {{ $pctSertif }}%;"></div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($kStat['proses'] > 0)
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace px-2 py-0.5 rounded-pill">{{ $kStat['proses'] }}</span>
                                        @else
                                            <span class="text-secondary font-monospace">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($kStat['belum_diurus'] > 0)
                                            <span class="badge bg-secondary-subtle text-secondary font-monospace px-2 py-0.5 rounded-pill">{{ $kStat['belum_diurus'] }}</span>
                                        @else
                                            <span class="text-secondary font-monospace">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($kStat['kendala'] > 0)
                                            <span class="badge bg-danger-subtle text-danger font-monospace px-2 py-0.5 rounded-pill">{{ $kStat['kendala'] }}</span>
                                        @else
                                            <span class="text-secondary font-monospace">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center pe-3">
                                        <a href="{{ $linkKec }}" class="btn btn-xs btn-outline-primary rounded-pill px-2.5 py-1 small fw-semibold" title="Lihat aset di {{ $kecName }}">
                                            <i class="bi bi-funnel-fill me-1"></i> Filter
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Baris 3: Aktivitas Terbaru Audit Log & Ringkasan Status -->
    <div class="row g-3 mb-4">
        <!-- Aktivitas Terbaru -->
        <div class="col-12 col-lg-5">
            <div class="card sipat-stat-card h-100 p-4">
                <h6 class="fw-bold mb-3 text-body"><i class="bi bi-clock-history text-primary me-2"></i>Aktivitas Terbaru</h6>
                
                <div class="d-flex flex-column gap-3" id="rt-recent-logs">
                    @forelse($recentLogs as $log)
                        <div class="activity-timeline-item d-flex align-items-start gap-3 border-bottom pb-2.5">
                            <div class="badge bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                                <i class="bi bi-journal-text fs-6"></i>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <h6 class="mb-0.5 fw-bold text-body small text-truncate" title="{{ $log->nama_aset ?? 'Update Status BPN' }}">{{ $log->nama_aset ?? 'Update Status BPN' }}</h6>
                                <p class="mb-0 text-secondary small text-truncate" title="{{ $log->nama_status ?? 'Pembaruan data' }}">
                                    {{ $log->nama_status ?? 'Pembaruan data' }} oleh <strong class="text-body">{{ $log->user_name ?? 'Sistem' }}</strong>
                                </p>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <span class="badge bg-body text-secondary font-monospace" style="font-size: 0.7rem;">
                                    {{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-secondary py-4">
                            <i class="bi bi-inbox fs-3 mb-2 d-block opacity-50"></i>
                            <span class="small">Belum ada riwayat aktivitas terbaru.</span>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Ringkasan Status Sertifikasi Mini Cards -->
        <div class="col-12 col-lg-7">
            <div class="card sipat-stat-card h-100 p-4 d-flex flex-column justify-content-between">
                <div>
                    <h6 class="fw-bold mb-3 text-body"><i class="bi bi-pie-chart-fill text-success me-2"></i>Ringkasan Kategori Sertifikasi</h6>
                    <div class="row g-3">
                        <div class="col-6 col-sm-3">
                            <div class="mini-stat-card bg-body h-100">
                                <div class="text-success fw-semibold small mb-1">Bersertifikat</div>
                                <div class="d-flex align-items-baseline justify-content-between mb-2">
                                    <span class="fs-4 fw-bold text-body font-monospace">{{ number_format($asetBersertifikat) }}</span>
                                    <span class="small text-success fw-bold font-monospace">{{ $pctBersertifikat }}%</span>
                                </div>
                                <div class="progress-bar-custom">
                                    <div class="progress-fill bg-success" style="width: {{ $pctBersertifikat }}%;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-6 col-sm-3">
                            <div class="mini-stat-card bg-body h-100">
                                <div class="text-warning-emphasis fw-semibold small mb-1">Dalam Proses</div>
                                <div class="d-flex align-items-baseline justify-content-between mb-2">
                                    <span class="fs-4 fw-bold text-body font-monospace">{{ number_format($asetProses) }}</span>
                                    <span class="small text-warning-emphasis fw-bold font-monospace">{{ $pctProses }}%</span>
                                </div>
                                <div class="progress-bar-custom">
                                    <div class="progress-fill bg-warning" style="width: {{ $pctProses }}%;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-6 col-sm-3">
                            <div class="mini-stat-card bg-body h-100">
                                <div class="text-danger fw-semibold small mb-1">Ada Kendala</div>
                                <div class="d-flex align-items-baseline justify-content-between mb-2">
                                    <span class="fs-4 fw-bold text-body font-monospace">{{ number_format($asetKendala) }}</span>
                                    <span class="small text-danger fw-bold font-monospace">{{ $pctKendala }}%</span>
                                </div>
                                <div class="progress-bar-custom">
                                    <div class="progress-fill bg-danger" style="width: {{ $pctKendala }}%;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-6 col-sm-3">
                            <div class="mini-stat-card bg-body h-100">
                                <div class="text-primary fw-semibold small mb-1">Belum Bersertifikat</div>
                                <div class="d-flex align-items-baseline justify-content-between mb-2">
                                    <span class="fs-4 fw-bold text-body font-monospace">{{ number_format($totalBelumBersertifikat ?? 672) }}</span>
                                    <span class="small text-primary fw-bold font-monospace">{{ $pctBelumBersertifikat ?? 56.5 }}%</span>
                                </div>
                                <div class="progress-bar-custom">
                                    <div class="progress-fill bg-primary" style="width: {{ $pctBelumBersertifikat ?? 56.5 }}%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-primary-subtle text-primary rounded-3 p-3 mt-3 d-flex align-items-center gap-2.5 small">
                    <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
                    <span>Data diperbarui secara real-time berdasarkan log proses BPN terbaru dari masing-masing OPD Pengelola.</span>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark' || document.body.classList.contains('dark-mode');
        const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.05)';
        const textColor = isDarkMode ? '#94a3b8' : '#64748b';

        // 1. Line Chart Progres Pensertifikatan Bulanan
        const progressCtx = document.getElementById('sipatProgressChart');
        if (progressCtx) {
            new Chart(progressCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                    datasets: [
                        {
                            label: 'Sertifikat Selesai',
                            data: @json($chartSelesai),
                            borderColor: '#10b981',
                            backgroundColor: 'transparent',
                            borderWidth: 2.5,
                            pointBackgroundColor: '#10b981',
                            pointRadius: 3.5,
                            tension: 0.35
                        },
                        {
                            label: 'Dalam Proses',
                            data: @json($chartProses),
                            borderColor: '#f59e0b',
                            backgroundColor: 'transparent',
                            borderWidth: 2.5,
                            pointBackgroundColor: '#f59e0b',
                            pointRadius: 3.5,
                            tension: 0.35
                        },
                        {
                            label: 'Belum Diurus',
                            data: @json($chartBelum),
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.08)',
                            borderWidth: 2.5,
                            fill: true,
                            pointBackgroundColor: '#3b82f6',
                            pointRadius: 3.5,
                            tension: 0.35
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { padding: 10, cornerRadius: 8 }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor },
                            ticks: { color: textColor, font: { size: 11 } }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor, font: { size: 11 } }
                        }
                    }
                }
            });
        }

        // 2. Doughnut Chart OPD
        const opdCtx = document.getElementById('sipatOpdChart');
        if (opdCtx) {
            const opdLabels = @json($opdChartLabels ?? []);
            const opdData = @json($opdChartData ?? []);
            const opdBreakdown = @json($opdChartBreakdown ?? []);
            const opdColors = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#94a3b8'];

            new Chart(opdCtx, {
                type: 'doughnut',
                data: {
                    labels: opdLabels,
                    datasets: [{
                        data: opdData,
                        backgroundColor: opdColors.slice(0, opdLabels.length),
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            padding: 12,
                            cornerRadius: 8,
                            titleFont: { weight: 'bold', size: 12 },
                            bodyFont: { size: 11 },
                            callbacks: {
                                label: function(context) {
                                    const idx = context.dataIndex;
                                    const item = opdBreakdown[idx];
                                    if (!item) {
                                        return `Total: ${context.raw} Bidang`;
                                    }
                                    const pct = item.pct_of_total ?? 0;
                                    return [
                                        `Total: ${item.total} Bidang (${pct}%)`,
                                        `  ✓ Bersertifikat : ${item.bersertifikat ?? 0}`,
                                        `  ⏳ Dalam Proses  : ${item.proses ?? 0}`,
                                        `  — Belum Diproses: ${item.belum_diproses ?? 0}`
                                    ];
                                }
                            }
                        }
                    }
                }
            });
        }
    });

    function filterOpdDashboardTable() {
        const input = document.getElementById('searchOpdTable');
        if (!input) return;
        const filter = input.value.toLowerCase().trim();
        const rows = document.querySelectorAll('.opd-row-item');
        let visibleCount = 0;

        rows.forEach(row => {
            const name = row.getAttribute('data-opd-name') || '';
            if (filter === '' || name.includes(filter)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const emptyRow = document.getElementById('emptyOpdSearchRow');
        if (emptyRow) {
            emptyRow.style.display = (visibleCount === 0 && filter !== '') ? '' : 'none';
        }
    }
</script>
@endpush
@endsection
