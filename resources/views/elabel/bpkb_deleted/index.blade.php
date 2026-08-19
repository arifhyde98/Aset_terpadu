@extends('layouts.app')

@section('title', 'BPKB Keluar / Soft Delete - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">BPKB Keluar</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0">Daftar BPKB Keluar (Soft Delete)</h4>
        </div>
        <div class="action-toolbar d-flex flex-wrap gap-2">
            <a href="{{ route('elabel.bpkb-deleted.export') }}" class="btn btn-outline-success shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </a>
            <a href="{{ route('elabel.bpkb.index') }}" class="btn btn-light border fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Katalog BPKB
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

    <!-- TABLE CARD -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <form action="{{ route('elabel.bpkb-deleted.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control border-start-0 shadow-none" placeholder="Cari no. polisi, no. BPKB, alasan...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="reason" class="form-select">
                        <option value="">-- Semua Alasan --</option>
                        <option value="Di pinjam" {{ request('reason') === 'Di pinjam' ? 'selected' : '' }}>Di pinjam</option>
                        <option value="Penjualan" {{ request('reason') === 'Penjualan' ? 'selected' : '' }}>Penjualan</option>
                        <option value="Dihibahkan" {{ request('reason') === 'Dihibahkan' ? 'selected' : '' }}>Dihibahkan</option>
                        <option value="Kendaraan hilang" {{ request('reason') === 'Kendaraan hilang' ? 'selected' : '' }}>Kendaraan hilang</option>
                        <option value="Kendaraan tidak ditemukan" {{ request('reason') === 'Kendaraan tidak ditemukan' ? 'selected' : '' }}>Kendaraan tidak ditemukan</option>
                        <option value="Lainnya" {{ request('reason') === 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-medium">Filter</button>
                    <a href="{{ route('elabel.bpkb-deleted.index') }}" class="btn btn-light border bg-white"><i class="bi bi-arrow-clockwise"></i></a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4 text-center" style="width: 50px;">No.</th>
                        <th class="py-3">No. Polisi / Tahun</th>
                        <th class="py-3">Identitas BPKB</th>
                        <th class="py-3">Alasan Keluar</th>
                        <th class="py-3">Diproses Oleh</th>
                        <th class="py-3 px-4 text-center" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td class="px-4 text-center fw-medium text-secondary">{{ $loop->iteration }}</td>
                            <td>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2 fs-6 rounded-3 fw-bold">{{ $item->plate_number }}</span>
                                <div class="small text-secondary mt-1"><i class="bi bi-calendar3 me-1"></i> Tahun {{ $item->year ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold text-navy">{{ $item->no_bpkb ?: '-' }}</div>
                                <div class="small text-secondary">NIBAR: {{ $item->nibar ?: '-' }}</div>
                            </td>
                            <td>
                                <span class="badge bg-warning bg-opacity-25 text-dark border border-warning px-2 py-1 fs-7 fw-semibold">{{ $item->reason }}</span>
                                @if($item->reason_detail)
                                    <div class="small text-secondary mt-1">{{ Str::limit($item->reason_detail, 40) }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-medium text-dark"><i class="bi bi-person-fill text-secondary me-1"></i> {{ $item->deleter->name ?? 'User #' . $item->deleted_by }}</div>
                                <div class="small text-secondary">{{ $item->deleted_at ? $item->deleted_at->format('d M Y H:i') : '-' }}</div>
                            </td>
                            <td class="px-4 text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    @if($item->pdf_path)
                                        <a href="{{ route('elabel.bpkb-deleted.view-pdf', $item->id) }}" target="_blank" class="btn btn-sm btn-light border text-danger" title="Lihat Scan BPKB">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>
                                    @endif

                                    @if($item->support_doc_path)
                                        <a href="{{ route('elabel.bpkb-deleted.view-doc', $item->id) }}" target="_blank" class="btn btn-sm btn-light border text-info" title="Lihat Dokumen Pendukung">
                                            <i class="bi bi-paperclip"></i>
                                        </a>
                                    @endif

                                    <form action="{{ route('elabel.bpkb-deleted.restore', $item->id) }}" method="POST" class="d-inline" onclick="return confirm('Kembalikan BPKB {{ $item->plate_number }} ke katalog aktif?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-light border text-success" title="Kembalikan ke Katalog BPKB">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    </form>

                                    <form action="{{ route('elabel.bpkb-deleted.destroy', $item->id) }}" method="POST" class="d-inline" onclick="return confirm('Hapus permanen riwayat BPKB keluar ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border text-danger" title="Hapus Permanen">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-secondary">
                                <i class="bi bi-check-circle fs-2 d-block mb-2 text-success"></i> Tidak ada BPKB keluar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
