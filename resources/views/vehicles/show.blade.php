@extends('layouts.app')

@section('title', 'Detail Kendaraan - ' . $vehicle->no_polisi)

@section('content')
<div class="container-fluid px-0">
    
    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}" class="text-decoration-none text-secondary">Data Kendaraan</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Detail</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-navy mb-0">Detail Kendaraan</h3>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('vehicles.index') }}" class="btn btn-light border bg-white shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-primary shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-pencil-square"></i> Edit Data
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- LEFT COLUMN: PHOTO & PRIMARY INFO -->
        <div class="col-xl-4">
            <div class="admin-card p-4 text-center">
                <div class="mb-4">
                    @if($vehicle->foto_kendaraan && count($vehicle->foto_kendaraan) > 0)
                        <div id="vehicleCarousel" class="carousel slide rounded-4 overflow-hidden border shadow-sm" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                @foreach($vehicle->foto_kendaraan as $index => $path)
                                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                        <img src="{{ asset('storage/' . $path) }}" class="d-block w-100" style="height: 300px; object-fit: cover;" alt="Foto {{ $index + 1 }}">
                                    </div>
                                @endforeach
                            </div>
                            @if(count($vehicle->foto_kendaraan) > 1)
                                <div class="carousel-indicators mb-0" style="bottom: 10px;">
                                    @foreach($vehicle->foto_kendaraan as $index => $path)
                                        <button type="button" data-bs-target="#vehicleCarousel" data-bs-slide-to="{{ $index }}" class="{{ $index === 0 ? 'active' : '' }}" aria-current="{{ $index === 0 ? 'true' : 'false' }}" aria-label="Slide {{ $index + 1 }}"></button>
                                    @endforeach
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#vehicleCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#vehicleCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                            @endif
                        </div>
                        <div class="row g-2 mt-2">
                            @foreach($vehicle->foto_kendaraan as $index => $path)
                                <div class="col-3">
                                    <img src="{{ asset('storage/' . $path) }}" 
                                         class="img-fluid rounded border cursor-pointer opacity-75 hover-opacity-100 transition-all" 
                                         style="height: 60px; width: 100%; object-fit: cover;"
                                         onclick="bootstrap.Carousel.getInstance(document.getElementById('vehicleCarousel')).to({{ $index }})">
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="bg-light rounded-4 p-5 d-flex align-items-center justify-content-center border border-dashed">
                            <i class="bi bi-car-front text-primary opacity-25" style="font-size: 8rem;"></i>
                        </div>
                        <p class="text-secondary small mt-2 italic">Belum ada foto kendaraan diunggah</p>
                    @endif
                </div>
                <h4 class="fw-bold text-navy mb-1">{{ $vehicle->merk }} {{ $vehicle->tipe }}</h4>
                <div class="badge bg-light text-dark border border-secondary border-opacity-25 px-4 py-2 fs-5 rounded-3 fw-bold mb-3">
                    {{ $vehicle->no_polisi }}
                </div>
                @if($vehicle->nomor_register)
                    <div class="text-secondary small mb-3">
                        <i class="bi bi-tag-fill me-1"></i> No. Register: <span class="fw-bold text-dark">{{ $vehicle->nomor_register }}</span>
                    </div>
                @endif
                <div class="d-flex justify-content-center mb-4">
                    <x-status-badge :status="$vehicle->status" />
                </div>
                
                <hr class="my-4 opacity-50">
                
                <div class="row text-start g-3">
                    <div class="col-6">
                        <small class="text-secondary d-block">Jenis Kendaraan</small>
                        <span class="fw-bold text-dark">{{ $vehicle->vehicleType->name ?? ($vehicle->jenis ?? '-') }}</span>
                    </div>
                    <div class="col-6">
                        <small class="text-secondary d-block">Tahun Pembuatan</small>
                        <span class="fw-bold text-dark">{{ $vehicle->tahun_pembuatan ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: DETAILED INFO -->
        <div class="col-xl-8">
            <!-- SPECIFICATIONS CARD -->
            <div class="admin-card p-4 mb-4">
                <div class="d-flex align-items-center mb-4 pb-2 border-bottom">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 me-3">
                        <i class="bi bi-info-circle-fill fs-5"></i>
                    </div>
                    <h5 class="fw-bold text-navy mb-0">Informasi Teknis</h5>
                </div>
                
                <div class="row g-4">
                    <div class="col-md-12">
                        <div class="p-3 bg-light rounded-3 border-start border-navy border-4">
                            <small class="text-secondary d-block text-uppercase fw-bold letter-spacing-1 mb-1" style="font-size: 0.65rem;">Nomor Register</small>
                            <span class="fw-bold text-dark fs-5">{{ $vehicle->nomor_register ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border-start border-primary border-4">
                            <small class="text-secondary d-block text-uppercase fw-bold letter-spacing-1 mb-1" style="font-size: 0.65rem;">Nomor Mesin</small>
                            <span class="fw-bold text-dark fs-5">{{ $vehicle->no_mesin ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border-start border-primary border-4">
                            <small class="text-secondary d-block text-uppercase fw-bold letter-spacing-1 mb-1" style="font-size: 0.65rem;">Nomor Rangka</small>
                            <span class="fw-bold text-dark fs-5">{{ $vehicle->no_rangka ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <small class="text-secondary d-block mb-1">STNK</small>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi {{ $vehicle->stnk_ada == 'Ada' ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' }}"></i>
                            <span class="fw-bold">{{ $vehicle->stnk_ada ?? 'Tidak Ada' }}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <small class="text-secondary d-block mb-1">BPKB</small>
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi {{ $vehicle->bpkb_ada == 'Ada' ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' }}"></i>
                                <span class="fw-bold">{{ $vehicle->bpkb_ada ?? 'Tidak Ada' }}</span>
                            </div>
                            @if($bpkb)
                                <a href="{{ route('elabel.bpkb.view-pdf', $bpkb->id) }}" target="_blank" class="btn btn-xs btn-outline-danger py-0.5 px-2 rounded-pill fw-semibold" style="font-size: 0.72rem;">
                                    <i class="bi bi-file-earmark-pdf me-0.5"></i> Lihat BPKB
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <small class="text-secondary d-block mb-1">Tanggal Perolehan</small>
                        <span class="fw-bold text-dark">{{ $vehicle->tgl_perolehan ? \Carbon\Carbon::parse($vehicle->tgl_perolehan)->translatedFormat('d F Y') : '-' }}</span>
                    </div>
                    <div class="col-md-12">
                        <div class="p-3 border rounded-3 bg-white">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="bg-success bg-opacity-10 text-success p-2 rounded-3">
                                        <i class="bi bi-currency-dollar fs-4"></i>
                                    </div>
                                </div>
                                <div class="col">
                                    <small class="text-secondary d-block mb-0">Nilai Perolehan / Aset</small>
                                    <h4 class="fw-bold text-navy mb-0">Rp {{ number_format($vehicle->nilai_perolehan, 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- OWNERSHIP CARD -->
            <div class="admin-card p-4">
                <div class="d-flex align-items-center mb-4 pb-2 border-bottom">
                    <div class="bg-success bg-opacity-10 text-success rounded-3 p-2 me-3">
                        <i class="bi bi-person-badge-fill fs-5"></i>
                    </div>
                    <h5 class="fw-bold text-navy mb-0">Data Pengguna & Instansi</h5>
                </div>
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <small class="text-secondary d-block mb-1">OPD / Instansi Pemilik</small>
                        <div class="p-3 bg-light rounded-3">
                            <i class="bi bi-building me-2 text-secondary"></i>
                            <span class="fw-bold text-dark">{{ $vehicle->opd }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <small class="text-secondary d-block mb-1">Pemegang / Pengguna</small>
                        <div class="p-3 bg-light rounded-3">
                            <i class="bi bi-person-fill me-2 text-secondary"></i>
                            <span class="fw-bold text-dark">{{ $vehicle->pemegang }}</span>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <small class="text-secondary d-block mb-1">Catatan / Keterangan</small>
                        <div class="p-3 bg-white border rounded-3 min-vh-10" style="min-height: 100px;">
                            <p class="text-secondary mb-0 italic">{{ $vehicle->keterangan ?: 'Tidak ada keterangan tambahan untuk kendaraan ini.' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
