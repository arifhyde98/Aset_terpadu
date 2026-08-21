@php
    $isSipatActive = request()->routeIs('sipat.*') || (request()->routeIs('master.*') && !request()->routeIs('master.opd-mapping.*')) || (request()->routeIs('activities.*') && request('module') === 'sipat');
    $isErandisActive = request()->routeIs('erandis.*', 'vehicles.*', 'vehicle-types.*', 'opds.*', 'master.opd-mapping.*', 'maintenance.*', 'reports.*') || (request()->routeIs('activities.*') && request('module') !== 'sipat');
    $isElabelActive = request()->routeIs('elabel.*');
@endphp
<!-- Bottom Navigation for Mobile -->
<nav class="bottom-nav d-md-none" aria-label="Mobile Navigation">
    <div class="bottom-nav-container">
        <!-- 1. Home / Terpadu -->
        <a href="{{ route('home') }}" class="bottom-nav-item {{ request()->routeIs('home') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2{{ request()->routeIs('home') ? '-fill' : '' }}"></i>
            <span>Home</span>
        </a>
        
        <!-- 2. SIPAT (Aset Tanah & Penggunaan Kantor) -->
        <a href="{{ route('sipat.aset.index') }}" class="bottom-nav-item nav-item-sipat {{ $isSipatActive ? 'active' : '' }}">
            <i class="bi bi-geo-alt{{ $isSipatActive ? '-fill' : '' }}"></i>
            <span>SIPAT</span>
        </a>
        
        <!-- 3. eRANDIS (Kendaraan Dinas) -->
        <a href="{{ route('vehicles.index') }}" class="bottom-nav-item nav-item-erandis {{ $isErandisActive ? 'active' : '' }}">
            <i class="bi bi-car-front{{ $isErandisActive ? '-fill' : '' }}"></i>
            <span>eRANDIS</span>
        </a>
        
        <!-- 4. eLABEL (Digital Arsip & Box) -->
        <a href="{{ route('elabel.dashboard') }}" class="bottom-nav-item nav-item-elabel {{ $isElabelActive ? 'active' : '' }}">
            <i class="bi bi-archive{{ $isElabelActive ? '-fill' : '' }}"></i>
            <span>eLABEL</span>
        </a>
        
        <!-- 5. Menu Lengkap (Offcanvas) -->
        <a href="javascript:void(0);" class="bottom-nav-item" id="mobileMenuToggle" data-bs-toggle="offcanvas" data-bs-target="#mobileMenuOffcanvas" aria-controls="mobileMenuOffcanvas">
            <i class="bi bi-grid-3x3-gap-fill"></i>
            <span>Menu</span>
        </a>
    </div>
</nav>

