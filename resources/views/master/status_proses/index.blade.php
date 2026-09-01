@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Master Status & Kategori Proses BPN</h4>
            <p class="text-body-secondary small mb-0">Kelola nama status, warna label, dan pengelompokan kategori tahapan pensertifikatan tanah</p>
        </div>
        <button type="button" class="btn btn-primary rounded-3 d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahStatus">
            <i class="bi bi-plus-lg"></i> Tambah Status Proses
        </button>
    </div>

    <!-- Category Filter Pills -->
    <div class="d-flex align-items-center gap-2 mb-4 overflow-x-auto pb-1">
        <a href="{{ route('status-proses.index') }}" class="btn btn-sm rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 {{ !request('kategori') ? 'btn-primary shadow-sm' : 'btn-outline-secondary bg-body' }}">
            <i class="bi bi-layers-fill"></i> Semua Status
            <span class="badge {{ !request('kategori') ? 'bg-white text-primary' : 'bg-secondary-subtle text-body' }} rounded-pill ms-1">{{ $counts['total'] ?? 0 }}</span>
        </a>
        <a href="{{ route('status-proses.index', ['kategori' => 'belum_diurus']) }}" class="btn btn-sm rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 {{ request('kategori') === 'belum_diurus' ? 'btn-secondary shadow-sm' : 'btn-outline-secondary bg-body' }}">
            <i class="bi bi-clock-history"></i> Belum Diurus
            <span class="badge {{ request('kategori') === 'belum_diurus' ? 'bg-white text-secondary' : 'bg-secondary-subtle text-body' }} rounded-pill ms-1">{{ $counts['belum_diurus'] ?? 0 }}</span>
        </a>
        <a href="{{ route('status-proses.index', ['kategori' => 'proses']) }}" class="btn btn-sm rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 {{ request('kategori') === 'proses' ? 'btn-info text-white shadow-sm' : 'btn-outline-info bg-body' }}">
            <i class="bi bi-hourglass-split"></i> Dalam Proses
            <span class="badge {{ request('kategori') === 'proses' ? 'bg-white text-info' : 'bg-info-subtle text-info-emphasis' }} rounded-pill ms-1">{{ $counts['proses'] ?? 0 }}</span>
        </a>
        <a href="{{ route('status-proses.index', ['kategori' => 'bersertifikat']) }}" class="btn btn-sm rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 {{ request('kategori') === 'bersertifikat' ? 'btn-success shadow-sm' : 'btn-outline-success bg-body' }}">
            <i class="bi bi-patch-check-fill"></i> Bersertifikat
            <span class="badge {{ request('kategori') === 'bersertifikat' ? 'bg-white text-success' : 'bg-success-subtle text-success-emphasis' }} rounded-pill ms-1">{{ $counts['bersertifikat'] ?? 0 }}</span>
        </a>
        <a href="{{ route('status-proses.index', ['kategori' => 'kendala']) }}" class="btn btn-sm rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 {{ request('kategori') === 'kendala' ? 'btn-danger shadow-sm' : 'btn-outline-danger bg-body' }}">
            <i class="bi bi-exclamation-triangle-fill"></i> Kendala / Sengketa
            <span class="badge {{ request('kategori') === 'kendala' ? 'bg-white text-danger' : 'bg-danger-subtle text-danger-emphasis' }} rounded-pill ms-1">{{ $counts['kendala'] ?? 0 }}</span>
        </a>
        @if(isset($customCategories))
            @foreach($customCategories as $customKat)
                <a href="{{ route('status-proses.index', ['kategori' => $customKat]) }}" class="btn btn-sm rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 {{ request('kategori') === $customKat ? 'btn-dark text-white shadow-sm' : 'btn-outline-dark bg-body' }}">
                    <i class="bi bi-tag-fill"></i> {{ ucwords(str_replace('_', ' ', $customKat)) }}
                    <span class="badge {{ request('kategori') === $customKat ? 'bg-white text-dark' : 'bg-secondary-subtle text-body' }} rounded-pill ms-1">{{ $counts[$customKat] ?? 0 }}</span>
                </a>
            @endforeach
        @endif
    </div>

    <!-- Data Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-body-tertiary text-body-secondary small fw-semibold">
                    <tr>
                        <th class="ps-4 py-3">NAMA STATUS PROSES BPN</th>
                        <th class="py-3">KATEGORI UTAMA</th>
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

                            $kat = strtolower(trim($status->kategori ?? 'proses'));
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-body fs-6">{{ $status->nama_status }}</div>
                            </td>
                            <td>
                                @if($kat === 'bersertifikat')
                                    <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-2.5 py-1 text-uppercase small rounded-pill fw-semibold">
                                        <i class="bi bi-patch-check-fill me-1"></i> Bersertifikat
                                    </span>
                                @elseif($kat === 'kendala')
                                    <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle px-2.5 py-1 text-uppercase small rounded-pill fw-semibold">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Kendala / Sengketa
                                    </span>
                                @elseif($kat === 'belum_diurus')
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle px-2.5 py-1 text-uppercase small rounded-pill fw-semibold">
                                        <i class="bi bi-clock-history me-1"></i> Belum Diurus
                                    </span>
                                @elseif($kat === 'proses')
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2.5 py-1 text-uppercase small rounded-pill fw-semibold">
                                        <i class="bi bi-hourglass-split me-1"></i> Dalam Proses
                                    </span>
                                @else
                                    <span class="badge bg-dark-subtle text-dark-emphasis border border-dark-subtle px-2.5 py-1 text-uppercase small rounded-pill fw-semibold">
                                        <i class="bi bi-tag-fill me-1"></i> {{ ucwords(str_replace('_', ' ', $kat)) }}
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
                                    <button type="button" class="btn btn-body-secondary text-primary border-0 rounded-2 p-1.5 me-1" onclick="editStatus({{ json_encode($status) }})" title="Edit Status & Kategori">
                                        <i class="bi bi-pencil-square fs-6"></i>
                                    </button>
                                    <form action="{{ route('status-proses.destroy', $status->id_status) }}" method="POST" class="d-inline delete-confirm" onsubmit="return confirm('Apakah Anda yakin ingin menghapus status proses ini?')">
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
                            <td colspan="4" class="text-center py-5 text-body-secondary">
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
                    <div class="mb-3">
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
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kategori Pengelompokan <span class="text-danger">*</span></label>
                        <select id="addKategoriSelect" name="kategori" class="form-select mb-2" onchange="toggleCustomCategoryInput(this.value, 'addCustomCategoryWrapper', 'addCustomCategoryInput')" required>
                            <option value="proses">Dalam Proses (Permohonan / Pengukuran / PERTEK / PKKPR)</option>
                            <option value="bersertifikat">Bersertifikat (Selesai / Terbit Sertifikat BPN)</option>
                            <option value="kendala">Kendala / Sengketa / Bermasalah</option>
                            <option value="belum_diurus">Belum Diurus / Tahap Awal</option>
                            @if(isset($customCategories) && count($customCategories) > 0)
                                @foreach($customCategories as $cc)
                                    <option value="{{ $cc }}">{{ ucwords(str_replace('_', ' ', $cc)) }}</option>
                                @endforeach
                            @endif
                            <option value="CUSTOM">+ Tulis Kategori Kustom Baru...</option>
                        </select>
                        <div id="addCustomCategoryWrapper" class="d-none">
                            <input type="text" id="addCustomCategoryInput" name="custom_kategori" class="form-control" placeholder="Tulis nama kategori baru (contoh: Hibah / Pengadaan)...">
                        </div>
                        <div class="form-text small">Kategori ini digunakan untuk pemfilteran otomatis pada Master Aset Tanah & Laporan.</div>
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
                    <h5 class="modal-title fw-bold">Edit Status Proses & Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Status Proses BPN <span class="text-danger">*</span></label>
                        <input type="text" id="editNamaStatus" name="nama_status" class="form-control" required>
                    </div>
                    <div class="mb-3">
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
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kategori Pengelompokan <span class="text-danger">*</span></label>
                        <select id="editKategoriSelect" name="kategori" class="form-select mb-2" onchange="toggleCustomCategoryInput(this.value, 'editCustomCategoryWrapper', 'editCustomCategoryInput')" required>
                            <option value="proses">Dalam Proses (Permohonan / Pengukuran / PERTEK / PKKPR)</option>
                            <option value="bersertifikat">Bersertifikat (Selesai / Terbit Sertifikat BPN)</option>
                            <option value="kendala">Kendala / Sengketa / Bermasalah</option>
                            <option value="belum_diurus">Belum Diurus / Tahap Awal</option>
                            @if(isset($customCategories) && count($customCategories) > 0)
                                @foreach($customCategories as $cc)
                                    <option value="{{ $cc }}">{{ ucwords(str_replace('_', ' ', $cc)) }}</option>
                                @endforeach
                            @endif
                            <option value="CUSTOM">+ Tulis Kategori Kustom Baru...</option>
                        </select>
                        <div id="editCustomCategoryWrapper" class="d-none">
                            <input type="text" id="editCustomCategoryInput" name="custom_kategori" class="form-control" placeholder="Tulis nama kategori baru (contoh: Hibah / Pengadaan)...">
                        </div>
                        <div class="form-text small">Memperbarui kategori ini akan langsung mempengaruhi filter ringkasan aset.</div>
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
@endpush

