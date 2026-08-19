@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Tambah Aset Tanah</h2>
            <p class="text-secondary small mb-0">Formulir Pendaftaran Master Data Aset Tanah Baru</p>
        </div>
        <a href="{{ route('sipat.aset.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>
</div>

@push('modals')
<!-- Modal Form Tambah Aset Tanah (Exact Business Process like Web SIPAT) -->
<div class="modal fade" id="modalFormCreate" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header bg-primary-subtle border-bottom px-4 py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-plus-circle-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-body mb-0">Tambah Aset Tanah Baru</h5>
                        <small class="text-primary fw-medium">Input Master Data Aset & Geospasial</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-body">
                @if ($errors->any())
                    <div class="alert alert-danger rounded-3 p-3 mb-3">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('sipat.aset.store') }}" method="POST" id="formCreateAset">
                    @csrf
                    
                    <!-- Section 1: Identitas & Pemilik Aset -->
                    <div class="card clean-card border-0 rounded-4 p-3 mb-3 shadow-sm bg-body">
                        <h6 class="fw-bold text-body mb-3">
                            <i class="bi bi-card-heading text-primary me-2"></i>1. Identitas & Pemilik Aset
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold text-secondary mb-1">Kode Aset (NIBAR) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body border-0 text-secondary"><i class="bi bi-hash"></i></span>
                                    <input type="text" name="kode_aset" class="form-control" placeholder="Contoh: 12.01.02.01.001" value="{{ old('kode_aset') }}" required>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small fw-semibold text-secondary mb-1">Nama Aset Tanah <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body border-0 text-secondary"><i class="bi bi-geo"></i></span>
                                    <input type="text" name="nama_aset" class="form-control" placeholder="Nama aset tanah..." value="{{ old('nama_aset') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1">OPD Pengelola</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body border-0 text-secondary"><i class="bi bi-building"></i></span>
                                    <select name="opd" class="form-select">
                                        <option value="">- Pilih OPD -</option>
                                        @foreach($opdList as $opd)
                                            <option value="{{ $opd->nama }}" {{ old('opd') == $opd->nama ? 'selected' : '' }}>{{ $opd->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1">Peruntukan / Penggunaan</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body border-0 text-secondary"><i class="bi bi-signpost-split"></i></span>
                                    <input type="text" name="peruntukan" class="form-control" placeholder="Contoh: Kantor Kecamatan / Lapangan" value="{{ old('peruntukan') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Legalitas & Nilai -->
                    <div class="card clean-card border-0 rounded-4 p-3 mb-3 shadow-sm bg-body">
                        <h6 class="fw-bold text-body mb-3">
                            <i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>2. Legalitas, Luas & Nilai Perolehan
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-secondary mb-1">Luas Tanah (m²)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body border-0 text-secondary"><i class="bi bi-aspect-ratio"></i></span>
                                    <input type="number" step="0.01" name="luas" class="form-control" placeholder="0.00" value="{{ old('luas') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-secondary mb-1">Tanggal Perolehan</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body border-0 text-secondary"><i class="bi bi-calendar-event"></i></span>
                                    <input type="date" name="tanggal_perolehan" class="form-control" value="{{ old('tanggal_perolehan') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-secondary mb-1">Harga Perolehan (Rp)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body border-0 text-secondary">Rp</span>
                                    <input type="number" step="0.01" name="harga_perolehan" class="form-control" placeholder="0" value="{{ old('harga_perolehan') }}">
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-secondary mb-1">Dasar Perolehan / Dokumen Awal</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body border-0 text-secondary"><i class="bi bi-journal-text"></i></span>
                                    <input type="text" name="dasar_perolehan" class="form-control" placeholder="Contoh: Hibah / Pembelian APBD / SK Bupati..." value="{{ old('dasar_perolehan') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Geospasial & Alamat -->
                    <div class="card clean-card border-0 rounded-4 p-3 mb-3 shadow-sm bg-body">
                        <h6 class="fw-bold text-body mb-3">
                            <i class="bi bi-geo-alt-fill text-danger me-2"></i>3. Lokasi Geospasial & Catatan
                        </h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-secondary mb-1">Alamat Lengkap</label>
                                <textarea name="alamat" class="form-control" rows="2" placeholder="Jalan, Desa/Kelurahan, Kecamatan...">{{ old('alamat') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1">Latitude (Koordinat Y)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body border-0 text-secondary"><i class="bi bi-pin-map"></i></span>
                                    <input type="text" name="lat" class="form-control" placeholder="-0.xxxxxx" value="{{ old('lat') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1">Longitude (Koordinat X)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body border-0 text-secondary"><i class="bi bi-pin-map"></i></span>
                                    <input type="text" name="lng" class="form-control" placeholder="119.xxxxxx" value="{{ old('lng') }}">
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-secondary mb-1">Keterangan Tambahan</label>
                                <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan fisik tanah, batas-batas, atau kondisi saat ini...">{{ old('keterangan') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <a href="{{ route('sipat.aset.index') }}" class="btn btn-secondary rounded-pill px-4">
                            <i class="bi bi-x-lg me-1"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                            <i class="bi bi-save2 me-1"></i> Simpan Data Aset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('modalFormCreate');
        if (!modalEl || typeof bootstrap === 'undefined') return;

        if (modalEl.parentNode !== document.body) {
            document.body.appendChild(modalEl);
        }

        const modal = new bootstrap.Modal(modalEl, { backdrop: 'static' });
        modal.show();

        modalEl.addEventListener('hidden.bs.modal', function () {
            window.location.href = '{{ route("sipat.aset.index") }}';
        });
    });
</script>
@endpush
@endsection
