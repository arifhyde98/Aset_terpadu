@extends('layouts.app')

@section('title', 'Detail Sertifikat Tanah - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.sertifikat.index') }}" class="text-decoration-none text-secondary">Sertifikat Tanah</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Detail Sertifikat</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h4 class="fw-bold text-navy mb-0">Detail Sertifikat {{ $item->no_sertipikat }}</h4>
                @if($item->pdf_path)
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1 rounded-pill small">
                        <i class="bi bi-file-earmark-check me-1"></i> Scan Tersedia
                    </span>
                @else
                    <span class="badge bg-warning bg-opacity-10 text-warning text-dark border border-warning border-opacity-25 px-2.5 py-1 rounded-pill small">
                        <i class="bi bi-file-earmark-x me-1"></i> Belum Ada Scan
                    </span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($item->pdf_path)
                <button type="button" 
                        class="btn btn-outline-danger shadow-sm fw-medium d-inline-flex align-items-center gap-1.5"
                        data-pdf-preview="true"
                        data-pdf-url="{{ route('elabel.sertifikat.view-pdf', $item->id) }}"
                        data-pdf-title="Scan Sertifikat {{ $item->no_sertipikat }}"
                        data-pdf-subtitle="Pemilik: {{ $item->nama_pemilik ?: '-' }} | Box: {{ $item->box->box_code ?? '-' }}"
                        data-pdf-badge="Sertifikat Tanah">
                    <i class="bi bi-arrows-fullscreen"></i>
                    <span>Pratinjau Penuh</span>
                </button>
            @endif
            <a href="{{ route('elabel.sertifikat.edit', $item->id) }}" class="btn btn-success shadow-sm fw-medium d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-pencil-square"></i>
                <span>Edit Data</span>
            </a>
            <a href="{{ route('elabel.sertifikat.index') }}" class="btn btn-light border fw-medium d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-arrow-left"></i>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Kolom Data Atribut Pertanahan & Dokumen -->
        <div class="{{ $item->pdf_path ? 'col-lg-6 col-xl-6' : 'col-lg-8' }}">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-info-circle me-2 text-success"></i> Informasi Sertifikat Tanah</h6>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1 rounded-pill small">
                        {{ $item->spesifikasi ?: 'Hak Tanah' }}
                    </span>
                </div>
                <div class="card-body p-4 pt-3">
                    <table class="table table-borderless align-middle mb-0">
                        <tbody>
                            <tr>
                                <td class="text-secondary small fw-semibold" style="width: 190px;">Nomor Sertipikat</td>
                                <td class="fw-bold text-navy fs-5">: <span class="font-monospace">{{ $item->no_sertipikat }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Tanggal Sertifikat</td>
                                <td class="fw-medium text-dark">: {{ $item->tanggal_sertifikat ? $item->tanggal_sertifikat->format('d M Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">NIBAR</td>
                                <td class="fw-medium text-dark">: <span class="font-monospace text-secondary">{{ $item->nibar ?: '-' }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Nama Pemilik / Atas Nama</td>
                                <td class="fw-bold text-dark">: {{ $item->nama_pemilik ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Dinas / OPD Pengguna</td>
                                <td class="fw-medium text-dark">: {{ $item->opdSipat ? $item->opdSipat->nama : ($item->dinas ?: '-') }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Status Penggunaan</td>
                                <td class="fw-medium text-dark">: {{ $item->status_penggunaan ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Spesifikasi / Jenis Hak</td>
                                <td class="fw-medium text-dark">: {{ $item->spesifikasi ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Luas Tanah</td>
                                <td class="fw-bold text-dark">: {{ $item->luas ? number_format($item->luas, 2) . ' m²' : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Nilai Perolehan</td>
                                <td class="fw-bold text-success">: {{ $item->nilai_perolehan ? 'Rp ' . number_format($item->nilai_perolehan, 0, ',', '.') : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Tanggal Perolehan</td>
                                <td class="fw-medium text-dark">: {{ $item->tanggal_perolehan ? $item->tanggal_perolehan->format('d M Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Cara Perolehan</td>
                                <td class="fw-medium text-dark">: {{ $item->cara_perolehan ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Lokasi / Kecamatan</td>
                                <td class="fw-medium text-dark">: {{ $item->lokasi ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Alamat Lengkap</td>
                                <td class="fw-medium text-dark">: {{ $item->alamat ?: '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Penyimpanan Box Fisik -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-archive me-2 text-success"></i> Penyimpanan Box Fisik</h6>
                </div>
                <div class="card-body p-4 text-center">
                    <div class="p-3 bg-light rounded-4 mb-0 border">
                        <div class="text-uppercase fw-semibold text-secondary fs-7 mb-1">Kode Box Fisik</div>
                        <h3 class="fw-extrabold text-success mb-1">{{ $item->box->box_code ?? '-' }}</h3>
                        <div class="small text-secondary"><i class="bi bi-geo-alt me-1 text-danger"></i> {{ $item->box->lokasi ?? 'Lokasi belum diatur' }}</div>
                    </div>
                </div>
            </div>

            <!-- Meta Data Audit -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-clock-history me-2 text-success"></i> Catatan Riwayat & Audit</h6>
                </div>
                <div class="card-body p-4">
                    <div class="small text-secondary mb-2">Dibuat Pada: <strong class="text-dark">{{ $item->created_at ? $item->created_at->format('d M Y H:i') : '-' }}</strong></div>
                    <div class="small text-secondary">Diubah Pada: <strong class="text-dark">{{ $item->updated_at ? $item->updated_at->format('d M Y H:i') : '-' }}</strong></div>
                </div>
            </div>
        </div>

        <!-- Kolom Pratinjau Dokumen PDF Scan (Side-by-Side) -->
        <div class="{{ $item->pdf_path ? 'col-lg-6 col-xl-6' : 'col-lg-4' }}">
            @if($item->pdf_path)
                <div class="card border-0 shadow-sm rounded-4 position-sticky" style="top: 1.25rem;">
                    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-pdf fs-5 text-danger"></i>
                            <h6 class="fw-bold text-navy mb-0">Pratinjau Scan Sertifikat</h6>
                        </div>
                        <div class="d-flex align-items-center gap-1.5">
                            <button type="button" 
                                    class="btn btn-sm btn-light border text-danger"
                                    data-pdf-preview="true"
                                    data-pdf-url="{{ route('elabel.sertifikat.view-pdf', $item->id) }}"
                                    data-pdf-title="Scan Sertifikat {{ $item->no_sertipikat }}"
                                    data-pdf-subtitle="Pemilik: {{ $item->nama_pemilik ?: '-' }} | Box: {{ $item->box->box_code ?? '-' }}"
                                    data-pdf-badge="Sertifikat Tanah"
                                    title="Tampilkan di Dialog Layar Penuh">
                                <i class="bi bi-arrows-fullscreen"></i>
                            </button>
                            <a href="{{ route('elabel.sertifikat.view-pdf', $item->id) }}" target="_blank" class="btn btn-sm btn-outline-danger" title="Buka di Tab Browser Baru">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-2 bg-light">
                        <div class="border rounded-3 overflow-hidden bg-white shadow-inner" style="height: 680px;">
                            <iframe src="{{ route('elabel.sertifikat.view-pdf', $item->id) }}" 
                                    class="w-100 h-100 border-0" 
                                    title="Scan PDF Sertifikat {{ $item->no_sertipikat }}">
                            </iframe>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top py-2.5 px-4 d-flex justify-content-between align-items-center small text-secondary">
                        <div>
                            <i class="bi bi-shield-check text-success me-1"></i> Berkas Resmi Lokal
                        </div>
                        <div>
                            <span class="font-monospace">{{ $item->no_sertipikat }}</span> &bull; Box {{ $item->box->box_code ?? '-' }}
                        </div>
                    </div>
                </div>
            @else
                <div class="card border-0 shadow-sm rounded-4 text-center p-5">
                    <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 68px; height: 68px;">
                        <i class="bi bi-file-earmark-x fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-navy mb-1">Belum Ada File Scan</h5>
                    <p class="text-secondary small mb-4">Berkas digital scan sertifikat tanah ini belum diunggah ke sistem eLABEL.</p>
                    <div>
                        <a href="{{ route('elabel.sertifikat.edit', $item->id) }}" class="btn btn-outline-success btn-sm fw-medium px-3">
                            <i class="bi bi-cloud-upload me-1"></i> Unggah Scan Sekarang
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
