<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'E-RANDIS PHP') }} - Admin Dashboard</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.ico') }}">

    @vite(['resources/css/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light" id="theme-root">
    <script>
        // Pre-initialization theme check to avoid flicker
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
            document.getElementById('theme-root').setAttribute('data-theme', savedTheme);
            if(savedTheme === 'dark') {
                document.body.classList.remove('bg-light');
            }
        })();
    </script>
    <div class="wrapper">
        @include('layouts.partials.sidebar')
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Page Content -->
        <div id="content">
            @include('layouts.partials.navbar')

            <!-- Main Content Area -->
            <main class="animate-on-scroll">
                @include('layouts.partials.alerts')

                @auth
                    @if(auth()->user()->role === \App\Enums\UserRole::OPD && is_null(auth()->user()->opd_id))
                        <div class="container-fluid">
                            <div class="alert alert-danger border-0 shadow-sm rounded-3 d-flex align-items-center mb-4" role="alert">
                                <i class="bi bi-exclamation-octagon-fill fs-4 me-3"></i>
                                <div>
                                    <h6 class="alert-heading fw-bold mb-1">Akses Dibatasi (Lock Mode)</h6>
                                    <p class="mb-0 small">Akun Anda belum terhubung dengan instansi OPD mana pun. Silakan hubungi <strong>Superadmin</strong> untuk menautkan akun Anda agar dapat mengelola data kendaraan.</p>
                                </div>
                            </div>
                        </div>
                    @endif
                @endauth

                @yield('content')
            </main>
            
            @include('layouts.partials.footer')
        </div>
    </div>

    @include('layouts.partials.bottom-nav')

    @stack('modals')
    @stack('scripts')

    <!-- Theme Toggle & UI Interactivity JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Intersection Observer for fade-in animations
            const observerOptions = { threshold: 0.1 };
            const scrollObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in-up');
                        scrollObserver.unobserve(entry.target);
                    }
                });
            }, observerOptions);
            
            document.querySelectorAll('.animate-on-scroll').forEach(el => {
                scrollObserver.observe(el);
            });

            // 2. Theme Logic
            const themeToggle = document.getElementById('themeToggle');
            const themeIcon = document.getElementById('themeIcon');
            const root = document.getElementById('theme-root');
            
            function updateTheme(theme) {
                document.documentElement.setAttribute('data-bs-theme', theme);
                root.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
                
                if (theme === 'dark') {
                    if(themeIcon) themeIcon.classList.replace('bi-moon-stars', 'bi-sun');
                    document.body.classList.remove('bg-light');
                } else {
                    if(themeIcon) themeIcon.classList.replace('bi-sun', 'bi-moon-stars');
                    document.body.classList.add('bg-light');
                }
            }
            
            const currentTheme = localStorage.getItem('theme') || 'light';
            updateTheme(currentTheme);
            
            if(themeToggle) {
                themeToggle.addEventListener('click', () => {
                    const newTheme = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                    updateTheme(newTheme);
                });
            }
            
            // 3. Sidebar Logic (Mobile Drawer + Compact Desktop Mode)
            const sidebarCollapse = document.getElementById('sidebarCollapse');
            const sidebarToggleCompact = document.getElementById('sidebarToggleCompact');
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            const overlay = document.getElementById('sidebarOverlay');

            // Initialize Tooltips
            let tooltipList = [];
            function initTooltips() {
                tooltipList.forEach(t => t.dispose());
                const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
            }
            initTooltips();
            
            function setCompactMode(isCompact) {
                if (!sidebar) return;
                if (isCompact) {
                    sidebar.classList.add('compact');
                    document.body.classList.add('sidebar-compact');
                    localStorage.setItem('sidebarMode', 'compact');
                    if (window.innerWidth > 768) {
                        content.style.marginLeft = '70px';
                    }
                } else {
                    sidebar.classList.remove('compact');
                    document.body.classList.remove('sidebar-compact');
                    localStorage.setItem('sidebarMode', 'normal');
                    if (window.innerWidth > 768) {
                        content.style.marginLeft = '250px';
                    }
                }
                initTooltips();
            }

            // Restore Compact Mode State
            const savedSidebarMode = localStorage.getItem('sidebarMode') || 'normal';
            if (window.innerWidth > 768 && savedSidebarMode === 'compact') {
                setCompactMode(true);
            }

            if (sidebarToggleCompact) {
                sidebarToggleCompact.addEventListener('click', function () {
                    const isCurrentlyCompact = sidebar.classList.contains('compact');
                    setCompactMode(!isCurrentlyCompact);
                });
            }
            
            function toggleSidebarMobile() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('show');
                document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : 'auto';
            }

            if (sidebarCollapse) {
                sidebarCollapse.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        toggleSidebarMobile();
                    } else {
                        const isCurrentlyCompact = sidebar.classList.contains('compact');
                        setCompactMode(!isCurrentlyCompact);
                    }
                });
            }

            if (overlay) {
                overlay.addEventListener('click', toggleSidebarMobile);
            }
            
            function handleResize() {
                if (!sidebar) return;
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('show');
                    content.style.marginLeft = '0';
                    document.body.style.overflow = 'auto';
                } else {
                    sidebar.classList.remove('active');
                    const isCompact = sidebar.classList.contains('compact');
                    content.style.marginLeft = isCompact ? '70px' : '250px';
                }
            }

            window.addEventListener('resize', handleResize);
            handleResize(); // Initial check

            // 4. Auto-close for Global Alerts
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });

            // 5. Global Delete Confirmation (SweetAlert2)
            document.body.addEventListener('submit', function(e) {
                if (e.target.classList.contains('delete-confirm')) {
                    e.preventDefault();
                    const form = e.target;
                    
                    Swal.fire({
                        title: 'Apakah Anda yakin?',
                        text: "Data yang dihapus tidak dapat dikembalikan!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#1e40af', 
                        cancelButtonColor: '#ef4444', 
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal',
                        reverseButtons: true,
                        background: root.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                        color: root.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
