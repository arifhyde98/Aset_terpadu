@extends('layouts.app')

@section('title', 'Katalog Berkas Arsip Dinamis - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Breadcrumbs -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Katalog Arsip Dinamis</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-folder2-open text-primary"></i> Katalog Berkas Arsip Dinamis
            </h4>
        </div>
        <div class="action-toolbar d-flex flex-wrap gap-2">
            <a href="{{ route('elabel.dynamic.items.export', request()->query()) }}" class="btn btn-outline-success shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </a>
            <a href="{{ route('elabel.dynamic.types.index') }}" class="btn btn-outline-secondary shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-sliders"></i> Master Kategori
            </a>
            <a href="{{ route('elabel.dynamic.items.create', ['type_id' => $selectedType]) }}" class="btn btn-primary shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-plus-circle"></i> Input Arsip Baru
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

    <!-- Category Pills Quick Filter -->
    <div class="d-flex align-items-center gap-2 overflow-x-auto pb-2 mb-3">
        <a href="{{ route('elabel.dynamic.items.index', request()->except('type_id', 'page')) }}" 
           class="btn btn-sm {{ empty($selectedType) ? 'btn-primary' : 'btn-light border bg-white text-secondary' }} rounded-pill px-3 text-nowrap fw-medium">
            <i class="bi bi-grid me-1"></i> Semua Kategori
        </a>
        @foreach($types as $t)
            <a href="{{ route('elabel.dynamic.items.index', array_merge(request()->except('page'), ['type_id' => $t->id])) }}" 
               class="btn btn-sm {{ $selectedType == $t->id ? 'btn-' . ($t->warna_badge ?: 'primary') : 'btn-light border bg-white text-secondary' }} rounded-pill px-3 text-nowrap fw-medium">
                <i class="bi {{ $t->icon ?: 'bi-folder' }} me-1"></i> {{ $t->nama }}
            </a>
        @endforeach
    </div>

    <!-- Filter & Search Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <form action="{{ route('elabel.dynamic.items.index') }}" method="GET" class="row g-2 align-items-center">
                @if(!empty($selectedType))
                    <input type="hidden" name="type_id" value="{{ $selectedType }}">
                @endif
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" name="q" value="{{ $searchQuery }}" class="form-control border-start-0 shadow-none" placeholder="Cari nomor berkas, nama/uraian, metadata...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="opd_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua OPD / Instansi</option>
                        @foreach($opds as $o)
                            <option value="{{ $o->id }}" {{ $selectedOpd == $o->id ? 'selected' : '' }}>{{ $o->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="Tersedia" {{ $selectedStatus == 'Tersedia' ? 'selected' : '' }}>Tersedia</option>
                        <option value="Dipinjam" {{ $selectedStatus == 'Dipinjam' ? 'selected' : '' }}>Dipinjam</option>
                        <option value="Musnah" {{ $selectedStatus == 'Musnah' ? 'selected' : '' }}>Musnah</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <input type="number" name="year" value="{{ $selectedYear }}" class="form-control" placeholder="Tahun" min="1900" max="2100">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-medium">Filter</button>
                    <a href="{{ route('elabel.dynamic.items.index') }}" class="btn btn-light border bg-white" title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4 text-center" style="width: 50px;">No.</th>
                        <th class="py-3">Nomor Dokumen</th>
                        <th class="py-3">Nama / Uraian Berkas</th>
                        <th class="py-3">Kategori</th>
                        <th class="py-3">OPD Pengolah</th>
                        <th class="py-3 text-center">Box & Lokasi</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3 text-center">Scan PDF</th>
                        <th class="py-3 px-4 text-center" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td class="px-4 text-center fw-medium text-secondary">{{ $loop->iteration + ($items->currentPage() - 1) * $items->perPage() }}</td>
                            <td>
                                <div class="fw-bold text-navy font-monospace">{{ $item->nomor_dokumen }}</div>
                                <span class="text-xs text-muted">Tahun {{ $item->tahun_dokumen ?: '-' }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $item->nama_dokumen }}</div>
                                @if(!empty($item->metadata))
                                    <div class="text-xs text-muted mt-1">
                                        @php $shownMeta = 0; @endphp
                                        @foreach($item->metadata as $mKey => $mVal)
                                            @if($mVal && $shownMeta < 2)
                                                <span class="badge bg-light text-secondary border me-1 font-monospace">{{ $mKey }}: {{ is_array($mVal) ? json_encode($mVal) : Str::limit($mVal, 20) }}</span>
                                                @php $shownMeta++; @endphp
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $item->archiveType->warna_badge ?? 'primary' }}-subtle text-{{ $item->archiveType->warna_badge ?? 'primary' }} border">
                                    <i class="bi {{ $item->archiveType->icon ?? 'bi-folder' }} me-1"></i> {{ $item->archiveType->nama ?? '-' }}
                                </span>
                            </td>
                            <td>
                                <span class="text-secondary small">{{ $item->opd->nama ?? '-' }}</span>
                            </td>
                            <td class="text-center">
                                @if($item->box)
                                    <a href="{{ route('elabel.dynamic.boxes.show', $item->box->id) }}" class="badge bg-warning-subtle text-dark border text-decoration-none">
                                        <i class="bi bi-box me-1"></i> {{ $item->box->nomor_box }}
                                    </a>
                                    <span class="d-block text-xs text-muted">{{ $item->box->lokasi_rak ?: '' }}</span>
                                @else
                                    <span class="badge bg-light text-muted border">Belum Di-box</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($item->status === 'Tersedia')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Tersedia</span>
                                @elseif($item->status === 'Dipinjam')
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Dipinjam</span>
                                @elseif($item->status === 'Musnah')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Musnah</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border">{{ $item->status }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if(!empty($item->file_scan_pdf))
                                    <a href="{{ route('elabel.dynamic.items.view-pdf', $item->id) }}" target="_blank" class="btn btn-sm btn-outline-danger px-2 py-1" title="Lihat PDF Dokumen">
                                        <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                                    </a>
                                @else
                                    <span class="text-muted text-xs"><i class="bi bi-dash"></i></span>
                                @endif
                            </td>
                            <td class="px-4 text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('elabel.dynamic.items.show', $item->id) }}" class="btn btn-outline-primary" title="Detail Dokumen">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('elabel.dynamic.items.edit', $item->id) }}" class="btn btn-outline-warning" title="Edit Data">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('elabel.dynamic.items.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus dokumen {{ $item->nomor_dokumen }}? Seluruh berkas scan dan lampiran akan dihapus.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Hapus Dokumen">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-secondary">
                                <i class="bi bi-folder-x fs-1 d-block mb-2"></i>
                                Belum ada berkas arsip yang sesuai dengan kriteria pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="card-footer bg-white border-0 py-3 px-4">
                {{ $items->links() }}
            </div>
        @endif
    </div>

</div>

<style>
.text-xs {
    font-size: 0.75rem;
}
</style>
@endsection
