<!-- Land Certification Status Overview Section with Glowing Elements -->
<section id="certification-overview" class="landing-certification-section py-5 bg-white">
    <div class="container">
        <div class="row align-items-center justify-content-between g-4">
            <!-- Left Info -->
            <div class="col-lg-5 text-center text-lg-start">
                <span class="text-uppercase tracking-wider text-success fw-bold small">Progres Pertanahan</span>
                <h3 class="fw-bold text-navy mt-1 mb-3">Status Sertifikasi Tanah</h3>
                <p class="text-secondary small mb-4 lh-base">
                    Pemantauan terpusat terhadap legalitas dan progres sertifikasi seluruh bidang tanah milik Pemerintah Daerah, mulai dari inventarisasi awal hingga terbitnya sertifikat resmi dari Kantor Pertanahan/BPN.
                </p>
                <div class="d-flex flex-column flex-sm-row justify-content-center justify-content-lg-start gap-2">
                    <button type="button" class="btn btn-outline-success rounded-pill px-4 py-2 fw-semibold quick-service-btn d-inline-flex align-items-center justify-content-center gap-2 hover-elevate" data-target-tab="land">
                        <span>Cari Status Sertifikat</span>
                        <i class="bi bi-arrow-up-right small"></i>
                    </button>
                </div>
            </div>

            <!-- Right Visual Progress Cards -->
            <div class="col-lg-7">
                <div class="p-3 p-sm-4 rounded-4 bg-light border border-light-subtle shadow-sm">
                    @php
                        $sertifikasi = $stats['sertifikasi'] ?? [];
                        $totalSertifikasi = max(1, $sertifikasi['total'] ?? 1);
                        $terbit = $sertifikasi['bersertifikat'] ?? 0;
                        $proses = $sertifikasi['proses'] ?? 0;
                        $belum = $sertifikasi['belum'] ?? 0;
                        
                        $pctTerbit = round(($terbit / $totalSertifikasi) * 100, 1);
                        $pctProses = round(($proses / $totalSertifikasi) * 100, 1);
                        $pctBelum = round(($belum / $totalSertifikasi) * 100, 1);
                    @endphp

                    <!-- Multi-bar progress with Shimmer Glow -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold text-navy">Capaian Sertifikasi Tanah</span>
                            <span class="badge bg-success text-white px-3 py-1 rounded-pill small fw-semibold shadow-sm">{{ $pctTerbit }}% Sudah Bersertifikat</span>
                        </div>
                        <div class="progress progress-glow" style="height: 14px; border-radius: 8px; background-color: #E2E8F0;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pctTerbit }}%" aria-valuenow="{{ $pctTerbit }}" aria-valuemin="0" aria-valuemax="100" title="Sudah Bersertifikat"></div>
                            <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $pctProses }}%" aria-valuenow="{{ $pctProses }}" aria-valuemin="0" aria-valuemax="100" title="Dalam Proses"></div>
                            <div class="progress-bar bg-secondary" role="progressbar" style="width: {{ $pctBelum }}%" aria-valuenow="{{ $pctBelum }}" aria-valuemin="0" aria-valuemax="100" title="Belum Diproses"></div>
                        </div>
                    </div>

                    <!-- 3 Progress Breakdown Cards (Sudah Bersertifikat, Dalam Proses, Belum Diproses) -->
                    <div class="row g-2 g-sm-3">
                        <!-- 1. Sudah Bersertifikat (Emerald Accent) -->
                        <div class="col-4">
                            <div class="p-2 p-sm-3 rounded-3 bg-white border border-success-subtle text-center h-100 d-flex flex-column justify-content-center hover-elevate transition-all" style="border-top: 3px solid #10B981 !important;">
                                <div class="small text-success fw-bold mb-1 d-flex align-items-center justify-content-center gap-1" style="font-size: clamp(0.68rem, 1.1vw, 0.82rem);">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>Bersertifikat</span>
                                </div>
                                <div class="fs-5 fs-sm-4 fw-bold text-navy">
                                    {{ number_format($terbit, 0, ',', '.') }}
                                </div>
                                <div class="text-secondary" style="font-size: 0.7rem;">
                                    Bidang
                                </div>
                            </div>
                        </div>

                        <!-- 2. Dalam Proses (Amber Accent) -->
                        <div class="col-4">
                            <div class="p-2 p-sm-3 rounded-3 bg-white border border-warning-subtle text-center h-100 d-flex flex-column justify-content-center hover-elevate transition-all" style="border-top: 3px solid #F59E0B !important;">
                                <div class="small text-warning-emphasis fw-bold mb-1 d-flex align-items-center justify-content-center gap-1" style="font-size: clamp(0.68rem, 1.1vw, 0.82rem);">
                                    <i class="bi bi-hourglass-split"></i>
                                    <span>Dalam Proses</span>
                                </div>
                                <div class="fs-5 fs-sm-4 fw-bold text-navy">
                                    {{ number_format($proses, 0, ',', '.') }}
                                </div>
                                <div class="text-secondary" style="font-size: 0.7rem;">
                                    Bidang
                                </div>
                            </div>
                        </div>

                        <!-- 3. Belum Diproses (Slate Accent) -->
                        <div class="col-4">
                            <div class="p-2 p-sm-3 rounded-3 bg-white border border-light-subtle text-center h-100 d-flex flex-column justify-content-center hover-elevate transition-all" style="border-top: 3px solid #94A3B8 !important;">
                                <div class="small text-secondary fw-bold mb-1 d-flex align-items-center justify-content-center gap-1" style="font-size: clamp(0.68rem, 1.1vw, 0.82rem);">
                                    <i class="bi bi-dash-circle"></i>
                                    <span>Belum Diproses</span>
                                </div>
                                <div class="fs-5 fs-sm-4 fw-bold text-navy">
                                    {{ number_format($belum, 0, ',', '.') }}
                                </div>
                                <div class="text-secondary" style="font-size: 0.7rem;">
                                    Bidang
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bagian Visualisasi Pie Chart: 2 Sebaran Aset (per OPD & per Kecamatan) -->
        <div class="mt-4 pt-4 border-top">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 small fw-bold font-monospace">
                        <i class="bi bi-pie-chart-fill me-1"></i> GRAFIK SEBARAN & PROGRES PENSERTIFIKATAN
                    </span>
                    <h5 class="fw-bold text-navy mt-1 mb-0">Distribusi Aset Tanah Daerah (Pie Chart)</h5>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="text-secondary small d-none d-sm-inline">
                        <i class="bi bi-cursor me-1"></i> Klik diagram atau tombol untuk membuka tabel lengkap
                    </span>
                </div>
            </div>

            <!-- 2 Pie Chart Cards (Side by Side) -->
            <div class="row g-3">
                <!-- Card 1: Pie Chart Sebaran per OPD -->
                <div class="col-12 col-lg-6">
                    <div class="p-3.5 rounded-4 bg-light border border-light-subtle h-100 d-flex flex-column justify-content-between shadow-sm hover-elevate transition-all" style="border-top: 3px solid #3B82F6 !important;">
                        <div>
                            <!-- Header Card -->
                            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                        <i class="bi bi-buildings-fill"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-navy mb-0">Sebaran Aset per OPD</h6>
                                        <small class="text-secondary" style="font-size: 0.72rem;">Proporsi bidang tanah antar instansi perangkat daerah</small>
                                    </div>
                                </div>
                                <span class="badge bg-primary text-white rounded-pill px-2.5 py-1 font-monospace small fw-bold">
                                    {{ count($opdTableStats ?? []) }} OPD
                                </span>
                            </div>

                            <!-- Chart Canvas Container -->
                            <div class="position-relative d-flex justify-content-center align-items-center mb-3" style="min-height: 220px; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#modalSebaranOpdLanding" title="Klik untuk membuka tabel lengkap OPD">
                                <div style="width: 210px; height: 210px; position: relative;">
                                    <canvas id="landingOpdPieChart"></canvas>
                                    <div class="position-absolute top-50 start-50 translate-middle text-center" style="pointer-events: none;">
                                        <div class="fs-4 fw-bold text-navy font-monospace lh-1">{{ number_format($totalAsetTanah ?? 0) }}</div>
                                        <div class="text-secondary small text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Bidang Tanah</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Highlights Mini Badges / Legend -->
                            <div class="d-flex flex-wrap justify-content-center gap-1.5 mb-2">
                                @php
                                    $opdColorMap = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#94a3b8'];
                                @endphp
                                @foreach(($opdChartBreakdown ?? []) as $i => $item)
                                    <span class="badge bg-white text-body border border-light-subtle rounded-pill px-2.5 py-1 d-inline-flex align-items-center gap-1.5 shadow-xs" style="font-size: 0.72rem;">
                                        <span class="rounded-circle d-inline-block" style="width: 8px; height: 8px; background-color: {{ $opdColorMap[$i] ?? '#94a3b8' }};"></span>
                                        <span class="fw-semibold text-truncate" style="max-width: 110px;">{{ $item['nama'] }}</span>
                                        <strong class="font-monospace text-navy">{{ number_format($item['total']) }}</strong>
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <!-- Card Action Button: Membuka Modal Tabel -->
                        <div class="pt-2 mt-auto">
                            <button type="button" class="btn btn-sm btn-white border border-primary-subtle text-primary fw-semibold rounded-pill w-100 py-2 d-flex align-items-center justify-content-center gap-1.5 hover-elevate shadow-xs" data-bs-toggle="modal" data-bs-target="#modalSebaranOpdLanding">
                                <i class="bi bi-table text-primary"></i>
                                <span>Buka Tabel Rekapitulasi Lengkap ({{ count($opdTableStats ?? []) }} OPD)</span>
                                <i class="bi bi-arrow-right small"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Pie Chart Sebaran per Kecamatan -->
                <div class="col-12 col-lg-6">
                    <div class="p-3.5 rounded-4 bg-light border border-light-subtle h-100 d-flex flex-column justify-content-between shadow-sm hover-elevate transition-all" style="border-top: 3px solid #10B981 !important;">
                        <div>
                            <!-- Header Card -->
                            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                        <i class="bi bi-geo-alt-fill"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-navy mb-0">Sebaran per Kecamatan</h6>
                                        <small class="text-secondary" style="font-size: 0.72rem;">Proporsi bidang tanah per wilayah kecamatan</small>
                                    </div>
                                </div>
                                <span class="badge bg-success text-white rounded-pill px-2.5 py-1 font-monospace small fw-bold">
                                    {{ count($kecamatanStats ?? []) }} Wilayah
                                </span>
                            </div>

                            <!-- Chart Canvas Container -->
                            <div class="position-relative d-flex justify-content-center align-items-center mb-3" style="min-height: 220px; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#modalSebaranKecamatanLanding" title="Klik untuk membuka tabel lengkap Kecamatan">
                                <div style="width: 210px; height: 210px; position: relative;">
                                    <canvas id="landingKecPieChart"></canvas>
                                    <div class="position-absolute top-50 start-50 translate-middle text-center" style="pointer-events: none;">
                                        <div class="fs-4 fw-bold text-navy font-monospace lh-1">{{ count($kecamatanStats ?? []) }}</div>
                                        <div class="text-secondary small text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Wilayah</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Highlights Mini Badges / Legend -->
                            <div class="d-flex flex-wrap justify-content-center gap-1.5 mb-2">
                                @php
                                    $kecColorMap = ['#10b981', '#3b82f6', '#f59e0b', '#06b6d4', '#8b5cf6', '#94a3b8'];
                                @endphp
                                @foreach(($kecChartBreakdown ?? []) as $i => $item)
                                    <span class="badge bg-white text-body border border-light-subtle rounded-pill px-2.5 py-1 d-inline-flex align-items-center gap-1.5 shadow-xs" style="font-size: 0.72rem;">
                                        <span class="rounded-circle d-inline-block" style="width: 8px; height: 8px; background-color: {{ $kecColorMap[$i] ?? '#94a3b8' }};"></span>
                                        <span class="fw-semibold text-truncate" style="max-width: 110px;">{{ $item['nama'] }}</span>
                                        <strong class="font-monospace text-navy">{{ number_format($item['total']) }}</strong>
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <!-- Card Action Button: Membuka Modal Tabel -->
                        <div class="pt-2 mt-auto">
                            <button type="button" class="btn btn-sm btn-white border border-success-subtle text-success fw-semibold rounded-pill w-100 py-2 d-flex align-items-center justify-content-center gap-1.5 hover-elevate shadow-xs" data-bs-toggle="modal" data-bs-target="#modalSebaranKecamatanLanding">
                                <i class="bi bi-table text-success"></i>
                                <span>Buka Tabel Rekapitulasi Lengkap ({{ count($kecamatanStats ?? []) }} Wilayah)</span>
                                <i class="bi bi-arrow-right small"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========================================================================= -->
