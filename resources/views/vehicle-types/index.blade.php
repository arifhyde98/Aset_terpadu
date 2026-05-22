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
        <div class="d-flex gap-2">
            <form action="{{ route('vehicle-types.cleanup') }}" method="POST" class="delete-confirm">
                @csrf
                <button type="submit" class="btn btn-outline-danger rounded-3 shadow-sm d-flex align-items-center gap-2">
                    <i class="bi bi-eraser"></i> Bersihkan Jenis Kosong
                </button>
            </form>
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
                <th class="py-3 px-4 border-bottom-0 fw-semibold" style="width: 50px;">No</th>
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
                <td class="px-4 py-3 text-secondary">{{ $loop->iteration }}</td>
                <td class="py-3">
                    <div class="fw-bold text-navy">{{ $type->name }}</div>
                </td>
                <td class="py-3 text-secondary small d-none d-md-table-cell">
                    {{ $type->description ?: '-' }}
                </td>
                <td class="py-3 text-center">
                    <span class="badge bg-light text-navy border px-3 py-2 rounded-pill fw-medium">
                        {{ $type->vehicles_count ?? $type->vehicles()->count() }} Unit
                    </span>
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
                        @if(($type->vehicles_count ?? $type->vehicles()->count()) == 0)
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
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
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
    });
</script>
@endpush
@endsection
