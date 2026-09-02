@extends('layouts.app')

@section('title', 'Detail Box ' . $box->nomor_box . ' - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Breadcrumbs -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dynamic.boxes.index') }}" class="text-decoration-none text-secondary">Manajemen Box</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">{{ $box->nomor_box }}</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-box-seam text-warning"></i> Detail Box Fisik: {{ $box->nomor_box }}
            </h4>
        </div>
        <div class="action-toolbar d-flex flex-wrap gap-2">
            <a href="{{ route('elabel.dynamic.boxes.label', $box->id) }}" target="_blank" class="btn btn-outline-dark shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-printer"></i> Cetak Label Stiker
            </a>
            <a href="{{ route('elabel.dynamic.items.create', ['type_id' => $box->archive_type_id, 'box_id' => $box->id]) }}" class="btn btn-primary shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i> Tambah Berkas ke Box Ini
            </a>
        </div>
    </div>

    <!-- Info Box Summary Card -->
    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-info-circle text-primary me-2"></i> Identitas Box</h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <span class="text-muted text-xs text-uppercase fw-bold d-block">Kategori Arsip</span>
                        <span class="badge bg-{{ $box->archiveType->warna_badge ?? 'primary' }}-subtle text-{{ $box->archiveType->warna_badge ?? 'primary' }} fs-6 fw-bold border mt-1">
                            <i class="bi {{ $box->archiveType->icon ?? 'bi-folder2' }} me-1"></i> {{ $box->archiveType->nama ?? '-' }}
                        </span>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted text-xs text-uppercase fw-bold d-block">Nomor Box & Barcode</span>
                        <div class="font-monospace fw-bold text-navy fs-5 mt-1">{{ $box->nomor_box }}</div>
                        <span class="text-xs text-muted font-monospace">{{ $box->barcode_code }}</span>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted text-xs text-uppercase fw-bold d-block">Lokasi Rak</span>
                        <div class="fw-semibold text-dark mt-1"><i class="bi bi-geo-alt text-danger me-1"></i> {{ $box->lokasi_rak ?: 'Belum ditentukan' }}</div>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted text-xs text-uppercase fw-bold d-block">Tahun Berkas</span>
                        <span class="badge bg-light text-dark border mt-1 fs-6">{{ $box->tahun ?: '-' }}</span>
                    </div>
                    <div>
                        <span class="text-muted text-xs text-uppercase fw-bold d-block">Catatan</span>
                        <p class="text-secondary small mb-0 mt-1">{{ $box->keterangan ?: 'Tidak ada catatan.' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-files text-success me-2"></i> Berkas Dokumen di Dalam Box ({{ $box->items->count() }})</h6>
                    <span class="badge bg-primary-subtle text-primary">Kapasitas Maks: {{ $box->kapasitas_maksimal }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3 px-4 text-center" style="width: 40px;">No.</th>
                                <th class="py-3">Nomor Dokumen</th>
                                <th class="py-3">Nama / Uraian</th>
                                <th class="py-3">OPD Pengolah</th>
                                <th class="py-3">Status</th>
                                <th class="py-3 text-center" style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($box->items as $item)
                                <tr>
                                    <td class="px-4 text-center fw-medium text-secondary">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="fw-bold text-navy font-monospace">{{ $item->nomor_dokumen }}</div>
                                        <span class="text-xs text-muted">Tahun {{ $item->tahun_dokumen ?: '-' }}</span>
                                    </td>
                                    <td>
                                        <div class="fw-medium text-dark">{{ $item->nama_dokumen }}</div>
                                    </td>
                                    <td>
                                        <span class="text-secondary small">{{ $item->opd->nama ?? '-' }}</span>
                                    </td>
                                    <td>
                                        @if($item->status === 'Tersedia')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Tersedia</span>
                                        @elseif($item->status === 'Dipinjam')
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Dipinjam</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border">{{ $item->status }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('elabel.dynamic.items.show', $item->id) }}" class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-secondary">
                                        <i class="bi bi-folder-x fs-2 d-block mb-2"></i>
                                        Belum ada dokumen yang dimasukkan ke dalam box ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.text-xs {
    font-size: 0.75rem;
}
</style>
@endsection