<!-- Offcanvas Mobile Menu (Pusat Navigasi Lengkap Seluruh Modul) -->
<div class="offcanvas offcanvas-bottom mobile-offcanvas-sheet" tabindex="-1" id="mobileMenuOffcanvas" aria-labelledby="mobileMenuOffcanvasLabel">
    <!-- Header Drawer -->
    <div class="offcanvas-header pb-2 border-bottom align-items-center">
        <div class="d-flex align-items-center gap-2">
            @php
                $mobileSiteLogo = \App\Models\Setting::get('site_logo');
            @endphp
            @if($mobileSiteLogo)
                <img src="{{ \App\Models\Setting::imageUrl($mobileSiteLogo) }}" alt="Logo" class="rounded-circle p-0.5 border" style="width: 32px; height: 32px; object-fit: contain;">
            @else
                <img src="{{ asset('images/hero-illustration.png') }}" alt="Logo" class="rounded-circle p-0.5 border" style="width: 32px; height: 32px; object-fit: contain;">
            @endif
            <div>
                <h6 class="offcanvas-title fw-bold text-navy mb-0" id="mobileMenuOffcanvasLabel">SIPAT TERPADU</h6>
                <small class="text-secondary" style="font-size: 0.7rem;">Navigasi Sistem Aset Daerah</small>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body p-3">
        <!-- User Info Bar -->
        @auth
        <div class="mobile-user-card p-2.5 rounded-3 mb-3 bg-light border d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="rounded-circle border" style="width: 38px; height: 38px; object-fit: cover;">
                <div class="lh-sm">
                    <div class="fw-bold text-dark small">{{ Auth::user()->name }}</div>
                    <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold" style="font-size: 0.65rem;">{{ Auth::user()->role->label() }}</span>
                </div>
            </div>
            <a href="{{ route('profile.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-1 text-nowrap" style="font-size: 0.75rem;">
                <i class="bi bi-person me-1"></i>Profil
            </a>
        </div>
        @endauth

        <!-- 1. HUB 4 DASHBOARD (Quick Switch) -->
        <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-bold text-secondary text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                    <i class="bi bi-speedometer2 me-1 text-primary"></i> Pilihan Dashboard
                </span>
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <a href="{{ route('home') }}" class="mobile-dash-btn p-2 rounded-3 text-decoration-none d-flex align-items-center gap-2 border {{ request()->routeIs('home') ? 'active-terpadu' : '' }}">
                        <div class="dash-icon-box bg-primary bg-opacity-10 text-primary rounded-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                            <i class="bi bi-grid-1x2-fill"></i>
                        </div>
                        <div class="lh-1 text-truncate">
                            <span class="d-block fw-bold small text-dark">Terpadu</span>
                            <small class="text-secondary" style="font-size: 0.65rem;">Ringkasan</small>
                        </div>
                    </a>
                </div>
                <div class="col-6">
                    <a href="{{ route('sipat.dashboard') }}" class="mobile-dash-btn p-2 rounded-3 text-decoration-none d-flex align-items-center gap-2 border {{ request()->routeIs('sipat.dashboard') ? 'active-sipat' : '' }}">
                        <div class="dash-icon-box bg-info bg-opacity-10 text-info rounded-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                        <div class="lh-1 text-truncate">
                            <span class="d-block fw-bold small text-dark">SIPAT</span>
                            <small class="text-secondary" style="font-size: 0.65rem;">Aset Tanah</small>
                        </div>
                    </a>
                </div>
                <div class="col-6">
                    <a href="{{ route('erandis.dashboard') }}" class="mobile-dash-btn p-2 rounded-3 text-decoration-none d-flex align-items-center gap-2 border {{ request()->routeIs('erandis.dashboard') ? 'active-erandis' : '' }}">
                        <div class="dash-icon-box bg-warning bg-opacity-10 text-warning rounded-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                            <i class="bi bi-car-front-fill"></i>
                        </div>
                        <div class="lh-1 text-truncate">
                            <span class="d-block fw-bold small text-dark">eRANDIS</span>
                            <small class="text-secondary" style="font-size: 0.65rem;">Kendaraan</small>
                        </div>
                    </a>
                </div>
                <div class="col-6">
                    <a href="{{ route('elabel.dashboard') }}" class="mobile-dash-btn p-2 rounded-3 text-decoration-none d-flex align-items-center gap-2 border {{ request()->routeIs('elabel.dashboard') ? 'active-elabel' : '' }}">
                        <div class="dash-icon-box bg-success bg-opacity-10 text-success rounded-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                            <i class="bi bi-box-seam-fill"></i>
                        </div>
                        <div class="lh-1 text-truncate">
                            <span class="d-block fw-bold small text-dark">eLABEL</span>
                            <small class="text-secondary" style="font-size: 0.65rem;">Arsip & Box</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <div class="mobile-menu-accordion accordion" id="mobileModulesAccordion">
            <!-- 2. MODUL SIPAT (Aset Tanah) -->
            <div class="accordion-item border rounded-3 mb-2 overflow-hidden module-item-sipat">
                <h2 class="accordion-header" id="headingMobileSipat">
                    <button class="accordion-button py-2.5 px-3 {{ $isSipatActive ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMobileSipat" aria-expanded="{{ $isSipatActive ? 'true' : 'false' }}">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary text-white rounded-pill p-1.5"><i class="bi bi-geo-alt-fill"></i></span>
                            <div class="lh-1">
                                <span class="fw-bold fs-6 text-dark d-block">MODUL SIPAT</span>
                                <small class="text-secondary" style="font-size: 0.68rem;">Aset Tanah & Penggunaan Kantor</small>
                            </div>
                        </div>
                    </button>
                </h2>
                <div id="collapseMobileSipat" class="accordion-collapse collapse {{ $isSipatActive ? 'show' : '' }}" data-bs-parent="#mobileModulesAccordion">
                    <div class="accordion-body p-2 bg-light bg-opacity-50">
                        <div class="list-group list-group-flush rounded-2 overflow-hidden border-0">
                            <!-- Aset & Peta -->
                            <div class="text-muted fw-bold px-2 pt-1 pb-1" style="font-size: 0.65rem; text-transform: uppercase;">Aset Tanah & Penggunaan</div>
                            <a href="{{ route('sipat.aset.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('sipat/aset*') ? 'active-sub-sipat' : '' }}">
                                <i class="bi bi-journal-text me-2 text-primary"></i> Data Aset Tanah
                            </a>
                            <a href="{{ route('sipat.peta.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('sipat/peta*') ? 'active-sub-sipat' : '' }}">
                                <i class="bi bi-map me-2 text-primary"></i> Peta Geografis GIS
                            </a>

                            <!-- Legalitas -->
                            <div class="text-muted fw-bold px-2 pt-2 pb-1" style="font-size: 0.65rem; text-transform: uppercase;">Legalitas & Pelaporan</div>
                            <a href="{{ route('sipat.surat.skpt') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('sipat/surat*') ? 'active-sub-sipat' : '' }}">
                                <i class="bi bi-file-earmark-word me-2 text-info"></i> Cetak Surat SKPT
                            </a>
                            <a href="{{ route('sipat.laporan.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('sipat/laporan*') ? 'active-sub-sipat' : '' }}">
                                <i class="bi bi-file-earmark-text me-2 text-info"></i> Laporan Aset Tanah
                            </a>
                            <a href="{{ route('sipat.rekonsiliasi.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('sipat/rekonsiliasi*') ? 'active-sub-sipat' : '' }}">
                                <i class="bi bi-arrow-left-right me-2 text-info"></i> Rekonsiliasi Arsip
                            </a>

                            <!-- Master Data SIPAT (Non-OPD) -->
                            @if(auth()->user()?->role !== \App\Enums\UserRole::OPD)
                                <div class="text-muted fw-bold px-2 pt-2 pb-1" style="font-size: 0.65rem; text-transform: uppercase;">Pengaturan SIPAT</div>
                                <a href="{{ route('status-proses.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('master-data/status-proses*') ? 'active-sub-sipat' : '' }}">
                                    <i class="bi bi-tags me-2 text-success"></i> Master Status Proses
                                </a>
                                <a href="{{ route('master.wilayah.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('master-data/wilayah*') ? 'active-sub-sipat' : '' }}">
                                    <i class="bi bi-geo-alt me-2 text-success"></i> Master Wilayah & Pejabat
                                </a>
                                <a href="{{ route('master.kop-settings.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('master-data/kop-surat*') ? 'active-sub-sipat' : '' }}">
                                    <i class="bi bi-file-earmark-pdf me-2 text-success"></i> KOP Surat Pemda
                                </a>
                                <a href="{{ route('master.opd-sipat.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('master-data/opd-sipat*') ? 'active-sub-sipat' : '' }}">
                                    <i class="bi bi-diagram-3 me-2 text-success"></i> OPD Instansi (SIPAT)
                                </a>
                                <a href="{{ route('master.import.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('master-data/import*') ? 'active-sub-sipat' : '' }}">
                                    <i class="bi bi-file-earmark-arrow-up me-2 text-success"></i> Import Data SIPAT
                                </a>
                                <a href="{{ route('activities.index', ['module' => 'sipat']) }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('activities*') && request('module') === 'sipat' ? 'active-sub-sipat' : '' }}">
                                    <i class="bi bi-journal-check me-2 text-success"></i> Log Aktivitas SIPAT
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. MODUL ERANDIS (Kendaraan Dinas) -->
            <div class="accordion-item border rounded-3 mb-2 overflow-hidden module-item-erandis">
                <h2 class="accordion-header" id="headingMobileErandis">
                    <button class="accordion-button py-2.5 px-3 {{ $isErandisActive ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMobileErandis" aria-expanded="{{ $isErandisActive ? 'true' : 'false' }}">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-warning text-dark rounded-pill p-1.5"><i class="bi bi-car-front-fill"></i></span>
                            <div class="lh-1">
                                <span class="fw-bold fs-6 text-dark d-block">MODUL ERANDIS</span>
                                <small class="text-secondary" style="font-size: 0.68rem;">Kendaraan Dinas & Pemeliharaan</small>
                            </div>
                        </div>
                    </button>
                </h2>
                <div id="collapseMobileErandis" class="accordion-collapse collapse {{ $isErandisActive ? 'show' : '' }}" data-bs-parent="#mobileModulesAccordion">
                    <div class="accordion-body p-2 bg-light bg-opacity-50">
                        <div class="list-group list-group-flush rounded-2 overflow-hidden border-0">
                            <a href="{{ route('vehicles.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ request()->routeIs('vehicles.*') ? 'active-sub-erandis' : '' }}">
                                <i class="bi bi-truck me-2 text-warning"></i> Data Kendaraan Dinas
                            </a>
                            <a href="{{ route('maintenance.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ request()->routeIs('maintenance.*') ? 'active-sub-erandis' : '' }}">
                                <i class="bi bi-wrench-adjustable me-2 text-info"></i> Servis & Pemeliharaan
                            </a>
                            <a href="{{ route('reports.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ request()->routeIs('reports.*') ? 'active-sub-erandis' : '' }}">
                                <i class="bi bi-graph-up me-2 text-success"></i> Laporan Kendaraan
                            </a>

                            @if(auth()->user()?->role !== \App\Enums\UserRole::OPD)
                                <div class="text-muted fw-bold px-2 pt-2 pb-1" style="font-size: 0.65rem; text-transform: uppercase;">Master Data ERANDIS</div>
                                <a href="{{ route('opds.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('opds*') ? 'active-sub-erandis' : '' }}">
                                    <i class="bi bi-building me-2 text-primary"></i> OPD / Instansi
                                </a>
                                <a href="{{ route('vehicle-types.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('vehicle-types*') ? 'active-sub-erandis' : '' }}">
                                    <i class="bi bi-grid-3x3-gap me-2 text-primary"></i> Jenis Kendaraan
                                </a>
                                @if(auth()->check() && auth()->user()?->role === \App\Enums\UserRole::SUPERADMIN)
                                    <a href="{{ route('activities.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('activities*') ? 'active-sub-erandis' : '' }}">
                                        <i class="bi bi-shield-lock me-2 text-primary"></i> Log Aktivitas Terpadu
                                    </a>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. MODUL ELABEL (Digital Arsip & Box) -->
            <div class="accordion-item border rounded-3 mb-2 overflow-hidden module-item-elabel">
                <h2 class="accordion-header" id="headingMobileElabel">
                    <button class="accordion-button py-2.5 px-3 {{ $isElabelActive ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMobileElabel" aria-expanded="{{ $isElabelActive ? 'true' : 'false' }}">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-info text-white rounded-pill p-1.5"><i class="bi bi-archive-fill"></i></span>
                            <div class="lh-1">
                                <span class="fw-bold fs-6 text-dark d-block">MODUL ELABEL</span>
                                <small class="text-secondary" style="font-size: 0.68rem;">BPKB, Sertifikat & Box Arsip</small>
                            </div>
                        </div>
                    </button>
                </h2>
                <div id="collapseMobileElabel" class="accordion-collapse collapse {{ $isElabelActive ? 'show' : '' }}" data-bs-parent="#mobileModulesAccordion">
                    <div class="accordion-body p-2 bg-light bg-opacity-50">
                        <div class="list-group list-group-flush rounded-2 overflow-hidden border-0">
                            <!-- BPKB -->
                            <div class="text-muted fw-bold px-2 pt-1 pb-1" style="font-size: 0.65rem; text-transform: uppercase;">Dokumen BPKB</div>
                            <a href="{{ route('elabel.bpkb.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('elabel/bpkb') || (Request::is('elabel/bpkb/*') && !Request::is('elabel/bpkb-deleted*')) ? 'active-sub-elabel' : '' }}">
                                <i class="bi bi-card-checklist me-2 text-primary"></i> Katalog BPKB
                            </a>
                            <a href="{{ route('elabel.bpkb-deleted.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('elabel/bpkb-deleted*') ? 'active-sub-elabel' : '' }}">
                                <i class="bi bi-box-arrow-right me-2 text-danger"></i> BPKB Keluar
                            </a>
                            <a href="{{ route('elabel.boxes.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('elabel/boxes*') ? 'active-sub-elabel' : '' }}">
                                <i class="bi bi-box-seam me-2 text-primary"></i> Box BPKB
                            </a>

                            <!-- Sertifikat -->
                            <div class="text-muted fw-bold px-2 pt-2 pb-1" style="font-size: 0.65rem; text-transform: uppercase;">Sertifikat Tanah</div>
                            <a href="{{ route('elabel.sertifikat.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('elabel/sertifikat') || (Request::is('elabel/sertifikat/*') && !Request::is('elabel/sertifikat-boxes*')) ? 'active-sub-elabel' : '' }}">
                                <i class="bi bi-patch-check-fill me-2 text-success"></i> Katalog Sertifikat
                            </a>
                            <a href="{{ route('elabel.sertifikat-boxes.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('elabel/sertifikat-boxes*') ? 'active-sub-elabel' : '' }}">
                                <i class="bi bi-archive me-2 text-success"></i> Box Sertifikat Tanah
                            </a>

                            <!-- Surat & Layanan -->
                            <div class="text-muted fw-bold px-2 pt-2 pb-1" style="font-size: 0.65rem; text-transform: uppercase;">Surat Penyerahan & Layanan</div>
                            <a href="{{ route('elabel.surat-penyerahan.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('elabel/surat-penyerahan') || (Request::is('elabel/surat-penyerahan/*') && !Request::is('elabel/surat-penyerahan-boxes*')) ? 'active-sub-elabel' : '' }}">
                                <i class="bi bi-file-earmark-text me-2 text-warning"></i> Surat Penyerahan
                            </a>
                            <a href="{{ route('elabel.surat-penyerahan-boxes.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('elabel/surat-penyerahan-boxes*') ? 'active-sub-elabel' : '' }}">
                                <i class="bi bi-folder2-open me-2 text-warning"></i> Box Surat Penyerahan
                            </a>
                            <a href="{{ route('elabel.peminjaman.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('elabel/peminjaman*') ? 'active-sub-elabel' : '' }}">
                                <i class="bi bi-clock-history me-2 text-info"></i> Request Scan & Pinjam
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. PENGATURAN SISTEM & GLOBAL -->
            <div class="accordion-item border rounded-3 mb-3 overflow-hidden">
                <h2 class="accordion-header" id="headingMobileSystem">
                    @php
                        $isSystemActive = request()->routeIs('users.*', 'settings.*') || (request()->routeIs('activities.*') && request('module') !== 'sipat') || request()->routeIs('master.opd-mapping.*');
                    @endphp
                    <button class="accordion-button py-2.5 px-3 {{ $isSystemActive ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMobileSystem" aria-expanded="{{ $isSystemActive ? 'true' : 'false' }}">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-secondary text-white rounded-pill p-1.5"><i class="bi bi-gear-wide-connected"></i></span>
                            <div class="lh-1">
                                <span class="fw-bold fs-6 text-dark d-block">PENGATURAN SISTEM</span>
                                <small class="text-secondary" style="font-size: 0.68rem;">Integrasi OPD & Konfigurasi</small>
                            </div>
                        </div>
                    </button>
                </h2>
                <div id="collapseMobileSystem" class="accordion-collapse collapse {{ $isSystemActive ? 'show' : '' }}" data-bs-parent="#mobileModulesAccordion">
                    <div class="accordion-body p-2 bg-light bg-opacity-50">
                        <div class="list-group list-group-flush rounded-2 overflow-hidden border-0">
                            @if(auth()->user()?->role !== \App\Enums\UserRole::OPD)
                                <a href="{{ route('master.opd-mapping.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('master-data/opd-mapping*') ? 'active rounded' : '' }}">
                                    <i class="bi bi-link-45deg me-2 text-info"></i> Pemetaan OPD Terpadu
                                </a>
                            @endif
                            @if(auth()->check() && auth()->user()?->role === \App\Enums\UserRole::SUPERADMIN)
                                <a href="{{ route('users.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('users*') ? 'active rounded' : '' }}">
                                    <i class="bi bi-people-fill me-2 text-primary"></i> Manajemen Pengguna
                                </a>
                                <a href="{{ route('settings.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ request()->routeIs('settings.*') ? 'active rounded' : '' }}">
                                    <i class="bi bi-gear me-2 text-secondary"></i> Pengaturan System
                                </a>
                                <a href="{{ route('activities.index') }}" class="list-group-item list-group-item-action border-0 py-2 rounded-2 {{ Request::is('activities*') ? 'active rounded' : '' }}">
                                    <i class="bi bi-shield-lock me-2 text-primary"></i> Log Aktivitas Terpadu
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logout Action -->
        <div class="pt-2 border-top">
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="btn btn-outline-danger w-100 py-2 rounded-3 d-flex align-items-center justify-content-center gap-2 fw-semibold">
                <i class="bi bi-box-arrow-left fs-5"></i> Logout dari Sistem
            </a>
        </div>
    </div>
</div>
