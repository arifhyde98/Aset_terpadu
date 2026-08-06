<!-- Sidebar -->
<nav id="sidebar" class="d-none d-md-block">
    <div class="sidebar-header d-flex align-items-center gap-2">
        @php
            $siteLogo = \App\Models\Setting::get('site_logo');
        @endphp

        @if($siteLogo)
            <img src="{{ \App\Models\Setting::imageUrl($siteLogo) }}" alt="Logo" class="bg-white rounded-circle p-1" style="width: 38px; height: 38px; object-fit: contain;">
        @else
            <img src="{{ asset('images/hero-illustration.png') }}" alt="Logo" class="bg-white rounded-circle p-1" style="width: 38px; height: 38px; object-fit: contain;">
        @endif
        <div>
            <div class="fw-bold fs-5 lh-1 text-white">E-RANDIS</div>
            <small class="text-white-50 d-block mt-1" style="font-size: 0.65rem; line-height: 1.2;">
                @if(auth()->user()->role === \App\Enums\UserRole::OPD)
                    {{ auth()->user()->opd?->singkatan ?: auth()->user()->opd?->nama ?: 'Instansi Tidak Ditemukan' }}
                @else
                    {{ auth()->user()->role->label() }}
                @endif
            </small>
        </div>
    </div>

    <ul class="list-unstyled components">
        <li class="{{ Request::is('home') ? 'active' : '' }}">
            <a href="{{ route('home') }}"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        </li>
        
        <li class="mt-4 mb-2 ps-3">
            <small class="text-uppercase fw-bold text-white-50" style="font-size: 0.7rem; letter-spacing: 0.5px;">Manajemen Data</small>
        </li>
        
        <li class="{{ Request::is('vehicles*') ? 'active' : '' }}">
            <a href="{{ route('vehicles.index') }}"><i class="bi bi-car-front"></i> Data Kendaraan</a>
        </li>

        <li>
            <a href="#"><i class="bi bi-people"></i> Pengguna Kendaraan</a>
        </li>

        <li>
            <a href="#"><i class="bi bi-clock-history"></i> Riwayat Penggunaan</a>
        </li>

        <li class="mt-4 mb-2 ps-3">
            <small class="text-uppercase fw-bold text-white-50" style="font-size: 0.7rem; letter-spacing: 0.5px;">Operasional</small>
        </li>

        @if(auth()->user()->role !== \App\Enums\UserRole::OPD)
        <li class="{{ Request::is('master-data*', 'opds*', 'vehicle-types*') ? 'active' : '' }}">
            <a href="javascript:void(0)" 
               class="has-submenu {{ Request::is('master-data*', 'opds*', 'vehicle-types*') ? '' : 'collapsed' }}"
               data-bs-toggle="collapse" 
               data-bs-target="#masterDataSubmenu" 
               aria-expanded="{{ Request::is('master-data*', 'opds*', 'vehicle-types*') ? 'true' : 'false' }}"
               role="button">
                <i class="bi bi-database"></i> Master Data
            </a>
            <ul class="collapse list-unstyled {{ Request::is('master-data*', 'opds*', 'vehicle-types*') ? 'show' : '' }}" id="masterDataSubmenu">
                
                <li class="{{ Request::is('vehicle-types*') ? 'active' : '' }}">
                    <a href="{{ route('vehicle-types.index') }}"><i class="bi bi-grid small me-2"></i> Jenis Kendaraan</a>
                </li>
                <li>
                    <a href="#"><i class="bi bi-people small me-2"></i> Data Pengguna</a>
                </li>
                <li class="{{ Request::is('opds*') ? 'active' : '' }}">
                    <a href="{{ route('opds.index') }}"><i class="bi bi-building small me-2"></i> OPD / Instansi</a>
                </li>
                <li>
                    <a href="#"><i class="bi bi-check-circle small me-2"></i> Status Kendaraan</a>
                </li>
            </ul>
        </li>
        @endif
        
        <li class="{{ Request::is('maintenance*') ? 'active' : '' }}">
            <a href="{{ route('maintenance.index') }}"><i class="bi bi-tools"></i> Maintenance</a>
        </li>

        <li class="{{ Request::is('reports*') ? 'active' : '' }}">
            <a href="{{ route('reports.index') }}"><i class="bi bi-file-earmark-bar-graph"></i> Laporan</a>
        </li>

        @if(auth()->user()->role === \App\Enums\UserRole::SUPERADMIN)
        <li class="mt-4 mb-2 ps-3">
            <small class="text-uppercase fw-bold text-white-50" style="font-size: 0.7rem; letter-spacing: 0.5px;">Sistem</small>
        </li>

        <li class="{{ Request::is('users*') ? 'active' : '' }}">
            <a href="{{ route('users.index') }}">
                <i class="bi bi-people-fill"></i> Manajemen Pengguna
            </a>
        </li>

        <li class="{{ Request::is('activities*') ? 'active' : '' }}">
            <a href="{{ route('activities.index') }}">
                <i class="bi bi-shield-lock"></i> Audit Log
            </a>
        </li>

        <li class="{{ request()->routeIs('settings.*') ? 'active' : '' }}">
            <a href="{{ route('settings.index') }}">
                <i class="bi bi-gear"></i> Pengaturan
            </a>
        </li>
        @endif

        <li>
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="text-danger">
                <i class="bi bi-box-arrow-left text-danger"></i> Logout
            </a>
        </li>
    </ul>
</nav>
