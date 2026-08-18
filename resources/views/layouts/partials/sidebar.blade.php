<!-- Sidebar -->
<nav id="sidebar" class="sidebar-modern d-none d-md-block">
    <!-- Header / Brand -->
    <div class="sidebar-header d-flex align-items-center justify-content-between px-3 py-3">
        <a href="{{ route('home') }}" class="d-flex align-items-center gap-2 text-decoration-none">
            @php
                $siteLogo = \App\Models\Setting::get('site_logo');
            @endphp
            @if($siteLogo)
                <img src="{{ \App\Models\Setting::imageUrl($siteLogo) }}" alt="Logo" class="bg-white rounded-circle p-1 sidebar-logo" style="width: 36px; height: 36px; object-fit: contain;">
            @else
                <img src="{{ asset('images/hero-illustration.png') }}" alt="Logo" class="bg-white rounded-circle p-1 sidebar-logo" style="width: 36px; height: 36px; object-fit: contain;">
            @endif
            <div class="sidebar-brand-text">
                <div class="fw-bold fs-6 lh-1 text-white">SIPAT TERPADU</div>
                <small class="text-white-50 d-block mt-1" style="font-size: 0.65rem;">Sistem Aset Daerah</small>
            </div>
        </a>
        <button type="button" id="sidebarToggleCompact" class="btn btn-sm text-white-50 hover-white p-0 d-none d-md-inline-block border-0" data-bs-toggle="tooltip" data-bs-placement="right" title="Toggle Mode Ringkasan">
            <i class="bi bi-layout-sidebar-inset fs-5"></i>
        </button>
    </div>

    <div class="sidebar-body px-2 py-3">
        <!-- 1. KELOMPOK DASHBOARD (4 DASHBOARD SEPARATED AT TOP) -->
        <div class="sidebar-dashboard-group mb-3">
            <div class="px-2 mb-2">
                <small class="text-uppercase fw-bold text-white-50" style="font-size: 0.65rem; letter-spacing: 0.5px;">DASHBOARD</small>
            </div>
            
            <!-- Dashboard 1: Utama -->
            <div class="mb-1">
                <a href="{{ route('home') }}" class="sidebar-link-main {{ Request::is('home') ? 'active' : '' }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard Utama">
                    <i class="bi bi-grid-1x2-fill link-icon text-primary"></i>
                    <span class="link-text">Dashboard Utama</span>
                </a>
            </div>

            <!-- Dashboard 2: SIPAT -->
            <div class="mb-1">
                <a href="{{ route('sipat.dashboard') }}" class="sidebar-link-main {{ Request::is('sipat/dashboard*') ? 'active' : '' }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard SIPAT">
                    <i class="bi bi-geo-alt-fill link-icon text-info"></i>
                    <span class="link-text">Dashboard SIPAT</span>
                </a>
            </div>

            <!-- Dashboard 3: eLABEL -->
            <div class="mb-1">
                <a href="{{ route('elabel.dashboard') }}" class="sidebar-link-main {{ Request::is('elabel/dashboard*') ? 'active' : '' }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard eLABEL">
                    <i class="bi bi-box-seam-fill link-icon text-success"></i>
                    <span class="link-text">Dashboard eLABEL</span>
                </a>
            </div>

            <!-- Dashboard 4: eRANDIS -->
            <div class="mb-1">
                <a href="{{ route('erandis.dashboard') }}" class="sidebar-link-main {{ Request::is('erandis/dashboard*') ? 'active' : '' }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard eRANDIS">
                    <i class="bi bi-car-front-fill link-icon text-warning"></i>
                    <span class="link-text">Dashboard eRANDIS</span>
                </a>
            </div>
        </div>

        <div class="sidebar-divider my-3"></div>

        <!-- 2. MODULE ACCORDION (HANYA 1 MODUL TERBUKA DALAM SATU WAKTU) -->
        <div class="accordion sidebar-accordion" id="moduleAccordion">

            <!-- MODUL: SIPAT -->
            <div class="module-group">
                <a class="module-header {{ Request::is('sipat*') ? '' : 'collapsed' }}" 
                   data-bs-toggle="collapse" 
                   data-bs-target="#moduleSipat" 
                   aria-expanded="{{ Request::is('sipat*') ? 'true' : 'false' }}"
                   data-bs-toggle-tooltip="tooltip" data-bs-placement="right" title="SIPAT">
                    <div class="module-header-title">
                        <i class="bi bi-geo-alt-fill module-icon text-primary"></i>
                        <span class="module-name">SIPAT</span>
                    </div>
                    <i class="bi bi-chevron-down chevron-icon"></i>
                </a>
                <div id="moduleSipat" class="collapse {{ Request::is('sipat*') ? 'show' : '' }}" data-bs-parent="#moduleAccordion">
                    <ul class="submenu-list">
                        <li class="{{ Request::is('sipat/aset*') ? 'active' : '' }}">
                            <a href="{{ Route::has('sipat.aset.index') ? route('sipat.aset.index') : '#' }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Data Aset Tanah">
                                <i class="bi bi-journal-text"></i>
                                <span>Data Aset Tanah</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('sipat/proses*') ? 'active' : '' }}">
                            <a href="{{ Route::has('sipat.proses.index') ? route('sipat.proses.index') : '#' }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Status & Proses BPN">
                                <i class="bi bi-hourglass-split"></i>
                                <span>Status & Proses BPN</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('sipat/pengamanan*') ? 'active' : '' }}">
                            <a href="{{ Route::has('sipat.pengamanan.index') ? route('sipat.pengamanan.index') : '#' }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Pengamanan Fisik">
                                <i class="bi bi-shield-check"></i>
                                <span>Pengamanan Fisik</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('sipat/peta*') ? 'active' : '' }}">
                            <a href="{{ Route::has('sipat.peta.index') ? route('sipat.peta.index') : '#' }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Peta Geografis GIS">
                                <i class="bi bi-map"></i>
                                <span>Peta Geografis GIS</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('sipat/surat*') ? 'active' : '' }}">
                            <a href="{{ Route::has('sipat.surat.index') ? route('sipat.surat.index') : '#' }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Cetak Surat SKPT">
                                <i class="bi bi-file-earmark-word"></i>
                                <span>Cetak Surat SKPT</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('sipat/laporan*') ? 'active' : '' }}">
                            <a href="{{ route('sipat.laporan.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Laporan Aset Tanah">
                                <i class="bi bi-file-earmark-bar-graph"></i>
                                <span>Laporan Aset Tanah</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('sipat/rekonsiliasi*') ? 'active' : '' }}">
                            <a href="{{ Route::has('sipat.rekonsiliasi.index') ? route('sipat.rekonsiliasi.index') : '#' }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Rekonsiliasi Arsip">
                                <i class="bi bi-arrow-left-right"></i>
                                <span>Rekonsiliasi Arsip</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- MODUL: ERANDIS -->
            <div class="module-group">
                <a class="module-header {{ (Request::is('vehicles*', 'maintenance*', 'reports*') && !Request::is('sipat*') && !Request::is('elabel*')) ? '' : 'collapsed' }}" 
                   data-bs-toggle="collapse" 
                   data-bs-target="#moduleErandis" 
                   aria-expanded="{{ (Request::is('vehicles*', 'maintenance*', 'reports*') && !Request::is('sipat*') && !Request::is('elabel*')) ? 'true' : 'false' }}"
                   data-bs-toggle-tooltip="tooltip" data-bs-placement="right" title="ERANDIS">
                    <div class="module-header-title">
                        <i class="bi bi-car-front-fill module-icon text-warning"></i>
                        <span class="module-name">ERANDIS</span>
                    </div>
                    <i class="bi bi-chevron-down chevron-icon"></i>
                </a>
                <div id="moduleErandis" class="collapse {{ (Request::is('vehicles*', 'maintenance*', 'reports*') && !Request::is('sipat*') && !Request::is('elabel*')) ? 'show' : '' }}" data-bs-parent="#moduleAccordion">
                    <ul class="submenu-list">
                        <li class="{{ Request::is('vehicles*') ? 'active' : '' }}">
                            <a href="{{ route('vehicles.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Data Kendaraan">
                                <i class="bi bi-car-front"></i>
                                <span>Data Kendaraan</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('maintenance*') ? 'active' : '' }}">
                            <a href="{{ route('maintenance.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Servis & Pemeliharaan">
                                <i class="bi bi-tools"></i>
                                <span>Servis & Pemeliharaan</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('reports*') ? 'active' : '' }}">
                            <a href="{{ route('reports.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Laporan Kendaraan">
                                <i class="bi bi-file-earmark-bar-graph"></i>
                                <span>Laporan Kendaraan</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- MODUL: ELABEL -->
            <div class="module-group">
                <a class="module-header {{ Request::is('elabel*') ? '' : 'collapsed' }}" 
                   data-bs-toggle="collapse" 
                   data-bs-target="#moduleElabel" 
                   aria-expanded="{{ Request::is('elabel*') ? 'true' : 'false' }}"
                   data-bs-toggle-tooltip="tooltip" data-bs-placement="right" title="ELABEL">
                    <div class="module-header-title">
                        <i class="bi bi-archive-fill module-icon text-info"></i>
                        <span class="module-name">ELABEL</span>
                    </div>
                    <i class="bi bi-chevron-down chevron-icon"></i>
                </a>
                <div id="moduleElabel" class="collapse {{ Request::is('elabel*') ? 'show' : '' }}" data-bs-parent="#moduleAccordion">
                    <ul class="submenu-list">
                        <li class="{{ Request::is('elabel/boxes*') ? 'active' : '' }}">
                            <a href="{{ Route::has('elabel.boxes.index') ? route('elabel.boxes.index') : '#' }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Box & Lokasi Rak">
                                <i class="bi bi-box-seam"></i>
                                <span>Box & Lokasi Rak</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('elabel/sertifikat*') ? 'active' : '' }}">
                            <a href="{{ Route::has('elabel.sertifikat.index') ? route('elabel.sertifikat.index') : '#' }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Katalog Sertifikat Tanah">
                                <i class="bi bi-patch-check"></i>
                                <span>Katalog Sertifikat Tanah</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('elabel/bpkb*') ? 'active' : '' }}">
                            <a href="{{ Route::has('elabel.bpkb.index') ? route('elabel.bpkb.index') : '#' }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Katalog BPKB Kendaraan">
                                <i class="bi bi-card-heading"></i>
                                <span>Katalog BPKB Kendaraan</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('elabel/surat-penyerahan*') ? 'active' : '' }}">
                            <a href="{{ Route::has('elabel.surat-penyerahan.index') ? route('elabel.surat-penyerahan.index') : '#' }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Surat Penyerahan Arsip">
                                <i class="bi bi-file-earmark-text"></i>
                                <span>Surat Penyerahan Arsip</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('elabel/peminjaman*') ? 'active' : '' }}">
                            <a href="{{ Route::has('elabel.peminjaman.index') ? route('elabel.peminjaman.index') : '#' }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Peminjaman & Riwayat">
                                <i class="bi bi-clock-history"></i>
                                <span>Peminjaman & Riwayat</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- MODUL: MASTER & SISTEM -->
            <div class="module-group">
                <a class="module-header {{ Request::is('master-data*', 'opds*', 'vehicle-types*', 'users*', 'activities*', 'settings*') ? '' : 'collapsed' }}" 
                   data-bs-toggle="collapse" 
                   data-bs-target="#moduleSystem" 
                   aria-expanded="{{ Request::is('master-data*', 'opds*', 'vehicle-types*', 'users*', 'activities*', 'settings*') ? 'true' : 'false' }}"
                   data-bs-toggle-tooltip="tooltip" data-bs-placement="right" title="MASTER & SISTEM">
                    <div class="module-header-title">
                        <i class="bi bi-gear-wide-connected module-icon text-success"></i>
                        <span class="module-name">MASTER & SISTEM</span>
                    </div>
                    <i class="bi bi-chevron-down chevron-icon"></i>
                </a>
                <div id="moduleSystem" class="collapse {{ Request::is('master-data*', 'opds*', 'vehicle-types*', 'users*', 'activities*', 'settings*') ? 'show' : '' }}" data-bs-parent="#moduleAccordion">
                    <ul class="submenu-list">
                        @if(auth()->user()?->role !== \App\Enums\UserRole::OPD)
                            <li class="{{ Request::is('opds*') ? 'active' : '' }}">
                                <a href="{{ route('opds.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="OPD / Instansi">
                                    <i class="bi bi-building"></i>
                                    <span>OPD / Instansi</span>
                                </a>
                            </li>
                            <li class="{{ Request::is('vehicle-types*') ? 'active' : '' }}">
                                <a href="{{ route('vehicle-types.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Jenis Kendaraan">
                                    <i class="bi bi-grid-3x3-gap"></i>
                                    <span>Jenis Kendaraan</span>
                                </a>
                            </li>
                            <li class="{{ Request::is('master-data/status-proses*') ? 'active' : '' }}">
                                <a href="{{ route('status-proses.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Status Proses Pensertifikatan">
                                    <i class="bi bi-tags"></i>
                                    <span>Master Status Proses</span>
                                </a>
                            </li>
                            <li class="{{ Request::is('master-data/opd-sipat*') ? 'active' : '' }}">
                                <a href="{{ route('master.opd-sipat.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="OPD / Instansi (SIPAT)">
                                    <i class="bi bi-diagram-3"></i>
                                    <span>OPD (SIPAT)</span>
                                </a>
                            </li>
                            <li class="{{ Request::is('master-data/kop-surat*') ? 'active' : '' }}">
                                <a href="{{ route('master.kop-settings.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Master KOP Surat Pemda">
                                    <i class="bi bi-file-earmark-pdf text-warning"></i>
                                    <span>Master KOP Surat</span>
                                </a>
                            </li>
                            <li class="{{ Request::is('master-data/log-aktivitas*') ? 'active' : '' }}">
                                <a href="{{ route('master.logs.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Log Aktivitas (SIPAT)">
                                    <i class="bi bi-journal-check text-primary"></i>
                                    <span>Log Aktivitas (SIPAT)</span>
                                </a>
                            </li>
                        @endif

                        @if(auth()->check() && auth()->user()?->role === \App\Enums\UserRole::SUPERADMIN)
                            <li class="{{ Request::is('users*') ? 'active' : '' }}">
                                <a href="{{ route('users.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Manajemen Pengguna">
                                    <i class="bi bi-people-fill"></i>
                                    <span>Manajemen Pengguna</span>
                                </a>
                            </li>
                            <li class="{{ Request::is('activities*') ? 'active' : '' }}">
                                <a href="{{ Route::has('activities.index') ? route('activities.index') : '#' }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Audit Log (ERANDIS)">
                                    <i class="bi bi-shield-lock text-warning"></i>
                                    <span>Audit Log (ERANDIS)</span>
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('settings.*') ? 'active' : '' }}">
                                <a href="{{ route('settings.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Pengaturan System">
                                    <i class="bi bi-gear"></i>
                                    <span>Pengaturan System</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>

        </div>
    </div>

    <!-- Footer Logout -->
    <div class="sidebar-footer px-3 py-3 border-top border-white border-opacity-10">
        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="sidebar-logout-link d-flex align-items-center gap-2 text-danger text-decoration-none fw-semibold small" data-bs-toggle="tooltip" data-bs-placement="right" title="Logout">
            <i class="bi bi-box-arrow-left fs-5"></i>
            <span class="logout-text">Logout</span>
        </a>
    </div>
</nav>
