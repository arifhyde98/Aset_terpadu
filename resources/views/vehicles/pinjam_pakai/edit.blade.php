@extends('layouts.app')

@section('title', 'Edit Kendaraan Pinjam Pakai')

@section('content')
<div class="container-fluid px-0">

    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('vehicles.pinjam-pakai.index') }}" class="text-decoration-none text-secondary">Pinjam Pakai BMD</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Edit Perjanjian</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-navy mb-0">Edit Perjanjian Pinjam Pakai Kendaraan</h3>
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
            <form action="{{ route('vehicles.pinjam-pakai.update', $pinjamPakai->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

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
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}" {{ old('vehicle_id', $pinjamPakai->vehicle_id) == $vehicle->id ? 'selected' : '' }}>
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
                                    @foreach($opds as $opd)
                                        <option value="{{ $opd->id }}" {{ old('opd_id', $pinjamPakai->opd_id) == $opd->id ? 'selected' : '' }}>
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
                                    <option value="Instansi Vertikal" {{ old('kategori_peminjam', $pinjamPakai->kategori_peminjam) == 'Instansi Vertikal' ? 'selected' : '' }}>Instansi Vertikal (Kejaksaan/Polres/dll)</option>
                                    <option value="Antar OPD" {{ old('kategori_peminjam', $pinjamPakai->kategori_peminjam) == 'Antar OPD' ? 'selected' : '' }}>Antar OPD Pemda</option>
                                    <option value="Lainnya" {{ old('kategori_peminjam', $pinjamPakai->kategori_peminjam) == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                                </select>
                                @error('kategori_peminjam')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-8">
                                <label for="nama_instansi_peminjam" class="form-label fw-semibold small">Nama Instansi Peminjam <span class="text-danger">*</span></label>
                                <input type="text" name="nama_instansi_peminjam" id="nama_instansi_peminjam" value="{{ old('nama_instansi_peminjam', $pinjamPakai->nama_instansi_peminjam) }}" class="form-control @error('nama_instansi_peminjam') is-invalid @enderror" required>
                                @error('nama_instansi_peminjam')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="nama_penanggung_jawab" class="form-label fw-semibold small">Nama Penanggung Jawab <span class="text-danger">*</span></label>
                                <input type="text" name="nama_penanggung_jawab" id="nama_penanggung_jawab" value="{{ old('nama_penanggung_jawab', $pinjamPakai->nama_penanggung_jawab) }}" class="form-control @error('nama_penanggung_jawab') is-invalid @enderror" required>
                                @error('nama_penanggung_jawab')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="nip_penanggung_jawab" class="form-label fw-semibold small">NIP / NRP Penanggung Jawab</label>
                                <input type="text" name="nip_penanggung_jawab" id="nip_penanggung_jawab" value="{{ old('nip_penanggung_jawab', $pinjamPakai->nip_penanggung_jawab) }}" class="form-control @error('nip_penanggung_jawab') is-invalid @enderror">
                                @error('nip_penanggung_jawab')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="jabatan_penanggung_jawab" class="form-label fw-semibold small">Jabatan Penanggung Jawab</label>
                                <input type="text" name="jabatan_penanggung_jawab" id="jabatan_penanggung_jawab" value="{{ old('jabatan_penanggung_jawab', $pinjamPakai->jabatan_penanggung_jawab) }}" class="form-control @error('jabatan_penanggung_jawab') is-invalid @enderror" placeholder="Kasi Intel / Kabag Ops / dll">
                                @error('jabatan_penanggung_jawab')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="kontak_penanggung_jawab" class="form-label fw-semibold small">Nomor Telepon / HP</label>
                                <input type="text" name="kontak_penanggung_jawab" id="kontak_penanggung_jawab" value="{{ old('kontak_penanggung_jawab', $pinjamPakai->kontak_penanggung_jawab) }}" class="form-control @error('kontak_penanggung_jawab') is-invalid @enderror">
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
                            <div class="col-md-4">
                                <label for="nomor_nppp_bast" class="form-label fw-semibold small">Nomor Dokumen NPPP / BAST <span class="text-danger">*</span></label>
                                <input type="text" name="nomor_nppp_bast" id="nomor_nppp_bast" value="{{ old('nomor_nppp_bast', $pinjamPakai->nomor_nppp_bast) }}" class="form-control @error('nomor_nppp_bast') is-invalid @enderror" required>
                                @error('nomor_nppp_bast')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="tanggal_nppp_bast" class="form-label fw-semibold small">Tanggal Penandatanganan Dokumen</label>
                                <input type="date" name="tanggal_nppp_bast" id="tanggal_nppp_bast" value="{{ old('tanggal_nppp_bast', optional($pinjamPakai->tanggal_nppp_bast)->format('Y-m-d')) }}" class="form-control @error('tanggal_nppp_bast') is-invalid @enderror">
                                @error('tanggal_nppp_bast')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="status_perjanjian" class="form-label fw-semibold small">Status Perjanjian <span class="text-danger">*</span></label>
                                <select name="status_perjanjian" id="status_perjanjian" class="form-select searchable-select @error('status_perjanjian') is-invalid @enderror" required>
                                    <option value="Aktif" {{ old('status_perjanjian', $pinjamPakai->status_perjanjian) == 'Aktif' ? 'selected' : '' }}>Aktif</option>
                                    <option value="Akan Jatuh Tempo" {{ old('status_perjanjian', $pinjamPakai->status_perjanjian) == 'Akan Jatuh Tempo' ? 'selected' : '' }}>Akan Jatuh Tempo</option>
                                    <option value="Diperpanjang" {{ old('status_perjanjian', $pinjamPakai->status_perjanjian) == 'Diperpanjang' ? 'selected' : '' }}>Diperpanjang</option>
                                    <option value="Selesai / Dikembalikan" {{ old('status_perjanjian', $pinjamPakai->status_perjanjian) == 'Selesai / Dikembalikan' ? 'selected' : '' }}>Selesai / Dikembalikan</option>
                                </select>
                                @error('status_perjanjian')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="tanggal_mulai" class="form-label fw-semibold small">Tanggal Mulai Pinjam Pakai <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_mulai" id="tanggal_mulai" value="{{ old('tanggal_mulai', optional($pinjamPakai->tanggal_mulai)->format('Y-m-d')) }}" class="form-control @error('tanggal_mulai') is-invalid @enderror" required>
                                @error('tanggal_mulai')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="tanggal_selesai" class="form-label fw-semibold small">Tanggal Akhir / Selesai <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_selesai" id="tanggal_selesai" value="{{ old('tanggal_selesai', optional($pinjamPakai->tanggal_selesai)->format('Y-m-d')) }}" class="form-control @error('tanggal_selesai') is-invalid @enderror" required>
                                @error('tanggal_selesai')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="file_dokumen_pdf" class="form-label fw-semibold small">Ganti File PDF NPPP / BAST (Kosongkan jika tidak diubah)</label>
                                <input type="file" name="file_dokumen_pdf" id="file_dokumen_pdf" accept="application/pdf" class="form-control @error('file_dokumen_pdf') is-invalid @enderror">
                                @if($pinjamPakai->file_dokumen_pdf)
                                    <div class="mt-1 small">
                                        <a href="{{ $pinjamPakai->dokumen_url }}" target="_blank" class="text-primary text-decoration-none"><i class="bi bi-file-earmark-pdf-fill me-1"></i> Lihat Dokumen Saat Ini</a>
                                    </div>
                                @endif
                                @error('file_dokumen_pdf')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="keterangan" class="form-label fw-semibold small">Keterangan / Catatan Tambahan</label>
                                <textarea name="keterangan" id="keterangan" rows="2" class="form-control @error('keterangan') is-invalid @enderror">{{ old('keterangan', $pinjamPakai->keterangan) }}</textarea>
                                @error('keterangan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('vehicles.pinjam-pakai.index') }}" class="btn btn-light border fw-medium px-4">Batal</a>
                    <button type="submit" class="btn btn-warning fw-bold px-4">
                        <i class="bi bi-pencil-square me-1"></i> Perbarui Data
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
