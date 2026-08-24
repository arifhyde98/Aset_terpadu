@extends('layouts.app')

@section('title', 'Data Kendaraan')

@section('content')
<div class="container-fluid px-0">
    
    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div class="mb-3 mb-lg-0">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Data Kendaraan</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0">Manajemen Data Kendaraan</h4>
        </div>
        <div class="action-toolbar d-flex flex-wrap gap-2">
            <form action="{{ route('vehicles.truncate') }}" method="POST" class="delete-confirm">
                @csrf
                <input type="hidden" name="target_table" value="{{ request('tab', 'real') }}">
                <button type="submit" class="btn btn-outline-danger shadow-sm fw-medium d-flex align-items-center gap-2">
                    <i class="bi bi-trash3"></i> <span class="d-none d-sm-inline">Kosongkan {{ request('tab') === 'ebmd' ? 'e-BMD' : 'Data Real' }}</span>
                </button>
            </form>
            <button type="button" class="btn btn-action btn-action-success shadow-sm fw-semibold d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#importModal">
                <span class="btn-action-icon"><i class="bi bi-file-earmark-arrow-up"></i></span>
                <span class="d-none d-sm-inline">Import</span>
            </button>
                 <button type="button" class="btn btn-outline-warning shadow-sm fw-semibold d-flex align-items-center gap-2" id="btnCheckDuplicates">
                <i class="bi bi-magic text-warning"></i> <span class="d-none d-sm-inline">Cek Duplikasi</span>
            </button>
            @if(auth()->user()->role === \App\Enums\UserRole::SUPERADMIN)
                <form id="sanitizeIdentifiersForm" action="{{ route('vehicles.sanitize-identifiers') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary shadow-sm fw-medium d-flex align-items-center gap-2" id="btnSanitizeIdentifiers" title="Pembersihan Karakter Nomor Rangka & Mesin">
                        <i class="bi bi-shield-check"></i> <span class="d-none d-sm-inline">Generate</span>
                    </button>
                </form>
                <form id="sanitizeSwappedIdentifiersForm" action="{{ route('vehicles.sanitize-swapped-identifiers') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-info shadow-sm fw-medium d-flex align-items-center gap-2" id="btnSanitizeSwappedIdentifiers" title="Tukar Posisi Mesin & Rangka (Jika Tertukar)">
                        <i class="bi bi-arrow-left-right"></i> <span class="d-none d-sm-inline">Tukar Posisi</span>
                    </button>
                </form>
            @endif
            <button type="button" class="btn btn-primary shadow-sm fw-medium d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                <i class="bi bi-plus-lg"></i> Tambah Kendaraan
            </button>
        </div>
    </div>



    <!-- OPTIONAL SIDEBAR SUMMARY (Displayed as top cards on smaller screens) -->
    <div class="row g-3 mb-4">
        @if(request('tab') === 'ebmd')
            @forelse($ebmdStats as $jenis => $total)
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <x-stat-card title="{{ $jenis }}" :value="$total" icon="car-front" gradient="success" subtitle="Total Kendaraan" />
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-light border border-secondary border-opacity-25 shadow-sm text-secondary mb-0">
                        <i class="bi bi-info-circle me-2"></i> Belum ada data e-BMD untuk menampilkan statistik jenis kendaraan.
                    </div>
                </div>
            @endforelse
        @else
            <div class="col-sm-6 col-lg-3">
                <x-stat-card title="Kondisi Baik" :value="$stats['baik']" icon="check-circle" gradient="success" subtitle="Aset Layak Pakai" />
            </div>
            <div class="col-sm-6 col-lg-3">
                <x-stat-card title="Rusak Ringan" :value="$stats['rusak_ringan']" icon="exclamation-triangle" gradient="warning" subtitle="Butuh Maintenance" />
            </div>
            <div class="col-sm-6 col-lg-3">
                <x-stat-card title="Rusak Berat" :value="$stats['rusak_berat']" icon="x-octagon" gradient="danger" subtitle="Tidak Operasional" />
            </div>
            <div class="col-sm-6 col-lg-3">
                <x-stat-card title="Hilang / TD" :value="$stats['hilang']" icon="question-circle" gradient="secondary" subtitle="Dalam Penelusuran" />
            </div>
        @endif
    </div>

    <!-- TABS NAVIGATION -->
    @php
        $activeTab = request('tab', 'real');
    @endphp
    <ul class="nav nav-tabs nav-fill mb-4 border-bottom" role="tablist">
        <li class="nav-item" role="presentation">
            <a href="?tab=real" class="nav-link fw-bold py-3 d-flex align-items-center justify-content-center gap-2 {{ $activeTab === 'real' ? 'active text-navy' : 'text-secondary' }}" role="tab">
                <i class="bi bi-car-front-fill fs-5 {{ $activeTab === 'real' ? 'text-primary' : '' }}"></i>
                <span>Data Real (Fisik)</span>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a href="?tab=ebmd" class="nav-link fw-bold py-3 d-flex align-items-center justify-content-center gap-2 {{ $activeTab === 'ebmd' ? 'active text-navy' : 'text-secondary' }}" role="tab">
                <i class="bi bi-file-earmark-spreadsheet-fill fs-5 {{ $activeTab === 'ebmd' ? 'text-success' : '' }}"></i>
                <span>Data e-BMD</span>
            </a>
        </li>
    </ul>

    <!-- MAIN TABLE SECTION -->
    <x-table-card 
        :empty="$vehicles->isEmpty()" 
        :collection="$vehicles"
        emptyText="Belum ada data kendaraan" 
        emptyIcon="bi-car-front">
        
        <x-slot:filters>
            <form action="{{ route('vehicles.index') }}" method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="tab" value="{{ request('tab', 'real') }}">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control border-start-0 bg-white shadow-none" placeholder="Cari nomor polisi, nomor register, atau nama pengguna...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm shadow-none" name="status">
                        <option value="">Semua Status</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm shadow-none" name="kondisi">
                        <option value="">Semua Kondisi</option>
                        @foreach($conditions as $key => $label)
                            <option value="{{ $key }}" {{ request('kondisi') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-medium">Filter</button>
                    <a href="{{ route('vehicles.index') }}" class="btn btn-light border btn-sm bg-white" title="Reset Filter"><i class="bi bi-arrow-clockwise"></i></a>
                </div>
            </form>
        </x-slot:filters>

        @php
            $currentSortBy = request('sort_by');
            $currentSortOrder = request('sort_order', 'asc');
            $nextSortOrder = $currentSortOrder === 'asc' ? 'desc' : 'asc';
        @endphp

        <x-slot:thead>
            <tr>
                <th class="py-3 px-4 border-bottom-0 fw-semibold text-center" style="width: 50px;">No.</th>
                <th class="py-3 border-bottom-0 fw-semibold">
                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'no_polisi', 'sort_order' => $currentSortBy === 'no_polisi' ? $nextSortOrder : 'asc', 'tab' => request('tab', 'real')]) }}" class="text-navy text-decoration-none d-inline-flex align-items-center gap-1">
                        <span>No. Polisi</span>
                        @if($currentSortBy === 'no_polisi')
                            <i class="bi bi-sort-alpha-{{ $currentSortOrder === 'asc' ? 'down' : 'up' }} text-primary"></i>
                        @else
                            <i class="bi bi-arrow-down-up text-secondary opacity-50 small" style="font-size: 0.75rem;"></i>
                        @endif
                    </a>
                </th>
                <th class="py-3 border-bottom-0 fw-semibold">
                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'jenis', 'sort_order' => $currentSortBy === 'jenis' ? $nextSortOrder : 'asc', 'tab' => request('tab', 'real')]) }}" class="text-navy text-decoration-none d-inline-flex align-items-center gap-1">
                        <span>Jenis Kendaraan</span>
                        @if($currentSortBy === 'jenis')
                            <i class="bi bi-sort-alpha-{{ $currentSortOrder === 'asc' ? 'down' : 'up' }} text-primary"></i>
                        @else
                            <i class="bi bi-arrow-down-up text-secondary opacity-50 small" style="font-size: 0.75rem;"></i>
                        @endif
                    </a>
                </th>
                <th class="py-3 border-bottom-0 fw-semibold d-none d-md-table-cell">
                    @if(request('tab') === 'ebmd')
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'merk', 'sort_order' => $currentSortBy === 'merk' ? $nextSortOrder : 'asc', 'tab' => request('tab', 'real')]) }}" class="text-navy text-decoration-none d-inline-flex align-items-center gap-1">
                            <span>Merk / Tgl Perolehan</span>
                            @if($currentSortBy === 'merk')
                                <i class="bi bi-sort-alpha-{{ $currentSortOrder === 'asc' ? 'down' : 'up' }} text-primary"></i>
                            @else
                                <i class="bi bi-arrow-down-up text-secondary opacity-50 small" style="font-size: 0.75rem;"></i>
                            @endif
                        </a>
                    @else
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'merk', 'sort_order' => $currentSortBy === 'merk' ? $nextSortOrder : 'asc', 'tab' => request('tab', 'real')]) }}" class="text-navy text-decoration-none d-inline-flex align-items-center gap-1">
                            <span>Merk / Tahun</span>
                            @if($currentSortBy === 'merk')
                                <i class="bi bi-sort-alpha-{{ $currentSortOrder === 'asc' ? 'down' : 'up' }} text-primary"></i>
                            @else
                                <i class="bi bi-arrow-down-up text-secondary opacity-50 small" style="font-size: 0.75rem;"></i>
                            @endif
                        </a>
                    @endif
                </th>
                <th class="py-3 border-bottom-0 fw-semibold">
                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'pemegang', 'sort_order' => $currentSortBy === 'pemegang' ? $nextSortOrder : 'asc']) }}" class="text-navy text-decoration-none d-inline-flex align-items-center gap-1">
                        <span>Pengguna / OPD</span>
                        @if($currentSortBy === 'pemegang')
                            <i class="bi bi-sort-alpha-{{ $currentSortOrder === 'asc' ? 'down' : 'up' }} text-primary"></i>
                        @else
                            <i class="bi bi-arrow-down-up text-secondary opacity-50 small" style="font-size: 0.75rem;"></i>
                        @endif
                    </a>
                </th>
                <th class="py-3 border-bottom-0 fw-semibold text-center">
                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'kondisi', 'sort_order' => $currentSortBy === 'kondisi' ? $nextSortOrder : 'asc']) }}" class="text-navy text-decoration-none d-inline-flex align-items-center gap-1">
                        <span>Kondisi Fisik</span>
                        @if($currentSortBy === 'kondisi')
                            <i class="bi bi-sort-alpha-{{ $currentSortOrder === 'asc' ? 'down' : 'up' }} text-primary"></i>
                        @else
                            <i class="bi bi-arrow-down-up text-secondary opacity-50 small" style="font-size: 0.75rem;"></i>
                        @endif
                    </a>
                </th>
                <th class="py-3 border-bottom-0 fw-semibold text-center">
                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'status', 'sort_order' => $currentSortBy === 'status' ? $nextSortOrder : 'asc']) }}" class="text-navy text-decoration-none d-inline-flex align-items-center gap-1">
                        <span>Status</span>
                        @if($currentSortBy === 'status')
                            <i class="bi bi-sort-alpha-{{ $currentSortOrder === 'asc' ? 'down' : 'up' }} text-primary"></i>
                        @else
                            <i class="bi bi-arrow-down-up text-secondary opacity-50 small" style="font-size: 0.75rem;"></i>
                        @endif
                    </a>
                </th>
                <th class="py-3 px-4 border-bottom-0 fw-semibold text-center" style="width: 100px;">Aksi</th>
            </tr>
        </x-slot:thead>

        @foreach($vehicles as $vehicle)
            <tr>
                <td data-label="No." class="px-4 py-3 text-center fw-medium text-secondary">
                    {{ ($vehicles->currentPage() - 1) * $vehicles->perPage() + $loop->iteration }}
                </td>
                <td data-label="No. Polisi" class="py-3">
                    <span class="badge bg-light text-dark border border-secondary border-opacity-25 px-3 py-2 fs-6 rounded-3 fw-bold plate-number">{{ $vehicle->no_polisi }}</span>
                    @if($vehicle->nomor_register)
                        <div class="mt-1 small text-secondary fw-semibold" style="font-size: 0.75rem;">
                            <i class="bi bi-hash text-primary"></i> {{ $vehicle->nomor_register }}
                        </div>
                    @endif
                </td>
                <td data-label="Jenis Kendaraan" class="py-3">
                    <div class="fw-bold text-navy">{{ $vehicle->vehicleType->name ?? ($vehicle->jenis ?? '-') }}</div>
                    <div class="small text-secondary">{{ $vehicle->tipe ?: '-' }}</div>
                </td>
                <td data-label="Merk/Tahun" class="py-3 d-none d-md-table-cell">
                    <div class="text-dark fw-medium">{{ $vehicle->merk ?: '-' }}</div>
                    <div class="small text-secondary">
                        @if(request('tab') === 'ebmd')
                            {{ $vehicle->tgl_perolehan ? \Carbon\Carbon::parse($vehicle->tgl_perolehan)->translatedFormat('d M Y') : '-' }}
                        @else
                            {{ $vehicle->tahun_pembuatan ?? ($vehicle->tgl_perolehan ? \Carbon\Carbon::parse($vehicle->tgl_perolehan)->year : '-') }}
                        @endif
                    </div>
                </td>
                <td data-label="Pengguna" class="py-3">
                    <div class="fw-medium text-dark"><i class="bi bi-person-fill text-secondary me-1"></i> {{ $vehicle->pemegang }}</div>
                    <div class="small text-secondary">{{ Str::limit($vehicle->opdRelation?->nama ?? $vehicle->opd, 40) }}</div>
                </td>
                <td data-label="Kondisi Fisik" class="text-center">
                    <x-condition-badge :kondisi="$vehicle->kondisi" />
                </td>
                <td data-label="Status" class="text-center">
                    <x-status-badge :status="$vehicle->status" />
                </td>
                <td data-label="Aksi" class="px-4 py-3 text-center">
                    <div class="d-flex justify-content-center gap-2">
                        @if(request('tab') === 'ebmd')
                            @if(!$vehicle->is_synced)
                                <form action="{{ route('vehicles.sync-to-real', $vehicle->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-light border shadow-none text-success fw-bold" data-bs-toggle="tooltip" title="Jadikan Data Real">
                                        <i class="bi bi-arrow-left-right"></i> Sinkron
                                    </button>
                                </form>
                            @else
                                <span class="badge bg-success text-white border border-success border-opacity-25 px-2 py-1 fs-7 rounded-2 fw-medium">Sudah Real</span>
                            @endif
                        @endif

                        <button type="button" class="btn btn-sm btn-light border shadow-none text-navy" 
                                data-bs-toggle="modal" data-bs-target="#detailVehicleModal" 
                                data-id="{{ $vehicle->id }}" title="Detail Kendaraan">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-light border shadow-none text-primary" 
                                data-bs-toggle="modal" data-bs-target="#editVehicleModal" 
                                data-id="{{ $vehicle->id }}" title="Edit Data">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <form action="{{ route('vehicles.destroy', $vehicle->id) }}" method="POST" class="d-inline delete-confirm">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="target_table" value="{{ request('tab', 'real') }}">
                            <button type="submit" class="btn btn-sm btn-light border shadow-none text-danger" data-bs-toggle="tooltip" title="Hapus Data">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach

        <x-slot:pagination>
            {{ $vehicles->links() }}
        </x-slot:pagination>
    </x-table-card>

