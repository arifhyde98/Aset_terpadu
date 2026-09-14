@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-lg-4 py-3">
    <!-- Header Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 text-muted small">
                    <li class="breadcrumb-item"><a href="{{ route('landing') }}" class="text-decoration-none">Beranda</a></li>
                    <li class="breadcrumb-item active" aria-current="page">KIB C - Bangunan</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-building text-primary"></i> Gedung dan Bangunan (KIB C)
            </h3>
            <p class="text-muted small mb-0">Pengelolaan inventaris gedung pemerintah daerah sesuai Permendagri No. 19/2016 &amp; No. 7/2006.</p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('bangunan.peta.index') }}" class="btn btn-outline-primary rounded-pill px-3 shadow-sm">
                <i class="bi bi-geo-alt me-1"></i> Peta GIS
            </a>
            <a href="{{ route('bangunan.laporan.index') }}" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i> Pusat Laporan
            </a>
            <button type="button" class="btn btn-success rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalSmartImport">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> AI Smart Import
            </button>
            <a href="{{ route('bangunan.create') }}" class="btn btn-primary rounded-pill px-3 shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Tambah Bangunan
            </a>
        </div>
    </div>

    <!-- Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white hover-elevate">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Total Bangunan</span>
                    <div class="rounded-circle p-2 bg-primary-subtle text-primary">
                        <i class="bi bi-building fs-5"></i>
                    </div>
                </div>
                <h4 class="fw-bold text-dark mb-1">{{ number_format($stats['total'] ?? 0, 0, ',', '.') }}</h4>
                <span class="text-muted small">Unit Tercatat</span>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white hover-elevate">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Nilai Perolehan</span>
                    <div class="rounded-circle p-2 bg-success-subtle text-success">
                        <i class="bi bi-cash-stack fs-5"></i>
                    </div>
                </div>
                <h4 class="fw-bold text-success mb-1">Rp {{ number_format(($stats['nilai_perolehan'] ?? 0) / 1000000000, 2, ',', '.') }} M</h4>
                <span class="text-muted small">Rp {{ number_format($stats['nilai_perolehan'] ?? 0, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white hover-elevate">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Luas Lantai</span>
                    <div class="rounded-circle p-2 bg-info-subtle text-info">
                        <i class="bi bi-bounding-box-circles fs-5"></i>
                    </div>
                </div>
                <h4 class="fw-bold text-dark mb-1">{{ number_format($stats['luas_total'] ?? 0, 2, ',', '.') }}</h4>
                <span class="text-muted small">Meter Persegi (m²)</span>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white hover-elevate">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Kondisi Fisik</span>
                    <div class="rounded-circle p-2 bg-warning-subtle text-warning">
                        <i class="bi bi-heart-pulse fs-5"></i>
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-success" title="Baik">{{ $stats['baik'] ?? 0 }} B</span>
                    <span class="badge bg-warning text-dark" title="Kurang Baik">{{ $stats['kurang_baik'] ?? 0 }} KB</span>
                    <span class="badge bg-danger" title="Rusak Berat">{{ $stats['rusak_berat'] ?? 0 }} RB</span>
                </div>
                <span class="text-muted small mt-1">Standar Permendagri 19</span>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('bangunan.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-12 col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control bg-light border-start-0" 
                               placeholder="Cari nama, kode, register, PBG..." value="{{ $filters['q'] ?? '' }}">
                    </div>
                </div>

                @if(auth()->user()->role !== \App\Enums\UserRole::OPD)
                <div class="col-12 col-md-3">
                    <select name="opd_id" class="form-select form-select-sm bg-light">
                        <option value="">-- Semua OPD / Instansi --</option>
                        @foreach($opds as $opd)
                            <option value="{{ $opd->id }}" {{ ($filters['opd_id'] ?? '') == $opd->id ? 'selected' : '' }}>
                                {{ $opd->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-6 col-md-2">
                    <select name="kondisi" class="form-select form-select-sm bg-light">
                        <option value="">-- Semua Kondisi --</option>
                        <option value="Baik" {{ ($filters['kondisi'] ?? '') === 'Baik' ? 'selected' : '' }}>Baik (B)</option>
                        <option value="Kurang Baik" {{ ($filters['kondisi'] ?? '') === 'Kurang Baik' ? 'selected' : '' }}>Kurang Baik (KB)</option>
                        <option value="Rusak Berat" {{ ($filters['kondisi'] ?? '') === 'Rusak Berat' ? 'selected' : '' }}>Rusak Berat (RB)</option>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <select name="kecamatan_id" class="form-select form-select-sm bg-light">
                        <option value="">-- Semua Kecamatan --</option>
                        @foreach($kecamatans as $kec)
                            <option value="{{ $kec->id }}" {{ ($filters['kecamatan_id'] ?? '') == $kec->id ? 'selected' : '' }}>
                                {{ $kec->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill w-100">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    @if(!empty(array_filter($filters ?? [])))
                        <a href="{{ route('bangunan.index') }}" class="btn btn-sm btn-light rounded-pill" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-uppercase text-muted" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                        <th class="ps-4" style="width: 50px;">No</th>
                        <th>Identitas Bangunan</th>
                        <th>OPD Pengguna</th>
                        <th>Karakteristik Fisik</th>
                        <th>Kondisi</th>
                        <th>Tanah Dasar (KIB A)</th>
                        <th>Nilai Perolehan</th>
                        <th class="text-end pe-4" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bangunans as $index => $b)
                        <tr>
                            <td class="ps-4 text-muted small">{{ $bangunans->firstItem() + $index }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-3 p-2 text-primary" style="background: rgba(30, 64, 175, 0.08);">
                                        <i class="bi {{ is_object($b->jenis_bangunan) ? $b->jenis_bangunan->icon() : 'bi-building' }} fs-5"></i>
                                    </div>
                                    <div>
                                        <a href="{{ route('bangunan.show', $b) }}" class="fw-bold text-dark text-decoration-none d-block">
                                            {{ $b->nama_bangunan }}
                                        </a>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.7rem;">
                                                {{ $b->kode_bangunan }}
                                            </span>
                                            @if($b->nomor_register)
                                                <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.7rem;">
                                                    Reg: {{ $b->nomor_register }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="fw-medium text-dark small d-block">{{ $b->opdSipat?->nama ?? '-' }}</span>
                                <span class="text-muted small" style="font-size: 0.75rem;">
                                    <i class="bi bi-geo-alt"></i> {{ $b->kecamatan?->nama ?? 'Wilayah Belum Ditentukan' }}
                                </span>
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark">{{ number_format($b->luas_lantai, 2, ',', '.') }} m²</div>
                                <span class="text-muted small" style="font-size: 0.75rem;">
                                    {{ $b->konstruksi_tingkat ? "Tingkat ({$b->jumlah_lantai} Lantai)" : '1 Lantai' }} • 
                                    {{ $b->konstruksi_beton ? 'Beton' : 'Bukan Beton' }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $kondisiBadge = match(is_object($b->kondisi) ? $b->kondisi->value : $b->kondisi) {
                                        'Baik' => 'bg-success-subtle text-success border border-success-subtle',
                                        'Kurang Baik' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                        'Rusak Berat' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                        default => 'bg-secondary-subtle text-secondary',
                                    };
                                @endphp
                                <span class="badge rounded-pill px-2.5 py-1 {{ $kondisiBadge }}">
                                    {{ is_object($b->kondisi) ? $b->kondisi->value : $b->kondisi }}
                                </span>
                            </td>
                            <td>
                                @if($b->asetTanah)
                                    <a href="{{ route('sipat.aset.show', $b->asetTanah) }}" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none" title="NIBAR KIB A">
                                        <i class="bi bi-link-45deg"></i> {{ $b->asetTanah->kode_aset ?? 'KIB A' }}
                                    </a>
                                @else
                                    <span class="badge bg-light text-muted border">
                                        {{ $b->status_tanah_dasar ?: 'Non-KIB A' }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-dark small">
                                    Rp {{ number_format($b->harga_perolehan, 0, ',', '.') }}
                                </div>
                                <span class="text-muted" style="font-size: 0.75rem;">Th. {{ $b->tahun_pengadaan ?: '-' }}</span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('bangunan.show', $b) }}" class="btn btn-sm btn-light rounded-pill px-2" title="Detail Profil">
                                        <i class="bi bi-eye text-primary"></i>
                                    </a>
                                    <a href="{{ route('bangunan.edit', $b) }}" class="btn btn-sm btn-light rounded-pill px-2" title="Edit Data">
                                        <i class="bi bi-pencil-square text-secondary"></i>
                                    </a>
                                    @if(auth()->user()->role !== \App\Enums\UserRole::OPD)
                                        <button type="button" class="btn btn-sm btn-light rounded-pill px-2 btn-delete" 
                                                data-id="{{ $b->id }}" data-nama="{{ $b->nama_bangunan }}" title="Hapus Data">
                                            <i class="bi bi-trash text-danger"></i>
                                        </button>
                                        <form id="delete-form-{{ $b->id }}" action="{{ route('bangunan.destroy', $b) }}" method="POST" class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-building-slash display-4 d-block mb-3 opacity-50"></i>
                                    <h6 class="fw-semibold">Belum Ada Data Gedung dan Bangunan</h6>
                                    <p class="small mb-3">Tambahkan data secara manual atau gunakan AI Smart Import untuk mengunggah berkas Excel.</p>
                                    <a href="{{ route('bangunan.create') }}" class="btn btn-sm btn-primary rounded-pill px-3">
                                        <i class="bi bi-plus-lg me-1"></i> Tambah Bangunan Baru
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($bangunans->hasPages())
            <div class="card-footer bg-white border-0 py-3 px-4">
                {{ $bangunans->links() }}
            </div>
        @endif
    </div>
</div>

@include('bangunan.partials.modal-smart-import')

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handler Hapus SweetAlert2
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nama = this.getAttribute('data-nama');

            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: `Apakah Anda yakin ingin menghapus data bangunan "${nama}"? Aksi ini tidak dapat dibatalkan.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Ya, Hapus Data',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(`delete-form-${id}`).submit();
                }
            });
        });
    });
});
</script>
@endsection
