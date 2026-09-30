@extends('layouts.app')

@section('title', 'Katalog BPKB Kendaraan - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Katalog BPKB</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0">Katalog BPKB Kendaraan ({{ $vehicleLabel }})</h4>
        </div>
        <div class="action-toolbar d-flex flex-wrap gap-2">
            <a href="{{ route('elabel.bpkb.export', request()->all()) }}" class="btn btn-outline-success shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </a>
            <button type="button" class="btn btn-outline-primary shadow-sm fw-medium d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-file-earmark-arrow-up"></i> Import Excel
            </button>
            <a href="{{ route('elabel.bpkb.create', ['type' => request('type')]) }}" class="btn btn-primary shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i> Tambah BPKB
            </a>
        </div>
    </div>

    <!-- CATEGORY FILTER TABS -->
    <ul class="nav nav-tabs nav-fill mb-4 border-bottom" role="tablist">
        <li class="nav-item">
            <a href="{{ route('elabel.bpkb.index') }}" class="nav-link fw-bold py-3 d-flex align-items-center justify-content-center gap-2 {{ !$vehicleType ? 'active text-navy border-bottom border-primary border-3' : 'text-secondary' }}">
                <i class="bi bi-collection-fill"></i> Semua BPKB
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('elabel.bpkb.index', ['type' => 'r4']) }}" class="nav-link fw-bold py-3 d-flex align-items-center justify-content-center gap-2 {{ $vehicleType === 'R4' ? 'active text-navy border-bottom border-primary border-3' : 'text-secondary' }}">
                <i class="bi bi-car-front-fill text-primary"></i> R4 (Mobil)
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('elabel.bpkb.index', ['type' => 'r2']) }}" class="nav-link fw-bold py-3 d-flex align-items-center justify-content-center gap-2 {{ $vehicleType === 'R2' ? 'active text-navy border-bottom border-primary border-3' : 'text-secondary' }}">
                <i class="bi bi-bicycle text-success"></i> R2 (Motor)
            </a>
        </li>
    </ul>

    <!-- SEARCH & TABLE CARD -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            @php
                $hasAdvancedFilter = request()->filled('year') || request()->filled('opd_id') || request()->filled('status') || request()->filled('nibar_status');
            @endphp
            <form action="{{ route('elabel.bpkb.index') }}" method="GET" id="filterForm">
                @if(request('type'))
                    <input type="hidden" name="type" value="{{ request('type') }}">
                @endif
                <div class="row g-2 align-items-center mb-2">
                    <div class="col-12 col-lg-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-secondary"><i class="bi bi-search"></i></span>
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control border-start-0 shadow-none" placeholder="Cari No. Polisi, No. BPKB, NIBAR, Mesin, Rangka, Merk, Box...">
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-3">
                        <div class="input-group">
                            <span class="input-group-text bg-white small text-secondary"><i class="bi bi-file-earmark-pdf"></i></span>
                            <select name="pdf_status" class="form-select shadow-none" onchange="document.getElementById('filterForm').submit()">
                                <option value="">Semua Status Scan PDF</option>
                                <option value="no_pdf" {{ request('pdf_status') === 'no_pdf' ? 'selected' : '' }}>🔴 Belum Ada Scan PDF</option>
                                <option value="has_pdf" {{ request('pdf_status') === 'has_pdf' ? 'selected' : '' }}>🟢 Sudah Ada Scan PDF</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6 col-md-2 col-lg-2">
                        <div class="input-group">
                            <span class="input-group-text bg-white small text-secondary"><i class="bi bi-archive"></i></span>
                            <select name="box_id" class="form-select shadow-none" onchange="document.getElementById('filterForm').submit()">
                                <option value="">Semua Box</option>
                                @foreach($boxes as $box)
                                    <option value="{{ $box->id }}" {{ request('box_id') == $box->id ? 'selected' : '' }}>Box {{ $box->box_code }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-2 col-lg-2 d-flex gap-1">
                        <button type="submit" class="btn btn-primary flex-grow-1 fw-medium" title="Terapkan Filter">
                            <i class="bi bi-funnel"></i> <span class="d-none d-lg-inline">Filter</span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary {{ $hasAdvancedFilter ? 'btn-primary text-white' : '' }}" data-bs-toggle="collapse" data-bs-target="#advancedFilters" aria-expanded="{{ $hasAdvancedFilter ? 'true' : 'false' }}" aria-controls="advancedFilters" title="Filter Lanjutan (Tahun, OPD, Status, NIBAR)">
                            <i class="bi bi-sliders"></i>
                        </button>
                        <a href="{{ route('elabel.bpkb.index', array_filter(['type' => request('type')])) }}" class="btn btn-light border bg-white" title="Reset Semua Filter"><i class="bi bi-arrow-clockwise"></i></a>
                    </div>
                </div>

                <!-- SECOND ROW: COLLAPSIBLE ADVANCED FILTERS (Tahun, OPD, Status, NIBAR, Tampil) -->
                <div class="collapse {{ $hasAdvancedFilter ? 'show' : '' }}" id="advancedFilters">
                    <div class="row g-2 align-items-center pt-2 border-top mt-1">
                        <div class="col-6 col-md-3 col-lg-2">
                            <select name="year" class="form-select form-select-sm shadow-none" onchange="document.getElementById('filterForm').submit()">
                                <option value="">Semua Tahun</option>
                                @foreach($years as $yr)
                                    <option value="{{ $yr }}" {{ request('year') == $yr ? 'selected' : '' }}>Tahun {{ $yr }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-3 col-lg-4">
                            <select name="opd_id" class="form-select form-select-sm shadow-none" onchange="document.getElementById('filterForm').submit()">
                                <option value="">Semua OPD / Instansi</option>
                                @foreach($opds as $opd)
                                    <option value="{{ $opd->id }}" {{ request('opd_id') == $opd->id ? 'selected' : '' }}>{{ Str::limit($opd->nama, 36) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-2 col-lg-2">
                            <select name="status" class="form-select form-select-sm shadow-none" onchange="document.getElementById('filterForm').submit()">
                                <option value="">Semua Status Fisik</option>
                                <option value="Tersedia" {{ request('status') === 'Tersedia' ? 'selected' : '' }}>Tersedia</option>
                                <option value="Dipinjam" {{ request('status') === 'Dipinjam' ? 'selected' : '' }}>Dipinjam</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2 col-lg-2">
                            <select name="nibar_status" class="form-select form-select-sm shadow-none" onchange="document.getElementById('filterForm').submit()">
                                <option value="">Semua NIBAR</option>
                                <option value="has_nibar" {{ request('nibar_status') === 'has_nibar' ? 'selected' : '' }}>Ada NIBAR</option>
                                <option value="no_nibar" {{ request('nibar_status') === 'no_nibar' ? 'selected' : '' }}>Tanpa NIBAR</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2 col-lg-2">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-secondary">Tampil</span>
                                <select name="per_page" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                                    <option value="15" {{ request('per_page', 15) == '15' ? 'selected' : '' }}>15</option>
                                    <option value="25" {{ request('per_page') == '25' ? 'selected' : '' }}>25</option>
                                    <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100</option>
                                    <option value="all" {{ request('per_page') == 'all' ? 'selected' : '' }}>Semua</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        @if(request('pdf_status') || request('box_id') || request('year') || request('opd_id') || request('status') || request('nibar_status') || request('q'))
            <div class="px-4 py-2 bg-light border-top border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex flex-wrap align-items-center gap-2 small">
                    <span class="text-secondary"><i class="bi bi-funnel-fill me-1 text-primary"></i>Filter aktif:</span>
                    @if(request('pdf_status') === 'no_pdf')
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                            <i class="bi bi-file-earmark-x me-1"></i>Belum Ada Scan PDF
                            <a href="{{ route('elabel.bpkb.index', request()->except('pdf_status', 'page')) }}" class="text-danger ms-1 text-decoration-none">&times;</a>
                        </span>
                    @elseif(request('pdf_status') === 'has_pdf')
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                            <i class="bi bi-file-earmark-check me-1"></i>Sudah Ada Scan PDF
                            <a href="{{ route('elabel.bpkb.index', request()->except('pdf_status', 'page')) }}" class="text-success ms-1 text-decoration-none">&times;</a>
                        </span>
                    @endif
                    @if(request('box_id'))
                        @php $activeBox = $boxes->firstWhere('id', request('box_id')); @endphp
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">
                            Box: {{ $activeBox ? $activeBox->box_code : request('box_id') }}
                            <a href="{{ route('elabel.bpkb.index', request()->except('box_id', 'page')) }}" class="text-primary ms-1 text-decoration-none">&times;</a>
                        </span>
                    @endif
                    @if(request('year'))
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1">
                            Tahun: {{ request('year') }}
                            <a href="{{ route('elabel.bpkb.index', request()->except('year', 'page')) }}" class="text-secondary ms-1 text-decoration-none">&times;</a>
                        </span>
                    @endif
                    @if(request('opd_id'))
                        @php $activeOpd = $opds->firstWhere('id', request('opd_id')); @endphp
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1">
                            OPD: {{ $activeOpd ? Str::limit($activeOpd->nama, 20) : request('opd_id') }}
                            <a href="{{ route('elabel.bpkb.index', request()->except('opd_id', 'page')) }}" class="text-secondary ms-1 text-decoration-none">&times;</a>
                        </span>
                    @endif
                    @if(request('status'))
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1">
                            Status: {{ request('status') }}
                            <a href="{{ route('elabel.bpkb.index', request()->except('status', 'page')) }}" class="text-secondary ms-1 text-decoration-none">&times;</a>
                        </span>
                    @endif
                    @if(request('nibar_status'))
                        <span class="badge bg-info bg-opacity-10 text-info-emphasis border border-info border-opacity-25 px-2 py-1">
                            NIBAR: {{ request('nibar_status') === 'has_nibar' ? 'Ada NIBAR' : 'Tanpa NIBAR' }}
                            <a href="{{ route('elabel.bpkb.index', request()->except('nibar_status', 'page')) }}" class="text-info-emphasis ms-1 text-decoration-none">&times;</a>
                        </span>
                    @endif
                    @if(request('q'))
                        <span class="badge bg-dark bg-opacity-10 text-dark border px-2 py-1">
                            Cari: "{{ request('q') }}"
                            <a href="{{ route('elabel.bpkb.index', request()->except('q', 'page')) }}" class="text-dark ms-1 text-decoration-none">&times;</a>
                        </span>
                    @endif
                    <span class="text-muted ms-2">(Ditemukan <strong>{{ number_format($items->total()) }}</strong> data)</span>
                </div>
                <div>
                    <a href="{{ route('elabel.bpkb.index', array_filter(['type' => request('type')])) }}" class="btn btn-sm btn-link text-danger text-decoration-none p-0 small">
                        <i class="bi bi-x-circle me-1"></i>Hapus Semua Filter
                    </a>
                </div>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4 text-center" style="width: 50px;">No.</th>
                        <th class="py-3">No. Polisi / Tahun</th>
                        <th class="py-3">Identitas Dokumen (BPKB/NIBAR)</th>
                        <th class="py-3">No. Mesin / No. Rangka</th>
                        <th class="py-3">Spesifikasi (Merk/Tipe/Warna)</th>
                        <th class="py-3">Pemegang / Dinas</th>
                        <th class="py-3 text-center">Box Fisik</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3 px-4 text-center" style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td class="px-4 text-center fw-medium text-secondary">{{ $items->firstItem() ? ($items->firstItem() + $loop->index) : $loop->iteration }}</td>
                            <td>
                                <span class="badge bg-light text-dark border px-3 py-2 fs-6 rounded-3 fw-bold">{{ $item->plate_number }}</span>
                                <div class="small text-secondary mt-1"><i class="bi bi-calendar3 me-1"></i> Tahun {{ $item->year ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold text-navy"><i class="bi bi-file-earmark-text me-1 text-primary"></i> {{ $item->no_bpkb ?: '-' }}</div>
                                <div class="small text-secondary">NIBAR: {{ $item->nibar ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="small"><span class="text-secondary">Mesin:</span> <strong class="font-monospace text-navy">{{ $item->no_mesin ?: '-' }}</strong></div>
                                <div class="small mt-0.5"><span class="text-secondary">Rangka:</span> <strong class="font-monospace text-dark">{{ $item->no_rangka ?: '-' }}</strong></div>
                            </td>
                            <td>
                                <div class="fw-medium text-dark">{{ $item->merek ?: '-' }} {{ $item->tipe ?: '' }}</div>
                                <div class="small text-secondary">{{ $item->isi_silinder ?: '-' }} · {{ $item->warna ?: '-' }}</div>
                            </td>
                            <td style="max-width: 220px;">
                                <div class="fw-semibold text-dark text-truncate" title="{{ $item->pengguna ?: '-' }}"><i class="bi bi-person-fill text-secondary me-1"></i>{{ $item->pengguna ?: '-' }}</div>
                                <div class="small text-secondary text-truncate" title="{{ $item->opdSipat ? $item->opdSipat->nama : '-' }}"><i class="bi bi-building me-1"></i>{{ $item->opdSipat ? $item->opdSipat->nama : '-' }}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-3 fw-bold">
                                    <i class="bi bi-archive me-1"></i> {{ $item->box->box_code ?? '-' }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($item->status === 'Tersedia')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-1 rounded-pill fw-medium">Tersedia</span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning text-dark border border-warning border-opacity-25 px-3 py-1 rounded-pill fw-medium">{{ $item->status }}</span>
                                @endif
                            </td>
                            <td class="px-3 text-center">
                                <div class="d-flex justify-content-center gap-1" style="white-space: nowrap;">
                                    @if($item->pdf_path)
                                        <a href="{{ route('elabel.bpkb.view-pdf', $item->id) }}" 
                                           data-pdf-preview="true"
                                           data-pdf-url="{{ route('elabel.bpkb.view-pdf', $item->id) }}"
                                           data-pdf-title="Scan BPKB {{ $item->plate_number }}"
                                           data-pdf-subtitle="No. BPKB: {{ $item->no_bpkb ?: '-' }} | Box: {{ $item->box->box_code ?? '-' }}"
                                           data-pdf-badge="{{ $item->vehicle_type }}"
                                           class="btn btn-sm btn-light border text-danger fw-medium d-inline-flex align-items-center gap-1" 
                                           title="Pratinjau Scan BPKB">
                                            <i class="bi bi-file-earmark-pdf"></i> <span class="d-none d-md-inline" style="font-size: 11px;">PDF</span>
                                        </a>
                                    @endif
                                    <a href="{{ route('elabel.bpkb.show', $item->id) }}" class="btn btn-sm btn-light border text-navy" title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('elabel.bpkb.edit', $item->id) }}" class="btn btn-sm btn-light border text-primary" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-light border text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $item->id }}" title="Keluarkan BPKB">
                                        <i class="bi bi-box-arrow-right"></i>
                                    </button>
                                </div>

                                <!-- DELETE/OUT MODAL FOR EACH BPKB -->
                                <div class="modal fade" id="deleteModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content rounded-4 border-0 shadow">
                                            <form action="{{ route('elabel.bpkb.delete', $item->id) }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <div class="modal-header border-bottom px-4 py-3">
                                                    <h5 class="modal-title fw-bold text-navy"><i class="bi bi-box-arrow-right text-danger me-2"></i> Memindahkan BPKB {{ $item->plate_number }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4 text-start">
                                                    <div class="alert alert-warning border-0 bg-warning bg-opacity-10 text-dark small mb-3">
                                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Data BPKB akan dipindahkan ke daftar <strong>BPKB Keluar</strong>.
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold small">Alasan Penghapusan/Keluar <span class="text-danger">*</span></label>
                                                        <select name="reason" class="form-select" required>
                                                            <option value="Di pinjam">Di pinjam</option>
                                                            <option value="Penjualan">Penjualan</option>
                                                            <option value="Dihibahkan">Dihibahkan</option>
                                                            <option value="Kendaraan hilang">Kendaraan hilang</option>
                                                            <option value="Kendaraan tidak ditemukan">Kendaraan tidak ditemukan</option>
                                                            <option value="Lainnya">Lainnya</option>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold small">Keterangan Tambahan</label>
                                                        <textarea name="reason_detail" class="form-control" rows="2" placeholder="Catatan opsional..."></textarea>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold small">Dokumen Pendukung (PDF/JPG/PNG Max 5MB)</label>
                                                        <input type="file" name="support_doc" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold small text-danger">Konfirmasi Password Login Anda <span class="text-danger">*</span></label>
                                                        <input type="password" name="delete_password" class="form-control" placeholder="Masukkan password anda" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4">
                                                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-danger fw-semibold">Pindahkan ke BPKB Keluar</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-secondary">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i> Belum ada data BPKB terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white border-top py-3 px-4 d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-2 small">
                <span class="badge bg-light text-navy border px-3 py-2 fw-medium me-1">
                    <i class="bi bi-journal-bookmark-fill text-primary me-1"></i> Total BPKB: <strong>{{ number_format($totalBpkbCount ?? 0) }}</strong>
                </span>
                <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle px-3 py-2 fw-medium">
                    <i class="bi bi-upc-scan text-primary me-1"></i> Ada NIBAR: <strong>{{ number_format($totalBpkbWithNibarCount ?? 0) }}</strong> <span class="fw-normal text-secondary">({{ ($totalBpkbCount ?? 0) > 0 ? round((($totalBpkbWithNibarCount ?? 0) / $totalBpkbCount) * 100, 1) : 0 }}%)</span>
                </span>
                <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-3 py-2 fw-medium">
                    <i class="bi bi-file-earmark-check-fill text-success me-1"></i> Ada File PDF: <strong>{{ number_format($totalBpkbWithFileCount ?? 0) }}</strong> <span class="fw-normal text-secondary">({{ ($totalBpkbCount ?? 0) > 0 ? round((($totalBpkbWithFileCount ?? 0) / $totalBpkbCount) * 100, 1) : 0 }}%)</span>
                </span>
            </div>
            @if($items->hasPages())
                <div class="pagination-sm mb-0">
                    {{ $items->links() }}
                </div>
            @endif
        </div>
    </div>

</div>

<!-- IMPORT MODAL -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('elabel.bpkb.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="vehicle_type_context" value="{{ request('type') }}">
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-navy"><i class="bi bi-file-earmark-arrow-up text-primary me-2"></i> Import Data BPKB</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="border rounded-3 p-3 bg-light mb-3">
                        <div class="fw-bold text-dark mb-1">Download Format Import</div>
                        <p class="small text-secondary mb-2">Gunakan format Excel standar untuk mengunggah banyak data sekaligus.</p>
                        <a href="{{ route('elabel.bpkb.template', ['type' => request('type')]) }}" class="btn btn-sm btn-outline-primary fw-medium">
                            <i class="bi bi-download me-1"></i> Download Format XLSX
                        </a>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Pilih File Excel (XLSX, XLS, CSV)</label>
                        <input type="file" name="import_file" class="form-control" accept=".xlsx, .xls, .csv" required>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Proses Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
