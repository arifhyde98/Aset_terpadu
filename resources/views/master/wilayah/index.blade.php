@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Master Data Wilayah & Pejabat</h4>
            <p class="text-secondary mb-0">Pengaturan terpadu data Kecamatan, Desa, Camat, Kades, Pemohon SKPT, dan Judul Laporan.</p>
        </div>
    </div>    @php
        $activeTab = session('active_tab', 'kecamatan');
    @endphp

    <div class="card border-0 shadow-sm clean-card">
        <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
            <ul class="nav nav-tabs card-header-tabs" id="masterTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link {{ $activeTab === 'kecamatan' ? 'active fw-bold' : 'text-secondary' }}" data-bs-toggle="tab" data-bs-target="#tab-kecamatan" type="button">Kecamatan</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link {{ $activeTab === 'desa' ? 'active fw-bold' : 'text-secondary' }}" data-bs-toggle="tab" data-bs-target="#tab-desa" type="button">Desa / Kelurahan</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link {{ $activeTab === 'camat' ? 'active fw-bold' : 'text-secondary' }}" data-bs-toggle="tab" data-bs-target="#tab-camat" type="button">Camat</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link {{ $activeTab === 'kades' ? 'active fw-bold' : 'text-secondary' }}" data-bs-toggle="tab" data-bs-target="#tab-kades" type="button">Kepala Desa</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link {{ $activeTab === 'pemohon' ? 'active fw-bold' : 'text-secondary' }}" data-bs-toggle="tab" data-bs-target="#tab-pemohon" type="button">Pemohon SKPT</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link {{ $activeTab === 'judul' ? 'active fw-bold' : 'text-secondary' }}" data-bs-toggle="tab" data-bs-target="#tab-judul" type="button">Judul Laporan</button>
                </li>
            </ul>
        </div>
        
        <div class="card-body p-0">
            <div class="tab-content" id="masterTabsContent">
                
                <!-- TAB KECAMATAN -->
                <div class="tab-pane fade {{ $activeTab === 'kecamatan' ? 'show active' : '' }}" id="tab-kecamatan">
                    <div class="p-4 d-flex justify-content-end">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahKec"><i class="bi bi-plus-circle me-1"></i> Tambah Kecamatan</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary">
                                <tr>
                                    <th class="ps-4" width="8%">NO</th>
                                    <th>NAMA KECAMATAN</th>
                                    <th class="text-end pe-4" width="15%">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($kecamatan as $i => $row)
                                <tr>
                                    <td class="ps-4">{{ $i + 1 }}</td>
                                    <td class="fw-medium">{{ $row->nama }}</td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditKec{{ $row->id }}">Edit</button>
                                        <form action="{{ route('master.kecamatan.destroy', $row->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus kecamatan ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center py-4 text-secondary">Data belum tersedia.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB DESA -->
                <div class="tab-pane fade {{ $activeTab === 'desa' ? 'show active' : '' }}" id="tab-desa">
                    <!-- 1. Toolbar Pencarian & Filter (Filter Bar) -->
                    <div class="p-3 px-4 border-bottom bg-white desa-filter-bar">
                        <div class="row g-2 align-items-center justify-content-between">
                            <!-- Kolom Kiri: Search Input (Real-time search bar dengan ikon kaca pembesar) -->
                            <div class="col-12 col-md-4 col-lg-3">
                                <div class="input-group input-group-sm desa-search-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted ps-3">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" id="desaSearchInput" class="form-control form-control-sm bg-light border-start-0 border-end-0 ps-1" placeholder="Cari Desa/Kel atau Kecamatan..." autocomplete="off">
                                    <button class="btn btn-light border border-start-0 text-muted px-2.5" type="button" id="btnDesaClearSearch" style="display: none;" title="Hapus pencarian">
                                        <i class="bi bi-x-circle-fill small"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Kolom Kanan: Select Filter (Kecamatan, Jenis, Keterkaitan Aset), Primary Button -->
                            <div class="col-12 col-md-8 col-lg-9">
                                <div class="d-flex flex-wrap align-items-center justify-content-md-end gap-2">
                                    <!-- Select Filter (Kecamatan) -->
                                    <div style="min-width: 155px;">
                                        <select id="desaFilterKecamatan" class="form-select form-select-sm">
                                            <option value="">Semua Kecamatan ({{ count($kecamatan) }})</option>
                                            @foreach($kecamatan as $k)
                                                <option value="{{ $k->id }}">{{ $k->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Select Filter (Jenis): All, Desa, Kelurahan -->
                                    <div style="min-width: 120px;">
                                        <select id="desaFilterJenis" class="form-select form-select-sm">
                                            <option value="">Semua Jenis</option>
                                            <option value="desa">Desa</option>
                                            <option value="kelurahan">Kelurahan</option>
                                        </select>
                                    </div>

                                    <!-- Select Filter (Keterkaitan Aset) -->
                                    <div style="min-width: 155px;">
                                        <select id="desaFilterAset" class="form-select form-select-sm">
                                            <option value="">Semua Status Aset</option>
                                            <option value="bebas">Bebas Aset (Bisa Dihapus)</option>
                                            <option value="terkait">Terkait Aset (Diproteksi)</option>
                                        </select>
                                    </div>

                                    <!-- Primary Button: Tombol "+ Tambah Desa/Kel" -->
                                    <button type="button" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1 shadow-xs fw-semibold px-3" data-bs-toggle="modal" data-bs-target="#modalTambahDesa">
                                        <i class="bi bi-plus-lg"></i>
                                        <span>Tambah Desa/Kel</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Informasi Ringkasan & Aksi Massal (Control Bar) -->
                    @php
                        $totalDesaCount = count($desa);
                        $desaTerkaitCount = $desa->filter(fn($d) => ($d->aset_tanah_count + $d->bangunan_count) > 0)->count();
                        $desaBebasCount = $totalDesaCount - $desaTerkaitCount;
                    @endphp
                    <div class="px-4 py-2.5 bg-light-subtle border-bottom desa-control-bar">
                        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
                            <!-- Sebelah Kiri: Teks statistik cepat -->
                            <div class="d-flex align-items-center flex-wrap gap-2 text-secondary small">
                                <span><i class="bi bi-geo-alt text-primary opacity-75 me-1"></i>Total Kecamatan: <strong class="text-dark">{{ count($kecamatan) }}</strong></span>
                                <span class="text-muted opacity-50">|</span>
                                <span><i class="bi bi-houses text-primary opacity-75 me-1"></i>Total Desa: <strong class="text-dark" id="statTotalDesa">{{ $totalDesaCount }}</strong></span>
                                <span class="text-muted opacity-50">|</span>
                                <span><i class="bi bi-shield-check text-success me-1"></i>Bebas Aset: <strong class="text-success" id="statDesaBebas">{{ $desaBebasCount }}</strong></span>
                                <span class="text-muted opacity-50">|</span>
                                <span><i class="bi bi-lock text-warning me-1"></i>Terkait Aset: <strong class="text-warning-emphasis" id="statDesaTerkait">{{ $desaTerkaitCount }}</strong></span>
                                <span id="badgeFilteredDesa" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5 ms-1" style="display: none;">
                                    <i class="bi bi-funnel me-1"></i><span id="statFilteredCount">0</span> terfilter
                                </span>
                                <span id="badgeSelectedDesa" class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-2 py-0.5 ms-1" style="display: none;">
                                    <span id="statSelectedCount">0</span> dipilih
                                </span>
                            </div>

                            <!-- Sebelah Kanan: Tombol "Bulk Actions" (Aksi Massal) dan ikon Hapus Terpilih -->
                            <div class="d-flex align-items-center gap-2">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-inline-flex align-items-center gap-1.5" type="button" id="btnBulkActionsDesa" data-bs-toggle="dropdown" aria-expanded="false" disabled>
                                        <i class="bi bi-layers"></i>
                                        <span>Bulk Actions</span>
                                        <span class="badge bg-primary text-white rounded-pill ms-1 bulk-selected-badge" style="display: none;">0</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-1" aria-labelledby="btnBulkActionsDesa">
                                        <li>
                                            <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" id="btnSelectOnlyFreeAset">
                                                <i class="bi bi-check2-circle text-success"></i>
                                                <span>Pilih Hanya yang Bebas Aset</span>
                                            </button>
                                        </li>
                                        <li><hr class="dropdown-divider my-1"></li>
                                        <li>
                                            <button type="button" class="dropdown-item text-danger d-flex align-items-center gap-2 py-2" id="dropdownBulkDeleteDesa">
                                                <i class="bi bi-trash3 text-danger"></i>
                                                <span>Hapus Terpilih (<span class="bulk-selected-count-text">0</span>)</span>
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1.5" id="btnDirectBulkDeleteDesa" disabled title="Hapus baris yang dicentang">
                                    <i class="bi bi-trash3"></i>
                                    <span class="d-none d-md-inline">Hapus Terpilih</span>
                                    <span class="badge bg-danger text-white rounded-pill ms-0.5 bulk-selected-badge" style="display: none;">0</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Restrukturisasi Tabel Data (Table Component) -->
                    <div class="table-responsive border-0 mb-0">
                        <table class="table table-hover align-middle mb-0" id="desaTable">
                            <thead class="bg-light text-secondary">
                                <tr>
                                    <th width="48" class="ps-4 text-center align-middle">
                                        <input class="form-check-input" type="checkbox" id="checkAllDesa" title="Pilih Semua di Halaman Ini">
                                    </th>
                                    <th width="65" class="text-center align-middle">NO.</th>
                                    <th class="align-middle">NAMA DESA/KEL</th>
                                    <th width="125" class="align-middle">JENIS</th>
                                    <th class="align-middle">KECAMATAN</th>
                                    <th width="155" class="align-middle">KETERKAITAN ASET</th>
                                    <th class="text-end pe-4 align-middle" width="110">AKSI</th>
                                </tr>
                            </thead>
                            <tbody id="desaTableBody">
                                @forelse($desa as $i => $row)
                                @php
                                    $totalAset = ($row->aset_tanah_count ?? 0) + ($row->bangunan_count ?? 0);
                                @endphp
                                <tr class="desa-row" 
                                    data-id="{{ $row->id }}" 
                                    data-nama="{{ strtolower($row->nama) }}" 
                                    data-kecamatan-id="{{ $row->kecamatan_id }}" 
                                    data-kecamatan-name="{{ strtolower($row->kecamatan->nama ?? '') }}" 
                                    data-jenis="{{ strtolower($row->jenis) }}"
                                    data-has-aset="{{ $totalAset > 0 ? '1' : '0' }}"
                                    data-aset-count="{{ $totalAset }}">
                                    <td class="ps-4 text-center align-middle">
                                        <input class="form-check-input desa-row-checkbox" type="checkbox" value="{{ $row->id }}" data-id="{{ $row->id }}" data-nama="{{ $row->nama }}" data-has-aset="{{ $totalAset > 0 ? '1' : '0' }}" data-aset-count="{{ $totalAset }}">
                                    </td>
                                    <td class="text-center text-secondary small align-middle desa-row-number">{{ $i + 1 }}</td>
                                    <td class="align-middle fw-semibold text-dark desa-nama-cell">
                                        {{ $row->nama }}
                                    </td>
                                    <td class="align-middle">
                                        @if(strtolower($row->jenis) === 'kelurahan')
                                            <span class="badge badge-jenis badge-kelurahan">
                                                <i class="bi bi-building me-1 opacity-75"></i>Kelurahan
                                            </span>
                                        @else
                                            <span class="badge badge-jenis badge-desa">
                                                <i class="bi bi-house-door me-1 opacity-75"></i>Desa
                                            </span>
                                        @endif
                                    </td>
                                    <td class="align-middle text-secondary">
                                        <i class="bi bi-geo-alt text-muted me-1.5 small"></i>
                                        <span class="desa-kecamatan-cell">{{ $row->kecamatan->nama ?? '-' }}</span>
                                    </td>
                                    <td class="align-middle">
                                        @if($totalAset > 0)
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1 small fw-medium d-inline-flex align-items-center" data-bs-toggle="tooltip" title="{{ $row->aset_tanah_count }} Aset Tanah, {{ $row->bangunan_count }} Bangunan terkait">
                                                <i class="bi bi-lock-fill text-warning me-1"></i>{{ $totalAset }} Aset (Terkait)
                                            </span>
                                        @else
                                            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle rounded-pill px-2.5 py-1 small fw-medium d-inline-flex align-items-center" data-bs-toggle="tooltip" title="Desa ini tidak terkait aset manapun (bebas untuk dihapus)">
                                                <i class="bi bi-shield-check text-success me-1"></i>0 Aset (Bebas)
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4 align-middle">
                                        <div class="d-inline-flex align-items-center gap-1.5">
                                            <button type="button" class="btn btn-action-icon" data-bs-toggle="modal" data-bs-target="#modalEditDesa{{ $row->id }}" title="Edit Desa/Kelurahan">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            @if($totalAset > 0)
                                                <button type="button" class="btn btn-action-icon btn-action-disabled text-muted opacity-50" disabled data-bs-toggle="tooltip" data-bs-placement="top" title="Aturan Sistem: Desa ini terkait {{ $totalAset }} aset aktif sehingga tidak bisa dihapus.">
                                                    <i class="bi bi-slash-circle"></i>
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-action-icon btn-action-delete" onclick="handleDeleteDesa({{ $row->id }}, '{{ addslashes($row->nama) }}')" title="Hapus Desa/Kelurahan (Bebas Aset)">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                                <form id="formDeleteDesa{{ $row->id }}" action="{{ route('master.desa.destroy', $row->id) }}" method="POST" class="d-none">
                                                    @csrf @method('DELETE')
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr id="desaInitialEmptyRow"><td colspan="7" class="text-center py-4 text-secondary">Data belum tersedia.</td></tr>
                                @endforelse
                                <tr id="desaNoResultsRow" style="display: none;">
                                    <td colspan="7" class="text-center py-5 text-secondary">
                                        <div class="py-3">
                                            <i class="bi bi-search text-muted fs-1 mb-2 d-block opacity-50"></i>
                                            <p class="fw-semibold text-dark mb-1">Tidak ada data Desa/Kelurahan yang cocok</p>
                                            <p class="text-muted small mb-3">Coba ubah kata kunci pencarian atau sesuaikan pilihan filter kecamatan, jenis, dan status aset.</p>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnResetDesaFilter">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Pencarian & Filter
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 4. Paginasi Komprehensif (Pagination Bar) -->
                    <div class="px-4 py-3 bg-white border-top desa-pagination-bar">
                        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
                            <!-- Sisi Kiri / Tengah: Penunjuk halaman aktif & navigasi -->
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <span class="text-secondary small" id="desaPaginationSummary">
                                    Menampilkan <strong>1</strong> - <strong>25</strong> dari <strong>{{ count($desa) }}</strong> data
                                </span>
                                <span class="badge bg-light text-secondary border px-2 py-1 rounded-pill small" id="desaPageIndicator">
                                    Page 1 of 1
                                </span>
                            </div>

                            <!-- Tombol Navigasi Halaman -->
                            <nav aria-label="Navigasi Halaman Desa">
                                <ul class="pagination pagination-sm mb-0 gap-1" id="desaPaginationButtons">
                                    <!-- Diisi via JavaScript -->
                                </ul>
                            </nav>

                            <!-- Sisi Kanan: Dropdown selector rows per page -->
                            <div class="d-flex align-items-center gap-2">
                                <label for="desaPerPage" class="text-secondary small text-nowrap mb-0">Rows per page:</label>
                                <select id="desaPerPage" class="form-select form-select-sm" style="width: 80px;">
                                    <option value="10">10</option>
                                    <option value="25" selected>25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Form Tersembunyi untuk Bulk Delete -->
                    <form id="formBulkDeleteDesa" action="{{ route('master.desa.bulkDestroy') }}" method="POST" class="d-none">
                        @csrf
                        <input type="hidden" name="ids" id="bulkDeleteDesaIds">
                    </form>
                </div>

                <!-- TAB CAMAT -->
                <div class="tab-pane fade {{ $activeTab === 'camat' ? 'show active' : '' }}" id="tab-camat">
                    <div class="p-4 d-flex justify-content-end">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahCamat"><i class="bi bi-plus-circle me-1"></i> Tambah Camat</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary">
                                <tr>
                                    <th class="ps-4" width="8%">NO</th>
                                    <th>NAMA CAMAT</th>
                                    <th>NIP</th>
                                    <th>KECAMATAN</th>
                                    <th>STATUS PJ</th>
                                    <th class="text-end pe-4" width="15%">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($camat as $i => $row)
                                <tr>
                                    <td class="ps-4">{{ $i + 1 }}</td>
                                    <td class="fw-medium">{{ $row->nama }}</td>
                                    <td>{{ $row->nip ?: '-' }}</td>
                                    <td>{{ $row->kecamatan->nama ?? '-' }}</td>
                                    <td>{!! $row->aktif ? '<span class="badge bg-warning text-dark">PJ / Plt</span>' : '<span class="badge bg-success">Definitif</span>' !!}</td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditCamat{{ $row->id }}">Edit</button>
                                        <form action="{{ route('master.camat.destroy', $row->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus camat ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center py-4 text-secondary">Data belum tersedia.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB KADES -->
                <div class="tab-pane fade {{ $activeTab === 'kades' ? 'show active' : '' }}" id="tab-kades">
                    <div class="p-4 d-flex justify-content-end">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahKades"><i class="bi bi-plus-circle me-1"></i> Tambah Kades</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary">
                                <tr>
                                    <th class="ps-4" width="8%">NO</th>
                                    <th>NAMA KADES/LURAH</th>
                                    <th>NIP</th>
                                    <th>DESA/KEL</th>
                                    <th>STATUS PJ</th>
                                    <th class="text-end pe-4" width="15%">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($kades as $i => $row)
                                <tr>
                                    <td class="ps-4">{{ $i + 1 }}</td>
                                    <td class="fw-medium">{{ $row->nama }}</td>
                                    <td>{{ $row->nip ?: '-' }}</td>
                                    <td>{{ $row->desa->nama ?? '-' }} <br><small class="text-secondary">{{ $row->desa->kecamatan->nama ?? '' }}</small></td>
                                    <td>{!! $row->aktif ? '<span class="badge bg-warning text-dark">PJ / Plt</span>' : '<span class="badge bg-success">Definitif</span>' !!}</td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditKades{{ $row->id }}">Edit</button>
                                        <form action="{{ route('master.kades.destroy', $row->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus pejabat ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center py-4 text-secondary">Data belum tersedia.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB PEMOHON -->
                <div class="tab-pane fade {{ $activeTab === 'pemohon' ? 'show active' : '' }}" id="tab-pemohon">
                    <div class="p-4 d-flex justify-content-end">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahPemohon"><i class="bi bi-plus-circle me-1"></i> Tambah Pemohon</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary">
                                <tr>
                                    <th class="ps-4" width="8%">NO</th>
                                    <th>NAMA PEMOHON</th>
                                    <th>NIK</th>
                                    <th>JABATAN</th>
                                    <th class="text-end pe-4" width="15%">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pemohon as $i => $row)
                                <tr>
                                    <td class="ps-4">{{ $i + 1 }}</td>
                                    <td class="fw-medium">{{ $row->nama }}</td>
                                    <td>{{ $row->nik ?: '-' }}</td>
                                    <td>{{ $row->jabatan ?: '-' }}</td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditPemohon{{ $row->id }}">Edit</button>
                                        <form action="{{ route('master.pemohon.destroy', $row->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus pemohon ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center py-4 text-secondary">Data belum tersedia.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB JUDUL LAPORAN -->
                <div class="tab-pane fade {{ $activeTab === 'judul' ? 'show active' : '' }}" id="tab-judul">
                    <div class="p-4 d-flex justify-content-end">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahJudul"><i class="bi bi-plus-circle me-1"></i> Tambah Judul</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary">
                                <tr>
                                    <th class="ps-4" width="8%">NO</th>
                                    <th>JUDUL LAPORAN</th>
                                    <th>STATUS</th>
                                    <th class="text-end pe-4" width="15%">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($judul as $i => $row)
                                <tr>
                                    <td class="ps-4">{{ $i + 1 }}</td>
                                    <td class="fw-medium">{{ $row->judul }}</td>
                                    <td>{!! $row->aktif ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>' !!}</td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditJudul{{ $row->id }}">Edit</button>
                                        <form action="{{ route('master.judul.destroy', $row->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus judul ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center py-4 text-secondary">Data belum tersedia.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!--                 MODALS SECTION                -->
<!-- ============================================= -->

@push('modals')
<!-- KECAMATAN -->
<div class="modal fade" id="modalTambahKec" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.kecamatan.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Tambah Kecamatan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><div class="mb-3"><label class="form-label">Nama Kecamatan</label><input type="text" name="nama" class="form-control" required></div></div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@foreach($kecamatan as $row)
<div class="modal fade" id="modalEditKec{{ $row->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.kecamatan.update', $row->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header"><h5 class="modal-title">Edit Kecamatan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><div class="mb-3"><label class="form-label">Nama Kecamatan</label><input type="text" name="nama" class="form-control" value="{{ $row->nama }}" required></div></div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@endforeach

<!-- DESA -->
<div class="modal fade" id="modalTambahDesa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.desa.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Tambah Desa/Kelurahan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kecamatan</label>
                        <select name="kecamatan_id" class="form-select" required>
                            <option value="">-- Pilih Kecamatan --</option>
                            @foreach($kecamatan as $k) <option value="{{ $k->id }}">{{ $k->nama }}</option> @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Nama Desa/Kel</label><input type="text" name="nama" class="form-control" required></div>
                    <div class="mb-3">
                        <label class="form-label">Jenis</label>
                        <select name="jenis" class="form-select" required><option value="Desa">Desa</option><option value="Kelurahan">Kelurahan</option></select>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@foreach($desa as $row)
<div class="modal fade" id="modalEditDesa{{ $row->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.desa.update', $row->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header"><h5 class="modal-title">Edit Desa/Kelurahan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kecamatan</label>
                        <select name="kecamatan_id" class="form-select" required>
                            @foreach($kecamatan as $k) <option value="{{ $k->id }}" {{ $k->id == $row->kecamatan_id ? 'selected' : '' }}>{{ $k->nama }}</option> @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Nama Desa/Kel</label><input type="text" name="nama" class="form-control" value="{{ $row->nama }}" required></div>
                    <div class="mb-3">
                        <label class="form-label">Jenis</label>
                        <select name="jenis" class="form-select" required><option value="Desa" {{ $row->jenis == 'Desa' ? 'selected' : '' }}>Desa</option><option value="Kelurahan" {{ $row->jenis == 'Kelurahan' ? 'selected' : '' }}>Kelurahan</option></select>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@endforeach

<!-- CAMAT -->
<div class="modal fade" id="modalTambahCamat" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.camat.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Tambah Camat</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kecamatan</label>
                        <select name="kecamatan_id" class="form-select" required>
                            <option value="">-- Pilih Kecamatan --</option>
                            @foreach($kecamatan as $k) <option value="{{ $k->id }}">{{ $k->nama }}</option> @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Nama Camat</label><input type="text" name="nama" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">NIP</label><input type="text" name="nip" class="form-control"></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="aktif" id="pjCamatAdd"><label class="form-check-label" for="pjCamatAdd">Status PJ / Plt</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@foreach($camat as $row)
<div class="modal fade" id="modalEditCamat{{ $row->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.camat.update', $row->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header"><h5 class="modal-title">Edit Camat</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kecamatan</label>
                        <select name="kecamatan_id" class="form-select" required>
                            @foreach($kecamatan as $k) <option value="{{ $k->id }}" {{ $k->id == $row->kecamatan_id ? 'selected' : '' }}>{{ $k->nama }}</option> @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Nama Camat</label><input type="text" name="nama" class="form-control" value="{{ $row->nama }}" required></div>
                    <div class="mb-3"><label class="form-label">NIP</label><input type="text" name="nip" class="form-control" value="{{ $row->nip }}"></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="aktif" id="pjCamatEdit{{ $row->id }}" {{ $row->aktif ? 'checked' : '' }}><label class="form-check-label" for="pjCamatEdit{{ $row->id }}">Status PJ / Plt</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@endforeach

<!-- KADES -->
<div class="modal fade" id="modalTambahKades" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.kades.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Tambah Kades/Lurah</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Desa / Kelurahan</label>
                        <select name="desa_id" class="form-select" required>
                            <option value="">-- Pilih Desa --</option>
                            @foreach($desa as $d) <option value="{{ $d->id }}">{{ $d->nama }} ({{ $d->kecamatan->nama ?? '' }})</option> @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Nama Kades/Lurah</label><input type="text" name="nama" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">NIP</label><input type="text" name="nip" class="form-control"></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="aktif" id="pjKadesAdd"><label class="form-check-label" for="pjKadesAdd">Status PJ / Plt</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@foreach($kades as $row)
<div class="modal fade" id="modalEditKades{{ $row->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.kades.update', $row->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header"><h5 class="modal-title">Edit Kades/Lurah</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Desa / Kelurahan</label>
                        <select name="desa_id" class="form-select" required>
                            @foreach($desa as $d) <option value="{{ $d->id }}" {{ $d->id == $row->desa_id ? 'selected' : '' }}>{{ $d->nama }} ({{ $d->kecamatan->nama ?? '' }})</option> @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Nama Kades/Lurah</label><input type="text" name="nama" class="form-control" value="{{ $row->nama }}" required></div>
                    <div class="mb-3"><label class="form-label">NIP</label><input type="text" name="nip" class="form-control" value="{{ $row->nip }}"></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="aktif" id="pjKadesEdit{{ $row->id }}" {{ $row->aktif ? 'checked' : '' }}><label class="form-check-label" for="pjKadesEdit{{ $row->id }}">Status PJ / Plt</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@endforeach

<!-- PEMOHON -->
<div class="modal fade" id="modalTambahPemohon" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.pemohon.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Tambah Pemohon</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" name="nama" class="form-control" required></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">NIK</label><input type="text" name="nik" class="form-control"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Pekerjaan</label><input type="text" name="pekerjaan" class="form-control"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Jabatan (Opsional)</label><input type="text" name="jabatan" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@foreach($pemohon as $row)
<div class="modal fade" id="modalEditPemohon{{ $row->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.pemohon.update', $row->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header"><h5 class="modal-title">Edit Pemohon</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" name="nama" class="form-control" value="{{ $row->nama }}" required></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">NIK</label><input type="text" name="nik" class="form-control" value="{{ $row->nik }}"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Pekerjaan</label><input type="text" name="pekerjaan" class="form-control" value="{{ $row->pekerjaan }}"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Jabatan (Opsional)</label><input type="text" name="jabatan" class="form-control" value="{{ $row->jabatan }}"></div>
                    <div class="mb-3"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" rows="2">{{ $row->alamat }}</textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@endforeach

<!-- JUDUL LAPORAN -->
<div class="modal fade" id="modalTambahJudul" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.judul.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Tambah Judul Laporan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Judul Laporan</label><input type="text" name="judul" class="form-control" required></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="aktif" id="aktifJudulAdd" checked><label class="form-check-label" for="aktifJudulAdd">Aktif</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@foreach($judul as $row)
<div class="modal fade" id="modalEditJudul{{ $row->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.judul.update', $row->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header"><h5 class="modal-title">Edit Judul Laporan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Judul Laporan</label><input type="text" name="judul" class="form-control" value="{{ $row->judul }}" required></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="aktif" id="aktifJudulEdit{{ $row->id }}" {{ $row->aktif ? 'checked' : '' }}><label class="form-check-label" for="aktifJudulEdit{{ $row->id }}">Aktif</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endpush

<style>
    .clean-card .nav-tabs .nav-link {
        border: none;
        border-bottom: 2px solid transparent;
        padding: 1rem 1.5rem;
        color: #64748b;
    }
    .clean-card .nav-tabs .nav-link.active {
        color: #0f172a;
        border-bottom: 2px solid #0d6efd;
        background: transparent;
    }
    .clean-card .nav-tabs .nav-link:hover:not(.active) {
        border-bottom: 2px solid #cbd5e1;
    }

    /* === TAB DESA / KELURAHAN ENHANCED UI === */
    .desa-filter-bar {
        background-color: var(--card-bg, #ffffff);
    }
    .desa-search-group {
        border-radius: 0.5rem;
        overflow: hidden;
    }
    .desa-search-group .form-control:focus {
        box-shadow: none;
        border-color: #cbd5e1;
    }
    .desa-control-bar {
        background-color: rgba(248, 250, 252, 0.8);
    }

    /* Minimalist Action Buttons */
    .btn-action-icon {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.375rem;
        color: #64748b;
        background-color: transparent;
        border: 1px solid #e2e8f0;
        transition: all 0.15s ease-in-out;
        font-size: 0.875rem;
    }
    .btn-action-icon:hover {
        background-color: #f8fafc;
        color: #0d6efd;
        border-color: #cbd5e1;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
    }
    .btn-action-icon.btn-action-delete:hover {
        background-color: #fef2f2;
        color: #dc2626;
        border-color: #fecaca;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(220, 38, 38, 0.1);
    }

    /* Badges Jenis (Desa vs Kelurahan) */
    .badge-jenis {
        font-size: 0.72rem;
        padding: 0.35rem 0.65rem;
        font-weight: 600;
        border-radius: 50rem;
        display: inline-flex;
        align-items: center;
        letter-spacing: 0.02em;
    }
    .badge-desa {
        background-color: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }
    .badge-kelurahan {
        background-color: #f1f5f9;
        color: #334155;
        border: 1px solid #cbd5e1;
    }

    /* Selected Row Highlight */
    tr.desa-row.row-selected td {
        background-color: rgba(13, 110, 253, 0.04) !important;
    }

    /* Pagination controls */
    .desa-pagination-bar {
        background-color: var(--card-bg, #ffffff);
    }
    #desaPaginationButtons .page-link {
        color: #475569;
        border-radius: 0.375rem !important;
        border-color: #e2e8f0;
        padding: 0.25rem 0.55rem;
        min-width: 32px;
        text-align: center;
        font-size: 0.8125rem;
        transition: all 0.15s ease;
    }
    #desaPaginationButtons .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: #ffffff;
        font-weight: 600;
    }
    #desaPaginationButtons .page-link:hover:not(.active) {
        background-color: #f1f5f9;
        color: #0f172a;
        border-color: #cbd5e1;
    }
    #desaPaginationButtons .page-item.disabled .page-link {
        color: #94a3b8;
        background-color: transparent;
        border-color: #f1f5f9;
    }

    /* Dark Mode Support */
    [data-bs-theme="dark"] .desa-filter-bar,
    [data-bs-theme="dark"] .desa-pagination-bar {
        background-color: var(--card-bg);
    }
    [data-bs-theme="dark"] .desa-control-bar {
        background-color: rgba(30, 41, 59, 0.5);
    }
    [data-bs-theme="dark"] .btn-action-icon {
        border-color: #334155;
        color: #94a3b8;
    }
    [data-bs-theme="dark"] .btn-action-icon:hover {
        background-color: #1e293b;
        color: #60a5fa;
        border-color: #475569;
    }
    [data-bs-theme="dark"] .btn-action-icon.btn-action-delete:hover {
        background-color: rgba(239, 68, 68, 0.15);
        color: #f87171;
        border-color: rgba(239, 68, 68, 0.3);
    }
    [data-bs-theme="dark"] .badge-desa {
        background-color: rgba(37, 99, 235, 0.2);
        color: #93c5fd;
        border-color: rgba(37, 99, 235, 0.3);
    }
    [data-bs-theme="dark"] .badge-kelurahan {
        background-color: rgba(148, 163, 184, 0.15);
        color: #cbd5e1;
        border-color: rgba(148, 163, 184, 0.25);
    }
    [data-bs-theme="dark"] #desaPaginationButtons .page-link {
        background-color: var(--card-bg);
        border-color: #334155;
        color: #94a3b8;
    }
    [data-bs-theme="dark"] #desaPaginationButtons .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: #ffffff;
    }
    [data-bs-theme="dark"] #desaPaginationButtons .page-link:hover:not(.active) {
        background-color: #1e293b;
        color: #f1f5f9;
        border-color: #475569;
    }
