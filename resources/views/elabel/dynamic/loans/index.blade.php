@extends('layouts.app')

@section('title', 'Layanan Peminjaman & Scan Berkas Dinamis - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Breadcrumbs -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Layanan Peminjaman</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left-right text-info"></i> Layanan Peminjaman & Scan Berkas Dinamis
            </h4>
        </div>
        <div class="action-toolbar d-flex flex-wrap gap-2">
            <a href="{{ route('elabel.dynamic.items.index') }}" class="btn btn-outline-primary shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-folder2-open"></i> Buka Katalog Berkas
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filter & Search Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <form action="{{ route('elabel.dynamic.loans.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" name="q" value="{{ $searchQuery }}" class="form-control border-start-0 shadow-none" placeholder="Cari nama pemohon, instansi, nomor berkas, keperluan...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Status Persetujuan</option>
                        <option value="pending" {{ $selectedStatus === 'pending' ? 'selected' : '' }}>⏳ Menunggu Persetujuan</option>
                        <option value="approved" {{ $selectedStatus === 'approved' ? 'selected' : '' }}>✅ Disetujui / Sedang Dipinjam</option>
                        <option value="returned" {{ $selectedStatus === 'returned' ? 'selected' : '' }}>🔄 Sudah Dikembalikan</option>
                        <option value="rejected" {{ $selectedStatus === 'rejected' ? 'selected' : '' }}>❌ Ditolak</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-medium">Filter</button>
                    <a href="{{ route('elabel.dynamic.loans.index') }}" class="btn btn-light border bg-white"><i class="bi bi-arrow-clockwise"></i></a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4 text-center" style="width: 50px;">No.</th>
                        <th class="py-3">Dokumen Arsip</th>
                        <th class="py-3">Pemohon & Instansi</th>
                        <th class="py-3">Jenis Layanan</th>
                        <th class="py-3">Tgl Pinjam / Kembali</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3 px-4 text-center" style="width: 170px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loans as $loan)
                        <tr>
                            <td class="px-4 text-center fw-medium text-secondary">{{ $loop->iteration + ($loans->currentPage() - 1) * $loans->perPage() }}</td>
                            <td>
                                @if($loan->item)
                                    <a href="{{ route('elabel.dynamic.items.show', $loan->item->id) }}" class="fw-bold text-navy text-decoration-none font-monospace">
                                        {{ $loan->item->nomor_dokumen }}
                                    </a>
                                    <div class="text-xs text-secondary">{{ Str::limit($loan->item->nama_dokumen, 35) }}</div>
                                    <span class="badge bg-{{ $loan->item->archiveType->warna_badge ?? 'primary' }}-subtle text-{{ $loan->item->archiveType->warna_badge ?? 'primary' }} text-xs border">
                                        {{ $loan->item->archiveType->nama ?? '-' }}
                                    </span>
                                @else
                                    <span class="text-muted fst-italic">Berkas Dihapus</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $loan->requester_name }}</div>
                                <span class="text-xs text-muted d-block">{{ $loan->requester_org ?: ($loan->opd->nama ?? '-') }}</span>
                                @if($loan->requester_phone)
                                    <span class="text-xs text-secondary"><i class="bi bi-telephone me-1"></i>{{ $loan->requester_phone }}</span>
                                @endif
                            </td>
                            <td>
                                @if($loan->jenis_layanan === 'scan_digital')
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> Scan Digital
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                        <i class="bi bi-box-arrow-right me-1"></i> Pinjam Fisik
                                    </span>
                                @endif
                                <div class="text-xs text-muted mt-1" title="{{ $loan->keperluan }}">
                                    {{ Str::limit($loan->keperluan, 30) }}
                                </div>
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark">{{ $loan->tanggal_pinjam ? $loan->tanggal_pinjam->format('d/m/Y') : '-' }}</div>
                                <span class="text-xs text-muted">s/d {{ $loan->tanggal_kembali ? $loan->tanggal_kembali->format('d/m/Y') : '-' }}</span>
                            </td>
                            <td class="text-center">
                                @if($loan->status_persetujuan === 'approved')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Disetujui</span>
                                @elseif($loan->status_persetujuan === 'pending')
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Menunggu</span>
                                @elseif($loan->status_persetujuan === 'returned')
                                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">Dikembalikan</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Ditolak</span>
                                @endif
                            </td>
                            <td class="px-4 text-center">
                                <div class="btn-group btn-group-sm">
                                    @if($loan->status_persetujuan === 'pending')
                                        <button type="button" class="btn btn-outline-success" title="Setujui Permohonan" data-bs-toggle="modal" data-bs-target="#approveModal{{ $loan->id }}">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" title="Tolak Permohonan" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $loan->id }}">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    @elseif($loan->status_persetujuan === 'approved' && $loan->jenis_layanan === 'pinjam_fisik')
                                        <form action="{{ route('elabel.dynamic.loans.returned', $loan->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Tandai berkas ini sudah dikembalikan fisik ke rak arsip?');">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-info" title="Tandai Sudah Kembali">
                                                <i class="bi bi-arrow-return-left"></i> Kembali
                                            </button>
                                        </form>
                                    @endif

                                    @if($loan->item && $loan->item->file_scan_pdf)
                                        <a href="{{ route('elabel.dynamic.items.view-pdf', $loan->item->id) }}" target="_blank" class="btn btn-outline-danger" title="Buka PDF">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>
                                    @endif

                                    <form action="{{ route('elabel.dynamic.loans.destroy', $loan->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus riwayat peminjaman ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-secondary" title="Hapus Riwayat">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>

                                <!-- Modal Approve -->
                                <div class="modal fade text-start" id="approveModal{{ $loan->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow rounded-4">
                                            <form action="{{ route('elabel.dynamic.loans.approve', $loan->id) }}" method="POST">
                                                @csrf
                                                <div class="modal-header border-bottom py-3 px-4">
                                                    <h5 class="modal-title fw-bold text-navy"><i class="bi bi-check-circle text-success me-2"></i> Setujui Permohonan</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <p class="small text-secondary mb-3">
                                                        Apakah Anda yakin ingin menyetujui permohonan layanan arsip untuk pemohon <strong>{{ $loan->requester_name }}</strong>?
                                                    </p>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold text-dark">Catatan Petugas Admin (Opsional)</label>
                                                        <textarea name="catatan_admin" class="form-control" rows="2" placeholder="Catatan kondisi berkas saat diserahkan..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-top py-3 px-4">
                                                    <button type="button" class="btn btn-light border px-3" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-success fw-semibold px-4">Setujui Permohonan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Reject -->
                                <div class="modal fade text-start" id="rejectModal{{ $loan->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow rounded-4">
                                            <form action="{{ route('elabel.dynamic.loans.reject', $loan->id) }}" method="POST">
                                                @csrf
                                                <div class="modal-header border-bottom py-3 px-4">
                                                    <h5 class="modal-title fw-bold text-danger"><i class="bi bi-x-circle me-2"></i> Tolak Permohonan</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold text-dark">Alasan Penolakan <span class="text-danger">*</span></label>
                                                        <textarea name="catatan_admin" class="form-control" rows="3" placeholder="Tuliskan alasan penolakan permohonan peminjaman..." required></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-top py-3 px-4">
                                                    <button type="button" class="btn btn-light border px-3" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-danger fw-semibold px-4">Tolak Permohonan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-secondary">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Belum ada permohonan layanan peminjaman atau scan berkas dinamis.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($loans->hasPages())
            <div class="card-footer bg-white border-0 py-3 px-4">
                {{ $loans->links() }}
            </div>
        @endif
    </div>

</div>

<style>
.text-xs {
    font-size: 0.75rem;
}
</style>
@endsection
