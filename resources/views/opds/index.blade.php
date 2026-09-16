@extends('layouts.app')

@section('title', 'Data OPD')

@push('styles')
<style>
    .admin-card .table-responsive {
        min-height: 280px;
    }
    .text-wrap-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
    
    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('master-data.index') }}" class="text-decoration-none text-secondary">Master Data</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Data OPD</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-navy mb-0">Manajemen Data OPD / Instansi</h3>
        </div>
        <div class="action-toolbar d-flex flex-wrap gap-2">
            <form action="{{ route('opds.truncate') }}" method="POST" class="d-inline truncate-confirm">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-light border shadow-sm fw-medium d-flex align-items-center gap-2">
                    <i class="bi bi-trash3 text-danger"></i> Kosongkan OPD Kosong
                </button>
            </form>
            <button type="button" class="btn btn-outline-info rounded-3 shadow-sm d-flex align-items-center gap-2 d-none" id="btnConvertToSubOpdSelected">
                <i class="bi bi-diagram-2"></i> Jadikan Sub-OPD (<span id="convertCount">0</span>)
            </button>
            <button type="button" class="btn btn-outline-warning rounded-3 shadow-sm d-flex align-items-center gap-2 d-none" id="btnMergeSelected">
                <i class="bi bi-intersect"></i> Gabungkan OPD (<span id="mergeCount">0</span>)
            </button>
            <button type="button" class="btn btn-primary shadow-sm fw-medium d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addOpdModal">
                <i class="bi bi-plus-lg"></i> Tambah OPD
            </button>
        </div>
    </div>



    <!-- MAIN TABLE SECTION -->
    <x-table-card 
        :empty="$opds->isEmpty()" 
        :collection="$opds"
        emptyText="Belum ada data OPD" 
        emptyIcon="bi-building">
        
        <x-slot:filters>
            <form action="{{ route('opds.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control border-start-0 bg-white shadow-none" placeholder="Cari nama atau singkatan OPD...">
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-medium">Cari</button>
                    <a href="{{ route('opds.index') }}" class="btn btn-light border btn-sm bg-white" title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
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
                <th class="py-3 px-2 border-bottom-0 fw-semibold text-center" style="width: 55px;">
                    <div class="d-flex align-items-center justify-content-center gap-1.5">
                        <input type="checkbox" class="form-check-input mt-0" id="checkAll" title="Pilih Semua">
                        <span>NO</span>
                    </div>
                </th>
                <th class="py-3 px-3 border-bottom-0 fw-semibold" style="width: 38%;">
                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'nama', 'sort_order' => $currentSortBy === 'nama' ? $nextSortOrder : 'asc']) }}" class="text-navy text-decoration-none d-inline-flex align-items-center gap-1">
                        <span>Nama Instansi / OPD</span>
                        @if($currentSortBy === 'nama')
                            <i class="bi bi-sort-alpha-{{ $currentSortOrder === 'asc' ? 'down' : 'up' }} text-primary"></i>
                        @else
                            <i class="bi bi-arrow-down-up text-secondary opacity-50 small" style="font-size: 0.75rem;"></i>
                        @endif
                    </a>
                </th>
                <th class="py-3 px-2 border-bottom-0 fw-semibold text-center" style="width: 110px;">
                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'singkatan', 'sort_order' => $currentSortBy === 'singkatan' ? $nextSortOrder : 'asc']) }}" class="text-navy text-decoration-none d-inline-flex align-items-center gap-1">
                        <span>Singkatan</span>
                        @if($currentSortBy === 'singkatan')
                            <i class="bi bi-sort-alpha-{{ $currentSortOrder === 'asc' ? 'down' : 'up' }} text-primary"></i>
                        @else
                            <i class="bi bi-arrow-down-up text-secondary opacity-50 small" style="font-size: 0.75rem;"></i>
                        @endif
                    </a>
                </th>
                <th class="py-3 px-2 border-bottom-0 fw-semibold text-center" style="width: 200px;">Ringkasan Aset</th>
                <th class="py-3 px-3 border-bottom-0 fw-semibold" style="width: 230px;">Akun Admin</th>
                <th class="py-3 px-3 border-bottom-0 fw-semibold text-center" style="width: 100px;">Aksi</th>
            </tr>
        </x-slot:thead>

        @foreach($opds as $index => $opd)
            <tr>
                <td class="px-2 py-2.5 text-center">
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <input type="checkbox" class="form-check-input merge-check mt-0" 
                               value="{{ $opd->id }}" 
                               data-name="{{ $opd->nama }}" 
                               data-singkatan="{{ $opd->singkatan ?? '' }}"
                               data-count="{{ ($opd->vehicles_count ?? 0) + ($opd->ebmd_vehicles_count ?? 0) + ($opd->aset_tanahs_count ?? 0) + ($opd->bangunans_count ?? 0) }}"
                               data-real="{{ $opd->vehicles_count ?? 0 }}"
                               data-ebmd="{{ $opd->ebmd_vehicles_count ?? 0 }}"
                               data-tanah="{{ $opd->aset_tanahs_count ?? 0 }}"
                               data-bangunan="{{ $opd->bangunans_count ?? 0 }}"
                               data-subopd="{{ $opd->sub_opds_count ?? 0 }}">
                        <span class="text-secondary small font-monospace fw-medium">{{ ($opds->currentPage() - 1) * $opds->perPage() + $loop->iteration }}</span>
                    </div>
                </td>
                <td class="py-2.5 px-3">
                    <div class="fw-bold text-navy" title="{{ $opd->nama }}" style="line-height: 1.35; font-size: 0.88rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        {{ $opd->nama }}
                    </div>
                </td>
                <td class="py-2.5 px-2 text-center">
                    @if($opd->singkatan && $opd->singkatan !== '-')
                        <span class="badge bg-light text-primary border border-primary border-opacity-25 px-2 py-1 font-monospace fw-semibold" style="font-size: 0.75rem;">{{ $opd->singkatan }}</span>
                    @else
                        <span class="text-secondary small px-2">-</span>
                    @endif
                </td>
                <td class="py-2.5 px-2 text-center">
                    <div class="d-flex justify-content-center align-items-center gap-1 flex-wrap">
                        @if(($opd->aset_tanahs_count ?? 0) > 0)
                            <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25 px-1.5 py-0.5 rounded-pill fw-medium" style="font-size: 0.72rem;" title="Aset Tanah (SIPAT)">
                                <i class="bi bi-geo-alt-fill me-0.5" style="font-size: 0.65rem;"></i>{{ $opd->aset_tanahs_count }}
                            </span>
                        @endif
                        @if(($opd->bangunans_count ?? 0) > 0)
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-1.5 py-0.5 rounded-pill fw-medium" style="font-size: 0.72rem;" title="Aset Bangunan / Gedung">
                                <i class="bi bi-building me-0.5" style="font-size: 0.65rem;"></i>{{ $opd->bangunans_count }}
                            </span>
                        @endif
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-1.5 py-0.5 rounded-pill fw-medium" style="font-size: 0.72rem;" title="Data Kendaraan Real">
                            <i class="bi bi-car-front-fill me-0.5" style="font-size: 0.65rem;"></i>{{ $opd->vehicles_count ?? 0 }}
                        </span>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-1.5 py-0.5 rounded-pill fw-medium" style="font-size: 0.72rem;" title="Data Kendaraan e-BMD">
                            <i class="bi bi-file-earmark-spreadsheet me-0.5" style="font-size: 0.65rem;"></i>{{ $opd->ebmd_vehicles_count ?? 0 }}
                        </span>
                        @if(($opd->sub_opds_count ?? 0) > 0)
                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-1.5 py-0.5 rounded-pill fw-medium" style="font-size: 0.72rem;" title="Sub-OPD / Kuasa Pengguna Barang (KPB)">
                                <i class="bi bi-diagram-3 me-0.5" style="font-size: 0.65rem;"></i>{{ $opd->sub_opds_count }}
                            </span>
                        @endif
                    </div>
                </td>
                <td class="py-2.5 px-3">
                    @if($opd->user)
                        <div class="d-inline-block text-truncate fw-medium text-dark small" style="max-width: 190px; vertical-align: middle;" title="{{ $opd->user->email }}">
                            {{ $opd->user->email }}
                        </div>
                        <div class="mt-0.5">
                            <span class="badge bg-success-subtle text-success border-0 px-1.5 py-0.5 d-inline-flex align-items-center gap-1" style="font-size: 0.65rem;">
                                <span style="font-size: 0.45rem;">●</span> AKTIF
                            </span>
                        </div>
                    @else
                        <span class="text-secondary small fst-italic" style="font-size: 0.75rem;">Tidak ada akun</span>
                    @endif
                </td>
                <td class="px-3 py-2.5 text-center">
                    <div class="d-flex justify-content-center align-items-center gap-1">
                        <button type="button" class="btn btn-sm btn-light border shadow-none text-primary px-2 py-1 rounded-2" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editOpdModal"
                                data-id="{{ $opd->id }}"
                                data-nama="{{ $opd->nama }}"
                                data-singkatan="{{ $opd->singkatan }}"
                                data-alamat="{{ $opd->alamat }}"
                                title="Edit Data OPD">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light border shadow-none px-2 py-1 rounded-2 text-secondary" 
                                    type="button" 
                                    data-bs-toggle="dropdown" 
                                    aria-expanded="false" 
                                    title="Menu Aksi">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-1" style="font-size: 0.85rem; min-width: 170px;">
                                <li>
                                    <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-1.5 text-secondary btn-row-convert-subopd" 
                                            data-id="{{ $opd->id }}"
                                            data-nama="{{ $opd->nama }}"
                                            data-singkatan="{{ $opd->singkatan }}"
                                            data-real="{{ $opd->vehicles_count ?? 0 }}"
                                            data-ebmd="{{ $opd->ebmd_vehicles_count ?? 0 }}"
                                            data-tanah="{{ $opd->aset_tanahs_count ?? 0 }}"
                                            data-bangunan="{{ $opd->bangunans_count ?? 0 }}"
                                            data-subopd="{{ $opd->sub_opds_count ?? 0 }}">
                                        <i class="bi bi-diagram-2 text-info"></i> Jadikan Sub-OPD
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                    <form action="{{ route('opds.destroy', $opd) }}" method="POST" class="delete-confirm">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger d-flex align-items-center gap-2 py-1.5">
                                            <i class="bi bi-trash3 text-danger"></i> Hapus OPD
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach

        <x-slot:pagination>
            {{ $opds->links() }}
        </x-slot:pagination>
    </x-table-card>

