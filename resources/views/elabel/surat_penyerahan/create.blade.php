@extends('layouts.app')

@section('title', 'Tambah Surat Penyerahan - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.surat-penyerahan.index') }}" class="text-decoration-none text-secondary">Surat Penyerahan</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Tambah Surat</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0">Tambah Surat Penyerahan Baru</h4>
        </div>
        <div>
            <a href="{{ route('elabel.surat-penyerahan.index') }}" class="btn btn-light border fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> Mohon periksa kembali inputan anda:
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <form action="{{ route('elabel.surat-penyerahan.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">No. Surat <span class="text-danger">*</span></label>
                        <input type="text" name="no_surat" value="{{ old('no_surat') }}" class="form-control" placeholder="593/001/BPKAD/2026" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">NIBAR</label>
                        <input type="text" name="nibar" value="{{ old('nibar') }}" class="form-control" placeholder="NBR-XXXXX">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Jenis Penyerahan</label>
                        <input type="text" name="jenis_penyerahan" value="{{ old('jenis_penyerahan', 'Hibah') }}" class="form-control" placeholder="Hibah / Serah Terima">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Pemberi Hibah / Instansi</label>
                        <input type="text" name="pemberi_hibah" value="{{ old('pemberi_hibah') }}" class="form-control" placeholder="Nama Pemberi Hibah">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Status Penggunaan</label>
                        <input type="text" name="status_penggunaan" value="{{ old('status_penggunaan') }}" class="form-control" placeholder="Dipakai Kantor">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Spesifikasi / Peruntukan</label>
                        <input type="text" name="spesifikasi" value="{{ old('spesifikasi') }}" class="form-control" placeholder="Bangunan Kantor / Lapangan">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Luas (m²)</label>
                        <input type="number" step="0.01" name="luas" value="{{ old('luas') }}" class="form-control" placeholder="250.50">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Tanggal Perolehan</label>
                        <input type="date" name="tanggal_perolehan" value="{{ old('tanggal_perolehan') }}" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Lokasi / Kecamatan</label>
                        <input type="text" name="lokasi" value="{{ old('lokasi') }}" class="form-control" placeholder="Banawa / Donggala">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Dinas / OPD</label>
                        <input type="text" name="dinas" value="{{ old('dinas') }}" class="form-control" placeholder="BPKAD">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Alamat Lengkap</label>
                        <input type="text" name="alamat" value="{{ old('alamat') }}" class="form-control" placeholder="Jl. Contoh No. 1">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Upload Scan Surat Penyerahan (PDF Max 50MB)</label>
                        <input type="file" name="pdf" class="form-control" accept=".pdf">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-4">
                    <a href="{{ route('elabel.surat-penyerahan.index') }}" class="btn btn-light border">Batal</a>
                    <button type="submit" class="btn btn-primary fw-semibold px-4"><i class="bi bi-save me-1"></i> Simpan Surat Penyerahan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
