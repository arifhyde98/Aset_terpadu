<!-- Navbar Header SIPAT Terpadu -->
<nav id="navbar-main" class="navbar navbar-expand-lg fixed-top landing-navbar shadow-sm">
    <div class="container">
        <!-- Brand Kiri: Logo Daerah (Bulat) & Teks Instansi -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ url('/') }}">
            @php
                $siteLogo = $settings['site_logo'] ?? null;
                $siteLogoRight = $settings['site_logo_right'] ?? null;
                $siteName = $settings['site_name'] ?? 'SIPAT TERPADU';
                $siteSubtitle = $settings['site_subtitle'] ?? 'Sistem Informasi Aset Pemerintah Daerah';
            @endphp

            <!-- 1. Logo Kiri (Lambang Daerah - Bulat Rapi) -->
            @if($siteLogo)
                <img src="{{ \App\Models\Setting::imageUrl($siteLogo) }}" alt="Logo Daerah" class="bg-white rounded-circle p-1 navbar-brand-logo shadow-sm" style="width: 44px; height: 44px; object-fit: contain;">
            @else
                <div class="bg-white rounded-circle p-1 d-flex align-items-center justify-content-center navbar-brand-logo shadow-sm" style="width: 44px; height: 44px;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 120" style="width: 24px; height: 28px;">
                        <path d="M50 0 L90 20 V70 C90 95 50 120 50 120 C50 120 10 95 10 70 V20 Z" fill="#15803D" stroke="#FACC15" stroke-width="5"/>
                        <circle cx="50" cy="32" r="8" fill="#FACC15"/>
                        <path d="M30 65 C40 50 60 50 70 65 Z" fill="#FACC15"/>
                        <path d="M40 85 L50 70 L60 85 Z" fill="#FFFFFF"/>
                    </svg>
                </div>
            @endif

            <!-- Teks Nama Aplikasi & Subtitle -->
            <div class="d-flex flex-column text-start">
                <span class="fw-bold text-white fs-6 tracking-wide lh-1">{{ $siteName }}</span>
                <span class="text-white-50 small fw-normal" style="font-size: 0.72rem; letter-spacing: 0.02em;">{{ $siteSubtitle }}</span>
            </div>
        </a>

        <!-- Mobile: Logo Kanan Ringkas & Hamburger Toggler -->
        <div class="d-flex align-items-center gap-2 d-lg-none">
            @if($siteLogoRight)
                <img src="{{ \App\Models\Setting::imageUrl($siteLogoRight) }}" alt="Logo Instansi" style="height: 36px; max-width: 110px; width: auto; object-fit: contain;">
            @endif
            <button class="navbar-toggler shadow-none border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="bi bi-list fs-1 text-white"></span>
            </button>
        </div>

        <!-- Nav Items & Action Kanan -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto fw-medium gap-lg-3 align-items-center">
                <li class="nav-item">
                    <a class="nav-link text-white-90 active" href="#hero-section">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white-90" href="#search-section">Cari Aset</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white-90" href="#quick-services">Layanan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white-90" href="#statistics-section">Statistik</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white-90" href="#about-section">Tentang</a>
                </li>
                <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                    <a class="btn btn-login-header btn-sm px-4 py-2 rounded-pill fw-bold d-flex align-items-center gap-1 shadow-sm" href="{{ route('login') }}">
                        <i class="bi bi-lock-fill small"></i>
                        <span>Login Petugas</span>
                    </a>
                </li>

                <!-- 2. Logo Kanan di Ujung Kanan Tampilan Samping Login Petugas (Besar, Jelas, Landscape) -->
                @if($siteLogoRight)
                    <li class="nav-item d-none d-lg-flex align-items-center ps-lg-3 ms-lg-2 border-start border-white border-opacity-25">
                        <img src="{{ \App\Models\Setting::imageUrl($siteLogoRight) }}" alt="Logo Instansi" class="navbar-logo-far-right" style="height: 50px; max-width: 190px; width: auto; object-fit: contain;">
                    </li>
                @else
                    <li class="nav-item d-none d-lg-flex align-items-center ps-lg-3 ms-lg-2 border-start border-white border-opacity-25">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo Instansi" class="navbar-logo-far-right" style="height: 50px; max-width: 190px; width: auto; object-fit: contain;" onerror="this.parentElement.style.display='none'">
                    </li>
                @endif
            </ul>
        </div>
    </div>
</nav>
