@extends('layouts.app')

@section('content')
<style>
    .target-card {
        border: 1px solid var(--border-color, rgba(0, 0, 0, 0.08));
        border-radius: 1.25rem;
        background: var(--bs-card-bg, #ffffff);
        box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }
    .table-container-sipat {
        border-radius: 1rem;
        border: 1px solid var(--border-color, rgba(0,0,0,0.08));
    }
    .aset-table thead th {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 0.75rem 0.85rem;
        border-bottom: 2px solid var(--border-color, rgba(0,0,0,0.08));
        white-space: nowrap;
    }
    .aset-table tbody td {
        padding: 0.65rem 0.85rem;
        vertical-align: middle;
        font-size: 0.82rem;
    }
    .target-card-header {
        background: var(--bs-tertiary-bg, rgba(59, 130, 246, 0.04));
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, 0.08));
    }
    .metric-box {
        padding: 1.25rem;
        border-radius: 1rem;
        border: 1px solid var(--border-color, rgba(0, 0, 0, 0.08));
        background: var(--bs-tertiary-bg, #f8fafc);
        position: relative;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .metric-box:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    }
    .info-banner {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.08) 0%, rgba(59, 130, 246, 0.08) 100%);
        border: 1px solid rgba(245, 158, 11, 0.2);
        border-radius: 1rem;
    }
    .badge-status {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 50rem;
        font-size: 0.72rem;
        font-weight: 600;
        white-space: nowrap;
    }
</style>

