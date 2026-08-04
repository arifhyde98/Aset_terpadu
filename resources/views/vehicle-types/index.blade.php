@extends('layouts.app')

@section('title', 'Jenis Kendaraan')

@section('content')
<div class="container-fluid px-0">
    
    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
        <div class="mb-3 mb-md-0">
            <h3 class="fw-bold text-navy mb-1">Daftar Jenis Kendaraan</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('master-data.index') }}" class="text-decoration-none text-secondary">Master Data</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Jenis Kendaraan</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <form action="{{ route('vehicle-types.cleanup') }}" method="POST" class="delete-confirm">
                @csrf
                <button type="submit" class="btn btn-outline-danger rounded-3 shadow-sm d-flex align-items-center gap-2">
                    <i class="bi bi-eraser"></i> Bersihkan Jenis Kosong
                </button>
            </form>
            <button type="button" class="btn btn-outline-warning rounded-3 shadow-sm d-flex align-items-center gap-2 d-none" id="btnMergeSelected">
                <i class="bi bi-intersect"></i> Gabungkan Jenis (<span id="mergeCount">0</span>)
            </button>
            <button type="button" class="btn btn-primary rounded-3 shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addVehicleTypeModal">
                <i class="bi bi-plus-lg"></i> Tambah Jenis
            </button>
        </div>
    </div>



    <!-- DATA TABLE -->
    <x-table-card 
        :empty="$types->isEmpty()" 
        :collection="$types"
        emptyText="Belum ada data jenis kendaraan" 
        emptyIcon="bi-grid">
        
        @php
            $currentSortBy = request('sort_by');
            $currentSortOrder = request('sort_order', 'asc');
            $nextSortOrder = $currentSortOrder === 'asc' ? 'desc' : 'asc';
        @endphp

        <x-slot:thead>
            <tr>
                <th class="py-3 px-3 border-bottom-0 fw-semibold text-center" style="width: 40px;">
                    <input type="checkbox" class="form-check-input" id="checkAll" title="Pilih Semua">
                </th>
                <th class="py-3 border-bottom-0 fw-semibold" style="width: 50px;">No</th>
                <th class="py-3 border-bottom-0 fw-semibold">
                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_order' => $currentSortBy === 'name' ? $nextSortOrder : 'asc']) }}" class="text-navy text-decoration-none d-inline-flex align-items-center gap-1">
                        <span>Nama Jenis</span>
                        @if($currentSortBy === 'name')
                            <i class="bi bi-sort-alpha-{{ $currentSortOrder === 'asc' ? 'down' : 'up' }} text-primary"></i>
                        @else
                            <i class="bi bi-arrow-down-up text-secondary opacity-50 small" style="font-size: 0.75rem;"></i>
                        @endif
                    </a>
                </th>
                <th class="py-3 border-bottom-0 fw-semibold d-none d-md-table-cell">Deskripsi</th>
                <th class="py-3 border-bottom-0 fw-semibold text-center">
                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'vehicles_count', 'sort_order' => $currentSortBy === 'vehicles_count' ? $nextSortOrder : 'asc']) }}" class="text-navy text-decoration-none d-inline-flex align-items-center gap-1 justify-content-center">
                        <span>Total Kendaraan</span>
                        @if($currentSortBy === 'vehicles_count')
                            <i class="bi bi-sort-numeric-{{ $currentSortOrder === 'asc' ? 'down' : 'up' }} text-primary"></i>
                        @else
                            <i class="bi bi-arrow-down-up text-secondary opacity-50 small" style="font-size: 0.75rem;"></i>
                        @endif
                    </a>
                </th>
                <th class="py-3 px-4 border-bottom-0 fw-semibold text-end">Aksi</th>
            </tr>
        </x-slot:thead>

        @foreach($types as $type)
            <tr>
                <td class="px-3 py-3 text-center">
                    <input type="checkbox" class="form-check-input merge-check" value="{{ $type->id }}" data-name="{{ $type->name }}" data-count="{{ ($type->vehicles_count ?? 0) + ($type->ebmd_vehicles_count ?? 0) }}">
                </td>
                <td class="py-3 text-secondary">{{ $loop->iteration }}</td>
                <td class="py-3">
                    <div class="fw-bold text-navy">{{ $type->name }}</div>
                </td>
                <td class="py-3 text-secondary small d-none d-md-table-cell">
                    {{ $type->description ?: '-' }}
                </td>
                <td class="py-3 text-center">
                    <div class="d-flex justify-content-center gap-1 flex-wrap">
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 rounded-pill fw-medium" title="Data Real">
                            <i class="bi bi-car-front-fill" style="font-size: 0.65rem;"></i> {{ $type->vehicles_count ?? 0 }}
                        </span>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 rounded-pill fw-medium" title="Data e-BMD">
                            <i class="bi bi-file-earmark-spreadsheet" style="font-size: 0.65rem;"></i> {{ $type->ebmd_vehicles_count ?? 0 }}
                        </span>
                    </div>
                </td>
                <td class="px-4 py-3 text-end">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-sm btn-light border text-primary rounded-3" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editVehicleTypeModal"
                                data-id="{{ $type->id }}"
                                data-name="{{ $type->name }}"
                                data-description="{{ $type->description }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        @if(($type->vehicles_count ?? 0) + ($type->ebmd_vehicles_count ?? 0) == 0)
                            <form action="{{ route('vehicle-types.destroy', $type) }}" method="POST" class="delete-confirm">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light border text-danger rounded-3" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        @else
                            <button class="btn btn-sm btn-light border text-muted rounded-3" disabled title="Tidak bisa dihapus karena masih ada unit">
                                <i class="bi bi-trash"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    </x-table-card>
