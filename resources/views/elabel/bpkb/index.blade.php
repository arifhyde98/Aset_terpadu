@extends('layouts.app')

@section('title', 'Katalog BPKB Kendaraan - eLABEL')

@section('content')
@php
    $currentYear = (int) date('Y') + 1;
    $editYearRange = range($currentYear, 1970);
    $allEditYears = array_values(array_unique(array_merge($years ?? [], $editYearRange)));
    rsort($allEditYears);
@endphp
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
                            <span class="input-group-text bg-white border-end-0 text-secondary" id="searchIconSpan">
                                <i class="bi bi-search" id="searchIcon"></i>
                            </span>
                            <input type="text" name="q" id="searchInput" value="{{ request('q') }}" class="form-control border-start-0 border-end-0 shadow-none ps-1" placeholder="Cari No. Polisi, No. BPKB, NIBAR, Mesin, Rangka, Merk, Box..." autocomplete="off">
                            <button type="button" class="btn btn-white bg-white border border-start-0 border-end-0 text-muted px-2.5" id="btnClearSearch" style="{{ request('q') ? '' : 'display: none;' }}" title="Hapus kata kunci pencarian">
                                <i class="bi bi-x-circle-fill text-secondary"></i>
                            </button>
                            <span class="input-group-text bg-white border-start-0 text-muted small d-none d-md-flex align-items-center pe-2.5" title="Tekan / atau Ctrl+K untuk fokus pencarian">
                                <kbd class="bg-light text-secondary border small px-1.5 py-0.5 rounded" style="font-size: 10px;">Ctrl+K</kbd>
                            </span>
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
                        @php
                            $bpkbData = [
                                'id'           => $item->id,
                                'plate_number' => $item->plate_number,
                                'vehicle_type' => $item->vehicle_type,
                                'year'         => (string) ($item->year ?: ''),
                                'no_bpkb'      => $item->no_bpkb ?: '',
                                'nibar'        => $item->nibar ?: '',
                                'no_rangka'    => $item->no_rangka ?: '',
                                'no_mesin'     => $item->no_mesin ?: '',
                                'merek'        => $item->merek ?: '',
                                'tipe'         => $item->tipe ?: '',
                                'isi_silinder' => $item->isi_silinder ?: '',
                                'warna'        => $item->warna ?: '',
                                'pengguna'     => $item->pengguna ?: '',
                                'sipat_opd_id' => (string) ($item->sipat_opd_id ?: ''),
                                'opd_nama'     => $item->opdSipat ? $item->opdSipat->nama : '-',
                                'box_code'     => $item->box ? $item->box->box_code : '-',
                                'box_location' => $item->box ? ($item->box->location ?: 'Lokasi belum diatur') : '-',
                                'status'       => $item->status ?: 'Tersedia',
                                'pdf_path'     => $item->pdf_path ?: '',
                                'pdf_url'      => $item->pdf_path ? route('elabel.bpkb.view-pdf', $item->id) : '',
                                'created_at'   => $item->created_at ? $item->created_at->format('d M Y H:i') : '-',
                                'updated_at'   => $item->updated_at ? $item->updated_at->format('d M Y H:i') : '-',
                                'input_user'   => $item->inputUser ? $item->inputUser->name : ($item->input_by ? 'User #' . $item->input_by : '-'),
                                'update_url'   => route('elabel.bpkb.update', $item->id),
                            ];
                        @endphp
                        <tr data-bpkb="{{ json_encode($bpkbData) }}">
                            <td class="px-4 text-center fw-medium text-secondary">{{ $items->firstItem() ? ($items->firstItem() + $loop->index) : $loop->iteration }}</td>
                            <td>
                                <span class="badge bg-light text-dark border px-3 py-2 fs-6 rounded-3 fw-bold btn-view-bpkb" style="cursor: pointer;" title="Klik untuk melihat detail">{{ $item->plate_number }}</span>
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
                                    <button type="button" class="btn btn-sm btn-light border text-navy btn-view-bpkb" title="Lihat Detail BPKB">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light border text-primary btn-edit-bpkb" title="Edit Data BPKB">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
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
                                <div class="py-4">
                                    <div class="mb-3">
                                        <i class="bi bi-search text-muted opacity-50" style="font-size: 2.8rem;"></i>
                                    </div>
                                    @if(request('q'))
                                        <h6 class="fw-bold text-navy mb-1">Tidak ada BPKB yang cocok dengan "{{ request('q') }}"</h6>
                                        <p class="small text-secondary mb-3">Coba periksa kembali ejaan plat nomor, nomor BPKB, NIBAR, atau gunakan kata kunci yang lebih umum.</p>
                                        <a href="{{ route('elabel.bpkb.index', request()->except('q', 'page')) }}" class="btn btn-sm btn-outline-primary fw-medium px-3">
                                            <i class="bi bi-arrow-clockwise me-1"></i> Reset Pencarian
                                        </a>
                                    @else
                                        <h6 class="fw-bold text-navy mb-1">Belum ada data BPKB terdaftar</h6>
                                        <p class="small text-secondary mb-0">Silakan gunakan tombol "+ Tambah BPKB" di atas untuk menambahkan arsip baru.</p>
                                    @endif
                                </div>
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