<div class="container-fluid px-0">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-warning-subtle text-warning-emphasis fw-semibold px-2.5 py-1 rounded-pill" style="font-size: 0.75rem;">
                    <i class="bi bi-geo-alt-fill me-1"></i> MODUL SIPAT
                </span>
                <span class="text-secondary small">&bull;</span>
                <span class="text-secondary small">Inventarisasi Aset Baru</span>
            </div>
            <h2 class="fw-bold mb-1">Tanah Belum / Tak Tercatat (KIB A)</h2>
            <p class="text-secondary mb-0 small">Pengelolaan dan pendaftaran bidang tanah daerah yang belum terdaftar di data induk atau belum memiliki NIBAR resmi BPKAD</p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button" class="btn btn-primary rounded-pill px-3.5 shadow-sm btn-premium-glow" data-bs-toggle="modal" data-bs-target="#modalCreateTanah">
                <i class="bi bi-plus-lg me-1.5"></i> Input Tanah Belum Tercatat Baru
            </button>
            <a href="{{ route('sipat.aset.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Master Aset
            </a>
        </div>
    </div>

    <!-- Info Banner Callout -->
    <div class="info-banner p-3.5 mb-4 d-flex align-items-start gap-3">
        <div class="p-2.5 bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
            <i class="bi bi-lightbulb-fill fs-5"></i>
        </div>
        <div>
            <h6 class="fw-bold text-dark mb-1">Manajemen Aset Tanah Usulan & Belum Memiliki NIBAR Resmi</h6>
            <p class="text-secondary small mb-0">
                Seluruh detail aset (seperti status proses pengurusan BPN, pengamanan fisik, dan dokumen lampiran) **tetap tersimpan utuh**. Halaman ini terintegrasi penuh dengan Master Aset Tanah untuk mempermudah pendaftaran NIBAR resmi BPKAD.
            </p>
        </div>
    </div>

    <!-- Filter Header -->
    <div class="card target-card mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('sipat.tanah-tak-tercatat.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4 col-sm-6">
                    <label class="form-label small fw-bold text-secondary mb-1"><i class="bi bi-building me-1"></i> OPD Pengelola</label>
                    @if(in_array(auth()->user()?->role, [\App\Enums\UserRole::SUPERADMIN, \App\Enums\UserRole::ADMIN]))
                    <select name="opd_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Semua OPD Pengelola --</option>
                        @foreach($opdList as $opd)
                            <option value="{{ $opd->id }}" {{ (string)$opdId === (string)$opd->id ? 'selected' : '' }}>{{ $opd->nama }}</option>
                        @endforeach
                    </select>
                    @else
                    <input type="text" class="form-control form-control-sm bg-light fw-medium" value="{{ auth()->user()->opd?->nama ?? 'OPD Anda' }}" readonly disabled>
                    <input type="hidden" name="opd_id" value="{{ auth()->user()->opd_id }}">
                    @endif
                </div>

                <div class="col-md-5 col-sm-6">
                    <label class="form-label small fw-bold text-secondary mb-1"><i class="bi bi-search me-1"></i> Kata Kunci Pencarian</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="NIBAR Draft / Nama Aset / Peruntukan / Lokasi..." value="{{ $search }}">
                </div>

                <div class="col-md-3 col-sm-12 align-self-end d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3 flex-grow-1">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    @if($opdId || $search)
                        <a href="{{ route('sipat.tanah-tak-tercatat.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5" data-bs-toggle="tooltip" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-lg-4 col-sm-6">
            <div class="metric-box" style="border-left: 4px solid #f59e0b;">
                <div class="text-warning-emphasis small fw-bold text-uppercase">Total Tanah Belum Tercatat</div>
                <div class="fs-2 fw-extrabold text-dark font-monospace mt-1">{{ number_format($tanahItems->total()) }}</div>
                <div class="text-secondary small">Menunggu NIBAR Resmi BPKAD</div>
            </div>
        </div>

        <div class="col-lg-4 col-sm-6">
            <div class="metric-box" style="border-left: 4px solid #3b82f6;">
                <div class="text-primary small fw-bold text-uppercase">NIBAR Sementara (Draft)</div>
                <div class="fs-2 fw-extrabold text-primary font-monospace mt-1">{{ number_format($totalDraftNibar) }}</div>
                <div class="text-secondary small">Kode Otomatis DRAFT-YYYYMMDD-XXXX</div>
            </div>
        </div>

        <div class="col-lg-4 col-sm-6">
            <div class="metric-box" style="border-left: 4px solid #10b981;">
                <div class="text-success small fw-bold text-uppercase">OPD Pengelola Terdaftar</div>
                <div class="fs-2 fw-extrabold text-success font-monospace mt-1">{{ number_format($totalOpdCount) }}</div>
                <div class="text-secondary small">Instansi Pemegang Hak Pakai</div>
            </div>
        </div>
    </div>

    <!-- Tabel Main Data (Struktur Identik dengan Master Aset Tanah) -->
    <div class="card target-card">
        <div class="target-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold mb-0 text-body">
                <i class="bi bi-card-checklist text-primary me-2"></i>Daftar Bidang Tanah Belum Tercatat NIBAR
            </h5>
            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1.5 fw-semibold">
                {{ $tanahItems->total() }} Bidang Ditampilkan
            </span>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 aset-table">
                    <thead class="bg-body text-secondary">
                        <tr>
                            <th class="ps-4 py-3" style="width: 50px;">NO</th>
                            <th class="py-3" style="min-width: 260px;">PERUNTUKAN / NAMA ASET</th>
                            <th class="py-3">LUAS (M²)</th>
                            <th class="py-3">OPD PENGELOLA</th>
                            <th class="py-3" style="min-width: 220px;">ALAMAT / LOKASI</th>
                            <th class="py-3">STATUS PROSES BPN</th>
                            <th class="text-center py-3 pe-4" style="width: 220px;">AKSI INTEGRASI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tanahItems as $index => $item)
                            <tr>
                                <td class="ps-4 fw-semibold text-secondary" style="font-size: 0.82rem;">
                                    {{ $tanahItems->firstItem() + $index }}
                                </td>
                                <td>
                                    <!-- 1. Identitas Aset Utama (Nama & Peruntukan) -->
                                    <div class="fw-bold text-body" style="font-size: 0.88rem; line-height: 1.35;">
                                        {{ $item->peruntukan ?? $item->nama_aset }}
                                    </div>
                                    @if($item->peruntukan && $item->nama_aset && trim(strtolower($item->peruntukan)) !== trim(strtolower($item->nama_aset)))
                                        <small class="text-secondary d-block mt-0.5" style="font-size: 0.76rem; line-height: 1.3;">{{ $item->nama_aset }}</small>
                                    @endif

                                    <!-- 2. NIBAR (Sub-teks Halus di Bawah) -->
                                    <div class="mt-1 d-flex align-items-center gap-2 flex-wrap" style="font-size: 0.73rem;">
                                        @if(str_starts_with($item->kode_aset ?? '', 'DRAFT-'))
                                            <span class="text-warning-emphasis font-monospace d-inline-flex align-items-center" title="NIBAR Sementara / Draft">
                                                <i class="bi bi-tag text-warning me-1"></i>{{ $item->kode_aset }}
                                            </span>
                                        @elseif($item->kode_aset === '-')
                                            <span class="text-danger font-monospace">
                                                <i class="bi bi-dash-circle me-1"></i>Tanpa NIBAR (-)
                                            </span>
                                        @elseif(!empty($item->kode_aset))
                                            <span class="text-secondary font-monospace d-inline-flex align-items-center gap-1" title="NIBAR (Nomor Induk Barang)">
                                                <i class="bi bi-upc text-secondary opacity-75"></i>
                                                <span>NIBAR: <span class="user-select-all text-body-secondary">{{ $item->kode_aset }}</span></span>
                                                <button type="button" class="btn btn-link p-0 text-secondary border-0 ms-1 btn-copy-nibar opacity-75" data-nibar="{{ $item->kode_aset }}" title="Salin NIBAR" style="font-size: 0.7rem; line-height: 1;">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </span>
                                        @else
                                            <span class="text-muted fst-italic">
                                                <i class="bi bi-dash-circle text-secondary opacity-50 me-1"></i>Kosong
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold text-body" style="font-size: 0.85rem;">{{ number_format($item->luas ?? 0, 0, ',', '.') }}</span> <small class="text-secondary">m²</small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-body-secondary fw-normal px-2.5 py-1 text-wrap" style="font-size: 0.78rem; max-width: 200px;">
                                        {{ $item->opdSipat->nama ?? $item->opd ?? 'Belum Ditentukan' }}
                                    </span>
                                </td>
                                <td style="min-width: 220px; max-width: 280px;">
                                    <div class="d-flex flex-column gap-0.5">
                                        @if(!empty($item->alamat))
                                            <div class="text-body-secondary" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.35; font-size: 0.78rem;" title="{{ $item->alamat }}">
                                                <i class="bi bi-geo-alt text-secondary opacity-75 me-1 flex-shrink-0"></i>{{ $item->alamat }}
                                            </div>
                                        @else
                                            <div class="text-muted fst-italic small" style="font-size: 0.76rem;">
                                                <i class="bi bi-geo-alt text-secondary opacity-50 me-1"></i>Belum ada alamat detail
                                            </div>
                                        @endif

                                        @if($item->wilayahKecamatan || $item->wilayahDesa)
                                            <div class="text-secondary opacity-75 small d-flex align-items-center gap-1.5 flex-wrap mt-0.5" style="font-size: 0.7rem;">
                                                @if($item->wilayahKecamatan)
                                                    <span>Kec. {{ $item->wilayahKecamatan->nama }}</span>
                                                @endif
                                                @if($item->wilayahKecamatan && $item->wilayahDesa)
                                                    <span>&bull;</span>
                                                @endif
                                                @if($item->wilayahDesa)
                                                    <span>Desa {{ $item->wilayahDesa->nama }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $stObj = $item->latestProses->statusProses ?? null;
                                        $statusName = $stObj->nama_status ?? 'Belum Diurus';
                                        $colorName = $stObj->warna ?? 'secondary';
                                        
                                        $badgeClass = 'bg-secondary';
                                        if ($colorName === 'success' || str_contains(strtolower($statusName), 'sertifikat') || str_contains(strtolower($statusName), 'selesai')) {
                                            $badgeClass = 'bg-success-subtle text-success border border-success-subtle';
                                        } elseif ($colorName === 'warning' || str_contains(strtolower($statusName), 'proses') || str_contains(strtolower($statusName), 'ukur')) {
                                            $badgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                                        } elseif ($colorName === 'danger' || str_contains(strtolower($statusName), 'kendala') || str_contains(strtolower($statusName), 'masalah') || str_contains(strtolower($statusName), 'sengketa')) {
                                            $badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                                        } elseif ($colorName === 'info' || str_contains(strtolower($statusName), 'pertek')) {
                                            $badgeClass = 'bg-info-subtle text-info border border-info-subtle';
                                        }
                                    @endphp
                                    <span class="badge badge-status {{ $badgeClass }}">
                                        <span class="d-inline-block rounded-circle me-1" style="width: 6px; height: 6px; background-color: currentColor;"></span>
                                        {{ $statusName }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end align-items-center gap-1">
                                        <!-- 1. Tombol Pratinjau Modal Detail (5 Tab) -->
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary rounded-3" 
                                                onclick="showDetail({{ $item->id_aset }})" 
                                                data-bs-toggle="tooltip" 
                                                title="Lihat Detail Lengkap (5 Tab)">
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        @if(auth()->user()?->role !== \App\Enums\UserRole::OPD)
                                        <!-- 2. Tombol Update NIBAR Resmi -->
                                        <button type="button" 
                                                class="btn btn-sm btn-success rounded-pill px-2.5 btn-update-nibar shadow-sm"
                                                data-id="{{ $item->id_aset }}"
                                                data-nama="{{ $item->nama_aset }}"
                                                data-kode="{{ $item->kode_aset }}"
                                                data-keterangan="{{ $item->keterangan }}"
                                                data-url="{{ route('sipat.tanah-tak-tercatat.update-nibar', $item->id_aset) }}"
                                                data-bs-toggle="tooltip" 
                                                title="Update menjadi NIBAR Resmi BPKAD">
                                            <i class="bi bi-pencil-square me-1"></i> Update NIBAR
                                        </button>
                                        @endif

                                        <!-- 3. Tombol Edit Aset Lengkap -->
                                        <a href="{{ route('sipat.aset.edit', $item->id_aset) }}" class="btn btn-sm btn-outline-secondary rounded-3" data-bs-toggle="tooltip" title="Edit Aset Lengkap">
                                            <i class="bi bi-gear"></i>
                                        </a>

                                        @if(auth()->user()?->role !== \App\Enums\UserRole::OPD)
                                        <!-- 4. Tombol Hapus Aset -->
                                        <form action="{{ route('sipat.aset.destroy', $item->id_aset) }}" method="POST" class="d-inline delete-confirm">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-3" data-bs-toggle="tooltip" title="Hapus Aset">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-secondary">
                                    <i class="bi bi-check-circle fs-1 d-block mb-2 text-success"></i>
                                    Tidak ada bidang tanah yang belum tercatat NIBAR.
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalCreateTanah">
                                            <i class="bi bi-plus-lg me-1"></i> Input Tanah Belum Tercatat Baru
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($tanahItems->hasPages())
                <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="small text-secondary">
                        Menampilkan {{ $tanahItems->firstItem() }} - {{ $tanahItems->lastItem() }} dari {{ $tanahItems->total() }} data
                    </div>
                    <div>
                        {{ $tanahItems->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Input Tanah Belum Tercatat Baru -->
<div class="modal fade" id="modalCreateTanah" tabindex="-1" aria-labelledby="modalCreateTanahLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-primary text-white p-3">
                <h5 class="modal-title fw-bold" id="modalCreateTanahLabel">
                    <i class="bi bi-plus-circle me-1.5"></i> Pendaftaran Tanah Belum Tercatat (KIB A)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('sipat.tanah-tak-tercatat.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2.5 px-3 small border-0 rounded-3 mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle-fill fs-5"></i>
                        <div>
                            Jika NIBAR/Kode Aset dikosongkan, sistem akan **otomatis membuatkan Kode NIBAR Sementara** dengan format <code>DRAFT-YYYYMMDD-XXXX</code> yang dapat diperbarui kapan saja.
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-body">NIBAR / Kode Aset (Opsional)</label>
                            <input type="text" name="kode_aset" class="form-control" placeholder="Kosongkan untuk NIBAR Draft Otomatis">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-body">Nama Aset Tanah <span class="text-danger">*</span></label>
                            <input type="text" name="nama_aset" class="form-control" placeholder="Contoh: Tanah Lapangan Olahraga Kec. Banawa" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-body">OPD Pengelola / Pemegang Hak</label>
                            @if(in_array(auth()->user()?->role, [\App\Enums\UserRole::SUPERADMIN, \App\Enums\UserRole::ADMIN]))
                            <select name="opd_id" class="form-select">
                                <option value="">-- Pilih OPD Pengelola --</option>
                                @foreach($opdList as $opd)
                                    <option value="{{ $opd->id }}">{{ $opd->nama }}</option>
                                @endforeach
                            </select>
                            @else
                            <div class="form-control bg-light text-body fw-medium py-2 d-flex align-items-center justify-content-between">
                                <span class="text-truncate" title="{{ auth()->user()->opd?->nama }}"><i class="bi bi-building me-1.5 text-secondary"></i> {{ auth()->user()->opd?->nama ?? 'OPD Anda' }}</span>
                                <span class="badge bg-secondary-subtle text-secondary border ms-2 flex-shrink-0">Terkunci</span>
                            </div>
                            <input type="hidden" name="opd_id" value="{{ auth()->user()->opd_id }}">
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-body">Peruntukan Tanah</label>
                            <input type="text" name="peruntukan" class="form-control" placeholder="Contoh: Gedung Sekolah / Fasilitas Umum / Taman">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-body">Luas Tanah (m²)</label>
                            <input type="number" step="0.01" name="luas" class="form-control" placeholder="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-body">Status Pengurusan BPN Awal</label>
                            <select name="initial_status_id" class="form-select">
                                <option value="">-- Pilih Status Awal --</option>
                                @foreach($statusList as $st)
                                    <option value="{{ $st->id_status }}">{{ $st->nama_status }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-body">Alamat / Lokasi Fisik Tanah</label>
                        <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat lengkap lokasi tanah..."></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-body">Dasar Perolehan</label>
                            <input type="text" name="dasar_perolehan" class="form-control" placeholder="Contoh: Hibah / Pembelian / Peraturan Daerah">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-body">Harga Perolehan (Rp)</label>
                            <input type="number" step="0.01" name="harga_perolehan" class="form-control" placeholder="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-body">Catatan / Keterangan Tambahan</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan khusus lokasi / usulan NIBAR KIB A..."></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="bi bi-save me-1"></i> Simpan Tanah Belum Tercatat
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Update NIBAR Resmi -->
<div class="modal fade" id="modalUpdateNibar" tabindex="-1" aria-labelledby="modalUpdateNibarLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-success text-white p-3">
                <h5 class="modal-title fw-bold" id="modalUpdateNibarLabel">
                    <i class="bi bi-patch-check me-1.5"></i> Update menjadi NIBAR Resmi
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formUpdateNibar" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3 p-3 bg-light rounded-3 border">
                        <div class="small text-secondary fw-bold">NIBAR SEMENTARA / ASET</div>
                        <div class="fw-bold text-primary font-monospace" id="updateTargetKode">-</div>
                        <div class="text-body fw-semibold" id="updateTargetNama">-</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-body">Masukkan NIBAR Resmi BPKAD / KIB A <span class="text-danger">*</span></label>
                        <input type="text" name="kode_aset" id="inputKodeResmi" class="form-control font-monospace fw-bold text-primary" placeholder="Contoh: 12017203010000..." required>
                        <div class="form-text small">NIBAR resmi ini akan menggantikan Kode NIBAR Sementara di seluruh laporan dan sistem.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-body">Catatan Keterangan Tambahan</label>
                        <textarea name="keterangan" id="inputKeteranganUpdate" class="form-control" rows="2" placeholder="Catatan penetapan NIBAR KIB A..."></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm">
                        <i class="bi bi-check-lg me-1"></i> Simpan NIBAR Resmi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Remote Container (Terintegrasi penuh dengan Master Aset) -->
<div class="modal fade" id="modalDetailAset" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow" id="modalDetailContent">
            <div class="p-5 text-center text-secondary">
                <div class="spinner-border text-primary me-2" role="status"></div> Memuat data detail aset...
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let detailModalInstance = null;

    function showDetail(id) {
        const modalEl = document.getElementById('modalDetailAset');
        const modalContent = document.getElementById('modalDetailContent');
        
        if (modalEl && modalEl.parentNode !== document.body) {
            document.body.appendChild(modalEl);
        }

        modalContent.innerHTML = `
            <div class="p-5 text-center text-secondary">
                <div class="spinner-border text-primary me-2" role="status"></div> Memuat data detail aset...
            </div>
        `;
        
        if (!detailModalInstance) {
            detailModalInstance = new bootstrap.Modal(modalEl, {
                backdrop: true,
                keyboard: true
            });
        }
        
        detailModalInstance.show();

        fetch(`{{ url('sipat/aset') }}/${id}/modal`)
            .then(res => res.text())
            .then(html => {
                modalContent.innerHTML = html;
            })
            .catch(err => {
                modalContent.innerHTML = `
                    <div class="p-4 text-center text-danger">
                        <i class="bi bi-exclamation-triangle fs-2 d-block mb-2"></i>
                        Gagal memuat data detail aset. Silakan coba lagi.
                    </div>
                `;
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modalUpdateEl = document.getElementById('modalUpdateNibar');
        if (modalUpdateEl) {
            const modalUpdate = new bootstrap.Modal(modalUpdateEl);
            const formUpdate = document.getElementById('formUpdateNibar');
            const updateTargetKode = document.getElementById('updateTargetKode');
            const updateTargetNama = document.getElementById('updateTargetNama');
            const inputKodeResmi = document.getElementById('inputKodeResmi');
            const inputKeteranganUpdate = document.getElementById('inputKeteranganUpdate');

            document.querySelectorAll('.btn-update-nibar').forEach(btn => {
                btn.addEventListener('click', function () {
                    const url = this.getAttribute('data-url');
                    const kode = this.getAttribute('data-kode');
                    const nama = this.getAttribute('data-nama');
                    const keterangan = this.getAttribute('data-keterangan');

                    formUpdate.action = url;
                    updateTargetKode.textContent = kode || 'Tanpa NIBAR';
                    updateTargetNama.textContent = nama;
                    inputKodeResmi.value = (kode && !kode.startsWith('DRAFT-') && kode !== '-') ? kode : '';
                    inputKeteranganUpdate.value = keterangan || '';

                    modalUpdate.show();
                });
            });
        }

        // Copy NIBAR Helper
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-copy-nibar');
            if (!btn) return;
            e.preventDefault();
            const nibar = btn.getAttribute('data-nibar');
            if (nibar && navigator.clipboard) {
                navigator.clipboard.writeText(nibar).then(() => {
                    const icon = btn.querySelector('i');
                    if (icon) {
                        const oldClass = icon.className;
                        icon.className = 'bi bi-check2 text-success';
                        btn.classList.add('text-success');
                        setTimeout(() => {
                            icon.className = oldClass;
                            btn.classList.remove('text-success');
                        }, 1500);
                    }
                }).catch(() => {
                    const tempInput = document.createElement('input');
                    tempInput.value = nibar;
                    document.body.appendChild(tempInput);
                    tempInput.select();
                    document.execCommand('copy');
                    document.body.removeChild(tempInput);
                });
            }
        });
    });
</script>
@endpush
@endsection
