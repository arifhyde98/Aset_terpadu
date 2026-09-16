@extends('layouts.app')

@section('title', 'Tambah Kendaraan Pinjam Pakai')

@section('content')
<div class="container-fluid px-0">

    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('vehicles.pinjam-pakai.index') }}" class="text-decoration-none text-secondary">Pinjam Pakai BMD</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Tambah Perjanjian</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-navy mb-0">Tambah Perjanjian Pinjam Pakai Kendaraan</h3>
        </div>
        <div>
            <a href="{{ route('vehicles.pinjam-pakai.index') }}" class="btn btn-light border shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- FORM CARD -->
    <div class="card border-0 shadow-sm rounded-4 bg-white">
        <div class="card-body p-4">
            <form action="{{ route('vehicles.pinjam-pakai.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-4">
                    <!-- SECTION 1: KENDARAAN DINAS & OPD -->
                    <div class="col-12">
                        <h6 class="fw-bold text-navy border-bottom pb-2 mb-3">
                            <i class="bi bi-car-front-fill text-primary me-2"></i> 1. Informasi Kendaraan Dinas (BMD)
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="vehicle_id" class="form-label fw-semibold small">Pilih Kendaraan Dinas <span class="text-danger">*</span></label>
                                <select name="vehicle_id" id="vehicle_id" class="form-select searchable-select @error('vehicle_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih Kendaraan Dinas --</option>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                            {{ $vehicle->no_polisi ?? 'Tanpa Nopol' }} - {{ $vehicle->merk_tipe ?? '' }} @if($vehicle->tahun_pembuatan) ({{ $vehicle->tahun_pembuatan }}) @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('vehicle_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="opd_id" class="form-label fw-semibold small">OPD Pemilik / Pengelola Aset</label>
                                <select name="opd_id" id="opd_id" class="form-select searchable-select @error('opd_id') is-invalid @enderror">
                                    <option value="">-- Otomatis Sesuai Kendaraan --</option>
                                    @foreach($opds as $opd)
                                        <option value="{{ $opd->id }}" {{ old('opd_id') == $opd->id ? 'selected' : '' }}>
                                            {{ $opd->nama }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('opd_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: INSTANSI PEMINJAM & PENANGGUNG JAWAB -->
                    <div class="col-12">
                        <h6 class="fw-bold text-navy border-bottom pb-2 mb-3">
                            <i class="bi bi-building-fill text-info me-2"></i> 2. Data Instansi Peminjam & Penanggung Jawab
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="kategori_peminjam" class="form-label fw-semibold small">Kategori Peminjam <span class="text-danger">*</span></label>
                                <select name="kategori_peminjam" id="kategori_peminjam" class="form-select searchable-select @error('kategori_peminjam') is-invalid @enderror" required>
                                    <option value="Instansi Vertikal" {{ old('kategori_peminjam') == 'Instansi Vertikal' ? 'selected' : '' }}>Instansi Vertikal (Kejaksaan/Polres/dll)</option>
                                    <option value="Antar OPD" {{ old('kategori_peminjam') == 'Antar OPD' ? 'selected' : '' }}>Antar OPD Pemda</option>
                                    <option value="Lainnya" {{ old('kategori_peminjam') == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                                </select>
                                @error('kategori_peminjam')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-8">
                                <label for="nama_instansi_peminjam" class="form-label fw-semibold small">Nama Instansi Peminjam <span class="text-danger">*</span></label>
                                <input type="text" name="nama_instansi_peminjam" id="nama_instansi_peminjam" value="{{ old('nama_instansi_peminjam') }}" class="form-control @error('nama_instansi_peminjam') is-invalid @enderror" placeholder="Contoh: Kejaksaan Negeri / Polres / Bawaslu" required>
                                @error('nama_instansi_peminjam')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="nama_penanggung_jawab" class="form-label fw-semibold small">Nama Penanggung Jawab <span class="text-danger">*</span></label>
                                <input type="text" name="nama_penanggung_jawab" id="nama_penanggung_jawab" value="{{ old('nama_penanggung_jawab') }}" class="form-control @error('nama_penanggung_jawab') is-invalid @enderror" placeholder="Nama lengkap pejabat peminjam" required>
                                @error('nama_penanggung_jawab')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="nip_penanggung_jawab" class="form-label fw-semibold small">NIP / NRP Penanggung Jawab</label>
                                <input type="text" name="nip_penanggung_jawab" id="nip_penanggung_jawab" value="{{ old('nip_penanggung_jawab') }}" class="form-control @error('nip_penanggung_jawab') is-invalid @enderror" placeholder="Nomor Induk Pegawai / Polisi">
                                @error('nip_penanggung_jawab')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="jabatan_penanggung_jawab" class="form-label fw-semibold small">Jabatan Penanggung Jawab</label>
                                <input type="text" name="jabatan_penanggung_jawab" id="jabatan_penanggung_jawab" value="{{ old('jabatan_penanggung_jawab') }}" class="form-control @error('jabatan_penanggung_jawab') is-invalid @enderror" placeholder="Kasi Intel / Kabag Ops / dll">
                                @error('jabatan_penanggung_jawab')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="kontak_penanggung_jawab" class="form-label fw-semibold small">Nomor Telepon / HP</label>
                                <input type="text" name="kontak_penanggung_jawab" id="kontak_penanggung_jawab" value="{{ old('kontak_penanggung_jawab') }}" class="form-control @error('kontak_penanggung_jawab') is-invalid @enderror" placeholder="0812xxxxxxxx">
                                @error('kontak_penanggung_jawab')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: PERJANJIAN NPPP & MASA BERLAKU -->
                    <div class="col-12">
                        <h6 class="fw-bold text-navy border-bottom pb-2 mb-3">
                            <i class="bi bi-file-earmark-text-fill text-warning me-2"></i> 3. Naskah Perjanjian Pinjam Pakai (NPPP / BAST)
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nomor_nppp_bast" class="form-label fw-semibold small">Nomor Dokumen NPPP / BAST <span class="text-danger">*</span></label>
                                <input type="text" name="nomor_nppp_bast" id="nomor_nppp_bast" value="{{ old('nomor_nppp_bast') }}" class="form-control @error('nomor_nppp_bast') is-invalid @enderror" placeholder="Contoh: 028/123/BPKAD/2026" required>
                                @error('nomor_nppp_bast')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="tanggal_nppp_bast" class="form-label fw-semibold small">Tanggal Penandatanganan Dokumen</label>
                                <input type="date" name="tanggal_nppp_bast" id="tanggal_nppp_bast" value="{{ old('tanggal_nppp_bast') }}" class="form-control @error('tanggal_nppp_bast') is-invalid @enderror">
                                @error('tanggal_nppp_bast')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="tanggal_mulai" class="form-label fw-semibold small">Tanggal Mulai Pinjam Pakai <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_mulai" id="tanggal_mulai" value="{{ old('tanggal_mulai', date('Y-m-d')) }}" class="form-control @error('tanggal_mulai') is-invalid @enderror" required>
                                @error('tanggal_mulai')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="tanggal_selesai" class="form-label fw-semibold small">Tanggal Akhir / Selesai <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_selesai" id="tanggal_selesai" value="{{ old('tanggal_selesai', date('Y-m-d', strtotime('+1 year'))) }}" class="form-control @error('tanggal_selesai') is-invalid @enderror" required>
                                @error('tanggal_selesai')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="file_dokumen_pdf" class="form-label fw-semibold small">Upload Salinan PDF NPPP / BAST (Maks. 10MB)</label>
                                <input type="file" name="file_dokumen_pdf" id="file_dokumen_pdf" accept="application/pdf" class="form-control @error('file_dokumen_pdf') is-invalid @enderror">
                                @error('file_dokumen_pdf')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="keterangan" class="form-label fw-semibold small">Keterangan / Catatan Tambahan</label>
                                <textarea name="keterangan" id="keterangan" rows="2" class="form-control @error('keterangan') is-invalid @enderror" placeholder="Catatan perihal pemeliharaan, pajak, dsb.">{{ old('keterangan') }}</textarea>
                                @error('keterangan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('vehicles.pinjam-pakai.index') }}" class="btn btn-light border fw-medium px-4">Batal</a>
                    <button type="submit" class="btn btn-primary fw-bold px-4">
                        <i class="bi bi-save me-1"></i> Simpan Data Pinjam Pakai
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
