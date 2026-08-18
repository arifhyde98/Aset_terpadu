<!-- Bottom Navigation for Mobile -->
<nav class="bottom-nav d-md-none">
    <div class="bottom-nav-container">
        <a href="{{ route('home') }}" class="bottom-nav-item {{ Request::is('home') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2{{ Request::is('home') ? '-fill' : '' }}"></i>
            <span>Home</span>
        </a>
        
        <a href="{{ route('vehicles.index') }}" class="bottom-nav-item {{ Request::is('vehicles*') ? 'active' : '' }}">
            <i class="bi bi-car-front{{ Request::is('vehicles*') ? '-fill' : '' }}"></i>
            <span>Kendaraan</span>
        </a>
        
        <a href="{{ route('maintenance.index') }}" class="bottom-nav-item {{ Request::is('maintenance*') ? 'active' : '' }}">
            <i class="bi bi-tools"></i>
            <span>Maintenance</span>
        </a>

        @if(auth()->check() && auth()->user()?->role === \App\Enums\UserRole::SUPERADMIN)
        <a href="{{ route('users.index') }}" class="bottom-nav-item {{ Request::is('users*') ? 'active' : '' }}">
            <i class="bi bi-people{{ Request::is('users*') ? '-fill' : '' }}"></i>
            <span>Pengguna</span>
        </a>
        @else
        <a href="{{ route('reports.index') }}" class="bottom-nav-item {{ Request::is('reports*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-bar-graph{{ Request::is('reports*') ? '-fill' : '' }}"></i>
            <span>Laporan</span>
        </a>
        @endif
        
        <a href="javascript:void(0);" class="bottom-nav-item" id="mobileMenuToggle" data-bs-toggle="offcanvas" data-bs-target="#mobileMenuOffcanvas">
            <i class="bi bi-list"></i>
            <span>Menu</span>
        </a>
    </div>
</nav>

<!-- Offcanvas Mobile Menu (For items that don't fit in bottom nav) -->
<div class="offcanvas offcanvas-bottom" tabindex="-1" id="mobileMenuOffcanvas" aria-labelledby="mobileMenuOffcanvasLabel" style="height: 60vh; border-top-left-radius: 1rem; border-top-right-radius: 1rem;">
    <div class="offcanvas-header pb-0 border-bottom">
        <h5 class="offcanvas-title fw-bold" id="mobileMenuOffcanvasLabel">Menu Lainnya</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="list-group list-group-flush">
            <!-- Pindahkan link dari sidebar yang tidak muat ke sini -->
            @if(auth()->user()?->role !== \App\Enums\UserRole::OPD)
                <div class="fw-bold text-muted small mb-2 mt-3 text-uppercase">Master Data</div>
                <a href="{{ route('vehicle-types.index') }}" class="list-group-item list-group-item-action border-0 {{ Request::is('vehicle-types*') ? 'active rounded' : '' }}">
                    <i class="bi bi-grid me-2"></i> Jenis Kendaraan
                </a>
                <a href="{{ route('opds.index') }}" class="list-group-item list-group-item-action border-0 {{ Request::is('opds*') ? 'active rounded' : '' }}">
                    <i class="bi bi-building me-2"></i> OPD / Instansi
                </a>
            @endif
            
            <div class="fw-bold text-muted small mb-2 mt-3 text-uppercase">Akun</div>
            <a href="#" class="list-group-item list-group-item-action border-0">
                <i class="bi bi-person-circle me-2"></i> Profil Saya
            </a>
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="list-group-item list-group-item-action text-danger border-0">
                <i class="bi bi-box-arrow-left me-2"></i> Logout
            </a>
        </div>
    </div>
</div>
