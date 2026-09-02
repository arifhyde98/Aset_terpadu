@extends('layouts.app')

@section('title', 'Master Jenis Arsip & Form Builder - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Breadcrumbs -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Master Jenis Arsip</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-sliders text-success"></i> Master Kategori & Form Builder Arsip
            </h4>
        </div>
        <div class="action-toolbar d-flex flex-wrap gap-2">
            <a href="{{ route('elabel.dynamic.items.index') }}" class="btn btn-outline-primary shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-folder2-open"></i> Buka Katalog Berkas
            </a>
            <a href="{{ route('elabel.dynamic.types.create') }}" class="btn btn-primary shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-plus-circle"></i> Tambah Kategori Baru
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Search & Summary Stats -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <form action="{{ route('elabel.dynamic.types.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" name="q" value="{{ $searchQuery }}" class="form-control border-start-0 shadow-none" placeholder="Cari kode tipe, nama jenis arsip, atau deskripsi...">
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-medium">Cari</button>
                    <a href="{{ route('elabel.dynamic.types.index') }}" class="btn btn-light border bg-white"><i class="bi bi-arrow-clockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Cards Grid Kategori Arsip -->
    <div class="row g-4">
        @forelse($types as $type)
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 position-relative hover-shadow transition-all">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 p-3 bg-{{ $type->warna_badge ?: 'primary' }}-subtle text-{{ $type->warna_badge ?: 'primary' }} fs-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                                    <i class="bi {{ $type->icon ?: 'bi-folder2' }}"></i>
                                </div>
                                <div>
                                    <span class="badge bg-{{ $type->warna_badge ?: 'primary' }} text-uppercase fw-semibold px-2 py-1 mb-1">{{ $type->kode }}</span>
                                    <h5 class="fw-bold text-navy mb-0">{{ $type->nama }}</h5>
                                </div>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light rounded-circle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-3">
                                    <li>
                                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('elabel.dynamic.items.index', ['type_id' => $type->id]) }}">
                                            <i class="bi bi-folder2-open text-primary"></i> Lihat Berkas
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('elabel.dynamic.items.create', ['type_id' => $type->id]) }}">
                                            <i class="bi bi-plus-circle text-success"></i> Input Berkas Baru
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('elabel.dynamic.types.edit', $type->id) }}">
                                            <i class="bi bi-pencil-square text-warning"></i> Edit & Form Builder
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('elabel.dynamic.types.destroy', $type->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jenis arsip {{ $type->nama }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger">
                                                <i class="bi bi-trash"></i> Hapus Kategori
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <p class="text-secondary small mb-4 flex-grow-1">
                            {{ $type->deskripsi ?: 'Tidak ada deskripsi tambahan.' }}
                        </p>

                        <!-- Custom Field Badges Summary -->
                        <div class="mb-4">
                            <div class="text-xs text-uppercase fw-bold text-muted mb-2 tracking-wider">
                                Atribut Form Dinamis ({{ count($type->schema_fields ?? []) }})
                            </div>
                            <div class="d-flex flex-wrap gap-1">
                                @if(!empty($type->schema_fields))
                                    @foreach(array_slice($type->schema_fields, 0, 5) as $f)
                                        <span class="badge bg-light text-dark border font-monospace small px-2 py-1">
                                            {{ $f['label'] }} <span class="text-secondary">({{ $f['type'] }})</span>
                                        </span>
                                    @endforeach
                                    @if(count($type->schema_fields) > 5)
                                        <span class="badge bg-secondary-subtle text-secondary small px-2 py-1">+{{ count($type->schema_fields) - 5 }} lainnya</span>
                                    @endif
                                @else
                                    <span class="text-muted small fst-italic">Hanya field standar (Nomor, Uraian, Tahun, File Scan)</span>
                                @endif
                            </div>
                        </div>

                        <!-- Card Footer Stats -->
                        <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                            <div class="d-flex gap-3">
                                <div>
                                    <span class="d-block fw-bold text-navy fs-6">{{ number_format($type->items_count) }}</span>
                                    <span class="text-muted text-xs">Total Berkas</span>
                                </div>
                                <div class="border-start ps-3">
                                    <span class="d-block fw-bold text-navy fs-6">{{ number_format($type->boxes_count) }}</span>
                                    <span class="text-muted text-xs">Box Fisik</span>
                                </div>
                            </div>
                            <a href="{{ route('elabel.dynamic.items.index', ['type_id' => $type->id]) }}" class="btn btn-sm btn-outline-{{ $type->warna_badge ?: 'primary' }} rounded-pill px-3">
                                Buka Katalog <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                    <div class="text-secondary mb-3 fs-1"><i class="bi bi-folder-x"></i></div>
                    <h5 class="fw-bold text-navy">Belum Ada Kategori Arsip Dinamis</h5>
                    <p class="text-muted small mb-4">Buat kategori arsip baru pertama Anda (misalnya IMB/PBG, Kontrak Pengadaan, SPJ, dll) dengan form kustom sesuai kebutuhan.</p>
                    <div>
                        <a href="{{ route('elabel.dynamic.types.create') }}" class="btn btn-primary px-4 py-2 fw-medium">
                            <i class="bi bi-plus-lg me-1"></i> Buat Kategori Sekarang
                        </a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

</div>

<style>
.hover-shadow:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.08) !important;
}
.transition-all {
    transition: all 0.25s ease-in-out;
}
.text-xs {
    font-size: 0.75rem;
}
</style>
@endsection
