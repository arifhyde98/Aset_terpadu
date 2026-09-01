@extends('layouts.app')

@section('title', 'Pengaturan Web')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold text-navy mb-1">Pengaturan Konten</h3>
            <p class="text-secondary mb-0 small">Kelola teks dan gambar untuk Landing Page dan Halaman Login.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div class="row g-4 mb-4">
            <!-- Top Column: General Branding -->
            <div class="col-12">
                <div class="admin-card p-4">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-info bg-opacity-10 text-info rounded-3 p-2 me-3">
                            <i class="bi bi-patch-check fs-4"></i>
                        </div>
                        <h5 class="fw-bold text-navy mb-0">Branding Umum</h5>
                    </div>
                    
                    <div class="row g-3">
                        @php
                            $labelMap = [
                                'site_logo' => 'Logo Kiri (Lambang Daerah / Utama)',
                                'site_logo_right' => 'Logo Kanan (Logo Instansi / SIPAT / BPKAD)',
                                'site_name' => 'Nama Aplikasi / Portal',
                                'site_subtitle' => 'Subtitle / Slogan Instansi',
                            ];
                        @endphp
                        @foreach($settings['general'] ?? [] as $setting)
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-dark small text-uppercase">
                                    {{ $labelMap[$setting->key] ?? str_replace('_', ' ', $setting->key) }}
                                </label>
                                @if($setting->type === 'text')
                                    <input type="text" name="settings[{{ $setting->key }}]" class="form-control rounded-3" value="{{ $setting->value }}">
                                @elseif($setting->type === 'image')
                                    <div class="d-flex align-items-center gap-3">
                                        @if($setting->value)
                                            <div class="bg-light p-1 rounded-3 border shadow-sm flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                                                <img src="{{ \App\Models\Setting::imageUrl($setting->value) }}" class="rounded-2" style="max-height: 44px; max-width: 44px; object-fit: contain;">
                                            </div>
                                        @endif
                                        <div class="flex-grow-1">
                                            <input type="file" name="settings[{{ $setting->key }}]" class="form-control rounded-3" accept="image/*">
                                            <small class="text-muted" style="font-size: 0.75rem;">Format: PNG, JPG, JPEG, SVG. Biarkan kosong jika tidak diubah.</small>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Landing Page -->
            <div class="col-xl-6">
                <div class="admin-card h-100 p-4">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 me-3">
                            <i class="bi bi-browser-safari fs-4"></i>
                        </div>
                        <h5 class="fw-bold text-navy mb-0">Halaman Landing</h5>
                    </div>

                    @foreach($settings['landing'] ?? [] as $setting)
                        <div class="mb-4">
                            <label class="form-label fw-semibold text-dark small text-uppercase">{{ str_replace('_', ' ', $setting->key) }}</label>
                            
                            @if($setting->type === 'text')
                                <input type="text" name="settings[{{ $setting->key }}]" class="form-control rounded-3" value="{{ $setting->value }}">
                            @elseif($setting->type === 'textarea')
                                <textarea name="settings[{{ $setting->key }}]" class="form-control rounded-3" rows="3">{{ $setting->value }}</textarea>
                            @elseif($setting->type === 'image')
                                <div class="d-flex align-items-center gap-3">
                                    @if($setting->value)
                                        <img src="{{ \App\Models\Setting::imageUrl($setting->value) }}" class="rounded-3 border shadow-sm" style="width: 80px; height: 50px; object-fit: cover;">
                                    @endif
                                    <input type="file" name="settings[{{ $setting->key }}]" class="form-control rounded-3">
                                </div>
                                <small class="text-muted mt-1 d-block">Biarkan kosong jika tidak ingin mengubah gambar.</small>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Right Column: Login Page -->
            <div class="col-xl-6">
                <div class="admin-card h-100 p-4">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-success bg-opacity-10 text-success rounded-3 p-2 me-3">
                            <i class="bi bi-shield-lock fs-4"></i>
                        </div>
                        <h5 class="fw-bold text-navy mb-0">Halaman Login</h5>
                    </div>

                    @foreach($settings['login'] ?? [] as $setting)
                        @continue($setting->key === 'login_logo_icon')

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-dark small text-uppercase">{{ str_replace('_', ' ', $setting->key) }}</label>
                            
                            @if($setting->type === 'text')
                                <input type="text" name="settings[{{ $setting->key }}]" class="form-control rounded-3" value="{{ $setting->value }}">
                            @elseif($setting->type === 'textarea')
                                <textarea name="settings[{{ $setting->key }}]" class="form-control rounded-3" rows="3">{{ $setting->value }}</textarea>
                            @elseif($setting->type === 'image')
                                <div class="d-flex align-items-center gap-3">
                                    @if($setting->value)
                                        <img src="{{ \App\Models\Setting::imageUrl($setting->value) }}" class="rounded-3 border shadow-sm" style="width: 80px; height: 50px; object-fit: cover;">
                                    @endif
                                    <input type="file" name="settings[{{ $setting->key }}]" class="form-control rounded-3">
                                </div>
                                <small class="text-muted mt-1 d-block">Biarkan kosong jika tidak ingin mengubah gambar.</small>
                            @endif
                        </div>
                    @endforeach

                    <div class="mt-auto pt-4 border-top">
                        <button type="submit" class="btn btn-primary rounded-3 w-100 py-2 fw-bold shadow-sm">
                            <i class="bi bi-check-circle me-2"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