<!-- DETAIL BPKB MODAL -->
<div class="modal fade" id="modalDetailBpkb" tabindex="-1" aria-labelledby="modalDetailBpkbLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom px-4 py-3 bg-light rounded-top-4">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h5 class="modal-title fw-bold text-navy mb-0" id="modalDetailBpkbLabel">
                        <i class="bi bi-info-circle text-primary me-2"></i> Detail BPKB <span id="detailPlateTitle" class="badge bg-light text-navy border ms-1 font-monospace"></span>
                    </h5>
                    <span id="detailVehicleBadge" class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2.5 py-1 rounded-pill small"></span>
                    <span id="detailStatusBadge" class="badge rounded-pill small"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-7">
                        <div class="card border rounded-3 p-3 h-100 bg-white shadow-none">
                            <h6 class="fw-bold text-navy mb-3 d-flex align-items-center gap-2">
                                <i class="bi bi-card-text text-primary"></i> Identitas Kendaraan & Dokumen
                            </h6>
                            <table class="table table-sm table-borderless align-middle mb-0 small">
                                <tbody>
                                    <tr>
                                        <td class="text-secondary fw-semibold" style="width: 140px;">No. Polisi</td>
                                        <td class="fw-bold text-navy fs-6">: <span id="detailPlateNumber" class="font-monospace">-</span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary fw-semibold">Tahun / Jenis</td>
                                        <td class="fw-medium text-dark">: <span id="detailYear">-</span> / <span id="detailVehicleType">-</span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary fw-semibold">No. BPKB</td>
                                        <td class="fw-semibold text-dark">: <span id="detailNoBpkb" class="font-monospace text-primary">-</span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary fw-semibold">NIBAR</td>
                                        <td class="fw-medium text-dark">: <span id="detailNibar" class="font-monospace text-secondary">-</span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary fw-semibold">No. Rangka</td>
                                        <td class="fw-medium text-dark">: <span id="detailNoRangka" class="font-monospace">-</span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary fw-semibold">No. Mesin</td>
                                        <td class="fw-medium text-dark">: <span id="detailNoMesin" class="font-monospace text-navy">-</span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary fw-semibold">Merek / Tipe</td>
                                        <td class="fw-medium text-dark">: <span id="detailMerekTipe">-</span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary fw-semibold">Silinder / Warna</td>
                                        <td class="fw-medium text-dark">: <span id="detailSilinderWarna">-</span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary fw-semibold">Pemegang</td>
                                        <td class="fw-medium text-dark">: <span id="detailPengguna">-</span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary fw-semibold">Dinas / OPD</td>
                                        <td class="fw-medium text-dark">: <span id="detailOpdNama">-</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-12 col-md-5 d-flex flex-column gap-3">
                        <!-- Box Fisik -->
                        <div class="card border rounded-3 p-3 bg-light shadow-none">
                            <h6 class="fw-bold text-navy mb-2 d-flex align-items-center gap-2">
                                <i class="bi bi-archive text-primary"></i> Penyimpanan Box Fisik
                            </h6>
                            <div class="text-center py-2 bg-white rounded-3 border">
                                <div class="text-uppercase fw-semibold text-secondary" style="font-size: 11px;">Kode Box Fisik</div>
                                <h3 class="fw-extrabold text-primary mb-0 mt-1" id="detailBoxCode">-</h3>
                                <div class="small text-secondary mt-1"><i class="bi bi-geo-alt me-1 text-danger"></i> <span id="detailBoxLocation">-</span></div>
                            </div>
                        </div>

                        <!-- Scan PDF Status -->
                        <div class="card border rounded-3 p-3 bg-white shadow-none flex-grow-1">
                            <h6 class="fw-bold text-navy mb-2 d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-pdf text-danger"></i> Berkas Scan Fisik
                            </h6>
                            <div id="detailPdfAvailable" class="d-none">
                                <div class="p-2.5 bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-3 mb-2 small fw-medium d-flex align-items-center gap-2">
                                    <i class="bi bi-check-circle-fill fs-6"></i> File scan fisik BPKB tersedia
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="button" 
                                            id="detailPdfPreviewBtn"
                                            class="btn btn-sm btn-outline-danger fw-medium d-inline-flex align-items-center justify-content-center gap-1.5"
                                            data-pdf-preview="true"
                                            data-pdf-url=""
                                            data-pdf-title=""
                                            data-pdf-subtitle=""
                                            data-pdf-badge="">
                                        <i class="bi bi-eye"></i> Pratinjau Dokumen PDF
                                    </button>
                                    <a href="#" id="detailPdfDownloadBtn" target="_blank" class="btn btn-sm btn-light border text-secondary fw-medium d-inline-flex align-items-center justify-content-center gap-1">
                                        <i class="bi bi-download"></i> Buka / Unduh File
                                    </a>
                                </div>
                            </div>
                            <div id="detailPdfMissing" class="p-3 bg-warning bg-opacity-10 text-warning text-dark border border-warning border-opacity-25 rounded-3 small">
                                <i class="bi bi-exclamation-triangle-fill me-1 text-warning"></i> Belum ada file scan fisik BPKB yang diunggah.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Audit Riwayat -->
                <div class="card border rounded-3 p-3 bg-light shadow-none">
                    <div class="row g-2 text-secondary small">
                        <div class="col-12 col-md-4">
                            <i class="bi bi-person me-1"></i> Input Oleh: <strong class="text-dark" id="detailInputUser">-</strong>
                        </div>
                        <div class="col-12 col-md-4">
                            <i class="bi bi-clock me-1"></i> Dibuat: <strong class="text-dark" id="detailCreatedAt">-</strong>
                        </div>
                        <div class="col-12 col-md-4">
                            <i class="bi bi-pencil me-1"></i> Diperbarui: <strong class="text-dark" id="detailUpdatedAt">-</strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4 d-flex justify-content-between">
                <button type="button" class="btn btn-primary fw-medium d-inline-flex align-items-center gap-1.5" id="btnDetailToEdit">
                    <i class="bi bi-pencil-square"></i> Edit Data Ini
                </button>
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT BPKB MODAL -->
<div class="modal fade" id="modalEditBpkb" tabindex="-1" aria-labelledby="modalEditBpkbLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="editBpkbForm" action="" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-bottom px-4 py-3 bg-light rounded-top-4">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h5 class="modal-title fw-bold text-navy mb-0" id="modalEditBpkbLabel">
                            <i class="bi bi-pencil-square text-primary me-2"></i> Edit Data BPKB <span id="editPlateTitle" class="badge bg-light text-navy border ms-1 font-monospace"></span>
                        </h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Tahun Dokumen <span class="text-danger">*</span></label>
                            <select name="year" id="edit_year" class="form-select" required>
                                <option value="">-- Pilih Tahun --</option>
                                @foreach($allEditYears as $yr)
                                    <option value="{{ $yr }}">{{ $yr }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Jenis Kendaraan <span class="text-danger">*</span></label>
                            <select name="vehicle_type" id="edit_vehicle_type" class="form-select" required>
                                <option value="R4">R4 (Mobil)</option>
                                <option value="R2">R2 (Motor)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">No. Polisi <span class="text-danger">*</span></label>
                            <input type="text" name="plate_number" id="edit_plate_number" class="form-control" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">No. BPKB</label>
                            <input type="text" name="no_bpkb" id="edit_no_bpkb" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">NIBAR</label>
                            <input type="text" name="nibar" id="edit_nibar" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">No. Rangka</label>
                            <input type="text" name="no_rangka" id="edit_no_rangka" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">No. Mesin</label>
                            <input type="text" name="no_mesin" id="edit_no_mesin" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Merek</label>
                            <input type="text" name="merek" id="edit_merek" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Tipe / Model</label>
                            <input type="text" name="tipe" id="edit_tipe" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Isi Silinder (CC)</label>
                            <input type="text" name="isi_silinder" id="edit_isi_silinder" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Warna</label>
                            <input type="text" name="warna" id="edit_warna" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Pemegang Kendaraan (Personal)</label>
                            <input type="text" name="pengguna" id="edit_pengguna" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Dinas / OPD (SIPAT)</label>
                            <select name="sipat_opd_id" id="edit_sipat_opd_id" class="form-select searchable-select" data-placeholder="Ketik untuk mencari Dinas / OPD...">
                                <option value="">-- Pilih Dinas / OPD --</option>
                                @foreach($opds as $opd)
                                    <option value="{{ $opd->id }}">{{ $opd->nama }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">File Scan BPKB (PDF, JPG, PNG Max 20MB)</label>
                            <input type="file" name="pdf" id="edit_pdf" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            <div id="editPdfCurrentContainer" class="d-none align-items-center justify-content-between mt-2 p-2 bg-light rounded-3 border">
                                <span class="small text-success fw-medium">
                                    <i class="bi bi-file-earmark-check me-1"></i> Scan fisik tersimpan di sistem
                                </span>
                                <button type="button" 
                                        id="editPdfPreviewBtn"
                                        class="btn btn-sm btn-outline-danger py-0 px-2 fw-medium d-inline-flex align-items-center gap-1"
                                        style="font-size: 11.5px; height: 26px;"
                                        data-pdf-preview="true"
                                        data-pdf-url=""
                                        data-pdf-title=""
                                        data-pdf-subtitle=""
                                        data-pdf-badge="">
                                    <i class="bi bi-eye"></i> Pratinjau Scan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-semibold px-4">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let currentActiveBpkb = null;

    const modalDetailEl = document.getElementById('modalDetailBpkb');
    const modalEditEl = document.getElementById('modalEditBpkb');
    const detailModal = modalDetailEl ? bootstrap.Modal.getOrCreateInstance(modalDetailEl) : null;
    const editModal = modalEditEl ? bootstrap.Modal.getOrCreateInstance(modalEditEl) : null;

    function populateDetailModal(data) {
        currentActiveBpkb = data;
        document.getElementById('detailPlateTitle').textContent = data.plate_number || '-';
        document.getElementById('detailPlateNumber').textContent = data.plate_number || '-';
        document.getElementById('detailYear').textContent = data.year || '-';
        document.getElementById('detailVehicleType').textContent = data.vehicle_type || '-';
        document.getElementById('detailNoBpkb').textContent = data.no_bpkb || '-';
        document.getElementById('detailNibar').textContent = data.nibar || '-';
        document.getElementById('detailNoRangka').textContent = data.no_rangka || '-';
        document.getElementById('detailNoMesin').textContent = data.no_mesin || '-';
        document.getElementById('detailMerekTipe').textContent = `${data.merek || '-'} ${data.tipe || ''}`.trim();
        document.getElementById('detailSilinderWarna').textContent = `${data.isi_silinder || '-'} · ${data.warna || '-'}`;
        document.getElementById('detailPengguna').textContent = data.pengguna || '-';
        document.getElementById('detailOpdNama').textContent = data.opd_nama || '-';
        document.getElementById('detailBoxCode').textContent = data.box_code || '-';
        document.getElementById('detailBoxLocation').textContent = data.box_location || '-';
        document.getElementById('detailInputUser').textContent = data.input_user || '-';
        document.getElementById('detailCreatedAt').textContent = data.created_at || '-';
        document.getElementById('detailUpdatedAt').textContent = data.updated_at || '-';

        const vehicleBadge = document.getElementById('detailVehicleBadge');
        if (vehicleBadge) vehicleBadge.textContent = data.vehicle_type || 'Kendaraan';

        const statusBadge = document.getElementById('detailStatusBadge');
        if (statusBadge) {
            statusBadge.textContent = data.status || 'Tersedia';
            if (data.status === 'Tersedia') {
                statusBadge.className = 'badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1 rounded-pill small';
            } else {
                statusBadge.className = 'badge bg-warning bg-opacity-10 text-warning text-dark border border-warning border-opacity-25 px-2.5 py-1 rounded-pill small';
            }
        }

        const pdfAvail = document.getElementById('detailPdfAvailable');
        const pdfMiss = document.getElementById('detailPdfMissing');
        const previewBtn = document.getElementById('detailPdfPreviewBtn');
        const downloadBtn = document.getElementById('detailPdfDownloadBtn');

        if (data.pdf_url) {
            pdfAvail.classList.remove('d-none');
            pdfMiss.classList.add('d-none');
            if (previewBtn) {
                previewBtn.setAttribute('data-pdf-url', data.pdf_url);
                previewBtn.setAttribute('data-pdf-title', `Scan BPKB ${data.plate_number}`);
                previewBtn.setAttribute('data-pdf-subtitle', `No. BPKB: ${data.no_bpkb || '-'} | Box: ${data.box_code || '-'}`);
                previewBtn.setAttribute('data-pdf-badge', data.vehicle_type || '');
            }
            if (downloadBtn) {
                downloadBtn.setAttribute('href', data.pdf_url);
            }
        } else {
            pdfAvail.classList.add('d-none');
            pdfMiss.classList.remove('d-none');
        }
    }

    function populateEditModal(data) {
        currentActiveBpkb = data;
        const form = document.getElementById('editBpkbForm');
        if (form) form.action = data.update_url;

        document.getElementById('editPlateTitle').textContent = data.plate_number || '';
        document.getElementById('edit_plate_number').value = data.plate_number || '';
        document.getElementById('edit_year').value = data.year || '';
        document.getElementById('edit_vehicle_type').value = data.vehicle_type || 'R4';
        document.getElementById('edit_no_bpkb').value = data.no_bpkb || '';
        document.getElementById('edit_nibar').value = data.nibar || '';
        document.getElementById('edit_no_rangka').value = data.no_rangka || '';
        document.getElementById('edit_no_mesin').value = data.no_mesin || '';
        document.getElementById('edit_merek').value = data.merek || '';
        document.getElementById('edit_tipe').value = data.tipe || '';
        document.getElementById('edit_isi_silinder').value = data.isi_silinder || '';
        document.getElementById('edit_warna').value = data.warna || '';
        document.getElementById('edit_pengguna').value = data.pengguna || '';
        
        // Reset file input
        const fileInput = document.getElementById('edit_pdf');
        if (fileInput) fileInput.value = '';

        // Sipat OPD (sync with TomSelect if active)
        const opdSelect = document.getElementById('edit_sipat_opd_id');
        if (opdSelect) {
            const ts = window.tsInstances ? window.tsInstances.get(opdSelect) : null;
            if (ts) {
                ts.setValue(data.sipat_opd_id || '', true);
            } else {
                opdSelect.value = data.sipat_opd_id || '';
            }
        }

        // PDF preview notice
        const pdfContainer = document.getElementById('editPdfCurrentContainer');
        const pdfBtn = document.getElementById('editPdfPreviewBtn');
        if (data.pdf_url) {
            pdfContainer.classList.remove('d-none');
            pdfContainer.classList.add('d-flex');
            if (pdfBtn) {
                pdfBtn.setAttribute('data-pdf-url', data.pdf_url);
                pdfBtn.setAttribute('data-pdf-title', `Scan BPKB ${data.plate_number}`);
                pdfBtn.setAttribute('data-pdf-subtitle', `No. BPKB: ${data.no_bpkb || '-'}`);
                pdfBtn.setAttribute('data-pdf-badge', data.vehicle_type || '');
            }
        } else {
            pdfContainer.classList.remove('d-flex');
            pdfContainer.classList.add('d-none');
        }
    }

    // Event delegation on table clicks
    document.addEventListener('click', function (e) {
        const viewBtn = e.target.closest('.btn-view-bpkb');
        if (viewBtn) {
            const tr = viewBtn.closest('tr');
            if (tr && tr.dataset.bpkb) {
                try {
                    const data = JSON.parse(tr.dataset.bpkb);
                    populateDetailModal(data);
                    if (detailModal) detailModal.show();
                } catch (err) {
                    console.error('Error parsing bpkb data:', err);
                }
            }
            return;
        }

        const editBtn = e.target.closest('.btn-edit-bpkb');
        if (editBtn) {
            const tr = editBtn.closest('tr');
            if (tr && tr.dataset.bpkb) {
                try {
                    const data = JSON.parse(tr.dataset.bpkb);
                    populateEditModal(data);
                    if (editModal) editModal.show();
                } catch (err) {
                    console.error('Error parsing bpkb data:', err);
                }
            }
            return;
        }
    });

    // Switch from Detail to Edit modal
    const switchBtn = document.getElementById('btnDetailToEdit');
    if (switchBtn) {
        switchBtn.addEventListener('click', function () {
            if (currentActiveBpkb) {
                if (detailModal) detailModal.hide();
                populateEditModal(currentActiveBpkb);
                setTimeout(() => {
                    if (editModal) editModal.show();
                }, 300);
            }
        });
    }

    // --- SEARCH UX ENHANCEMENTS ---
    const searchInput = document.getElementById('searchInput');
    const btnClearSearch = document.getElementById('btnClearSearch');
    const filterForm = document.getElementById('filterForm');
    const searchIconSpan = document.getElementById('searchIconSpan');

    if (searchInput && btnClearSearch && filterForm) {
        // Dynamic clear button toggle on typing
        searchInput.addEventListener('input', function () {
            if (this.value.trim().length > 0) {
                btnClearSearch.style.display = '';
            } else {
                btnClearSearch.style.display = 'none';
            }
        });

        // Clear button click: clear & submit to reset results instantly
        btnClearSearch.addEventListener('click', function () {
            searchInput.value = '';
            btnClearSearch.style.display = 'none';
            searchInput.focus();
            filterForm.submit();
        });

        // Escape key clears or blurs
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                if (this.value.length > 0) {
                    this.value = '';
                    btnClearSearch.style.display = 'none';
                    filterForm.submit();
                } else {
                    this.blur();
                }
            }
        });

        // Global keyboard shortcut: '/' or 'Ctrl+K' / 'Cmd+K' to focus search
        document.addEventListener('keydown', function (e) {
            const isInput = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName);
            const isModalOpen = document.body.classList.contains('modal-open');

            // Ctrl+K or Cmd+K
            if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
                if (!isModalOpen) {
                    e.preventDefault();
                    searchInput.focus();
                    searchInput.select();
                }
                return;
            }

            // '/' shortcut when not typing in any other field and modal not open
            if (e.key === '/' && !isInput && !isModalOpen) {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        });

        // Show subtle loading spinner on submit
        filterForm.addEventListener('submit', function () {
            if (searchIconSpan) {
                searchIconSpan.innerHTML = '<span class="spinner-border spinner-border-sm text-primary" role="status" style="width: 14px; height: 14px;"></span>';
            }
        });
    }

    // Search Keyword Highlighting in Table Results
    const currentQuery = @json(trim((string) request('q')));
    if (currentQuery && currentQuery.length >= 2) {
        try {
            const escaped = currentQuery.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
            const regex = new RegExp(`(${escaped})`, 'gi');
            const cells = document.querySelectorAll('table tbody tr td:not(:first-child):not(:last-child)');

            cells.forEach(cell => {
                const walker = document.createTreeWalker(cell, NodeFilter.SHOW_TEXT, null, false);
                const textNodes = [];
                let node;
                while ((node = walker.nextNode())) {
                    if (node.nodeValue && node.nodeValue.trim().length > 0) {
                        textNodes.push(node);
                    }
                }

                textNodes.forEach(textNode => {
                    if (regex.test(textNode.nodeValue)) {
                        const wrapper = document.createElement('span');
                        wrapper.innerHTML = textNode.nodeValue.replace(regex, '<mark class="bg-warning-subtle text-dark p-0 px-1 rounded fw-semibold">$1</mark>');
                        textNode.parentNode.replaceChild(wrapper, textNode);
                    }
                });
            });
        } catch (err) {
            console.warn('Highlight search warning:', err);
        }
    }
});
</script>
@endpush