</style>

<script>
    // Global Single Delete Handler
    function handleDeleteDesa(id, name) {
        const form = document.getElementById('formDeleteDesa' + id);
        if (!form) return;

        if (window.Swal) {
            Swal.fire({
                title: 'Hapus Desa/Kelurahan?',
                text: `Apakah Anda yakin ingin menghapus "${name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="bi bi-trash3 me-1"></i> Ya, Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                background: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#1e293b' : '#ffffff',
                color: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#f1f5f9' : '#1e293b',
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        } else {
            if (confirm(`Apakah Anda yakin ingin menghapus data "${name}"?`)) {
                form.submit();
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Tab preservation logic
        const hash = window.location.hash;
        if(hash) {
            const tabButton = document.querySelector(`button[data-bs-target="${hash}"]`);
            if(tabButton) {
                const tab = new bootstrap.Tab(tabButton);
                tab.show();
            }
        }
        
        const tabs = document.querySelectorAll('button[data-bs-toggle="tab"]');
        tabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', function (event) {
                const target = event.target.getAttribute('data-bs-target');
                history.pushState(null, null, target);
            });
        });

        // === DESA TABLE MANAGER (Search, Filters, Bulk Actions, Pagination) ===
        const desaTable = document.getElementById('desaTable');
        if (!desaTable) return;

        const allRows = Array.from(document.querySelectorAll('.desa-row'));
        const searchInput = document.getElementById('desaSearchInput');
        const btnClearSearch = document.getElementById('btnDesaClearSearch');
        const filterKecamatan = document.getElementById('desaFilterKecamatan');
        const filterJenis = document.getElementById('desaFilterJenis');
        const filterAset = document.getElementById('desaFilterAset');
        const btnResetFilter = document.getElementById('btnResetDesaFilter');

        const checkAll = document.getElementById('checkAllDesa');
        const btnBulkActions = document.getElementById('btnBulkActionsDesa');
        const btnDirectBulkDelete = document.getElementById('btnDirectBulkDeleteDesa');
        const dropdownBulkDelete = document.getElementById('dropdownBulkDeleteDesa');
        const btnSelectOnlyFreeAset = document.getElementById('btnSelectOnlyFreeAset');
        const bulkBadges = document.querySelectorAll('.bulk-selected-badge');
        const bulkCountTexts = document.querySelectorAll('.bulk-selected-count-text');

        const badgeFiltered = document.getElementById('badgeFilteredDesa');
        const statFilteredCount = document.getElementById('statFilteredCount');
        const badgeSelected = document.getElementById('badgeSelectedDesa');
        const statSelectedCount = document.getElementById('statSelectedCount');

        const paginationSummary = document.getElementById('desaPaginationSummary');
        const pageIndicator = document.getElementById('desaPageIndicator');
        const paginationButtons = document.getElementById('desaPaginationButtons');
        const perPageSelect = document.getElementById('desaPerPage');
        const noResultsRow = document.getElementById('desaNoResultsRow');

        let currentPage = 1;
        let perPage = parseInt(perPageSelect.value, 10) || 25;
        let selectedIds = new Set();
        let filteredRows = [...allRows];

        // Parse dataset into memory for ultra-fast filtering
        const rowData = allRows.map(row => ({
            el: row,
            id: row.dataset.id,
            nama: (row.dataset.nama || '').toLowerCase(),
            displayName: row.querySelector('.desa-nama-cell')?.textContent?.trim() || '',
            kecamatanId: row.dataset.kecamatanId || '',
            kecamatanName: (row.dataset.kecamatanName || '').toLowerCase(),
            jenis: (row.dataset.jenis || '').toLowerCase(),
            hasAset: parseInt(row.dataset.hasAset || '0', 10),
            asetCount: parseInt(row.dataset.asetCount || '0', 10),
            checkbox: row.querySelector('.desa-row-checkbox'),
            numberCell: row.querySelector('.desa-row-number'),
        }));

        function applyFilterAndPaginate(resetPage = false) {
            if (resetPage) {
                currentPage = 1;
            }

            const query = (searchInput.value || '').trim().toLowerCase();
            const kecVal = filterKecamatan.value;
            const jenisVal = (filterJenis.value || '').toLowerCase();
            const asetVal = (filterAset?.value || '').toLowerCase();

            // Toggle clear button
            if (btnClearSearch) {
                btnClearSearch.style.display = query.length > 0 ? 'inline-block' : 'none';
            }

            // Filter logic
            filteredRows = rowData.filter(item => {
                const matchQuery = !query || item.nama.includes(query) || item.kecamatanName.includes(query);
                const matchKec = !kecVal || item.kecamatanId === kecVal;
                const matchJenis = !jenisVal || item.jenis === jenisVal;
                let matchAset = true;
                if (asetVal === 'bebas') {
                    matchAset = item.hasAset === 0;
                } else if (asetVal === 'terkait') {
                    matchAset = item.hasAset > 0;
                }
                return matchQuery && matchKec && matchJenis && matchAset;
            });

            const totalFiltered = filteredRows.length;
            const totalPages = Math.ceil(totalFiltered / perPage) || 1;

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            const startIndex = (currentPage - 1) * perPage;
            const endIndex = Math.min(startIndex + perPage, totalFiltered);

            // Hide all rows first
            rowData.forEach(item => {
                item.el.style.display = 'none';
            });

            // Show paginated rows and update sequence number
            filteredRows.slice(startIndex, endIndex).forEach((item, idx) => {
                item.el.style.display = '';
                if (item.numberCell) {
                    item.numberCell.textContent = startIndex + idx + 1;
                }
            });

            // Empty state row
            if (noResultsRow) {
                noResultsRow.style.display = totalFiltered === 0 ? '' : 'none';
            }

            // Update stats
            const isFiltered = query.length > 0 || kecVal !== '' || jenisVal !== '' || asetVal !== '';
            if (badgeFiltered) {
                badgeFiltered.style.display = isFiltered ? 'inline-flex' : 'none';
                if (statFilteredCount) statFilteredCount.textContent = totalFiltered;
            }

            // Update pagination summary
            if (paginationSummary) {
                if (totalFiltered === 0) {
                    paginationSummary.innerHTML = 'Menampilkan <strong>0</strong> data';
                } else {
                    paginationSummary.innerHTML = `Menampilkan <strong>${startIndex + 1}</strong> - <strong>${endIndex}</strong> dari <strong>${totalFiltered}</strong> data`;
                }
            }

            if (pageIndicator) {
                pageIndicator.textContent = `Page ${totalFiltered === 0 ? 0 : currentPage} of ${totalPages}`;
            }

            // Render pagination buttons
            renderPaginationButtons(totalPages);

            // Sync checkbox select-all state
            syncCheckAllState();
        }

        function renderPaginationButtons(totalPages) {
            if (!paginationButtons) return;
            paginationButtons.innerHTML = '';

            if (totalPages <= 1) return;

            // Previous button
            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
            prevLi.innerHTML = `<button class="page-link" type="button" aria-label="Previous"><i class="bi bi-chevron-left"></i></button>`;
            prevLi.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    applyFilterAndPaginate();
                }
            });
            paginationButtons.appendChild(prevLi);

            // Page numbers with windowing
            const getPageRange = () => {
                const delta = 2;
                const range = [];
                const rangeWithDots = [];
                let l;

                for (let i = 1; i <= totalPages; i++) {
                    if (i === 1 || i === totalPages || (i >= currentPage - delta && i <= currentPage + delta)) {
                        range.push(i);
                    }
                }

                for (let i of range) {
                    if (l) {
                        if (i - l === 2) {
                            rangeWithDots.push(l + 1);
                        } else if (i - l !== 1) {
                            rangeWithDots.push('...');
                        }
                    }
                    rangeWithDots.push(i);
                    l = i;
                }

                return rangeWithDots;
            };

            const pages = getPageRange();
            pages.forEach(p => {
                const li = document.createElement('li');
                if (p === '...') {
                    li.className = 'page-item disabled';
                    li.innerHTML = '<span class="page-link border-0 text-muted">...</span>';
                } else {
                    li.className = `page-item ${p === currentPage ? 'active' : ''}`;
                    li.innerHTML = `<button class="page-link" type="button">${p}</button>`;
                    li.addEventListener('click', () => {
                        currentPage = p;
                        applyFilterAndPaginate();
                    });
                }
                paginationButtons.appendChild(li);
            });

            // Next button
            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
            nextLi.innerHTML = `<button class="page-link" type="button" aria-label="Next"><i class="bi bi-chevron-right"></i></button>`;
            nextLi.addEventListener('click', () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    applyFilterAndPaginate();
                }
            });
            paginationButtons.appendChild(nextLi);
        }

        function syncCheckAllState() {
            const startIndex = (currentPage - 1) * perPage;
            const endIndex = Math.min(startIndex + perPage, filteredRows.length);
            const currentVisible = filteredRows.slice(startIndex, endIndex);

            if (currentVisible.length === 0) {
                checkAll.checked = false;
                checkAll.indeterminate = false;
                return;
            }

            const checkedCount = currentVisible.filter(item => selectedIds.has(item.id)).length;

            if (checkedCount === currentVisible.length) {
                checkAll.checked = true;
                checkAll.indeterminate = false;
            } else if (checkedCount > 0) {
                checkAll.checked = false;
                checkAll.indeterminate = true;
            } else {
                checkAll.checked = false;
                checkAll.indeterminate = false;
            }

            updateBulkControls();
        }

        function updateBulkControls() {
            const count = selectedIds.size;

            if (btnBulkActions) btnBulkActions.disabled = count === 0;
            if (btnDirectBulkDelete) btnDirectBulkDelete.disabled = count === 0;

            bulkBadges.forEach(b => {
                b.textContent = count;
                b.style.display = count > 0 ? 'inline-block' : 'none';
            });

            bulkCountTexts.forEach(t => {
                t.textContent = count;
            });

            if (badgeSelected) {
                badgeSelected.style.display = count > 0 ? 'inline-flex' : 'none';
                if (statSelectedCount) statSelectedCount.textContent = count;
            }

            // Sync individual row checkbox and class
            rowData.forEach(item => {
                const isSelected = selectedIds.has(item.id);
                if (item.checkbox) item.checkbox.checked = isSelected;
                item.el.classList.toggle('row-selected', isSelected);
            });
        }

        // Event: Check All on current page
        checkAll.addEventListener('change', function() {
            const startIndex = (currentPage - 1) * perPage;
            const endIndex = Math.min(startIndex + perPage, filteredRows.length);
            const currentVisible = filteredRows.slice(startIndex, endIndex);

            currentVisible.forEach(item => {
                if (checkAll.checked) {
                    selectedIds.add(item.id);
                } else {
                    selectedIds.delete(item.id);
                }
            });

            updateBulkControls();
        });

        // Event: Individual row checkboxes
        desaTable.addEventListener('change', function(e) {
            if (e.target && e.target.classList.contains('desa-row-checkbox')) {
                const id = e.target.value;
                if (e.target.checked) {
                    selectedIds.add(id);
                } else {
                    selectedIds.delete(id);
                }
                syncCheckAllState();
            }
        });

        // Event: Select Only Free Asset Villages (Pilih Hanya yang Bebas Aset)
        if (btnSelectOnlyFreeAset) {
            btnSelectOnlyFreeAset.addEventListener('click', function() {
                const startIndex = (currentPage - 1) * perPage;
                const endIndex = Math.min(startIndex + perPage, filteredRows.length);
                const currentVisible = filteredRows.slice(startIndex, endIndex);

                let newlySelected = 0;
                currentVisible.forEach(item => {
                    if (item.hasAset === 0) {
                        selectedIds.add(item.id);
                        newlySelected++;
                    }
                });

                updateBulkControls();
                syncCheckAllState();

                if (window.Swal) {
                    if (newlySelected > 0) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: `${newlySelected} desa bebas aset di halaman ini dipilih`,
                            showConfirmButton: false,
                            timer: 2000
                        });
                    } else {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'info',
                            title: 'Tidak ada desa bebas aset di halaman ini',
                            showConfirmButton: false,
                            timer: 2500
                        });
                    }
                }
            });
        }

        // Event: Real-time search with debounce
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                applyFilterAndPaginate(true);
            }, 120);
        });

        if (btnClearSearch) {
            btnClearSearch.addEventListener('click', function() {
                searchInput.value = '';
                applyFilterAndPaginate(true);
                searchInput.focus();
            });
        }

        // Event: Filter Kecamatan, Jenis, & Status Aset
        filterKecamatan.addEventListener('change', () => applyFilterAndPaginate(true));
        filterJenis.addEventListener('change', () => applyFilterAndPaginate(true));
        if (filterAset) {
            filterAset.addEventListener('change', () => applyFilterAndPaginate(true));
        }

        // Event: Rows per page
        perPageSelect.addEventListener('change', function() {
            perPage = parseInt(this.value, 10) || 25;
            applyFilterAndPaginate(true);
        });

        // Event: Reset Filter Button
        if (btnResetFilter) {
            btnResetFilter.addEventListener('click', function() {
                searchInput.value = '';
                filterKecamatan.value = '';
                filterJenis.value = '';
                if (filterAset) filterAset.value = '';
                applyFilterAndPaginate(true);
            });
        }

        // Event: Bulk Delete Execution dengan Aturan Proteksi Aset Ketat
        function executeBulkDelete() {
            const count = selectedIds.size;
            if (count === 0) return;

            const selectedRows = rowData.filter(r => selectedIds.has(r.id));
            const lockedRows = selectedRows.filter(r => r.hasAset > 0);
            const freeRows = selectedRows.filter(r => r.hasAset === 0);

            // KASUS 1: Semua desa yang dicentang terkait dengan aset
            if (lockedRows.length > 0 && freeRows.length === 0) {
                const contohNama = lockedRows.slice(0, 3).map(r => r.displayName).join(', ') + (lockedRows.length > 3 ? ' dll.' : '');
                if (window.Swal) {
                    Swal.fire({
                        title: 'Penghapusan Ditolak!',
                        html: `<p class="mb-2">Seluruh <strong>${lockedRows.length} desa</strong> yang Anda pilih masih memiliki data aset aktif (${contohNama}).</p>
                               <div class="alert alert-danger py-2 small mb-0">
                                   <i class="bi bi-shield-lock-fill me-1"></i> <strong>Aturan Sistem:</strong> Desa yang terkait dengan aset (tanah atau bangunan) TIDAK BOLEH dihapus. Hanya desa yang tidak terkait aset manapun yang dapat dihapus.
                               </div>`,
                        icon: 'error',
                        confirmButtonText: 'Mengerti',
                        confirmButtonColor: '#dc2626'
                    });
                } else {
                    alert(`Penghapusan ditolak: Seluruh ${lockedRows.length} desa terpilih masih memiliki data aset aktif dan diproteksi sistem.`);
                }
                return;
            }

            // KASUS 2: Campuran antara desa terkait aset dan desa bebas aset
            if (lockedRows.length > 0 && freeRows.length > 0) {
                const contohTerkait = lockedRows.slice(0, 3).map(r => r.displayName).join(', ') + (lockedRows.length > 3 ? ' dll.' : '');
                if (window.Swal) {
                    Swal.fire({
                        title: 'Proteksi Aset Terdeteksi',
                        html: `<p class="mb-2">Dari total <strong>${selectedRows.length} desa</strong> yang Anda centang:</p>
                               <ul class="text-start small mb-3">
                                   <li class="text-success fw-bold"><strong>${freeRows.length} desa</strong> BEBAS ASET (akan diproses untuk dihapus).</li>
                                   <li class="text-danger fw-semibold"><strong>${lockedRows.length} desa</strong> TERKAIT ASET (${contohTerkait}) <span class="badge bg-danger-subtle text-danger">DIPROTEKSI & DILEWATI</span>.</li>
                                </ul>
                               <p class="mb-0 text-muted small">Apakah Anda ingin melanjutkan penghapusan <strong>${freeRows.length} desa</strong> yang benar-benar bebas dari aset?</p>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc2626',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: `<i class="bi bi-trash3 me-1"></i> Ya, Hapus ${freeRows.length} Desa Bebas`,
                        cancelButtonText: 'Batal',
                        reverseButtons: true,
                        background: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#1e293b' : '#ffffff',
                        color: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#f1f5f9' : '#1e293b',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const freeIds = freeRows.map(r => r.id);
                            const idsInput = document.getElementById('bulkDeleteDesaIds');
                            const bulkForm = document.getElementById('formBulkDeleteDesa');
                            if (idsInput && bulkForm) {
                                idsInput.value = freeIds.join(',');
                                bulkForm.submit();
                            }
                        }
                    });
                } else {
                    if (confirm(`Hanya ${freeRows.length} desa bebas aset yang akan dihapus (${lockedRows.length} desa terkait aset dilewati). Lanjutkan?`)) {
                        const freeIds = freeRows.map(r => r.id);
                        const idsInput = document.getElementById('bulkDeleteDesaIds');
                        const bulkForm = document.getElementById('formBulkDeleteDesa');
                        if (idsInput && bulkForm) {
                            idsInput.value = freeIds.join(',');
                            bulkForm.submit();
                        }
                    }
                }
                return;
            }

            // KASUS 3: Seluruh desa yang dipilih adalah desa bebas aset
            const text = `Apakah Anda yakin ingin menghapus ${freeRows.length} data Desa/Kelurahan terpilih? Seluruh data ini sudah dipastikan bebas dari keterkaitan aset.`;

            if (window.Swal) {
                Swal.fire({
                    title: `Hapus ${freeRows.length} Desa Bebas Aset?`,
                    text: text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: `<i class="bi bi-trash3 me-1"></i> Ya, Hapus (${freeRows.length})`,
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    background: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#1e293b' : '#ffffff',
                    color: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#f1f5f9' : '#1e293b',
                }).then((result) => {
                    if (result.isConfirmed) {
                        const idsInput = document.getElementById('bulkDeleteDesaIds');
                        const bulkForm = document.getElementById('formBulkDeleteDesa');
                        if (idsInput && bulkForm) {
                            idsInput.value = Array.from(selectedIds).join(',');
                            bulkForm.submit();
                        }
                    }
                });
            } else {
                if (confirm(text)) {
                    const idsInput = document.getElementById('bulkDeleteDesaIds');
                    const bulkForm = document.getElementById('formBulkDeleteDesa');
                    if (idsInput && bulkForm) {
                        idsInput.value = Array.from(selectedIds).join(',');
                        bulkForm.submit();
                    }
                }
            }
        }

        if (btnDirectBulkDelete) {
            btnDirectBulkDelete.addEventListener('click', executeBulkDelete);
        }
        if (dropdownBulkDelete) {
            dropdownBulkDelete.addEventListener('click', executeBulkDelete);
        }

        // Initial setup
        applyFilterAndPaginate();
    });
</script>
@endsection
