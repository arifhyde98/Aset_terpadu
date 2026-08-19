@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Pemetaan OPD Terpadu</h4>
            <p class="text-muted mb-0 small">Jembatan penghubung data OPD antara Modul Pertanahan (SIPAT) dan Pengelolaan Aset (E-RANDIS)</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- STATS CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-primary bg-opacity-10 border-start border-primary border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Terhubung (Mapped)</div>
                        <div class="fs-4 fw-bold text-primary">{{ number_format($totalMappings) }} Instansi</div>
                    </div>
                    <div class="bg-primary text-white p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-link-45deg fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-info bg-opacity-10 border-start border-info border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">OPD SIPAT (Pertanahan)</div>
                        <div class="fs-4 fw-bold text-info">{{ number_format($totalSipat) }} Instansi</div>
                    </div>
                    <div class="bg-info text-white p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-geo-alt-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-success bg-opacity-10 border-start border-success border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">OPD E-RANDIS (Aset)</div>
                        <div class="fs-4 fw-bold text-success">{{ number_format($totalErandis) }} Instansi</div>
                    </div>
                    <div class="bg-success text-white p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-truck fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLE CARD -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-transparent border-0 p-3">
            <form action="{{ route('master.opd-mapping.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Cari nama OPD SIPAT atau E-RANDIS..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Cari</button>
                </div>
                @if(request('search'))
                    <div class="col-md-2">
                        <a href="{{ route('master.opd-mapping.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
                    </div>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;" class="text-center">#</th>
                        <th>OPD Modul SIPAT (Pertanahan)</th>
                        <th class="text-center" style="width: 80px;"><i class="bi bi-arrow-left-right text-primary fs-5"></i></th>
                        <th>OPD Modul E-RANDIS (Kendaraan Aset)</th>
                        <th class="text-center" style="width: 130px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mappings as $index => $m)
                        <tr>
                            <td class="text-center fw-semibold text-muted">{{ $mappings->firstItem() + $index }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $m->sipatOpd->nama ?? '-' }}</div>
                                <span class="badge bg-info-subtle text-info small">ID SIPAT: {{ $m->sipat_opd_id }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary-subtle text-primary p-2 rounded-circle">
                                    <i class="bi bi-link-45deg fs-6"></i>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $m->erandisOpd->nama ?? '-' }}</div>
                                <span class="badge bg-success-subtle text-success small">ID E-RANDIS: {{ $m->erandis_opd_id }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success text-white px-2 py-1">
                                    <i class="bi bi-check-circle me-1"></i> {{ ucfirst($m->status_verifikasi) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Tidak ada data pemetaan OPD yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($mappings->hasPages())
            <div class="card-footer bg-transparent border-0 p-3">
                {{ $mappings->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
