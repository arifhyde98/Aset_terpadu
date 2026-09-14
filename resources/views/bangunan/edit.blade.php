@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-lg-4 py-3">
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 text-muted small">
                <li class="breadcrumb-item"><a href="{{ route('landing') }}" class="text-decoration-none">Beranda</a></li>
                <li class="breadcrumb-item"><a href="{{ route('bangunan.index') }}" class="text-decoration-none">KIB C - Bangunan</a></li>
                <li class="breadcrumb-item"><a href="{{ route('bangunan.show', $bangunan) }}" class="text-decoration-none">{{ $bangunan->nama_bangunan }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>
        <h3 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-pencil-square text-primary"></i> Edit Gedung dan Bangunan
        </h3>
        <p class="text-muted small mb-0">Perbarui data aset KIB C: <strong>{{ $bangunan->nama_bangunan }}</strong> ({{ $bangunan->kode_bangunan }}).</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4">
            <h6 class="fw-bold d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-exclamation-triangle-fill"></i> Terdapat Kesalahan Input
            </h6>
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('bangunan.update', $bangunan) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <!-- Kolom Kiri: Data Pokok & Konstruksi -->
            <div class="col-12 col-lg-8">
                <!-- 1. Identitas Pokok & Klasifikasi -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <span class="rounded-circle p-1 bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                <i class="bi bi-card-text fs-6"></i>
                            </span>
                            1. Identitas &amp; Klasifikasi Bangunan
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12 col-md-8">
                                <label class="form-label small fw-semibold text-dark">Nama Gedung / Bangunan <span class="text-danger">*</span></label>
                                <input type="text" name="nama_bangunan" class="form-control @error('nama_bangunan') is-invalid @enderror" 
                                       value="{{ old('nama_bangunan', $bangunan->nama_bangunan) }}" required>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Kode Bangunan</label>
                                <input type="text" name="kode_bangunan" class="form-control font-monospace bg-light" 
                                       value="{{ old('kode_bangunan', $bangunan->kode_bangunan) }}" required>
                            </div>

                            <div class="col-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Kode Barang (BMD)</label>
                                <input type="text" name="kode_barang" class="form-control font-monospace" 
                                       placeholder="1.3.3.01.01.001" value="{{ old('kode_barang', $bangunan->kode_barang) }}">
                            </div>

                            <div class="col-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Nomor Register</label>
                                <input type="text" name="nomor_register" class="form-control font-monospace" 
                                       placeholder="0001" value="{{ old('nomor_register', $bangunan->nomor_register) }}">
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Klasifikasi Jenis Bangunan <span class="text-danger">*</span></label>
                                @php
                                    $currentJenis = is_object($bangunan->jenis_bangunan) ? $bangunan->jenis_bangunan->value : $bangunan->jenis_bangunan;
                                @endphp
                                <select name="jenis_bangunan" id="selectJenisBangunan" class="form-select" required>
                                    @foreach($jenisList as $jenis)
                                        <option value="{{ $jenis->value }}" {{ old('jenis_bangunan', $currentJenis) === $jenis->value ? 'selected' : '' }}>
                                            {{ $jenis->value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Field Dinamis Permendagri 7/2006 (Rumah Dinas) -->
                            <div class="col-12 p-3 bg-light rounded-3 d-none" id="groupRumahDinas">
                                <h6 class="fw-bold text-primary small mb-2 d-flex align-items-center gap-1">
                                    <i class="bi bi-house-door-fill"></i> Standarisasi Rumah Jabatan / Dinas (Permendagri No. 7/2006)
                                </h6>
                                <div class="row g-2">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-dark">Tipe Rumah Dinas</label>
                                        @php
                                            $currentTipe = is_object($bangunan->tipe_rumah_dinas) ? $bangunan->tipe_rumah_dinas->value : $bangunan->tipe_rumah_dinas;
                                        @endphp
                                        <select name="tipe_rumah_dinas" class="form-select form-select-sm">
                                            <option value="">-- Pilih Tipe Rumah Dinas --</option>
                                            @foreach($tipeRumahList as $tipe)
                                                <option value="{{ $tipe->value }}" {{ old('tipe_rumah_dinas', $currentTipe) === $tipe->value ? 'selected' : '' }}>
                                                    {{ $tipe->value }} ({{ $tipe->description() }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-dark">Nama &amp; NIP Pejabat Penghuni</label>
                                        <input type="text" name="nama_penghuni" class="form-control form-select-sm" 
                                               value="{{ old('nama_penghuni', $bangunan->nama_penghuni) }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Karakteristik Konstruksi & Fisik -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <span class="rounded-circle p-1 bg-info-subtle text-info d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                <i class="bi bi-tools fs-6"></i>
                            </span>
                            2. Karakteristik Fisik &amp; Konstruksi (Permendagri 19/2016)
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Kondisi Bangunan <span class="text-danger">*</span></label>
                                @php
                                    $currentKondisi = is_object($bangunan->kondisi) ? $bangunan->kondisi->value : $bangunan->kondisi;
                                @endphp
                                <select name="kondisi" class="form-select" required>
                                    @foreach($kondisis as $kon)
                                        <option value="{{ $kon->value }}" {{ old('kondisi', $currentKondisi) === $kon->value ? 'selected' : '' }}>
                                            {{ $kon->value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Luas Total Lantai (m²) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="luas_lantai" class="form-control @error('luas_lantai') is-invalid @enderror" 
                                       value="{{ old('luas_lantai', $bangunan->luas_lantai) }}" required>
                            </div>

                            <div class="col-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Luas Tapak Dasar (m²)</label>
                                <input type="number" step="0.01" name="luas_dasar" class="form-control" 
                                       value="{{ old('luas_dasar', $bangunan->luas_dasar) }}">
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label small fw-semibold text-dark">Konstruksi Tingkat</label>
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" name="konstruksi_tingkat" id="switchTingkat" value="1" 
                                           {{ old('konstruksi_tingkat', $bangunan->konstruksi_tingkat) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="switchTingkat">Bertingkat</label>
                                </div>
                            </div>

                            <div class="col-6 col-md-3" id="groupJumlahLantai" style="display: none;">
                                <label class="form-label small fw-semibold text-dark">Jumlah Lantai</label>
                                <input type="number" name="jumlah_lantai" class="form-control" min="1" value="{{ old('jumlah_lantai', $bangunan->jumlah_lantai) }}">
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label small fw-semibold text-dark">Struktur Beton</label>
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" name="konstruksi_beton" id="switchBeton" value="1" 
                                           {{ old('konstruksi_beton', $bangunan->konstruksi_beton) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="switchBeton">Beton Bertulang</label>
                                </div>
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label small fw-semibold text-dark">Tipe Konstruksi</label>
                                <select name="tipe_konstruksi" class="form-select">
                                    <option value="Permanen" {{ old('tipe_konstruksi', $bangunan->tipe_konstruksi) === 'Permanen' ? 'selected' : '' }}>Permanen</option>
                                    <option value="Semi-Permanen" {{ old('tipe_konstruksi', $bangunan->tipe_konstruksi) === 'Semi-Permanen' ? 'selected' : '' }}>Semi-Permanen</option>
                                    <option value="Darurat" {{ old('tipe_konstruksi', $bangunan->tipe_konstruksi) === 'Darurat' ? 'selected' : '' }}>Darurat</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Legalitas PBG & Nilai Akuntansi -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <span class="rounded-circle p-1 bg-success-subtle text-success d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                <i class="bi bi-file-earmark-check fs-6"></i>
                            </span>
                            3. Legalitas Perizinan (PBG/IMB) &amp; Nilai Perolehan
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-dark">Nomor Dokumen PBG / IMB</label>
                                <input type="text" name="nomor_dokumen_pbg" class="form-control" 
                                       value="{{ old('nomor_dokumen_pbg', $bangunan->nomor_dokumen_pbg) }}">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-dark">Tanggal Terbit PBG / IMB</label>
                                <input type="date" name="tanggal_dokumen_pbg" class="form-control" 
                                       value="{{ old('tanggal_dokumen_pbg', $bangunan->tanggal_dokumen_pbg?->format('Y-m-d')) }}">
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Asal Usul Perolehan</label>
                                <input type="text" name="asal_usul" class="form-control" 
                                       value="{{ old('asal_usul', $bangunan->asal_usul) }}">
                            </div>

                            <div class="col-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Tahun Pengadaan / Selesai</label>
                                <input type="number" name="tahun_pengadaan" class="form-control" 
                                       min="1950" max="{{ date('Y') + 1 }}" value="{{ old('tahun_pengadaan', $bangunan->tahun_pengadaan) }}">
                            </div>

                            <div class="col-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Status Penggunaan</label>
                                <select name="status_penggunaan" class="form-select">
                                    <option value="Digunakan Sendiri" {{ old('status_penggunaan', $bangunan->status_penggunaan) === 'Digunakan Sendiri' ? 'selected' : '' }}>Digunakan Sendiri</option>
                                    <option value="Disewakan" {{ old('status_penggunaan', $bangunan->status_penggunaan) === 'Disewakan' ? 'selected' : '' }}>Disewakan</option>
                                    <option value="Pinjam Pakai" {{ old('status_penggunaan', $bangunan->status_penggunaan) === 'Pinjam Pakai' ? 'selected' : '' }}>Pinjam Pakai</option>
                                    <option value="Kosong / Rusak" {{ old('status_penggunaan', $bangunan->status_penggunaan) === 'Kosong / Rusak' ? 'selected' : '' }}>Kosong / Rusak</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-dark">Harga / Nilai Perolehan (Rp)</label>
                                <input type="text" name="harga_perolehan" class="form-control fw-bold text-success" 
                                       value="{{ old('harga_perolehan', number_format($bangunan->harga_perolehan, 0, ',', '.')) }}">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-dark">Nilai Buku Saat Ini (Rp)</label>
                                <input type="text" name="nilai_buku" class="form-control" 
                                       value="{{ old('nilai_buku', $bangunan->nilai_buku ? number_format($bangunan->nilai_buku, 0, ',', '.') : '') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Multi-Tenancy, Tanah KIB A & Lampiran -->
            <div class="col-12 col-lg-4">
                <!-- 4. Penguasaan & Tanah KIB A -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <span class="rounded-circle p-1 bg-warning-subtle text-warning d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                <i class="bi bi-shield-lock fs-6"></i>
                            </span>
                            4. Instansi &amp; Tanah Dasar
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        @if(auth()->user()->role !== \App\Enums\UserRole::OPD)
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-dark">OPD Pengguna / Pemilik <span class="text-danger">*</span></label>
                                <select name="opd_id" class="form-select" required>
                                    <option value="">-- Pilih Instansi OPD --</option>
                                    @foreach($opds as $opd)
                                        <option value="{{ $opd->id }}" {{ old('opd_id', $bangunan->opd_id) == $opd->id ? 'selected' : '' }}>
                                            {{ $opd->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-dark">Instansi OPD</label>
                                <input type="text" class="form-control bg-light" value="{{ $bangunan->opdSipat?->nama ?? 'Instansi Anda' }}" disabled>
                                <input type="hidden" name="opd_id" value="{{ $bangunan->opd_id }}">
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Pilih Tanah KIB A (SIPAT)</label>
                            <select name="aset_tanah_id" id="selectTanahKibA" class="form-select">
                                <option value="">-- Non-KIB A / Tanah Lain --</option>
                                @foreach($tanahs as $tanah)
                                    <option value="{{ $tanah->id_aset }}" {{ old('aset_tanah_id', $bangunan->aset_tanah_id) == $tanah->id_aset ? 'selected' : '' }}>
                                        {{ $tanah->kode_aset ?? 'NIBAR' }} - {{ $tanah->nama_aset }} ({{ number_format($tanah->luas, 0) }} m²)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Status Kepemilikan Tanah Dasar</label>
                            <select name="status_tanah_dasar" class="form-select form-select-sm">
                                <option value="Tanah Pemkab Bersertifikat" {{ old('status_tanah_dasar', $bangunan->status_tanah_dasar) === 'Tanah Pemkab Bersertifikat' ? 'selected' : '' }}>Tanah Pemkab Bersertifikat</option>
                                <option value="Tanah Pemkab Belum Bersertifikat" {{ old('status_tanah_dasar', $bangunan->status_tanah_dasar) === 'Tanah Pemkab Belum Bersertifikat' ? 'selected' : '' }}>Tanah Pemkab Belum Bersertifikat</option>
                                <option value="Tanah Pinjam Pakai" {{ old('status_tanah_dasar', $bangunan->status_tanah_dasar) === 'Tanah Pinjam Pakai' ? 'selected' : '' }}>Tanah Pinjam Pakai</option>
                                <option value="Tanah Sewa" {{ old('status_tanah_dasar', $bangunan->status_tanah_dasar) === 'Tanah Sewa' ? 'selected' : '' }}>Tanah Sewa</option>
                                <option value="Tanah Kas Desa" {{ old('status_tanah_dasar', $bangunan->status_tanah_dasar) === 'Tanah Kas Desa' ? 'selected' : '' }}>Tanah Kas Desa</option>
                                <option value="Tanah Hibah" {{ old('status_tanah_dasar', $bangunan->status_tanah_dasar) === 'Tanah Hibah' ? 'selected' : '' }}>Tanah Hibah</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 5. Lokasi & Spasial -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <span class="rounded-circle p-1 bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                <i class="bi bi-geo-alt fs-6"></i>
                            </span>
                            5. Lokasi &amp; Koordinat GPS
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Kecamatan</label>
                            <select name="kecamatan_id" class="form-select form-select-sm">
                                <option value="">-- Pilih Kecamatan --</option>
                                @foreach($kecamatans as $kec)
                                    <option value="{{ $kec->id }}" {{ old('kecamatan_id', $bangunan->kecamatan_id) == $kec->id ? 'selected' : '' }}>
                                        {{ $kec->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Alamat Lengkap</label>
                            <textarea name="alamat" class="form-control form-control-sm" rows="2">{{ old('alamat', $bangunan->alamat) }}</textarea>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-dark">Latitude</label>
                                <input type="number" step="0.0000001" name="lat" class="form-control form-control-sm" value="{{ old('lat', $bangunan->lat) }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-dark">Longitude</label>
                                <input type="number" step="0.0000001" name="lng" class="form-control form-control-sm" value="{{ old('lng', $bangunan->lng) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. Berkas Lampiran -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <span class="rounded-circle p-1 bg-secondary-subtle text-secondary d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                <i class="bi bi-paperclip fs-6"></i>
                            </span>
                            6. Foto &amp; Dokumen Lampiran
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Foto Fasad Bangunan</label>
                            @if($bangunan->foto_utama)
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $bangunan->foto_utama) }}" class="rounded-3 img-thumbnail" style="max-height: 120px;" alt="Foto Fasad">
                                </div>
                            @endif
                            <input type="file" name="foto_utama" accept="image/*" class="form-control form-control-sm">
                            <span class="text-muted small" style="font-size: 0.72rem;">Unggah foto baru jika ingin mengganti.</span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Dokumen PBG / BAST (PDF)</label>
                            @if($bangunan->dokumen_pdf)
                                <div class="mb-2">
                                    <a href="{{ asset('storage/' . $bangunan->dokumen_pdf) }}" target="_blank" class="btn btn-sm btn-outline-danger rounded-pill">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> Buka Dokumen Saat Ini
                                    </a>
                                </div>
                            @endif
                            <input type="file" name="dokumen_pdf" accept=".pdf" class="form-control form-control-sm">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Catatan / Keterangan</label>
                            <textarea name="keterangan" class="form-control form-control-sm" rows="2">{{ old('keterangan', $bangunan->keterangan) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary rounded-pill py-2.5 shadow-sm fw-bold">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan
                    </button>
                    <a href="{{ route('bangunan.show', $bangunan) }}" class="btn btn-light rounded-pill py-2">
                        Batal
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectJenis = document.getElementById('selectJenisBangunan');
    const groupRumahDinas = document.getElementById('groupRumahDinas');
    const switchTingkat = document.getElementById('switchTingkat');
    const groupJumlahLantai = document.getElementById('groupJumlahLantai');

    function toggleRumahDinas() {
        if (selectJenis.value === 'Rumah Dinas / Jabatan') {
            groupRumahDinas.classList.remove('d-none');
        } else {
            groupRumahDinas.classList.add('d-none');
        }
    }

    function toggleJumlahLantai() {
        if (switchTingkat.checked) {
            groupJumlahLantai.style.display = 'block';
        } else {
            groupJumlahLantai.style.display = 'none';
        }
    }

    selectJenis.addEventListener('change', toggleRumahDinas);
    switchTingkat.addEventListener('change', toggleJumlahLantai);

    toggleRumahDinas();
    toggleJumlahLantai();
});
</script>
@endsection