</div>

@push('modals')
    <!-- ADD MODAL -->
    <x-modal id="addOpdModal" title="Tambah OPD / Instansi Baru" size="md" submitLabel="Simpan Data" form="addOpdForm">
        <form id="addOpdForm" action="{{ route('opds.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold text-dark small">Nama Instansi / OPD</label>
                <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" placeholder="Masukkan nama lengkap OPD" value="{{ old('nama') }}" required>
                @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold text-dark small">Singkatan</label>
                <input type="text" name="singkatan" class="form-control" placeholder="Contoh: BAPPEDA" value="{{ old('singkatan') }}">
            </div>
            <div class="mb-0">
                <label class="form-label fw-semibold text-dark small">Alamat Kantor</label>
                <textarea name="alamat" class="form-control" rows="3" placeholder="Jl. Jalur Dua No. 1...">{{ old('alamat') }}</textarea>
            </div>
        </form>
    </x-modal>

    <!-- EDIT MODAL -->
    <x-modal id="editOpdModal" title="Edit Data OPD" size="md" submitLabel="Simpan Perubahan" form="editOpdForm">
        <form id="editOpdForm" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold text-dark small">Nama Instansi / OPD</label>
                <input type="text" name="nama" id="edit_nama" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold text-dark small">Singkatan</label>
                <input type="text" name="singkatan" id="edit_singkatan" class="form-control">
            </div>
            <div class="mb-0">
                <label class="form-label fw-semibold text-dark small">Alamat Kantor</label>
                <textarea name="alamat" id="edit_alamat" class="form-control" rows="3"></textarea>
            </div>
        </form>
    </x-modal>
    <!-- MERGE OPD MODAL -->
    <div class="modal fade" id="mergeOpdModal" tabindex="-1" aria-labelledby="mergeOpdModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom px-4 py-3 bg-light rounded-top-4">
                    <h5 class="modal-title fw-bold text-navy d-flex align-items-center gap-2" id="mergeOpdModalLabel">
                        <i class="bi bi-intersect text-warning"></i> Gabungkan Instansi OPD
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('opds.merge') }}" method="POST" id="mergeForm">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="alert alert-warning border-0 bg-warning bg-opacity-10 d-flex align-items-start mb-4 rounded-3">
                            <div class="fs-4 me-3 text-warning"><i class="bi bi-exclamation-triangle-fill"></i></div>
                            <div>
                                <h6 class="fw-bold mb-1 text-warning-emphasis" style="font-size: 0.9rem;">Perhatian — Aksi Peleburan Instansi Permanen</h6>
                                <p class="mb-0 small text-secondary">
                                    Seluruh aset fisik (Tanah, Bangunan, Kendaraan Real & e-BMD), dokumen e-Label, unit kerja Sub-OPD (KPB), akun admin, serta riwayat dari OPD yang dipilih akan <strong>dipindahkan</strong> ke OPD tujuan, lalu OPD sumber akan <strong>dihapus</strong> secara aman.
                                </p>
                            </div>
                        </div>

                        <!-- Daftar OPD yang akan di-merge -->
                        <h6 class="fw-bold text-navy mb-2"><i class="bi bi-list-check me-1"></i> OPD yang Akan Digabungkan:</h6>
                        <div class="border rounded-3 p-3 mb-4 bg-light" id="mergeSourceList" style="max-height: 200px; overflow-y: auto;">
                            <!-- Diisi via JavaScript -->
                        </div>

                        <!-- Pilih OPD tujuan -->
                        <div class="mb-2">
                            <label class="form-label fw-bold text-navy"><i class="bi bi-bullseye me-1"></i> Pilih OPD Tujuan (Induk Utama) <span class="text-danger">*</span></label>
                            <select name="target_id" id="mergeTargetSelect" class="form-select form-select-lg shadow-none" required>
                                <option value="">-- Pilih OPD Tujuan --</option>
                            </select>
                            <div class="form-text small text-secondary mt-1">
                                <i class="bi bi-info-circle me-1"></i> Semua aset fisik (Tanah, Bangunan, Kendaraan) dan Sub-OPD akan dialihkan ke instansi yang dipilih di atas.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light px-4 py-3 rounded-bottom-4">
                        <button type="button" class="btn btn-light border fw-medium" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning fw-bold px-4 d-flex align-items-center gap-2" id="mergeSubmitBtn">
                            <i class="bi bi-intersect"></i> Gabungkan Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- CONVERT TO SUB-OPD MODAL -->
    <div class="modal fade" id="convertToSubOpdModal" tabindex="-1" aria-labelledby="convertToSubOpdModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom px-4 py-3 bg-light rounded-top-4">
                    <h5 class="modal-title fw-bold text-navy d-flex align-items-center gap-2" id="convertToSubOpdModalLabel">
                        <i class="bi bi-diagram-2 text-info"></i> Jadikan Sub-OPD (Kuasa Pengguna Barang)
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('opds.convert-to-sub-opd') }}" method="POST" id="convertToSubOpdForm">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="alert alert-info border-0 bg-info bg-opacity-10 d-flex align-items-start mb-4 rounded-3">
                            <div class="fs-4 me-3 text-info"><i class="bi bi-info-circle-fill"></i></div>
                            <div>
                                <h6 class="fw-bold mb-1 text-info-emphasis" style="font-size: 0.9rem;">Penyelarasan Sub-Unit / Kuasa Pengguna Barang (KPB)</h6>
                                <p class="mb-0 small text-secondary">
                                    Gunakan fitur ini jika OPD yang dipilih sebenarnya merupakan <strong>Sub-Unit / Kuasa Pengguna Barang (KPB)</strong> (seperti Puskesmas, Bagian, UPTD, atau Sekolah) yang berinduk di bawah dinas lain.
                                    Seluruh aset (Tanah, Bangunan, Kendaraan Real & e-BMD) akan dialokasikan ke Sub-OPD ini di bawah OPD Induk yang dipilih, dan akun pengguna akan disesuaikan menjadi Operator KPB.
                                </p>
                            </div>
                        </div>

                        <!-- Daftar OPD yang akan dijadikan Sub-OPD -->
                        <h6 class="fw-bold text-navy mb-2"><i class="bi bi-list-check me-1"></i> OPD yang Akan Dijadikan Sub-OPD:</h6>
                        <div class="border rounded-3 p-3 mb-4 bg-light" id="convertSourceList" style="max-height: 180px; overflow-y: auto;">
                            <!-- Diisi via JavaScript -->
                        </div>

                        <!-- Pilih OPD Induk -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-navy"><i class="bi bi-building me-1"></i> Pilih OPD Induk (Pengguna Barang) <span class="text-danger">*</span></label>
                            <select name="parent_opd_id" id="convertParentSelect" class="form-select form-select-lg shadow-none" required>
                                <option value="">-- Pilih OPD Induk Tujuan --</option>
                            </select>
                            <div class="form-text small text-secondary mt-1">
                                Seluruh aset (Tanah, Bangunan, Kendaraan) dan akun admin akan dialihkan di bawah naungan dinas induk ini.
                            </div>
                        </div>

                        <!-- Jenis Sub-OPD -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-navy"><i class="bi bi-tag me-1"></i> Jenis Sub-OPD</label>
                            <select name="jenis" id="convertJenisSelect" class="form-select shadow-none">
                                <option value="puskesmas">Puskesmas / Fasilitas Pelayanan Kesehatan</option>
                                <option value="uptd">UPTD / Balai Teknis Daerah</option>
                                <option value="bagian">Bagian / Bidang / Sekretariat</option>
                                <option value="sekolah">Sekolah / Satuan Pendidikan (SMP/SD/TK)</option>
                                <option value="rsud">RSUD / Rumah Sakit Daerah</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                            <div class="form-text small text-secondary">
                                Klasifikasi unit kerja untuk kemudahan pengelompokan dan penyaringan data laporan.
                            </div>
                        </div>

                        <!-- Opsi Khusus Single OPD Selection -->
                        <div id="singleOpdExtraFields" class="d-none">
                            <div class="card border bg-light rounded-3 p-3">
                                <h6 class="fw-bold text-navy mb-2 small"><i class="bi bi-pencil-square me-1"></i> Penyesuaian Identitas Sub-OPD Baru (Opsional):</h6>
                                <div class="row g-2">
                                    <div class="col-md-8">
                                        <label class="form-label small fw-medium text-secondary mb-1">Nama Sub-OPD</label>
                                        <input type="text" name="nama" id="convertCustomNama" class="form-control form-control-sm shadow-none" placeholder="Nama Sub-OPD...">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-medium text-secondary mb-1">Kode Sub (Singkatan)</label>
                                        <input type="text" name="kode_sub" id="convertCustomKodeSub" class="form-control form-control-sm shadow-none" placeholder="Contoh: PKM-BNW">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light px-4 py-3 rounded-bottom-4">
                        <button type="button" class="btn btn-light border fw-medium" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-info text-white fw-bold px-4 d-flex align-items-center gap-2" id="convertSubmitBtn">
                            <i class="bi bi-diagram-2"></i> Jadikan Sub-OPD Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Data seluruh OPD untuk dropdown selector
        const allOpdsData = @json($allOpds ?? []);

        // Notifikasi Akun Baru
        @if(session('new_account'))
            Swal.fire({
                title: 'Akun Admin OPD Dibuat!',
                html: `
                    <div class="text-start p-3 bg-light rounded-3 border">
                        <div class="mb-2"><strong>OPD:</strong> {{ session('new_account')['opd_nama'] }}</div>
                        <div class="mb-2"><strong>Email/User:</strong> <code class="bg-white px-2 py-1 border rounded">{{ session('new_account')['email'] }}</code></div>
                        <div class="mb-0"><strong>Password:</strong> <code class="bg-white px-2 py-1 border rounded">{{ session('new_account')['password'] }}</code></div>
                    </div>
                    <div class="alert alert-warning mt-3 small mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Harap catat password ini. Password hanya akan ditampilkan satu kali demi keamanan.
                    </div>
                `,
                icon: 'success',
                confirmButtonText: 'Saya Sudah Mencatatnya',
                confirmButtonColor: '#1e40af',
                allowOutsideClick: false
            });
        @endif

        // Modal Edit OPD
        const editModal = document.getElementById('editOpdModal');
        if (editModal) {
            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const nama = button.getAttribute('data-nama');
                const singkatan = button.getAttribute('data-singkatan');
                const alamat = button.getAttribute('data-alamat');

                const form = document.getElementById('editOpdForm');
                const routeTemplate = "{{ route('opds.update', ':id') }}";
                form.action = routeTemplate.replace(':id', id);

                document.getElementById('edit_nama').value = nama || '';
                document.getElementById('edit_singkatan').value = (singkatan && singkatan !== '-') ? singkatan : '';
                document.getElementById('edit_alamat').value = (alamat && alamat !== '-') ? alamat : '';
            });
        }

        // Truncate Confirmation
        const truncateForm = document.querySelector('.truncate-confirm');
        if (truncateForm) {
            truncateForm.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Kosongkan OPD Tanpa Kendaraan?',
                    text: "Seluruh data instansi (dan akun adminnya) yang tidak memiliki kendaraan terdaftar akan dihapus permanen. Lanjutkan?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Kosongkan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        }

        // === CHECKBOX MULTI-SELECT LOGIC ===
        const checkAll = document.getElementById('checkAll');
        const mergeChecks = document.querySelectorAll('.merge-check');
        const btnMerge = document.getElementById('btnMergeSelected');
        const btnConvert = document.getElementById('btnConvertToSubOpdSelected');
        const mergeCount = document.getElementById('mergeCount');
        const convertCount = document.getElementById('convertCount');

        function updateActionButtons() {
            const checked = document.querySelectorAll('.merge-check:checked');
            const count = checked.length;
            if (mergeCount) mergeCount.textContent = count;
            if (convertCount) convertCount.textContent = count;

            // Tombol Gabungkan OPD (minimal 2 OPD dipilih)
            if (btnMerge) {
                if (count >= 2) {
                    btnMerge.classList.remove('d-none');
                } else {
                    btnMerge.classList.add('d-none');
                }
            }

            // Tombol Jadikan Sub-OPD (minimal 1 OPD dipilih)
            if (btnConvert) {
                if (count >= 1) {
                    btnConvert.classList.remove('d-none');
                } else {
                    btnConvert.classList.add('d-none');
                }
            }
        }

        if (checkAll) {
            checkAll.addEventListener('change', function () {
                mergeChecks.forEach(cb => cb.checked = this.checked);
                updateActionButtons();
            });
        }

        mergeChecks.forEach(cb => {
            cb.addEventListener('change', updateActionButtons);
        });

        // === FUNGSI HELPER AUTO-DETECT JENIS SUB-OPD ===
        function detectJenisSubOpd(name) {
            const lower = (name || '').toLowerCase();
            if (lower.includes('puskesmas') || lower.includes('pkm')) return 'puskesmas';
            if (lower.includes('rsud') || lower.includes('rumah sakit')) return 'rsud';
            if (lower.includes('uptd') || lower.includes('balai')) return 'uptd';
            if (lower.includes('bagian') || lower.includes('sekretariat') || lower.includes('bidang')) return 'bagian';
            if (lower.includes('smp') || lower.includes('sd ') || lower.includes('sekolah') || lower.includes('tk ')) return 'sekolah';
            return 'uptd';
        }

        // === MODAL JADIKAN SUB-OPD (BULK & SINGLE) ===
        function openConvertToSubOpdModal(items) {
            if (!items || items.length === 0) return;

            const sourceList = document.getElementById('convertSourceList');
            const parentSelect = document.getElementById('convertParentSelect');
            const jenisSelect = document.getElementById('convertJenisSelect');
            const singleExtra = document.getElementById('singleOpdExtraFields');
            const customNama = document.getElementById('convertCustomNama');
            const customKode = document.getElementById('convertCustomKodeSub');
            const form = document.getElementById('convertToSubOpdForm');

            // Hapus hidden input lama
            form.querySelectorAll('input[name="source_ids[]"]').forEach(el => el.remove());

            // Render list sumber
            sourceList.innerHTML = '';
            const sourceIds = items.map(it => parseInt(it.id));

            items.forEach(it => {
                let details = [];
                if (parseInt(it.tanah) > 0) details.push(`<span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25 rounded-pill px-2">${it.tanah} Tanah</span>`);
                if (parseInt(it.bangunan) > 0) details.push(`<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2">${it.bangunan} Gedung</span>`);
                if (parseInt(it.real) > 0) details.push(`<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2">${it.real} Real</span>`);
                if (parseInt(it.ebmd) > 0) details.push(`<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2">${it.ebmd} e-BMD</span>`);
                if (parseInt(it.subopd) > 0) details.push(`<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2">${it.subopd} KPB</span>`);
                if (details.length === 0) details.push(`<span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill px-2">Kosong</span>`);

                sourceList.innerHTML += `
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-light">
                        <span class="fw-semibold text-dark"><i class="bi bi-building text-secondary me-1"></i> ${it.nama}</span>
                        <div class="d-flex gap-1">${details.join(' ')}</div>
                    </div>
                `;

                // Hidden input
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'source_ids[]';
                hidden.value = it.id;
                form.appendChild(hidden);
            });

            // Bangun opsi Parent OPD (kecualikan OPD sumber)
            parentSelect.innerHTML = '<option value="">-- Pilih OPD Induk Tujuan --</option>';
            allOpdsData.forEach(opd => {
                if (!sourceIds.includes(parseInt(opd.id))) {
                    const opt = document.createElement('option');
                    opt.value = opd.id;
                    opt.textContent = `${opd.nama}${opd.singkatan ? ' (' + opd.singkatan + ')' : ''}`;
                    parentSelect.appendChild(opt);
                }
            });

            // Tangani Single vs Multiple
            if (items.length === 1) {
                singleExtra.classList.remove('d-none');
                customNama.value = items[0].nama || '';
                customKode.value = (items[0].singkatan && items[0].singkatan !== '-') ? items[0].singkatan : '';
                jenisSelect.value = detectJenisSubOpd(items[0].nama);
            } else {
                singleExtra.classList.add('d-none');
                customNama.value = '';
                customKode.value = '';
                jenisSelect.value = detectJenisSubOpd(items[0].nama);
            }

            const modal = new bootstrap.Modal(document.getElementById('convertToSubOpdModal'));
            modal.show();
        }

        // Trigger dari Toolbar: Jadikan Sub-OPD
        if (btnConvert) {
            btnConvert.addEventListener('click', function () {
                const checked = document.querySelectorAll('.merge-check:checked');
                const items = [];
                checked.forEach(cb => {
                    items.push({
                        id: cb.value,
                        nama: cb.getAttribute('data-name'),
                        singkatan: cb.getAttribute('data-singkatan') || '',
                        real: cb.getAttribute('data-real') || 0,
                        ebmd: cb.getAttribute('data-ebmd') || 0,
                        tanah: cb.getAttribute('data-tanah') || 0,
                        bangunan: cb.getAttribute('data-bangunan') || 0,
                        subopd: cb.getAttribute('data-subopd') || 0,
                    });
                });
                openConvertToSubOpdModal(items);
            });
        }

        // Trigger dari Baris Tabel: Tombol Jadikan Sub-OPD
        document.querySelectorAll('.btn-row-convert-subopd').forEach(btn => {
            btn.addEventListener('click', function () {
                const item = {
                    id: this.getAttribute('data-id'),
                    nama: this.getAttribute('data-nama'),
                    singkatan: this.getAttribute('data-singkatan') || '',
                    real: this.getAttribute('data-real') || 0,
                    ebmd: this.getAttribute('data-ebmd') || 0,
                    tanah: this.getAttribute('data-tanah') || 0,
                    bangunan: this.getAttribute('data-bangunan') || 0,
                    subopd: this.getAttribute('data-subopd') || 0,
                };
                openConvertToSubOpdModal([item]);
            });
        });

        // Konfirmasi Form Jadikan Sub-OPD
        const convertForm = document.getElementById('convertToSubOpdForm');
        if (convertForm) {
            convertForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const parentSelect = document.getElementById('convertParentSelect');
                const parentName = parentSelect.options[parentSelect.selectedIndex]?.text || 'OPD Induk Terpilih';

                Swal.fire({
                    title: 'Jadikan Sub-OPD?',
                    html: `Instansi yang dipilih akan dikonversi menjadi <strong>Sub-OPD (Kuasa Pengguna Barang)</strong> di bawah naungan <strong>${parentName}</strong>.<br><br>Seluruh kendaraan fisik (Real) dan e-BMD akan dialihkan ke unit ini. Lanjutkan?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0ea5e9',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Konversi Sekarang!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        }

        // === MERGE MODAL OPEN & SUBMIT ===
        if (btnMerge) {
            btnMerge.addEventListener('click', function () {
                const checked = document.querySelectorAll('.merge-check:checked');
                const sourceList = document.getElementById('mergeSourceList');
                const targetSelect = document.getElementById('mergeTargetSelect');
                const form = document.getElementById('mergeForm');

                // Hapus hidden inputs lama
                form.querySelectorAll('input[name="source_ids[]"]').forEach(el => el.remove());

                // Bangun daftar sumber & opsi target
                sourceList.innerHTML = '';
                targetSelect.innerHTML = '<option value="">-- Pilih OPD Tujuan (Induk Utama) --</option>';

                const checkedIds = [];
                checked.forEach(cb => {
                    const name = cb.getAttribute('data-name');
                    const real = cb.getAttribute('data-real') || 0;
                    const ebmd = cb.getAttribute('data-ebmd') || 0;
                    const tanah = cb.getAttribute('data-tanah') || 0;
                    const bangunan = cb.getAttribute('data-bangunan') || 0;
                    const subopd = cb.getAttribute('data-subopd') || 0;
                    const id = cb.value;
                    checkedIds.push(parseInt(id));

                    // Tampilkan di daftar sumber
                    let details = [];
                    if (parseInt(tanah) > 0) details.push(`<span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25 rounded-pill px-2">${tanah} Tanah</span>`);
                    if (parseInt(bangunan) > 0) details.push(`<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2">${bangunan} Gedung</span>`);
                    if (parseInt(real) > 0) details.push(`<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2">${real} Real</span>`);
                    if (parseInt(ebmd) > 0) details.push(`<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2">${ebmd} e-BMD</span>`);
                    if (parseInt(subopd) > 0) details.push(`<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2">${subopd} KPB</span>`);
                    if (details.length === 0) details.push(`<span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill px-2">Kosong</span>`);

                    sourceList.innerHTML += `
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-light">
                            <span class="fw-semibold text-dark"><i class="bi bi-building text-secondary me-1"></i> ${name}</span>
                            <div class="d-flex gap-1">${details.join(' ')}</div>
                        </div>
                    `;

                    // Tambah hidden input source_ids
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'source_ids[]';
                    hidden.value = id;
                    form.appendChild(hidden);
                });

                // Tampilkan OPD terpilih di atas
                const optGroupChecked = document.createElement('optgroup');
                optGroupChecked.label = '— Dari OPD yang Terpilih —';
                checked.forEach(cb => {
                    const opt = document.createElement('option');
                    opt.value = cb.value;
                    opt.textContent = `${cb.getAttribute('data-name')} (${cb.getAttribute('data-real') || 0} Real / ${cb.getAttribute('data-ebmd') || 0} e-BMD)`;
                    optGroupChecked.appendChild(opt);
                });
                targetSelect.appendChild(optGroupChecked);

                // Tambahkan OPD lainnya yang tidak terpilih
                const optGroupOther = document.createElement('optgroup');
                optGroupOther.label = '— OPD Lainnya di Sistem —';
                allOpdsData.forEach(opd => {
                    if (!checkedIds.includes(parseInt(opd.id))) {
                        const opt = document.createElement('option');
                        opt.value = opd.id;
                        opt.textContent = `${opd.nama}${opd.singkatan ? ' (' + opd.singkatan + ')' : ''}`;
                        optGroupOther.appendChild(opt);
                    }
                });
                targetSelect.appendChild(optGroupOther);

                // Buka modal
                const modal = new bootstrap.Modal(document.getElementById('mergeOpdModal'));
                modal.show();
            });
        }

        // Konfirmasi Form Merge OPD
        const mergeForm = document.getElementById('mergeForm');
        if (mergeForm) {
            mergeForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const targetSelect = document.getElementById('mergeTargetSelect');
                const targetName = targetSelect.options[targetSelect.selectedIndex]?.text || 'OPD Tujuan';

                Swal.fire({
                    title: 'Peleburan Instansi Permanen?',
                    html: `Seluruh aset fisik (Tanah, Bangunan, Kendaraan) dan akun dari OPD sumber akan disatukan ke dalam <strong>${targetName}</strong>, lalu data OPD sumber akan dihapus secara aman. Lanjutkan?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f59e0b',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Gabungkan Sekarang!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        }
    });
</script>
@endpush
@endsection
