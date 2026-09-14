@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-lg-4 py-3">
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 text-muted small">
                <li class="breadcrumb-item"><a href="{{ route('landing') }}" class="text-decoration-none">Beranda</a></li>
                <li class="breadcrumb-item"><a href="{{ route('bangunan.index') }}" class="text-decoration-none">KIB C - Bangunan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tambah Bangunan</li>
            </ol>
        </nav>
        <h3 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-plus-circle text-primary"></i> Tambah Gedung dan Bangunan Baru
        </h3>
        <p class="text-muted small mb-0">Lengkapi formulir pendataan aset KIB C berdasarkan standar Permendagri No. 19/2016 dan No. 7/2006.</p>
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

    <form action="{{ route('bangunan.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

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
                                       placeholder="Contoh: Gedung Kantor Dinas Kesehatan" value="{{ old('nama_bangunan') }}" required>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Kode Bangunan (Otomatis)</label>
                                <input type="text" name="kode_bangunan" class="form-control font-monospace bg-light" 
                                       placeholder="Auto-generate" value="{{ old('kode_bangunan') }}">
                            </div>

                            <div class="col-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Kode Barang (BMD)</label>
                                <input type="text" name="kode_barang" class="form-control font-monospace" 
                                       placeholder="1.3.3.01.01.001" value="{{ old('kode_barang') }}">
                            </div>

                            <div class="col-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Nomor Register</label>
                                <input type="text" name="nomor_register" class="form-control font-monospace" 
                                       placeholder="0001" value="{{ old('nomor_register') }}">
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Klasifikasi Jenis Bangunan <span class="text-danger">*</span></label>
                                <select name="jenis_bangunan" id="selectJenisBangunan" class="form-select" required>
                                    @foreach($jenisList as $jenis)
                                        <option value="{{ $jenis->value }}" {{ old('jenis_bangunan') === $jenis->value ? 'selected' : '' }}>
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
                                        <select name="tipe_rumah_dinas" class="form-select form-select-sm">
                                            <option value="">-- Pilih Tipe Rumah Dinas --</option>
                                            @foreach($tipeRumahList as $tipe)
                                                <option value="{{ $tipe->value }}" {{ old('tipe_rumah_dinas') === $tipe->value ? 'selected' : '' }}>
                                                    {{ $tipe->value }} ({{ $tipe->description() }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-dark">Nama &amp; NIP Pejabat Penghuni</label>
                                        <input type="text" name="nama_penghuni" class="form-control form-select-sm" 
                                               placeholder="Nama Pejabat Pemakai" value="{{ old('nama_penghuni') }}">
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
                                <select name="kondisi" class="form-select" required>
                                    @foreach($kondisis as $kon)
                                        <option value="{{ $kon->value }}" {{ old('kondisi', 'Baik') === $kon->value ? 'selected' : '' }}>
                                            {{ $kon->value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Luas Total Lantai (m²) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="luas_lantai" class="form-control @error('luas_lantai') is-invalid @enderror" 
                                       placeholder="Contoh: 450.50" value="{{ old('luas_lantai') }}" required>
                            </div>

                            <div class="col-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Luas Tapak Dasar (m²)</label>
                                <input type="number" step="0.01" name="luas_dasar" class="form-control" 
                                       placeholder="Contoh: 300.00" value="{{ old('luas_dasar') }}">
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label small fw-semibold text-dark">Konstruksi Tingkat</label>
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" name="konstruksi_tingkat" id="switchTingkat" value="1" {{ old('konstruksi_tingkat') ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="switchTingkat">Bertingkat</label>
                                </div>
                            </div>

                            <div class="col-6 col-md-3" id="groupJumlahLantai" style="display: none;">
                                <label class="form-label small fw-semibold text-dark">Jumlah Lantai</label>
                                <input type="number" name="jumlah_lantai" class="form-control" min="1" value="{{ old('jumlah_lantai', 1) }}">
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label small fw-semibold text-dark">Struktur Beton</label>
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" name="konstruksi_beton" id="switchBeton" value="1" {{ old('konstruksi_beton', true) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="switchBeton">Beton Bertulang</label>
                                </div>
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label small fw-semibold text-dark">Tipe Konstruksi</label>
                                <select name="tipe_konstruksi" class="form-select">
                                    <option value="Permanen" {{ old('tipe_konstruksi') === 'Permanen' ? 'selected' : '' }}>Permanen</option>
                                    <option value="Semi-Permanen" {{ old('tipe_konstruksi') === 'Semi-Permanen' ? 'selected' : '' }}>Semi-Permanen</option>
                                    <option value="Darurat" {{ old('tipe_konstruksi') === 'Darurat' ? 'selected' : '' }}>Darurat</option>
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
                                       placeholder="Nomor Izin Bangunan" value="{{ old('nomor_dokumen_pbg') }}">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-dark">Tanggal Terbit PBG / IMB</label>
                                <input type="date" name="tanggal_dokumen_pbg" class="form-control" value="{{ old('tanggal_dokumen_pbg') }}">
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Asal Usul Perolehan</label>
                                <input type="text" name="asal_usul" class="form-control" 
                                       placeholder="APBD Kab / Hibah / APBN" value="{{ old('asal_usul', 'APBD Kab') }}">
                            </div>

                            <div class="col-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Tahun Pengadaan / Selesai</label>
                                <input type="number" name="tahun_pengadaan" class="form-control" 
                                       min="1950" max="{{ date('Y') + 1 }}" value="{{ old('tahun_pengadaan', date('Y')) }}">
                            </div>

                            <div class="col-6 col-md-4">
                                <label class="form-label small fw-semibold text-dark">Status Penggunaan</label>
                                <select name="status_penggunaan" class="form-select">
                                    <option value="Digunakan Sendiri" {{ old('status_penggunaan') === 'Digunakan Sendiri' ? 'selected' : '' }}>Digunakan Sendiri</option>
                                    <option value="Disewakan" {{ old('status_penggunaan') === 'Disewakan' ? 'selected' : '' }}>Disewakan</option>
                                    <option value="Pinjam Pakai" {{ old('status_penggunaan') === 'Pinjam Pakai' ? 'selected' : '' }}>Pinjam Pakai</option>
                                    <option value="Kosong / Rusak" {{ old('status_penggunaan') === 'Kosong / Rusak' ? 'selected' : '' }}>Kosong / Rusak</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-dark">Harga / Nilai Perolehan (Rp)</label>
                                <input type="text" name="harga_perolehan" class="form-control fw-bold text-success" 
                                       placeholder="Contoh: 1.500.000.000" value="{{ old('harga_perolehan') }}">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-dark">Nilai Buku Saat Ini (Rp)</label>
                                <input type="text" name="nilai_buku" class="form-control" 
                                       placeholder="Contoh: 1.200.000.000" value="{{ old('nilai_buku') }}">
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
                                        <option value="{{ $opd->id }}" {{ old('opd_id') == $opd->id ? 'selected' : '' }}>
                                            {{ $opd->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-dark">Instansi OPD</label>
                                <input type="text" class="form-control bg-light" value="{{ auth()->user()->opdSipat?->nama ?? 'Instansi Anda' }}" disabled>
                                <input type="hidden" name="opd_id" value="{{ auth()->user()->opd_id }}">
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Pilih Tanah KIB A (SIPAT)</label>
                            <select name="aset_tanah_id" id="selectTanahKibA" class="form-select">
                                <option value="">-- Non-KIB A / Tanah Lain --</option>
                                @foreach($tanahs as $tanah)
                                    <option value="{{ $tanah->id_aset }}" {{ old('aset_tanah_id') == $tanah->id_aset ? 'selected' : '' }}>
                                        {{ $tanah->kode_aset ?? 'NIBAR' }} - {{ $tanah->nama_aset }} ({{ number_format($tanah->luas, 0) }} m²)
                                    </option>
                                @endforeach
                            </select>
                            <span class="text-muted small" style="font-size: 0.72rem;">Tautkan ke bidang tanah KIB A tempat gedung berdiri.</span>
                        </div>

                        <div class="mb-3" id="groupTanahNonKibA">
                            <label class="form-label small fw-semibold text-dark">Status Kepemilikan Tanah Dasar</label>
                            <select name="status_tanah_dasar" class="form-select form-select-sm">
                                <option value="Tanah Pemkab Bersertifikat">Tanah Pemkab Bersertifikat</option>
                                <option value="Tanah Pemkab Belum Bersertifikat">Tanah Pemkab Belum Bersertifikat</option>
                                <option value="Tanah Pinjam Pakai">Tanah Pinjam Pakai</option>
                                <option value="Tanah Sewa">Tanah Sewa</option>
                                <option value="Tanah Kas Desa">Tanah Kas Desa</option>
                                <option value="Tanah Hibah">Tanah Hibah</option>
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
                                    <option value="{{ $kec->id }}" {{ old('kecamatan_id') == $kec->id ? 'selected' : '' }}>
                                        {{ $kec->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Alamat Lengkap</label>
                            <textarea name="alamat" class="form-control form-control-sm" rows="2" placeholder="Nama Jalan, RT/RW, Dusun">{{ old('alamat') }}</textarea>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-dark">Latitude (Lintang)</label>
                                <input type="number" step="0.0000001" name="lat" class="form-control form-control-sm" placeholder="-0.6789012" value="{{ old('lat') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-dark">Longitude (Bujur)</label>
                                <input type="number" step="0.0000001" name="lng" class="form-control form-control-sm" placeholder="119.8901234" value="{{ old('lng') }}">
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
                            <label class="form-label small fw-semibold text-dark">Foto Fasad Bangunan (JPG/PNG maks. 5MB)</label>
                            <input type="file" name="foto_utama" accept="image/*" class="form-control form-control-sm">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Scan Dokumen PBG / BAST (PDF maks. 10MB)</label>
                            <input type="file" name="dokumen_pdf" accept=".pdf" class="form-control form-control-sm">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Catatan / Keterangan</label>
                            <textarea name="keterangan" class="form-control form-control-sm" rows="2" placeholder="Catatan fisik/audit">{{ old('keterangan') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary rounded-pill py-2.5 shadow-sm fw-bold">
                        <i class="bi bi-check-circle-fill me-1"></i> Simpan Data Bangunan
                    </button>
                    <a href="{{ route('bangunan.index') }}" class="btn btn-light rounded-pill py-2">
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
