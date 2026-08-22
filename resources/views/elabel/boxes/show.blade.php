@extends('layouts.app')

@section('title', 'Detail Box BPKB - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.boxes.index') }}" class="text-decoration-none text-secondary">Box BPKB</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">{{ $box->box_code }}</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0">Detail Box: {{ $box->box_code }}</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('elabel.boxes.label', $box->id) }}" target="_blank" class="btn btn-outline-navy fw-medium">
                <i class="bi bi-printer me-1"></i> Cetak Label Box
            </a>
            <a href="{{ route('elabel.bpkb.create') }}" class="btn btn-primary fw-medium">
                <i class="bi bi-plus-lg me-1"></i> Tambah BPKB
            </a>
            <a href="{{ route('elabel.boxes.index') }}" class="btn btn-light border fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>
    <!-- BOX INFO CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="text-secondary small fw-semibold text-uppercase mb-1">Kode Box & Jenis</div>
                <h3 class="fw-extrabold text-navy mb-1">{{ $box->box_code }}</h3>
                <div class="small">
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 rounded-2">
                        {{ $box->vehicle_type === 'R2' ? 'R2 (Motor)' : 'R4 (Mobil)' }}
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="text-secondary small fw-semibold text-uppercase mb-1">Lokasi Ruang / Rak</div>
                <h4 class="fw-bold text-dark mb-1"><i class="bi bi-geo-alt text-danger me-1"></i> {{ $box->location ?: '-' }}</h4>
                <div class="small text-secondary">Posisi penyimpanan fisik</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="text-secondary small fw-semibold text-uppercase mb-1">Kapasitas BPKB & Tahun</div>
                <h4 class="fw-bold text-primary mb-1">{{ $mergeCandidateCount }} / 55 BPKB</h4>
                <div class="small text-secondary">
                    Tahun: {{ implode(', ', $years->pluck('year')->toArray()) ?: '-' }}
                </div>
            </div>
        </div>
    </div>

    <!-- MERGE BOX SECTION -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h6 class="fw-bold text-navy mb-0"><i class="bi bi-object-group text-warning me-2"></i> Gabungkan Box BPKB</h6>
        </div>
        <div class="card-body p-4 pt-0">
            @if($mergeCandidateCount === 0)
                <div class="alert alert-secondary mb-0 border-0">Box ini belum memiliki data BPKB untuk digabung.</div>
            @elseif($mergeCandidateCount > $maxMergeSource)
                <div class="alert alert-warning mb-0 border-0">
                    <i class="bi bi-exclamation-triangle me-1"></i> Box hanya bisa digabung ke box lain jika berisi maksimal {{ $maxMergeSource }} BPKB. Saat ini box berisi {{ $mergeCandidateCount }} BPKB.
                </div>
            @elseif(empty($mergeTargets))
                <div class="alert alert-warning mb-0 border-0">
                    <i class="bi bi-info-circle me-1"></i> Belum ada box tujuan yang kompatibel untuk menerima penggabungan. (Tahun box sumber harus sudah tercakup di box tujuan).
                </div>
            @else
                <div class="alert alert-info border-0 mb-3">
                    <i class="bi bi-info-circle me-1"></i> Gabungkan box ini ke box lain dengan jenis kendaraan yang sama. Tahun box sumber harus sudah tercakup di box tujuan. Khusus hasil penggabungan, kapasitas box tujuan boleh melebih 55 BPKB. Box sumber akan dihapus otomatis.
                </div>
                <form action="{{ route('elabel.boxes.merge', $box->id) }}" method="POST" onsubmit="return confirm('Gabungkan seluruh data box ini ke box tujuan? Box sumber {{ $box->box_code }} akan dihapus setelah berhasil.');">
                    @csrf
                    <div class="row g-2 align-items-center">
                        <div class="col-md-9">
                            <select name="target_box_id" class="form-select" required>
                                <option value="">-- Pilih Box Tujuan --</option>
                                @foreach($mergeTargets as $target)
                                    <option value="{{ $target->id }}">
                                        {{ $target->box_code }} ({{ $target->location ?: '-' }}) - Terisi {{ $target->bpkbs_count }} BPKB
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-warning w-100 fw-bold">
                                <i class="bi bi-object-group me-1"></i> Gabungkan Box
                            </button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <!-- TABLE BPKB INSIDE BOX -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h6 class="fw-bold text-navy mb-0"><i class="bi bi-card-heading me-2 text-primary"></i> Daftar BPKB di Dalam Box Ini</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4 text-center" style="width: 50px;">No.</th>
                        <th class="py-3">Tahun</th>
                        <th class="py-3">No. Polisi / Plat</th>
                        <th class="py-3">Merk / Tipe</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3 text-center">Scan PDF</th>
                        <th class="py-3 px-4 text-center" style="width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td class="px-4 text-center fw-medium text-secondary">{{ $loop->iteration }}</td>
                            <td><span class="badge bg-light text-dark border fw-bold">{{ $item->year }}</span></td>
                            <td>
                                <div class="fw-bold text-navy">{{ $item->plate_number }}</div>
                                <div class="small text-secondary">NIBAR: {{ $item->nibar ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="fw-medium text-dark">{{ $item->brand ?: '-' }} {{ $item->type_model ?: '' }}</div>
                                <div class="small text-secondary">OPD: {{ $item->opd_name ?: '-' }}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-1 rounded-pill fw-medium">
                                    {{ $item->status }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($item->pdf_path)
                                    <span class="badge bg-success"><i class="bi bi-file-earmark-check me-1"></i> Ada</span>
                                @else
                                    <span class="badge bg-secondary">Tidak ada</span>
                                @endif
                            </td>
                            <td class="px-4 text-center">
                                <a href="{{ route('elabel.bpkb.show', $item->id) }}" class="btn btn-sm btn-light border text-navy">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-secondary">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i> Belum ada data BPKB di dalam Box ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
