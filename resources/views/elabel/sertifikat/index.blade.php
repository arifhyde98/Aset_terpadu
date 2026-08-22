@extends('layouts.app')

@section('title', 'Katalog Sertifikat Tanah - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Sertifikat Tanah</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0">Katalog Sertifikat Tanah</h4>
        </div>
        <div class="action-toolbar d-flex flex-wrap gap-2">
            <a href="{{ route('elabel.sertifikat.export') }}" class="btn btn-outline-success shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </a>
            <button type="button" class="btn btn-outline-primary shadow-sm fw-medium d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-file-earmark-arrow-up"></i> Import Excel
            </button>
            <a href="{{ route('elabel.sertifikat.create') }}" class="btn btn-success shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i> Tambah Sertifikat
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
            <form action="{{ route('elabel.sertifikat.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control border-start-0 shadow-none" placeholder="Cari no. sertipikat, NIBAR, pemilik, lokasi, dinas...">
                    </div>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-success w-100 fw-medium">Cari</button>
                    <a href="{{ route('elabel.sertifikat.index') }}" class="btn btn-light border bg-white"><i class="bi bi-arrow-clockwise"></i></a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4 text-center" style="width: 50px;">No.</th>
                        <th class="py-3">No. Sertipikat / NIBAR</th>
                        <th class="py-3">Pemilik / Pengguna</th>
                        <th class="py-3">Lokasi / Alamat</th>
                        <th class="py-3 text-end">Luas (m²)</th>
                        <th class="py-3 text-center">Box Fisik</th>
                        <th class="py-3 px-4 text-center" style="width: 130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td class="px-4 text-center fw-medium text-secondary">{{ $loop->iteration }}</td>
                            <td>
                                <div class="fw-bold text-navy"><i class="bi bi-patch-check-fill text-success me-1"></i> {{ $item->no_sertipikat }}</div>
                                <div class="small text-secondary">NIBAR: {{ $item->nibar ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="fw-medium text-dark">{{ $item->nama_pemilik ?: '-' }}</div>
                                <div class="small text-secondary">Dinas: {{ $item->opdSipat ? $item->opdSipat->nama : ($item->dinas ?: '-') }}</div>
                            </td>
                            <td>
                                <div class="fw-medium text-dark"><i class="bi bi-geo-alt text-danger me-1"></i> {{ $item->lokasi ?: '-' }}</div>
                                <div class="small text-secondary">{{ Str::limit($item->alamat, 35) }}</div>
                            </td>
                            <td class="text-end fw-bold text-dark">
                                {{ $item->luas ? number_format($item->luas, 2) : '-' }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-3 fw-bold">
                                    <i class="bi bi-archive me-1"></i> {{ $item->box->box_code ?? '-' }}
                                </span>
                            </td>
                            <td class="px-4 text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    @if($item->pdf_path)
                                        <a href="{{ route('elabel.sertifikat.view-pdf', $item->id) }}" target="_blank" class="btn btn-sm btn-light border text-danger" title="Lihat Scan PDF">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('elabel.sertifikat.show', $item->id) }}" class="btn btn-sm btn-light border text-navy" title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('elabel.sertifikat.edit', $item->id) }}" class="btn btn-sm btn-light border text-primary" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('elabel.sertifikat.destroy', $item->id) }}" method="POST" class="d-inline" onclick="return confirm('Hapus sertipikat {{ $item->no_sertipikat }}?')">
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
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i> Belum ada data Sertifikat Tanah.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- IMPORT MODAL -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('elabel.sertifikat.index') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-navy"><i class="bi bi-file-earmark-arrow-up text-success me-2"></i> Import Data Sertifikat Tanah</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="border rounded-3 p-3 bg-light mb-3">
                        <div class="fw-bold text-dark mb-1">Download Format Import</div>
                        <p class="small text-secondary mb-2">Gunakan format Excel standar untuk mengunggah sertifikat tanah.</p>
                        <a href="{{ route('elabel.sertifikat.template') }}" class="btn btn-sm btn-outline-success fw-medium">
                            <i class="bi bi-download me-1"></i> Download Format XLSX
                        </a>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Pilih File Excel (XLSX, XLS)</label>
                        <input type="file" name="import_file" class="form-control" accept=".xlsx, .xls" required>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success fw-semibold">Proses Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
