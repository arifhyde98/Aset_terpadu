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
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-secondary mb-1">Status Pencatatan Aset</label>
                                <div class="d-flex gap-3 flex-wrap">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status_pencatatan" id="statusTercatat" value="TERCATAT_KIB_A" {{ old('status_pencatatan', request('status_pencatatan', 'TERCATAT_KIB_A')) === 'TERCATAT_KIB_A' ? 'checked' : '' }}>
                                        <label class="form-check-label small fw-semibold text-body" for="statusTercatat">
                                            <i class="bi bi-patch-check-fill text-success me-1"></i>Tercatat Resmi KIB A
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status_pencatatan" id="statusBelumTercatat" value="USULAN_BELUM_TERCATAT" {{ old('status_pencatatan', request('status_pencatatan')) === 'USULAN_BELUM_TERCATAT' ? 'checked' : '' }}>
                                        <label class="form-check-label small fw-semibold text-body" for="statusBelumTercatat">
                                            <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>Tanah Belum Tercatat / Usulan (NIBAR Sementara)
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold text-secondary mb-1" id="labelKodeAset">Kode Aset (NIBAR) <span class="text-danger" id="reqStarKodeAset">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body border-0 text-secondary"><i class="bi bi-hash"></i></span>
                                    <input type="text" name="kode_aset" id="inputKodeAset" class="form-control" placeholder="Contoh: 12.01.02.01.001" value="{{ old('kode_aset') }}" required>
                                </div>
                                <div class="form-text small text-muted" id="helpKodeAset">Nomor Induk Barang resmi KIB A dari BPKAD.</div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small fw-semibold text-secondary mb-1">Nama Aset Tanah (Klasifikasi KIB A) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body border-0 text-secondary"><i class="bi bi-tag"></i></span>
                                    <select name="nama_aset_id" id="createNamaAsetSelect" class="form-select" required>
                                        <option value="">-- Pilih Nama Aset --</option>
                                        @if(isset($masterNamaAsetList))
                                            @foreach($masterNamaAsetList as $mna)
                                                <option value="{{ $mna->id }}" {{ old('nama_aset_id') == $mna->id ? 'selected' : '' }}>
                                                    {{ $mna->nama }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1">OPD Pengelola</label>
                                @if(auth()->check() && (auth()->user()->role === \App\Enums\UserRole::OPD || auth()->user()->role === \App\Enums\UserRole::KPB))
                                    <div class="input-group">
                                        <span class="input-group-text bg-body border-0 text-secondary"><i class="bi bi-building-lock text-primary"></i></span>
                                        <div class="form-control bg-light text-secondary fw-semibold">
                                            {{ auth()->user()->opd?->nama ?? 'Instansi Tidak Ditemukan' }}
                                        </div>
                                    </div>
                                    <input type="hidden" name="opd_id" value="{{ auth()->user()->opd_id }}">
                                    <input type="hidden" name="opd" value="{{ auth()->user()->opd?->nama }}">
                                    @if(auth()->user()->role === \App\Enums\UserRole::KPB)
                                        <input type="hidden" name="sub_opd_id" value="{{ auth()->user()->sub_opd_id }}">
                                    @endif
                                @else
                                    <div class="input-group">
                                        <span class="input-group-text bg-body border-0 text-secondary"><i class="bi bi-building"></i></span>
                                        <select name="opd_id" class="form-select">
                                            <option value="">- Pilih OPD -</option>
                                            @foreach($opdList as $opd)
                                                <option value="{{ $opd->id }}" {{ old('opd_id') == $opd->id ? 'selected' : '' }}>{{ $opd->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
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
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1">Wilayah Kecamatan</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body border-0 text-secondary"><i class="bi bi-geo-alt"></i></span>
                                    <select name="kecamatan_id" id="createKecamatanSelect" class="form-select">
                                        <option value="">- Pilih Kecamatan -</option>
                                        @if(isset($kecamatanList))
                                            @foreach($kecamatanList as $kec)
                                                <option value="{{ $kec->id }}" {{ old('kecamatan_id') == $kec->id ? 'selected' : '' }}>{{ $kec->nama }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1">Wilayah Desa / Kelurahan</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body border-0 text-secondary"><i class="bi bi-houses"></i></span>
                                    <select name="desa_id" id="createDesaSelect" class="form-select">
                                        <option value="">- Pilih Desa / Kelurahan -</option>
                                        @if(isset($desaList))
                                            @foreach($desaList as $ds)
                                                <option value="{{ $ds->id }}" data-kec="{{ $ds->kecamatan_id }}" {{ old('desa_id') == $ds->id ? 'selected' : '' }}>
                                                    {{ $ds->nama }} {{ $ds->kecamatan ? '('.$ds->kecamatan->nama.')' : '' }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-secondary mb-1">Alamat Singkat / Detail Jalan</label>
                                <textarea name="alamat" class="form-control" rows="2" placeholder="Jalan / Dusun / RT...">{{ old('alamat') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1">Latitude (Koordinat Y)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body border-0 text-secondary"><i class="bi bi-geo"></i></span>
                                    <input type="text" name="lat" class="form-control" placeholder="-0.xxxxxx" value="{{ old('lat') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary mb-1">Longitude (Koordinat X)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body border-0 text-secondary"><i class="bi bi-geo"></i></span>
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

        // Toggle required & helper for NIBAR berdasarkan status pencatatan
        const statusTercatat = document.getElementById('statusTercatat');
        const statusBelumTercatat = document.getElementById('statusBelumTercatat');
        const inputKodeAset = document.getElementById('inputKodeAset');
        const reqStar = document.getElementById('reqStarKodeAset');
        const helpKode = document.getElementById('helpKodeAset');

        const updateStatusPencatatanUI = () => {
            if (statusBelumTercatat && statusBelumTercatat.checked) {
                inputKodeAset.removeAttribute('required');
                inputKodeAset.placeholder = 'Kosongkan untuk otomatis NIBAR Sementara (DRAFT-...)';
                if (reqStar) reqStar.style.display = 'none';
                if (helpKode) helpKode.innerText = 'Opsional. Jika dikosongkan, sistem akan otomatis membuat NIBAR Sementara (DRAFT-...).';
            } else {
                inputKodeAset.setAttribute('required', 'required');
                inputKodeAset.placeholder = 'Contoh: 12.01.02.01.001';
                if (reqStar) reqStar.style.display = 'inline';
                if (helpKode) helpKode.innerText = 'Nomor Induk Barang resmi KIB A dari BPKAD.';
            }
        };

        if (statusTercatat && statusBelumTercatat) {
            statusTercatat.addEventListener('change', updateStatusPencatatanUI);
            statusBelumTercatat.addEventListener('change', updateStatusPencatatanUI);
            updateStatusPencatatanUI();
        }

        const kecSelect = document.getElementById('createKecamatanSelect');
        const desaSelect = document.getElementById('createDesaSelect');
        if (kecSelect && desaSelect) {
            const allDesaOptions = Array.from(desaSelect.options);
            
            const filterDesa = () => {
                const kecId = kecSelect.value;
                const curDesaId = desaSelect.value;
                desaSelect.innerHTML = '';
                allDesaOptions.forEach(opt => {
                    if (opt.value === '' || !kecId || opt.dataset.kec === kecId) {
                        desaSelect.appendChild(opt.cloneNode(true));
                    }
                });
                if (curDesaId) {
                    const match = Array.from(desaSelect.options).some(o => o.value === curDesaId);
                    if (match) desaSelect.value = curDesaId;
                }
            };

            kecSelect.addEventListener('change', filterDesa);
            desaSelect.addEventListener('change', function() {
                const sel = this.options[this.selectedIndex];
                if (sel && sel.dataset.kec && !kecSelect.value) {
                    kecSelect.value = sel.dataset.kec;
                    filterDesa();
                }
            });

            if (kecSelect.value) {
                filterDesa();
            }
        }
    });
</script>
@endpush
@endsection
