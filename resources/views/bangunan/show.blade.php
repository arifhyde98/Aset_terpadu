@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-lg-4 py-3">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 text-muted small">
                    <li class="breadcrumb-item"><a href="{{ route('landing') }}" class="text-decoration-none">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('bangunan.index') }}" class="text-decoration-none">KIB C - Bangunan</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $bangunan->nama_bangunan }}</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h3 class="fw-bold text-dark mb-0">{{ $bangunan->nama_bangunan }}</h3>
                <span class="badge bg-light text-secondary border font-monospace">{{ $bangunan->kode_bangunan }}</span>
                @php
                    $kondisiBadge = match(is_object($bangunan->kondisi) ? $bangunan->kondisi->value : $bangunan->kondisi) {
                        'Baik' => 'bg-success text-white',
                        'Kurang Baik' => 'bg-warning text-dark',
                        'Rusak Berat' => 'bg-danger text-white',
                        default => 'bg-secondary text-white',
                    };
                @endphp
                <span class="badge rounded-pill px-3 py-1.5 {{ $kondisiBadge }}">
                    <i class="bi {{ is_object($bangunan->kondisi) ? $bangunan->kondisi->icon() : 'bi-check-circle' }} me-1"></i>
                    {{ is_object($bangunan->kondisi) ? $bangunan->kondisi->value : $bangunan->kondisi }}
                </span>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('bangunan.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            <a href="{{ route('bangunan.edit', $bangunan) }}" class="btn btn-primary rounded-pill px-3">
                <i class="bi bi-pencil-square me-1"></i> Edit Bangunan
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4 d-flex align-items-center gap-2">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <div class="row g-4">
        <!-- Kolom Kiri: Foto Fasad, Spesifikasi Teknis & Legalitas -->
        <div class="col-12 col-lg-8">
            <!-- Kartu Visual & Fasad -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="row g-0">
                    <div class="col-12 col-md-5 bg-light d-flex align-items-center justify-content-center p-3 text-center" style="min-height: 240px;">
                        @if($bangunan->foto_utama && \Illuminate\Support\Facades\Storage::disk('public')->exists($bangunan->foto_utama))
                            <img src="{{ asset('storage/' . $bangunan->foto_utama) }}" alt="{{ $bangunan->nama_bangunan }}" 
                                 class="img-fluid rounded-3 shadow-sm object-fit-cover w-100" style="max-height: 260px;">
                        @else
                            <div class="text-muted p-4">
                                <i class="bi bi-building display-3 d-block mb-2 text-primary opacity-50"></i>
                                <span class="small">Belum Ada Foto Fasad Bangunan</span>
                            </div>
                        @endif
                    </div>
                    <div class="col-12 col-md-7 p-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                <i class="bi {{ is_object($bangunan->jenis_bangunan) ? $bangunan->jenis_bangunan->icon() : 'bi-building' }} me-1"></i>
                                {{ is_object($bangunan->jenis_bangunan) ? $bangunan->jenis_bangunan->value : $bangunan->jenis_bangunan }}
                            </span>
                            @if($bangunan->nomor_register)
                                <span class="badge bg-light text-muted border font-monospace">
                                    No. Reg: {{ $bangunan->nomor_register }}
                                </span>
                            @endif
                        </div>
                        <h4 class="fw-bold text-dark mb-1">{{ $bangunan->nama_bangunan }}</h4>
                        <p class="text-muted small mb-3">
                            <i class="bi bi-geo-alt text-danger me-1"></i>
                            {{ $bangunan->alamat ?: 'Alamat belum dilengkapi' }}, 
                            {{ $bangunan->kecamatan?->nama ?? '-' }}, 
                            Kabupaten Donggala
                        </p>

                        <div class="row g-2 pt-2 border-top">
                            <div class="col-6">
                                <span class="text-muted small d-block">Luas Total Lantai</span>
                                <span class="fw-bold text-dark fs-5">{{ number_format($bangunan->luas_lantai, 2, ',', '.') }} m²</span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small d-block">Nilai Perolehan</span>
                                <span class="fw-bold text-success fs-5">Rp {{ number_format($bangunan->harga_perolehan, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Spesifikasi Teknis & Konstruksi Permendagri 19/2016 -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-tools text-primary"></i> Spesifikasi Fisik &amp; Konstruksi (Permendagri No. 19/2016)
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <span class="text-muted small d-block">Konstruksi Bertingkat</span>
                            <span class="fw-semibold text-dark">
                                {{ $bangunan->konstruksi_tingkat ? "Bertingkat ({$bangunan->jumlah_lantai} Lantai)" : 'Tidak Bertingkat (1 Lantai)' }}
                            </span>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="text-muted small d-block">Konstruksi Beton</span>
                            <span class="fw-semibold text-dark">
                                {{ $bangunan->konstruksi_beton ? 'Beton Bertulang' : 'Bukan Beton' }}
                            </span>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="text-muted small d-block">Tipe Konstruksi</span>
                            <span class="fw-semibold text-dark">{{ $bangunan->tipe_konstruksi ?: 'Permanen' }}</span>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="text-muted small d-block">Luas Tapak Dasar</span>
                            <span class="fw-semibold text-dark">{{ number_format($bangunan->luas_dasar, 2, ',', '.') }} m²</span>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="text-muted small d-block">Status Penggunaan</span>
                            <span class="badge bg-light text-dark border">{{ $bangunan->status_penggunaan }}</span>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="text-muted small d-block">Tahun Pembangunan</span>
                            <span class="fw-semibold text-dark">{{ $bangunan->tahun_pengadaan ?: '-' }}</span>
                        </div>
                        <div class="col-6 col-md-6">
                            <span class="text-muted small d-block">Kodefikasi Barang BMD</span>
                            <span class="fw-semibold text-dark font-monospace">{{ $bangunan->kode_barang ?: 'Belum diisi' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Standarisasi Rumah Jabatan / Dinas Permendagri 7/2006 (Jika Ada) -->
            @if(is_object($bangunan->jenis_bangunan) ? $bangunan->jenis_bangunan->value === 'Rumah Dinas / Jabatan' : $bangunan->jenis_bangunan === 'Rumah Dinas / Jabatan')
                <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-primary">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                        <h5 class="fw-bold text-primary mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-house-door-fill"></i> Standarisasi Rumah Dinas (Permendagri No. 7/2006)
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <span class="text-muted small d-block">Tipe Rumah Dinas</span>
                                <span class="badge bg-primary px-3 py-1.5 rounded-pill fs-6">
                                    {{ is_object($bangunan->tipe_rumah_dinas) ? $bangunan->tipe_rumah_dinas->value : ($bangunan->tipe_rumah_dinas ?: 'Belum ditentukan') }}
                                </span>
                            </div>
                            <div class="col-12 col-md-8">
                                <span class="text-muted small d-block">Ketentuan Standar Luas</span>
                                <span class="text-dark small fw-medium">
                                    {{ is_object($bangunan->tipe_rumah_dinas) ? $bangunan->tipe_rumah_dinas->description() : '-' }}
                                </span>
                            </div>
                            <div class="col-12 border-top pt-2">
                                <span class="text-muted small d-block">Pejabat Penghuni / Pengguna</span>
                                <span class="fw-bold text-dark">{{ $bangunan->nama_penghuni ?: 'Kosong / Belum Dihuni' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Dokumen Perizinan (PBG / IMB) -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-check text-success"></i> Dokumen Perizinan &amp; Legalitas Gedung
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 align-items-center">
                        <div class="col-12 col-md-4">
                            <span class="text-muted small d-block">Nomor PBG / IMB</span>
                            <span class="fw-semibold text-dark">{{ $bangunan->nomor_dokumen_pbg ?: 'Belum Tercatat' }}</span>
                        </div>
                        <div class="col-12 col-md-4">
                            <span class="text-muted small d-block">Tanggal Terbit Dokumen</span>
                            <span class="fw-semibold text-dark">{{ $bangunan->tanggal_dokumen_pbg ? $bangunan->tanggal_dokumen_pbg->format('d F Y') : '-' }}</span>
                        </div>
                        <div class="col-12 col-md-4 text-md-end">
                            @if($bangunan->dokumen_pdf && \Illuminate\Support\Facades\Storage::disk('public')->exists($bangunan->dokumen_pdf))
                                <a href="{{ asset('storage/' . $bangunan->dokumen_pdf) }}" target="_blank" class="btn btn-sm btn-danger rounded-pill px-3">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> Buka Berkas PDF
                                </a>
                            @else
                                <span class="text-muted small fst-italic">Tidak ada lampiran PDF</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Tanah KIB A, OPD, Nilai & Audit Trail -->
        <div class="col-12 col-lg-4">
            <!-- Pengamanan Hukum: Relasi Tanah Dasar KIB A -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-shield-check text-primary"></i> Pengamanan Hukum (Tanah KIB A)
                    </h5>
                </div>
                <div class="card-body p-4">
                    @if($bangunan->asetTanah)
                        <div class="p-3 bg-primary-subtle rounded-3 mb-3 border border-primary-subtle">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="badge bg-primary">Terhubung KIB A</span>
                                <span class="font-monospace fw-bold text-primary">{{ $bangunan->asetTanah->kode_aset }}</span>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">{{ $bangunan->asetTanah->nama_aset }}</h6>
                            <p class="small text-muted mb-2">{{ $bangunan->asetTanah->lokasi }}</p>
                            <div class="d-flex justify-content-between small text-dark border-top pt-2">
                                <span>Luas Bidang Tanah:</span>
                                <strong>{{ number_format($bangunan->asetTanah->luas, 0) }} m²</strong>
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('sipat.aset.show', $bangunan->asetTanah) }}" class="btn btn-sm btn-primary rounded-pill w-100">
                                    <i class="bi bi-arrow-up-right-circle me-1"></i> Lihat Data Tanah SIPAT
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="p-3 bg-light rounded-3 mb-3">
                            <span class="text-muted small d-block">Status Tanah Tempat Berdiri</span>
                            <span class="fw-bold text-dark">{{ $bangunan->status_tanah_dasar ?: 'Non-KIB A (Belum Ditautkan)' }}</span>
                            @if($bangunan->luas_tanah_dasar)
                                <div class="small text-muted mt-1">Estimasi Luas Tanah: {{ number_format($bangunan->luas_tanah_dasar, 2) }} m²</div>
                            @endif
                        </div>
                    @endif

                    <div class="mb-3">
                        <span class="text-muted small d-block">Instansi Pengguna (OPD)</span>
                        <span class="fw-bold text-dark">{{ $bangunan->opdSipat?->nama ?? '-' }}</span>
                    </div>

                    <div class="mb-0">
                        <span class="text-muted small d-block">Asal Usul Perolehan</span>
                        <span class="fw-semibold text-dark">{{ $bangunan->asal_usul ?: 'APBD Kab' }}</span>
                    </div>
                </div>
            </div>

            <!-- Nilai Akuntansi & Keuangan -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-cash-stack text-success"></i> Nilai Akuntansi &amp; Aset
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <span class="text-muted small d-block">Harga Perolehan Awal</span>
                        <h4 class="fw-bold text-success mb-0">Rp {{ number_format($bangunan->harga_perolehan, 0, ',', '.') }}</h4>
                    </div>

                    @if($bangunan->nilai_buku)
                        <div class="mb-3">
                            <span class="text-muted small d-block">Nilai Buku Saat Ini (Setelah Penyusutan)</span>
                            <h5 class="fw-bold text-dark mb-0">Rp {{ number_format($bangunan->nilai_buku, 0, ',', '.') }}</h5>
                        </div>
                    @endif

                    <div class="border-top pt-3">
                        <span class="text-muted small d-block">Status Penetapan Status Penggunaan (PSP)</span>
                        <span class="badge bg-light text-secondary border mt-1">{{ $bangunan->status_psp ?: 'Belum PSP' }}</span>
                    </div>
                </div>
            </div>

            <!-- Jejak Audit Trail -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h6 class="fw-bold text-muted text-uppercase mb-0" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                        <i class="bi bi-clock-history me-1"></i> Jejak Audit Aset
                    </h6>
                </div>
                <div class="card-body p-4 pt-3 small text-muted">
                    <div class="mb-2">
                        <span>Didaftarkan:</span>
                        <div class="fw-medium text-dark">{{ $bangunan->created_at?->format('d F Y, H:i') }} WITA</div>
                        @if($bangunan->creator)
                            <span class="text-muted" style="font-size: 0.72rem;">Oleh: {{ $bangunan->creator->name }}</span>
                        @endif
                    </div>
                    <div>
                        <span>Terakhir Diperbarui:</span>
                        <div class="fw-medium text-dark">{{ $bangunan->updated_at?->format('d F Y, H:i') }} WITA</div>
                        @if($bangunan->updater)
                            <span class="text-muted" style="font-size: 0.72rem;">Oleh: {{ $bangunan->updater->name }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
