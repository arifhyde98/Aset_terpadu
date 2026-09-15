@extends('layouts.app')

@section('title', 'Data Sub-OPD / Kuasa Pengguna Barang')

@section('content')
<div class="container-fluid px-0">
    
    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('opds.index') }}" class="text-decoration-none text-secondary">Master OPD</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Sub-OPD (KPB)</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-navy mb-0">Kuasa Pengguna Barang (Sub-OPD)</h3>
            <p class="text-muted small mb-0">Manajemen unit kerja bawahan (Puskesmas, Bagian, UPTD, Sekolah, RSUD) khusus modul E-RANDIS</p>
        </div>
        <div class="action-toolbar d-flex gap-2">
            <a href="{{ route('opds.index') }}" class="btn btn-outline-secondary shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-building"></i> Kelola OPD Induk
            </a>
            <button type="button" class="btn btn-primary shadow-sm fw-medium d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addSubOpdModal">
                <i class="bi bi-plus-lg"></i> Tambah Sub-OPD
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- MAIN TABLE SECTION -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-transparent border-0 p-3">
            <form action="{{ route('sub-opds.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control bg-light border-start-0 shadow-none" placeholder="Cari nama unit, kode, pimpinan...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="opd_id" class="form-select form-select-sm bg-light shadow-none">
                        <option value="">-- Semua OPD Induk --</option>
                        @foreach($opds as $o)
                            <option value="{{ $o->id }}" {{ request('opd_id') == $o->id ? 'selected' : '' }}>{{ $o->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="jenis" class="form-select form-select-sm bg-light shadow-none">
                        <option value="">-- Semua Jenis --</option>
                        <option value="puskesmas" {{ request('jenis') === 'puskesmas' ? 'selected' : '' }}>Puskesmas</option>
                        <option value="bagian" {{ request('jenis') === 'bagian' ? 'selected' : '' }}>Bagian Setda</option>
                        <option value="uptd" {{ request('jenis') === 'uptd' ? 'selected' : '' }}>UPTD</option>
                        <option value="rsud" {{ request('jenis') === 'rsud' ? 'selected' : '' }}>RSUD</option>
                        <option value="sekolah" {{ request('jenis') === 'sekolah' ? 'selected' : '' }}>Sekolah</option>
                        <option value="lainnya" {{ request('jenis') === 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3 fw-medium">Filter</button>
                    <a href="{{ route('sub-opds.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset"><i class="bi bi-arrow-clockwise"></i> Reset</a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4 text-center" style="width: 50px;">No</th>
                        <th class="py-3">Nama Sub-OPD / KPB</th>
                        <th class="py-3">OPD Induk (Pengguna Barang)</th>
                        <th class="py-3 text-center" style="width: 120px;">Jenis</th>
                        <th class="py-3">Pimpinan (KPB)</th>
                        <th class="py-3 text-center" style="width: 140px;">Kendaraan</th>
                        <th class="py-3 text-center" style="width: 100px;">Status</th>
                        <th class="py-3 text-end px-4" style="width: 110px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subOpds as $index => $sub)
                        <tr>
                            <td class="text-center fw-semibold text-muted">{{ $subOpds->firstItem() + $index }}</td>
                            <td>
                                <div class="fw-bold text-navy">{{ $sub->nama }}</div>
                                @if($sub->kode_sub)
                                    <span class="badge bg-light text-secondary border small font-monospace">Kode: {{ $sub->kode_sub }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-dark fw-medium small">{{ $sub->opd->nama ?? '-' }}</div>
                            </td>
                            <td class="text-center">
                                @php
                                    $badgeClass = match($sub->jenis) {
                                        'puskesmas' => 'bg-danger-subtle text-danger border-danger-subtle',
                                        'bagian'    => 'bg-primary-subtle text-primary border-primary-subtle',
                                        'uptd'      => 'bg-success-subtle text-success border-success-subtle',
                                        'rsud'      => 'bg-info-subtle text-info border-info-subtle',
                                        default     => 'bg-secondary-subtle text-secondary border-secondary-subtle'
                                    };
                                @endphp
                                <span class="badge border {{ $badgeClass }} px-2 py-1 text-uppercase small">
                                    {{ $sub->jenis }}
                                </span>
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark">{{ $sub->nama_pimpinan ?: '-' }}</div>
                                @if($sub->nip_pimpinan)
                                    <div class="text-muted small">NIP: {{ $sub->nip_pimpinan }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary-subtle text-primary small px-2 py-1" title="Kendaraan Fisik">
                                    <i class="bi bi-truck me-1"></i> {{ $sub->vehicles_count }} Real
                                </span>
                                <span class="badge bg-info-subtle text-info small px-2 py-1 mt-1" title="Kendaraan e-BMD">
                                    {{ $sub->ebmd_vehicles_count }} e-BMD
                                </span>
                            </td>
                            <td class="text-center">
                                @if($sub->aktif)
                                    <span class="badge bg-success-subtle text-success small px-2 py-1"><i class="bi bi-check-circle me-1"></i> Aktif</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger small px-2 py-1">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-end px-4">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary btn-edit-sub" 
                                        data-id="{{ $sub->id }}"
                                        data-nama="{{ $sub->nama }}"
                                        data-opd_id="{{ $sub->opd_id }}"
                                        data-kode_sub="{{ $sub->kode_sub }}"
                                        data-jenis="{{ $sub->jenis }}"
                                        data-alamat="{{ $sub->alamat }}"
                                        data-nama_pimpinan="{{ $sub->nama_pimpinan }}"
                                        data-nip_pimpinan="{{ $sub->nip_pimpinan }}"
                                        data-aktif="{{ $sub->aktif ? 1 : 0 }}"
                                        title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('sub-opds.destroy', $sub->id) }}" method="POST" class="d-inline delete-sub-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-diagram-3 fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                Belum ada data Sub-OPD / Kuasa Pengguna Barang yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($subOpds->hasPages())
            <div class="card-footer bg-transparent border-0 p-3">
                {{ $subOpds->links() }}
            </div>
        @endif
    </div>
</div>

<!-- MODAL TAMBAH SUB-OPD -->
<div class="modal fade" id="addSubOpdModal" tabindex="-1" aria-labelledby="addSubOpdModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-navy" id="addSubOpdModalLabel">Tambah Sub-OPD / Kuasa Pengguna Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('sub-opds.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">OPD Induk (Pengguna Barang) <span class="text-danger">*</span></label>
                            <select name="opd_id" class="form-select" required>
                                <option value="">-- Pilih OPD Induk --</option>
                                @foreach($opds as $o)
                                    <option value="{{ $o->id }}">{{ $o->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Jenis Unit <span class="text-danger">*</span></label>
                            <select name="jenis" class="form-select" required>
                                <option value="puskesmas">Puskesmas</option>
                                <option value="bagian">Bagian Setda</option>
                                <option value="uptd" selected>UPTD</option>
                                <option value="rsud">RSUD</option>
                                <option value="sekolah">Sekolah</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-secondary">Nama Sub-OPD / Unit Kerja <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control" placeholder="Contoh: PUSKESMAS LABUAN" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Kode Sub-Unit (Opsional)</label>
                            <input type="text" name="kode_sub" class="form-control" placeholder="Contoh: 1.02.01.01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Nama Pimpinan / KPB</label>
                            <input type="text" name="nama_pimpinan" class="form-control" placeholder="Nama Kepala Puskesmas / Kepala UPTD">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">NIP Pimpinan</label>
                            <input type="text" name="nip_pimpinan" class="form-control" placeholder="NIP 18 digit">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Alamat Kantor / Lokasi</label>
                            <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat lengkap unit kerja..."></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="aktif" value="1" id="aktifSwitch" checked>
                                <label class="form-check-label small fw-semibold" for="aktifSwitch">Status Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-medium px-4">Simpan Sub-OPD</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDIT SUB-OPD -->
<div class="modal fade" id="editSubOpdModal" tabindex="-1" aria-labelledby="editSubOpdModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-navy" id="editSubOpdModalLabel">Edit Sub-OPD / Kuasa Pengguna Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editSubOpdForm" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">OPD Induk (Pengguna Barang) <span class="text-danger">*</span></label>
                            <select name="opd_id" id="edit_opd_id" class="form-select" required>
                                <option value="">-- Pilih OPD Induk --</option>
                                @foreach($opds as $o)
                                    <option value="{{ $o->id }}">{{ $o->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Jenis Unit <span class="text-danger">*</span></label>
                            <select name="jenis" id="edit_jenis" class="form-select" required>
                                <option value="puskesmas">Puskesmas</option>
                                <option value="bagian">Bagian Setda</option>
                                <option value="uptd">UPTD</option>
                                <option value="rsud">RSUD</option>
                                <option value="sekolah">Sekolah</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-secondary">Nama Sub-OPD / Unit Kerja <span class="text-danger">*</span></label>
                            <input type="text" name="nama" id="edit_nama" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Kode Sub-Unit</label>
                            <input type="text" name="kode_sub" id="edit_kode_sub" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Nama Pimpinan / KPB</label>
                            <input type="text" name="nama_pimpinan" id="edit_nama_pimpinan" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">NIP Pimpinan</label>
                            <input type="text" name="nip_pimpinan" id="edit_nip_pimpinan" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Alamat Kantor / Lokasi</label>
                            <textarea name="alamat" id="edit_alamat" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="aktif" value="1" id="edit_aktif">
                                <label class="form-check-label small fw-semibold" for="edit_aktif">Status Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-medium px-4">Perbarui Sub-OPD</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editModal = new bootstrap.Modal(document.getElementById('editSubOpdModal'));
        const editForm = document.getElementById('editSubOpdForm');

        document.querySelectorAll('.btn-edit-sub').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                editForm.action = `/sub-opds/${id}`;
                document.getElementById('edit_opd_id').value = this.dataset.opd_id;
                document.getElementById('edit_nama').value = this.dataset.nama;
                document.getElementById('edit_kode_sub').value = this.dataset.kode_sub || '';
                document.getElementById('edit_jenis').value = this.dataset.jenis;
                document.getElementById('edit_alamat').value = this.dataset.alamat || '';
                document.getElementById('edit_nama_pimpinan').value = this.dataset.nama_pimpinan || '';
                document.getElementById('edit_nip_pimpinan').value = this.dataset.nip_pimpinan || '';
                document.getElementById('edit_aktif').checked = this.dataset.aktif === '1';

                editModal.show();
            });
        });

        document.querySelectorAll('.delete-sub-form').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Hapus Sub-OPD?',
                    text: 'Data Sub-OPD akan dihapus. Kendaraan yang tertaut akan tetap tersimpan pada OPD Induk namun kolom Sub-OPD akan dikosongkan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((res) => {
                    if (res.isConfirmed) {
                        this.submit();
                    }
                });
            });
        });
    });
</script>
@endsection
