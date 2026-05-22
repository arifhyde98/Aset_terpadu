@extends('layouts.app')

@section('title', 'Manajemen Pengguna')

@section('content')
<div class="container-fluid px-0">
    
    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Manajemen Pengguna</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-navy mb-0">Manajemen Pengguna & Role</h3>
        </div>
        <div class="action-toolbar d-flex gap-2">
            <form action="{{ route('users.generate-opd-accounts') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-light border shadow-sm fw-medium d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-repeat text-primary"></i> Generate Semua Akun OPD
                </button>
            </form>
            <button type="button" class="btn btn-primary shadow-sm fw-medium d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus"></i> Tambah Pengguna
            </button>
        </div>
    </div>

    <!-- MAIN TABLE SECTION -->
    <x-table-card 
        :empty="$users->isEmpty()" 
        :collection="$users"
        emptyText="Belum ada data pengguna" 
        emptyIcon="bi-people">
        
        <x-slot:filters>
            <form action="{{ route('users.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control border-start-0 bg-white shadow-none" placeholder="Cari nama atau email...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select form-select-sm bg-white shadow-none" onchange="this.form.submit()">
                        <option value="">Semua Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->value }}" {{ request('role') === $role->value ? 'selected' : '' }}>{{ $role->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-medium">Filter</button>
                    <a href="{{ route('users.index') }}" class="btn btn-light border btn-sm bg-white" title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
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
                <th class="py-3 px-4 border-bottom-0 fw-semibold text-center" style="width: 50px;">No</th>
                <th class="py-3 border-bottom-0 fw-semibold">
                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_order' => $currentSortBy === 'name' ? $nextSortOrder : 'asc']) }}" class="text-navy text-decoration-none d-inline-flex align-items-center gap-1">
                        <span>Nama Pengguna</span>
                        @if($currentSortBy === 'name')
                            <i class="bi bi-sort-alpha-{{ $currentSortOrder === 'asc' ? 'down' : 'up' }} text-primary"></i>
                        @else
                            <i class="bi bi-arrow-down-up text-secondary opacity-50 small" style="font-size: 0.75rem;"></i>
                        @endif
                    </a>
                </th>
                <th class="py-3 border-bottom-0 fw-semibold">
                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'email', 'sort_order' => $currentSortBy === 'email' ? $nextSortOrder : 'asc']) }}" class="text-navy text-decoration-none d-inline-flex align-items-center gap-1">
                        <span>Email / Akun</span>
                        @if($currentSortBy === 'email')
                            <i class="bi bi-sort-alpha-{{ $currentSortOrder === 'asc' ? 'down' : 'up' }} text-primary"></i>
                        @else
                            <i class="bi bi-arrow-down-up text-secondary opacity-50 small" style="font-size: 0.75rem;"></i>
                        @endif
                    </a>
                </th>
                <th class="py-3 border-bottom-0 fw-semibold">
                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'role', 'sort_order' => $currentSortBy === 'role' ? $nextSortOrder : 'asc']) }}" class="text-navy text-decoration-none d-inline-flex align-items-center gap-1">
                        <span>Role</span>
                        @if($currentSortBy === 'role')
                            <i class="bi bi-sort-alpha-{{ $currentSortOrder === 'asc' ? 'down' : 'up' }} text-primary"></i>
                        @else
                            <i class="bi bi-arrow-down-up text-secondary opacity-50 small" style="font-size: 0.75rem;"></i>
                        @endif
                    </a>
                </th>
                <th class="py-3 border-bottom-0 fw-semibold">Instansi (OPD)</th>
                <th class="py-3 px-4 border-bottom-0 fw-semibold text-center" style="width: 100px;">Aksi</th>
            </tr>
        </x-slot:thead>

        @foreach($users as $index => $user)
            <tr>
                <td class="px-4 py-3 text-secondary text-center">
                    {{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}
                </td>
                <td class="py-3">
                    <div class="fw-bold text-navy">{{ $user->name }}</div>
                </td>
                <td class="py-3 text-secondary">
                    {{ $user->email }}
                </td>
                <td class="py-3">
                    @php
                        $badgeClass = match($user->role->value) {
                            'superadmin' => 'bg-danger text-danger',
                            'admin' => 'bg-primary text-primary',
                            default => 'bg-success text-success',
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }} border-opacity-25 px-2 py-1" style="background-color: rgba(var(--bs-{{ explode(' ', $badgeClass)[0] == 'bg-danger' ? 'danger' : (explode(' ', $badgeClass)[0] == 'bg-primary' ? 'primary' : 'success') }}-rgb), 0.1) !important;">
                        {{ $user->role->label() }}
                    </span>
                </td>
                <td class="py-3 text-secondary small">
                    {{ $user->opd->singkatan ?? ($user->role->value === 'opd' ? '-' : 'Akses Global') }}
                </td>
                <td class="px-4 py-3 text-center">
                    <div class="d-flex justify-content-center gap-2">
                        <form action="{{ route('users.reset-password', $user) }}" method="POST" class="d-inline reset-password-confirm">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-light border shadow-none text-warning" title="Reset Password">
                                <i class="bi bi-key-fill"></i>
                            </button>
                        </form>
                        <button type="button" class="btn btn-sm btn-light border shadow-none text-primary" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editUserModal"
                                data-id="{{ $user->id }}"
                                data-name="{{ $user->name }}"
                                data-email="{{ $user->email }}"
                                data-role="{{ $user->role->value }}"
                                data-opd="{{ $user->opd_id }}">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        @if(auth()->id() !== $user->id)
                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline delete-confirm">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light border shadow-none text-danger">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach

        <x-slot:pagination>
            {{ $users->links() }}
        </x-slot:pagination>
    </x-table-card>

