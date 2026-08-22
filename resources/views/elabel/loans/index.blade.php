@extends('layouts.app')

@section('title', 'Peminjaman & Request Scan - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Peminjaman Scan</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0">Permintaan Scan & Peminjaman Arsip</h4>
        </div>
        <div>
            <button type="button" class="btn btn-primary shadow-sm fw-medium d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#manualRequestModal">
                <i class="bi bi-plus-lg"></i> Permintaan Scan Manual
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h6 class="fw-bold text-navy mb-0"><i class="bi bi-clock-history text-primary me-2"></i> Riwayat Permintaan Scan</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4 text-center" style="width: 50px;">No.</th>
                        <th class="py-3">Pemohon</th>
                        <th class="py-3">Dokumen Dipesan</th>
                        <th class="py-3">Waktu Pengajuan</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3 px-4 text-center" style="width: 160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $loan)
                        <tr>
                            <td class="px-4 text-center fw-medium text-secondary">{{ $loop->iteration }}</td>
                            <td>
                                <div class="fw-bold text-navy">{{ $loan->requester_name ?: ($loan->requester->name ?? 'Pemohon External') }}</div>
                                <div class="small text-secondary">{{ $loan->opdSipat ? $loan->opdSipat->nama : ($loan->requester_org ?: '-') }} · {{ $loan->requester_phone ?: '-' }}</div>
                            </td>
                            <td>
                                @if($loan->bpkb)
                                    <div class="fw-semibold text-dark"><i class="bi bi-card-heading text-primary me-1"></i> BPKB {{ $loan->bpkb->plate_number }}</div>
                                    <div class="small text-secondary">Tahun {{ $loan->bpkb->year }} · Box {{ $loan->bpkb->box->box_code ?? '-' }}</div>
                                @else
                                    <div class="text-secondary">-</div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-medium text-dark">{{ $loan->requested_at ? $loan->requested_at->format('d M Y H:i') : '-' }}</div>
                            </td>
                            <td class="text-center">
                                @if($loan->status === 'Disetujui')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-1 rounded-pill fw-medium">Disetujui</span>
                                @elseif($loan->status === 'Ditolak')
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-1 rounded-pill fw-medium">Ditolak</span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning text-dark border border-warning border-opacity-25 px-3 py-1 rounded-pill fw-medium">Menunggu</span>
                                @endif
                            </td>
                            <td class="px-4 text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    @if($loan->status === 'Menunggu')
                                        <form action="{{ route('elabel.peminjaman.approve', $loan->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success fw-medium px-2" title="Setujui">
                                                <i class="bi bi-check-lg"></i> Setujui
                                            </button>
                                        </form>

                                        <button type="button" class="btn btn-sm btn-outline-danger fw-medium px-2" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $loan->id }}" title="Tolak">
                                            <i class="bi bi-x-lg"></i> Tolak
                                        </button>
                                    @endif

                                    @if($loan->status === 'Disetujui')
                                        <a href="{{ route('elabel.peminjaman.download', $loan->id) }}" class="btn btn-sm btn-primary fw-medium px-2" title="Download Scan Watermark">
                                            <i class="bi bi-download"></i> PDF
                                        </a>
                                    @endif

                                    <form action="{{ route('elabel.peminjaman.destroy', $loan->id) }}" method="POST" class="d-inline" onclick="return confirm('Hapus riwayat permohonan scan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border text-danger" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>

                                <!-- REJECT MODAL -->
                                @if($loan->status === 'Menunggu')
                                    <div class="modal fade" id="rejectModal{{ $loan->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <form action="{{ route('elabel.peminjaman.reject', $loan->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header border-bottom px-4 py-3">
                                                        <h5 class="modal-title fw-bold text-navy"><i class="bi bi-x-circle text-danger me-2"></i> Tolak Permintaan Scan</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4 text-start">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold small">Alasan Penolakan</label>
                                                            <textarea name="note" class="form-control" rows="3" placeholder="Masukkan alasan penolakan..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4">
                                                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-danger fw-semibold">Tolak Permintaan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-secondary">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i> Belum ada riwayat permohonan scan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MANUAL REQUEST MODAL -->
<div class="modal fade" id="manualRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('elabel.peminjaman.store-manual') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-navy"><i class="bi bi-plus-lg text-primary me-2"></i> Permintaan Scan Manual (Admin)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Pilih BPKB <span class="text-danger">*</span></label>
                        <select name="bpkb_id" class="form-select" required>
                            <option value="">-- Pilih BPKB Tersedia --</option>
                            @foreach($availableBpkb as $b)
                                <option value="{{ $b->id }}">{{ $b->plate_number }} (Tahun {{ $b->year }} - Box {{ $b->box->box_code ?? '-' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nama Pemohon <span class="text-danger">*</span></label>
                        <input type="text" name="requester_name" class="form-control" placeholder="Nama Pemohon" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Dinas / OPD (Internal SIPAT)</label>
                        <select name="sipat_opd_id" class="form-select">
                            <option value="">-- Pilih Dinas / OPD (Jika Internal) --</option>
                            @foreach($opds as $opd)
                                <option value="{{ $opd->id }}">{{ $opd->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Instansi / Organisasi (Eksternal / Lainnya)</label>
                        <input type="text" name="requester_org" class="form-control" placeholder="Inspektorat / BPK / Kejaksaan (diisi jika bukan OPD internal)">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">No. HP Pemohon</label>
                        <input type="text" name="requester_phone" class="form-control" placeholder="08XXXXXXXXXX">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Catatan / Peruntukan</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="Keperluan pemeriksaan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Simpan & Setujui</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
