<!-- Horizontal Floating Topbar Navigation -->
<div class="horizontal-topbar-wrapper d-none mb-4">
    <div class="card border-0 shadow-sm rounded-4 w-100 bg-white" id="horizontalNavbarCard">
        <div class="card-body p-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <!-- Brand / Logo for Horizontal View -->
            <a href="{{ Route::has('home') ? route('home') : '#' }}" class="d-flex align-items-center gap-2 text-decoration-none px-2 me-1 border-end pe-3">
                @php
                    $siteLogo = \App\Models\Setting::get('site_logo');
                @endphp
                @if($siteLogo)
                    <img src="{{ \App\Models\Setting::imageUrl($siteLogo) }}" alt="Logo" class="rounded-circle p-0.5 border shadow-sm" style="width: 32px; height: 32px; object-fit: contain;">
                @else
                    <img src="{{ asset('images/hero-illustration.png') }}" alt="Logo" class="rounded-circle p-0.5 border shadow-sm" style="width: 32px; height: 32px; object-fit: contain;">
                @endif
                <div>
                    <div class="fw-bold text-navy small lh-1">SIPAT TERPADU</div>
                    <small class="text-secondary d-block mt-0.5" style="font-size: 0.65rem;">Sistem Aset Daerah</small>
                </div>
            </a>

            <!-- Module Navigation Dropdowns (Pill Style Nav) -->
            <div class="d-flex align-items-center gap-1.5 flex-wrap me-auto">
                <!-- 1. DASHBOARD -->
                <div class="dropdown">
                    <button class="btn btn-sm border-0 fw-semibold text-navy d-flex align-items-center gap-1.5 px-3 py-1.5 rounded-pill nav-pill-btn {{ Request::is('home') || Request::is('sipat/dashboard*') || Request::is('elabel/dashboard*') || Request::is('erandis/dashboard*') ? 'active-pill bg-primary text-white shadow-sm' : 'bg-light hover-light' }}" 
                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-grid-1x2-fill {{ Request::is('home') || Request::is('sipat/dashboard*') || Request::is('elabel/dashboard*') || Request::is('erandis/dashboard*') ? 'text-white' : 'text-primary' }}"></i> 
                        <span>Dashboard</span> 
                        <i class="bi bi-chevron-down opacity-75 small" style="font-size: 0.65rem;"></i>
                    </button>
                    <ul class="dropdown-menu border-0 shadow-lg rounded-3 py-1.5 mt-2">
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('home') ? 'active fw-bold' : '' }}" href="{{ Route::has('home') ? route('home') : '#' }}"><i class="bi bi-grid-1x2-fill text-primary"></i> Dashboard Utama</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('sipat/dashboard*') ? 'active fw-bold' : '' }}" href="{{ Route::has('sipat.dashboard') ? route('sipat.dashboard') : '#' }}"><i class="bi bi-geo-alt-fill text-info"></i> Dashboard SIPAT</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('elabel/dashboard*') ? 'active fw-bold' : '' }}" href="{{ Route::has('elabel.dashboard') ? route('elabel.dashboard') : '#' }}"><i class="bi bi-box-seam-fill text-success"></i> Dashboard eLABEL</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('erandis/dashboard*') ? 'active fw-bold' : '' }}" href="{{ Route::has('erandis.dashboard') ? route('erandis.dashboard') : '#' }}"><i class="bi bi-car-front-fill text-warning"></i> Dashboard eRANDIS</a></li>
                    </ul>
                </div>

                <!-- 2. PENSERTIFIKATAN TANAH -->
                <div class="dropdown">
                    <button class="btn btn-sm border-0 fw-semibold text-navy d-flex align-items-center gap-1.5 px-3 py-1.5 rounded-pill nav-pill-btn {{ Request::is('sipat*') && !Request::is('sipat/dashboard*') && !Request::is('sipat/aset-bangunan*') && !Request::is('bangunan*') ? 'active-pill bg-info text-white shadow-sm' : 'bg-light hover-light' }}" 
                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-geo-alt-fill {{ Request::is('sipat*') && !Request::is('sipat/dashboard*') && !Request::is('sipat/aset-bangunan*') && !Request::is('bangunan*') ? 'text-white' : 'text-info' }}"></i> 
                        <span>Aset Tanah</span> 
                        <i class="bi bi-chevron-down opacity-75 small" style="font-size: 0.65rem;"></i>
                    </button>
                    <ul class="dropdown-menu border-0 shadow-lg rounded-3 py-1.5 mt-2">
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('sipat/aset') ? 'active fw-bold' : '' }}" href="{{ Route::has('sipat.aset.index') ? route('sipat.aset.index') : '#' }}"><i class="bi bi-map-fill text-primary"></i> Data Aset Tanah</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('sipat/tanah-tak-tercatat*') ? 'active fw-bold' : '' }}" href="{{ Route::has('sipat.tanah-tak-tercatat.index') ? route('sipat.tanah-tak-tercatat.index') : '#' }}"><i class="bi bi-exclamation-triangle-fill text-warning"></i> Tanah Belum Tercatat</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('sipat/target-pensertifikatan*') ? 'active fw-bold' : '' }}" href="{{ Route::has('sipat.target-pensertifikatan.index') ? route('sipat.target-pensertifikatan.index') : '#' }}"><i class="bi bi-bullseye text-danger"></i> Target Sertifikat BPN</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('sipat/peta*') ? 'active fw-bold' : '' }}" href="{{ Route::has('sipat.peta.index') ? route('sipat.peta.index') : '#' }}"><i class="bi bi-globe-americas text-info"></i> Peta Spasial GIS</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('sipat/laporan*') ? 'active fw-bold' : '' }}" href="{{ Route::has('sipat.laporan.index') ? route('sipat.laporan.index') : '#' }}"><i class="bi bi-file-earmark-bar-graph-fill text-success"></i> Laporan Pertanahan</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('sipat/surat*') ? 'active fw-bold' : '' }}" href="{{ Route::has('sipat.surat.skpt') ? route('sipat.surat.skpt') : '#' }}"><i class="bi bi-file-earmark-pdf-fill text-danger"></i> Cetak Dokumen SKPT</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('sipat/rekonsiliasi*') ? 'active fw-bold' : '' }}" href="{{ Route::has('sipat.rekonsiliasi.index') ? route('sipat.rekonsiliasi.index') : '#' }}"><i class="bi bi-intersect text-secondary"></i> Rekonsiliasi Arsip</a></li>
                    </ul>
                </div>

                <!-- 3. KIB C (BANGUNAN) -->
                <div class="dropdown">
                    <button class="btn btn-sm border-0 fw-semibold text-navy d-flex align-items-center gap-1.5 px-3 py-1.5 rounded-pill nav-pill-btn {{ Request::is('bangunan*') ? 'active-pill bg-danger text-white shadow-sm' : 'bg-light hover-light' }}" 
                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-building-fill {{ Request::is('bangunan*') ? 'text-white' : 'text-danger' }}"></i> 
                        <span>Bangunan</span> 
                        <i class="bi bi-chevron-down opacity-75 small" style="font-size: 0.65rem;"></i>
                    </button>
                    <ul class="dropdown-menu border-0 shadow-lg rounded-3 py-1.5 mt-2">
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('bangunan') ? 'active fw-bold' : '' }}" href="{{ Route::has('bangunan.index') ? route('bangunan.index') : '#' }}"><i class="bi bi-building-fill text-danger"></i> Data Gedung & Bangunan</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('bangunan/peta*') ? 'active fw-bold' : '' }}" href="{{ Route::has('bangunan.peta.index') ? route('bangunan.peta.index') : '#' }}"><i class="bi bi-geo-fill text-info"></i> Peta GIS Gedung</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('bangunan/laporan*') ? 'active fw-bold' : '' }}" href="{{ Route::has('bangunan.laporan.index') ? route('bangunan.laporan.index') : '#' }}"><i class="bi bi-file-earmark-spreadsheet-fill text-success"></i> Laporan KIB C</a></li>
                    </ul>
                </div>

                <!-- 4. KENDARAAN DINAS -->
                <div class="dropdown">
                    <button class="btn btn-sm border-0 fw-semibold text-navy d-flex align-items-center gap-1.5 px-3 py-1.5 rounded-pill nav-pill-btn {{ Request::is('vehicles*') || Request::is('reports*') ? 'active-pill bg-warning text-dark shadow-sm fw-bold' : 'bg-light hover-light' }}" 
                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-car-front-fill {{ Request::is('vehicles*') || Request::is('reports*') ? 'text-dark' : 'text-warning' }}"></i> 
                        <span>Kendaraan</span> 
                        <i class="bi bi-chevron-down opacity-75 small" style="font-size: 0.65rem;"></i>
                    </button>
                    <ul class="dropdown-menu border-0 shadow-lg rounded-3 py-1.5 mt-2">
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('vehicles') ? 'active fw-bold' : '' }}" href="{{ Route::has('vehicles.index') ? route('vehicles.index') : '#' }}"><i class="bi bi-car-front-fill text-warning"></i> Data Kendaraan Dinas</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('reports*') ? 'active fw-bold' : '' }}" href="{{ Route::has('reports.index') ? route('reports.index') : '#' }}"><i class="bi bi-bar-chart-line-fill text-primary"></i> Laporan Kendaraan</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('vehicles/rekon-bpkb*') ? 'active fw-bold' : '' }}" href="{{ Route::has('vehicles.rekon-bpkb') ? route('vehicles.rekon-bpkb') : '#' }}"><i class="bi bi-intersect text-info"></i> Rekonsiliasi BPKB</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('vehicles/pinjam-pakai*') ? 'active fw-bold' : '' }}" href="{{ Route::has('vehicles.pinjam-pakai.index') ? route('vehicles.pinjam-pakai.index') : '#' }}"><i class="bi bi-journal-arrow-up text-danger"></i> Pinjam Pakai Kendaraan</a></li>
                    </ul>
                </div>

                <!-- 5. MANAJEMEN ARSIP -->
                <div class="dropdown">
                    <button class="btn btn-sm border-0 fw-semibold text-navy d-flex align-items-center gap-1.5 px-3 py-1.5 rounded-pill nav-pill-btn {{ Request::is('elabel*') && !Request::is('elabel/dashboard*') ? 'active-pill bg-success text-white shadow-sm' : 'bg-light hover-light' }}" 
                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-archive-fill {{ Request::is('elabel*') && !Request::is('elabel/dashboard*') ? 'text-white' : 'text-success' }}"></i> 
                        <span>Arsip Dokumen</span> 
                        <i class="bi bi-chevron-down opacity-75 small" style="font-size: 0.65rem;"></i>
                    </button>
                    <ul class="dropdown-menu border-0 shadow-lg rounded-3 py-1.5 mt-2">
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('elabel/bpkb*') ? 'active fw-bold' : '' }}" href="{{ Route::has('elabel.bpkb.index') ? route('elabel.bpkb.index') : '#' }}"><i class="bi bi-journal-check text-warning"></i> Dokumen BPKB</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('elabel/sertifikat*') ? 'active fw-bold' : '' }}" href="{{ Route::has('elabel.sertifikat.index') ? route('elabel.sertifikat.index') : '#' }}"><i class="bi bi-shield-check text-primary"></i> Dokumen Sertifikat</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('elabel/surat-penyerahan*') ? 'active fw-bold' : '' }}" href="{{ Route::has('elabel.surat-penyerahan.index') ? route('elabel.surat-penyerahan.index') : '#' }}"><i class="bi bi-file-earmark-text-fill text-info"></i> Surat Penyerahan</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('elabel/dynamic/types*') ? 'active fw-bold' : '' }}" href="{{ Route::has('elabel.dynamic.types.index') ? route('elabel.dynamic.types.index') : '#' }}"><i class="bi bi-folder2-open text-danger"></i> Kategori Arsip Dinamis</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('elabel/peminjaman*') ? 'active fw-bold' : '' }}" href="{{ Route::has('elabel.peminjaman.index') ? route('elabel.peminjaman.index') : '#' }}"><i class="bi bi-arrow-left-right text-success"></i> Layanan Peminjaman</a></li>
                    </ul>
                </div>

                <!-- 6. MASTER DATA -->
                <div class="dropdown">
                    <button class="btn btn-sm border-0 fw-semibold text-navy d-flex align-items-center gap-1.5 px-3 py-1.5 rounded-pill nav-pill-btn {{ Request::is('master-data*') || Request::is('opds*') || Request::is('sub-opds*') || Request::is('vehicle-types*') || Request::is('status-proses*') ? 'active-pill bg-primary text-white shadow-sm' : 'bg-light hover-light' }}" 
                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-folder-symlink-fill {{ Request::is('master-data*') || Request::is('opds*') || Request::is('sub-opds*') || Request::is('vehicle-types*') || Request::is('status-proses*') ? 'text-white' : 'text-primary' }}"></i> 
                        <span>Master Data</span> 
                        <i class="bi bi-chevron-down opacity-75 small" style="font-size: 0.65rem;"></i>
                    </button>
                    <ul class="dropdown-menu border-0 shadow-lg rounded-3 py-1.5 mt-2">
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('opds*') ? 'active fw-bold' : '' }}" href="{{ Route::has('opds.index') ? route('opds.index') : '#' }}"><i class="bi bi-building text-primary"></i> Data OPD / Instansi</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('sub-opds*') ? 'active fw-bold' : '' }}" href="{{ Route::has('sub-opds.index') ? route('sub-opds.index') : '#' }}"><i class="bi bi-diagram-3-fill text-info"></i> Data Sub-OPD (KPB)</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('master-data/wilayah*') ? 'active fw-bold' : '' }}" href="{{ Route::has('master.wilayah.kecamatan') ? route('master.wilayah.kecamatan') : '#' }}"><i class="bi bi-geo-alt text-success"></i> Master Wilayah</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('status-proses*') ? 'active fw-bold' : '' }}" href="{{ Route::has('status-proses.index') ? route('status-proses.index') : '#' }}"><i class="bi bi-hourglass-split text-warning"></i> Status Proses</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('vehicle-types*') ? 'active fw-bold' : '' }}" href="{{ Route::has('vehicle-types.index') ? route('vehicle-types.index') : '#' }}"><i class="bi bi-truck text-secondary"></i> Jenis Kendaraan</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('master-data/import*') ? 'active fw-bold' : '' }}" href="{{ Route::has('master-data.import.index') ? route('master-data.import.index') : '#' }}"><i class="bi bi-file-earmark-excel text-success"></i> Import Data Massal</a></li>
                    </ul>
                </div>

                @if(Auth::check() && Auth::user()->role === \App\Enums\UserRole::SUPERADMIN)
                <!-- 7. PENGATURAN SISTEM -->
                <div class="dropdown">
                    <button class="btn btn-sm border-0 fw-semibold text-navy d-flex align-items-center gap-1.5 px-3 py-1.5 rounded-pill nav-pill-btn {{ Request::is('settings*') || Request::is('users*') || Request::is('activities*') ? 'active-pill bg-secondary text-white shadow-sm' : 'bg-light hover-light' }}" 
                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-gear-fill {{ Request::is('settings*') || Request::is('users*') || Request::is('activities*') ? 'text-white' : 'text-secondary' }}"></i> 
                        <span>Pengaturan</span> 
                        <i class="bi bi-chevron-down opacity-75 small" style="font-size: 0.65rem;"></i>
                    </button>
                    <ul class="dropdown-menu border-0 shadow-lg rounded-3 py-1.5 mt-2">
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('users*') ? 'active fw-bold' : '' }}" href="{{ Route::has('users.index') ? route('users.index') : '#' }}"><i class="bi bi-people-fill text-primary"></i> Manajemen Pengguna</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('settings') ? 'active fw-bold' : '' }}" href="{{ Route::has('settings.index') ? route('settings.index') : '#' }}"><i class="bi bi-sliders text-info"></i> Pengaturan Sistem</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('reports/settings*') ? 'active fw-bold' : '' }}" href="{{ Route::has('reports.settings.index') ? route('reports.settings.index') : '#' }}"><i class="bi bi-printer-fill text-success"></i> Pengaturan Cetak</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('settings/backups*') ? 'active fw-bold' : '' }}" href="{{ Route::has('settings.backups.index') ? route('settings.backups.index') : '#' }}"><i class="bi bi-database-fill-gear text-warning"></i> Backup & Restore</a></li>
                        <li><a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 {{ Request::is('activities*') ? 'active fw-bold' : '' }}" href="{{ Route::has('activities.index') ? route('activities.index') : '#' }}"><i class="bi bi-journal-text text-danger"></i> Log Aktivitas</a></li>
                    </ul>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
