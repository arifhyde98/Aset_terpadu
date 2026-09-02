@extends('layouts.app')

@section('title', 'Tambah Jenis Arsip & Form Builder - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Breadcrumbs -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dynamic.types.index') }}" class="text-decoration-none text-secondary">Master Jenis Arsip</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Tambah Kategori Baru</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-plus-circle-fill text-primary"></i> Buat Jenis Arsip & Rancang Formulir
            </h4>
        </div>
        <div class="action-toolbar d-flex flex-wrap gap-2">
            <a href="{{ route('elabel.dynamic.types.index') }}" class="btn btn-outline-secondary shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Periksa kembali inputan Anda:</strong>
            <ul class="mb-0 mt-2 small">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('elabel.dynamic.types.store') }}" method="POST" id="typeForm">
        @csrf

        <div class="row g-4">
            <!-- 1. Informasi Master Kategori -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="fw-bold text-navy mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-info-circle text-primary"></i> Identitas Kategori Arsip
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Kode Arsip <span class="text-danger">*</span></label>
                            <input type="text" name="kode" value="{{ old('kode') }}" class="form-control text-uppercase font-monospace" placeholder="Cth: IMB, KONTRAK, SPJ, SK" required>
                            <div class="form-text small">Maksimal 20 karakter, huruf/angka tanpa spasi.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Nama Jenis Arsip <span class="text-danger">*</span></label>
                            <input type="text" name="nama" value="{{ old('nama') }}" class="form-control" placeholder="Cth: Dokumen IMB & PBG Bangunan" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Deskripsi Singkat</label>
                            <textarea name="deskripsi" class="form-control" rows="3" placeholder="Uraian berkas atau aturan penyimpanan kategori ini...">{{ old('deskripsi') }}</textarea>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold text-dark">Ikon Bootstrap</label>
                                <select name="icon" class="form-select">
                                    <option value="bi-folder2" {{ old('icon') == 'bi-folder2' ? 'selected' : '' }}>📁 Folder (bi-folder2)</option>
                                    <option value="bi-file-earmark-text" {{ old('icon') == 'bi-file-earmark-text' ? 'selected' : '' }}>📄 Dokumen Teks</option>
                                    <option value="bi-building" {{ old('icon') == 'bi-building' ? 'selected' : '' }}>🏢 IMB / Gedung</option>
                                    <option value="bi-briefcase" {{ old('icon') == 'bi-briefcase' ? 'selected' : '' }}>💼 Kontrak / Pengadaan</option>
                                    <option value="bi-receipt" {{ old('icon') == 'bi-receipt' ? 'selected' : '' }}>🧾 Kuitansi / SPJ Keuangan</option>
                                    <option value="bi-people" {{ old('icon') == 'bi-people' ? 'selected' : '' }}>👥 Kepegawaian / SK</option>
                                    <option value="bi-patch-check" {{ old('icon') == 'bi-patch-check' ? 'selected' : '' }}>🏅 Ijazah / Sertifikasi</option>
                                    <option value="bi-shield-check" {{ old('icon') == 'bi-shield-check' ? 'selected' : '' }}>🛡️ Dokumen Rahasia / SKP</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold text-dark">Warna Tema</label>
                                <select name="warna_badge" class="form-select">
                                    <option value="primary" {{ old('warna_badge') == 'primary' ? 'selected' : '' }}>Biru (Primary)</option>
                                    <option value="success" {{ old('warna_badge') == 'success' ? 'selected' : '' }}>Hijau (Success)</option>
                                    <option value="warning" {{ old('warna_badge') == 'warning' ? 'selected' : '' }}>Kuning/Oranye</option>
                                    <option value="info" {{ old('warna_badge') == 'info' ? 'selected' : '' }}>Biru Muda (Info)</option>
                                    <option value="danger" {{ old('warna_badge') == 'danger' ? 'selected' : '' }}>Merah (Danger)</option>
                                    <option value="dark" {{ old('warna_badge') == 'dark' ? 'selected' : '' }}>Hitam / Gelap</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-check form-switch pt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActiveSwitch" checked>
                            <label class="form-check-label fw-semibold" for="isActiveSwitch">Kategori Aktif Digunakan</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Interactive Visual Form Builder -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h6 class="fw-bold text-navy mb-0 d-flex align-items-center gap-2">
                                <i class="bi bi-ui-checks-grid text-success"></i> Visual Form Builder (Atribut Kustom)
                            </h6>
                            <span class="text-muted small">Tentukan kolom-kolom formulir yang akan diisi saat menginput arsip tipe ini.</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-success fw-medium d-flex align-items-center gap-1" id="btnAddField">
                            <i class="bi bi-plus-circle"></i> Tambah Field Baru
                        </button>
                    </div>
                    <div class="card-body p-4">

                        <!-- Default Standard Fields Notice -->
                        <div class="alert alert-light border rounded-3 p-3 mb-4 d-flex align-items-start gap-3">
                            <i class="bi bi-layers text-primary fs-4"></i>
                            <div>
                                <strong class="d-block text-navy">Kolom Standar Otomatis:</strong>
                                <span class="text-secondary small">Setiap arsip sudah otomatis memiliki kolom: <em>Nomor Berkas/Dokumen, Nama/Uraian Dokumen, Tahun, Unit OPD, Box Fisik, Status, Berkas PDF Utama,</em> dan <em>Catatan</em>. Tambahkan field khusus tambahan di bawah ini jika diperlukan.</span>
                            </div>
                        </div>

                        <!-- Dynamic Fields Container -->
                        <div id="fieldsContainer" class="d-flex flex-column gap-3">
                            <!-- Fields will be dynamically added here -->
                        </div>

                        <div id="emptyFieldsNotice" class="text-center py-4 border rounded-3 bg-light-subtle">
                            <i class="bi bi-input-cursor-text text-secondary fs-3 d-block mb-2"></i>
                            <span class="text-muted small">Belum ada field kustom tambahan. Klik tombol <strong>+ Tambah Field Baru</strong> di atas untuk merancang form.</span>
                        </div>

                    </div>
                    <div class="card-footer bg-white border-top py-3 px-4 d-flex justify-content-end gap-2">
                        <a href="{{ route('elabel.dynamic.types.index') }}" class="btn btn-light border px-4">Batal</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-check-lg me-1"></i> Simpan Kategori & Formulir
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

