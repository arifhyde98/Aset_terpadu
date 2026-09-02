@extends('layouts.app')

@section('title', 'Detail Arsip ' . $item->nomor_dokumen . ' - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Breadcrumbs -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dynamic.items.index') }}" class="text-decoration-none text-secondary">Katalog Arsip</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">{{ $item->nomor_dokumen }}</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 p-2 bg-{{ $item->archiveType->warna_badge ?? 'primary' }}-subtle text-{{ $item->archiveType->warna_badge ?? 'primary' }} fs-4 d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                    <i class="bi {{ $item->archiveType->icon ?? 'bi-folder2' }}"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-navy mb-0 font-monospace">{{ $item->nomor_dokumen }}</h4>
                    <span class="text-secondary small">{{ $item->nama_dokumen }}</span>
                </div>
            </div>
        </div>
        <div class="action-toolbar d-flex flex-wrap gap-2">
            @if(!empty($item->file_scan_pdf))
                <a href="{{ route('elabel.dynamic.items.view-pdf', $item->id) }}" target="_blank" class="btn btn-danger shadow-sm fw-medium d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-pdf-fill"></i> Buka Berkas Scan PDF
                </a>
            @endif
            <button type="button" class="btn btn-outline-primary shadow-sm fw-medium d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#loanModal">
                <i class="bi bi-clock-history"></i> Ajukan Pinjam / Scan
            </button>
            <a href="{{ route('elabel.dynamic.items.edit', $item->id) }}" class="btn btn-warning text-dark shadow-sm fw-semibold d-flex align-items-center gap-2">
                <i class="bi bi-pencil-square"></i> Edit
            </a>
            <a href="{{ route('elabel.dynamic.items.index') }}" class="btn btn-light border shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Kolom Kiri: Metadata & Detail Dokumen -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-info-circle text-primary me-2"></i> Spesifikasi & Metadata Arsip</h6>
                    @if($item->status === 'Tersedia')
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6">Tersedia</span>
                    @elseif($item->status === 'Dipinjam')
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 fs-6">Dipinjam</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border px-3 py-2 fs-6">{{ $item->status }}</span>
                    @endif
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <span class="text-xs text-uppercase fw-bold text-muted d-block">Jenis / Kategori Arsip</span>
                            <span class="badge bg-{{ $item->archiveType->warna_badge ?? 'primary' }}-subtle text-{{ $item->archiveType->warna_badge ?? 'primary' }} border mt-1 fs-6">
                                <i class="bi {{ $item->archiveType->icon ?? 'bi-folder' }} me-1"></i> {{ $item->archiveType->nama ?? '-' }}
                            </span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-xs text-uppercase fw-bold text-muted d-block">Tahun Berkas</span>
                            <span class="fw-bold text-dark fs-6 mt-1 d-block">{{ $item->tahun_dokumen ?: '-' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-xs text-uppercase fw-bold text-muted d-block">OPD Pengolah</span>
                            <span class="fw-semibold text-dark mt-1 d-block"><i class="bi bi-building me-1 text-secondary"></i> {{ $item->opd->nama ?? 'Belum ditentukan' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-xs text-uppercase fw-bold text-muted d-block">Penyimpanan Box Fisik</span>
                            @if($item->box)
                                <a href="{{ route('elabel.dynamic.boxes.show', $item->box->id) }}" class="badge bg-warning-subtle text-dark border fs-6 mt-1 text-decoration-none d-inline-block">
                                    <i class="bi bi-box me-1"></i> {{ $item->box->nomor_box }} ({{ $item->box->lokasi_rak ?: 'Rak' }})
                                </a>
                            @else
                                <span class="badge bg-light text-muted border mt-1 fs-6">Belum Di-box</span>
                            @endif
                        </div>
                        <div class="col-12 border-top pt-3">
                            <span class="text-xs text-uppercase fw-bold text-muted d-block">Nama / Uraian Dokumen</span>
                            <div class="fw-bold text-navy fs-6 mt-1">{{ $item->nama_dokumen }}</div>
                        </div>
                        <div class="col-12">
                            <span class="text-xs text-uppercase fw-bold text-muted d-block">Catatan / Keterangan</span>
                            <p class="text-secondary small mb-0 mt-1">{{ $item->keterangan ?: 'Tidak ada catatan khusus.' }}</p>
                        </div>
                    </div>

                    <!-- Custom Metadata Key-Values -->
                    @if(!empty($item->metadata) && count($item->metadata) > 0)
                        <h6 class="fw-bold text-navy mt-4 mb-3 pt-3 border-top d-flex align-items-center gap-2">
                            <i class="bi bi-ui-checks text-success"></i> Kolom Atribut Kustom
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 35%;">Atribut</th>
                                        <th>Nilai / Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $schemaFields = collect($item->archiveType->schema_fields ?? []);
                                    @endphp
                                    @foreach($item->metadata as $key => $val)
                                        @php
                                            $schemaObj = $schemaFields->firstWhere('name', $key);
                                            $label = $schemaObj['label'] ?? ucfirst(str_replace('_', ' ', $key));
                                        @endphp
                                        <tr>
                                            <td class="fw-semibold text-secondary bg-light-subtle">{{ $label }}</td>
                                            <td class="fw-medium text-dark font-monospace">
                                                @if(is_array($val))
                                                    {{ json_encode($val) }}
                                                @else
                                                    {{ $val !== '' && $val !== null ? $val : '-' }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <!-- Lampiran Pendukung -->
                    <h6 class="fw-bold text-navy mt-4 mb-3 pt-3 border-top d-flex align-items-center gap-2">
                        <i class="bi bi-paperclip text-info"></i> Berkas Lampiran Pendukung ({{ $item->attachments->count() }})
                    </h6>
                    @if($item->attachments->isNotEmpty())
                        <div class="list-group list-group-flush rounded-3 border">
                            @foreach($item->attachments as $att)
                                <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-file-earmark fs-5 text-secondary"></i>
                                        <div>
                                            <span class="fw-semibold text-dark small d-block">{{ $att->file_title }}</span>
                                            <span class="text-xs text-muted">{{ $att->formatted_size }} &bull; {{ strtoupper($att->file_type) }}</span>
                                        </div>
                                    </div>
                                    <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary" download>
                                        <i class="bi bi-download"></i> Unduh
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <span class="text-muted small fst-italic">Tidak ada berkas lampiran pendukung tambahan.</span>
                    @endif
                </div>
            </div>

            <!-- Riwayat Peminjaman Dokumen Ini -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-clock-history text-warning me-2"></i> Riwayat Layanan Peminjaman Dokumen</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-2 px-3 text-xs text-uppercase">Pemohon</th>
                                    <th class="py-2 text-xs text-uppercase">Layanan</th>
                                    <th class="py-2 text-xs text-uppercase">Tanggal</th>
                                    <th class="py-2 text-xs text-uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($item->loans as $loan)
                                    <tr>
                                        <td class="px-3">
                                            <div class="fw-bold text-dark small">{{ $loan->requester_name }}</div>
                                            <span class="text-xs text-muted">{{ $loan->requester_org ?: ($loan->opd->nama ?? '-') }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border small">
                                                {{ $loan->jenis_layanan === 'scan_digital' ? 'Scan Digital' : 'Pinjam Fisik' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-xs text-secondary">{{ $loan->tanggal_pinjam ? $loan->tanggal_pinjam->format('d/m/Y') : '-' }}</span>
                                        </td>
                                        <td>
                                            @if($loan->status_persetujuan === 'approved')
                                                <span class="badge bg-success-subtle text-success border">Disetujui</span>
                                            @elseif($loan->status_persetujuan === 'pending')
                                                <span class="badge bg-warning-subtle text-warning border">Menunggu</span>
                                            @elseif($loan->status_persetujuan === 'returned')
                                                <span class="badge bg-info-subtle text-info border">Kembali</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger border">Ditolak</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted small">
                                            Belum ada riwayat permohonan peminjaman untuk berkas ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Pratinjau Dokumen PDF -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-file-earmark-pdf text-danger me-2"></i> Pratinjau Dokumen PDF</h6>
                    @if(!empty($item->file_scan_pdf))
                        <a href="{{ route('elabel.dynamic.items.view-pdf', $item->id) }}" target="_blank" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-arrows-fullscreen me-1"></i> Layar Penuh
                        </a>
                    @endif
                </div>
                <div class="card-body p-3 d-flex flex-column">
                    @if(!empty($item->file_scan_pdf))
                        <div class="ratio ratio-1x1 border rounded-3 overflow-hidden bg-light flex-grow-1" style="min-height: 500px;">
                            <iframe src="{{ route('elabel.dynamic.items.view-pdf', $item->id) }}" style="width: 100%; height: 100%; border: none;"></iframe>
                        </div>
                    @else
                        <div class="text-center py-5 my-auto text-secondary">
                            <i class="bi bi-file-earmark-x fs-1 d-block mb-3 text-muted"></i>
                            <h6 class="fw-bold text-navy">Belum Ada File Scan PDF</h6>
                            <p class="text-muted small mb-3">Anda dapat mengunggah berkas scan PDF dengan mengklik tombol edit di atas.</p>
                            <a href="{{ route('elabel.dynamic.items.edit', $item->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-upload me-1"></i> Unggah Scan PDF
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal Ajukan Peminjaman / Scan -->
<div class="modal fade" id="loanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="{{ route('elabel.dynamic.loans.store') }}" method="POST">
                @csrf
                <input type="hidden" name="archive_item_id" value="{{ $item->id }}">

                <div class="modal-header border-bottom py-3 px-4">
                    <h5 class="modal-title fw-bold text-navy"><i class="bi bi-clock-history text-primary me-2"></i> Permohonan Layanan Arsip</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border rounded-3 small mb-3">
                        Dokumen: <strong>{{ $item->nomor_dokumen }}</strong><br>
                        Uraian: {{ $item->nama_dokumen }}
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Jenis Layanan <span class="text-danger">*</span></label>
                        <select name="jenis_layanan" class="form-select" required>
                            <option value="scan_digital">Scan Digital / Softcopy</option>
                            <option value="pinjam_fisik">Peminjaman Berkas Fisik</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Nama Pemohon / Penanggung Jawab <span class="text-danger">*</span></label>
                        <input type="text" name="requester_name" value="{{ Auth::user()->name ?? '' }}" class="form-control" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold text-dark">No. Telepon / WhatsApp</label>
                            <input type="text" name="requester_phone" class="form-control" placeholder="08xxxxxxxx">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold text-dark">Email</label>
                            <input type="email" name="requester_email" value="{{ Auth::user()->email ?? '' }}" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Instansi / Unit OPD Pemohon</label>
                        <select name="opd_id" class="form-select">
                            <option value="">-- Pilih OPD Pemohon --</option>
                            @foreach(\App\Models\Opd::orderBy('nama')->get() as $o)
                                <option value="{{ $o->id }}" {{ (Auth::user()->opd_id ?? null) == $o->id ? 'selected' : '' }}>{{ $o->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold text-dark">Tanggal Pinjam</label>
                            <input type="date" name="tanggal_pinjam" value="{{ date('Y-m-d') }}" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold text-dark">Tanggal Kembali</label>
                            <input type="date" name="tanggal_kembali" value="{{ date('Y-m-d', strtotime('+7 days')) }}" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Keperluan / Alasan Permohonan <span class="text-danger">*</span></label>
                        <textarea name="keperluan" class="form-control" rows="3" placeholder="Jelaskan keperluan peminjaman atau permohonan scan..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top py-3 px-4">
                    <button type="button" class="btn btn-light border px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-semibold px-4">Kirim Permohonan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.text-xs {
    font-size: 0.75rem;
}
</style>
@endsection
