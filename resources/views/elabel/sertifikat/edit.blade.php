@extends('layouts.app')

@section('title', 'Edit Sertifikat Tanah - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.sertifikat.index') }}" class="text-decoration-none text-secondary">Sertifikat Tanah</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Edit Sertifikat</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0">Edit Sertifikat Tanah {{ $item->no_sertipikat }}</h4>
        </div>
        <div>
            <a href="{{ route('elabel.sertifikat.index') }}" class="btn btn-light border fw-medium">
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
            <form action="{{ route('elabel.sertifikat.update', $item->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">No. Sertipikat <span class="text-danger">*</span></label>
                        <input type="text" name="no_sertipikat" value="{{ old('no_sertipikat', $item->no_sertipikat) }}" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">NIBAR</label>
                        <input type="text" name="nibar" value="{{ old('nibar', $item->nibar) }}" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Status Penggunaan</label>
                        <input type="text" name="status_penggunaan" value="{{ old('status_penggunaan', $item->status_penggunaan) }}" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Spesifikasi / Jenis Hak</label>
                        <input type="text" name="spesifikasi" value="{{ old('spesifikasi', $item->spesifikasi) }}" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Luas (m²)</label>
                        <input type="number" step="0.01" name="luas" value="{{ old('luas', $item->luas) }}" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Tanggal Perolehan</label>
                        <input type="date" name="tanggal_perolehan" value="{{ old('tanggal_perolehan', $item->tanggal_perolehan ? $item->tanggal_perolehan->format('Y-m-d') : '') }}" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Nilai Perolehan (Rp)</label>
                        <input type="number" step="0.01" name="nilai_perolehan" value="{{ old('nilai_perolehan', $item->nilai_perolehan) }}" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Nama Pemilik / Atas Nama</label>
                        <input type="text" name="nama_pemilik" value="{{ old('nama_pemilik', $item->nama_pemilik) }}" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Cara Perolehan</label>
                        <input type="text" name="cara_perolehan" value="{{ old('cara_perolehan', $item->cara_perolehan) }}" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Alamat Lengkap</label>
                        <input type="text" name="alamat" value="{{ old('alamat', $item->alamat) }}" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Lokasi / Kecamatan</label>
                        <input type="text" name="lokasi" value="{{ old('lokasi', $item->lokasi) }}" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Dinas / OPD Pengguna</label>
                        <input type="text" name="dinas" value="{{ old('dinas', $item->dinas) }}" class="form-control">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold small">Ganti File Scan Sertifikat (PDF Max 50MB)</label>
                        <input type="file" name="pdf" class="form-control" accept=".pdf">
                        @if($item->pdf_path)
                            <div class="small text-success mt-1"><i class="bi bi-file-earmark-check"></i> File scan saat ini tersedia. Upload file baru untuk mengganti.</div>
                        @endif
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-4">
                    <a href="{{ route('elabel.sertifikat.index') }}" class="btn btn-light border">Batal</a>
                    <button type="submit" class="btn btn-success fw-semibold px-4"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
