@extends('layouts.app')

@section('title', ($currentType ? 'Box Arsip ' . $currentType->nama : 'Manajemen Box Fisik Arsip Dinamis') . ' - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Breadcrumbs -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    @if($currentType)
                        <li class="breadcrumb-item text-secondary">{{ $currentType->nama }}</li>
                        <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Box Arsip</li>
                    @else
                        <li class="breadcrumb-item text-secondary">Arsip Dinamis</li>
                        <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Manajemen Box Arsip</li>
                    @endif
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0 d-flex align-items-center gap-2">
                @if($currentType)
                    <i class="bi bi-box-seam text-warning"></i>
                    <span>Box Arsip: {{ $currentType->nama }}</span>
                    <span class="badge bg-warning-subtle text-dark border fs-6 font-monospace">{{ $currentType->kode }}</span>
                @else
                    <i class="bi bi-box-seam text-warning"></i>
                    <span>Manajemen Seluruh Box Fisik Arsip Dinamis</span>
                @endif
            </h4>
            @if($currentType)
                <p class="text-muted small mb-0 mt-1">
                    @if($currentType->deskripsi && $currentType->deskripsi !== '-') {{ $currentType->deskripsi }} &bull; @endif
                    Total: <strong>{{ $boxes->total() }}</strong> box fisik terdaftar
                </p>
            @endif
        </div>
        <div class="action-toolbar d-flex flex-wrap gap-2">
            @if($currentType)
                <a href="{{ route('elabel.dynamic.items.index', ['type_id' => $currentType->id]) }}" class="btn btn-outline-primary shadow-sm fw-medium d-flex align-items-center gap-2">
                    <i class="bi bi-folder2-open"></i> Katalog {{ $currentType->kode ?: 'Berkas' }}
                </a>
                <button type="button" class="btn btn-warning text-dark shadow-sm fw-semibold d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createBoxModal">
                    <i class="bi bi-plus-circle"></i> Tambah Box {{ $currentType->kode ?: 'Baru' }}
                </button>
            @else
                <button type="button" class="btn btn-warning text-dark shadow-sm fw-semibold d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createBoxModal">
                    <i class="bi bi-plus-circle"></i> Tambah Box Baru
                </button>
            @endif
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <form action="{{ route('elabel.dynamic.boxes.index') }}" method="GET" class="row g-2 align-items-center">
                @if(!empty($selectedType))
                    <input type="hidden" name="type_id" value="{{ $selectedType }}">
                @endif
                <div class="{{ empty($selectedType) ? 'col-md-5' : 'col-md-9' }}">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" name="q" value="{{ $searchQuery }}" class="form-control border-start-0 shadow-none" placeholder="Cari nomor box, barcode, lokasi rak, atau catatan...">
                    </div>
                </div>
                @if(empty($selectedType))
                    <div class="col-md-4">
                        <select name="type_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua Kategori Arsip</option>
                            @foreach($types as $t)
                                <option value="{{ $t->id }}" {{ $selectedType == $t->id ? 'selected' : '' }}>{{ $t->nama }} ({{ $t->kode }})</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-medium">Filter</button>
                    <a href="{{ route('elabel.dynamic.boxes.index', !empty($selectedType) ? ['type_id' => $selectedType] : []) }}" class="btn btn-light border bg-white" title="Reset Filter"><i class="bi bi-arrow-clockwise"></i></a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4 text-center" style="width: 50px;">No.</th>
                        <th class="py-3">Nomor Box</th>
                        @if(empty($selectedType))
                            <th class="py-3">Kategori Arsip</th>
                        @endif
                        <th class="py-3">Lokasi / Rak Fisik</th>
                        <th class="py-3">Tahun</th>
                        <th class="py-3 text-center">Kapasitas & Isi</th>
                        <th class="py-3 px-4 text-center" style="width: 160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($boxes as $box)
                        <tr>
                            <td class="px-4 text-center fw-medium text-secondary">{{ $loop->iteration + ($boxes->currentPage() - 1) * $boxes->perPage() }}</td>
                            <td>
                                <div class="fw-bold text-navy font-monospace fs-6">
                                    <i class="bi bi-box me-1 text-warning"></i> {{ $box->nomor_box }}
                                </div>
                                <span class="text-xs text-muted font-monospace">{{ $box->barcode_code }}</span>
                            </td>
                            @if(empty($selectedType))
                                <td>
                                    <span class="badge bg-{{ $box->archiveType->warna_badge ?? 'primary' }}-subtle text-{{ $box->archiveType->warna_badge ?? 'primary' }} border">
                                        {{ $box->archiveType->nama ?? '-' }}
                                    </span>
                                </td>
                            @endif
                            <td>
                                <span class="fw-medium text-dark"><i class="bi bi-geo-alt text-danger me-1"></i> {{ $box->lokasi_rak ?: '-' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $box->tahun ?: '-' }}</span>
                            </td>
                            <td class="text-center" style="min-width: 140px;">
                                @php
                                    $pct = $box->kapasitas_maksimal > 0 ? min(100, round(($box->items_count / $box->kapasitas_maksimal) * 100)) : 0;
                                    $barColor = $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success');
                                @endphp
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-xs fw-bold {{ $box->items_count > 0 ? 'text-navy' : 'text-muted' }}">{{ $box->items_count }} berkas</span>
                                    <span class="text-xs text-muted">Maks. {{ $box->kapasitas_maksimal }}</span>
                                </div>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar {{ $barColor }}" role="progressbar" style="width: {{ $pct }}%" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </td>
                            <td class="px-4 text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('elabel.dynamic.boxes.show', $box->id) }}" class="btn btn-outline-primary" title="Lihat Berkas dalam Box">
                                        <i class="bi bi-folder2-open"></i>
                                    </a>
                                    <a href="{{ route('elabel.dynamic.boxes.label', $box->id) }}" target="_blank" class="btn btn-outline-secondary" title="Cetak Label Barcode">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <form action="{{ route('elabel.dynamic.boxes.destroy', $box->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus Box {{ $box->nomor_box }}? Berkas di dalamnya akan dilepas dari box ini.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Hapus Box">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ empty($selectedType) ? 7 : 6 }}" class="text-center py-5 text-secondary">
                                <i class="bi bi-box fs-1 d-block mb-2 text-muted"></i>
                                @if($currentType)
                                    <div class="fw-medium">Belum ada box fisik arsip <strong>{{ $currentType->nama }}</strong> yang terdaftar atau sesuai kriteria pencarian.</div>
                                    <button type="button" class="btn btn-sm btn-warning text-dark rounded-pill mt-3 px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#createBoxModal">
                                        <i class="bi bi-plus-circle me-1"></i> Buat Box {{ $currentType->kode ?: 'Baru' }}
                                    </button>
                                @else
                                    <div class="fw-medium">Belum ada box fisik arsip dinamis yang terdaftar atau sesuai kriteria pencarian.</div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($boxes->hasPages())
            <div class="card-footer bg-white border-0 py-3 px-4">
                {{ $boxes->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Modal Create Box -->
<div class="modal fade" id="createBoxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="{{ route('elabel.dynamic.boxes.store') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom py-3 px-4">
                    <h5 class="modal-title fw-bold text-navy"><i class="bi bi-box-seam text-warning me-2"></i> {{ $currentType ? 'Buat Box: ' . $currentType->nama : 'Buat Box Fisik Baru' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    @if($currentType)
                        <input type="hidden" name="archive_type_id" value="{{ $currentType->id }}">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Kategori Arsip</label>
                            <div class="form-control bg-light d-flex align-items-center justify-content-between">
                                <span class="fw-semibold text-navy">{{ $currentType->nama }}</span>
                                <span class="badge bg-{{ $currentType->warna_badge ?: 'primary' }}-subtle text-{{ $currentType->warna_badge ?: 'primary' }} border font-monospace">{{ $currentType->kode }}</span>
                            </div>
                        </div>
                    @else
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Kategori / Jenis Arsip <span class="text-danger">*</span></label>
                            <select name="archive_type_id" class="form-select" required>
                                @foreach($types as $t)
                                    <option value="{{ $t->id }}" {{ $selectedType == $t->id ? 'selected' : '' }}>{{ $t->nama }} ({{ $t->kode }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Nomor / Kode Box (Opsional)</label>
                        <input type="text" name="nomor_box" class="form-control font-monospace" placeholder="Kosongkan untuk nomor otomatis (cth: BOX-IMB-001)">
                        <div class="form-text small">Jika dikosongkan, sistem akan mengurutkan nomor box secara otomatis.</div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label fw-semibold text-dark">Lokasi Rak / Ruang</label>
                            <input type="text" name="lokasi_rak" class="form-control" placeholder="Cth: Rak B-02 Lantai 2">
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-semibold text-dark">Tahun</label>
                            <input type="number" name="tahun" class="form-control" value="{{ date('Y') }}" min="1900" max="2100">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Kapasitas Maksimal Berkas</label>
                        <input type="number" name="kapasitas_maksimal" class="form-control" value="100" min="1" max="1000" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Catatan / Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan fisik box..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top py-3 px-4">
                    <button type="button" class="btn btn-light border px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-dark fw-semibold px-4">Buat Box</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.text-xs {
    font-size: 0.75rem;
}
</style>
@endsection
