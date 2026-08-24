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
            <h4 class="fw-bold text-navy mb-0">Detail BPKB {{ $item->plate_number }}</h4>
        </div>
        <div class="d-flex gap-2">
            @if($item->pdf_path)
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-3 py-2 rounded-pill d-inline-flex align-items-center">
                        <i class="bi bi-hdd me-1.5"></i> Harddisk Lokal
                    </span>
                <a href="{{ route('elabel.bpkb.view-pdf', $item->id) }}" target="_blank" class="btn btn-outline-danger shadow-sm fw-medium">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Lihat Scan PDF
                </a>
            @endif
            <a href="{{ route('elabel.bpkb.edit', $item->id) }}" class="btn btn-primary shadow-sm fw-medium">
                <i class="bi bi-pencil-square me-1"></i> Edit Data
            </a>
            <a href="{{ route('elabel.bpkb.index') }}" class="btn btn-light border fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 py-3 px-4">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-info-circle me-2 text-primary"></i> Informasi Dokumen & Kendaraan</h6>
                </div>
                <div class="card-body p-4 pt-0">
                    <table class="table table-borderless align-middle mb-0">
                        <tbody>
                            <tr>
                                <td class="text-secondary small fw-semibold" style="width: 200px;">Nomor Polisi</td>
                                <td class="fw-bold text-navy fs-5">: {{ $item->plate_number }}</td>
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
                                <td class="fw-medium text-dark">: {{ $item->no_bpkb ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">NIBAR</td>
                                <td class="fw-medium text-dark">: {{ $item->nibar ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Nomor Rangka</td>
                                <td class="fw-medium text-dark">: {{ $item->no_rangka ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Nomor Mesin</td>
                                <td class="fw-medium text-dark">: {{ $item->no_mesin ?: '-' }}</td>
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
                                <td class="text-secondary small fw-semibold">Pemegang Kendaraan (Personal)</td>
                                <td class="fw-medium text-dark">: {{ $item->pengguna ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-secondary small fw-semibold">Dinas / OPD (SIPAT)</td>
                                <td class="fw-medium text-dark">: {{ $item->opdSipat ? $item->opdSipat->nama : '-' }}</td>
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
                        <div class="small text-secondary"><i class="bi bi-geo-alt me-1"></i> {{ $item->box->location ?? 'Lokasi tidak diatur' }}</div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 py-3 px-4">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-person me-2 text-primary"></i> Meta Data Audit</h6>
                </div>
                <div class="card-body p-4 pt-0">
                    <div class="small text-secondary mb-2">Input Oleh: <strong class="text-dark">{{ $item->inputUser->name ?? 'User #' . $item->input_by }}</strong></div>
                    <div class="small text-secondary mb-2">Dibuat Pada: <strong class="text-dark">{{ $item->created_at ? $item->created_at->format('d M Y H:i') : '-' }}</strong></div>
                    <div class="small text-secondary">Diubah Pada: <strong class="text-dark">{{ $item->updated_at ? $item->updated_at->format('d M Y H:i') : '-' }}</strong></div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