</div>

@push('modals')
<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" id="importModalDialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom px-4 py-3">
                <h5 class="modal-title fw-bold text-navy d-flex align-items-center gap-2" id="importModalLabel">
                    <i class="bi bi-file-earmark-arrow-up text-success"></i> Import Data Kendaraan
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close" id="closeImportModalBtn"></button>
            </div>
            
            <!-- Menggunakan div container untuk me-render body & footer secara dinamis via AJAX -->
            <div id="importModalContainer">
                <form action="{{ route('vehicles.import-preview') }}" method="POST" enctype="multipart/form-data" id="importPreviewForm">
                    @csrf
                    <input type="hidden" name="target_table" value="{{ request('tab', 'real') }}">
                    <div class="modal-body p-4">
                        <div class="import-helper d-flex border rounded-3 p-3 mb-4">
                            <div class="me-3 text-primary">
                                <i class="bi bi-info-circle fs-4"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Persiapkan Data Anda</h6>
                                <p class="small text-secondary mb-2">Pastikan data Excel (.xlsx) mengikuti format standar sistem. Sistem mendukung pengimporan banyak sheet (multi-sheet) secara otomatis selama seluruh sheet memiliki struktur kolom yang sama.</p>
                                <a href="{{ route('vehicles.template') }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" data-template-button>
                                    <i class="bi bi-download"></i> Download Template
                                </a>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark small">Pilih File Excel</label>
                            <input type="file" name="file" class="form-control shadow-none" accept=".xlsx, .xls" required id="importFileField">
                            <div class="small text-secondary mt-2" id="importFileNameText">Belum ada file dipilih.</div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light px-4 py-3 rounded-bottom-4">
                        <button type="button" class="btn btn-light border fw-medium" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary fw-medium px-4" id="importPreviewSubmit">
                            <i class="bi bi-cpu me-1"></i> Analisis Kolom (AI)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endpush

