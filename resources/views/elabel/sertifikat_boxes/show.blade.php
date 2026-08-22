@extends('layouts.app')

@section('title', 'Detail Box Sertifikat - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.sertifikat-boxes.index') }}" class="text-decoration-none text-secondary">Box Sertifikat</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">{{ $box->box_code }}</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0">Detail Box: {{ $box->box_code }}</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('elabel.sertifikat.create') }}" class="btn btn-success fw-medium">
                <i class="bi bi-plus-lg me-1"></i> Tambah Sertifikat
            </a>
            <a href="{{ route('elabel.sertifikat-boxes.index') }}" class="btn btn-light border fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>
    <!-- BOX INFO CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="text-secondary small fw-semibold text-uppercase mb-1">Kode Box & Lokasi</div>
                <h3 class="fw-extrabold text-success mb-1">{{ $box->box_code }}</h3>
                <div class="small text-dark fw-medium"><i class="bi bi-geo-alt text-danger me-1"></i> {{ $box->lokasi }}</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="text-secondary small fw-semibold text-uppercase mb-1">Kapasitas Isian</div>
                <h3 class="fw-extrabold text-navy mb-1">{{ $mergeCandidateCount }} / {{ $maxPerBox }}</h3>
                <div class="small text-secondary">Sertifikat Terisi</div>
            </div>
        </div>
    </div>

    <!-- MERGE & SPLIT SECTIONS -->
    <div class="row g-3 mb-4">
        <!-- MERGE BOX -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 py-3 px-4">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-object-group text-warning me-2"></i> Gabungkan Box Sertifikat</h6>
                </div>
                <div class="card-body p-4 pt-0">
                    @if($mergeCandidateCount === 0)
                        <div class="alert alert-secondary mb-0 border-0">Box ini belum memiliki data sertifikat untuk digabung.</div>
                    @elseif(empty($mergeTargets))
                        <div class="alert alert-warning mb-0 border-0">
                            Belum ada box tujuan yang bisa digabung. Total isi box sumber dan tujuan harus maksimal {{ $maxPerBox }} sertifikat.
                        </div>
                    @else
                        <div class="alert alert-info border-0 mb-3 small">
                            Penggabungan bisa dilakukan antar lokasi jika total isi box sumber dan tujuan maksimal {{ $maxPerBox }} sertifikat. Lokasi box tujuan akan digabung (contoh: <strong>Banawa, Sojol</strong>). Box sumber akan dihapus.
                        </div>
                        <form action="{{ route('elabel.sertifikat-boxes.merge', $box->id) }}" method="POST" onsubmit="return confirm('Gabungkan seluruh isi box ini ke box tujuan? Box sumber {{ $box->box_code }} akan dihapus.');">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Pilih Box Tujuan</label>
                                <select name="target_box_id" class="form-select" required>
                                    <option value="">-- Pilih Box Tujuan --</option>
                                    @foreach($mergeTargets as $target)
                                        <option value="{{ $target->id }}">
                                            {{ $target->box_code }} - {{ $target->lokasi }} (Isi: {{ $target->sertifikats_count }} sertifikat, Total: {{ $target->combined_count }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-warning w-100 fw-bold">
                                <i class="bi bi-object-group me-1"></i> Gabungkan Box
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- SPLIT BOX -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 py-3 px-4">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-diagram-3 text-primary me-2"></i> Pisahkan Kembali Box</h6>
                </div>
                <div class="card-body p-4 pt-0">
                    @if(empty($splitOptions))
                        <div class="alert alert-secondary mb-0 border-0">Box ini belum memiliki gabungan lokasi yang bisa dipisahkan kembali.</div>
                    @else
                        <div class="alert alert-info border-0 mb-3 small">
                            Pilih satu lokasi dari box gabungan untuk dipisahkan ke box baru. Box baru akan memakai kode turunan seperti <strong>(2)</strong>, <strong>(3)</strong>.
                        </div>
                        <form action="{{ route('elabel.sertifikat-boxes.split', $box->id) }}" method="POST" onsubmit="return confirm('Pisahkan lokasi terpilih ke box baru?');">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Pilih Lokasi yang Dipisahkan</label>
                                <select name="split_location" class="form-select" required>
                                    <option value="">-- Pilih Lokasi --</option>
                                    @foreach($splitOptions as $option)
                                        <option value="{{ $option['label'] }}">
                                            {{ $option['label'] }} ({{ $option['count'] }} sertifikat)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-outline-primary w-100 fw-bold">
                                <i class="bi bi-diagram-3 me-1"></i> Pisahkan Lokasi ke Box Baru
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- TABLE SERTIFIKAT INSIDE BOX -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h6 class="fw-bold text-navy mb-0"><i class="bi bi-patch-check me-2 text-success"></i> Daftar Sertifikat di Dalam Box Ini</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4 text-center" style="width: 50px;">No.</th>
                        <th class="py-3">No. Sertipikat / NIBAR</th>
                        <th class="py-3">Pemilik / Dinas</th>
                        <th class="py-3">Status / Spesifikasi</th>
                        <th class="py-3 text-end">Luas (m²)</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td class="px-4 text-center fw-medium text-secondary">{{ $loop->iteration }}</td>
                            <td>
                                <div class="fw-bold text-navy">{{ $item->no_sertipikat }}</div>
                                <div class="small text-secondary">NIBAR: {{ $item->nibar ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="fw-medium text-dark">{{ $item->nama_pemilik ?: '-' }}</div>
                                <div class="small text-secondary">{{ $item->dinas ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="fw-medium text-dark">{{ $item->spesifikasi ?: '-' }}</div>
                                <div class="small text-secondary">{{ $item->status_penggunaan ?: '-' }}</div>
                            </td>
                            <td class="text-end fw-bold text-dark">
                                {{ $item->luas ? number_format($item->luas, 2) : '-' }}
                            </td>
                            <td class="px-4 text-center">
                                <a href="{{ route('elabel.sertifikat.show', $item->id) }}" class="btn btn-sm btn-light border text-navy">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-secondary">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i> Belum ada data sertifikat di dalam Box ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
