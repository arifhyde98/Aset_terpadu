<style>
    .detail-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--bs-secondary-color, #94a3b8);
        font-weight: 700;
        margin-bottom: 0.25rem;
    }
    .detail-value {
        font-size: 0.95rem;
        font-weight: 600;
        margin-bottom: 0.85rem;
    }
    .modal-tabs .nav-link {
        font-size: 0.88rem;
        font-weight: 600;
        border: none;
        border-bottom: 2px solid transparent;
        padding: 0.75rem 1rem;
        margin-right: 0.25rem;
        background: transparent;
        transition: all 0.2s ease;
    }
    .modal-tabs .nav-link:hover {
        color: #3b82f6;
    }
    .modal-tabs .nav-link.active {
        color: #3b82f6;
        border-bottom-color: #3b82f6;
        background: transparent;
    }
    .detail-card-surface {
        border-radius: 1rem;
        border: 1px solid var(--border-color, rgba(0,0,0,0.08));
    }
    .v-timeline {
        position: relative;
        padding-left: 1.5rem;
        list-style: none;
        margin-bottom: 0;
    }
    .v-timeline::before {
        content: '';
        position: absolute;
        top: 0; bottom: 0; left: 0.4rem;
        width: 2px;
        background: var(--border-color, rgba(148, 163, 184, 0.3));
    }
    .v-timeline-item {
        position: relative;
        padding-bottom: 1.25rem;
    }
    .v-timeline-item:last-child { padding-bottom: 0; }
    .v-timeline-node {
        position: absolute;
        left: -1.5rem; top: 0.25rem;
        width: 14px; height: 14px;
        border-radius: 50%;
        border: 2px solid var(--bs-body-bg, #fff);
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
        z-index: 1;
    }
</style>

<div class="modal-header border-bottom px-4 py-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary font-monospace px-2.5 py-1" style="font-size: 0.8rem;">
                {{ $aset->kode_aset ?? '-' }}
            </span>
            <span class="badge bg-secondary-subtle text-body-secondary fw-normal px-2.5 py-1" style="font-size: 0.78rem;">
                {{ $aset->opd ?? 'BPKAD' }}
            </span>
        </div>
        <h4 class="modal-title fw-bold mb-0 text-body">{{ $aset->nama_aset }}</h4>
    </div>
    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Tutup"></button>
</div>

<!-- Navigasi 5 Tab -->
<div class="px-4 pt-3 border-bottom">
    <ul class="nav nav-tabs modal-tabs border-bottom-0" id="assetDetailTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#tab-info" type="button" role="tab">
                <i class="bi bi-info-circle me-1.5"></i>Informasi Utama
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="timeline-tab" data-bs-toggle="tab" data-bs-target="#tab-timeline" type="button" role="tab">
                <i class="bi bi-hourglass-split me-1.5"></i>Riwayat & Proses BPN
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#tab-security" type="button" role="tab">
                <i class="bi bi-shield-check me-1.5"></i>Pengamanan Fisik
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="docs-tab" data-bs-toggle="tab" data-bs-target="#tab-docs" type="button" role="tab">
                <i class="bi bi-file-earmark-pdf me-1.5"></i>Dokumen Lampiran
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="elabel-tab" data-bs-toggle="tab" data-bs-target="#tab-elabel" type="button" role="tab">
                <i class="bi bi-box-seam me-1.5"></i>Arsip Fisik (eLabel)
            </button>
        </li>
    </ul>
</div>

<div class="modal-body p-4">
    <div class="tab-content" id="assetDetailTabsContent">
        
        <!-- TAB 1: Informasi Utama -->
        <div class="tab-pane fade show active" id="tab-info" role="tabpanel">
            <div class="detail-card-surface p-4">
                <div class="row g-2">
                    <div class="col-md-6 col-lg-4">
                        <div class="detail-label">OPD PENGELOLA</div>
                        <div class="detail-value text-body">{{ $aset->opd ?? '-' }}</div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="detail-label">PENGGUNAAN / PERUNTUKAN</div>
                        <div class="detail-value text-body">{{ $aset->peruntukan ?? '-' }}</div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="detail-label">LUAS TANAH</div>
                        <div class="detail-value text-success font-monospace">{{ number_format($aset->luas ?? 0, 0, ',', '.') }} <span class="fw-normal text-secondary">m²</span></div>
                    </div>
                    
                    <div class="col-md-6 col-lg-4">
                        <div class="detail-label">HARGA PEROLEHAN</div>
                        <div class="detail-value text-body font-monospace">
                            {{ $aset->harga_perolehan ? 'Rp ' . number_format($aset->harga_perolehan, 2, ',', '.') : '-' }}
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="detail-label">TANGGAL PEROLEHAN</div>
                        <div class="detail-value text-body">{{ $aset->tanggal_perolehan ?? '-' }}</div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="detail-label">DASAR PEROLEHAN</div>
                        <div class="detail-value text-body">{{ $aset->dasar_perolehan ?? '-' }}</div>
                    </div>

                    <div class="col-12 my-2"><hr class="text-secondary opacity-25 m-0"></div>

                    <div class="col-lg-6">
                        <div class="detail-label">ALAMAT / LOKASI TANAH</div>
                        <div class="p-3 bg-body rounded-3 border text-body fw-medium" style="font-size: 0.9rem;">
                            <i class="bi bi-geo-alt text-danger me-1"></i> {{ $aset->alamat ?? '-' }}
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="detail-label">LATITUDE (GIS)</div>
                        <div class="detail-value text-primary font-monospace">{{ $aset->lat ?? '-' }}</div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="detail-label">LONGITUDE (GIS)</div>
                        <div class="detail-value text-primary font-monospace">{{ $aset->lng ?? '-' }}</div>
                    </div>

                    <div class="col-12 my-2"><hr class="text-secondary opacity-25 m-0"></div>

                    <div class="col-12">
                        <div class="detail-label">KETERANGAN TAMBAHAN</div>
                        <div class="text-secondary small">{{ $aset->keterangan ?? 'Tidak ada catatan tambahan.' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: Riwayat & Proses BPN -->
        <div class="tab-pane fade" id="tab-timeline" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold mb-0 text-body">Kronologi Pensertifikatan BPN</h6>
                    <small class="text-secondary">Tahapan pengurusan sertifikat tanah di BPN Kabupaten Donggala</small>
                </div>
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="collapse" data-bs-target="#formTambahProses">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Status BPN
                </button>
            </div>

            <!-- Form Tambah Status BPN -->
            <div class="collapse mb-4" id="formTambahProses">
                <div class="detail-card-surface p-3 border-top border-3 border-primary">
                    <h6 class="fw-bold mb-3 small text-body">Form Tambah Log Tahapan BPN</h6>
                    <form action="{{ route('sipat.aset.storeProses', $aset->id_aset) }}" method="POST">
                        @csrf
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small text-secondary mb-1">Status Tahapan BPN</label>
                                <select name="id_status" class="form-select form-select-sm" required>
                                    <option value="">-- Pilih Status --</option>
                                    @foreach($statusList as $st)
                                        <option value="{{ $st->id_status }}">{{ $st->nama_status }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-secondary mb-1">Catatan / Keterangan</label>
                                <input type="text" name="keterangan" class="form-control form-control-sm" placeholder="Contoh: Pengukuran ulang oleh BPN">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-secondary mb-1">Tanggal Mulai</label>
                                <input type="date" name="tgl_mulai" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-secondary mb-1">Tanggal Selesai (Opsional)</label>
                                <input type="date" name="tgl_selesai" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="text-end mt-3">
                            <button type="button" class="btn btn-sm btn-secondary rounded-pill me-1" data-bs-toggle="collapse" data-bs-target="#formTambahProses">Batal</button>
                            <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4"><i class="bi bi-save me-1"></i>Simpan Status</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="detail-card-surface p-4">
                @if(empty($prosesList) || count($prosesList) == 0)
                    <div class="text-center py-4 text-secondary">
                        <i class="bi bi-clock-history fs-1 mb-2 d-block opacity-50"></i>
                        Belum ada riwayat proses dicatat untuk aset ini.
                    </div>
                @else
                    <ul class="v-timeline">
                        @foreach($prosesList as $proses)
                            <li class="v-timeline-item">
                                <div class="v-timeline-node bg-primary"></div>
                                <div class="p-3 bg-body rounded-3 border">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle mb-2">
                                                {{ $proses->statusProses->nama_status ?? 'Proses BPN' }}
                                            </span>
                                            <h6 class="fw-bold mb-1 text-body">{{ $proses->keterangan ?? 'Tanpa catatan' }}</h6>
                                            <div class="text-secondary small">
                                                <i class="bi bi-calendar-event me-1"></i> 
                                                {{ $proses->tgl_mulai ?? '-' }} 
                                                <i class="bi bi-arrow-right mx-1"></i> 
                                                {{ $proses->tgl_selesai ?? 'Sekarang' }}
                                                @if(!empty($proses->durasi_hari))
                                                    <span class="ms-2 badge bg-body text-body-secondary border">({{ $proses->durasi_hari }} hari)</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <!-- TAB 3: Pengamanan Fisik -->
        <div class="tab-pane fade" id="tab-security" role="tabpanel">
            <div class="detail-card-surface p-4 mx-auto" style="max-width: 650px;">
                <h6 class="fw-bold text-body mb-3"><i class="bi bi-shield-check text-success me-2"></i>Status Pengamanan Fisik Aset</h6>
                <form action="{{ route('sipat.aset.storePengamanan', $aset->id_aset) }}" method="POST">
                    @csrf
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="form-check form-switch p-3 border rounded-3 bg-body d-flex align-items-center justify-content-between">
                                <label class="form-check-label fw-semibold text-body cursor-pointer" for="chk_sertifikat">Sertifikat Ada & Valid</label>
                                <input class="form-check-input ms-0 mt-0" type="checkbox" role="switch" name="sertifikat_ada" id="chk_sertifikat" value="1" {{ !empty($pengamanan->sertifikat_ada) ? 'checked' : '' }}>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch p-3 border rounded-3 bg-body d-flex align-items-center justify-content-between">
                                <label class="form-check-label fw-semibold text-body cursor-pointer" for="chk_papan">Papan Nama Pemda</label>
                                <input class="form-check-input ms-0 mt-0" type="checkbox" role="switch" name="papan_nama" id="chk_papan" value="1" {{ !empty($pengamanan->papan_nama) ? 'checked' : '' }}>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch p-3 border rounded-3 bg-body d-flex align-items-center justify-content-between">
                                <label class="form-check-label fw-semibold text-body cursor-pointer" for="chk_pagar">Pagar Batas/Patok</label>
                                <input class="form-check-input ms-0 mt-0" type="checkbox" role="switch" name="pagar" id="chk_pagar" value="1" {{ !empty($pengamanan->pagar) ? 'checked' : '' }}>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch p-3 border rounded-3 bg-body d-flex align-items-center justify-content-between">
                                <label class="form-check-label fw-semibold text-body cursor-pointer" for="chk_dikuasai">Dikuasai Pihak Lain</label>
                                <input class="form-check-input ms-0 mt-0" type="checkbox" role="switch" name="dikuasai_pihak_lain" id="chk_dikuasai" value="1" {{ !empty($pengamanan->dikuasai_pihak_lain) ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Tanggal Pengecekan Lapangan</label>
                            <input type="date" name="tgl_cek" class="form-control" value="{{ $pengamanan->tgl_cek ?? '' }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Catatan Kondisi Fisik Lapangan</label>
                            <textarea name="catatan" class="form-control" rows="3" placeholder="Tuliskan kondisi fisik tanah di lapangan...">{{ $pengamanan->catatan ?? '' }}</textarea>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm">
                            <i class="bi bi-save me-1"></i> Simpan Status Pengamanan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TAB 4: Dokumen Lampiran -->
        <div class="tab-pane fade" id="tab-docs" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="mb-0 fw-bold text-body">Arsip Dokumen Digital</h6>
                    <small class="text-secondary">Berkas digital sertifikat, SKPT, dan dokumen tanah</small>
                </div>
                <button class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#formUploadDokumen">
                    <i class="bi bi-cloud-arrow-up me-1"></i> Upload Dokumen
                </button>
            </div>

            <!-- Form Upload Dokumen -->
            <div class="collapse mb-4" id="formUploadDokumen">
                <div class="detail-card-surface p-4 border-top border-3 border-indigo">
                    <h6 class="fw-bold mb-3 small text-body">Form Upload Dokumen Baru</h6>
                    <form action="{{ route('sipat.aset.storeDokumen', $aset->id_aset) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small text-secondary mb-1">Jenis Dokumen</label>
                                <input type="text" name="jenis_dokumen" class="form-control" placeholder="Cth: Sertifikat Hak Pakai / SKPT" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-secondary mb-1">Status Dokumen</label>
                                <input type="text" name="status_dokumen" class="form-control" placeholder="Cth: Asli / Salinan">
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-secondary mb-1">Pilih File (PDF/JPG/PNG max 10MB)</label>
                                <input type="file" name="file" class="form-control" required>
                            </div>
                        </div>
                        <div class="text-end mt-3">
                            <button type="button" class="btn btn-sm btn-secondary rounded-pill me-1" data-bs-toggle="collapse" data-bs-target="#formUploadDokumen">Batal</button>
                            <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4"><i class="bi bi-upload me-1"></i>Upload Sekarang</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="detail-card-surface overflow-hidden">
                @if(empty($dokumenList) || count($dokumenList) == 0)
                    <div class="text-center py-5 text-secondary">
                        <i class="bi bi-folder-x fs-1 mb-2 d-block opacity-50"></i>
                        Belum ada dokumen yang diunggah untuk aset ini.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-body text-secondary small">
                                <tr>
                                    <th class="ps-4 py-3">JENIS DOKUMEN</th>
                                    <th class="py-3">STATUS</th>
                                    <th class="py-3">TANGGAL UPLOAD</th>
                                    <th class="text-end pe-4 py-3">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dokumenList as $dok)
                                    <tr>
                                        <td class="ps-4 fw-semibold text-body">{{ $dok->jenis_dokumen }}</td>
                                        <td><span class="badge bg-info-subtle text-info">{{ $dok->status_dokumen ?? 'Asli' }}</span></td>
                                        <td class="text-secondary small">{{ $dok->uploaded_at ?? '-' }}</td>
                                        <td class="text-end pe-4">
                                            @if($dok->file_path)
                                                <a href="{{ asset('storage/' . $dok->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                    <i class="bi bi-eye me-1"></i> Buka File
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- TAB 5: Arsip Fisik (eLabel) -->
        <div class="tab-pane fade" id="tab-elabel" role="tabpanel">
            <div class="detail-card-surface p-4">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                    <h6 class="fw-bold mb-0 text-body"><i class="bi bi-box-seam me-1 text-primary"></i> Integrasi Arsip Fisik (eLabel)</h6>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 fs-6 fw-bold">Modul eLABEL</span>
                </div>

                @if($elabelSertifikat)
                    <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success rounded-3 mb-4 d-flex align-items-center justify-content-between">
                        <div>
                            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                            <strong>Sertifikat Terverifikasi di Modul eLabel</strong> - Data fisik arsip telah tersimpan di gudang aset.
                        </div>
                        <a href="{{ route('elabel.sertifikat.show', $elabelSertifikat->id) }}" target="_blank" class="btn btn-sm btn-success fw-bold">
                            <i class="bi bi-eye me-1"></i> Buka Katalog eLabel
                        </a>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="p-3 bg-body rounded-3 border h-100">
                                <small class="text-secondary d-block fw-semibold text-uppercase mb-1">KODE BOX GUDANG</small>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 fs-6 rounded-3 fw-bold">
                                    <i class="bi bi-archive me-1"></i> {{ $elabelSertifikat->box->box_code ?? 'Box Belum Ditentukan' }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-body rounded-3 border h-100">
                                <small class="text-secondary d-block fw-semibold text-uppercase mb-1">LOKASI RAK / WILAYAH</small>
                                <span class="fw-bold text-dark fs-6"><i class="bi bi-geo-alt text-danger me-1"></i> {{ $elabelSertifikat->box->lokasi ?? $elabelSertifikat->lokasi ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-body rounded-3 border h-100">
                                <small class="text-secondary d-block fw-semibold text-uppercase mb-1">NO. SERTIPIKAT (eLABEL)</small>
                                <span class="fw-bold text-navy font-monospace fs-6">{{ $elabelSertifikat->no_sertipikat }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <small class="text-secondary d-block fw-semibold text-uppercase mb-1">STATUS PENGGUNAAN</small>
                            <div class="fw-semibold text-dark">{{ $elabelSertifikat->status_penggunaan ?: '-' }}</div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-secondary d-block fw-semibold text-uppercase mb-1">ATAS NAMA PEMILIK</small>
                            <div class="fw-semibold text-dark">{{ $elabelSertifikat->nama_pemilik ?: '-' }}</div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-secondary d-block fw-semibold text-uppercase mb-1">DINAS / OPD PENGGUNA</small>
                            <div class="fw-semibold text-dark">{{ $elabelSertifikat->dinas ?: '-' }}</div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 pt-3 border-top">
                        @if($elabelSertifikat->pdf_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($elabelSertifikat->pdf_path))
                            <a href="{{ route('elabel.sertifikat.view-pdf', $elabelSertifikat->id) }}" target="_blank" class="btn btn-primary fw-medium shadow-sm">
                                <i class="bi bi-file-earmark-pdf me-1"></i> Buka Fullscreen PDF
                            </a>
                        @endif

                        @if($elabelSertifikat->box_id)
                            <a href="{{ route('elabel.sertifikat-boxes.label', ['id' => $elabelSertifikat->box_id, 'autoprint' => 1]) }}" target="_blank" class="btn btn-outline-success fw-medium">
                                <i class="bi bi-printer me-1"></i> Cetak Label Box Fisik
                            </a>
                            <a href="{{ route('elabel.sertifikat-boxes.show', $elabelSertifikat->box_id) }}" target="_blank" class="btn btn-light border fw-medium">
                                <i class="bi bi-box-seam me-1"></i> Detail Box Gudang
                            </a>
                        @endif
                    </div>

                    <!-- DIRECT LIVE PREVIEW SCAN PDF SERTIFIKAT -->
                    @if($elabelSertifikat->pdf_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($elabelSertifikat->pdf_path))
                        <div class="mt-4 pt-3 border-top">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="fw-bold mb-0 text-navy"><i class="bi bi-file-earmark-pdf text-danger me-1"></i> Viewer Scan PDF Sertifikat Tanah (eLabel)</h6>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2.5 py-1 small">PDF Viewer</span>
                            </div>
                            <div class="rounded-4 overflow-hidden border shadow-sm" style="height: 520px; background: #525659;">
                                <iframe src="{{ route('elabel.sertifikat.view-pdf', $elabelSertifikat->id) }}" style="width: 100%; height: 100%; border: none;"></iframe>
                            </div>
                        </div>
                    @else
                        <div class="mt-4 pt-3 border-top">
                            <div class="p-3 bg-light rounded-3 border text-center text-secondary">
                                <i class="bi bi-file-earmark-x fs-2 d-block mb-1 text-warning opacity-75"></i>
                                <span class="small fw-semibold">File scan PDF sertifikat belum diunggah di Katalog eLabel.</span>
                                <div class="mt-2">
                                    <a href="{{ route('elabel.sertifikat.edit', $elabelSertifikat->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-upload me-1"></i> Upload Scan PDF Sekarang
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="alert alert-warning border-0 bg-warning bg-opacity-10 text-dark rounded-3 mb-4">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill text-warning me-2 fs-5"></i>
                            <div>
                                <strong>Sertifikat Belum Terdaftar di Modul eLabel</strong>
                                <div class="small text-secondary mt-1">Dokumen fisik sertifikat untuk aset ini belum tercatat dalam sistem penataan box gudang eLabel.</div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-body rounded-3 border text-center my-3">
                        <i class="bi bi-folder-plus text-primary fs-1 d-block mb-2"></i>
                        <h6 class="fw-bold text-dark mb-1">Daftarkan Sertifikat Fisik Sekarang</h6>
                        <p class="text-secondary small mb-3">Klik tombol di bawah untuk mendaftarkan sertifikat tanah ini langsung ke Katalog eLabel dengan data terisi otomatis.</p>
                        
                        <a href="{{ route('elabel.sertifikat.create', [
                            'no_sertipikat' => $aset->no_sertifikat ?? '',
                            'nibar' => $aset->kode_aset ?? '',
                            'nama_pemilik' => 'Pemerintah Kabupaten Donggala',
                            'dinas' => $aset->opd ?? '',
                            'luas' => $aset->luas ?? '',
                            'alamat' => $aset->alamat ?? '',
                        ]) }}" target="_blank" class="btn btn-primary fw-bold px-4 py-2 shadow-sm">
                            <i class="bi bi-plus-lg me-1"></i> + Daftarkan ke Katalog eLabel
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal-footer border-top px-4 py-3">
    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup Modal</button>
</div>
