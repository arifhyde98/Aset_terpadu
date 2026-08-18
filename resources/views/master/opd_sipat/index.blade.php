@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary fw-semibold px-2.5 py-1 rounded-pill" style="font-size: 0.75rem;">
                    <i class="bi bi-gear-wide-connected me-1"></i> MASTER & SISTEM
                </span>
                <span class="text-secondary small">&bull;</span>
                <span class="text-secondary small">Master Instansi Pengelola Aset Tanah</span>
            </div>
            <h2 class="fw-bold mb-1">Master OPD (SIPAT)</h2>
            <p class="text-secondary mb-0 small">Kelola master daftar Organisasi Perangkat Daerah (OPD) & Instansi khusus modul SIPAT Aset Tanah</p>
        </div>
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm d-flex align-items-center gap-2" onclick="openModalTambah()">
            <i class="bi bi-plus-lg"></i> Tambah OPD Baru
        </button>
    </div>

    <!-- Alert Success / Error -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Card Toolbar Filter -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('opd-sipat.index') }}">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-body-tertiary border-0 text-secondary"><i class="bi bi-search"></i></span>
                            <input type="text" name="q" class="form-control bg-body-tertiary border-0" placeholder="Cari nama OPD / Instansi..." value="{{ request('q') }}">
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <select name="status" class="form-select bg-body-tertiary border-0">
                            <option value="">-- Semua Status --</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Non-Aktif</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary rounded-pill px-3 flex-grow-1">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                        <a href="{{ route('opd-sipat.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-body-tertiary text-secondary small fw-bold">
                    <tr>
                        <th class="ps-4 py-3" style="width: 70px;">NO</th>
                        <th class="py-3">NAMA OPD / INSTANSI</th>
                        <th class="py-3" style="width: 140px;">STATUS</th>
                        <th class="text-center py-3 pe-4" style="width: 140px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($opdList as $index => $item)
                        <tr>
                            <td class="ps-4 fw-medium text-secondary" style="font-size: 0.85rem;">
                                {{ $index + 1 }}
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2.5">
                                    <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                                        <i class="bi bi-building fs-6"></i>
                                    </div>
                                    <div class="fw-semibold text-body fs-6">{{ $item->nama }}</div>
                                </div>
                            </td>
                            <td>
                                @if($item->aktif)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 rounded-pill small">
                                        <i class="bi bi-check-circle-fill me-1"></i> Aktif
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1.5 rounded-pill small">
                                        <i class="bi bi-x-circle-fill me-1"></i> Non-Aktif
                                    </span>
                                @endif
                            </td>
                            <td class="text-center pe-4">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary rounded-start-pill px-3" onclick="editOpd({{ json_encode($item) }})" title="Edit OPD">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                    <form action="{{ route('opd-sipat.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus OPD ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger rounded-end-pill px-2.5" title="Hapus OPD">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-secondary">
                                <i class="bi bi-building-dash fs-1 text-muted d-block mb-2"></i>
                                Belum ada data OPD SIPAT yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('modals')
<!-- Modal Tambah OPD -->
<div class="modal fade" id="modalTambahOpd" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('opd-sipat.store') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-body mb-0"><i class="bi bi-plus-circle text-primary me-2"></i>Tambah OPD SIPAT</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-body-tertiary">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nama OPD / Instansi <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control bg-body border-0 shadow-sm" required placeholder="Contoh: Dinas Kesehatan">
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="aktif" value="1" id="checkTambahAktif" checked>
                        <label class="form-check-label small fw-semibold text-body" for="checkTambahAktif">Status Aktif</label>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">Simpan OPD</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit OPD -->
<div class="modal fade" id="modalEditOpd" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="formEditOpd" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-body mb-0"><i class="bi bi-pencil-square text-primary me-2"></i>Edit OPD SIPAT</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-body-tertiary">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nama OPD / Instansi <span class="text-danger">*</span></label>
                        <input type="text" id="editNamaOpd" name="nama" class="form-control bg-body border-0 shadow-sm" required>
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="aktif" value="1" id="editAktifOpd">
                        <label class="form-check-label small fw-semibold text-body" for="editAktifOpd">Status Aktif</label>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">Update OPD</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
    function openModalTambah() {
        const el = document.getElementById('modalTambahOpd');
        if (!el) return;
        if (el.parentNode !== document.body) {
            document.body.appendChild(el);
        }
        const modal = bootstrap.Modal.getOrCreateInstance(el);
        modal.show();
    }

    function editOpd(item) {
        const el = document.getElementById('modalEditOpd');
        if (!el) return;
        if (el.parentNode !== document.body) {
            document.body.appendChild(el);
        }
        document.getElementById('formEditOpd').action = `{{ url('master-data/opd-sipat') }}/${item.id}`;
        document.getElementById('editNamaOpd').value = item.nama;
        document.getElementById('editAktifOpd').checked = (item.aktif == 1);

        const modal = bootstrap.Modal.getOrCreateInstance(el);
        modal.show();
    }
</script>
@endpush
@endsection
