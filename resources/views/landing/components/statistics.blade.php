<!-- Statistics Section -->
<section id="statistics-section" class="landing-statistics-section py-5 bg-light-subtle border-top border-bottom border-light-subtle">
    <div class="container">
        <!-- Section Header -->
        <div class="text-center mb-4 mb-md-5">
            <span class="text-uppercase tracking-wider text-primary fw-bold small">Data Terpadu</span>
            <h3 class="fw-bold text-navy mt-1 mb-2">Statistik Data Aset Daerah</h3>
            <p class="text-secondary small mx-auto mb-0" style="max-width: 580px;">
                Rekapitulasi data aset milik Pemerintah Daerah yang telah terdata dan termonitor dalam sistem SIPAT Terpadu.
            </p>
        </div>

        <!-- 4 Stats Cards -->
        <div class="row g-2 g-sm-3 g-lg-4 justify-content-center">
            <!-- Total Aset -->
            <div class="col-6 col-md-3">
                <div class="stat-card p-3 p-md-4 rounded-4 bg-white border border-light-subtle shadow-sm text-center h-100 position-relative overflow-hidden d-flex flex-column justify-content-between">
                    <div class="stat-icon-bg">
                        <i class="bi bi-layers-fill"></i>
                    </div>
                    <div>
                        <div class="stat-number fw-extrabold text-navy mb-1">
                            {{ number_format($stats['total_aset'] ?? 0, 0, ',', '.') }}
                        </div>
                        <div class="stat-label text-secondary fw-semibold small">
                            Total Aset Daerah
                        </div>
                    </div>
                    <div>
                        <span class="badge bg-primary-subtle text-primary mt-2 rounded-pill small px-2 py-0" style="font-size: 0.72rem;">
                            Tanah & Kendaraan
                        </span>
                    </div>
                </div>
            </div>

            <!-- Kendaraan Dinas -->
            <div class="col-6 col-md-3">
                <div class="stat-card p-3 p-md-4 rounded-4 bg-white border border-light-subtle shadow-sm text-center h-100 position-relative overflow-hidden d-flex flex-column justify-content-between">
                    <div class="stat-icon-bg text-primary-subtle">
                        <i class="bi bi-car-front-fill"></i>
                    </div>
                    <div>
                        <div class="stat-number fw-extrabold text-primary mb-1">
                            {{ number_format($stats['total_kendaraan'] ?? 0, 0, ',', '.') }}
                        </div>
                        <div class="stat-label text-secondary fw-semibold small">
                            Kendaraan Dinas
                        </div>
                    </div>
                    <div>
                        <span class="badge bg-primary-subtle text-primary mt-2 rounded-pill small px-2 py-0" style="font-size: 0.72rem;">
                            E-RANDIS
                        </span>
                    </div>
                </div>
            </div>

            <!-- Aset Tanah -->
            <div class="col-6 col-md-3">
                <div class="stat-card p-3 p-md-4 rounded-4 bg-white border border-light-subtle shadow-sm text-center h-100 position-relative overflow-hidden d-flex flex-column justify-content-between">
                    <div class="stat-icon-bg text-success-subtle">
                        <i class="bi bi-geo-alt-fill"></i>
                    </div>
                    <div>
                        <div class="stat-number fw-extrabold text-success mb-1">
                            {{ number_format($stats['total_tanah'] ?? 0, 0, ',', '.') }}
                        </div>
                        <div class="stat-label text-secondary fw-semibold small">
                            Aset Tanah
                        </div>
                    </div>
                    <div>
                        <span class="badge bg-success-subtle text-success mt-2 rounded-pill small px-2 py-0" style="font-size: 0.72rem;">
                            SIPAT
                        </span>
                    </div>
                </div>
            </div>

            <!-- Arsip Tersedia -->
            <div class="col-6 col-md-3">
                <div class="stat-card p-3 p-md-4 rounded-4 bg-white border border-light-subtle shadow-sm text-center h-100 position-relative overflow-hidden d-flex flex-column justify-content-between">
                    <div class="stat-icon-bg text-warning-subtle">
                        <i class="bi bi-archive-fill"></i>
                    </div>
                    <div>
                        <div class="stat-number fw-extrabold text-warning-emphasis mb-1">
                            {{ number_format($stats['total_arsip'] ?? 0, 0, ',', '.') }}
                        </div>
                        <div class="stat-label text-secondary fw-semibold small">
                            Arsip Tersedia
                        </div>
                    </div>
                    <div>
                        <span class="badge bg-amber-subtle text-dark mt-2 rounded-pill small px-2 py-0" style="font-size: 0.72rem;">
                            EARSIP / eLABEL
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
