<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | E-RANDIS Pemerintah Daerah</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.ico') }}">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    @vite(['resources/css/app.scss', 'resources/js/app.js'])

    <style>
        .login-side-bg {
            @php
                $loginBg = \App\Models\Setting::get('login_bg_image', 'images/hero-illustration.png');
                $bgUrl = \App\Models\Setting::imageUrl($loginBg);
            @endphp
            background: linear-gradient(rgba(30, 64, 175, 0.85), rgba(30, 58, 138, 0.95)), url('{{ $bgUrl }}');
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1.5rem;
        }
        .login-form-container {
            background-color: var(--bg-color);
            transition: all 0.3s ease;
        }
        @media (max-width: 991.98px) {
            .login-form-container {
                padding: 2rem 1.5rem !important;
            }
        }
    </style>
</head>
<body id="theme-root">
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.getElementById('theme-root').setAttribute('data-theme', savedTheme);
        })();
    </script>

    <div class="container-fluid p-0">
        <div class="row g-0 min-vh-100">
            
            <!-- Left Section: Visual Branding -->
            <div class="col-lg-6 d-none d-lg-flex login-side-bg flex-column justify-content-center align-items-center p-5 text-white">
                <div class="glass-card p-5 text-center shadow-lg" style="max-width: 500px;">
                    <div class="d-flex justify-content-center mb-4">
                        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow p-2" style="width: 70px; height: 70px;">
                            @php
                                $siteLogo = \App\Models\Setting::get('site_logo');
                            @endphp

                            @if($siteLogo)
                                <img src="{{ \App\Models\Setting::imageUrl($siteLogo) }}" alt="Logo" style="max-width: 48px; max-height: 48px; object-fit: contain;">
                            @else
                                <img src="{{ asset('images/hero-illustration.png') }}" alt="Logo" style="max-width: 48px; max-height: 48px; object-fit: contain;">
                            @endif
                        </div>
                    </div>
                    <h1 class="fw-bold mb-2">{{ \App\Models\Setting::get('login_title', 'E-RANDIS') }}</h1>
                    <p class="fs-5 opacity-75 mb-4">{{ \App\Models\Setting::get('login_subtitle', 'Sistem Monitoring Kendaraan Dinas') }}</p>
                    <hr class="border-white opacity-25 my-4">
                    <p class="small opacity-50 mb-0">
                        {{ \App\Models\Setting::get('login_description', 'Platform digital terintegrasi untuk efisiensi, transparansi, dan akuntabilitas manajemen aset daerah.') }}
                    </p>
                </div>
            </div>

            <!-- Right Section: Login Form -->
            <div class="col-lg-6 login-form-container d-flex align-items-center justify-content-center p-4 p-md-5">
                <div class="w-100" style="max-width: 420px;">
                    
                    <div class="mb-5 text-center text-lg-start">
                        <div class="d-lg-none mb-4">
                            @php
                                $siteLogo = \App\Models\Setting::get('site_logo');
                            @endphp

                            @if($siteLogo)
                                <img src="{{ \App\Models\Setting::imageUrl($siteLogo) }}" alt="Logo" class="mb-2" style="height: 58px; width: auto; max-width: 120px; object-fit: contain;">
                            @else
                                <img src="{{ asset('images/hero-illustration.png') }}" alt="Logo" class="mb-2" style="height: 58px; width: auto; max-width: 120px; object-fit: contain;">
                            @endif
                            <h2 class="fw-bold text-navy mt-2">E-RANDIS</h2>
                        </div>
                        <h2 class="fw-bold text-navy mb-2 fs-2">Selamat Datang</h2>
                        <p class="text-secondary small-mobile">Masukkan kredensial Anda untuk melanjutkan ke dashboard.</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger border-0 border-start border-danger border-4 rounded-3 shadow-sm mb-4">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-exclamation-circle-fill fs-5 me-3"></i>
                                <div class="small fw-medium">Email atau kata sandi salah.</div>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-secondary">ALAMAT EMAIL</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-secondary"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control form-control-lg fs-6 border-start-0 bg-light" placeholder="admin@pemda.go.id" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-1">
                                <label class="form-label fw-semibold small text-secondary">KATA SANDI</label>
                                <a href="#" class="small text-decoration-none fw-bold">Lupa Sandi?</a>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-secondary"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" class="form-control form-control-lg fs-6 border-start-0 bg-light" placeholder="••••••••" required>
                            </div>
                        </div>

                        <div class="mb-4 d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                <label class="form-check-label small text-secondary" for="remember">Ingat Saya</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold py-3 shadow-sm mb-4">
                            MASUK SEKARANG <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </form>

                    <div class="text-center">
                        <a href="{{ url('/') }}" class="text-secondary text-decoration-none small">
                            <i class="bi bi-house-door me-1"></i> Kembali ke Beranda
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</body>
</html>
