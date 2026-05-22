@extends('layouts.app')

@section('title', 'Data OPD')

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
        <div class="action-toolbar d-flex gap-2">
            <form action="{{ route('opds.truncate') }}" method="POST" class="d-inline truncate-confirm">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-light border shadow-sm fw-medium d-flex align-items-center gap-2">
                    <i class="bi bi-trash3 text-danger"></i> Kosongkan Master OPD
                </button>
            </form>
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
                <th class="py-3 px-4 border-bottom-0 fw-semibold" style="width: 50px;">No</th>
                <th class="py-3 border-bottom-0 fw-semibold">
                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'nama', 'sort_order' => $currentSortBy === 'nama' ? $nextSortOrder : 'asc']) }}" class="text-navy text-decoration-none d-inline-flex align-items-center gap-1">
                        <span>Nama Instansi / OPD</span>
                        @if($currentSortBy === 'nama')
                            <i class="bi bi-sort-alpha-{{ $currentSortOrder === 'asc' ? 'down' : 'up' }} text-primary"></i>
                        @else
                            <i class="bi bi-arrow-down-up text-secondary opacity-50 small" style="font-size: 0.75rem;"></i>
                        @endif
                    </a>
                </th>
                <th class="py-3 border-bottom-0 fw-semibold">
                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'singkatan', 'sort_order' => $currentSortBy === 'singkatan' ? $nextSortOrder : 'asc']) }}" class="text-navy text-decoration-none d-inline-flex align-items-center gap-1">
                        <span>Singkatan</span>
                        @if($currentSortBy === 'singkatan')
                            <i class="bi bi-sort-alpha-{{ $currentSortOrder === 'asc' ? 'down' : 'up' }} text-primary"></i>
                        @else
                            <i class="bi bi-arrow-down-up text-secondary opacity-50 small" style="font-size: 0.75rem;"></i>
                        @endif
                    </a>
                </th>
                <th class="py-3 border-bottom-0 fw-semibold">Akun Admin</th>
                <th class="py-3 border-bottom-0 fw-semibold d-none d-md-table-cell">Alamat</th>
                <th class="py-3 px-4 border-bottom-0 fw-semibold text-center" style="width: 100px;">Aksi</th>
            </tr>
        </x-slot:thead>

        @foreach($opds as $index => $opd)
            <tr>
                <td class="px-4 py-3 text-secondary text-center">
                    {{ ($opds->currentPage() - 1) * $opds->perPage() + $loop->iteration }}
                </td>
                <td class="py-3">
                    <div class="fw-bold text-navy">{{ $opd->nama }}</div>
                </td>
                <td class="py-3">
                    <span class="badge bg-light text-primary border border-primary border-opacity-25 px-2 py-1">{{ $opd->singkatan ?? '-' }}</span>
                </td>
                <td class="py-3">
                    @if($opd->user)
                        <div class="small fw-medium text-dark">{{ $opd->user->email }}</div>
                        <span class="badge bg-success-subtle text-success small border-0 py-0" style="font-size: 0.65rem;">AKTIF</span>
                    @else
                        <span class="text-secondary small italic">Tidak ada akun</span>
                    @endif
                </td>
                <td class="py-3 text-secondary small d-none d-md-table-cell">
                    {{ Str::limit($opd->alamat ?? '-', 50) }}
                </td>
                <td class="px-4 py-3 text-center">
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-sm btn-light border shadow-none text-primary" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editOpdModal"
                                data-id="{{ $opd->id }}"
                                data-nama="{{ $opd->nama }}"
                                data-singkatan="{{ $opd->singkatan }}"
                                data-alamat="{{ $opd->alamat }}">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <form action="{{ route('opds.destroy', $opd) }}" method="POST" class="d-inline delete-confirm">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light border shadow-none text-danger">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </form>
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
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
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

        const editModal = document.getElementById('editOpdModal');
        if (editModal) {
            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const nama = button.getAttribute('data-nama');
                const singkatan = button.getAttribute('data-singkatan');
                const alamat = button.getAttribute('data-alamat');

                const form = document.getElementById('editOpdForm');
                // Use a safe way to construct the URL
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
                    title: 'Kosongkan Master OPD?',
                    text: "Seluruh data instansi DAN AKUN ADMIN terkait akan dihapus permanen. Lanjutkan?",
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
    });
</script>
@endpush
@endsection
