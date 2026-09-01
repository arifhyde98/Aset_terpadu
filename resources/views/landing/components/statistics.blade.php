<!-- Statistics Section with Animated Counters & Glowing Cards -->
<section id="statistics-section" class="landing-statistics-section py-5 bg-light-subtle border-top border-bottom border-light-subtle">
    <div class="container">
        <!-- Section Header -->
        <div class="text-center mb-5">
            <span class="text-uppercase tracking-wider text-primary fw-bold small">Data Terpadu</span>
            <h3 class="fw-bold text-navy mt-1 mb-2">Statistik Data Aset Daerah</h3>
            <p class="text-secondary small mx-auto mb-0" style="max-width: 580px;">
                Rekapitulasi data aset milik Pemerintah Daerah yang telah terdata dan termonitor dalam sistem SIPAT Terpadu.
            </p>
        </div>

        <!-- 4 Stats Cards (Total Aset, Kendaraan, Tanah, Dokumen Arsip) -->
        <div class="row g-3 g-lg-4 justify-content-center">
            <!-- 1. Total Aset (Purple Gradient Accent) -->
            <div class="col-6 col-md-3">
                <div class="stat-card stat-total p-3 p-md-4 rounded-4 bg-white border border-light-subtle shadow-sm text-center h-100 position-relative overflow-hidden d-flex flex-column justify-content-between">
                    <div class="stat-icon-bg text-primary">
                        <i class="bi bi-layers-fill"></i>
                    </div>
                    <div>
                        <div class="stat-number fw-extrabold text-navy mb-1 counter-animate" data-target="{{ $stats['total_aset'] ?? 0 }}">
                            0
                        </div>
                        <div class="stat-label text-secondary fw-semibold small">
                            Total Aset
                        </div>
                    </div>
                    <div>
                        <span class="badge bg-primary text-white mt-2 rounded-pill small px-3 py-1 fw-medium shadow-sm" style="font-size: 0.72rem;">
                            Tanah & Kendaraan
                        </span>
                    </div>
                </div>
            </div>

            <!-- 2. Kendaraan (Blue Gradient Accent) -->
            <div class="col-6 col-md-3">
                <div class="stat-card stat-vehicle p-3 p-md-4 rounded-4 bg-white border border-light-subtle shadow-sm text-center h-100 position-relative overflow-hidden d-flex flex-column justify-content-between">
                    <div class="stat-icon-bg text-info">
                        <i class="bi bi-car-front-fill"></i>
                    </div>
                    <div>
                        <div class="stat-number fw-extrabold text-primary mb-1 counter-animate" data-target="{{ $stats['total_kendaraan'] ?? 0 }}">
                            0
                        </div>
                        <div class="stat-label text-secondary fw-semibold small">
                            Kendaraan
                        </div>
                    </div>
                    <div>
                        <span class="badge bg-info text-dark mt-2 rounded-pill small px-3 py-1 fw-semibold shadow-sm" style="font-size: 0.72rem;">
                            E-RANDIS
                        </span>
                    </div>
                </div>
            </div>

            <!-- 3. Tanah (Emerald Gradient Accent) -->
            <div class="col-6 col-md-3">
                <div class="stat-card stat-land p-3 p-md-4 rounded-4 bg-white border border-light-subtle shadow-sm text-center h-100 position-relative overflow-hidden d-flex flex-column justify-content-between">
                    <div class="stat-icon-bg text-success">
                        <i class="bi bi-geo-alt-fill"></i>
                    </div>
                    <div>
                        <div class="stat-number fw-extrabold text-success mb-1 counter-animate" data-target="{{ $stats['total_tanah'] ?? 0 }}">
                            0
                        </div>
                        <div class="stat-label text-secondary fw-semibold small">
                            Tanah
                        </div>
                    </div>
                    <div>
                        <span class="badge bg-success text-white mt-2 rounded-pill small px-3 py-1 fw-medium shadow-sm" style="font-size: 0.72rem;">
                            SIPAT
                        </span>
                    </div>
                </div>
            </div>

            <!-- 4. Dokumen Arsip (Amber Gradient Accent) -->
            <div class="col-6 col-md-3">
                <div class="stat-card stat-archive p-3 p-md-4 rounded-4 bg-white border border-light-subtle shadow-sm text-center h-100 position-relative overflow-hidden d-flex flex-column justify-content-between">
                    <div class="stat-icon-bg text-warning">
                        <i class="bi bi-archive-fill"></i>
                    </div>
                    <div>
                        <div class="stat-number fw-extrabold text-warning-emphasis mb-1 counter-animate" data-target="{{ $stats['total_arsip'] ?? 0 }}">
                            0
                        </div>
                        <div class="stat-label text-secondary fw-semibold small">
                            Dokumen Arsip
                        </div>
                    </div>
                    <div>
                        <span class="badge bg-amber text-dark mt-2 rounded-pill small px-3 py-1 fw-bold shadow-sm" style="font-size: 0.72rem;">
                            Tersedia di Box
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
