@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Master Status & Kategori Proses BPN</h4>
            <p class="text-body-secondary small mb-0">Kelola status proses BPN, pengelompokan kategori dinamis, dan saklar pengecualian target pensertifikatan.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary rounded-3 d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">
                <i class="bi bi-folder-plus"></i> Tambah Kategori
            </button>
            <button type="button" class="btn btn-primary rounded-3 d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahStatus">
                <i class="bi bi-plus-lg"></i> Tambah Status Proses
            </button>
        </div>
    </div>

    <!-- Alert Notifikasi -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill text-success fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Nav Pills / Tabs -->
    <ul class="nav nav-pills gap-2 mb-4 bg-body-tertiary p-1.5 rounded-4 d-inline-flex border" id="masterTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-3 fw-semibold px-3.5 py-2 {{ request('tab') !== 'kategori' ? 'active shadow-sm' : '' }}" id="tab-status-proses" data-bs-toggle="pill" data-bs-target="#content-status-proses" type="button" role="tab" aria-controls="content-status-proses" aria-selected="{{ request('tab') !== 'kategori' ? 'true' : 'false' }}">
                <i class="bi bi-diagram-3-fill me-1.5"></i> Status Proses BPN
                <span class="badge {{ request('tab') !== 'kategori' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-body' }} rounded-pill ms-1">{{ $counts['total'] ?? 0 }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-3 fw-semibold px-3.5 py-2 {{ request('tab') === 'kategori' ? 'active shadow-sm' : '' }}" id="tab-kategori-proses" data-bs-toggle="pill" data-bs-target="#content-kategori-proses" type="button" role="tab" aria-controls="content-kategori-proses" aria-selected="{{ request('tab') === 'kategori' ? 'true' : 'false' }}">
                <i class="bi bi-tags-fill me-1.5"></i> Master Kategori Proses
                <span class="badge {{ request('tab') === 'kategori' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-body' }} rounded-pill ms-1">{{ count($kategoriList) }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="masterTabsContent">
        <!-- ========================================== -->
        <!-- TAB 1: STATUS PROSES BPN                   -->
        <!-- ========================================== -->
        <div class="tab-pane fade {{ request('tab') !== 'kategori' ? 'show active' : '' }}" id="content-status-proses" role="tabpanel" aria-labelledby="tab-status-proses">
            <!-- Category Filter Pills -->
            <div class="d-flex align-items-center gap-2 mb-4 overflow-x-auto pb-1">
                <a href="{{ route('status-proses.index') }}" class="btn btn-sm rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 {{ !request('kategori') ? 'btn-primary shadow-sm' : 'btn-outline-secondary bg-body' }}">
                    <i class="bi bi-layers-fill"></i> Semua Status
                    <span class="badge {{ !request('kategori') ? 'bg-white text-primary' : 'bg-secondary-subtle text-body' }} rounded-pill ms-1">{{ $counts['total'] ?? 0 }}</span>
                </a>
                @foreach($kategoriList as $kat)
                    @php
                        $isSelected = request('kategori') === $kat->kode;
                        $badgeBg = $isSelected ? 'bg-white text-primary' : 'bg-secondary-subtle text-body';
                    @endphp
                    <a href="{{ route('status-proses.index', ['kategori' => $kat->kode]) }}" class="btn btn-sm rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 {{ $isSelected ? 'btn-primary shadow-sm' : 'btn-outline-secondary bg-body' }}">
                        <i class="bi bi-tag-fill"></i> {{ $kat->nama }}
                        <span class="badge {{ $badgeBg }} rounded-pill ms-1">{{ $kat->status_proses_count ?? 0 }}</span>
                    </a>
                @endforeach
            </div>

            <!-- Data Table Status Proses Card -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-body-tertiary text-body-secondary small fw-semibold">
                            <tr>
                                <th class="ps-4 py-3" style="width: 70px;">URUTAN</th>
                                <th class="py-3">NAMA STATUS PROSES BPN</th>
                                <th class="py-3">KATEGORI PENGELOMPOKAN (DINAMIS)</th>
                                <th class="py-3">WARNA LABEL</th>
                                <th class="text-center py-3 pe-4" style="width: 120px;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($statusProses as $status)
                                @php
                                    $w = strtolower(trim($status->warna ?? 'primary'));
                                    if (empty($w)) { $w = 'primary'; }

                                    $colorConfigs = [
                                        'primary'   => ['label' => 'Primary (Biru)', 'badge' => 'bg-primary-subtle text-primary-emphasis border-primary-subtle', 'dot' => '#0d6efd'],
                                        'secondary' => ['label' => 'Secondary (Abu-abu)', 'badge' => 'bg-secondary-subtle text-secondary-emphasis border-secondary-subtle', 'dot' => '#6c757d'],
                                        'success'   => ['label' => 'Success (Hijau)', 'badge' => 'bg-success-subtle text-success-emphasis border-success-subtle', 'dot' => '#198754'],
                                        'danger'    => ['label' => 'Danger (Merah)', 'badge' => 'bg-danger-subtle text-danger-emphasis border-danger-subtle', 'dot' => '#dc3545'],
                                        'warning'   => ['label' => 'Warning (Kuning)', 'badge' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle', 'dot' => '#ffc107'],
                                        'info'      => ['label' => 'Info (Biru Muda)', 'badge' => 'bg-info-subtle text-info-emphasis border-info-subtle', 'dot' => '#0dcaf0'],
                                        'dark'      => ['label' => 'Dark (Gelap)', 'badge' => 'bg-dark-subtle text-dark-emphasis border-dark-subtle', 'dot' => '#212529'],
                                    ];

                                    $cfg = $colorConfigs[$w] ?? [
                                        'label' => strtoupper($w),
                                        'badge' => 'bg-body-secondary text-body border-body-subtle',
                                        'dot' => (str_starts_with($w, '#') ? $w : '#0d6efd')
                                    ];

                                    // Ambil relasi dinamis atau fallback kategori string
                                    $kats = $status->kategoriProses;
                                @endphp
                                <tr>
                                    <td class="ps-4 font-monospace text-secondary fw-bold">
                                        #{{ $status->urutan ?? 0 }}
                                    </td>
                                    <td>
                                        <div class="fw-bold text-body fs-6">{{ $status->nama_status }}</div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1.5">
                                            @forelse($kats as $kp)
                                                <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle px-2 py-1 small rounded-pill fw-semibold">
                                                    <i class="bi bi-tag-fill me-1"></i> {{ $kp->nama }}
                                                    @if($kp->exclude_target)
                                                        <i class="bi bi-shield-slash text-danger ms-1" title="Kategori ini mengecualikan target"></i>
                                                    @endif
                                                </span>
                                            @empty
                                                @forelse($status->categories as $fallbackCat)
                                                    <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle px-2 py-1 small rounded-pill fw-semibold">
                                                        {{ ucwords(str_replace('_', ' ', $fallbackCat)) }}
                                                    </span>
                                                @empty
                                                    <span class="badge bg-secondary-subtle text-secondary px-2 py-1 rounded-pill">Tanpa Kategori</span>
                                                @endforelse
                                            @endforelse
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-inline-flex align-items-center gap-2 px-2.5 py-1 rounded-pill border {{ $cfg['badge'] }} small fw-semibold">
                                            <span class="rounded-circle d-inline-block shadow-sm" style="width: 10px; height: 10px; background-color: {{ $cfg['dot'] }};"></span>
                                            <span>{{ $cfg['label'] }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-body-secondary text-primary border-0 rounded-2 p-1.5 me-1" onclick="editStatus({{ json_encode($status) }}, {{ json_encode($status->kategoriProses->pluck('kode')->toArray() ?: $status->categories) }})" title="Edit Status & Kategori">
                                                <i class="bi bi-pencil-square fs-6"></i>
                                            </button>
                                            <form action="{{ route('status-proses.destroy', $status->id_status) }}" method="POST" class="d-inline delete-confirm">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-body-secondary text-danger border-0 rounded-2 p-1.5" title="Hapus Status">
                                                    <i class="bi bi-trash fs-6"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-body-secondary">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Belum ada data status proses untuk kategori ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 2: MASTER KATEGORI PROSES              -->
        <!-- ========================================== -->
        <div class="tab-pane fade {{ request('tab') === 'kategori' ? 'show active' : '' }}" id="content-kategori-proses" role="tabpanel" aria-labelledby="tab-kategori-proses">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-body py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0">Daftar Kategori Proses & Konfigurasi Filter</h6>
                        <small class="text-secondary">Atur pengelompokan proses BPN, saklar kecualikan target tahunan, dan status sistem</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary rounded-3 d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">
                        <i class="bi bi-plus-lg"></i> Tambah Kategori
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-body-tertiary text-body-secondary small fw-semibold">
                            <tr>
                                <th class="ps-4 py-3" style="width: 70px;">URUTAN</th>
                                <th class="py-3">KODE & NAMA KATEGORI</th>
                                <th class="py-3">KECUALIKAN TARGET TAHUNAN</th>
                                <th class="py-3">TERMASUK ASET BELUM DIURUS</th>
                                <th class="py-3 text-center">STATUS BPN TERHUBUNG</th>
                                <th class="py-3 text-center">TIPE KATEGORI</th>
                                <th class="text-center py-3 pe-4" style="width: 120px;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($kategoriList as $kategori)
                                <tr>
                                    <td class="ps-4 font-monospace text-secondary fw-bold">
                                        #{{ $kategori->urutan }}
                                    </td>
                                    <td>
                                        <div class="fw-bold text-body fs-6">{{ $kategori->nama }}</div>
                                        <div class="font-monospace small text-secondary">kode: <code>{{ $kategori->kode }}</code></div>
                                        @if($kategori->deskripsi)
                                            <div class="small text-body-secondary mt-1">{{ Str::limit($kategori->deskripsi, 60) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($kategori->exclude_target)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded-pill fw-semibold">
                                                <i class="bi bi-shield-slash-fill me-1"></i> Ya (Kecualikan Target)
                                            </span>
                                        @else
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-pill fw-semibold">
                                                <i class="bi bi-check-circle-fill me-1"></i> Tidak (Sertakan Target)
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($kategori->includes_unprocessed)
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2.5 py-1.5 rounded-pill fw-semibold">
                                                <i class="bi bi-check-circle-fill me-1"></i> Ya (Aset Tanpa Riwayat)
                                            </span>
                                        @else
                                            <span class="badge bg-body-secondary text-secondary border px-2.5 py-1.5 rounded-pill fw-semibold">
                                                <i class="bi bi-dash me-1"></i> Hanya Berriwayat
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary rounded-pill px-2.5 py-1">{{ $kategori->status_proses_count ?? 0 }} Status</span>
                                    </td>
                                    <td class="text-center">
                                        @if($kategori->is_system)
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill fw-semibold" title="Kategori bawaan sistem">
                                                <i class="bi bi-lock-fill me-1"></i> Sistem
                                            </span>
                                        @else
                                            <span class="badge bg-light text-secondary border px-2 py-1 rounded-pill fw-semibold">
                                                Kustom
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-body-secondary text-primary border-0 rounded-2 p-1.5 me-1" onclick="editKategori({{ json_encode($kategori) }})" title="Edit Kategori">
                                                <i class="bi bi-pencil-square fs-6"></i>
                                            </button>
                                            @if(!$kategori->is_system)
                                                <form action="{{ route('status-proses.kategori.destroy', $kategori->id) }}" method="POST" class="d-inline delete-confirm">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-body-secondary text-danger border-0 rounded-2 p-1.5" title="Hapus Kategori">
                                                        <i class="bi bi-trash fs-6"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <button type="button" class="btn btn-body-secondary text-muted border-0 rounded-2 p-1.5 disabled" title="Kategori sistem dilindungi dari penghapusan">
                                                    <i class="bi bi-lock fs-6"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-body-secondary">
                                        <i class="bi bi-tags fs-1 d-block mb-2"></i>
                                        Belum ada kategori yang terdaftar.
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

@push('modals')
<!-- Modal Tambah Status -->
<div class="modal fade" id="modalTambahStatus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('status-proses.store') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Tambah Status Proses & Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Status Proses BPN <span class="text-danger">*</span></label>
                        <input type="text" name="nama_status" class="form-control" required placeholder="Contoh: Pengukuran BPN / Terbit Sertifikat">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-8">
                            <label class="form-label small fw-semibold">Warna Label Visual</label>
                            <select name="warna" class="form-select">
                                <option value="primary">Primary (Biru)</option>
                                <option value="success">Success (Hijau / Bersertifikat)</option>
                                <option value="warning">Warning (Kuning / Proses)</option>
                                <option value="danger">Danger (Merah / Kendala)</option>
                                <option value="info">Info (Biru Muda)</option>
                                <option value="secondary">Secondary (Abu-abu)</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-semibold">No. Urut</label>
                            <input type="number" name="urutan" class="form-control" placeholder="Otomatis">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold d-block mb-2">
                            Pilih Kategori Pengelompokan (Bisa Pilih Lebih dari 1) <span class="text-danger">*</span>
                        </label>
                        <div class="p-3 bg-light rounded-3 border d-flex flex-column gap-2">
                            @foreach($kategoriList as $kat)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="kategori[]" value="{{ $kat->kode }}" id="add_cat_{{ $kat->kode }}" {{ $kat->kode === 'dalam_proses' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold text-body" for="add_cat_{{ $kat->kode }}">
                                        {{ $kat->nama }}
                                        @if($kat->exclude_target)
                                            <small class="text-danger fw-normal">(Kecualikan Target)</small>
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-2">
                            <label class="form-label small fw-semibold text-secondary">Kategori Kustom Tambahan (Opsional, pisahkan koma)</label>
                            <input type="text" name="custom_kategori" class="form-control form-control-sm" placeholder="Contoh: hibah, pengadaan, redistribusi">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Simpan Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Status -->
<div class="modal fade" id="modalEditStatus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="formEditStatus" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Edit Status Proses & Multi-Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Status Proses BPN <span class="text-danger">*</span></label>
                        <input type="text" id="editNamaStatus" name="nama_status" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-8">
                            <label class="form-label small fw-semibold">Warna Label Visual</label>
                            <select id="editWarna" name="warna" class="form-select">
                                <option value="primary">Primary (Biru)</option>
                                <option value="success">Success (Hijau / Bersertifikat)</option>
                                <option value="warning">Warning (Kuning / Proses)</option>
                                <option value="danger">Danger (Merah / Kendala)</option>
                                <option value="info">Info (Biru Muda)</option>
                                <option value="secondary">Secondary (Abu-abu)</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-semibold">No. Urut</label>
                            <input type="number" id="editUrutan" name="urutan" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold d-block mb-2">
                            Pilih Kategori Pengelompokan (Bisa Pilih Lebih dari 1) <span class="text-danger">*</span>
                        </label>
                        <div class="p-3 bg-light rounded-3 border d-flex flex-column gap-2">
                            @foreach($kategoriList as $kat)
                                <div class="form-check">
                                    <input class="form-check-input edit-cat-checkbox" type="checkbox" name="kategori[]" value="{{ $kat->kode }}" id="edit_cat_{{ $kat->kode }}">
                                    <label class="form-check-label fw-semibold text-body" for="edit_cat_{{ $kat->kode }}">
                                        {{ $kat->nama }}
                                        @if($kat->exclude_target)
                                            <small class="text-danger fw-normal">(Kecualikan Target)</small>
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-2">
                            <label class="form-label small fw-semibold text-secondary">Kategori Kustom Tambahan (Opsional, pisahkan koma)</label>
                            <input type="text" id="editCustomKategori" name="custom_kategori" class="form-control form-control-sm" placeholder="Contoh: hibah, pengadaan, redistribusi">
                        </div>
                        <div class="form-text small mt-1">Perubahan kategori ini langsung otomatis memperbarui statistik dashboard & filter laporan.</div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Simpan Pembaruan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Kategori -->
<div class="modal fade" id="modalTambahKategori" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('status-proses.kategori.store') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Tambah Kategori Proses Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" required placeholder="Contoh: Proses Pensertifikatan Khusus">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-8">
                            <label class="form-label small fw-semibold">Kode Unik (Opsional)</label>
                            <input type="text" name="kode" class="form-control font-monospace" placeholder="Otomatis dari nama (slug)">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-semibold">No. Urut</label>
                            <input type="number" name="urutan" class="form-control" placeholder="Otomatis">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Deskripsi (Opsional)</label>
                        <textarea name="deskripsi" class="form-control" rows="2" placeholder="Keterangan mengenai kategori ini..."></textarea>
                    </div>

                    <!-- Saklar Konfigurasi Filter -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" name="exclude_target" value="1" id="add_exclude_target">
                            <label class="form-check-label fw-semibold text-danger" for="add_exclude_target">
                                <i class="bi bi-shield-slash me-1"></i> Kecualikan Target Pensertifikatan
                            </label>
                            <div class="form-text small">Jika diaktifkan, aset tanah yang masuk dalam target sertifikat tahun berjalan tidak akan dimunculkan pada kategori ini.</div>
                        </div>
                        <hr class="my-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="includes_unprocessed" value="1" id="add_includes_unprocessed">
                            <label class="form-check-label fw-semibold text-info-emphasis" for="add_includes_unprocessed">
                                <i class="bi bi-folder-check me-1"></i> Sertakan Aset Belum Diproses
                            </label>
                            <div class="form-text small">Jika diaktifkan, aset yang sama sekali belum memiliki riwayat proses BPN akan otomatis masuk ke kategori ini.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div class="modal fade" id="modalEditKategori" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="formEditKategori" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Edit Kategori Proses</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" id="editKategoriNama" name="nama" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-8">
                            <label class="form-label small fw-semibold">Kode Unik</label>
                            <input type="text" id="editKategoriKode" name="kode" class="form-control font-monospace">
                            <div class="form-text small" id="editKategoriKodeNotice"></div>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-semibold">No. Urut</label>
                            <input type="number" id="editKategoriUrutan" name="urutan" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Deskripsi</label>
                        <textarea id="editKategoriDeskripsi" name="deskripsi" class="form-control" rows="2"></textarea>
                    </div>

                    <!-- Saklar Konfigurasi Filter -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" name="exclude_target" value="1" id="edit_exclude_target">
                            <label class="form-check-label fw-semibold text-danger" for="edit_exclude_target">
                                <i class="bi bi-shield-slash me-1"></i> Kecualikan Target Pensertifikatan
                            </label>
                            <div class="form-text small">Jika diaktifkan, aset tanah yang masuk dalam target sertifikat tahun berjalan tidak akan dimunculkan pada kategori ini.</div>
                        </div>
                        <hr class="my-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="includes_unprocessed" value="1" id="edit_includes_unprocessed">
                            <label class="form-check-label fw-semibold text-info-emphasis" for="edit_includes_unprocessed">
                                <i class="bi bi-folder-check me-1"></i> Sertakan Aset Belum Diproses
                            </label>
                            <div class="form-text small">Jika diaktifkan, aset yang sama sekali belum memiliki riwayat proses BPN akan otomatis masuk ke kategori ini.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
    function editStatus(status, categories) {
        document.getElementById('formEditStatus').action = `{{ url('master-data/status-proses') }}/${status.id_status}`;
        document.getElementById('editNamaStatus').value = status.nama_status;
        document.getElementById('editWarna').value = status.warna || 'primary';
        document.getElementById('editUrutan').value = status.urutan || 0;

        // Reset all checkboxes
        const checkboxes = document.querySelectorAll('.edit-cat-checkbox');
        checkboxes.forEach(cb => cb.checked = false);

        const registeredCodes = [];
        checkboxes.forEach(cb => registeredCodes.push(cb.value.toLowerCase()));

        const customCats = [];
        const catArray = Array.isArray(categories) ? categories : (status.kategori ? status.kategori.split(',').map(s => s.trim()) : []);

        catArray.forEach(cat => {
            const normalized = cat.toLowerCase();
            const cb = document.getElementById(`edit_cat_${normalized}`);
            if (cb) {
                cb.checked = true;
            } else if (!registeredCodes.includes(normalized) && normalized !== '') {
                customCats.push(normalized);
            }
        });

        document.getElementById('editCustomKategori').value = customCats.join(', ');

        const modal = new bootstrap.Modal(document.getElementById('modalEditStatus'));
        modal.show();
    }

    function editKategori(kategori) {
        document.getElementById('formEditKategori').action = `{{ url('master-data/status-proses/kategori') }}/${kategori.id}`;
        document.getElementById('editKategoriNama').value = kategori.nama;
        document.getElementById('editKategoriKode').value = kategori.kode;
        document.getElementById('editKategoriUrutan').value = kategori.urutan || 0;
        document.getElementById('editKategoriDeskripsi').value = kategori.deskripsi || '';

        document.getElementById('edit_exclude_target').checked = Boolean(kategori.exclude_target);
        document.getElementById('edit_includes_unprocessed').checked = Boolean(kategori.includes_unprocessed);

        const kodeInput = document.getElementById('editKategoriKode');
        const kodeNotice = document.getElementById('editKategoriKodeNotice');
        if (kategori.is_system) {
            kodeInput.readOnly = true;
            kodeNotice.textContent = 'Kode kategori sistem terkunci.';
            kodeNotice.className = 'form-text text-warning small';
        } else {
            kodeInput.readOnly = false;
            kodeNotice.textContent = 'Kode slug unik untuk URL filter.';
            kodeNotice.className = 'form-text text-secondary small';
        }

        const modal = new bootstrap.Modal(document.getElementById('modalEditKategori'));
        modal.show();
    }
</script>
@endpush
@endsection
