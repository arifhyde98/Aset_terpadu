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
        transition: transform 0.2s ease;
    }
    .metric-box:hover {
        transform: translateY(-2px);
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
            <button type="button" class="btn btn-primary rounded-pill px-3.5 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCreateTanah">
                <i class="bi bi-plus-lg me-1.5"></i> Input Tanah Belum Tercatat Baru
            </button>
            <a href="{{ route('sipat.aset.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Master Aset
            </a>
        </div>
    </div>

    <!-- Filter Header -->
    <div class="card target-card mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('sipat.tanah-tak-tercatat.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4 col-sm-6">
                    <label class="form-label small fw-bold text-secondary mb-1"><i class="bi bi-building me-1"></i> OPD Pengelola</label>
                    <select name="opd_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Semua OPD Pengelola --</option>
                        @foreach($opdList as $opd)
                            <option value="{{ $opd->id }}" {{ (string)$opdId === (string)$opd->id ? 'selected' : '' }}>{{ $opd->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-5 col-sm-6">
                    <label class="form-label small fw-bold text-secondary mb-1"><i class="bi bi-search me-1"></i> Kata Kunci</label>
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
                <div class="fs-2 fw-extrabold text-dark font-monospace mt-1">{{ number_format($totalUnrecorded) }}</div>
                <div class="text-secondary small">Belum Masuk Data Induk KIB A Resmi</div>
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

    <!-- Tabel Main Data -->
    <div class="card target-card">
        <div class="target-card-header d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-body">
                <i class="bi bi-card-checklist text-primary me-2"></i>Daftar Bidang Tanah Belum Tercatat di KIB A
            </h5>
            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1.5 fw-semibold">
                {{ $tanahItems->total() }} Bidang Ditampilkan
            </span>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 50px;">No</th>
                            <th>NIBAR / Kode Aset</th>
                            <th>Nama Aset Tanah / Peruntukan</th>
                            <th>OPD Pengelola</th>
                            <th>Luas (m²) & Lokasi</th>
                            <th>Status Pengurusan BPN</th>
                            <th>Catatan</th>
                            <th class="text-end pe-4" style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tanahItems as $index => $item)
                            <tr>
                                <td class="ps-4 fw-semibold text-secondary">
                                    {{ $tanahItems->firstItem() + $index }}
                                </td>
                                <td>
                                    @if(str_starts_with($item->kode_aset ?? '', 'DRAFT-'))
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace px-2.5 py-1">
                                            <i class="bi bi-tag-fill me-1"></i>{{ $item->kode_aset }}
                                        </span>
                                    @elseif(!empty($item->kode_aset))
                                        <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace px-2.5 py-1">
                                            <i class="bi bi-patch-check-fill me-1"></i>{{ $item->kode_aset }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary px-2.5 py-1">Tanpa NIBAR</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold text-body">{{ $item->nama_aset }}</div>
                                    <small class="text-secondary">{{ $item->peruntukan ?? 'Peruntukan belum diisi' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $item->opdSipat->nama ?? $item->opd ?? 'Belum Ditentukan' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ number_format($item->luas ?? 0, 0, ',', '.') }} m²</div>
                                    <small class="text-secondary">{{ \Illuminate\Support\Str::limit($item->alamat ?? '-', 35) }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2.5 py-1">
                                        {{ $item->latestProses->statusProses->nama_status ?? 'Belum Diurus' }}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-secondary">{{ \Illuminate\Support\Str::limit($item->keterangan ?? '-', 30) }}</small>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-success rounded-pill px-2.5 btn-update-nibar"
                                                data-id="{{ $item->id_aset }}"
                                                data-nama="{{ $item->nama_aset }}"
                                                data-kode="{{ $item->kode_aset }}"
                                                data-keterangan="{{ $item->keterangan }}"
                                                data-url="{{ route('sipat.tanah-tak-tercatat.update-nibar', $item->id_aset) }}"
                                                data-bs-toggle="tooltip" 
                                                title="Update menjadi NIBAR Resmi">
                                            <i class="bi bi-pencil-square me-1"></i> Update NIBAR
                                        </button>
                                        <a href="{{ route('sipat.aset.edit', $item->id_aset) }}" class="btn btn-sm btn-outline-secondary border-0 rounded-circle" data-bs-toggle="tooltip" title="Edit Aset Lengkap">
                                            <i class="bi bi-gear"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-secondary">
                                    <i class="bi bi-check-circle fs-1 d-block mb-2 text-success"></i>
                                    Tidak ada bidang tanah yang belum tercatat atau bermasalah.
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
                <div class="p-3 border-top d-flex justify-content-between align-items-center">
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
                            <select name="opd_id" class="form-select">
                                <option value="">-- Pilih OPD Pengelola --</option>
                                @foreach($opdList as $opd)
                                    <option value="{{ $opd->id }}">{{ $opd->nama }}</option>
                                @endforeach
                            </select>
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

@push('scripts')
<script>
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
                    inputKodeResmi.value = (kode && !kode.startsWith('DRAFT-')) ? kode : '';
                    inputKeteranganUpdate.value = keterangan || '';

                    modalUpdate.show();
                });
            });
        }
    });
</script>
@push('scripts')
@endsection
