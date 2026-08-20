@extends('layouts.app')

@section('title', 'Dashboard Utama Terpadu')

@section('content')
<div class="container-fluid px-0">
    
    <!-- SECTION 1: Hero Welcome Header & Quick Action Center -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 pb-3 border-bottom gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-1.5 rounded-pill fw-bold">
                    <i class="bi bi-shield-check me-1"></i> SIPAT TERPADU SYSTEM
                </span>
                <span class="text-secondary small">| {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>
            </div>
            <h3 class="fw-bold text-navy mb-0">Overview Operasional Terpadu</h3>
            <p class="text-secondary mb-0 small">Integrasi data real-time dari modul SIPAT (Aset Tanah), eLABEL (Box & Label Gudang), dan eRANDIS (Kendaraan Dinas).</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('sipat.aset.index') }}" class="btn btn-outline-primary fw-semibold border shadow-sm">
                <i class="bi bi-geo-alt me-1"></i> Aset Tanah
            </a>
            <a href="{{ route('elabel.dashboard') }}" class="btn btn-outline-info fw-semibold border shadow-sm">
                <i class="bi bi-archive me-1"></i> Katalog eLABEL
            </a>
            <a href="{{ route('erandis.dashboard') }}" class="btn btn-outline-warning fw-semibold text-dark border shadow-sm">
                <i class="bi bi-car-front me-1"></i> Kendaraan eRANDIS
            </a>
        </div>
    </div>

    <!-- SECTION 2: 4 Top KPI Summary Cards (High-Contrast Surface Design) -->
    <div class="row g-3 mb-4">
        <!-- Card 1: SIPAT Aset Tanah (Blue Accent) -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-primary">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-1.5 rounded-pill fw-bold">
                            <i class="bi bi-geo-alt-fill me-1"></i> MODUL SIPAT
                        </span>
                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                            <i class="bi bi-journal-album fs-4 text-primary"></i>
                        </div>
                    </div>
                    <small class="text-secondary fw-semibold text-uppercase d-block mb-1">TOTAL ASET TANAH</small>
                    <h2 class="fw-bold text-navy mb-1">{{ number_format($sipatTotalTanah) }} <span class="fs-6 text-secondary fw-normal">Bidang</span></h2>
                    
                    <!-- Breakdown Rincian Status Pensertifikatan SIPAT -->
                    <div class="mt-2 pt-2 border-top">
                        <div class="d-flex justify-content-between align-items-center text-secondary small mb-1">
                            <span><i class="bi bi-patch-check-fill text-primary me-1"></i> Sudah Bersertifikat:</span>
                            <strong class="text-dark">{{ number_format($sipatSertifikatCount) }} Bidang</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-secondary small mb-1">
                            <span><i class="bi bi-hourglass-split text-warning me-1"></i> Sedang Diproses BPN:</span>
                            <strong class="text-dark">{{ number_format($sipatProsesBpnCount) }} Berkas</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-secondary small">
                            <span><i class="bi bi-exclamation-circle text-danger me-1"></i> Belum Bersertifikat:</span>
                            <strong class="text-danger">{{ number_format($sipatBelumSertifikatCount) }} Bidang</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: eRANDIS Kendaraan Dinas (Yellow/Orange Accent) -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-warning">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 px-3 py-1.5 rounded-pill fw-bold">
                            <i class="bi bi-car-front-fill me-1 text-warning"></i> MODUL ERANDIS
                        </span>
                        <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                            <i class="bi bi-car-front-fill fs-4 text-warning"></i>
                        </div>
                    </div>
                    <small class="text-secondary fw-semibold text-uppercase d-block mb-1">TOTAL KENDARAAN DINAS</small>
                    <h2 class="fw-bold text-navy mb-1">{{ number_format($erandisStats['total']) }} <span class="fs-6 text-secondary fw-normal">Unit</span></h2>
                    
                    <!-- Breakdown Rincian Kondisi eRANDIS -->
                    <div class="mt-2 pt-2 border-top">
                        <div class="d-flex justify-content-between align-items-center text-secondary small mb-1">
                            <span><i class="bi bi-check-circle-fill text-success me-1"></i> Kondisi Baik (Layak):</span>
                            <strong class="text-dark">{{ number_format($erandisStats['baik']) }} Unit</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-secondary small mb-1">
                            <span><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Kondisi Rusak Ringan:</span>
                            <strong class="text-dark">{{ number_format($erandisStats['rusak_ringan']) }} Unit</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-secondary small">
                            <span><i class="bi bi-x-octagon-fill text-danger me-1"></i> Kondisi Rusak Berat:</span>
                            <strong class="text-danger">{{ number_format($erandisStats['rusak_berat'] + $erandisStats['hilang']) }} Unit</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: eLABEL Box & Digital Arsip (Cyan/Teal Accent) -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-info">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-3 py-1.5 rounded-pill fw-bold">
                            <i class="bi bi-box-seam-fill me-1"></i> MODUL ELABEL
                        </span>
                        <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                            <i class="bi bi-box-seam-fill fs-4 text-info"></i>
                        </div>
                    </div>
                    <small class="text-secondary fw-semibold text-uppercase d-block mb-1">DOKUMEN TER-LABEL</small>
                    <h2 class="fw-bold text-navy mb-1">{{ number_format($elabelTotalBpkb + $elabelTotalSertifikat + $elabelTotalSurat) }} <span class="fs-6 text-secondary fw-normal">Dokumen</span></h2>
                    
                    <!-- Breakdown Rincian Dokumen eLABEL -->
                    <div class="mt-2 pt-2 border-top">
                        <div class="d-flex justify-content-between align-items-center text-secondary small mb-1">
                            <span><i class="bi bi-card-checklist text-primary me-1"></i> BPKB Kendaraan:</span>
                            <strong class="text-dark">{{ number_format($elabelTotalBpkb) }} Unit</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-secondary small mb-1">
                            <span><i class="bi bi-patch-check text-success me-1"></i> Sertifikat Tanah:</span>
                            <strong class="text-dark">{{ number_format($elabelTotalSertifikat) }} Berkas</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-secondary small">
                            <span><i class="bi bi-archive text-info me-1"></i> Box Gudang Aktif:</span>
                            <strong class="text-info">{{ number_format($elabelTotalBoxes) }} Box</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Status Peminjaman & Layanan Aktif (Purple Accent) -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-danger">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-purple bg-opacity-10 text-purple border border-purple border-opacity-25 px-3 py-1.5 rounded-pill fw-bold" style="color: #6b21a8; background: rgba(107, 33, 168, 0.1);">
                            <i class="bi bi-clock-history me-1"></i> LAYANAN AKTIF
                        </span>
                        <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                            <i class="bi bi-clock-history fs-4 text-danger"></i>
                        </div>
                    </div>
                    <small class="text-secondary fw-semibold text-uppercase d-block mb-1">ARSIP DIPINJAM</small>
                    <h2 class="fw-bold text-navy mb-1">{{ number_format($elabelPeminjamanAktif) }} <span class="fs-6 text-secondary fw-normal">Dipinjam</span></h2>
                    <div class="d-flex align-items-center justify-content-between text-secondary small mt-3 pt-2 border-top">
                        <span>Progres BPN: <strong>{{ $sipatProsesBpnCount }} Berkas</strong></span>
                        <span class="fw-semibold text-danger"><i class="bi bi-arrow-repeat me-1"></i>Aktif</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 3: Visual Analytics Section (Charts) -->
    <div class="row g-4 mb-4">
        <!-- Chart 1: Status Pensertifikatan BPN (SIPAT) -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold text-navy mb-0"><i class="bi bi-pie-chart-fill text-primary me-2"></i> Legalitas Sertifikat BPN</h6>
                        <small class="text-secondary">Status kepemilikan aset tanah</small>
                    </div>
                </div>
                <div class="card-body p-4 d-flex align-items-center justify-content-center" style="min-height: 260px;">
                    <canvas id="chartSipatBpn" style="max-height: 220px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Chart 2: Kondisi Kendaraan Dinas (eRANDIS) -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold text-navy mb-0"><i class="bi bi-donut-chart-fill text-warning me-2"></i> Kondisi Armada Kendaraan</h6>
                        <small class="text-secondary">Kelayakan operasional eRANDIS</small>
                    </div>
                </div>
                <div class="card-body p-4 d-flex align-items-center justify-content-center" style="min-height: 260px;">
                    <canvas id="chartErandisKondisi" style="max-height: 220px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Chart 3: Top 5 OPD Pengelola Aset Terbanyak -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold text-navy mb-0"><i class="bi bi-bar-chart-line-fill text-info me-2"></i> Top 5 OPD Pengelola Aset</h6>
                        <small class="text-secondary">Alokasi kendaraan per OPD</small>
                    </div>
                </div>
                <div class="card-body p-4 d-flex align-items-center justify-content-center" style="min-height: 260px;">
                    <canvas id="chartTopOpd" style="max-height: 220px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 4: Split Main Content (Tables & Live Activity Stream) -->
    <div class="row g-4">
        <!-- Left Main Column (Table Kendaraan & Box Status) -->
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold text-navy mb-0"><i class="bi bi-truck text-warning me-2"></i> Status Armada Kendaraan Terkini (eRANDIS)</h6>
                        <small class="text-secondary">Monitoring pengoperasian kendaraan dinas pemda</small>
                    </div>
                    <a href="{{ route('vehicles.index') }}" class="btn btn-sm btn-light border fw-semibold">Lihat Semua <i class="bi bi-chevron-right ms-1"></i></a>
                </div>
                <div class="card-body p-0 mt-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 text-secondary small fw-semibold">PLAT NOMOR</th>
                                    <th class="py-3 text-secondary small fw-semibold">KENDARAAN</th>
                                    <th class="py-3 text-secondary small fw-semibold">PENGGUNA / OPD</th>
                                    <th class="py-3 text-secondary small fw-semibold text-center">KONDISI</th>
                                    <th class="pe-4 py-3 text-secondary small fw-semibold text-end">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($latestVehicles as $v)
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <span class="badge bg-dark text-white font-monospace px-2.5 py-1.5 fs-6 fw-bold rounded-2">{{ $v->no_polisi }}</span>
                                        </td>
                                        <td class="py-3">
                                            <div class="fw-bold text-navy">{{ $v->merk }} {{ $v->tipe }}</div>
                                            <small class="text-secondary">{{ $v->tahun_pembuatan ?? '-' }}</small>
                                        </td>
                                        <td class="py-3">
                                            <div class="fw-semibold text-dark">{{ $v->pemegang ?: '-' }}</div>
                                            <small class="text-secondary">{{ Str::limit($v->opd, 35) }}</small>
                                        </td>
                                        <td class="py-3 text-center">
                                            <x-condition-badge :kondisi="$v->kondisi" />
                                        </td>
                                        <td class="pe-4 py-3 text-end">
                                            <a href="{{ route('vehicles.index', ['q' => $v->no_polisi]) }}" class="btn btn-sm btn-light border">Detail</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-secondary">Belum ada data kendaraan tercatat.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side Column (Live Activity Log & Alerts) -->
        <div class="col-xl-4 d-flex flex-column gap-4">
            <!-- Box eLABEL Quick Monitor -->
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h6 class="fw-bold text-navy mb-3"><i class="bi bi-box-seam text-info me-2"></i> Ringkasan Box Gudang (eLABEL)</h6>
                <div class="row g-2">
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-3 border text-center">
                            <small class="text-secondary d-block fw-semibold text-uppercase" style="font-size: 0.65rem;">BOX BPKB</small>
                            <span class="fw-bold fs-4 text-primary">{{ \App\Models\Elabel\ElabelBox::count() }}</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-3 border text-center">
                            <small class="text-secondary d-block fw-semibold text-uppercase" style="font-size: 0.65rem;">BOX SERTIFIKAT</small>
                            <span class="fw-bold fs-4 text-success">{{ \App\Models\Elabel\ElabelSertifikatBox::count() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Real-time Activity Timeline -->
            <div class="card border-0 shadow-sm rounded-4 p-4 flex-grow-1">
                <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-activity text-primary me-2"></i> Live Activity Feed</h6>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 small">Realtime</span>
                </div>

                <div class="position-relative border-start border-2 border-light ms-2 ps-3">
                    @forelse($activities->take(5) as $act)
                        <div class="position-relative mb-3 pb-2">
                            <span class="position-absolute top-0 start-0 translate-middle bg-primary border border-white border-2 rounded-circle" style="width: 12px; height: 12px; margin-left: -13px;"></span>
                            <div class="fw-semibold text-dark small">{{ $act->description }}</div>
                            <div class="d-flex align-items-center justify-content-between text-secondary mt-1" style="font-size: 0.75rem;">
                                <span>Oleh: <strong class="text-dark">{{ $act->user->name ?? 'Sistem' }}</strong></span>
                                <span>{{ $act->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-secondary small">Belum ada aktivitas tercatat hari ini.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Chart 1: SIPAT Status Sertifikat BPN
        const ctxSipat = document.getElementById('chartSipatBpn');
        if (ctxSipat) {
            new Chart(ctxSipat, {
                type: 'pie',
                data: {
                    labels: ['Bersertifikat', 'Sedang Diproses BPN', 'Belum Diproses'],
                    datasets: [{
                        data: [{{ $sipatSertifikatCount }}, {{ $sipatProsesBpnCount }}, {{ $sipatBelumSertifikatCount }}],
                        backgroundColor: ['#3b82f6', '#f59e0b', '#ef4444'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }

        // Chart 2: eRANDIS Kondisi Kendaraan
        const ctxErandis = document.getElementById('chartErandisKondisi');
        if (ctxErandis) {
            new Chart(ctxErandis, {
                type: 'doughnut',
                data: {
                    labels: ['Baik', 'Rusak Ringan', 'Rusak Berat'],
                    datasets: [{
                        data: [{{ $erandisStats['baik'] }}, {{ $erandisStats['rusak_ringan'] }}, {{ $erandisStats['rusak_berat'] }}],
                        backgroundColor: ['#22c55e', '#f59e0b', '#ef4444'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }

        // Chart 3: Top OPD Pengelola Aset
        const ctxTopOpd = document.getElementById('chartTopOpd');
        if (ctxTopOpd) {
            new Chart(ctxTopOpd, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($topOpds->pluck('name')->map(fn($n) => Str::limit($n, 14))->toArray()) !!},
                    datasets: [{
                        label: 'Jumlah Kendaraan',
                        data: {!! json_encode($topOpds->pluck('count')->toArray()) !!},
                        backgroundColor: '#06b6d4',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }
    });
</script>
@endpush
@endsection

