@extends('layouts.app')

@section('title', 'Surat Penyerahan Arsip - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Surat Penyerahan</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0">Surat Penyerahan Arsip</h4>
        </div>
        <div class="action-toolbar d-flex flex-wrap gap-2">
            <a href="{{ route('elabel.surat-penyerahan.export') }}" class="btn btn-outline-success shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </a>
            <a href="{{ route('elabel.surat-penyerahan.create') }}" class="btn btn-primary shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i> Tambah Surat Penyerahan
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
            <form action="{{ route('elabel.surat-penyerahan.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control border-start-0 shadow-none" placeholder="Cari no. surat, NIBAR, jenis penyerahan, pemberi hibah...">
                    </div>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-medium">Cari</button>
                    <a href="{{ route('elabel.surat-penyerahan.index') }}" class="btn btn-light border bg-white"><i class="bi bi-arrow-clockwise"></i></a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4 text-center" style="width: 50px;">No.</th>
                        <th class="py-3">No. Surat / NIBAR</th>
                        <th class="py-3">Jenis Penyerahan</th>
                        <th class="py-3">Pemberi Hibah / Dinas</th>
                        <th class="py-3">Lokasi / Alamat</th>
                        <th class="py-3 text-center">Box Fisik</th>
                        <th class="py-3 px-4 text-center" style="width: 130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td class="px-4 text-center fw-medium text-secondary">{{ $loop->iteration }}</td>
                            <td>
                                <div class="fw-bold text-navy"><i class="bi bi-file-earmark-text-fill text-primary me-1"></i> {{ $item->no_surat }}</div>
                                <div class="small text-secondary">NIBAR: {{ $item->nibar ?: '-' }}</div>
                            </td>
                            <td>
                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-3 py-1 rounded-pill fw-medium">
                                    {{ $item->jenis_penyerahan ?: 'Hibah' }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-medium text-dark">{{ $item->pemberi_hibah ?: '-' }}</div>
                                <div class="small text-secondary">Dinas: {{ $item->dinas ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="fw-medium text-dark"><i class="bi bi-geo-alt text-danger me-1"></i> {{ $item->lokasi ?: '-' }}</div>
                                <div class="small text-secondary">{{ Str::limit($item->alamat, 35) }}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-3 fw-bold">
                                    <i class="bi bi-archive me-1"></i> {{ $item->box->box_code ?? '-' }}
                                </span>
                            </td>
                            <td class="px-4 text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    @if($item->pdf_path)
                                        <a href="{{ route('elabel.surat-penyerahan.pdf', $item->id) }}" target="_blank" class="btn btn-sm btn-light border text-danger" title="Lihat PDF">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('elabel.surat-penyerahan.show', $item->id) }}" class="btn btn-sm btn-light border text-navy" title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('elabel.surat-penyerahan.edit', $item->id) }}" class="btn btn-sm btn-light border text-primary" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('elabel.surat-penyerahan.destroy', $item->id) }}" method="POST" class="d-inline" onclick="return confirm('Hapus surat penyerahan {{ $item->no_surat }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border text-danger" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-secondary">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i> Belum ada Surat Penyerahan terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
