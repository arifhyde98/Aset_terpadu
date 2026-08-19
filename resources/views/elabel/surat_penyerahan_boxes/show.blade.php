@extends('layouts.app')

@section('title', 'Detail Box Surat Penyerahan - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.surat-penyerahan-boxes.index') }}" class="text-decoration-none text-secondary">Box Surat Penyerahan</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">{{ $box->box_code }}</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0">Detail Box: {{ $box->box_code }}</h4>
        </div>
        <div>
            <a href="{{ route('elabel.surat-penyerahan-boxes.index') }}" class="btn btn-light border fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <!-- BOX INFO -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="text-secondary small fw-semibold text-uppercase mb-1">Kode Box Surat Penyerahan</div>
                <h3 class="fw-extrabold text-primary mb-1">{{ $box->box_code }}</h3>
                <div class="small text-secondary"><i class="bi bi-geo-alt me-1"></i> {{ $box->lokasi }}</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="text-secondary small fw-semibold text-uppercase mb-1">Kapasitas Isian</div>
                <h3 class="fw-extrabold text-navy mb-1">{{ $box->surats->count() }} / 40</h3>
                <div class="small text-secondary">Surat Terisi</div>
            </div>
        </div>
    </div>

    <!-- TABLE SURAT INSIDE BOX -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h6 class="fw-bold text-navy mb-0"><i class="bi bi-file-earmark-text me-2 text-primary"></i> Daftar Surat di Dalam Box Ini</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4 text-center" style="width: 50px;">No.</th>
                        <th class="py-3">No. Surat / NIBAR</th>
                        <th class="py-3">Jenis / Pemberi Hibah</th>
                        <th class="py-3">Dinas / Lokasi</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($box->surats as $item)
                        <tr>
                            <td class="px-4 text-center fw-medium text-secondary">{{ $loop->iteration }}</td>
                            <td>
                                <div class="fw-bold text-navy">{{ $item->no_surat }}</div>
                                <div class="small text-secondary">NIBAR: {{ $item->nibar ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="fw-medium text-dark">{{ $item->jenis_penyerahan ?: 'Hibah' }}</div>
                                <div class="small text-secondary">{{ $item->pemberi_hibah ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="fw-medium text-dark">{{ $item->dinas ?: '-' }}</div>
                                <div class="small text-secondary">{{ $item->lokasi ?: '-' }}</div>
                            </td>
                            <td class="px-4 text-center">
                                <a href="{{ route('elabel.surat-penyerahan.show', $item->id) }}" class="btn btn-sm btn-light border text-navy">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-secondary">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i> Belum ada data surat di dalam Box ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
