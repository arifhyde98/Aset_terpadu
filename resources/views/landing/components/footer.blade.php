<!-- Footer Section -->
<footer id="footer" class="landing-footer py-5 bg-white border-top border-light-subtle">
    <div class="container">
        <div class="row g-4 justify-content-between mb-4">
            <!-- Brand Column -->
            <div class="col-lg-5">
                <div class="d-flex align-items-center gap-2 mb-3">
                    @php
                        $siteLogo = $settings['site_logo'] ?? null;
                        $siteLogoRight = $settings['site_logo_right'] ?? null;
                        $siteName = $settings['site_name'] ?? 'SIPAT TERPADU';
                        $siteSubtitle = $settings['site_subtitle'] ?? 'Sistem Informasi Aset Pemerintah Daerah';
                    @endphp

                    <!-- Logo Kiri -->
                    @if($siteLogo)
                        <img src="{{ \App\Models\Setting::imageUrl($siteLogo) }}" alt="Logo Kiri" class="bg-white rounded-circle p-1" style="height: 36px; width: 36px; object-fit: contain; border: 1px solid #e2e8f0;">
                    @else
                        <div class="bg-navy rounded-circle p-1 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="bi bi-shield-shaded text-amber fs-5"></i>
                        </div>
                    @endif

                    <!-- Logo Kanan -->
                    @if($siteLogoRight)
                        <img src="{{ \App\Models\Setting::imageUrl($siteLogoRight) }}" alt="Logo Kanan" class="bg-white rounded-circle p-1" style="height: 36px; width: 36px; object-fit: contain; border: 1px solid #e2e8f0;">
                    @endif

                    <div>
                        <span class="fw-bold text-navy fs-6">{{ $siteName }}</span>
                        <div class="small text-secondary" style="font-size: 0.75rem;">{{ $siteSubtitle }}</div>
                    </div>
                </div>
                <p class="text-secondary small mb-3 lh-base" style="max-width: 420px;">
                    Portal terpadu penyediaan informasi, pelacakan, dan transparansi pengelolaan aset Pemerintah Daerah secara akuntabel, real-time, dan mudah diakses publik.
                </p>
            </div>

            <!-- Quick Links -->
            <div class="col-6 col-md-3 col-lg-2">
                <h6 class="fw-bold text-navy mb-3 small text-uppercase tracking-wider">Modul Terpadu</h6>
                <ul class="list-unstyled small text-secondary d-flex flex-column gap-2 mb-0">
                    <li><a href="#search-section" class="text-decoration-none text-secondary hover-navy quick-service-link" data-target-tab="vehicle">Kendaraan Dinas</a></li>
                    <li><a href="#search-section" class="text-decoration-none text-secondary hover-navy quick-service-link" data-target-tab="land">Sertifikat Tanah</a></li>
                    <li><a href="#search-section" class="text-decoration-none text-secondary hover-navy quick-service-link" data-target-tab="archive">Arsip Dokumen</a></li>
                </ul>
            </div>

            <!-- Portal Links -->
            <div class="col-6 col-md-3 col-lg-2">
                <h6 class="fw-bold text-navy mb-3 small text-uppercase tracking-wider">Akses Portal</h6>
                <ul class="list-unstyled small text-secondary d-flex flex-column gap-2 mb-0">
                    <li><a href="#hero-section" class="text-decoration-none text-secondary hover-navy">Beranda</a></li>
                    <li><a href="#statistics-section" class="text-decoration-none text-secondary hover-navy">Statistik Aset</a></li>
                    <li><a href="#about-section" class="text-decoration-none text-secondary hover-navy">Tentang Sistem</a></li>
                    <li><a href="{{ route('login') }}" class="text-decoration-none text-primary fw-semibold">Login Petugas &rarr;</a></li>
                </ul>
            </div>

            <!-- Kontak & Instansi -->
            <div class="col-md-6 col-lg-3">
                <h6 class="fw-bold text-navy mb-3 small text-uppercase tracking-wider">Pemerintah Daerah</h6>
                <p class="text-secondary small mb-2">
                    Badan Pengelola Keuangan dan Aset Daerah (BPKAD) Bidang Pengelolaan Aset Daerah.
                </p>
                <div class="small text-secondary">
                    <i class="bi bi-shield-lock-fill text-success me-1"></i> Sistem Keamanan Terverifikasi
                </div>
            </div>
        </div>

        <hr class="border-light-subtle my-4">

        <!-- Copyright -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-secondary">
            <div>
                &copy; {{ date('Y') }} <strong>{{ $siteName }}</strong> - Pemerintah Daerah. Hak Cipta Dilindungi Undang-Undang.
            </div>
            <div>
                Informasi Publik Aset Daerah &bull; Versi 2.0 Terpadu
            </div>
        </div>
    </div>
</footer>
