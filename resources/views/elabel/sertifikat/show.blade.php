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
            <h4 class="fw-bold text-navy mb-0">Detail Sertifikat {{ $item->no_sertipikat }}</h4>
        </div>
        <div class="d-flex gap-2">
            @if($item->pdf_path)
                <a href="{{ route('elabel.sertifikat.view-pdf', $item->id) }}" target="_blank" class="btn btn-outline-danger shadow-sm fw-medium">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Lihat Scan PDF
                </a>
            @endif
            <a href="{{ route('elabel.sertifikat.edit', $item->id) }}" class="btn btn-success shadow-sm fw-medium">
                <i class="bi bi-pencil-square me-1"></i> Edit Data
            </a>
            <a href="{{ route('elabel.sertifikat.index') }}" class="btn btn-light border fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 py-3 px-4">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-info-circle me-2 text-success"></i> Informasi Sertifikat Tanah</h6>
                </div>
                <div class="card-body p-4 pt-0">
                    <table class="table table-borderless align-middle mb-0">
                        <tbody>
                            <tr>
                                <td class="text-secondary small fw-semibold" style="width: 200px;">Nomor Sertipikat</td>
                                <td class="fw-bold text-navy fs-5">: {{ $item->no_sertipikat }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">NIBAR</td>
                                <td class="fw-medium text-dark">: {{ $item->nibar ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Nama Pemilik / Atas Nama</td>
                                <td class="fw-medium text-dark">: {{ $item->nama_pemilik ?: '-' }}</td>
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
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 py-3 px-4">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-archive me-2 text-success"></i> Penyimpanan Box Fisik</h6>
                </div>
                <div class="card-body p-4 pt-0 text-center">
                    <div class="p-3 bg-light rounded-4 mb-3 border">
                        <div class="text-uppercase fw-semibold text-secondary fs-7 mb-1">Kode Box Fisik</div>
                        <h3 class="fw-extrabold text-success mb-1">{{ $item->box->box_code ?? '-' }}</h3>
                        <div class="small text-secondary"><i class="bi bi-geo-alt me-1"></i> {{ $item->box->lokasi ?? 'Lokasi tidak diatur' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
