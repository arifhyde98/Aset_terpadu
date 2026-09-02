<!-- Sidebar -->
<nav id="sidebar" class="sidebar-modern">
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
        <div class="d-flex align-items-center gap-1">
            <button type="button" id="sidebarToggleCompact" class="btn btn-sm text-white-50 hover-white p-0 d-none d-md-inline-block border-0" data-bs-toggle="tooltip" data-bs-placement="right" title="Toggle Mode Ringkasan">
                <i class="bi bi-layout-sidebar-inset fs-5"></i>
            </button>
            <button type="button" id="sidebarCloseMobile" class="btn btn-sm text-white-50 hover-white p-1 d-md-none border-0" aria-label="Tutup Menu">
                <i class="bi bi-x-lg fs-5"></i>
            </button>
        </div>
    </div>

    <div class="sidebar-body px-2 py-3">
        <!-- 1. DASHBOARD UTAMA (COLLAPSIBLE GROUP FOR ALL 4 DASHBOARDS) -->
        <div class="module-group module-sipat mb-2">
            <a class="module-header {{ Request::is('home') || Request::is('sipat/dashboard*') || Request::is('elabel/dashboard*') || Request::is('erandis/dashboard*') ? '' : 'collapsed' }}" 
               data-bs-toggle="collapse" 
               data-bs-target="#moduleDashboardGroup" 
               aria-expanded="{{ Request::is('home') || Request::is('sipat/dashboard*') || Request::is('elabel/dashboard*') || Request::is('erandis/dashboard*') ? 'true' : 'false' }}"
               data-bs-toggle-tooltip="tooltip" data-bs-placement="right" title="DASHBOARD UTAMA">
                <div class="module-header-title">
                    <i class="bi bi-grid-1x2-fill module-icon text-primary"></i>
                    <span class="module-name">DASHBOARD</span>
                </div>
                <i class="bi bi-chevron-down chevron-icon"></i>
            </a>
            <div id="moduleDashboardGroup" class="collapse {{ Request::is('home') || Request::is('sipat/dashboard*') || Request::is('elabel/dashboard*') || Request::is('erandis/dashboard*') ? 'show' : '' }}" data-bs-parent="#moduleAccordion">
                <ul class="submenu-list py-1">
                    <li class="{{ Request::is('home') ? 'active' : '' }}">
                        <a href="{{ route('home') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard Utama (Ringkasan Terpadu)">
                            <i class="bi bi-grid-1x2-fill text-primary"></i>
                            <span>Dashboard Utama</span>
                        </a>
                    </li>
                    <li class="{{ Request::is('sipat/dashboard*') ? 'active' : '' }}">
                        <a href="{{ route('sipat.dashboard') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard SIPAT">
                            <i class="bi bi-geo-alt-fill text-info"></i>
                            <span>Dashboard SIPAT</span>
                        </a>
                    </li>
                    <li class="{{ Request::is('elabel/dashboard*') ? 'active' : '' }}">
                        <a href="{{ route('elabel.dashboard') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard eLABEL">
                            <i class="bi bi-box-seam-fill text-success"></i>
                            <span>Dashboard eLABEL</span>
                        </a>
                    </li>
                    <li class="{{ Request::is('erandis/dashboard*') ? 'active' : '' }}">
                        <a href="{{ route('erandis.dashboard') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard eRANDIS">
                            <i class="bi bi-car-front-fill text-warning"></i>
                            <span>Dashboard eRANDIS</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="sidebar-divider my-3"></div>

        <!-- 2. MODULE ACCORDION (TERSTRUKTUR PER MODUL) -->
        <div class="accordion sidebar-accordion" id="moduleAccordion">

            <!-- MODUL: SIPAT (Blue Accent) -->
            <div class="module-group module-sipat">
                <a class="module-header {{ Request::is('sipat*') || Request::is('master-data/status-proses*', 'master-data/wilayah*', 'master-data/kop-surat*', 'master-data/opd-sipat*', 'master-data/import*', 'master-data/log-aktivitas*') || (Request::is('activities*') && request('module') === 'sipat') ? '' : 'collapsed' }}" 
                   data-bs-toggle="collapse" 
                   data-bs-target="#moduleSipat" 
                   aria-expanded="{{ Request::is('sipat*') || Request::is('master-data/status-proses*', 'master-data/wilayah*', 'master-data/kop-surat*', 'master-data/opd-sipat*', 'master-data/import*', 'master-data/log-aktivitas*') || (Request::is('activities*') && request('module') === 'sipat') ? 'true' : 'false' }}"
                   data-bs-toggle-tooltip="tooltip" data-bs-placement="right" title="PENSERTIFIKATAN TANAH">
                    <div class="module-header-title">
                        <i class="bi bi-geo-alt-fill module-icon text-primary"></i>
                        <span class="module-name">PENSERTIFIKATAN TANAH</span>
                    </div>
                    <i class="bi bi-chevron-down chevron-icon"></i>
                </a>
                <div id="moduleSipat" class="collapse {{ Request::is('sipat*') || Request::is('master-data/status-proses*', 'master-data/wilayah*', 'master-data/kop-surat*', 'master-data/opd-sipat*', 'master-data/import*', 'master-data/log-aktivitas*') || (Request::is('activities*') && request('module') === 'sipat') ? 'show' : '' }}" data-bs-parent="#moduleAccordion">
                    
                    <!-- Nested Submenu 1: Aset & Inventaris -->
                    <div class="nested-group">
                        <a class="nested-header {{ Request::is('sipat/aset*', 'sipat/peta*', 'sipat/target-pensertifikatan*', 'sipat/tanah-tak-tercatat*') ? '' : 'collapsed' }}"
                           data-bs-toggle="collapse"
                           href="#sipatSubAset"
                           role="button"
                           aria-expanded="{{ Request::is('sipat/aset*', 'sipat/peta*', 'sipat/target-pensertifikatan*', 'sipat/tanah-tak-tercatat*') ? 'true' : 'false' }}">
                            <span><i class="bi bi-journal-album me-1 text-primary"></i> ASET TANAH </span>
                            <i class="bi bi-chevron-down nested-chevron"></i>
                        </a>
                        <div id="sipatSubAset" class="collapse {{ Request::is('sipat/aset*', 'sipat/peta*', 'sipat/target-pensertifikatan*', 'sipat/tanah-tak-tercatat*','sipat/laporan*') ? 'show' : '' }}">
                            <ul class="submenu-list">
                                <li class="{{ Request::is('sipat/aset*') ? 'active' : '' }}">
                                    <a href="{{ Route::has('sipat.aset.index') ? route('sipat.aset.index') : '#' }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Data Aset Tanah">
                                        <i class="bi bi-journal-text"></i>
                                        <span>Data Aset Tanah</span>
                                    </a>
                                </li>
                                <li class="{{ Request::is('sipat/tanah-tak-tercatat*') ? 'active' : '' }}">
                                    <a href="{{ route('sipat.tanah-tak-tercatat.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Tanah Belum / Tak Tercatat">
                                        <i class="bi bi-geo-alt"></i>
                                        <span>Tanah Belum Tercatat</span>
                                    </a>
                                </li>
                                <li class="{{ Request::is('sipat/target-pensertifikatan*') ? 'active' : '' }}">
                                    <a href="{{ route('sipat.target-pensertifikatan.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Target Pensertifikatan Tahunan">
                                        <i class="bi bi-crosshair"></i>
                                        <span>Target Pensertifikatan</span>
                                    </a>
                                </li>
                                <li class="{{ Request::is('sipat/peta*') ? 'active' : '' }}">
                                    <a href="{{ Route::has('sipat.peta.index') ? route('sipat.peta.index') : '#' }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Peta Geografis GIS">
                                        <i class="bi bi-map"></i>
                                        <span>Peta Geografis GIS</span>
                                    </a>
                                </li>
                                <li class="{{ Request::is('sipat/laporan*') ? 'active' : '' }}">
                                    <a href="{{ route('sipat.laporan.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Laporan Aset Tanah">
                                        <i class="bi bi-file-earmark-text"></i>
                                        <span>Laporan Aset Tanah</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Nested Submenu 3: Dokumen & Laporan -->
                     @if(auth()->check() && auth()->user()?->role === \App\Enums\UserRole::SUPERADMIN)
                    <div class="nested-group">
                        <a class="nested-header {{ Request::is('sipat/surat*', 'sipat/laporan*', 'sipat/rekonsiliasi*') ? '' : 'collapsed' }}"
                           data-bs-toggle="collapse"
                           href="#sipatSubDokumen"
                           role="button"
                           aria-expanded="{{ Request::is('sipat/surat*', 'sipat/laporan*', 'sipat/rekonsiliasi*') ? 'true' : 'false' }}">
                            <span><i class="bi bi-file-earmark-bar-graph me-1 text-info"></i> DOKUMEN & PELAPORAN</span>
                            <i class="bi bi-chevron-down nested-chevron"></i>
                        </a>
                        <div id="sipatSubDokumen" class="collapse {{ Request::is('sipat/surat*', 'sipat/laporan*', 'sipat/rekonsiliasi*') ? 'show' : '' }}">
                            <ul class="submenu-list">
                                <li class="{{ Request::is('sipat/surat*') ? 'active' : '' }}">
                                    <a href="{{ Route::has('sipat.surat.skpt') ? route('sipat.surat.skpt') : '#' }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Cetak Surat SKPT">
                                        <i class="bi bi-file-earmark-word"></i>
                                        <span>Cetak Surat SKPT</span>
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
                    @endif

                    <!-- Nested Submenu 4: Master Data & Pengaturan SIPAT -->
                    @if(auth()->user()?->role !== \App\Enums\UserRole::OPD)
                        <div class="nested-group">
                            <a class="nested-header {{ Request::is('master-data/status-proses*', 'master-data/wilayah*', 'master-data/kop-surat*', 'master-data/opd-sipat*', 'master-data/import*', 'master-data/log-aktivitas*') || (Request::is('activities*') && request('module') === 'sipat') ? '' : 'collapsed' }}"
                               data-bs-toggle="collapse"
                               href="#sipatSubMaster"
                               role="button"
                               aria-expanded="{{ Request::is('master-data/status-proses*', 'master-data/wilayah*', 'master-data/kop-surat*', 'master-data/opd-sipat*', 'master-data/import*', 'master-data/log-aktivitas*') || (Request::is('activities*') && request('module') === 'sipat') ? 'true' : 'false' }}">
                                <span><i class="bi bi-sliders me-1 text-success"></i> PENGATURAN SIPAT</span>
                                <i class="bi bi-chevron-down nested-chevron"></i>
                            </a>
                            <div id="sipatSubMaster" class="collapse {{ Request::is('master-data/status-proses*', 'master-data/wilayah*', 'master-data/kop-surat*', 'master-data/opd-sipat*', 'master-data/import*', 'master-data/log-aktivitas*') || (Request::is('activities*') && request('module') === 'sipat') ? 'show' : '' }}">
                                <ul class="submenu-list">
                                    <li class="{{ Request::is('master-data/status-proses*') ? 'active' : '' }}">
                                        <a href="{{ route('status-proses.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Status Proses Pensertifikatan">
                                            <i class="bi bi-tags"></i>
                                            <span>Master Status Proses</span>
                                        </a>
                                    </li>
                                    <li class="{{ Request::is('master-data/wilayah*') ? 'active' : '' }}">
                                        <a href="{{ route('master.wilayah.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Master Wilayah & Pejabat (SKPT)">
                                            <i class="bi bi-geo-alt"></i>
                                            <span>Wilayah & Pejabat</span>
                                        </a>
                                    </li>
                                    <li class="{{ Request::is('master-data/kop-surat*') ? 'active' : '' }}">
                                        <a href="{{ route('master.kop-settings.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Master KOP Surat Pemda">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                            <span>KOP Surat Pemda</span>
                                        </a>
                                    </li>
                                    <li class="{{ Request::is('master-data/opd-sipat*') ? 'active' : '' }}">
                                        <a href="{{ route('master.opd-sipat.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="OPD / Instansi (SIPAT)">
                                            <i class="bi bi-diagram-3"></i>
                                            <span>OPD Instansi (SIPAT)</span>
                                        </a>
                                    </li>
                                    <li class="{{ Request::is('master-data/import*') ? 'active' : '' }}">
                                        <a href="{{ route('master.import.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Import Aset Tanah & Status Proses">
                                            <i class="bi bi-file-earmark-arrow-up"></i>
                                            <span>Import Data SIPAT</span>
                                        </a>
                                    </li>
                                    <li class="{{ Request::is('activities*') && request('module') === 'sipat' ? 'active' : '' }}">
                                        <a href="{{ route('activities.index', ['module' => 'sipat']) }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Log Aktivitas (SIPAT)">
                                            <i class="bi bi-journal-check"></i>
                                            <span>Log Aktivitas SIPAT</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    @endif

                </div>
            </div>

            <!-- MODUL: ERANDIS (Yellow/Orange Accent) -->
            <div class="module-group module-erandis">
                <a class="module-header {{ (Request::is('vehicles*', 'maintenance*', 'reports*', 'opds*', 'vehicle-types*', 'activities*') && request('module') !== 'sipat' && !Request::is('sipat*') && !Request::is('elabel*')) ? '' : 'collapsed' }}" 
                   data-bs-toggle="collapse" 
                   data-bs-target="#moduleErandis" 
                   aria-expanded="{{ (Request::is('vehicles*', 'maintenance*', 'reports*', 'opds*', 'vehicle-types*', 'activities*') && request('module') !== 'sipat' && !Request::is('sipat*') && !Request::is('elabel*')) ? 'true' : 'false' }}"
                   data-bs-toggle-tooltip="tooltip" data-bs-placement="right" title="ERANDIS">
                    <div class="module-header-title">
                        <i class="bi bi-car-front-fill module-icon text-warning"></i>
                        <span class="module-name">KENDARAN DINAS</span>
                    </div>
                    <i class="bi bi-chevron-down chevron-icon"></i>
                </a>
                <div id="moduleErandis" class="collapse {{ (Request::is('erandis*', 'vehicles*', 'maintenance*', 'reports*', 'opds*', 'vehicle-types*', 'activities*') && request('module') !== 'sipat' && !Request::is('sipat*') && !Request::is('elabel*')) ? 'show' : '' }}" data-bs-parent="#moduleAccordion">
                    
                    <!-- Nested Submenu 1: Inventaris Kendaraan -->
                    <div class="nested-group">
                        <a class="nested-header {{ Request::is('vehicles*') ? '' : 'collapsed' }}"
                           data-bs-toggle="collapse"
                           href="#erandisSubKendaraan"
                           role="button"
                           aria-expanded="{{ Request::is('vehicles*') ? 'true' : 'false' }}">
                            <span><i class="bi bi-car-front me-1 text-warning"></i> MANAJEMEN KENDARAAN</span>
                            <i class="bi bi-chevron-down nested-chevron"></i>
                        </a>
                        <div id="erandisSubKendaraan" class="collapse {{ Request::is('vehicles*') ? 'show' : '' }}">
                            <ul class="submenu-list">
                                <li class="{{ Request::is('vehicles*') && !Request::is('vehicles/rekon-bpkb') ? 'active' : '' }}">
                                    <a href="{{ route('vehicles.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Data Kendaraan">
                                        <i class="bi bi-truck"></i>
                                        <span>Data Kendaraan Dinas</span>
                                    </a>
                                </li>
                                <li class="{{ Request::is('reports*') ? 'active' : '' }}">
                                    <a href="{{ route('reports.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Laporan Kendaraan">
                                        <i class="bi bi-graph-up"></i>
                                        <span>Laporan Kendaraan Dinas</span>
                                    </a>
                                </li>
                                <li class="{{ Request::is('vehicles/rekon-bpkb') ? 'active' : '' }}">
                                    <a href="{{ route('vehicles.rekon-bpkb') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Rekonsiliasi BPKB">
                                        <i class="bi bi-arrow-left-right"></i>
                                        <span>Rekonsiliasi BPKB</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Nested Submenu 2: Operasional & Pemeliharaan -->
                  

                    <!-- Nested Submenu 3: Pelaporan -->
                    

                    <!-- Nested Submenu 4: Master Data ERANDIS -->
                    @if(auth()->user()?->role !== \App\Enums\UserRole::OPD)
                        <div class="nested-group">
                            <a class="nested-header {{ Request::is('opds*', 'vehicle-types*', 'activities*') ? '' : 'collapsed' }}"
                               data-bs-toggle="collapse"
                               href="#erandisSubMaster"
                               role="button"
                               aria-expanded="{{ Request::is('opds*', 'vehicle-types*', 'activities*') ? 'true' : 'false' }}">
                                <span><i class="bi bi-sliders me-1 text-primary"></i> MASTER ERANDIS</span>
                                <i class="bi bi-chevron-down nested-chevron"></i>
                            </a>
                            <div id="erandisSubMaster" class="collapse {{ Request::is('opds*', 'vehicle-types*', 'activities*') ? 'show' : '' }}">
                                <ul class="submenu-list">
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
                                    @if(auth()->check() && auth()->user()?->role === \App\Enums\UserRole::SUPERADMIN)
                                        <li class="{{ Request::is('activities*') ? 'active' : '' }}">
                                            <a href="{{ Route::has('activities.index') ? route('activities.index') : '#' }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Log Aktivitas Terpadu">
                                                <i class="bi bi-shield-lock"></i>
                                                <span>Log Aktivitas Terpadu</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    @endif

                </div>
            </div>

            <!-- MODUL: ELABEL (Light Blue/Cyan Accent) -->
            <div class="module-group module-elabel">
                <a class="module-header {{ Request::is('elabel*') ? '' : 'collapsed' }}" 
                   data-bs-toggle="collapse" 
                   data-bs-target="#moduleElabel" 
                   aria-expanded="{{ Request::is('elabel*') ? 'true' : 'false' }}"
                   data-bs-toggle-tooltip="tooltip" data-bs-placement="right" title="ELABEL">
                    <div class="module-header-title">
                        <i class="bi bi-archive-fill module-icon text-info"></i>
                        <span class="module-name">MANAJEMEN ARSIP</span>
                    </div>
                    <i class="bi bi-chevron-down chevron-icon"></i>
                </a>
                <div id="moduleElabel" class="collapse {{ Request::is('elabel*') ? 'show' : '' }}" data-bs-parent="#moduleAccordion">
                    
                    <!-- Nested Submenu 1: Dokumen BPKB -->
                    <div class="nested-group">
                        <a class="nested-header {{ Request::is('elabel/bpkb*', 'elabel/boxes*') ? '' : 'collapsed' }}"
                           data-bs-toggle="collapse"
                           href="#elabelSubBpkb"
                           role="button"
                           aria-expanded="{{ Request::is('elabel/bpkb*', 'elabel/boxes*') ? 'true' : 'false' }}">
                            <span><i class="bi bi-card-heading me-1 text-primary"></i> DOKUMEN BPKB</span>
                            <i class="bi bi-chevron-down nested-chevron"></i>
                        </a>
                        <div id="elabelSubBpkb" class="collapse {{ Request::is('elabel/bpkb*', 'elabel/boxes*') ? 'show' : '' }}">
                            <ul class="submenu-list">
                                <li class="{{ Request::is('elabel/bpkb') || Request::is('elabel/bpkb/*') && !Request::is('elabel/bpkb-deleted*') ? 'active' : '' }}">
                                    <a href="{{ route('elabel.bpkb.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Katalog BPKB Kendaraan">
                                        <i class="bi bi-card-checklist"></i>
                                        <span>Katalog BPKB</span>
                                    </a>
                                </li>
                                <li class="{{ Request::is('elabel/bpkb-deleted*') ? 'active' : '' }}">
                                    <a href="{{ route('elabel.bpkb-deleted.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="BPKB Keluar">
                                        <i class="bi bi-box-arrow-right"></i>
                                        <span>BPKB Keluar</span>
                                    </a>
                                </li>
                                <li class="{{ Request::is('elabel/boxes*') ? 'active' : '' }}">
                                    <a href="{{ route('elabel.boxes.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Box & Lokasi Rak BPKB">
                                        <i class="bi bi-box-seam"></i>
                                        <span>Box BPKB</span>
                                    </a>
                                </li>
                                <li class="{{ Request::is('elabel/bpkb-smart-extractor*') ? 'active' : '' }}">
                                    <a href="{{ route('elabel.bpkb.smart-extractor.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Smart BPKB PDF Folder Scanner">
                                        <i class="bi bi-folder-symlink-fill text-info"></i>
                                        <span>Smart Folder Scanner</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Nested Submenu 2: Sertifikat Tanah -->
                    <div class="nested-group">
                        <a class="nested-header {{ Request::is('elabel/sertifikat*') ? '' : 'collapsed' }}"
                           data-bs-toggle="collapse"
                           href="#elabelSubSertifikat"
                           role="button"
                           aria-expanded="{{ Request::is('elabel/sertifikat*') ? 'true' : 'false' }}">
                            <span><i class="bi bi-patch-check me-1 text-success"></i> SERTIFIKAT TANAH</span>
                            <i class="bi bi-chevron-down nested-chevron"></i>
                        </a>
                        <div id="elabelSubSertifikat" class="collapse {{ Request::is('elabel/sertifikat*') ? 'show' : '' }}">
                            <ul class="submenu-list">
                                <li class="{{ Request::is('elabel/sertifikat') || Request::is('elabel/sertifikat/*') && !Request::is('elabel/sertifikat-boxes*') ? 'active' : '' }}">
                                    <a href="{{ route('elabel.sertifikat.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Katalog Sertifikat Tanah">
                                        <i class="bi bi-patch-check-fill"></i>
                                        <span>Katalog Sertifikat</span>
                                    </a>
                                </li>
                                <li class="{{ Request::is('elabel/sertifikat-boxes*') ? 'active' : '' }}">
                                    <a href="{{ route('elabel.sertifikat-boxes.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Box Sertifikat Tanah">
                                        <i class="bi bi-archive"></i>
                                        <span>Box Sertifikat Tanah</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Nested Submenu 3: Surat Penyerahan & Layanan -->
                    <div class="nested-group">
                        <a class="nested-header {{ Request::is('elabel/surat-penyerahan*', 'elabel/peminjaman*') ? '' : 'collapsed' }}"
                           data-bs-toggle="collapse"
                           href="#elabelSubSurat"
                           role="button"
                           aria-expanded="{{ Request::is('elabel/surat-penyerahan*', 'elabel/peminjaman*') ? 'true' : 'false' }}">
                            <span><i class="bi bi-folder-symlink me-1 text-warning"></i> SURAT PENYERAHAN</span>
                            <i class="bi bi-chevron-down nested-chevron"></i>
                        </a>
                        <div id="elabelSubSurat" class="collapse {{ Request::is('elabel/surat-penyerahan*', 'elabel/peminjaman*') ? 'show' : '' }}">
                            <ul class="submenu-list">
                                <li class="{{ Request::is('elabel/surat-penyerahan') || Request::is('elabel/surat-penyerahan/*') && !Request::is('elabel/surat-penyerahan-boxes*') ? 'active' : '' }}">
                                    <a href="{{ route('elabel.surat-penyerahan.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Surat Penyerahan Arsip">
                                        <i class="bi bi-file-earmark-text"></i>
                                        <span>Surat Penyerahan</span>
                                    </a>
                                </li>
                                <li class="{{ Request::is('elabel/surat-penyerahan-boxes*') ? 'active' : '' }}">
                                    <a href="{{ route('elabel.surat-penyerahan-boxes.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Box Surat Penyerahan">
                                        <i class="bi bi-folder2-open"></i>
                                        <span>Box Surat Penyerahan</span>
                                    </a>
                                </li>
                                <li class="{{ Request::is('elabel/peminjaman*') ? 'active' : '' }}">
                                    <a href="{{ route('elabel.peminjaman.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Peminjaman & Riwayat">
                                        <i class="bi bi-clock-history"></i>
                                        <span>Request Scan & Pinjam</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Nested Submenu 4: Universal Dynamic Archive Engine -->
                    <div class="nested-group">
                        <a class="nested-header {{ Request::is('elabel/dynamic*') ? '' : 'collapsed' }}"
                           data-bs-toggle="collapse"
                           href="#elabelSubDynamic"
                           role="button"
                           aria-expanded="{{ Request::is('elabel/dynamic*') ? 'true' : 'false' }}">
                            <span><i class="bi bi-collection-play me-1 text-info"></i> ARSIP DINAMIS</span>
                            <i class="bi bi-chevron-down nested-chevron"></i>
                        </a>
                        <div id="elabelSubDynamic" class="collapse {{ Request::is('elabel/dynamic*') ? 'show' : '' }}">
                            <ul class="submenu-list">
                                <li class="{{ Request::is('elabel/dynamic/items*') ? 'active' : '' }}">
                                    <a href="{{ route('elabel.dynamic.items.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Katalog Berkas Arsip Dinamis">
                                        <i class="bi bi-folder2-open text-primary"></i>
                                        <span>Katalog Berkas</span>
                                    </a>
                                </li>
                                <li class="{{ Request::is('elabel/dynamic/boxes*') ? 'active' : '' }}">
                                    <a href="{{ route('elabel.dynamic.boxes.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Manajemen Box Fisik Arsip Dinamis">
                                        <i class="bi bi-box-seam text-warning"></i>
                                        <span>Manajemen Box</span>
                                    </a>
                                </li>
                                <li class="{{ Request::is('elabel/dynamic/types*') ? 'active' : '' }}">
                                    <a href="{{ route('elabel.dynamic.types.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Master Jenis & Form Builder">
                                        <i class="bi bi-sliders text-success"></i>
                                        <span>Master Kategori & Form</span>
                                    </a>
                                </li>
                                <li class="{{ Request::is('elabel/dynamic/loans*') ? 'active' : '' }}">
                                    <a href="{{ route('elabel.dynamic.loans.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Layanan Peminjaman & Scan Digital">
                                        <i class="bi bi-arrow-left-right text-info"></i>
                                        <span>Layanan Peminjaman</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>

            <!-- MODUL: PENGATURAN SISTEM (GLOBAL UTILITY & ADMIN) -->
            <div class="module-group">
                <a class="module-header {{ (Request::is('users*', 'settings*', 'activities*', 'master-data/opd-mapping*') && request('module') !== 'sipat') ? '' : 'collapsed' }}" 
                   data-bs-toggle="collapse" 
                   data-bs-target="#moduleSystem" 
                   aria-expanded="{{ (Request::is('users*', 'settings*', 'activities*', 'master-data/opd-mapping*') && request('module') !== 'sipat') ? 'true' : 'false' }}"
                   data-bs-toggle-tooltip="tooltip" data-bs-placement="right" title="PENGATURAN SISTEM">
                    <div class="module-header-title">
                        <i class="bi bi-gear-wide-connected module-icon text-success"></i>
                        <span class="module-name">PENGATURAN SISTEM</span>
                    </div>
                    <i class="bi bi-chevron-down chevron-icon"></i>
                </a>
                <div id="moduleSystem" class="collapse {{ (Request::is('users*', 'settings*', 'activities*', 'master-data/opd-mapping*') && request('module') !== 'sipat') ? 'show' : '' }}" data-bs-parent="#moduleAccordion">
                    <ul class="submenu-list">
                        @if(auth()->user()?->role !== \App\Enums\UserRole::OPD)
                            <li class="{{ Request::is('master-data/opd-mapping*') ? 'active' : '' }}">
                                <a href="{{ route('master.opd-mapping.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Pemetaan OPD Terpadu (SIPAT ↔ E-RANDIS)">
                                    <i class="bi bi-link-45deg text-info"></i>
                                    <span>Pemetaan OPD Terpadu</span>
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
                            <li class="{{ Request::is('settings') ? 'active' : '' }}">
                                <a href="{{ route('settings.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Pengaturan System">
                                    <i class="bi bi-gear"></i>
                                    <span>Pengaturan System</span>
                                </a>
                            </li>
                            <li class="{{ Request::is('settings/backups*') ? 'active' : '' }}">
                                <a href="{{ route('settings.backups.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Backup Sistem">
                                    <i class="bi bi-cloud-arrow-down"></i>
                                    <span>Backup Sistem</span>
                                </a>
                            </li>
                            <li class="{{ Request::is('activities*') ? 'active' : '' }}">
                                <a href="{{ route('activities.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="Log Aktivitas Terpadu">
                                    <i class="bi bi-shield-check"></i>
                                    <span>Log Aktivitas Terpadu</span>
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
