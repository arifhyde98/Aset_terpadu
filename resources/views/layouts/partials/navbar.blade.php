<!-- Top Navbar -->
<nav class="navbar navbar-expand-lg navbar-admin rounded-3 mb-4">
    <div class="container-fluid px-0">
        <div class="d-flex align-items-center gap-3">
            <button type="button" id="sidebarCollapse" class="btn btn-light rounded-3 shadow-sm border">
                <i class="bi bi-list"></i>
            </button>
            <h5 class="fw-bold text-navy mb-0 d-none d-md-block">@yield('title', 'Admin Dashboard')</h5>
        </div>
        
        <div class="ms-auto d-flex align-items-center gap-3">
            @guest
                <!-- Guest Links -->
            @else
                <!-- Notification -->
                <a href="#" class="text-secondary position-relative">
                    <i class="bi bi-bell fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                        <span class="visually-hidden">New alerts</span>
                    </span>
                </a>

                <!-- Theme Toggle -->
                <button type="button" id="themeToggle" class="btn btn-light rounded-3 shadow-sm border text-secondary px-2 py-1">
                    <i class="bi bi-moon-stars fs-5" id="themeIcon"></i>
                </button>
                
                <div class="vr mx-1"></div>

                <!-- Profile Dropdown -->
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark fw-medium" data-bs-dropdown-toggle="dropdown" data-bs-toggle="dropdown">
                        <img src="{{ Auth::user()->avatar_url }}" 
                             alt="{{ Auth::user()->name }}" 
                             class="rounded-circle me-2 border shadow-sm" 
                             style="width: 35px; height: 35px; object-fit: cover;">
                        <div class="d-none d-md-block text-start lh-1">
                            <div class="fw-bold" style="font-size: 0.9rem;">{{ Auth::user()->name }}</div>
                            <small class="text-secondary" style="font-size: 0.75rem;">{{ Auth::user()->role->label() }}</small>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-2 rounded-4" style="min-width: 220px; animation: fadeIn 0.2s ease-in-out;">
                        <li class="px-3 py-3 border-bottom mb-2 text-center bg-light bg-gradient rounded-top-4">
                            <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="rounded-circle mb-2 border border-2 border-white shadow-sm" style="width: 60px; height: 60px; object-fit: cover;">
                            <div class="fw-bold text-dark fs-6">{{ Auth::user()->name }}</div>
                            <div class="text-secondary small">{{ Auth::user()->email }}</div>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 px-3 d-flex align-items-center fw-medium rounded-3 mx-2 my-1" href="{{ route('profile.index') }}" style="transition: all 0.2s;">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle me-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 36px; height: 36px;">
                                    <i class="bi bi-person-badge-fill fs-5"></i>
                                </div>
                                Edit Profil
                            </a>
                        </li>
                        @if(Auth::user()->role === \App\Enums\UserRole::SUPERADMIN)
                            <li>
                                <a class="dropdown-item py-2 px-3 d-flex align-items-center fw-medium rounded-3 mx-2 my-1" href="{{ route('settings.index') }}" style="transition: all 0.2s;">
                                    <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle me-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 36px; height: 36px;">
                                        <i class="bi bi-gear-fill fs-5"></i>
                                    </div>
                                    Pengaturan
                                </a>
                            </li>
                        @endif
                        <li><hr class="dropdown-divider my-2 opacity-25"></li>
                        <li>
                            <a class="dropdown-item py-2 px-3 d-flex align-items-center text-danger fw-bold rounded-3 mx-2 mb-2" href="{{ route('logout') }}"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="transition: all 0.2s;">
                                <div class="bg-danger bg-opacity-10 text-danger rounded-circle me-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 36px; height: 36px;">
                                    <i class="bi bi-box-arrow-right fs-5 fw-bold"></i>
                                </div>
                                Signout
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </li>
                    </ul>
                </div>
            @endguest
        </div>
    </div>
</nav>
