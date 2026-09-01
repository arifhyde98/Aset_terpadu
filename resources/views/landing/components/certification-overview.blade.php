<!-- Land Certification Progress Overview Section -->
<section id="certification-overview" class="landing-certification-section py-5 bg-white">
    <div class="container">
        <div class="row align-items-center justify-content-between g-4">
            <!-- Left Info -->
            <div class="col-lg-5 text-center text-lg-start">
                <span class="text-uppercase tracking-wider text-success fw-bold small">Progres Pertanahan</span>
                <h3 class="fw-bold text-navy mt-1 mb-3">Monitoring Sertifikasi Tanah</h3>
                <p class="text-secondary small mb-4 lh-base">
                    Pemantauan terpusat terhadap legalitas dan progres pensertifikatan seluruh bidang tanah milik Pemerintah Daerah, mulai dari tanah belum terdata hingga terbitnya sertifikat resmi dari BPN.
                </p>
                <div class="d-flex flex-column flex-sm-row justify-content-center justify-content-lg-start gap-2">
                    <button type="button" class="btn btn-outline-success rounded-pill px-4 py-2 fw-semibold quick-service-btn d-inline-flex align-items-center justify-content-center gap-2" data-target-tab="land">
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

                    <!-- Multi-bar progress -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold text-navy">Capaian Pensertifikatan Tanah</span>
                            <span class="badge bg-success text-white px-2 py-1 rounded-pill small fw-semibold">{{ $pctTerbit }}% Terbit</span>
                        </div>
                        <div class="progress" style="height: 12px; border-radius: 6px; background-color: #E2E8F0;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pctTerbit }}%" aria-valuenow="{{ $pctTerbit }}" aria-valuemin="0" aria-valuemax="100" title="Sertifikat Terbit"></div>
                            <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $pctProses }}%" aria-valuenow="{{ $pctProses }}" aria-valuemin="0" aria-valuemax="100" title="Dalam Proses"></div>
                            <div class="progress-bar bg-secondary" role="progressbar" style="width: {{ $pctBelum }}%" aria-valuenow="{{ $pctBelum }}" aria-valuemin="0" aria-valuemax="100" title="Belum Bersertifikat"></div>
                        </div>
                    </div>

                    <!-- 3 Progress Breakdown Cards -->
                    <div class="row g-2 g-sm-3">
                        <!-- Sertifikat Terbit -->
                        <div class="col-4">
                            <div class="p-2 p-sm-3 rounded-3 bg-white border border-success-subtle text-center h-100 d-flex flex-column justify-content-center">
                                <div class="small text-success fw-bold mb-1 d-flex align-items-center justify-content-center gap-1" style="font-size: clamp(0.7rem, 1.2vw, 0.85rem);">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span class="d-none d-sm-inline">Sertifikat</span> Terbit
                                </div>
                                <div class="fs-5 fs-sm-4 fw-bold text-navy">
                                    {{ number_format($terbit, 0, ',', '.') }}
                                </div>
                                <div class="text-secondary" style="font-size: 0.7rem;">
                                    Bidang
                                </div>
                            </div>
                        </div>

                        <!-- Dalam Proses -->
                        <div class="col-4">
                            <div class="p-2 p-sm-3 rounded-3 bg-white border border-warning-subtle text-center h-100 d-flex flex-column justify-content-center">
                                <div class="small text-warning-emphasis fw-bold mb-1 d-flex align-items-center justify-content-center gap-1" style="font-size: clamp(0.7rem, 1.2vw, 0.85rem);">
                                    <i class="bi bi-hourglass-split"></i>
                                    <span>Proses</span>
                                </div>
                                <div class="fs-5 fs-sm-4 fw-bold text-navy">
                                    {{ number_format($proses, 0, ',', '.') }}
                                </div>
                                <div class="text-secondary" style="font-size: 0.7rem;">
                                    Bidang
                                </div>
                            </div>
                        </div>

                        <!-- Belum Bersertifikat -->
                        <div class="col-4">
                            <div class="p-2 p-sm-3 rounded-3 bg-white border border-light-subtle text-center h-100 d-flex flex-column justify-content-center">
                                <div class="small text-secondary fw-bold mb-1 d-flex align-items-center justify-content-center gap-1" style="font-size: clamp(0.7rem, 1.2vw, 0.85rem);">
                                    <i class="bi bi-dash-circle"></i>
                                    <span>Belum</span>
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
    </div>
</section>
