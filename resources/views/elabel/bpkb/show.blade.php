@extends('layouts.app')

@section('title', 'Detail BPKB Kendaraan - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.bpkb.index') }}" class="text-decoration-none text-secondary">Katalog BPKB</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Detail BPKB</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h4 class="fw-bold text-navy mb-0">Detail BPKB {{ $item->plate_number }}</h4>
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
                        data-pdf-url="{{ route('elabel.bpkb.view-pdf', $item->id) }}"
                        data-pdf-title="Scan BPKB {{ $item->plate_number }}"
                        data-pdf-subtitle="No. BPKB: {{ $item->no_bpkb ?: '-' }} | Box: {{ $item->box->box_code ?? '-' }}"
                        data-pdf-badge="{{ $item->vehicle_type }}">
                    <i class="bi bi-arrows-fullscreen"></i>
                    <span>Pratinjau Penuh</span>
                </button>
            @endif
            <a href="{{ route('elabel.bpkb.edit', $item->id) }}" class="btn btn-primary shadow-sm fw-medium d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-pencil-square"></i>
                <span>Edit Data</span>
            </a>
            <a href="{{ route('elabel.bpkb.index') }}" class="btn btn-light border fw-medium d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-arrow-left"></i>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Kolom Data Atribut Kendaraan & Dokumen -->
        <div class="{{ $item->pdf_path ? 'col-lg-6 col-xl-6' : 'col-lg-8' }}">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-info-circle me-2 text-primary"></i> Informasi Dokumen & Kendaraan</h6>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2.5 py-1 rounded-pill small">
                        {{ $item->vehicle_type ?? 'Kendaraan' }}
                    </span>
                </div>
                <div class="card-body p-4 pt-3">
                    <table class="table table-borderless align-middle mb-0">
                        <tbody>
                            <tr>
                                <td class="text-secondary small fw-semibold" style="width: 190px;">Nomor Polisi</td>
                                <td class="fw-bold text-navy fs-5">: <span class="font-monospace">{{ $item->plate_number }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Jenis Kendaraan</td>
                                <td class="fw-medium text-dark">: {{ $item->vehicle_type }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Tahun Pembuatan</td>
                                <td class="fw-medium text-dark">: {{ $item->year ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Nomor BPKB</td>
                                <td class="fw-bold text-dark">: <span class="font-monospace">{{ $item->no_bpkb ?: '-' }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">NIBAR</td>
                                <td class="fw-medium text-dark">: <span class="font-monospace text-secondary">{{ $item->nibar ?: '-' }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Nomor Rangka</td>
                                <td class="fw-medium text-dark">: <span class="font-monospace">{{ $item->no_rangka ?: '-' }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Nomor Mesin</td>
                                <td class="fw-medium text-dark">: <span class="font-monospace">{{ $item->no_mesin ?: '-' }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Merek / Tipe</td>
                                <td class="fw-medium text-dark">: {{ $item->merek ?: '-' }} {{ $item->tipe ?: '' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Isi Silinder (CC)</td>
                                <td class="fw-medium text-dark">: {{ $item->isi_silinder ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Warna</td>
                                <td class="fw-medium text-dark">: {{ $item->warna ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Pemegang Kendaraan</td>
                                <td class="fw-medium text-dark">: {{ $item->pengguna ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Dinas / OPD Pengguna</td>
                                <td class="fw-medium text-dark">: {{ $item->opdSipat ? $item->opdSipat->nama : '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Penyimpanan Box Fisik -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-archive me-2 text-primary"></i> Penyimpanan Box Fisik</h6>
                </div>
                <div class="card-body p-4 text-center">
                    <div class="p-3 bg-light rounded-4 mb-0 border">
                        <div class="text-uppercase fw-semibold text-secondary fs-7 mb-1">Kode Box Fisik</div>
                        <h3 class="fw-extrabold text-primary mb-1">{{ $item->box->box_code ?? '-' }}</h3>
                        <div class="small text-secondary"><i class="bi bi-geo-alt me-1 text-danger"></i> {{ $item->box->location ?? 'Lokasi belum diatur' }}</div>
                    </div>
                </div>
            </div>

            <!-- Meta Data Audit -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-clock-history me-2 text-primary"></i> Catatan Riwayat & Audit</h6>
                </div>
                <div class="card-body p-4">
                    <div class="small text-secondary mb-2">Input Oleh: <strong class="text-dark">{{ $item->inputUser->name ?? 'User #' . $item->input_by }}</strong></div>
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
                            <h6 class="fw-bold text-navy mb-0">Pratinjau Dokumen BPKB</h6>
                        </div>
                        <div class="d-flex align-items-center gap-1.5">
                            <button type="button" 
                                    class="btn btn-sm btn-light border text-danger"
                                    data-pdf-preview="true"
                                    data-pdf-url="{{ route('elabel.bpkb.view-pdf', $item->id) }}"
                                    data-pdf-title="Scan BPKB {{ $item->plate_number }}"
                                    data-pdf-subtitle="No. BPKB: {{ $item->no_bpkb ?: '-' }} | Box: {{ $item->box->box_code ?? '-' }}"
                                    data-pdf-badge="{{ $item->vehicle_type }}"
                                    title="Tampilkan di Dialog Layar Penuh">
                                <i class="bi bi-arrows-fullscreen"></i>
                            </button>
                            <a href="{{ route('elabel.bpkb.view-pdf', $item->id) }}" target="_blank" class="btn btn-sm btn-outline-danger" title="Buka di Tab Browser Baru">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-2 bg-light">
                        <div class="border rounded-3 overflow-hidden bg-white shadow-inner" style="height: 680px;">
                            <iframe src="{{ route('elabel.bpkb.view-pdf', $item->id) }}" 
                                    class="w-100 h-100 border-0" 
                                    title="Scan PDF BPKB {{ $item->plate_number }}">
                            </iframe>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top py-2.5 px-4 d-flex justify-content-between align-items-center small text-secondary">
                        <div>
                            <i class="bi bi-shield-check text-success me-1"></i> Berkas Resmi Lokal
                        </div>
                        <div>
                            <span class="font-monospace">{{ $item->plate_number }}</span> &bull; Box {{ $item->box->box_code ?? '-' }}
                        </div>
                    </div>
                </div>
            @else
                <div class="card border-0 shadow-sm rounded-4 text-center p-5">
                    <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 68px; height: 68px;">
                        <i class="bi bi-file-earmark-x fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-navy mb-1">Belum Ada File Scan</h5>
                    <p class="text-secondary small mb-4">Berkas digital scan BPKB untuk kendaraan ini belum diunggah ke sistem eLABEL.</p>
                    <div>
                        <a href="{{ route('elabel.bpkb.edit', $item->id) }}" class="btn btn-outline-primary btn-sm fw-medium px-3">
                            <i class="bi bi-cloud-upload me-1"></i> Unggah Scan Sekarang
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