</div>

@push('modals')
    <!-- ADD MODAL -->
    <x-modal id="addVehicleTypeModal" title="Tambah Jenis Kendaraan Baru" size="md" submitLabel="Simpan Data" form="addVehicleTypeForm">
        <form id="addVehicleTypeForm" action="{{ route('vehicle-types.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold text-dark small">Nama Jenis Kendaraan</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Contoh: Roda 4 (Jeep)" value="{{ old('name') }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-0">
                <label class="form-label fw-semibold text-dark small">Deskripsi Singkat</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Keterangan tambahan...">{{ old('description') }}</textarea>
            </div>
        </form>
    </x-modal>

    <!-- EDIT MODAL -->
    <x-modal id="editVehicleTypeModal" title="Edit Data Jenis Kendaraan" size="md" submitLabel="Simpan Perubahan" form="editVehicleTypeForm">
        <form id="editVehicleTypeForm" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold text-dark small">Nama Jenis Kendaraan</label>
                <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
            <div class="mb-0">
                <label class="form-label fw-semibold text-dark small">Deskripsi Singkat</label>
                <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
            </div>
        </form>
    </x-modal>

    <!-- MERGE MODAL -->
    <div class="modal fade" id="mergeVehicleTypeModal" tabindex="-1" aria-labelledby="mergeVehicleTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-navy d-flex align-items-center gap-2" id="mergeVehicleTypeModalLabel">
                        <i class="bi bi-intersect text-warning"></i> Gabungkan Jenis Kendaraan
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('vehicle-types.merge') }}" method="POST" id="mergeForm">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="alert alert-warning border-0 bg-warning bg-opacity-10 d-flex align-items-start mb-4 rounded-3">
                            <div class="fs-4 me-3 text-warning"><i class="bi bi-exclamation-triangle-fill"></i></div>
                            <div>
                                <h6 class="fw-bold mb-1" style="font-size: 0.9rem;">Perhatian — Aksi Tidak Dapat Dibatalkan</h6>
                                <p class="mb-0 small text-secondary">
                                    Seluruh kendaraan dari jenis yang dipilih akan <strong>dipindahkan</strong> ke jenis tujuan, 
                                    lalu jenis sumber akan <strong>dihapus</strong> secara permanen.
                                </p>
                            </div>
                        </div>

                        <!-- Daftar jenis yang akan di-merge -->
                        <h6 class="fw-bold text-navy mb-3"><i class="bi bi-list-check me-1"></i> Jenis yang Akan Digabungkan:</h6>
                        <div class="border rounded-3 p-3 mb-4 bg-light" id="mergeSourceList" style="max-height: 200px; overflow-y: auto;">
                            <!-- Diisi via JavaScript -->
                        </div>

                        <!-- Pilih jenis tujuan -->
                        <h6 class="fw-bold text-navy mb-2"><i class="bi bi-bullseye me-1"></i> Gabungkan Ke Jenis Tujuan:</h6>
                        <select name="target_id" id="mergeTargetSelect" class="form-select form-select-lg shadow-none" required>
                            <option value="">-- Pilih Jenis Tujuan --</option>
                        </select>
                        <div class="small text-secondary mt-2">
                            <i class="bi bi-info-circle me-1"></i> Semua kendaraan akan dipindahkan ke jenis yang dipilih di atas.
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
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // === EDIT MODAL ===
        const editModal = document.getElementById('editVehicleTypeModal');
        if (editModal) {
            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const description = button.getAttribute('data-description');

                const form = document.getElementById('editVehicleTypeForm');
                const routeTemplate = "{{ route('vehicle-types.update', ':id') }}";
                form.action = routeTemplate.replace(':id', id);

                document.getElementById('edit_name').value = name || '';
                document.getElementById('edit_description').value = description || '';
            });
        }

        // === MERGE CHECKBOX LOGIC ===
        const checkAll = document.getElementById('checkAll');
        const mergeChecks = document.querySelectorAll('.merge-check');
        const btnMerge = document.getElementById('btnMergeSelected');
        const mergeCount = document.getElementById('mergeCount');

        function updateMergeButton() {
            const checked = document.querySelectorAll('.merge-check:checked');
            const count = checked.length;
            mergeCount.textContent = count;

            if (count >= 2) {
                btnMerge.classList.remove('d-none');
            } else {
                btnMerge.classList.add('d-none');
            }
        }

        if (checkAll) {
            checkAll.addEventListener('change', function () {
                mergeChecks.forEach(cb => cb.checked = this.checked);
                updateMergeButton();
            });
        }

        mergeChecks.forEach(cb => {
            cb.addEventListener('change', updateMergeButton);
        });

        // === MERGE MODAL OPEN ===
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
                targetSelect.innerHTML = '<option value="">-- Pilih Jenis Tujuan --</option>';

                checked.forEach(cb => {
                    const name = cb.getAttribute('data-name');
                    const count = cb.getAttribute('data-count');
                    const id = cb.value;

                    // Tampilkan badge di daftar sumber
                    sourceList.innerHTML += `
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-medium text-dark"><i class="bi bi-tag text-secondary me-1"></i> ${name}</span>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2">${count} unit</span>
                        </div>
                    `;

                    // Tambah sebagai opsi target
                    targetSelect.innerHTML += `<option value="${id}">${name} (${count} unit)</option>`;

                    // Tambah hidden input source_ids
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'source_ids[]';
                    hidden.value = id;
                    form.appendChild(hidden);
                });

                // Buka modal
                const modal = new bootstrap.Modal(document.getElementById('mergeVehicleTypeModal'));
                modal.show();
            });
        }
    });
</script>
@endpush
@endsection
