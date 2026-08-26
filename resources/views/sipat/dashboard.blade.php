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
        <!-- Card 1: Total Aset Tanah -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card sipat-stat-card h-100 p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="stat-icon-circle bg-primary-subtle text-primary">
                            <i class="bi bi-box-seam-fill"></i>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 font-monospace" style="font-size: 0.72rem;">
                            KIB A
                        </span>
                    </div>
                    <div class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.05em;">Total Aset Tanah</div>
                    <div class="stat-value-lg text-body mb-3">{{ number_format($totalAset, 0, ',', '.') }} <span class="fs-6 fw-normal text-secondary">Bidang</span></div>
                </div>

                <div class="card-breakdown-box">
                    <div class="d-flex justify-content-between align-items-center mb-1.5 pb-1.5 border-bottom">
                        <a href="{{ route('sipat.tanah-tak-tercatat.index') }}" class="text-decoration-none text-warning-emphasis fw-bold small d-flex align-items-center gap-1">
                            <i class="bi bi-exclamation-circle-fill text-warning"></i> Tanah Belum Tercatat
                        </a>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-0.5 font-monospace fw-bold">{{ number_format($totalTanahTakTercatat ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-secondary fw-semibold small">Belum Diurus BPN</span>
                        <span class="fw-bold text-body small font-monospace">{{ number_format($asetBelumDiurus, 0, ',', '.') }}</span>
                    </div>
                    @php $cnt = 0; @endphp
                    @foreach(($statusBreakdowns['belum_diurus'] ?? []) as $stName => $val)
                        @if($cnt < 2)
                            <div class="breakdown-row d-flex justify-content-between align-items-center text-secondary">
                                <span class="text-truncate me-2" title="{{ $stName }}">&bull; {{ $stName }}</span>
                                <span class="fw-semibold text-body font-monospace">{{ number_format($val) }}</span>
                            </div>
                            @php $cnt++; @endphp
                        @endif
                    @endforeach
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
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h6 class="fw-bold mb-0 text-body">Distribusi Aset per OPD</h6>
                            <small class="text-secondary">5 OPD dengan bidang tanah terbanyak</small>
                        </div>
                    </div>

                    <div class="row align-items-center g-3 my-2">
                        <div class="col-12 col-sm-5">
                            <div style="height: 180px; position: relative;">
                                <canvas id="sipatOpdChart"></canvas>
                            </div>
                        </div>
                        <div class="col-12 col-sm-7">
                            <div class="d-flex flex-column gap-1">
                                @php
                                    $colors = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444'];
                                    $i = 0;
                                    $opdTotalSum = array_sum($opdStats);
                                @endphp
                                @foreach($opdStats as $opdName => $count)
                                    @php
                                        $pctOpd = $opdTotalSum > 0 ? round(($count / $opdTotalSum) * 100, 1) : 0;
                                    @endphp
                                    <div class="opd-legend-item d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2 min-width-0 me-2">
                                            <span class="d-inline-block rounded-circle flex-shrink-0" style="width: 8px; height: 8px; background-color: {{ $colors[$i % count($colors)] }};"></span>
                                            <span class="text-body fw-medium text-truncate" title="{{ $opdName }}">{{ $opdName }}</span>
                                        </div>
                                        <span class="fw-semibold text-secondary small flex-shrink-0 font-monospace">{{ number_format($count) }} <span class="opacity-75" style="font-size: 0.72rem;">({{ $pctOpd }}%)</span></span>
                                    </div>
                                    @php $i++; @endphp
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-breakdown-box d-flex align-items-center justify-content-between mt-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-database-fill text-primary fs-5"></i>
                        <span class="small fw-semibold text-body">Total Aset Terdata</span>
                    </div>
                    <span class="fw-bold text-primary fs-6 font-monospace">{{ number_format($totalAset, 0, ',', '.') }} Bidang</span>
                </div>
            </div>
        </div>
    </div>

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
                                <div class="text-primary fw-semibold small mb-1">Belum Diurus</div>
                                <div class="d-flex align-items-baseline justify-content-between mb-2">
                                    <span class="fs-4 fw-bold text-body font-monospace">{{ number_format($asetBelumDiurus) }}</span>
                                    <span class="small text-primary fw-bold font-monospace">{{ $pctBelumDiurus }}%</span>
                                </div>
                                <div class="progress-bar-custom">
                                    <div class="progress-fill bg-primary" style="width: {{ $pctBelumDiurus }}%;"></div>
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
            new Chart(opdCtx, {
                type: 'doughnut',
                data: {
                    labels: @json(array_keys($opdStats)),
                    datasets: [{
                        data: @json(array_values($opdStats)),
                        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { padding: 10, cornerRadius: 8 }
                    }
                }
            });
        }
    });
</script>
@endpush
@endsection
