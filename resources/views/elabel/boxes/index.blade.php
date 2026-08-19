@extends('layouts.app')

@section('title', 'Box & Lokasi Rak BPKB - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Box BPKB</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0">Manajemen Box & Lokasi Rak BPKB</h4>
        </div>
        <div>
            <a href="{{ route('elabel.boxes.create') }}" class="btn btn-primary shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i> Tambah Box BPKB
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

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <form action="{{ route('elabel.boxes.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control border-start-0 shadow-none" placeholder="Cari kode box atau lokasi rak...">
                    </div>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-medium">Cari</button>
                    <a href="{{ route('elabel.boxes.index') }}" class="btn btn-light border bg-white"><i class="bi bi-arrow-clockwise"></i></a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4 text-center" style="width: 50px;">No.</th>
                        <th class="py-3">Kode Box</th>
                        <th class="py-3">Jenis Kendaraan</th>
                        <th class="py-3">Tahun Dokumen</th>
                        <th class="py-3">Lokasi Rak / Brankas</th>
                        <th class="py-3 text-center">Jumlah Isian BPKB</th>
                        <th class="py-3 px-4 text-center" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($boxes as $box)
                        <tr>
                            <td class="px-4 text-center fw-medium text-secondary">{{ $loop->iteration }}</td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 fs-6 rounded-3 fw-bold">
                                    <i class="bi bi-archive me-1"></i> {{ $box->box_code }}
                                </span>
                            </td>
                            <td>
                                <span class="fw-semibold text-navy">{{ $box->vehicle_type === 'R2' ? 'R2 (Motor)' : 'R4 (Mobil)' }}</span>
                            </td>
                            <td>
                                @php
                                    $yrs = $box->years->pluck('year')->toArray();
                                @endphp
                                <div class="small fw-medium text-dark">{{ !empty($yrs) ? implode(', ', $yrs) : '-' }}</div>
                            </td>
                            <td>
                                <div class="fw-medium text-dark"><i class="bi bi-geo-alt text-danger me-1"></i> {{ $box->location ?: 'Lokasi Belum Diatur' }}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary bg-opacity-10 text-dark border px-3 py-1 rounded-pill fw-bold">
                                    {{ $box->bpkbs_count }} / 55
                                </span>
                            </td>
                            <td class="px-4 text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('elabel.boxes.show', $box->id) }}" class="btn btn-sm btn-light border text-navy" title="Lihat Isi Box">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('elabel.boxes.label', ['id' => $box->id, 'autoprint' => 1]) }}" target="_blank" class="btn btn-sm btn-light border text-primary" title="Cetak Label Box">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <form action="{{ route('elabel.boxes.destroy', $box->id) }}" method="POST" class="d-inline" onclick="return confirm('Hapus Box {{ $box->box_code }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border text-danger" title="Hapus Box">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-secondary">
                                <i class="bi bi-archive fs-2 d-block mb-2"></i> Belum ada Box BPKB terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
