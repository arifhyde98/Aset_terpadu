@extends('layouts.app')

@section('title', 'Detail Surat Penyerahan - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.surat-penyerahan.index') }}" class="text-decoration-none text-secondary">Surat Penyerahan</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Detail Surat</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0">Detail Surat {{ $item->no_surat }}</h4>
        </div>
        <div class="d-flex gap-2">
            @if($item->pdf_path)
                <a href="{{ route('elabel.surat-penyerahan.pdf', $item->id) }}" target="_blank" class="btn btn-outline-danger shadow-sm fw-medium">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Lihat Scan PDF
                </a>
            @endif
            <a href="{{ route('elabel.surat-penyerahan.edit', $item->id) }}" class="btn btn-primary shadow-sm fw-medium">
                <i class="bi bi-pencil-square me-1"></i> Edit Data
            </a>
            <a href="{{ route('elabel.surat-penyerahan.index') }}" class="btn btn-light border fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 py-3 px-4">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-info-circle me-2 text-primary"></i> Informasi Surat Penyerahan Arsip</h6>
                </div>
                <div class="card-body p-4 pt-0">
                    <table class="table table-borderless align-middle mb-0">
                        <tbody>
                            <tr>
                                <td class="text-secondary small fw-semibold" style="width: 200px;">Nomor Surat</td>
                                <td class="fw-bold text-navy fs-5">: {{ $item->no_surat }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">NIBAR</td>
                                <td class="fw-medium text-dark">: {{ $item->nibar ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Jenis Penyerahan</td>
                                <td class="fw-medium text-dark">: {{ $item->jenis_penyerahan ?: 'Hibah' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Pemberi Hibah / Instansi</td>
                                <td class="fw-medium text-dark">: {{ $item->pemberi_hibah ?: '-' }}</td>
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
                                <td class="text-secondary small fw-semibold">Spesifikasi / Peruntukan</td>
                                <td class="fw-medium text-dark">: {{ $item->spesifikasi ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Luas Tanah / Bangunan</td>
                                <td class="fw-bold text-dark">: {{ $item->luas ? number_format($item->luas, 2) . ' m²' : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Tanggal Perolehan</td>
                                <td class="fw-medium text-dark">: {{ $item->tanggal_perolehan ? $item->tanggal_perolehan->format('d M Y') : '-' }}</td>
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
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-archive me-2 text-primary"></i> Penyimpanan Box Fisik</h6>
                </div>
                <div class="card-body p-4 pt-0 text-center">
                    <div class="p-3 bg-light rounded-4 mb-3 border">
                        <div class="text-uppercase fw-semibold text-secondary fs-7 mb-1">Kode Box Fisik</div>
                        <h3 class="fw-extrabold text-primary mb-1">{{ $item->box->box_code ?? '-' }}</h3>
                        <div class="small text-secondary"><i class="bi bi-geo-alt me-1"></i> {{ $item->box->lokasi ?? 'Lokasi tidak diatur' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
