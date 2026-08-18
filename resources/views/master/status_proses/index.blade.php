@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1">Master Status Proses Pensertifikatan</h2>
            <p class="text-secondary small mb-0">Kelola master urutan dan status tahapan BPN dalam pensertifikatan tanah</p>
        </div>
        <button type="button" class="btn btn-primary rounded-3 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalTambahStatus">
            <i class="bi bi-plus-lg"></i> Tambah Status Proses
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-body-tertiary text-secondary small fw-semibold">
                    <tr>
                        <th class="ps-3 py-3" style="width: 80px;">URUTAN</th>
                        <th class="py-3">NAMA STATUS PROSES</th>
                        <th class="py-3">KATEGORI</th>
                        <th class="py-3">WARNA LENGKAP</th>
                        <th class="text-center py-3 pe-3" style="width: 150px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statusProses as $status)
                        <tr>
                            <td class="ps-3">
                                <span class="badge bg-secondary-subtle text-body fw-bold rounded-circle p-2" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                    {{ $status->urutan }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-body fs-6">{{ $status->nama_status }}</div>
                            </td>
                            <td>
                                <span class="badge bg-info-subtle text-info px-2.5 py-1 text-uppercase small">
                                    {{ $status->kategori ?? 'proses' }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="d-inline-block rounded-circle border" style="width: 16px; height: 16px; background-color: {{ $status->warna ?? '#3b82f6' }};"></span>
                                    <code class="small text-secondary">{{ $status->warna ?? '#3b82f6' }}</code>
                                </div>
                            </td>
                            <td class="text-center pe-3">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" onclick="editStatus({{ json_encode($status) }})">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form action="{{ route('status-proses.destroy', $status->id_status) }}" method="POST" class="d-inline delete-confirm">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-secondary">
                                Belum ada data status proses.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Status -->
<div class="modal fade" id="modalTambahStatus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('status-proses.store') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Tambah Status Proses</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Status Proses</label>
                        <input type="text" name="nama_status" class="form-control" required placeholder="Contoh: Pengukuran BPN">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nomor Urutan</label>
                        <input type="number" name="urutan" class="form-control" required value="{{ count($statusProses) + 1 }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Warna Label (HEX / Named)</label>
                        <input type="color" name="warna" class="form-control form-control-color w-100" value="#3b82f6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kategori</label>
                        <select name="kategori" class="form-select">
                            <option value="proses">Proses</option>
                            <option value="selesai">Selesai / Bersertifikat</option>
                            <option value="kendala">Kendala / Sengketa</option>
                            <option value="belum">Belum Diurus</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan</button>
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
                    <h5 class="modal-title fw-bold">Edit Status Proses</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Status Proses</label>
                        <input type="text" id="editNamaStatus" name="nama_status" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nomor Urutan</label>
                        <input type="number" id="editUrutan" name="urutan" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Warna Label</label>
                        <input type="color" id="editWarna" name="warna" class="form-control form-control-color w-100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kategori</label>
                        <select id="editKategori" name="kategori" class="form-select">
                            <option value="proses">Proses</option>
                            <option value="selesai">Selesai / Bersertifikat</option>
                            <option value="kendala">Kendala / Sengketa</option>
                            <option value="belum">Belum Diurus</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function editStatus(status) {
        document.getElementById('formEditStatus').action = `{{ url('master-data/status-proses') }}/${status.id_status}`;
        document.getElementById('editNamaStatus').value = status.nama_status;
        document.getElementById('editUrutan').value = status.urutan;
        document.getElementById('editWarna').value = status.warna || '#3b82f6';
        document.getElementById('editKategori').value = status.kategori || 'proses';

        const modal = new bootstrap.Modal(document.getElementById('modalEditStatus'));
        modal.show();
    }
</script>
@endpush
@endsection