</div>

@push('modals')
    <!-- ADD MODAL -->
    <x-modal id="addUserModal" title="Tambah Pengguna Baru" size="md" submitLabel="Simpan Pengguna" form="addUserForm">
        <form id="addUserForm" action="{{ route('users.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold text-dark small">Nama Lengkap</label>
                <input type="text" name="name" class="form-control" placeholder="Nama lengkap admin" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold text-dark small">Email / Username</label>
                <input type="email" name="email" class="form-control" placeholder="email@e-randis.id" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold text-dark small">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Minimal 8 karakter" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold text-dark small">Role / Hak Akses</label>
                <select name="role" id="add_role" class="form-select" required>
                    @foreach($roles as $role)
                        <option value="{{ $role->value }}">{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div id="opd_select_group" class="mb-0 d-none">
                <label class="form-label fw-semibold text-dark small">Pilih OPD</label>
                <select name="opd_id" class="form-select">
                    <option value="">-- Pilih OPD --</option>
                    @foreach($opds as $opd)
                        <option value="{{ $opd->id }}">{{ $opd->nama }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </x-modal>

    <!-- EDIT MODAL -->
    <x-modal id="editUserModal" title="Edit Data Pengguna" size="md" submitLabel="Simpan Perubahan" form="editUserForm">
        <form id="editUserForm" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold text-dark small">Nama Lengkap</label>
                <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold text-dark small">Email</label>
                <input type="email" name="email" id="edit_email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold text-dark small">Password (Kosongkan jika tidak diganti)</label>
                <input type="password" name="password" class="form-control" placeholder="Isi hanya jika ingin mengganti password">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold text-dark small">Role / Hak Akses</label>
                <select name="role" id="edit_role" class="form-select" required>
                    @foreach($roles as $role)
                        <option value="{{ $role->value }}">{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div id="edit_opd_select_group" class="mb-0 d-none">
                <label class="form-label fw-semibold text-dark small">Pilih OPD</label>
                <select name="opd_id" id="edit_opd_id" class="form-select">
                    <option value="">-- Pilih OPD --</option>
                    @foreach($opds as $opd)
                        <option value="{{ $opd->id }}">{{ $opd->nama }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </x-modal>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Notifikasi Reset Password
        @if(session('reset_password'))
            Swal.fire({
                title: 'Password Berhasil Di-reset!',
                html: `
                    <div class="text-start p-3 bg-light rounded-3 border">
                        <div class="mb-2"><strong>Nama:</strong> {{ session('reset_password')['name'] }}</div>
                        <div class="mb-2"><strong>Email/User:</strong> <code class="bg-white px-2 py-1 border rounded">{{ session('reset_password')['email'] }}</code></div>
                        <div class="mb-0"><strong>Password Baru:</strong> <code class="bg-white px-2 py-1 border rounded">{{ session('reset_password')['password'] }}</code></div>
                    </div>
                    <div class="alert alert-warning mt-3 small mb-0 text-start">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Harap berikan password baru ini kepada pengguna yang bersangkutan.
                    </div>
                `,
                icon: 'success',
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#1e40af'
            });
        @endif

        // Toggle OPD Select based on Role
        const addRoleSelect = document.getElementById('add_role');
        const addOpdGroup = document.getElementById('opd_select_group');
        const editRoleSelect = document.getElementById('edit_role');
        const editOpdGroup = document.getElementById('edit_opd_select_group');

        function toggleOpdSelect(roleValue, targetGroup) {
            if (roleValue === 'opd') {
                targetGroup.classList.remove('d-none');
            } else {
                targetGroup.classList.add('d-none');
            }
        }

        addRoleSelect.addEventListener('change', (e) => toggleOpdSelect(e.target.value, addOpdGroup));
        editRoleSelect.addEventListener('change', (e) => toggleOpdSelect(e.target.value, editOpdGroup));

        // Bulk Generate Confirmation
        const bulkForm = document.querySelector('form[action*="generate-opd-accounts"]');
        if (bulkForm) {
            bulkForm.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Generate Semua Akun?',
                    text: "Sistem akan membuat akun admin otomatis untuk seluruh OPD yang belum memiliki akun. Lanjutkan?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#1e40af',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Generate!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        }

        // Reset Password Confirmation
        const resetForms = document.querySelectorAll('.reset-password-confirm');
        resetForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Reset Password?',
                    text: "Password akan diubah menjadi string acak baru. Lanjutkan?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f59e0b',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Reset!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        });

        const editModal = document.getElementById('editUserModal');
        if (editModal) {
            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const email = button.getAttribute('data-email');
                const role = button.getAttribute('data-role');
                const opdId = button.getAttribute('data-opd');

                const form = document.getElementById('editUserForm');
                const routeTemplate = "{{ route('users.update', ':id') }}";
                form.action = routeTemplate.replace(':id', id);

                document.getElementById('edit_name').value = name;
                document.getElementById('edit_email').value = email;
                document.getElementById('edit_role').value = role;
                document.getElementById('edit_opd_id').value = opdId || '';
                
                toggleOpdSelect(role, editOpdGroup);
            });
        }
    });
</script>
@endpush
@endsection
