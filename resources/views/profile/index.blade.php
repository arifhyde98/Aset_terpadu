@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
<div class="container-fluid px-0">
    
    <!-- PAGE HEADER -->
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Profil Saya</li>
            </ol>
        </nav>
        <h3 class="fw-bold text-navy mb-0">Pengaturan Profil & Keamanan</h3>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <!-- PROFILE CARD -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="bg-navy p-4 text-center py-5" style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);">
                    <div class="position-relative d-inline-block">
                        <img src="{{ $user->avatar_url }}" 
                             alt="{{ $user->name }}" 
                             class="rounded-circle border border-4 border-white shadow-sm bg-white" 
                             style="width: 120px; height: 120px; object-fit: cover;">
                        <span class="position-absolute bottom-0 end-0 bg-success border border-2 border-white rounded-circle p-2" title="Aktif"></span>
                    </div>
                    <h5 class="text-white mt-3 mb-1 fw-bold">{{ $user->name }}</h5>
                    <p class="text-white-50 small mb-0">{{ $user->role->label() }}</p>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-light p-2 rounded-3 me-3">
                            <i class="bi bi-envelope text-primary fs-5"></i>
                        </div>
                        <div>
                            <small class="text-secondary d-block">Email Utama</small>
                            <span class="fw-medium">{{ $user->email }}</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-0">
                        <div class="bg-light p-2 rounded-3 me-3">
                            <i class="bi bi-building text-primary fs-5"></i>
                        </div>
                        <div>
                            <small class="text-secondary d-block">Instansi / Unit</small>
                            <span class="fw-medium">{{ $user->opd->nama ?? 'Akses Global' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <!-- EDIT FORM -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-0 p-4 pb-0">
                    <h5 class="fw-bold text-navy mb-0">Informasi Akun</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row g-4">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold text-dark small">Ganti Foto Profil</label>
                                <div class="d-flex align-items-center gap-3 mt-1">
                                    <div id="avatar-preview-container" style="width: 60px; height: 60px;">
                                        <img src="{{ $user->avatar_url }}" id="avatar-preview" class="rounded-circle shadow-sm border" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <div class="flex-grow-1">
                                        <input type="file" name="avatar" id="avatar-input" class="form-control form-control-sm" accept="image/png, image/jpeg, image/jpg">
                                        <div class="form-text small text-secondary mt-1">PNG, JPG, max 2MB.</div>
                                    </div>
                                </div>
                                @error('avatar') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark small">Nama Lengkap</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-secondary"></i></span>
                                    <input type="text" name="name" class="form-control border-start-0 bg-white" value="{{ old('name', $user->name) }}" required>
                                </div>
                                @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark small">Alamat Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-secondary"></i></span>
                                    <input type="email" name="email" class="form-control border-start-0 bg-white" value="{{ old('email', $user->email) }}" required>
                                </div>
                                @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <hr class="my-2 text-secondary opacity-25">
                            
                            <div class="col-md-12 mb-0">
                                <div class="alert alert-light border-0 shadow-none py-2 px-0 mb-0">
                                    <h6 class="fw-bold text-navy mb-1"><i class="bi bi-shield-lock me-2"></i>Keamanan Akun</h6>
                                    <p class="text-secondary small mb-0">Kosongkan jika Anda tidak ingin mengganti kata sandi.</p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark small">Kata Sandi Baru</label>
                                <input type="password" name="password" class="form-control" placeholder="Minimal 8 karakter">
                                @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark small">Konfirmasi Kata Sandi</label>
                                <input type="password" name="password_confirmation" class="form-control" placeholder="Ulangi kata sandi baru">
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const avatarInput = document.getElementById('avatar-input');
        const avatarPreview = document.getElementById('avatar-preview');

        if (avatarInput) {
            avatarInput.addEventListener('change', function() {
                const file = this.files[0];
                if (!file) return;

                // Jika file lebih besar dari 2MB, perkecil ukurannya
                if (file.size > 2 * 1024 * 1024) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = new Image();
                        img.onload = function() {
                            const canvas = document.createElement('canvas');
                            const ctx = canvas.getContext('2d');
                            
                            // Tentukan ukuran maksimal
                            const MAX_WIDTH = 800;
                            const MAX_HEIGHT = 800;
                            let width = img.width;
                            let height = img.height;
                            
                            if (width > height) {
                                if (width > MAX_WIDTH) {
                                    height *= MAX_WIDTH / width;
                                    width = MAX_WIDTH;
                                }
                            } else {
                                if (height > MAX_HEIGHT) {
                                    width *= MAX_HEIGHT / height;
                                    height = MAX_HEIGHT;
                                }
                            }
                            
                            canvas.width = width;
                            canvas.height = height;
                            ctx.drawImage(img, 0, 0, width, height);
                            
                            // Konversi ke JPEG dengan kualitas 80% agar ukuran kecil
                            const dataUrl = canvas.toDataURL('image/jpeg', 0.8);
                            avatarPreview.src = dataUrl;
                            
                            // Ganti file input dengan file yang sudah dikompresi
                            fetch(dataUrl)
                                .then(res => res.blob())
                                .then(blob => {
                                    const newFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".jpg", { type: 'image/jpeg' });
                                    const dataTransfer = new DataTransfer();
                                    dataTransfer.items.add(newFile);
                                    avatarInput.files = dataTransfer.files;
                                });
                        };
                        img.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                } else {
                    // Jika kurang dari 2MB, tampilkan preview langsung
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        avatarPreview.src = e.target.result;
                    }
                    reader.readAsDataURL(file);
                }
            });
        }
    });
</script>
@endpush
@endsection
