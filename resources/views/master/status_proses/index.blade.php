@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Master Status Proses Pensertifikatan</h4>
            <p class="text-body-secondary small mb-0">Kelola urutan dan status tahapan BPN dalam pensertifikatan tanah</p>
        </div>
        <button type="button" class="btn btn-primary rounded-3 d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahStatus">
            <i class="bi bi-plus-lg"></i> Tambah Status Proses
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 bg-success-subtle text-success-emphasis" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-body-tertiary text-body-secondary small fw-semibold">
                    <tr>
                        <th class="ps-4 py-3" style="width: 90px;">URUTAN</th>
                        <th class="py-3">NAMA STATUS PROSES</th>
                        <th class="py-3">KATEGORI</th>
                        <th class="py-3">WARNA LABEL</th>
                        <th class="text-center py-3 pe-4" style="width: 120px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statusProses as $status)
                        @php
                            $w = strtolower(trim($status->warna ?? 'primary'));
                            if (empty($w)) { $w = 'primary'; }

                            // Color config mapped to BS 5.3 theme classes
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

                            $kat = strtolower(trim($status->kategori ?? 'proses'));
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <span class="badge bg-body-secondary text-body-emphasis fw-bold rounded-circle p-2" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.85rem;">
                                    {{ $status->urutan }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-body fs-6">{{ $status->nama_status }}</div>
                            </td>
                            <td>
                                @if($kat == 'bersertifikat' || str_contains(strtolower($status->nama_status), 'sertifikat'))
                                    <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-2.5 py-1 text-uppercase small rounded-pill fw-semibold">
                                        <i class="bi bi-patch-check-fill me-1"></i> Bersertifikat
                                    </span>
                                @elseif($kat == 'kendala' || str_contains(strtolower($status->nama_status), 'masalah'))
                                    <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle px-2.5 py-1 text-uppercase small rounded-pill fw-semibold">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Kendala / Sengketa
                                    </span>
                                @elseif($kat == 'belum_diurus' || str_contains(strtolower($status->nama_status), 'belum'))
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle px-2.5 py-1 text-uppercase small rounded-pill fw-semibold">
                                        <i class="bi bi-clock-history me-1"></i> Belum Diurus
                                    </span>
                                @else
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2.5 py-1 text-uppercase small rounded-pill fw-semibold">
                                        <i class="bi bi-hourglass-split me-1"></i> Dalam Proses
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="d-inline-flex align-items-center gap-2 px-2.5 py-1 rounded-pill border {{ $cfg['badge'] }} small fw-semibold">
                                    <span class="rounded-circle d-inline-block shadow-sm" style="width: 10px; height: 10px; background-color: {{ $cfg['dot'] }};"></span>
                                    <span>{{ $cfg['label'] }}</span>
                                </div>
                            </td>
                            <td class="text-center pe-4">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-body-secondary text-primary border-0 rounded-2 p-1.5 me-1" onclick="editStatus({{ json_encode($status) }})" title="Edit Status">
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
                                Belum ada data status proses.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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
                        <label class="form-label small fw-semibold">Warna Label</label>
                        <select name="warna" class="form-select">
                            <option value="primary">Primary (Biru)</option>
                            <option value="success">Success (Hijau / Bersertifikat)</option>
                            <option value="warning">Warning (Kuning / Proses)</option>
                            <option value="danger">Danger (Merah / Kendala)</option>
                            <option value="info">Info (Biru Muda)</option>
                            <option value="secondary">Secondary (Abu-abu)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kategori</label>
                        <select name="kategori" class="form-select">
                            <option value="proses">Dalam Proses</option>
                            <option value="bersertifikat">Selesai / Bersertifikat</option>
                            <option value="kendala">Kendala / Sengketa</option>
                            <option value="belum_diurus">Belum Diurus</option>
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
                        <select id="editWarna" name="warna" class="form-select">
                            <option value="primary">Primary (Biru)</option>
                            <option value="success">Success (Hijau / Bersertifikat)</option>
                            <option value="warning">Warning (Kuning / Proses)</option>
                            <option value="danger">Danger (Merah / Kendala)</option>
                            <option value="info">Info (Biru Muda)</option>
                            <option value="secondary">Secondary (Abu-abu)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kategori</label>
                        <select id="editKategori" name="kategori" class="form-select">
                            <option value="proses">Dalam Proses</option>
                            <option value="bersertifikat">Selesai / Bersertifikat</option>
                            <option value="kendala">Kendala / Sengketa</option>
                            <option value="belum_diurus">Belum Diurus</option>
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
@endpush

@push('scripts')
<script>
    function editStatus(status) {
        document.getElementById('formEditStatus').action = `{{ url('master-data/status-proses') }}/${status.id_status}`;
        document.getElementById('editNamaStatus').value = status.nama_status;
        document.getElementById('editUrutan').value = status.urutan;
        document.getElementById('editWarna').value = status.warna || 'primary';
        document.getElementById('editKategori').value = status.kategori || 'proses';

        const modal = new bootstrap.Modal(document.getElementById('modalEditStatus'));
        modal.show();
    }
</script>
@endpush
@endsection