</div>

<!-- Template Field Builder Row -->
<template id="fieldTemplate">
    <div class="field-row card border rounded-3 p-3 bg-white shadow-xs position-relative">
        <div class="row g-2 align-items-start">
            <div class="col-md-3">
                <label class="form-label text-xs fw-bold text-secondary text-uppercase mb-1">Nama Variabel (Key)</label>
                <input type="text" name="schema_fields[INDEX][name]" class="form-control form-control-sm font-monospace field-name-input" placeholder="cth: nilai_kontrak" required>
            </div>
            <div class="col-md-3">
                <label class="form-label text-xs fw-bold text-secondary text-uppercase mb-1">Label Input</label>
                <input type="text" name="schema_fields[INDEX][label]" class="form-control form-control-sm field-label-input" placeholder="cth: Nilai Kontrak (Rp)" required>
            </div>
            <div class="col-md-2">
                <label class="form-label text-xs fw-bold text-secondary text-uppercase mb-1">Tipe Input</label>
                <select name="schema_fields[INDEX][type]" class="form-select form-select-sm field-type-select">
                    <option value="text">Teks Singkat</option>
                    <option value="number">Angka / Nilai</option>
                    <option value="date">Tanggal</option>
                    <option value="select">Pilihan Dropdown</option>
                    <option value="textarea">Area Teks (Panjang)</option>
                    <option value="file">Berkas Lampiran</option>
                </select>
            </div>
            <div class="col-md-3 options-container d-none">
                <label class="form-label text-xs fw-bold text-secondary text-uppercase mb-1">Pilihan Dropdown (Pisahkan Koma)</label>
                <input type="text" name="schema_fields[INDEX][options]" class="form-control form-control-sm" placeholder="Opsi 1, Opsi 2, Opsi 3">
            </div>
            <div class="col-md-1 text-end pt-3">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-field" title="Hapus Field">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
        <div class="row g-2 mt-2 pt-2 border-top">
            <div class="col-md-6">
                <input type="text" name="schema_fields[INDEX][placeholder]" class="form-control form-control-sm" placeholder="Placeholder panduan pengisian (opsional)...">
            </div>
            <div class="col-md-4">
                <input type="text" name="schema_fields[INDEX][help_text]" class="form-control form-control-sm" placeholder="Teks bantuan kecil di bawah input...">
            </div>
            <div class="col-md-2 d-flex align-items-center">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="schema_fields[INDEX][required]" value="1" id="req_INDEX">
                    <label class="form-check-label text-xs fw-semibold" for="req_INDEX">Wajib Isi</label>
                </div>
            </div>
        </div>
    </div>
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let fieldIndex = 0;
    const container = document.getElementById('fieldsContainer');
    const emptyNotice = document.getElementById('emptyFieldsNotice');
    const btnAdd = document.getElementById('btnAddField');
    const template = document.getElementById('fieldTemplate');

    function checkEmpty() {
        if (container.children.length === 0) {
            emptyNotice.classList.remove('d-none');
        } else {
            emptyNotice.classList.add('d-none');
        }
    }

    function addField(data = null) {
        const index = fieldIndex++;
        let html = template.innerHTML.replaceAll('INDEX', index);
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        const row = wrapper.firstElementChild;

        // Event listener for type change to show options input for 'select'
        const typeSelect = row.querySelector('.field-type-select');
        const optionsCol = row.querySelector('.options-container');
        typeSelect.addEventListener('change', function() {
            if (this.value === 'select') {
                optionsCol.classList.remove('d-none');
            } else {
                optionsCol.classList.add('d-none');
            }
        });

        // Auto convert label to snake_case variable name if name is empty
        const labelInput = row.querySelector('.field-label-input');
        const nameInput = row.querySelector('.field-name-input');
        labelInput.addEventListener('input', function() {
            if (!nameInput.dataset.manual) {
                nameInput.value = this.value.toLowerCase().trim().replace(/[^a-z0-9]/g, '_').replace(/_+/g, '_');
            }
        });
        nameInput.addEventListener('input', function() {
            this.dataset.manual = "1";
        });

        // Remove button
        row.querySelector('.btn-remove-field').addEventListener('click', function() {
            row.remove();
            checkEmpty();
        });

        container.appendChild(row);
        checkEmpty();
    }

    btnAdd.addEventListener('click', function() {
        addField();
    });

    // Add 2 default sample fields for quick start
    addField();
    const firstRow = container.firstElementChild;
    if (firstRow) {
        firstRow.querySelector('.field-label-input').value = 'Uraian Khusus / Peruntukan';
        firstRow.querySelector('.field-name-input').value = 'peruntukan';
    }
});
</script>
@endpush

@endsection
