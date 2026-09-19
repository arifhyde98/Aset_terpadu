@extends('layouts.app')

@section('title', 'Master Data')

@section('content')
<div class="container-fluid px-0">
    
    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
        <div class="mb-3 mb-md-0">
            <h3 class="fw-bold text-navy mb-1">Master Data</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Master Data</li>
                </ol>
            </nav>
        </div>
        <div class="text-md-end">
            <p class="text-secondary mb-0 small">
                Pengelolaan data utama sistem <strong class="text-navy">E-RANDIS</strong>
            </p>
        </div>
    </div>

    <!-- SUMMARY SECTION -->
    <div class="row g-3 mb-5">
        <div class="col-sm-6 col-lg-3">
            <x-stat-card title="Total Kendaraan" :value="$stats['total_kendaraan']" icon="car-front-fill" gradient="primary" />
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat-card title="Total Pengguna" :value="$stats['total_pengguna']" icon="people-fill" gradient="success" />
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat-card title="Total OPD" :value="$stats['total_opd']" icon="building-fill" gradient="info" />
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat-card title="Total Sopir" :value="$stats['total_sopir']" icon="person-badge-fill" gradient="warning" />
        </div>
    </div>

    <!-- MASTER MENU DISPLAY -->
    <h5 class="fw-bold text-navy mb-4">Navigasi Data Master</h5>
    
    <div class="row g-4">
        <!-- 1. Jenis Kendaraan -->
        <div class="col-md-6 col-lg-3">
            <div class="admin-card h-100 p-4 text-center border-0 shadow-sm transition-all">
                <div class="bg-light text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                    <i class="bi bi-grid fs-1"></i>
                </div>
                <h5 class="fw-bold text-navy">Jenis Kendaraan</h5>
                <p class="text-secondary small mb-4">Pengelolaan kategori dan tipe armada kendaraan dinas.</p>
                <a href="{{ route('vehicle-types.index') }}" class="btn btn-outline-primary btn-sm rounded-pill px-4">Buka Data</a>
            </div>
        </div>

        <!-- 2. Data Pengguna -->
        <div class="col-md-6 col-lg-3">
            <div class="admin-card h-100 p-4 text-center border-0 shadow-sm transition-all">
                <div class="bg-light text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                    <i class="bi bi-people fs-1"></i>
                </div>
                <h5 class="fw-bold text-navy">Data Pengguna</h5>
                <p class="text-secondary small mb-4">Pengelolaan data pengguna dan penanggung jawab kendaraan.</p>
                <a href="#" class="btn btn-outline-primary btn-sm rounded-pill px-4">Buka Data</a>
            </div>
        </div>

        <!-- 3. OPD / Instansi -->
        <div class="col-md-6 col-lg-3">
            <div class="admin-card h-100 p-4 text-center border-0 shadow-sm transition-all">
                <div class="bg-light text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                    <i class="bi bi-building fs-1"></i>
                </div>
                <h5 class="fw-bold text-navy">OPD / Instansi</h5>
                <p class="text-secondary small mb-4">Pengelolaan data Organisasi Perangkat Daerah dan unit kerja.</p>
                <a href="{{ route('opds.index') }}" class="btn btn-outline-primary btn-sm rounded-pill px-4">Buka Data</a>
            </div>
        </div>

        <!-- 4. Pusat Rekonsiliasi e-BMD -->
        <div class="col-md-6 col-lg-3">
            <div class="admin-card h-100 p-4 text-center border-0 shadow-sm transition-all">
                <div class="bg-light text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                    <i class="bi bi-arrow-repeat fs-1"></i>
                </div>
                <h5 class="fw-bold text-navy">Rekonsiliasi e-BMD</h5>
                <p class="text-secondary small mb-4">Pusat rekonsiliasi & sinkronisasi e-BMD terpadu lintas seluruh modul aset.</p>
                <a href="{{ route('rekon-ebmd.index') }}" class="btn btn-outline-primary btn-sm rounded-pill px-4">Buka Fitur</a>
            </div>
        </div>
    </div>

    <!-- Master Landing Page Setting (Optional/Extended) -->
    <div class="admin-card mt-5 p-4 border-0 shadow-sm">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-secondary bg-opacity-10 text-secondary p-3 rounded-3 lh-1">
                <i class="bi bi-window-sidebar fs-3"></i>
            </div>
            <div class="flex-grow-1">
                <h6 class="fw-bold text-navy mb-1">Landing Page Setting</h6>
                <p class="text-secondary mb-0 small">Kustomisasi informasi publik pada halaman utama portal E-RANDIS.</p>
            </div>
            <div>
                <a href="#" class="btn btn-light border text-navy fw-medium">Konfigurasi <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>

</div>

<style>
    .transition-all {
        transition: all 0.3s ease;
    }
    .admin-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08) !important;
    }
</style>
@endsection
