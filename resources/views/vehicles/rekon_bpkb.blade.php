@extends('layouts.app')

@section('title', 'Rekonsiliasi BPKB - e-RANDIS')

@section('content')
<div class="page-header-global mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-warning-subtle text-warning-emphasis fw-semibold px-2.5 py-1 rounded-pill" style="font-size: 0.75rem;">
                    <i class="bi bi-arrow-left-right me-1"></i> MODUL E-RANDIS
                </span>
            </div>
            <h2 class="fw-bold mb-1 text-navy">Rekonsiliasi BPKB</h2>
            <p class="text-secondary small mb-0">Pencocokan data fisik kendaraan dinas (Motor & Mobil) di e-Randis dengan arsip BPKB di eLABEL</p>
        </div>
        <div>
            <a href="{{ route('vehicles.index') }}" class="btn btn-light border fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Kendaraan
            </a>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card clean-card h-100 border-0 bg-warning text-dark shadow-sm">
            <div class="card-body p-4 d-flex align-items-center">
                <div class="rounded-circle bg-dark bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 55px; height: 55px;">
                    <i class="bi bi-card-checklist fs-2 text-dark"></i>
                </div>
                <div>
                    <h2 class="fw-bold mb-0 text-dark font-monospace">{{ $totalElabel }}</h2>
                    <div class="small text-dark text-opacity-75 fw-medium">Total BPKB di eLABEL</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card clean-card h-100 border-0 bg-success text-white shadow-sm">
            <div class="card-body p-4 d-flex align-items-center">
                <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center me-3" style="width: 55px; height: 55px;">
                    <i class="bi bi-check-circle fs-2"></i>
                </div>
                <div>
                    <h2 class="fw-bold mb-0 font-monospace">{{ count($matchList) }}</h2>
                    <div class="small text-white-50 fw-medium">Cocok (Sudah Diarsipkan)</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card clean-card h-100 border-0 bg-danger text-white shadow-sm">
            <div class="card-body p-4 d-flex align-items-center">
                <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center me-3" style="width: 55px; height: 55px;">
                    <i class="bi bi-exclamation-triangle fs-2"></i>
                </div>
                <div>
                    <h2 class="fw-bold mb-0 font-monospace">{{ count($missList) }}</h2>
                    <div class="small text-white-50 fw-medium">Selisih (Belum Diarsipkan)</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card clean-card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <ul class="nav nav-pills" id="rekonTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-pill fw-semibold px-4" id="miss-tab" data-bs-toggle="tab" data-bs-target="#miss-tab-pane" type="button" role="tab">
                    <i class="bi bi-x-circle me-1"></i> Selisih <span class="badge bg-danger ms-1">{{ count($missList) }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill fw-semibold px-4 ms-2" id="match-tab" data-bs-toggle="tab" data-bs-target="#match-tab-pane" type="button" role="tab">
                    <i class="bi bi-check-circle me-1"></i> Cocok <span class="badge bg-success ms-1">{{ count($matchList) }}</span>
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body p-0">
        <div class="tab-content" id="rekonTabsContent">
            
            <!-- TAB 1: Selisih -->
            <div class="tab-pane fade show active" id="miss-tab-pane" role="tabpanel">
                <div class="p-3 bg-light text-secondary small border-bottom">
                    <i class="bi bi-info-circle-fill text-warning me-1"></i> 
                    <strong>Selisih:</strong> Kendaraan dinas yang terdaftar di E-Randis namun berkas BPKB fisiknya <strong>belum ditemukan/belum diunggah</strong> di eLABEL.
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3 px-4 text-center" style="width: 50px;">No</th>
                                <th class="py-3" style="width: 150px;">No. Polisi</th>
                                <th class="py-3">Kategori</th>
                                <th class="py-3">Merk / Tipe</th>
                                <th class="py-3">No. Mesin / Rangka</th>
                                <th class="py-3">OPD Pengelola</th>
                                <th class="py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($missList as $idx => $v)
                                @php
                                    $isMotor = Str::contains(strtolower($v->jenis . ($v->vehicleType->name ?? '')), ['motor', 'roda dua', 'roda 2', 'r2']);
                                @endphp
                                <tr>
                                    <td class="px-4 text-center fw-medium text-secondary">{{ $idx + 1 }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border border-secondary border-opacity-25 px-2.5 py-1.5 fw-bold font-monospace fs-6">
                                            {{ $v->no_polisi }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($isMotor)
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded px-2.5 py-1 fw-bold">
                                                <i class="bi bi-bicycle me-1"></i> R2 (Motor)
                                            </span>
                                        @else
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded px-2.5 py-1 fw-bold">
                                                <i class="bi bi-car-front me-1"></i> R4 (Mobil)
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $v->merk }}</div>
                                        <div class="small text-secondary">{{ $v->tipe ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <div class="small"><span class="text-secondary">Mesin:</span> <strong class="font-monospace text-navy">{{ $v->no_mesin ?: '-' }}</strong></div>
                                        <div class="small"><span class="text-secondary">Rangka:</span> <strong class="font-monospace text-navy">{{ $v->no_rangka ?: '-' }}</strong></div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-dark">{{ $v->opd }}</div>
                                        <div class="small text-secondary">Pemegang: {{ $v->pemegang ?: '-' }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-1.5 rounded-pill small">
                                            {{ $v->reason }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-secondary">
                                        <i class="bi bi-shield-check text-success fs-1 mb-2 d-block"></i>
                                        <span class="fw-bold">Tidak ada selisih.</span> Semua BPKB sudah terarsipkan dengan baik di eLABEL!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: Cocok -->
            <div class="tab-pane fade" id="match-tab-pane" role="tabpanel">
                <div class="p-3 bg-light text-secondary small border-bottom">
                    <i class="bi bi-check-circle-fill text-success me-1"></i> 
                    <strong>Cocok:</strong> Kendaraan dinas yang datanya cocok dengan berkas fisik arsip BPKB di eLABEL (berdasarkan kecocokan No. Mesin & Rangka).
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3 px-4 text-center" style="width: 50px;">No</th>
                                <th class="py-3" style="width: 150px;">No. Polisi</th>
                                <th class="py-3">Kategori</th>
                                <th class="py-3">Merk / Tipe</th>
                                <th class="py-3">No. Mesin / Rangka</th>
                                <th class="py-3">Box BPKB (eLABEL)</th>
                                <th class="py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($matchList as $idx => $v)
                                @php
                                    $isMotor = Str::contains(strtolower($v->jenis . ($v->vehicleType->name ?? '')), ['motor', 'roda dua', 'roda 2', 'r2']);
                                @endphp
                                <tr>
                                    <td class="px-4 text-center fw-medium text-secondary">{{ $idx + 1 }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border border-secondary border-opacity-25 px-2.5 py-1.5 fw-bold font-monospace fs-6">
                                            {{ $v->no_polisi }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($isMotor)
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded px-2.5 py-1 fw-bold">
                                                <i class="bi bi-bicycle me-1"></i> R2 (Motor)
                                            </span>
                                        @else
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded px-2.5 py-1 fw-bold">
                                                <i class="bi bi-car-front me-1"></i> R4 (Mobil)
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $v->merk }}</div>
                                        <div class="small text-secondary">{{ $v->tipe ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <div class="small"><span class="text-secondary">Mesin:</span> <strong class="font-monospace text-navy">{{ $v->no_mesin ?: '-' }}</strong></div>
                                        <div class="small"><span class="text-secondary">Rangka:</span> <strong class="font-monospace text-navy">{{ $v->no_rangka ?: '-' }}</strong></div>
                                    </td>
                                    <td>
                                        @if(isset($v->bpkb_record->box))
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-3 fw-bold">
                                                <i class="bi bi-archive me-1"></i> {{ $v->bpkb_record->box->box_code }}
                                            </span>
                                            <div class="small text-secondary mt-1"><i class="bi bi-geo-alt"></i> Rak: {{ $v->bpkb_record->box->location ?? '-' }}</div>
                                        @else
                                            <span class="text-muted small">Box belum ditentukan</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-1.5 rounded-pill small">
                                            Terarsip (Cocok)
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-secondary">
                                        <i class="bi bi-inbox fs-1 mb-2 d-block"></i>
                                        Belum ada data kendaraan yang cocok dengan arsip BPKB di eLABEL.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