<!-- MODAL 1: TABEL LENGKAP SEBARAN ASET PER OPD -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalSebaranOpdLanding" tabindex="-1" aria-labelledby="modalSebaranOpdLandingLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-primary text-white p-3 p-sm-4 border-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center shadow-sm flex-shrink-0" style="width: 44px; height: 44px;">
                        <i class="bi bi-buildings-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalSebaranOpdLandingLabel">Sebaran Aset Tanah & Progres Sertifikasi per OPD</h5>
                        <small class="text-white-50">Rekapitulasi {{ count($opdTableStats ?? []) }} Organisasi Perangkat Daerah Pemerintah Kabupaten Donggala</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-sm-4 bg-body-tertiary">
                <!-- Search & Info Bar -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div class="input-group input-group-sm" style="max-width: 320px;">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" id="searchLandingOpd" class="form-control border-start-0" placeholder="Cari nama instansi OPD..." onkeyup="filterLandingOpdTable()">
                    </div>
                    <span class="badge bg-light text-secondary border px-3 py-1.5 font-monospace small">
                        Total Terdata: <strong>{{ number_format($totalAsetTanah ?? 0) }}</strong> Bidang Tanah
                    </span>
                </div>

                <!-- Table Container -->
                <div class="table-responsive bg-white rounded-3 border shadow-sm" style="max-height: 480px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0" id="tableLandingOpd">
                        <thead class="bg-body-secondary text-secondary small fw-semibold text-uppercase sticky-top" style="z-index: 2;">
                            <tr>
                                <th class="ps-3 py-2.5 text-center" style="width: 50px;">NO.</th>
                                <th class="py-2.5">ORGANISASI PERANGKAT DAERAH (OPD)</th>
                                <th class="text-center py-2.5">TOTAL BIDANG</th>
                                <th class="text-end py-2.5">LUAS (M²)</th>
                                <th class="py-2.5" style="min-width: 170px;">PROGRES SERTIPIKAT</th>
                                <th class="text-center py-2.5">PROSES BPN</th>
                                <th class="text-center py-2.5">BELUM DIPROSES</th>
                                <th class="text-center pe-3 py-2.5">KENDALA</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @php $noLandingOpd = 1; @endphp
                            @foreach($opdTableStats ?? [] as $opd)
                                <tr class="landing-opd-row" data-name="{{ strtolower($opd['nama'] ?? '') }}">
                                    <td class="ps-3 text-center text-secondary font-monospace">{{ $noLandingOpd++ }}</td>
                                    <td class="fw-bold text-body">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-building text-primary small"></i>
                                            <span>{{ $opd['nama'] ?? '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center font-monospace fw-bold fs-6 text-primary">{{ number_format($opd['total'] ?? 0) }}</td>
                                    <td class="text-end font-monospace text-secondary">{{ number_format($opd['luas'] ?? 0, 0, ',', '.') }} m²</td>
                                    <td>
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span class="text-success fw-semibold font-monospace">{{ number_format($opd['bersertifikat'] ?? 0) }} Selesai</span>
                                            <span class="badge bg-success-subtle text-success font-monospace" style="font-size: 0.7rem;">{{ $opd['persen_bersertifikat'] ?? 0 }}%</span>
                                        </div>
                                        <div class="progress" style="height: 6px; border-radius: 3px; background-color: #E2E8F0;">
                                            <div class="progress-bar bg-success" style="width: {{ $opd['persen_bersertifikat'] ?? 0 }}%;"></div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if(($opd['proses'] ?? 0) > 0)
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace px-2 py-0.5 rounded-pill">{{ $opd['proses'] }}</span>
                                        @else
                                            <span class="text-secondary font-monospace">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if(($opd['belum_diproses'] ?? 0) > 0)
                                            <span class="badge bg-secondary-subtle text-secondary font-monospace px-2 py-0.5 rounded-pill">{{ $opd['belum_diproses'] }}</span>
                                        @else
                                            <span class="text-secondary font-monospace">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center pe-3">
                                        @if(($opd['kendala'] ?? 0) > 0)
                                            <span class="badge bg-danger-subtle text-danger font-monospace px-2 py-0.5 rounded-pill">{{ $opd['kendala'] }}</span>
                                        @else
                                            <span class="text-secondary font-monospace">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            <tr id="emptyLandingOpdRow" style="display: none;">
                                <td colspan="8" class="text-center py-4 text-secondary">
                                    <i class="bi bi-search fs-3 mb-2 d-block opacity-50"></i>
                                    <span>Tidak ada instansi OPD yang cocok dengan kata kunci pencarian.</span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-body-secondary fw-bold small text-body sticky-bottom" style="z-index: 1;">
                            <tr>
                                <td colspan="2" class="ps-3 py-2.5 text-uppercase">TOTAL KESELURUHAN ({{ count($opdTableStats ?? []) }} OPD)</td>
                                <td class="text-center font-monospace fs-6 text-primary">{{ number_format($totalAsetTanah ?? 0) }}</td>
                                <td class="text-end font-monospace">{{ number_format($totalLuasTanah ?? 0, 0, ',', '.') }} m²</td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="text-success font-monospace">{{ number_format($asetBersertifikat ?? 0) }} Selesai</span>
                                        <span class="badge bg-success-subtle text-success font-monospace">{{ $pctBersertifikat ?? 0 }}%</span>
                                    </div>
                                    <div class="progress" style="height: 6px; border-radius: 3px; background-color: #E2E8F0;">
                                        <div class="progress-bar bg-success" style="width: {{ $pctBersertifikat ?? 0 }}%;"></div>
                                    </div>
                                </td>
                                <td class="text-center font-monospace text-warning-emphasis">{{ number_format($asetProses ?? 0) }}</td>
                                <td class="text-center font-monospace text-secondary">{{ number_format($asetBelumDiurus ?? 0) }}</td>
                                <td class="text-center pe-3 font-monospace text-danger">{{ number_format($asetKendala ?? 0) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-white border-top px-4 py-2.5 d-flex justify-content-between">
                <span class="text-secondary small"><i class="bi bi-info-circle me-1"></i> Rekapitulasi disinkronkan secara berkala dengan basis data SIPAT Terpadu.</span>
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 2: TABEL LENGKAP SEBARAN ASET PER KECAMATAN -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalSebaranKecamatanLanding" tabindex="-1" aria-labelledby="modalSebaranKecamatanLandingLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-success text-white p-3 p-sm-4 border-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white text-success d-flex align-items-center justify-content-center shadow-sm flex-shrink-0" style="width: 44px; height: 44px;">
                        <i class="bi bi-geo-alt-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalSebaranKecamatanLandingLabel">Sebaran Aset Tanah & Progres Sertifikasi per Kecamatan</h5>
                        <small class="text-white-50">Rekapitulasi 16 Wilayah Kecamatan Pemerintah Kabupaten Donggala</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-sm-4 bg-body-tertiary">
                <!-- Search & Info Bar -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div class="input-group input-group-sm" style="max-width: 320px;">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" id="searchLandingKec" class="form-control border-start-0" placeholder="Cari nama kecamatan..." onkeyup="filterLandingKecTable()">
                    </div>
                    <span class="badge bg-light text-secondary border px-3 py-1.5 font-monospace small">
                        Total Terdata: <strong>{{ number_format($totalAsetTanah ?? 0) }}</strong> Bidang Tanah
                    </span>
                </div>

                <!-- Table Container -->
                <div class="table-responsive bg-white rounded-3 border shadow-sm" style="max-height: 480px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0" id="tableLandingKec">
                        <thead class="bg-body-secondary text-secondary small fw-semibold text-uppercase sticky-top" style="z-index: 2;">
                            <tr>
                                <th class="ps-3 py-2.5 text-center" style="width: 50px;">NO.</th>
                                <th class="py-2.5">WILAYAH KECAMATAN</th>
                                <th class="text-center py-2.5">TOTAL BIDANG</th>
                                <th class="text-end py-2.5">LUAS (M²)</th>
                                <th class="py-2.5" style="min-width: 170px;">PROGRES SERTIPIKAT</th>
                                <th class="text-center py-2.5">PROSES BPN</th>
                                <th class="text-center py-2.5">BELUM DIURUS</th>
                                <th class="text-center pe-3 py-2.5">KENDALA</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @php $noLandingKec = 1; @endphp
                            @foreach($kecamatanStats ?? [] as $kStat)
                                <tr class="landing-kec-row" data-name="{{ strtolower($kStat['nama'] ?? '') }}">
                                    <td class="ps-3 text-center text-secondary font-monospace">{{ $noLandingKec++ }}</td>
                                    <td class="fw-bold text-body">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-geo-alt text-success small"></i>
                                            <span>{{ $kStat['nama'] ?? '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center font-monospace fw-bold fs-6 text-primary">{{ number_format($kStat['total'] ?? 0) }}</td>
                                    <td class="text-end font-monospace text-secondary">{{ number_format($kStat['luas'] ?? 0, 0, ',', '.') }} m²</td>
                                    <td>
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span class="text-success fw-semibold font-monospace">{{ number_format($kStat['bersertifikat'] ?? 0) }} Selesai</span>
                                            <span class="badge bg-success-subtle text-success font-monospace" style="font-size: 0.7rem;">{{ $kStat['persen_bersertifikat'] ?? 0 }}%</span>
                                        </div>
                                        <div class="progress" style="height: 6px; border-radius: 3px; background-color: #E2E8F0;">
                                            <div class="progress-bar bg-success" style="width: {{ $kStat['persen_bersertifikat'] ?? 0 }}%;"></div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if(($kStat['proses'] ?? 0) > 0)
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace px-2 py-0.5 rounded-pill">{{ $kStat['proses'] }}</span>
                                        @else
                                            <span class="text-secondary font-monospace">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if(($kStat['belum_diurus'] ?? 0) > 0)
                                            <span class="badge bg-secondary-subtle text-secondary font-monospace px-2 py-0.5 rounded-pill">{{ $kStat['belum_diurus'] }}</span>
                                        @else
                                            <span class="text-secondary font-monospace">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center pe-3">
                                        @if(($kStat['kendala'] ?? 0) > 0)
                                            <span class="badge bg-danger-subtle text-danger font-monospace px-2 py-0.5 rounded-pill">{{ $kStat['kendala'] }}</span>
                                        @else
                                            <span class="text-secondary font-monospace">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            <tr id="emptyLandingKecRow" style="display: none;">
                                <td colspan="8" class="text-center py-4 text-secondary">
                                    <i class="bi bi-search fs-3 mb-2 d-block opacity-50"></i>
                                    <span>Tidak ada wilayah kecamatan yang cocok dengan kata kunci pencarian.</span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-body-secondary fw-bold small text-body sticky-bottom" style="z-index: 1;">
                            <tr>
                                <td colspan="2" class="ps-3 py-2.5 text-uppercase">TOTAL KESELURUHAN ({{ count($kecamatanStats ?? []) }} KECAMATAN)</td>
                                <td class="text-center font-monospace fs-6 text-primary">{{ number_format($totalAsetTanah ?? 0) }}</td>
                                <td class="text-end font-monospace">{{ number_format($totalLuasTanah ?? 0, 0, ',', '.') }} m²</td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="text-success font-monospace">{{ number_format($asetBersertifikat ?? 0) }} Selesai</span>
                                        <span class="badge bg-success-subtle text-success font-monospace">{{ $pctBersertifikat ?? 0 }}%</span>
                                    </div>
                                    <div class="progress" style="height: 6px; border-radius: 3px; background-color: #E2E8F0;">
                                        <div class="progress-bar bg-success" style="width: {{ $pctBersertifikat ?? 0 }}%;"></div>
                                    </div>
                                </td>
                                <td class="text-center font-monospace text-warning-emphasis">{{ number_format($asetProses ?? 0) }}</td>
                                <td class="text-center font-monospace text-secondary">{{ number_format($asetBelumDiurus ?? 0) }}</td>
                                <td class="text-center pe-3 font-monospace text-danger">{{ number_format($asetKendala ?? 0) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-white border-top px-4 py-2.5 d-flex justify-content-between">
                <span class="text-secondary small"><i class="bi bi-info-circle me-1"></i> Data terintegrasi dengan batas wilayah resmi Kabupaten Donggala.</span>
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // 1. Inisialisasi Pie Chart Sebaran OPD
        const opdCanvas = document.getElementById('landingOpdPieChart');
        if (opdCanvas && typeof Chart !== 'undefined') {
            const opdLabels = @json($opdChartLabels ?? []);
            const opdData = @json($opdChartData ?? []);
            const opdBreakdown = @json($opdChartBreakdown ?? []);
            const opdColors = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#94a3b8'];

            new Chart(opdCanvas, {
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
                                    if (!item) return `Total: ${context.raw} Bidang`;
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
                    },
                    onClick: function() {
                        const modalEl = document.getElementById('modalSebaranOpdLanding');
                        if (modalEl && window.bootstrap) {
                            bootstrap.Modal.getOrCreateInstance(modalEl).show();
                        }
                    }
                }
            });
        }

        // 2. Inisialisasi Pie Chart Sebaran Kecamatan
        const kecCanvas = document.getElementById('landingKecPieChart');
        if (kecCanvas && typeof Chart !== 'undefined') {
            const kecLabels = @json($kecChartLabels ?? []);
            const kecData = @json($kecChartData ?? []);
            const kecBreakdown = @json($kecChartBreakdown ?? []);
            const kecColors = ['#10b981', '#3b82f6', '#f59e0b', '#06b6d4', '#8b5cf6', '#94a3b8'];

            new Chart(kecCanvas, {
                type: 'doughnut',
                data: {
                    labels: kecLabels,
                    datasets: [{
                        data: kecData,
                        backgroundColor: kecColors.slice(0, kecLabels.length),
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
                                    const item = kecBreakdown[idx];
                                    if (!item) return `Total: ${context.raw} Bidang`;
                                    const pct = item.pct_of_total ?? 0;
                                    return [
                                        `Total: ${item.total} Bidang (${pct}%)`,
                                        `  ✓ Bersertifikat : ${item.bersertifikat ?? 0}`,
                                        `  ⏳ Dalam Proses  : ${item.proses ?? 0}`,
                                        `  — Belum Diurus  : ${item.belum_diurus ?? 0}`
                                    ];
                                }
                            }
                        }
                    },
                    onClick: function() {
                        const modalEl = document.getElementById('modalSebaranKecamatanLanding');
                        if (modalEl && window.bootstrap) {
                            bootstrap.Modal.getOrCreateInstance(modalEl).show();
                        }
                    }
                }
            });
        }
    });

    function filterLandingOpdTable() {
        const input = document.getElementById('searchLandingOpd');
        if (!input) return;
        const filter = input.value.toLowerCase().trim();
        const rows = document.querySelectorAll('.landing-opd-row');
        let count = 0;

        rows.forEach(r => {
            const name = r.getAttribute('data-name') || '';
            if (filter === '' || name.includes(filter)) {
                r.style.display = '';
                count++;
            } else {
                r.style.display = 'none';
            }
        });

        const empty = document.getElementById('emptyLandingOpdRow');
        if (empty) {
            empty.style.display = (count === 0 && filter !== '') ? '' : 'none';
        }
    }

    function filterLandingKecTable() {
        const input = document.getElementById('searchLandingKec');
        if (!input) return;
        const filter = input.value.toLowerCase().trim();
        const rows = document.querySelectorAll('.landing-kec-row');
        let count = 0;

        rows.forEach(r => {
            const name = r.getAttribute('data-name') || '';
            if (filter === '' || name.includes(filter)) {
                r.style.display = '';
                count++;
            } else {
                r.style.display = 'none';
            }
        });

        const empty = document.getElementById('emptyLandingKecRow');
        if (empty) {
            empty.style.display = (count === 0 && filter !== '') ? '' : 'none';
        }
    }
</script>
