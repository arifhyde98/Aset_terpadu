@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <!-- Header Title & Breadcrumb -->
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold px-2 py-1 rounded-pill" style="font-size: 0.72rem;">
                    <i class="bi bi-gear-wide-connected me-1"></i> MASTER & SISTEM
                </span>
                <span class="text-secondary small">&bull;</span>
                <span class="text-secondary small fw-medium">Modul SIPAT Terpadu</span>
            </div>
            <h3 class="fw-bold mb-1 text-body">Master Nama Aset Tanah (KIB A)</h3>
            <p class="text-body-secondary small mb-0">
                Kelola master referensi nama aset tanah untuk standardisasi pemilihan data aset tanah KIB A di seluruh OPD.
            </p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary rounded-3 px-3 py-2 d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahNamaAset">
                <i class="bi bi-plus-lg"></i> <span class="d-none d-sm-inline">Tambah</span> Nama Aset
            </button>
        </div>
    </div>

    <!-- Alert Flash Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div>{{ session('error') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(isset($errors) && $errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle-fill me-1"></i> Terjadi kesalahan input:</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-body h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary-subtle text-primary p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-card-checklist fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium">Total Master Nama</div>
                        <h4 class="fw-bold mb-0 text-body">{{ $totalAll }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-body h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success-subtle text-success p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-check2-circle fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium">Status Aktif</div>
                        <h4 class="fw-bold mb-0 text-success">{{ $totalActive }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-body h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-warning-subtle text-warning p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-tags fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium">Jumlah Kelompok</div>
                        <h4 class="fw-bold mb-0 text-body">{{ count($kelompokList) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-body">
        <form action="{{ route('master.nama-aset.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-body-tertiary border-end-0 text-secondary">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="q" class="form-control bg-body-tertiary border-start-0" placeholder="Cari nama aset atau kode barang..." value="{{ request('q') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="kelompok" class="form-select bg-body-tertiary" onchange="this.form.submit()">
                    <option value="">-- Semua Kelompok --</option>
                    @foreach($kelompokList as $k)
                        <option value="{{ $k }}" {{ request('kelompok') == $k ? 'selected' : '' }}>{{ $k }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select bg-body-tertiary" onchange="this.form.submit()">
                    <option value="">-- Status --</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100 rounded-3">
                    <i class="bi bi-filter"></i> Filter
                </button>
                @if(request()->anyFilled(['q', 'kelompok', 'status']))
                    <a href="{{ route('master.nama-aset.index') }}" class="btn btn-outline-secondary rounded-3" title="Reset Filter">
                        <i class="bi bi-arrow-clockwise"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Data Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-body">
        <div class="card-header bg-body py-3 px-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h6 class="fw-bold mb-0 text-body">Daftar Master Nama Aset Tanah</h6>
                <small class="text-body-secondary">Digunakan sebagai opsi standar saat input & edit data KIB A</small>
            </div>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 rounded-pill fw-semibold">
                Total {{ $namaAsets->total() }} Data
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-body-tertiary text-body-secondary small fw-bold">
                    <tr>
                        <th class="ps-4 py-3" style="width: 70px;">URUTAN</th>
                        <th class="py-3" style="width: 140px;">KODE BARANG</th>
                        <th class="py-3">NAMA ASET TANAH</th>
                        <th class="py-3">KELOMPOK / KATEGORI</th>
                        <th class="text-center py-3" style="width: 130px;">TERPAKAI</th>
                        <th class="text-center py-3" style="width: 110px;">STATUS</th>
                        <th class="text-end py-3 pe-4" style="width: 140px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($namaAsets as $item)
                        <tr>
                            <td class="ps-4">
                                <span class="badge bg-light text-secondary border font-monospace px-2 py-1 rounded-2">
                                    #{{ $item->urutan }}
                                </span>
                            </td>
                            <td>
                                @if($item->kode_barang)
                                    <code class="text-primary fw-bold">{{ $item->kode_barang }}</code>
                                @else
                                    <span class="text-secondary small fst-italic">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-body">{{ $item->nama }}</div>
                                @if($item->deskripsi)
                                    <small class="text-secondary d-block">{{ Str::limit($item->deskripsi, 60) }}</small>
                                @endif
                            </td>
                            <td>
                                @if($item->kelompok)
                                    <span class="badge bg-secondary-subtle text-body border border-secondary-subtle px-2.5 py-1 rounded-pill">
                                        <i class="bi bi-folder2 me-1"></i>{{ $item->kelompok }}
                                    </span>
                                @else
                                    <span class="text-secondary small fst-italic">Lain-Lain</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('sipat.aset.index', ['q' => $item->nama]) }}" class="badge {{ $item->aset_tanah_count > 0 ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-body-secondary text-secondary' }} text-decoration-none px-2.5 py-1.5 rounded-pill" title="Lihat aset tanah terkait">
                                    <i class="bi bi-geo-alt-fill me-1"></i>{{ number_format($item->aset_tanah_count) }} Tanah
                                </a>
                            </td>
                            <td class="text-center">
                                <form action="{{ route('master.nama-aset.toggle', $item->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm border-0 p-0" title="Klik untuk mengubah status">
                                        @if($item->is_active)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                                <i class="bi bi-check-circle-fill me-1"></i>Aktif
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill">
                                                <i class="bi bi-x-circle-fill me-1"></i>Nonaktif
                                            </span>
                                        @endif
                                    </button>
                                </form>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" 
                                            class="btn btn-outline-primary rounded-2 me-1 btn-edit-nama-aset" 
                                            data-id="{{ $item->id }}"
                                            data-nama="{{ $item->nama }}"
                                            data-kode="{{ $item->kode_barang }}"
                                            data-kelompok="{{ $item->kelompok }}"
                                            data-deskripsi="{{ $item->deskripsi }}"
                                            data-urutan="{{ $item->urutan }}"
                                            data-active="{{ $item->is_active ? 1 : 0 }}"
                                            title="Edit Nama Aset">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    @if($item->aset_tanah_count == 0)
                                        <form action="{{ route('master.nama-aset.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus nama aset ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger rounded-2" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-outline-secondary rounded-2 opacity-50" disabled title="Tidak dapat dihapus karena masih digunakan pada data aset">
                                            <i class="bi bi-lock-fill"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-secondary">
                                <div class="py-4">
                                    <i class="bi bi-inbox fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                    <div class="fw-semibold">Tidak ada master nama aset ditemukan</div>
                                    <small class="text-muted">Coba ubah kata kunci pencarian atau tambahkan nama aset baru.</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($namaAsets->hasPages())
            <div class="card-footer bg-body py-3 px-4 border-top">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-secondary">
                        Menampilkan {{ $namaAsets->firstItem() }} s/d {{ $namaAsets->lastItem() }} dari {{ $namaAsets->total() }} data
                    </small>
                    <div>
                        {{ $namaAsets->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Modal Tambah Master Nama Aset -->
<div class="modal fade" id="modalTambahNamaAset" tabindex="-1" aria-labelledby="modalTambahNamaAsetLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="{{ route('master.nama-aset.store') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold" id="modalTambahNamaAsetLabel">
                        <i class="bi bi-plus-circle-fill text-primary me-2"></i>Tambah Master Nama Aset
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nama Aset Tanah (KIB A) <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control rounded-3" placeholder="Contoh: Tanah Bangunan Kantor Pemerintah" required value="{{ old('nama') }}">
                        <div class="form-text small">Nama resmi pengelompokan bidang tanah KIB A.</div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Kode Barang (Opsional)</label>
                            <input type="text" name="kode_barang" class="form-control rounded-3" placeholder="Contoh: 01.01.11.01" value="{{ old('kode_barang') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Urutan Tampilan</label>
                            <input type="number" name="urutan" class="form-control rounded-3" placeholder="0" value="{{ old('urutan', 0) }}" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Kelompok / Kategori</label>
                        <input type="text" name="kelompok" class="form-control rounded-3" placeholder="Contoh: Tanah Bangunan Gedung" list="listKelompok" value="{{ old('kelompok') }}">
                        <datalist id="listKelompok">
                            @foreach($kelompokList as $k)
                                <option value="{{ $k }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Deskripsi / Keterangan</label>
                        <textarea name="deskripsi" class="form-control rounded-3" rows="2" placeholder="Keterangan tambahan klasifikasi aset...">{{ old('deskripsi') }}</textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active_create" name="is_active" value="1" checked>
                        <label class="form-check-label small fw-medium" for="is_active_create">Aktifkan untuk opsi form aset</label>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Master Nama Aset -->
<div class="modal fade" id="modalEditNamaAset" tabindex="-1" aria-labelledby="modalEditNamaAsetLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="formEditNamaAset" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold" id="modalEditNamaAsetLabel">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Edit Master Nama Aset
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nama Aset Tanah (KIB A) <span class="text-danger">*</span></label>
                        <input type="text" name="nama" id="editNama" class="form-control rounded-3" required>
                        <div class="form-text small">Perubahan nama akan otomatis tersinkronisasi ke seluruh data aset tanah terkait.</div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Kode Barang (Opsional)</label>
                            <input type="text" name="kode_barang" id="editKodeBarang" class="form-control rounded-3">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Urutan Tampilan</label>
                            <input type="number" name="urutan" id="editUrutan" class="form-control rounded-3" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Kelompok / Kategori</label>
                        <input type="text" name="kelompok" id="editKelompok" class="form-control rounded-3" list="listKelompokEdit">
                        <datalist id="listKelompokEdit">
                            @foreach($kelompokList as $k)
                                <option value="{{ $k }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Deskripsi / Keterangan</label>
                        <textarea name="deskripsi" id="editDeskripsi" class="form-control rounded-3" rows="2"></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="editIsActive" name="is_active" value="1">
                        <label class="form-check-label small fw-medium" for="editIsActive">Aktifkan untuk opsi form aset</label>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editModalEl = document.getElementById('modalEditNamaAset');
    const editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;
    const formEdit = document.getElementById('formEditNamaAset');

    document.querySelectorAll('.btn-edit-nama-aset').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const nama = this.getAttribute('data-nama') || '';
            const kode = this.getAttribute('data-kode') || '';
            const kelompok = this.getAttribute('data-kelompok') || '';
            const deskripsi = this.getAttribute('data-deskripsi') || '';
            const urutan = this.getAttribute('data-urutan') || '0';
            const isActive = this.getAttribute('data-active') === '1';

            formEdit.action = `{{ url('master-data/nama-aset') }}/${id}`;
            document.getElementById('editNama').value = nama;
            document.getElementById('editKodeBarang').value = kode;
            document.getElementById('editKelompok').value = kelompok;
            document.getElementById('editDeskripsi').value = deskripsi;
            document.getElementById('editUrutan').value = urutan;
            document.getElementById('editIsActive').checked = isActive;

            if (editModal) {
                editModal.show();
            }
        });
    });
});
</script>
@endpush
