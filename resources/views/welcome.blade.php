<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>E-RANDIS | Sistem Monitoring Kendaraan Dinas</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.ico') }}">
    
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
</head>
<body data-bs-spy="scroll" data-bs-target="#navbar-main">

    <!-- Navbar -->
    <nav id="navbar-main" class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <!-- Logo Replikasi Persis Referensi -->
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ url('/') }}">
                @php
                    $siteLogo = $settings['site_logo'];
                    $siteName = $settings['site_name'];
                @endphp
                
                @if($siteLogo)
                    <img src="{{ \App\Models\Setting::imageUrl($siteLogo) }}" alt="Logo" class="bg-white rounded-circle p-1 navbar-brand-logo">
                @else
                    <div class="bg-white rounded-circle p-1 d-flex align-items-center justify-content-center navbar-brand-logo">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 120">
                            <!-- Shield outline -->
                            <path d="M50 0 L90 20 V70 C90 95 50 120 50 120 C50 120 10 95 10 70 V20 Z" fill="#15803d" stroke="#facc15" stroke-width="6"/>
                            <!-- Inner elements to match government crest look -->
                            <circle cx="50" cy="30" r="8" fill="#facc15"/>
                            <path d="M30 65 C40 50 60 50 70 65 Z" fill="#facc15"/>
                            <path d="M40 85 L50 70 L60 85 Z" fill="#ffffff"/>
                        </svg>
                    </div>
                @endif
                <span class="fw-bold text-white fs-6" style="letter-spacing: 0.05em;">{{ $siteName }}</span>
            </a>
            <button class="navbar-toggler shadow-none border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="bi bi-list fs-1"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto fw-medium gap-lg-4 align-items-center">
                    <li class="nav-item position-relative">
                        <a class="nav-link active pb-1" href="#hero-section">Beranda</a>
                        <!-- Active line indicator exactly under text as shown in image -->
                        <div class="position-absolute bottom-0 start-0 w-100 bg-primary" style="height: 3px; border-radius: 2px;"></div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link pb-1" href="#search-section">Cek Kendaraan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link pb-1" href="{{ route('login') }}">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link pb-1" href="#footer">Kontak</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="hero-section" style="
        @php
            $heroBg = $settings['hero_bg_image'];
            $bgUrl = \App\Models\Setting::imageUrl($heroBg);
        @endphp
        background: linear-gradient(rgba(30, 64, 175, 0.85), rgba(30, 58, 138, 0.95)), url('{{ $bgUrl }}');
        background-size: cover;
        background-position: center;
    ">
        <div class="container position-relative z-1">
            <div class="row align-items-center pt-3 pt-lg-0">
                <div class="col-lg-7 mb-5 mb-lg-0 pe-lg-4">
                    <!-- Judul 1 baris persis seperti referensi -->
                    <h1 class="mb-4 fw-bold text-white lh-sm hero-title">{{ $settings['hero_title'] }}</h1>
                    <p class="lead mb-5 text-white opacity-90 fw-normal hero-subtitle">
                        {{ $settings['hero_subtitle'] }}
                    </p>
                    <div class="d-flex flex-column flex-sm-row justify-content-lg-start justify-content-center gap-3">
                        <a href="#search-section" class="btn btn-primary btn-premium-glow px-4 py-2 fs-6 fw-semibold">Cek Kendaraan</a>
                        <a href="#feature-section" class="btn btn-outline-white px-4 py-2 fs-6 fw-semibold">Pelajari Sistem</a>
                    </div>
                </div>
                <div class="col-lg-5 text-center text-lg-end mt-4 mt-lg-0">
                    <div class="hero-image-wrapper">
                        @php
                            $heroImage = $settings['hero_image'];
                        @endphp
                        <img src="{{ \App\Models\Setting::imageUrl($heroImage) }}" alt="Monitoring Illustration" style="max-height: 400px; width: auto;">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Hero Curve/Wave persis referensi (melengkung ke bawah di tengah) -->
        <div class="hero-curve">
            <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0,20 C450,110 990,110 1440,20 V120 H0 Z" fill="#ffffff"/>
            </svg>
        </div>
    </section>

    <!-- Search Section -->
    <section id="search-section" class="search-section mt-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="text-center mb-4 mt-3">
                        <!-- Judul dengan garis bawah tipis persis referensi -->
                         
                        <h3 class="fw-bold text-navy mb-2">Cek Status Kendaraan Anda</h3>
                        <div class="mx-auto" style="width: 240px; height: 1px; background-color: #cbd5e1;"></div>
                    </div>
                    
                    <div class="search-card border border-light-subtle">
                        <form id="vehicleSearchForm" action="{{ route('landing') }}" method="GET" data-search-url="{{ route('landing.vehicle-search') }}">
                            <div class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                                <div class="flex-grow-1">
                                    <input type="text" name="q" class="form-control form-control-lg border py-3 px-4 shadow-none fs-6" placeholder="Masukkan Nomor Polisi" value="{{ $query ?? '' }}" required style="text-transform: uppercase;">
                                </div>
                                <div>
                                    <button class="btn btn-primary btn-premium-glow px-5 py-3 fw-bold h-100 w-100" type="submit" style="background-color: #0256c4;">Cari</button>
                                </div>
                            </div>
                            <div class="form-text mt-2 text-secondary text-start ps-1 small">
                                Contoh: {{ $query ?? 'DD 1234 XX' }}
                            </div>
                        </form>

                    </div>
                    
                    <div class="text-center mt-4 text-secondary small">
                        Cek status kendaraan, pengguna, dan informasi penggunaannya dengan mudah.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="vehicleSearchResultModal" tabindex="-1" aria-labelledby="vehicleSearchResultModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div id="vehicleSearchModalHeader" class="modal-header border-0 bg-primary text-white px-4 py-3">
                    <div>
                        <div id="vehicleSearchModalEyebrow" class="small text-white-50 mb-1">Hasil Pencarian</div>
                        <h5 class="modal-title fw-bold" id="vehicleSearchResultModalLabel">Memuat...</h5>
                    </div>
                    <button type="button" id="vehicleSearchModalClose" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="vehicleSearchModalBody">
                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4 pb-3 border-bottom">
                            <div>
                                <div class="skeleton-block skeleton-shimmer title"></div>
                                <div class="skeleton-block skeleton-shimmer" style="width: 150px; height: 16px;"></div>
                            </div>
                            <div>
                                <div class="skeleton-block skeleton-shimmer badge"></div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="skeleton-block skeleton-shimmer box"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="skeleton-block skeleton-shimmer box"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="skeleton-block skeleton-shimmer box"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-primary px-4 rounded-3 fw-semibold" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <section id="features" class="py-5 animate-on-scroll bg-white" style="margin-top: 4rem;">
        <div class="container py-4">
            <!-- Judul dilewati garis tengah persis referensi -->
            <div class="position-relative text-center mb-5">
                <hr class="position-absolute w-100 top-50 start-0 translate-middle-y m-0" style="border-color: #cbd5e1; z-index: 0;">
                <h3 class="fw-bold d-inline-block bg-white px-4 position-relative text-navy m-0" style="z-index: 1;">Features</h3>
            </div>
            
            <div class="row g-4 justify-content-center">
                <div class="col-md-6 col-lg-3 text-center">
                    <div class="feature-card border border-light-subtle rounded-4 p-4 h-100 bg-white shadow-sm">
                        <i class="bi bi-car-front-fill text-primary display-5 mb-3 d-block" style="color: #1e40af;"></i>
                        <h6 class="fw-bold mb-3 text-navy">Monitoring Kendaraan</h6>
                        <!-- Garis pemisah kecil di dalam card persis referensi -->
                        <hr class="mx-auto my-2" style="width: 40px; border-color: #e2e8f0;">
                        <p class="text-secondary small mt-3 mb-0" style="font-size: 0.85rem;">Pantau kondisi kendaraan secara real-time.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 text-center">
                    <div class="feature-card border border-light-subtle rounded-4 p-4 h-100 bg-white shadow-sm">
                        <i class="bi bi-clipboard-check-fill text-primary display-5 mb-3 d-block" style="color: #1e40af;"></i>
                        <h6 class="fw-bold mb-3 text-navy">Status Penggunaan</h6>
                        <hr class="mx-auto my-2" style="width: 40px; border-color: #e2e8f0;">
                        <p class="text-secondary small mt-3 mb-0" style="font-size: 0.85rem;">Lihat status aktif, dipinjam, atau maintenance</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 text-center">
                    <div class="feature-card border border-light-subtle rounded-4 p-4 h-100 bg-white shadow-sm">
                        <i class="bi bi-person-check-fill text-primary display-5 mb-3 d-block" style="color: #1e40af;"></i>
                        <h6 class="fw-bold mb-3 text-navy">Data Pengguna</h6>
                        <hr class="mx-auto my-2" style="width: 40px; border-color: #e2e8f0;">
                        <p class="text-secondary small mt-3 mb-0" style="font-size: 0.85rem;">Informasi lengkap pengguna kendaraan</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 text-center">
                    <div class="feature-card border border-light-subtle rounded-4 p-4 h-100 bg-white shadow-sm">
                        <i class="bi bi-clock-history text-primary display-5 mb-3 d-block" style="color: #1e40af;"></i>
                        <h6 class="fw-bold mb-3 text-navy">Riwayat Kendaraan</h6>
                        <hr class="mx-auto my-2" style="width: 40px; border-color: #e2e8f0;">
                        <p class="text-secondary small mt-3 mb-0" style="font-size: 0.85rem;">Catatan lengkap riwayat penggunaan</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5 bg-light animate-on-scroll">
        <div class="container py-4">
            <!-- Judul dilewati garis tengah persis referensi -->
            <div class="position-relative text-center mb-4">
                <hr class="position-absolute w-100 top-50 start-0 translate-middle-y m-0" style="border-color: #e2e8f0; z-index: 0;">
                <h3 class="fw-bold d-inline-block bg-white px-4 position-relative text-navy m-0" style="z-index: 1;">Tentang E-RANDIS</h3>
            </div>
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <p class="text-secondary small lh-base m-0" style="font-size: 0.95rem;">
                        Sistem untuk transparansi dan efisiensi pengelolaan kendaraan dinas di lingkungan pemerintah daerah. Memastikan penggunaan kendaraan lebih aman dan terpantau.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="footer" class="py-5 bg-white border-top border-light-subtle">
        <div class="container text-center pt-2">
            <!-- Logo Replikasi Persis Referensi di Footer -->
            <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                @if($settings['site_logo'])
                    <img src="{{ \App\Models\Setting::imageUrl($settings['site_logo']) }}" alt="Logo" class="bg-white rounded-circle p-1" style="height: 30px; width: 30px; object-fit: contain; border: 1px solid #e2e8f0;">
                @else
                    <div class="bg-white rounded-circle p-1 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; border: 1px solid #e2e8f0;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 120" style="width: 18px; height: 22px;">
                            <path d="M50 0 L90 20 V70 C90 95 50 120 50 120 C50 120 10 95 10 70 V20 Z" fill="#15803d" stroke="#facc15" stroke-width="6"/>
                            <circle cx="50" cy="30" r="8" fill="#facc15"/>
                            <path d="M30 65 C40 50 60 50 70 65 Z" fill="#facc15"/>
                            <path d="M40 85 L50 70 L60 85 Z" fill="#ffffff"/>
                        </svg>
                    </div>
                @endif
                <span class="fw-bold text-navy fs-6 letter-spacing-1">{{ $settings['site_name'] }}</span>
            </div>
            
            <!-- Garis pemisah di bawah logo persis referensi -->
            <hr class="mx-auto mb-4" style="width: 100%; max-width: 900px; border-color: #e2e8f0;">
            
            <div class="text-secondary small mb-2" style="font-size: 0.8rem;">
                &copy; {{ date('Y') }} Pemerintah Daerah. All Rights Reserved.
            </div>
            <div class="text-secondary small fw-medium" style="font-size: 0.8rem;">
                Kontak: <span class="text-navy">admin@example.com</span>
            </div>
        </div>
    </footer>

    <script>
        // Navbar Scroll Effect
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                document.querySelector('.navbar').classList.add('scrolled');
            } else {
                document.querySelector('.navbar').classList.remove('scrolled');
            }
        });

        window.addEventListener('DOMContentLoaded', function() {
            const searchForm = document.getElementById('vehicleSearchForm');
            const resultModalElement = document.getElementById('vehicleSearchResultModal');
            const modalHeader = document.getElementById('vehicleSearchModalHeader');
            const modalEyebrow = document.getElementById('vehicleSearchModalEyebrow');
            const modalTitle = document.getElementById('vehicleSearchResultModalLabel');
            const modalBody = document.getElementById('vehicleSearchModalBody');
            const modalClose = document.getElementById('vehicleSearchModalClose');

            if (!searchForm || !resultModalElement || !window.bootstrap) {
                return;
            }

            const resultModal = new bootstrap.Modal(resultModalElement);
            const escapeHtml = function(value) {
                return String(value ?? '').replace(/[&<>"']/g, function(character) {
                    return {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    }[character];
                });
            };

            const setHeaderState = function(found) {
                modalHeader.className = found
                    ? 'modal-header border-0 bg-primary text-white px-4 py-3'
                    : 'modal-header border-0 bg-warning-subtle px-4 py-3';
                modalEyebrow.className = found ? 'small text-white-50 mb-1' : 'small text-secondary mb-1';
                modalClose.className = found ? 'btn-close btn-close-white shadow-none' : 'btn-close shadow-none';
            };

            searchForm.addEventListener('submit', async function(event) {
                event.preventDefault();

                const formData = new FormData(searchForm);
                const query = formData.get('q');
                const searchUrl = new URL(searchForm.dataset.searchUrl, window.location.origin);
                searchUrl.searchParams.set('q', query);

                setHeaderState(true);
                modalTitle.textContent = 'Memuat...';
                modalBody.innerHTML = `
                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4 pb-3 border-bottom">
                            <div>
                                <div class="skeleton-block skeleton-shimmer title"></div>
                                <div class="skeleton-block skeleton-shimmer" style="width: 150px; height: 16px;"></div>
                            </div>
                            <div>
                                <div class="skeleton-block skeleton-shimmer badge"></div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="skeleton-block skeleton-shimmer box"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="skeleton-block skeleton-shimmer box"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="skeleton-block skeleton-shimmer box"></div>
                            </div>
                        </div>
                    </div>
                `;
                resultModal.show();

                try {
                    const response = await fetch(searchUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('Search request failed');
                    }

                    const data = await response.json();

                    if (data.found && data.vehicle) {
                        setHeaderState(true);
                        modalTitle.textContent = data.vehicle.no_polisi;
                        const renderLandingPhotos = (photos) => {
                            if (!photos || photos.length === 0) return '';
                            
                            let html = `
                                <div class="mt-4 pt-3 border-top">
                                    <div class="text-secondary small mb-3 fw-bold text-uppercase">Foto Fisik Kendaraan</div>
                                    <div class="row g-2">
                            `;
                            
                            photos.forEach(path => {
                                const url = `/storage/${path}`;
                                html += `
                                    <div class="col-6 col-md-3">
                                        <a href="${url}" target="_blank">
                                            <img src="${url}" class="img-fluid rounded-3 border" style="height: 100px; width: 100%; object-fit: cover;" alt="Foto Kendaraan">
                                        </a>
                                    </div>
                                `;
                            });
                            
                            html += '</div></div>';
                            return html;
                        };

                        modalBody.innerHTML = `
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4 pb-3 border-bottom">
                                <div>
                                    <div class="text-secondary small mb-1">Nama Kendaraan</div>
                                    <div class="fw-bold fs-5 text-navy">${escapeHtml(data.vehicle.nama)}</div>
                                </div>
                                <div>
                                    <span class="badge bg-success px-3 py-2 rounded-pill fw-medium">${escapeHtml(data.vehicle.status)}</span>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded-3 h-100">
                                        <div class="text-secondary small mb-1">OPD / Instansi</div>
                                        <div class="fw-bold text-dark">${escapeHtml(data.vehicle.opd)}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded-3 h-100">
                                        <div class="text-secondary small mb-1">Pemegang</div>
                                        <div class="fw-bold text-dark">${escapeHtml(data.vehicle.pemegang)}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded-3 h-100">
                                        <div class="text-secondary small mb-1">Kondisi Fisik</div>
                                        <div class="fw-bold text-dark">${escapeHtml(data.vehicle.kondisi)}</div>
                                    </div>
                                </div>
                            </div>

                            ${renderLandingPhotos(data.vehicle.foto_kendaraan)}
                        `;
                    } else {
                        setHeaderState(false);
                        modalTitle.textContent = 'Data tidak ditemukan';
                        modalBody.innerHTML = `
                            <div class="d-flex align-items-start gap-3">
                                <i class="bi bi-exclamation-triangle-fill fs-3 text-warning"></i>
                                <div>
                                    <div class="fw-bold text-dark mb-1">Kendaraan belum terdaftar.</div>
                                    <p class="text-secondary mb-0">Kendaraan dengan kata kunci "${escapeHtml(data.query || query)}" belum ditemukan dalam database.</p>
                                </div>
                            </div>
                        `;
                    }
                } catch (error) {
                    setHeaderState(false);
                    modalTitle.textContent = 'Pencarian gagal';
                    modalBody.innerHTML = `
                        <div class="d-flex align-items-start gap-3">
                            <i class="bi bi-exclamation-triangle-fill fs-3 text-warning"></i>
                            <div>
                                <div class="fw-bold text-dark mb-1">Terjadi kendala saat mencari data.</div>
                                <p class="text-secondary mb-0">Silakan coba beberapa saat lagi.</p>
                            </div>
                        </div>
                    `;
                }
            });
        });
    </script>
</body>
</html>
