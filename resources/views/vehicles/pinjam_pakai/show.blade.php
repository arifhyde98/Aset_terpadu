@extends('layouts.app')

@section('title', 'Detail Kendaraan Pinjam Pakai')

@section('content')
<div class="container-fluid px-0">

    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('vehicles.pinjam-pakai.index') }}" class="text-decoration-none text-secondary">Pinjam Pakai BMD</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Detail Perjanjian</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-navy mb-0">Detail Perjanjian Pinjam Pakai</h3>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('vehicles.pinjam-pakai.index') }}" class="btn btn-light border shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <a href="{{ route('vehicles.pinjam-pakai.edit', $pinjamPakai->id) }}" class="btn btn-warning shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-pencil-square"></i> Edit
            </a>
        </div>
    </div>

    <!-- DETAIL CONTENT CARD -->
    <div class="row g-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
                        <div>
                            <span class="badge bg-light text-dark border rounded-pill mb-2">{{ $pinjamPakai->kategori_peminjam }}</span>
                            <h4 class="fw-bold text-navy mb-1">{{ $pinjamPakai->nama_instansi_peminjam }}</h4>
                            <p class="text-secondary small mb-0">Dokumen NPPP/BAST: <strong>{{ $pinjamPakai->nomor_nppp_bast }}</strong></p>
                        </div>
                        <div>
                            {!! $pinjamPakai->status_badge !!}
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-secondary d-block mb-1">Nama Penanggung Jawab</small>
                            <div class="fw-semibold text-dark">{{ $pinjamPakai->nama_penanggung_jawab }}</div>
                        </div>
                        <div class="col-md-6">
                            <small class="text-secondary d-block mb-1">NIP / NRP</small>
                            <div class="fw-semibold text-dark">{{ $pinjamPakai->nip_penanggung_jawab ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <small class="text-secondary d-block mb-1">Jabatan</small>
                            <div class="fw-semibold text-dark">{{ $pinjamPakai->jabatan_penanggung_jawab ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <small class="text-secondary d-block mb-1">Nomor Kontak / HP</small>
                            <div class="fw-semibold text-dark">{{ $pinjamPakai->kontak_penanggung_jawab ?? '-' }}</div>
                        </div>
                        <div class="col-12"><hr class="my-2 opacity-25"></div>
                        <div class="col-md-6">
                            <small class="text-secondary d-block mb-1">Tanggal Mulai Pinjam Pakai</small>
                            <div class="fw-semibold text-navy"><i class="bi bi-calendar-check text-success me-1"></i> {{ $pinjamPakai->tanggal_mulai ? $pinjamPakai->tanggal_mulai->format('d F Y') : '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <small class="text-secondary d-block mb-1">Tanggal Akhir Perjanjian</small>
                            <div class="fw-semibold text-danger"><i class="bi bi-calendar-x text-danger me-1"></i> {{ $pinjamPakai->tanggal_selesai ? $pinjamPakai->tanggal_selesai->format('d F Y') : '-' }}</div>
                        </div>
                        <div class="col-12">
                            <small class="text-secondary d-block mb-1">Keterangan / Catatan Tambahan</small>
                            <div class="p-3 bg-light rounded-3 text-dark small">{{ $pinjamPakai->keterangan ?? 'Tidak ada keterangan tambahan.' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- KENDARAAN INFO CARD -->
            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-car-front-fill text-primary me-2"></i> Kendaraan Dinas (BMD)</h6>
                </div>
                <div class="card-body p-4">
                    @if($pinjamPakai->vehicle)
                        <div class="text-center pb-3 border-bottom mb-3">
                            <div class="display-6 fw-bold text-navy font-monospace mb-1">{{ $pinjamPakai->vehicle->no_polisi ?? 'Tanpa Nopol' }}</div>
                            <div class="fw-semibold text-secondary">{{ $pinjamPakai->vehicle->merk_tipe ?? '' }}</div>
                        </div>
                        <div class="small">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-secondary">No. Rangka:</span>
                                <span class="fw-semibold font-monospace">{{ $pinjamPakai->vehicle->no_rangka ?? '-' }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-secondary">No. Mesin:</span>
                                <span class="fw-semibold font-monospace">{{ $pinjamPakai->vehicle->no_mesin ?? '-' }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-secondary">Tahun Pembuatan:</span>
                                <span class="fw-semibold">{{ $pinjamPakai->vehicle->tahun_pembuatan ?? '-' }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-secondary">OPD Pemilik:</span>
                                <span class="fw-semibold text-end">{{ $pinjamPakai->opd->nama ?? 'BPKAD' }}</span>
                            </div>
                        </div>
                    @else
                        <div class="text-muted small text-center">Data kendaraan telah dihapus</div>
                    @endif
                </div>
            </div>

            <!-- DOKUMEN PDF CARD -->
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-file-earmark-pdf-fill text-danger me-2"></i> Dokumen NPPP / BAST</h6>
                </div>
                <div class="card-body p-4 text-center">
                    @if($pinjamPakai->file_dokumen_pdf)
                        <i class="bi bi-file-earmark-pdf text-danger display-4 d-block mb-2"></i>
                        <p class="small text-secondary mb-3">Salinan digital PDF Naskah Perjanjian Pinjam Pakai / BAST resmi.</p>
                        <a href="{{ $pinjamPakai->dokumen_url }}" target="_blank" class="btn btn-outline-danger w-100 fw-bold rounded-pill">
                            <i class="bi bi-download me-1"></i> Buka / Download PDF
                        </a>
                    @else
                        <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i> Belum ada file PDF yang diunggah.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