@push('modals')
    <!-- ADD VEHICLE MODAL -->
    <x-modal id="addVehicleModal" title="Tambah Kendaraan Dinas Baru" size="xl" submitLabel="Simpan Data" form="addVehicleForm">
        <form id="addVehicleForm" action="{{ route('vehicles.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="target_table" value="{{ request('tab', 'real') }}">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">No. Polisi <span class="text-danger">*</span></label>
                    <input type="text" name="no_polisi" class="form-control" placeholder="DN 1234 XX" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">No. Register</label>
                    <input type="text" name="nomor_register" class="form-control" placeholder="REG-1234567">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">Jenis <span class="text-danger">*</span></label>
                    <select name="vehicle_type_id" class="form-select" required onchange="document.getElementById('add_jenis_text').value = this.options[this.selectedIndex].text">
                        <option value="">-- Pilih Jenis --</option>
                        @foreach($vehicleTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="jenis" id="add_jenis_text" value="">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">Merk <span class="text-danger">*</span></label>
                    <input type="text" name="merk" class="form-control" placeholder="Toyota" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">Tipe / Model <span class="text-danger">*</span></label>
                    <input type="text" name="tipe" class="form-control" placeholder="Innova" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-uppercase">OPD / Instansi <span class="text-danger">*</span></label>
                    @if(auth()->user()->role === \App\Enums\UserRole::OPD)
                        <div class="form-control bg-light text-secondary fw-medium">
                            {{ auth()->user()->opd?->nama ?? 'Instansi Tidak Ditemukan' }}
                        </div>
                        <input type="hidden" name="opd_id" value="{{ auth()->user()->opd_id }}">
                        <input type="hidden" name="opd" id="add_opd_text" value="{{ auth()->user()->opd?->nama }}">
                    @else
                        <select name="opd_id" class="form-select" required onchange="document.getElementById('add_opd_text').value = this.options[this.selectedIndex].text">
                            <option value="">-- Pilih OPD --</option>
                            @foreach($opds as $opd)
                                <option value="{{ $opd->id }}">{{ $opd->nama }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="opd" id="add_opd_text" value="">
                    @endif
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-uppercase">Pemegang <span class="text-danger">*</span></label>
                    <input type="text" name="pemegang" class="form-control" placeholder="Nama Pemegang" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-uppercase">Kondisi Fisik <span class="text-danger">*</span></label>
                    <select name="kondisi" class="form-select shadow-none" required>
                        @foreach($conditions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-uppercase">Status Operasional <span class="text-danger">*</span></label>
                    <select name="status" class="form-select shadow-none" required>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @if(request('tab') !== 'ebmd')
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">Tahun</label>
                    <input type="number" name="tahun_pembuatan" class="form-control" placeholder="2024">
                </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">Warna</label>
                    <input type="text" name="warna" class="form-control" placeholder="Hitam">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">Dok. STNK <span class="text-danger">*</span></label>
                    <select name="stnk_ada" class="form-select" required>
                        <option value="Ada">Ada</option>
                        <option value="Tidak">Tidak Ada</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">Dok. BPKB <span class="text-danger">*</span></label>
                    <select name="bpkb_ada" class="form-select" required>
                        <option value="Ada">Ada</option>
                        <option value="Tidak">Tidak Ada</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">Tgl STNK</label>
                    <input type="date" name="tgl_stnk" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">Tgl Perolehan @if(request('tab') === 'ebmd') <span class="text-danger">*</span> @endif</label>
                    <input type="date" name="tgl_perolehan" class="form-control" @if(request('tab') === 'ebmd') required @endif>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-uppercase">Nilai Perolehan (Rp)</label>
                    <input type="number" name="nilai_perolehan" class="form-control" placeholder="250000000">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-uppercase">Nomor Mesin</label>
                    <input type="text" name="no_mesin" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-uppercase">Nomor Rangka</label>
                    <input type="text" name="no_rangka" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-uppercase">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-uppercase">Foto Kendaraan (Max 4)</label>
                    <input type="file" name="foto_kendaraan[]" class="form-control" multiple accept="image/*">
                    <small class="text-secondary italic">Hanya file gambar (max 2MB per file).</small>
                </div>
            </div>
        </form>
    </x-modal>

    <!-- DETAIL VEHICLE MODAL -->
    <x-modal id="detailVehicleModal" title="Detail Informasi Kendaraan" size="lg">
        <div id="detailContent">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
        </div>
    </x-modal>
    <!-- EDIT VEHICLE MODAL -->
    <x-modal id="editVehicleModal" title="Edit Informasi Kendaraan" size="xl" submitLabel="Simpan Perubahan" form="editVehicleForm">
        <form id="editVehicleForm" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="target_table" value="{{ request('tab', 'real') }}">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">No. Polisi <span class="text-danger">*</span></label>
                    <input type="text" name="no_polisi" id="edit_no_polisi" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">No. Register</label>
                    <input type="text" name="nomor_register" id="edit_nomor_register" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">Jenis <span class="text-danger">*</span></label>
                    <select name="vehicle_type_id" id="edit_vehicle_type_id" class="form-select" required onchange="document.getElementById('edit_jenis_text').value = this.options[this.selectedIndex].text">
                        <option value="">-- Pilih Jenis --</option>
                        @foreach($vehicleTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="jenis" id="edit_jenis_text" value="">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">Merk <span class="text-danger">*</span></label>
                    <input type="text" name="merk" id="edit_merk" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">Tipe / Model <span class="text-danger">*</span></label>
                    <input type="text" name="tipe" id="edit_tipe" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-uppercase">OPD / Instansi <span class="text-danger">*</span></label>
                    @if(auth()->user()->role === \App\Enums\UserRole::OPD)
                        <div class="form-control bg-light text-secondary fw-medium" id="edit_opd_display">
                            {{ auth()->user()->opd?->nama ?? 'Instansi Tidak Ditemukan' }}
                        </div>
                        <input type="hidden" name="opd_id" id="edit_opd_id_locked" value="{{ auth()->user()->opd_id }}">
                        <input type="hidden" name="opd" id="edit_opd_text" value="{{ auth()->user()->opd?->nama }}">
                    @else
                        <select name="opd_id" id="edit_opd_id" class="form-select" required onchange="document.getElementById('edit_opd_text').value = this.options[this.selectedIndex].text">
                            <option value="">-- Pilih OPD --</option>
                            @foreach($opds as $opd)
                                <option value="{{ $opd->id }}">{{ $opd->nama }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="opd" id="edit_opd_text" value="">
                    @endif
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-uppercase">Pemegang <span class="text-danger">*</span></label>
                    <input type="text" name="pemegang" id="edit_pemegang" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-uppercase">Kondisi Fisik <span class="text-danger">*</span></label>
                    <select name="kondisi" id="edit_kondisi" class="form-select shadow-none" required>
                        @foreach($conditions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-uppercase">Status Operasional <span class="text-danger">*</span></label>
                    <select name="status" id="edit_status" class="form-select shadow-none" required>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @if(request('tab') !== 'ebmd')
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">Tahun</label>
                    <input type="number" name="tahun_pembuatan" id="edit_tahun_pembuatan" class="form-control">
                </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">Warna</label>
                    <input type="text" name="warna" id="edit_warna" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">Dok. STNK <span class="text-danger">*</span></label>
                    <select name="stnk_ada" id="edit_stnk_ada" class="form-select" required>
                        <option value="Ada">Ada</option>
                        <option value="Tidak">Tidak Ada</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">Dok. BPKB <span class="text-danger">*</span></label>
                    <select name="bpkb_ada" id="edit_bpkb_ada" class="form-select" required>
                        <option value="Ada">Ada</option>
                        <option value="Tidak">Tidak Ada</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">Tgl STNK</label>
                    <input type="date" name="tgl_stnk" id="edit_tgl_stnk" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">Tgl Perolehan @if(request('tab') === 'ebmd') <span class="text-danger">*</span> @endif</label>
                    <input type="date" name="tgl_perolehan" id="edit_tgl_perolehan" class="form-control" @if(request('tab') === 'ebmd') required @endif>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-uppercase">Nilai Perolehan (Rp)</label>
                    <input type="number" name="nilai_perolehan" id="edit_nilai_perolehan" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-uppercase">Nomor Mesin</label>
                    <input type="text" name="no_mesin" id="edit_no_mesin" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-uppercase">Nomor Rangka</label>
                    <input type="text" name="no_rangka" id="edit_no_rangka" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-uppercase">Keterangan</label>
                    <textarea name="keterangan" id="edit_keterangan" class="form-control" rows="2"></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-uppercase">Ganti Foto Kendaraan (Max 4)</label>
                    <input type="file" name="foto_kendaraan[]" class="form-control" multiple accept="image/*">
                    <small class="text-secondary italic text-danger">Mencuplik foto baru akan menghapus semua foto lama.</small>
                </div>
            </div>
        </form>
    </x-modal>

    <!-- DIAGNOSIS DUPLICATES MODAL (Magic Button) -->
    <x-modal id="diagnosisDuplicatesModal" title="Diagnosis & Bersihkan Data Ganda" size="xl">
        <div class="modal-body p-4 bg-light">
            
            <!-- Tab Navigation -->
            <ul class="nav nav-tabs nav-fill mb-4 border-bottom" id="duplicateTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold text-navy py-3 d-flex align-items-center justify-content-center gap-2" id="vehicles-tab" data-bs-toggle="tab" data-bs-target="#vehicles-pane" type="button" role="tab" aria-controls="vehicles-pane" aria-selected="true">
                        <i class="bi bi-car-front-fill fs-5 text-warning"></i>
                        <span>Kendaraan Ganda / Identik (<span id="vehicle-dup-count">0</span>)</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-navy py-3 d-flex align-items-center justify-content-center gap-2" id="opds-tab" data-bs-toggle="tab" data-bs-target="#opds-pane" type="button" role="tab" aria-controls="opds-pane" aria-selected="false">
                        <i class="bi bi-building-fill fs-5 text-info"></i>
                        <span>OPD / Dinas Ganda & Mirip (<span id="opd-dup-count">0</span>)</span>
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="duplicateTabsContent">
                
                <!-- PANE 1: VEHICLE DUPLICATES -->
                <div class="tab-pane fade show active" id="vehicles-pane" role="tabpanel" aria-labelledby="vehicles-tab" tabindex="0">
                    <div class="alert alert-warning border-0 bg-warning bg-opacity-10 text-navy d-flex align-items-center mb-4 rounded-3 shadow-none">
                        <div class="fs-4 me-3 text-warning"><i class="bi bi-info-circle-fill"></i></div>
                        <div>
                            <h6 class="alert-heading fw-bold mb-1" style="font-size: 0.9rem;">Instruksi Pembersihan Kendaraan</h6>
                            <p class="mb-0 small text-secondary">
                                Kendaraan terdeteksi ganda berdasarkan kemiripan Nomor Polisi (plat berakhiran index hasil impor) atau Nomor Mesin identik. Anda dapat:
                                <br>1. <strong>Gabungkan Data</strong>: Menyalin kelengkapan data kosong dari baris ganda ke baris asli, lalu menghapus baris ganda.
                                <br>2. <strong>Hapus Duplikat</strong>: Menghapus baris ganda yang salah secara langsung.
                            </p>
                        </div>
                    </div>
                    
                    <div class="table-responsive border rounded-3 bg-white shadow-sm" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-navy text-white text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px; position: sticky; top: 0; z-index: 2;">
                                <tr>
                                    <th class="px-3 py-3" style="width: 25%;">Data Ganda (Hasil Impor/Baru)</th>
                                    <th class="px-3 py-3" style="width: 25%;">Data Induk (Asli/Lama)</th>
                                    <th class="px-3 py-3" style="width: 30%;">Indikasi Duplikasi</th>
                                    <th class="px-3 py-3 text-center" style="width: 20%;">Aksi Resolusi</th>
                                </tr>
                            </thead>
                            <tbody id="vehicle-dup-list">
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-secondary">
                                        <div class="spinner-border text-warning mb-2" role="status"></div>
                                        <div class="small fw-medium">Sedang memindai duplikasi kendaraan...</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- PANE 2: OPD DUPLICATES -->
                <div class="tab-pane fade" id="opds-pane" role="tabpanel" aria-labelledby="opds-tab" tabindex="0">
                    <div class="alert alert-info border-0 bg-info bg-opacity-10 text-navy d-flex align-items-center mb-4 rounded-3 shadow-none">
                        <div class="fs-4 me-3 text-info"><i class="bi bi-info-circle-fill"></i></div>
                        <div>
                            <h6 class="alert-heading fw-bold mb-1" style="font-size: 0.9rem;">Instruksi Konsolidasi OPD / Instansi</h6>
                            <p class="mb-0 small text-secondary">
                                OPD terdeteksi mirip berdasarkan analisis teks (case-insensitive atau kemiripan nama instansi inti).
                                <br>Menekan tombol <strong>Gabungkan Instansi</strong> akan **memindahkan seluruh kendaraan** dari OPD duplikat (OPD B) ke OPD utama (OPD A), lalu menghapus OPD duplikat kosong tersebut secara bersih.
                            </p>
                        </div>
                    </div>
                    
                    <div class="table-responsive border rounded-3 bg-white shadow-sm" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-navy text-white text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px; position: sticky; top: 0; z-index: 2;">
                                <tr>
                                    <th class="px-3 py-3" style="width: 35%;">OPD Utama (Dipertahankan)</th>
                                    <th class="px-3 py-3" style="width: 35%;">OPD Duplikat (Akan Dihapus)</th>
                                    <th class="px-3 py-3" style="width: 15%;">Indikasi</th>
                                    <th class="px-3 py-3 text-center" style="width: 15%;">Aksi Konsolidasi</th>
                                </tr>
                            </thead>
                            <tbody id="opd-dup-list">
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-secondary">
                                        <div class="spinner-border text-info mb-2" role="status"></div>
                                        <div class="small fw-medium">Sedang memindai duplikasi OPD...</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
        <div class="modal-footer border-top bg-light px-4 py-3 rounded-bottom-4">
            <button type="button" class="btn btn-secondary fw-semibold px-4 shadow-sm" data-bs-dismiss="modal">Tutup Diagnosis</button>
        </div>
    </x-modal>
@endpush

@push('scripts')
{{-- Centralized Vehicle Data Map (Optimasi Payload Fase 2) --}}
<script id="vehicle-data-map" type="application/json">
    @json($vehicleDataMap)
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Global Helpers
        const escapeHtml = (str) => {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        };

        // Vehicle Data Map
        const vehicleMap = JSON.parse(document.getElementById('vehicle-data-map').textContent);
        // Tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Detail Modal Logic
        const detailModal = document.getElementById('detailVehicleModal');
        if (detailModal) {
            detailModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const vehicleId = button.getAttribute('data-id');
                const vehicle = vehicleMap[vehicleId];
                const detailContent = document.getElementById('detailContent');

                const formatRupiah = (num) => {
                    if (!num) return 'Rp 0';
                    return 'Rp ' + Number(num).toLocaleString('id-ID');
                };

                const renderPhotos = (photos) => {
                    if (!photos || photos.length === 0) {
                        return `
                            <div class="col-12 text-center py-4 bg-light rounded-3 border">
                                <i class="bi bi-image text-secondary opacity-50 fs-2 d-block mb-2"></i>
                                <span class="text-secondary small">Belum ada foto kendaraan</span>
                            </div>
                        `;
                    }
                    
                    let itemsHtml = '';
                    let indicatorsHtml = '';
                    
                    photos.forEach((path, index) => {
                        const url = `/storage/${path}`;
                        itemsHtml += `
                            <div class="carousel-item ${index === 0 ? 'active' : ''}">
                                <a href="${url}" target="_blank">
                                    <img src="${url}" class="d-block w-100 rounded-3 shadow-sm" style="height: 350px; object-fit: contain; background: #f1f5f9;" alt="Foto ${index + 1}">
                                </a>
                            </div>
                        `;
                        indicatorsHtml += `
                            <button type="button" data-bs-target="#modalCarousel" data-bs-slide-to="${index}" class="${index === 0 ? 'active' : ''}" aria-current="${index === 0 ? 'true' : 'false'}"></button>
                        `;
                    });

                    const controls = photos.length > 1 ? `
                        <button class="carousel-control-prev" type="button" data-bs-target="#modalCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#modalCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                        <div class="carousel-indicators" style="bottom: -40px;">
                            ${indicatorsHtml}
                        </div>
                    ` : '';

                    return `
                        <div id="modalCarousel" class="carousel slide pb-4" data-bs-ride="false">
                            <div class="carousel-inner rounded-3 border">
                                ${itemsHtml}
                            </div>
                            ${controls}
                        </div>
                    `;
                };
                
                detailContent.innerHTML = `
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border-start border-primary border-4">
                                <small class="text-secondary text-uppercase fw-bold d-block mb-1" style="font-size: 0.7rem;">Nomor Polisi</small>
                                <h4 class="fw-bold text-navy mb-0">${escapeHtml(vehicle.no_polisi)}</h4>
                                ${vehicle.nomor_register ? `<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mt-1 small"><i class="bi bi-hash"></i> ${escapeHtml(vehicle.nomor_register)}</span>` : ''}
                                <p class="text-secondary mb-0 small mt-1">${escapeHtml(vehicle.merk)} (${escapeHtml(vehicle.tahun_pembuatan)})</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 h-100 d-flex flex-column justify-content-center border-start border-info border-4">
                                <small class="text-secondary text-uppercase fw-bold d-block mb-1" style="font-size: 0.7rem;">OPD Pengelola</small>
                                <div class="fw-bold text-navy small">${escapeHtml(vehicle.opd)}</div>
                            </div>
                        </div>
                        <div class="col-12 mt-4">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="small text-secondary fw-bold text-uppercase" style="font-size: 0.65rem;">Nomor Register</label>
                                    <div class="fw-semibold text-dark">${escapeHtml(vehicle.nomor_register) || '-'}</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="small text-secondary fw-bold text-uppercase" style="font-size: 0.65rem;">Nomor Mesin</label>
                                    <div class="fw-semibold text-dark">${escapeHtml(vehicle.no_mesin) || '-'}</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="small text-secondary fw-bold text-uppercase" style="font-size: 0.65rem;">Nomor Rangka</label>
                                    <div class="fw-semibold text-dark">${escapeHtml(vehicle.no_rangka) || '-'}</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="small text-secondary fw-bold text-uppercase" style="font-size: 0.65rem;">Pemegang</label>
                                    <div class="fw-semibold text-dark">${escapeHtml(vehicle.pemegang) || '-'}</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="small text-secondary fw-bold text-uppercase" style="font-size: 0.65rem;">Status STNK</label>
                                    <div class="fw-semibold text-dark">${escapeHtml(vehicle.stnk_ada) || '-'}</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="small text-secondary fw-bold text-uppercase" style="font-size: 0.65rem;">Status BPKB</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-semibold text-dark">${escapeHtml(vehicle.bpkb_ada) || '-'}</span>
                                        ${vehicle.bpkb_id ? `
                                            <a href="/elabel/bpkb/${vehicle.bpkb_id}/view-pdf" target="_blank" class="btn btn-xs btn-outline-danger py-0.5 px-2 rounded-pill fw-semibold" style="font-size: 0.7rem;">
                                                <i class="bi bi-file-earmark-pdf me-0.5"></i> Lihat BPKB
                                            </a>
                                        ` : ''}
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="small text-secondary fw-bold text-uppercase" style="font-size: 0.65rem;">Warna</label>
                                    <div class="fw-semibold text-dark">${escapeHtml(vehicle.warna) || '-'}</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="small text-secondary fw-bold text-uppercase" style="font-size: 0.65rem;">Tahun Pembuatan</label>
                                    <div class="fw-semibold text-dark">${escapeHtml(vehicle.tahun_pembuatan) || '-'}</div>
                                </div>
                                <div class="col-md-12 mt-2">
                                    <div class="p-3 border rounded-3 bg-white d-flex align-items-center">
                                        <div class="bg-success bg-opacity-10 text-success p-2 rounded-3 me-3">
                                            <i class="bi bi-cash-stack fs-5"></i>
                                        </div>
                                        <div>
                                            <small class="text-secondary d-block mb-0" style="font-size: 0.7rem;">Nilai Perolehan / Aset</small>
                                            <h5 class="fw-bold text-navy mb-0">${formatRupiah(vehicle.nilai_perolehan)}</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-3">
                                    <label class="small text-secondary fw-bold text-uppercase mb-2" style="font-size: 0.65rem;">Galeri Foto Fisik</label>
                                    ${renderPhotos(vehicle.foto_kendaraan)}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
        }

        // Edit Modal Logic
        const editModal = document.getElementById('editVehicleModal');
        if (editModal) {
            editModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const vehicleId = button.getAttribute('data-id');
                const vehicle = vehicleMap[vehicleId];
                const form = document.getElementById('editVehicleForm');
                
                // Update Form Action
                const routeTemplate = "{{ route('vehicles.update', ':id') }}";
                form.action = routeTemplate.replace(':id', vehicle.id);

                // Populate Fields
                document.getElementById('edit_no_polisi').value = vehicle.no_polisi || '';
                document.getElementById('edit_nomor_register').value = vehicle.nomor_register || '';
                document.getElementById('edit_vehicle_type_id').value = vehicle.vehicle_type_id || '';
                document.getElementById('edit_jenis_text').value = vehicle.jenis || '';
                document.getElementById('edit_merk').value = vehicle.merk || '';
                document.getElementById('edit_tipe').value = vehicle.tipe || '';
                const editOpdSelect = document.getElementById('edit_opd_id');
                const editOpdLocked = document.getElementById('edit_opd_id_locked');
                const editOpdText = document.getElementById('edit_opd_text');

                if (editOpdSelect) {
                    editOpdSelect.value = vehicle.opd_id || '';
                }
                if (editOpdLocked) {
                    editOpdLocked.value = @js(auth()->user()->opd_id);
                }
                if (editOpdText) {
                    editOpdText.value = editOpdLocked
                        ? @js(auth()->user()->opd?->nama)
                        : (vehicle.opd || '');
                }
                document.getElementById('edit_pemegang').value = vehicle.pemegang || '';
                document.getElementById('edit_kondisi').value = vehicle.kondisi || 'Baik';
                document.getElementById('edit_status').value = vehicle.status || 'Tersedia';
                document.getElementById('edit_tahun_pembuatan').value = vehicle.tahun_pembuatan || '';
                document.getElementById('edit_warna').value = vehicle.warna || '';
                document.getElementById('edit_stnk_ada').value = vehicle.stnk_ada || 'Ada';
                document.getElementById('edit_bpkb_ada').value = vehicle.bpkb_ada || 'Ada';
                document.getElementById('edit_tgl_stnk').value = vehicle.tgl_stnk || '';
                document.getElementById('edit_tgl_perolehan').value = vehicle.tgl_perolehan || '';
                document.getElementById('edit_nilai_perolehan').value = vehicle.nilai_perolehan || '';
                document.getElementById('edit_no_mesin').value = vehicle.no_mesin || '';
                document.getElementById('edit_no_rangka').value = vehicle.no_rangka || '';
                document.getElementById('edit_keterangan').value = vehicle.keterangan || '';
            });
        }

        // Export/Import Scripts
        const exportButton = document.querySelector('[data-export-button]');
        if (exportButton) {
            exportButton.addEventListener('click', function () {
                const label = exportButton.querySelector('[data-export-label]');
                exportButton.classList.add('is-loading');
                if (label) label.textContent = 'Menyiapkan...';
                setTimeout(() => {
                    exportButton.classList.remove('is-loading');
                    if (label) label.textContent = 'Export';
                }, 2500);
            });
        }

        // --- AI SMART IMPORT MULTI-STEP LOGIC ---
        const importModalElement = document.getElementById('importModal');
        const importModalDialog = document.getElementById('importModalDialog');
        const importModalContainer = document.getElementById('importModalContainer');
        const importFileField = document.getElementById('importFileField');
        const importFileNameText = document.getElementById('importFileNameText');

        // Simpan HTML form awal sebagai cadangan saat modal di-close/reset
        const initialFormHtml = importModalContainer.innerHTML;

        if (importFileField && importFileNameText) {
            // Tampilkan nama file yang dipilih
            importModalElement.addEventListener('change', function(e) {
                if (e.target && e.target.id === 'importFileField') {
                    const file = e.target.files[0];
                    if (file) {
                        importFileNameText.textContent = `File terpilih: ${file.name} (${(file.size / 1024).toFixed(1)} KB)`;
                    } else {
                        importFileNameText.textContent = 'Belum ada file dipilih.';
                    }
                }
            });
        }

        // Reset modal ke tampilan awal saat ditutup
        if (importModalElement) {
            importModalElement.addEventListener('hidden.bs.modal', function() {
                importModalContainer.innerHTML = initialFormHtml;
                importModalDialog.classList.remove('modal-xl');
                importModalDialog.classList.add('modal-dialog-centered');
            });
        }

        // Intersept submit form pratinjau
        importModalElement.addEventListener('submit', function(e) {
            if (e.target && e.target.id === 'importPreviewForm') {
                e.preventDefault();
                
                const form = e.target;
                const submitBtn = document.getElementById('importPreviewSubmit');
                const formData = new FormData(form);

                // Efek loading premium
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Menganalisis Kolom (AI)...';

                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Perbesar ukuran modal menjadi modal-xl untuk visualisasi mapping
                        importModalDialog.classList.remove('modal-dialog-centered');
                        importModalDialog.classList.add('modal-xl');

                        // Render Halaman Pemetaan (Mapping)
                        renderMappingInterface(data);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Membaca File',
                            text: data.message || 'Terjadi kesalahan saat memproses file Excel.',
                            confirmButtonColor: '#1e40af',
                        });
                        // Kembalikan tombol ke sedia kala
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-cpu me-1"></i> Analisis Kolom (AI)';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error Sistem',
                        text: 'Gagal terhubung ke server untuk menganalisis file Excel.',
                        confirmButtonColor: '#1e40af',
                    });
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-cpu me-1"></i> Analisis Kolom (AI)';
                });
            }
        });

        // Merender Antarmuka Pemetaan Kolom Excel
        function renderMappingInterface(data) {
            const headers = data.headers;
            const samples = data.samples;
            const targetColumns = data.target_columns;
            const suggestedMapping = data.suggested_mapping;
            const importToken = data.import_token;
            const headerRowIndex = data.header_row_index;

            // Generate header preview untuk tabel
            let tableHeaderHtml = '<th class="bg-light px-3 py-2 text-center" style="width: 50px;">No.</th>';
            headers.forEach(h => {
                tableHeaderHtml += `<th class="bg-light px-3 py-2">${escapeHtml(h)}</th>`;
            });

            // Generate baris sampel data
            let tableBodyHtml = '';
            samples.forEach((row, rIdx) => {
                tableBodyHtml += '<tr>';
                tableBodyHtml += `<td class="text-center text-secondary small px-3 py-2 fw-medium">${rIdx + 1}</td>`;
                headers.forEach((h, hIdx) => {
                    const cellVal = row[hIdx] !== undefined && row[hIdx] !== null ? row[hIdx] : '-';
                    tableBodyHtml += `<td class="px-3 py-2 small text-truncate" style="max-width: 150px;" title="${escapeHtml(cellVal)}">${escapeHtml(cellVal)}</td>`;
                });
                tableBodyHtml += '</tr>';
            });

            // Generate baris form pemetaan kolom
            let mappingRowsHtml = '';
            Object.entries(targetColumns).forEach(([dbKey, dbLabel]) => {
                // Cari apakah ada saran AI yang cocok otomatis
                let selectOptionsHtml = '<option value="">-- Abaikan Kolom Ini --</option>';
                headers.forEach(h => {
                    const isSuggested = suggestedMapping[h] === dbKey;
                    selectOptionsHtml += `<option value="${escapeHtml(h)}" ${isSuggested ? 'selected' : ''}>${escapeHtml(h)}</option>`;
                });

                // Cek apakah kolom ini berhasil dipetakan secara otomatis oleh AI
                const hasMatch = Object.values(suggestedMapping).includes(dbKey);
                const badgeHtml = hasMatch 
                    ? '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1"><i class="bi bi-robot me-1"></i> Cocok (AI)</span>'
                    : '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1">Manual</span>';

                mappingRowsHtml += `
                    <div class="row align-items-center py-2 border-bottom g-3">
                        <div class="col-md-4">
                            <div class="fw-bold text-navy small">${escapeHtml(dbLabel)}</div>
                            <div class="text-secondary" style="font-size: 0.75rem;">Field DB: <code>${dbKey}</code></div>
                        </div>
                        <div class="col-md-2 text-md-center">
                            ${badgeHtml}
                        </div>
                        <div class="col-md-6">
                            <select name="mapping[${dbKey}]" class="form-select form-select-sm shadow-none mapping-select border-primary-hover">
                                ${selectOptionsHtml}
                            </select>
                        </div>
                    </div>
                `;
            });

            // Ganti kontainer modal dengan Form Pemetaan Kolom
            importModalContainer.innerHTML = `
                <form action="{{ route('vehicles.import') }}" method="POST" id="importExecuteForm">
                    @csrf
                    <input type="hidden" name="import_token" value="${escapeHtml(importToken)}">
                    <input type="hidden" name="header_row_index" value="${headerRowIndex}">
                    <input type="hidden" name="target_table" value="{{ request('tab', 'real') }}">
                    
                    <!-- Simpan nama header Excel asli ke input JSON untuk dipetakan indeksnya di Backend -->
                    ${headers.map((h, idx) => `<input type="hidden" name="headers[${idx}]" value="${escapeHtml(h)}">`).join('')}

                    <div class="modal-body p-4">
                        
                        <!-- NOTIFIKASI TEMA AI -->
                        <div class="alert alert-info border-0 bg-info bg-opacity-10 text-navy d-flex align-items-center mb-4 rounded-3">
                            <div class="fs-4 me-3 text-info">
                                <i class="bi bi-robot"></i>
                            </div>
                            <div>
                                <h6 class="alert-heading fw-bold mb-1" style="font-size: 0.9rem;">Analisis Semantik AI Selesai</h6>
                                <p class="mb-0 small text-secondary">AI telah memetakan kolom Excel Anda secara cerdas. Silakan periksa kembali daftar di bawah sebelum melanjutkan.</p>
                            </div>
                        </div>

                        <!-- PRATINJAU DOKUMEN EXCEL -->
                        <h6 class="fw-bold text-navy mb-3"><i class="bi bi-table me-1"></i> Preview Data Excel Anda (3 Baris Sampel)</h6>
                        <div class="table-responsive border rounded-3 mb-4 bg-white shadow-sm" style="max-height: 200px;">
                            <table class="table table-hover table-striped table-sm mb-0 align-middle">
                                <thead>
                                    <tr>${tableHeaderHtml}</tr>
                                </thead>
                                <tbody>
                                    ${tableBodyHtml}
                                </tbody>
                            </table>
                        </div>

                        <!-- DAFTAR PEMETAAN KOLOM -->
                        <h6 class="fw-bold text-navy mb-3"><i class="bi bi-arrow-left-right me-1"></i> Konfirmasi Pemetaan Kolom Database</h6>
                        <div class="border rounded-3 p-3 bg-white shadow-sm">
                            <div class="row border-bottom pb-2 fw-semibold text-secondary small d-none d-md-flex">
                                <div class="col-md-4">Kolom Aplikasi E-RANDIS</div>
                                <div class="col-md-2 text-center">Status Pemetaan</div>
                                <div class="col-md-6">Pilih Kolom dari Excel Anda</div>
                            </div>
                            <div class="mapping-rows" style="max-height: 350px; overflow-y: auto; overflow-x: hidden; padding-right: 5px;">
                                ${mappingRowsHtml}
                            </div>
                        </div>

                    </div>
                    
                    <div class="modal-footer border-top bg-light px-4 py-3 rounded-bottom-4 justify-content-between">
                        <button type="button" class="btn btn-light border fw-medium" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success fw-medium px-4" id="importExecuteSubmit">
                            <i class="bi bi-check-all me-1"></i> Mulai Impor Data
                        </button>
                    </div>
                </form>
            `;

            // Intersept submit form eksekusi impor final
            const executeForm = document.getElementById('importExecuteForm');
            if (executeForm) {
                executeForm.addEventListener('submit', function() {
                    const submitBtn = document.getElementById('importExecuteSubmit');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Mengimpor Data Massal...';
                });
            }
        }


        // 6. Real-time File Count Validation (Max 4 Photos)
        const photoInputs = document.querySelectorAll('input[name="foto_kendaraan[]"]');
        photoInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.files.length > 4) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Terlalu Banyak Foto',
                        text: 'Anda hanya dapat mengunggah maksimal 4 foto per kendaraan.',
                        confirmButtonColor: '#1e40af',
                    });
                    this.value = ''; // Kosongkan input
                }
            });
        });

        // ==========================================
        // MAGIC BUTTON: DIAGNOSIS DUPLIKASI DATA
        // ==========================================
        const btnCheckDuplicates = document.getElementById('btnCheckDuplicates');
        const diagnosisModal = new bootstrap.Modal(document.getElementById('diagnosisDuplicatesModal'));

        if (btnCheckDuplicates) {
            btnCheckDuplicates.addEventListener('click', function() {
                // Tampilkan loading spinner awal di tabel
                document.getElementById('vehicle-dup-list').innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center py-5 text-secondary">
                            <div class="spinner-border text-warning mb-2" role="status"></div>
                            <div class="small fw-medium">Sedang mendiagnosis database kendaraan ganda...</div>
                        </td>
                    </tr>
                `;
                document.getElementById('opd-dup-list').innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center py-5 text-secondary">
                            <div class="spinner-border text-info mb-2" role="status"></div>
                            <div class="small fw-medium">Sedang memindai kemiripan instansi OPD...</div>
                        </td>
                    </tr>
                `;
                document.getElementById('vehicle-dup-count').textContent = '0';
                document.getElementById('opd-dup-count').textContent = '0';

                // Buka modal
                diagnosisModal.show();

                // Panggil Ajax Diagnosis
                fetch("{{ route('vehicles.check-duplicates') }}", {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderDuplicateVehicles(data.vehicles);
                        renderDuplicateOpds(data.opds);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Diagnosis Gagal',
                            text: data.message || 'Terjadi kesalahan internal saat memeriksa data.',
                            confirmButtonColor: '#1e40af',
                        });
                        diagnosisModal.hide();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error Koneksi',
                        text: 'Gagal terhubung ke database untuk memindai duplikasi.',
                        confirmButtonColor: '#1e40af',
                    });
                    diagnosisModal.hide();
                });
            });
        }

        // Render Kendaraan Ganda
        function renderDuplicateVehicles(vehicles) {
            const listContainer = document.getElementById('vehicle-dup-list');
            document.getElementById('vehicle-dup-count').textContent = vehicles.length;

            if (vehicles.length === 0) {
                listContainer.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center py-5 text-success">
                            <i class="bi bi-patch-check-fill fs-1 text-success d-block mb-2"></i>
                            <h6 class="fw-bold mb-1">Database Bersih!</h6>
                            <p class="mb-0 small text-secondary">Luar biasa! Tidak terdeteksi adanya plat ganda atau nomor mesin bentrok di sistem.</p>
                        </td>
                    </tr>
                `;
                return;
            }

            let html = '';
            vehicles.forEach((item, index) => {
                // Generate baris perbandingan kolom detail
                let diffRowsHtml = '';
                item.differences.forEach(diff => {
                    const rowClass = diff.is_different ? 'table-danger bg-danger bg-opacity-10' : '';
                    const badgeHtml = diff.is_different 
                        ? '<span class="badge bg-danger text-white px-2 py-0.5" style="font-size: 0.65rem;">Berbeda</span>' 
                        : '<span class="badge bg-light text-secondary border px-2 py-0.5" style="font-size: 0.65rem;">Identik</span>';

                    diffRowsHtml += `
                        <tr class="${rowClass}">
                            <td class="fw-bold text-navy py-2 px-3" style="width: 25%; font-size: 0.8rem;">${escapeHtml(diff.label)}</td>
                            <td class="text-secondary py-2 px-3" style="width: 35%; font-size: 0.8rem;">${escapeHtml(diff.original_val)}</td>
                            <td class="text-dark fw-bold py-2 px-3" style="width: 30%; font-size: 0.8rem;">${escapeHtml(diff.duplicate_val)}</td>
                            <td class="text-center py-2 px-3" style="width: 10%;">${badgeHtml}</td>
                        </tr>
                    `;
                });

                html += `
                    <tr class="align-middle border-bottom-0" id="vehicle-dup-row-${item.duplicate_id}">
                        <td colspan="4" class="p-0">
                            <!-- BRIEF INFO ROW -->
                            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between p-3 bg-white hover-shadow border-bottom transition-all gap-3">
                                <div class="d-flex flex-wrap align-items-center gap-3 flex-grow-1">
                                    <div class="text-center bg-light border rounded px-3 py-1.5" style="min-width: 105px;">
                                        <div class="text-secondary small fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.5px;">PLAT GANDA</div>
                                        <span class="badge bg-danger text-white fw-bold px-2 py-0.5" style="font-size: 0.8rem;">${escapeHtml(item.duplicate_plate)}</span>
                                    </div>
                                    <div class="text-center bg-light border rounded px-3 py-1.5" style="min-width: 105px;">
                                        <div class="text-secondary small fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.5px;">PLAT INDUK</div>
                                        <span class="badge bg-success text-white fw-bold px-2 py-0.5" style="font-size: 0.8rem;">${escapeHtml(item.original_plate || '-')}</span>
                                    </div>
                                    <div class="ms-2">
                                        <div class="fw-bold text-navy mb-0" style="font-size: 0.9rem;">${escapeHtml(item.duplicate_merk)}</div>
                                        <div class="text-secondary small" style="font-size: 0.75rem;"><i class="bi bi-building me-1"></i>${escapeHtml(item.duplicate_opd)}</div>
                                    </div>
                                    <div class="ms-md-auto d-flex align-items-center gap-2">
                                        <span class="badge bg-warning text-dark px-2.5 py-1.5 d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;">
                                            <i class="bi bi-exclamation-triangle-fill"></i>
                                            <span>${escapeHtml(item.reason)}</span>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center gap-2">
                                    <!-- MERGE AND DELETE ACTIONS -->
                                    <button type="button" class="btn btn-sm btn-outline-success fw-bold d-inline-flex align-items-center gap-1 btn-resolve-vehicle shadow-sm" data-action="merge" data-original-id="${item.original_id}" data-duplicate-id="${item.duplicate_id}">
                                        <i class="bi bi-intersect"></i> <span>Gabungkan</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger fw-bold d-inline-flex align-items-center gap-1 btn-resolve-vehicle shadow-sm" data-action="delete" data-original-id="${item.original_id}" data-duplicate-id="${item.duplicate_id}">
                                        <i class="bi bi-trash3"></i> <span>Hapus</span>
                                    </button>
                                    
                                    <!-- EXPAND BUTTON -->
                                    <button class="btn btn-sm btn-light border shadow-sm ms-1 fw-medium" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-diff-${item.duplicate_id}" aria-expanded="false" aria-controls="collapse-diff-${item.duplicate_id}">
                                        <i class="bi bi-eye me-1 text-info"></i> Bandingkan
                                    </button>
                                </div>
                            </div>
                            
                            <!-- COLLAPSIBLE COMPARISON TABLE -->
                            <div class="collapse bg-light p-3 border-bottom shadow-inner" id="collapse-diff-${item.duplicate_id}">
                                <div class="card card-body border-0 shadow-none p-0 bg-transparent">
                                    <div class="d-flex align-items-center justify-content-between mb-2 px-1">
                                        <h6 class="fw-bold text-navy mb-0" style="font-size: 0.85rem;"><i class="bi bi-grid-3x3-gap-fill me-1"></i> Perbandingan Atribut Kendaraan Ganda vs Induk</h6>
                                        <span class="text-secondary small" style="font-size: 0.72rem;"><i class="bi bi-info-circle me-1"></i>Baris berwarna merah menunjukkan nilai yang berbeda</span>
                                    </div>
                                    <div class="table-responsive border rounded-3 bg-white">
                                        <table class="table table-hover table-bordered table-sm mb-0 align-middle">
                                            <thead class="table-secondary" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                                <tr>
                                                    <th class="py-2 px-3">Kolom / Atribut</th>
                                                    <th class="py-2 px-3">Data Induk (Asli)</th>
                                                    <th class="py-2 px-3">Data Ganda (Impor Baru)</th>
                                                    <th class="py-2 px-3 text-center">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${diffRowsHtml}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
            });

            listContainer.innerHTML = html;
            attachVehicleResolveEvents();
        }

        // Render OPD Ganda
        function renderDuplicateOpds(opds) {
            const listContainer = document.getElementById('opd-dup-list');
            document.getElementById('opd-dup-count').textContent = opds.length;

            if (opds.length === 0) {
                listContainer.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center py-5 text-success">
                            <i class="bi bi-patch-check-fill fs-1 text-success d-block mb-2"></i>
                            <h6 class="fw-bold mb-1">Database Bersih!</h6>
                            <p class="mb-0 small text-secondary">Tidak ada nama OPD/Dinas yang mirip atau terindikasi ganda di sistem.</p>
                        </td>
                    </tr>
                `;
                return;
            }

            let html = '';
            opds.forEach(item => {
                html += `
                    <tr class="align-middle" id="opd-dup-row-${item.opd_b_id}">
                        <td class="px-3 py-3">
                            <div class="fw-bold text-navy">${escapeHtml(item.opd_a_nama)}</div>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-0.5 mt-1 small" style="font-size: 0.7rem;">
                                Dipertahankan (${item.count_a} Kendaraan)
                            </span>
                        </td>
                        <td class="px-3 py-3">
                            <div class="fw-bold text-danger">${escapeHtml(item.opd_b_nama)}</div>
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-0.5 mt-1 small" style="font-size: 0.7rem;">
                                Akan Dihapus (${item.count_b} Kendaraan)
                            </span>
                        </td>
                        <td class="px-3 py-3 text-secondary small">
                            ${escapeHtml(item.reason)}
                        </td>
                        <td class="px-3 py-3 text-center">
                            <button type="button" class="btn btn-primary btn-xs fw-semibold py-2 px-3 btn-resolve-opd" data-target-id="${item.opd_a_id}" data-source-id="${item.opd_b_id}">
                                <i class="bi bi-signpost-split me-1"></i> Gabungkan Instansi
                            </button>
                        </td>
                    </tr>
                `;
            });

            listContainer.innerHTML = html;
            attachOpdResolveEvents();
        }

        // Event Resolusi Kendaraan
        function attachVehicleResolveEvents() {
            document.querySelectorAll('.btn-resolve-vehicle').forEach(btn => {
                btn.addEventListener('click', function() {
                    const action = this.getAttribute('data-action');
                    const originalId = this.getAttribute('data-original-id');
                    const duplicateId = this.getAttribute('data-duplicate-id');
                    const btnElement = this;
                    
                    const executeResolve = (direction = 'keep_original') => {
                        // Tampilkan loading di baris tersebut
                        const actionsCell = btnElement.closest('td');
                        const originalHtml = actionsCell.innerHTML;
                        actionsCell.innerHTML = '<span class="spinner-border spinner-border-sm text-primary" role="status"></span>';

                        fetch("{{ route('vehicles.resolve-duplicate-vehicle') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                original_id: originalId,
                                duplicate_id: duplicateId,
                                action: action,
                                direction: direction
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Sukses',
                                    text: data.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                // Hapus baris dari tabel
                                const row = document.getElementById(`vehicle-dup-row-${duplicateId}`);
                                if (row) row.remove();
                                
                                // Update counter
                                const countEl = document.getElementById('vehicle-dup-count');
                                const currentCount = parseInt(countEl.textContent);
                                countEl.textContent = Math.max(0, currentCount - 1);
                                
                                if (currentCount - 1 <= 0) {
                                    document.getElementById('vehicle-dup-list').innerHTML = `
                                        <tr>
                                            <td colspan="4" class="text-center py-5 text-success">
                                                <i class="bi bi-patch-check-fill fs-1 text-success d-block mb-2"></i>
                                                <h6 class="fw-bold mb-1">Database Bersih!</h6>
                                                <p class="mb-0 small text-secondary">Semua duplikasi telah diselesaikan.</p>
                                            </td>
                                        </tr>
                                    `;
                                }
                            } else {
                                actionsCell.innerHTML = originalHtml;
                                attachVehicleResolveEvents(); // Re-attach event listeners
                                Swal.fire('Gagal', data.message || 'Terjadi kesalahan.', 'error');
                            }
                        })
                        .catch(error => {
                            actionsCell.innerHTML = originalHtml;
                            attachVehicleResolveEvents(); // Re-attach event listeners
                            Swal.fire('Error', 'Gagal terhubung ke server.', 'error');
                        });
                    };

                    if (action === 'merge') {
                        Swal.fire({
                            title: 'Pilih Data Utama',
                            text: 'Pilih data mana yang akan menjadi data utama (dipertahankan). Data lainnya akan dihapus setelah melengkapi kolom yang kosong.',
                            icon: 'question',
                            showDenyButton: true,
                            showCancelButton: true,
                            confirmButtonText: 'Pertahankan Data Induk',
                            denyButtonText: 'Pertahankan Data Ganda',
                            cancelButtonText: 'Batal',
                            confirmButtonColor: '#1e40af',
                            denyButtonColor: '#0ea5e9'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                executeResolve('keep_original');
                            } else if (result.isDenied) {
                                executeResolve('keep_duplicate');
                            }
                        });
                    } else {
                        Swal.fire({
                            title: 'Konfirmasi Penghapusan',
                            text: 'Apakah Anda yakin ingin menghapus data kendaraan ganda ini dari database?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#dc3545',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Ya, Hapus!',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                executeResolve('keep_original');
                            }
                        });
                    }
                });
            });
        }

        // Event Resolusi OPD
        function attachOpdResolveEvents() {
            document.querySelectorAll('.btn-resolve-opd').forEach(btn => {
                btn.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target-id');
                    const sourceId = this.getAttribute('data-source-id');
                    const row = document.getElementById(`opd-dup-row-${sourceId}`);

                    Swal.fire({
                        title: 'Gabungkan Instansi OPD?',
                        text: 'Semua data kendaraan pada OPD duplikat akan otomatis dipindahkan ke OPD utama. Setelah itu, OPD duplikat yang kosong akan dihapus bersih secara otomatis.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#1e40af',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Gabungkan OPD!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const actionCell = this.closest('td');
                            const originalHtml = actionCell.innerHTML;
                            actionCell.innerHTML = '<span class="spinner-border spinner-border-sm text-primary" role="status"></span>';

                            fetch("{{ route('vehicles.resolve-duplicate-opd') }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({
                                    target_opd_id: targetId,
                                    source_opd_id: sourceId
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Sukses Konsolidasi',
                                        text: data.message,
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                    // Hapus baris dari tabel diagnosis
                                    row.style.transition = 'all 0.5s ease';
                                    row.style.opacity = '0';
                                    setTimeout(() => {
                                        row.remove();
                                        // Update counter
                                        const countEl = document.getElementById('opd-dup-count');
                                        const newCount = Math.max(0, parseInt(countEl.textContent) - 1);
                                        countEl.textContent = newCount;
                                        if (newCount === 0) {
                                            renderDuplicateOpds([]);
                                        }
                                    }, 500);
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal Konsolidasi',
                                        text: data.message,
                                        confirmButtonColor: '#1e40af'
                                    });
                                    actionCell.innerHTML = originalHtml;
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                actionCell.innerHTML = originalHtml;
                            });
                        }
                    });
                });
            });
        }

        // Sanitasi Identifikasi Kendaraan (Nomor Rangka & Mesin)
        const sanitizeForm = document.getElementById('sanitizeIdentifiersForm');
        if (sanitizeForm) {
            sanitizeForm.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Konfirmasi Pembersihan?',
                    text: 'Aksi ini akan membersihkan semua karakter spesial (spasi, tanda hubung, titik, dll) kecuali huruf dan angka pada kolom Nomor Rangka dan Nomor Mesin untuk SELURUH kendaraan di sistem.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#1e40af',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Bersihkan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Memproses data...',
                            text: 'Mohon tunggu sebentar.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        this.submit();
                    }
                });
            });
        }
    });
</script>
@endpush
@endsection