@push('scripts')
<script>
    function toggleCustomCategoryInput(val, wrapperId, inputId) {
        const wrapper = document.getElementById(wrapperId);
        const input = document.getElementById(inputId);
        if (val === 'CUSTOM') {
            wrapper.classList.remove('d-none');
            input.setAttribute('required', 'required');
            input.focus();
        } else {
            wrapper.classList.add('d-none');
            input.removeAttribute('required');
            input.value = '';
        }
    }

    function editStatus(status) {
        document.getElementById('formEditStatus').action = `{{ url('master-data/status-proses') }}/${status.id_status}`;
        document.getElementById('editNamaStatus').value = status.nama_status;
        document.getElementById('editWarna').value = status.warna || 'primary';
        
        const editSelect = document.getElementById('editKategoriSelect');
        const katVal = status.kategori || 'proses';
        
        let found = false;
        for (let i = 0; i < editSelect.options.length; i++) {
            if (editSelect.options[i].value === katVal) {
                editSelect.selectedIndex = i;
                found = true;
                break;
            }
        }
        
        if (!found) {
            editSelect.value = 'CUSTOM';
            toggleCustomCategoryInput('CUSTOM', 'editCustomCategoryWrapper', 'editCustomCategoryInput');
            document.getElementById('editCustomCategoryInput').value = katVal;
        } else {
            toggleCustomCategoryInput(katVal, 'editCustomCategoryWrapper', 'editCustomCategoryInput');
        }

        const modal = new bootstrap.Modal(document.getElementById('modalEditStatus'));
        modal.show();
    }
</script>
@endpush
@endsection
