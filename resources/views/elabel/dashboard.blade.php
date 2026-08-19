@extends('layouts.app')

@section('title', 'Dashboard eLABEL')

@section('content')
<div class="container-fluid px-0">

    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">SIPAT Terpadu</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Dashboard eLABEL</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0">Dashboard Manajemen Arsip (eLABEL)</h4>
        </div>
        <div class="action-toolbar d-flex flex-wrap gap-2">
            <a href="{{ route('elabel.bpkb.index') }}" class="btn btn-primary shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-card-heading"></i> Katalog BPKB
            </a>
            <a href="{{ route('elabel.sertifikat.index') }}" class="btn btn-success shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-patch-check"></i> Sertifikat Tanah
            </a>
            <a href="{{ route('elabel.peminjaman.index') }}" class="btn btn-warning text-dark shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-clock-history"></i> Peminjaman Scan
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- TOP STAT CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase fw-semibold text-secondary fs-7 mb-1">Total Box Fisik</div>
                        <h2 class="fw-extrabold text-navy mb-1">{{ number_format($boxCount) }}</h2>
                        <div class="small text-secondary">
                            <span class="text-primary fw-bold">{{ $bpkbBoxCount }}</span> BPKB · 
                            <span class="text-success fw-bold">{{ $sertifikatBoxCount }}</span> Sertifikat · 
                            <span class="text-info fw-bold">{{ $suratPenyerahanBoxCount }}</span> Surat
                        </div>
                    </div>
                    <div class="rounded-4 bg-primary bg-opacity-10 p-3 text-primary fs-3">
                        <i class="bi bi-archive-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase fw-semibold text-secondary fs-7 mb-1">BPKB Kendaraan</div>
                        <h2 class="fw-extrabold text-navy mb-1">{{ number_format($bpkbCount) }}</h2>
                        <div class="small text-secondary">
                            <span class="text-success fw-bold">{{ $bpkbAvailableCount }}</span> Tersedia · 
                            <span class="text-danger fw-bold">{{ $bpkbDeletedCount }}</span> Keluar
                        </div>
                    </div>
                    <div class="rounded-4 bg-info bg-opacity-10 p-3 text-info fs-3">
                        <i class="bi bi-card-heading"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase fw-semibold text-secondary fs-7 mb-1">Sertifikat Tanah</div>
                        <h2 class="fw-extrabold text-navy mb-1">{{ number_format($sertifikatCount) }}</h2>
                        <div class="small text-success fw-semibold">
                            <i class="bi bi-check-all"></i> Terarsip Lengkap
                        </div>
                    </div>
                    <div class="rounded-4 bg-success bg-opacity-10 p-3 text-success fs-3">
                        <i class="bi bi-patch-check-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase fw-semibold text-secondary fs-7 mb-1">Peminjaman / Request</div>
                        <h2 class="fw-extrabold text-navy mb-1">{{ number_format($loanCount) }}</h2>
                        <div class="small text-secondary">
                            <span class="text-warning fw-bold">{{ $loanApprovedCount }}</span> Disetujui
                        </div>
                    </div>
                    <div class="rounded-4 bg-warning bg-opacity-10 p-3 text-warning fs-3">
                        <i class="bi bi-file-earmark-pdf-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PROGRESS & METRICS SECTION -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-navy mb-0">Box Terisi</h6>
                        <span class="badge bg-primary rounded-pill">{{ $boxFilledPercent }}%</span>
                    </div>
                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar bg-primary rounded-pill" role="progressbar" style="width: {{ $boxFilledPercent }}%" aria-valuenow="{{ $boxFilledPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="small text-secondary">
                        <strong class="text-dark">{{ $filledBoxCount }}</strong> dari <strong>{{ $boxCount }}</strong> Box penyimpanan terisi dokumen.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-navy mb-0">BPKB Aktif (Tersedia)</h6>
                        <span class="badge bg-success rounded-pill">{{ $bpkbActivePercent }}%</span>
                    </div>
                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar bg-success rounded-pill" role="progressbar" style="width: {{ $bpkbActivePercent }}%" aria-valuenow="{{ $bpkbActivePercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="small text-secondary">
                        <strong class="text-dark">{{ $bpkbAvailableCount }}</strong> BPKB berada di brankas fisik dari total {{ $bpkbCount }} BPKB.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-navy mb-0">Rasio Peminjaman Scan</h6>
                        <span class="badge bg-warning text-dark rounded-pill">{{ $loanPercent }}%</span>
                    </div>
                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar bg-warning rounded-pill" role="progressbar" style="width: {{ $loanPercent }}%" aria-valuenow="{{ $loanPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="small text-secondary">
                        <strong class="text-dark">{{ $loanCount }}</strong> permohonan scan diajukan oleh pemohon/admin.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RECENT ACTIVITY LOGS -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-navy mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-clock-history text-primary"></i> Riwayat Aktivitas eLABEL Terbaru
            </h6>
            @if($oldActivity180Count > 0)
                <form action="{{ route('elabel.dashboard.cleanup-logs') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Bersihkan {{ $oldActivity180Count }} log yang berusia lebih dari 180 hari?')">
                        <i class="bi bi-trash"></i> Bersihkan Log Lama ({{ $oldActivity180Count }})
                    </button>
                </form>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3 text-secondary small fw-semibold">Pengguna</th>
                        <th class="py-3 text-secondary small fw-semibold">Aksi</th>
                        <th class="py-3 text-secondary small fw-semibold">Modul</th>
                        <th class="py-3 text-secondary small fw-semibold">Keterangan</th>
                        <th class="py-3 px-4 text-secondary small fw-semibold text-end">Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activityLogs as $log)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="fw-semibold text-navy">{{ $log->user->name ?? 'User #' . $log->user_id }}</div>
                                <div class="small text-secondary">{{ $log->user->email ?? '-' }}</div>
                            </td>
                            <td>
                                @php
                                    $badgeClass = match($log->action) {
                                        'create' => 'bg-success',
                                        'update' => 'bg-info',
                                        'delete' => 'bg-danger',
                                        'approve' => 'bg-primary',
                                        'reject' => 'bg-warning text-dark',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }} text-uppercase px-2 py-1 fs-7 rounded-2">{{ $log->action }}</span>
                            </td>
                            <td class="fw-medium text-dark">{{ $log->module }}</td>
                            <td class="text-secondary small">{{ $log->description }}</td>
                            <td class="px-4 text-end text-secondary small">
                                {{ $log->created_at ? $log->created_at->diffForHumans() : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-secondary">
                                <i class="bi bi-inbox fs-3 d-block mb-1"></i> Belum ada aktivitas tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
