<!-- Unified Asset Search Section (Focal Point) -->
<section id="search-section" class="landing-search-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-11 col-xl-10">
                <!-- Main Search Card -->
                <div class="unified-search-card shadow-lg rounded-4 bg-white border border-light-subtle overflow-hidden">
                    
                    <!-- Search Header & Tab Navigation -->
                    <div class="unified-search-header bg-navy text-white px-3 px-md-4 pt-3 pb-2">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="search-header-icon rounded-circle d-flex align-items-center justify-content-center text-amber shadow-sm">
                                    <i class="bi bi-search fs-5"></i>
                                </span>
                                <div>
                                    <h3 class="fs-5 fw-bold mb-0 text-white">Pencarian Aset Terpadu</h3>
                                    <p class="small text-white-50 mb-0">Pilih modul aset, masukkan nomor atau kata kunci untuk mencari data publik.</p>
                                </div>
                            </div>
                            <span class="badge bg-white bg-opacity-10 border border-white border-opacity-20 text-white-90 fw-normal px-3 py-1 rounded-pill small align-self-start align-self-md-auto">
                                <i class="bi bi-shield-check text-success me-1"></i> Akses Publik Bebas Login
                            </span>
                        </div>

                        <!-- Tab Pills (3 Modul: Kendaraan Dinas, Sertifikasi Tanah, Arsip Aset) -->
                        <ul class="nav nav-pills search-nav-pills gap-2" id="unifiedSearchTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link tab-btn-vehicle active d-flex align-items-center justify-content-center gap-2" id="tab-vehicle-btn" data-bs-toggle="pill" data-bs-target="#tab-vehicle-pane" type="button" role="tab" aria-controls="tab-vehicle-pane" aria-selected="true" data-tab-name="vehicle">
                                    <i class="bi bi-car-front fs-6"></i>
                                    <span>Kendaraan Dinas</span>
                                    <span class="badge tab-badge-module fw-normal px-2 py-0 d-none d-sm-inline" style="font-size: 0.65rem;">ERANDIS</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link tab-btn-land d-flex align-items-center justify-content-center gap-2" id="tab-land-btn" data-bs-toggle="pill" data-bs-target="#tab-land-pane" type="button" role="tab" aria-controls="tab-land-pane" aria-selected="false" data-tab-name="land">
                                    <i class="bi bi-geo-alt fs-6"></i>
                                    <span>Sertifikasi Tanah</span>
                                    <span class="badge tab-badge-module fw-normal px-2 py-0 d-none d-sm-inline" style="font-size: 0.65rem;">SIPAT</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link tab-btn-archive d-flex align-items-center justify-content-center gap-2" id="tab-archive-btn" data-bs-toggle="pill" data-bs-target="#tab-archive-pane" type="button" role="tab" aria-controls="tab-archive-pane" aria-selected="false" data-tab-name="archive">
                                    <i class="bi bi-folder2-open fs-6"></i>
                                    <span>Arsip Aset</span>
                                    <span class="badge tab-badge-module fw-normal px-2 py-0 d-none d-sm-inline" style="font-size: 0.65rem;">EARSIP</span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Search Tab Content Panels -->
                    <div class="tab-content p-3 p-md-4" id="unifiedSearchTabContent">
                        
                        <!-- ================= TAB 1: KENDARAAN DINAS ================= -->
                        <div class="tab-pane fade show active" id="tab-vehicle-pane" role="tabpanel" aria-labelledby="tab-vehicle-btn" tabindex="0">
                            <form id="vehicleSearchForm" class="unified-search-form" data-search-endpoint="{{ route('landing.search.vehicles') }}">
                                <div class="row g-2 align-items-stretch">
                                    <!-- Dropdown Cari Berdasarkan -->
                                    <div class="col-md-3 col-lg-3">
                                        <label class="form-label small text-secondary fw-semibold mb-1">Cari Berdasarkan</label>
                                        <select name="search_by" class="form-select form-select-lg fs-6 py-2 shadow-none border-light-subtle rounded-3" id="vehicleSearchBy">
                                            <option value="no_polisi" selected>Nomor Polisi</option>
                                            <option value="nibar">NIBAR / Register</option>
                                            <option value="kode_barang">Merk / Tipe Kendaraan</option>
                                            <option value="all">Semua Kriteria</option>
                                        </select>
                                    </div>

                                    <!-- Input Keyword -->
                                    <div class="col-md-6 col-lg-6">
                                        <label class="form-label small text-secondary fw-semibold mb-1">Nomor Polisi / Kata Kunci</label>
                                        <div class="position-relative">
                                            <input type="text" name="q" id="vehicleQueryInput" class="form-control form-control-lg fs-6 py-2 ps-3 pe-4 shadow-none border-light-subtle rounded-3" placeholder="Contoh: DN 1234 XX atau B 1234 XX" style="text-transform: uppercase;">
                                            <button type="button" class="btn btn-link text-muted position-absolute end-0 top-50 translate-middle-y text-decoration-none clear-input-btn d-none" style="z-index: 5;">
                                                <i class="bi bi-x-circle-fill"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="col-md-3 col-lg-3 d-flex align-items-end">
                                        <button type="submit" class="btn btn-search-vehicle w-100 py-2 fs-6 fw-bold rounded-3 d-flex align-items-center justify-content-center gap-2" style="height: 48px;">
                                            <i class="bi bi-search"></i>
                                            <span>Cari Kendaraan</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Quick Interactive Suggestion Chips -->
                                <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-2">
                                    <span class="small text-secondary fw-semibold" style="font-size: 0.78rem;">💡 Saran Pencarian:</span>
                                    <div class="quick-search-chips">
                                        <span class="chip-item chip-blue quick-query-chip" data-target-input="vehicleQueryInput" data-target-form="vehicleSearchForm" data-query="DN">🚗 DN Plat</span>
                                        <span class="chip-item chip-blue quick-query-chip" data-target-input="vehicleQueryInput" data-target-form="vehicleSearchForm" data-query="Toyota">🚗 Toyota</span>
                                        <span class="chip-item chip-blue quick-query-chip" data-target-input="vehicleQueryInput" data-target-form="vehicleSearchForm" data-query="Mitsubishi">🚗 Mitsubishi</span>
                                        <span class="chip-item chip-blue quick-query-chip" data-target-input="vehicleQueryInput" data-target-form="vehicleSearchForm" data-query="Honda">🚗 Honda</span>
                                    </div>
                                </div>

                                <!-- Collapsible Additional Filters Toggle -->
                                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-light-subtle">
                                    <button class="btn btn-sm btn-link text-decoration-none text-navy fw-semibold p-0 d-flex align-items-center gap-1" type="button" data-bs-toggle="collapse" data-bs-target="#vehicleAdvancedFilter" aria-expanded="false" aria-controls="vehicleAdvancedFilter">
                                        <i class="bi bi-sliders2 text-primary"></i>
                                        <span>Filter Lanjutan (OPD, Jenis, Status)</span>
                                        <i class="bi bi-chevron-down small transition-transform"></i>
                                    </button>
                                    <span class="text-secondary small d-none d-sm-inline">Pencarian data kendaraan dinas Pemerintah Daerah</span>
                                </div>

                                <!-- Collapsible Filter Form -->
                                <div class="collapse mt-3" id="vehicleAdvancedFilter">
                                    <div class="p-3 bg-light rounded-3 border border-light-subtle">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label small text-secondary fw-semibold">OPD / Instansi</label>
                                                <select name="opd" class="form-select form-select-sm shadow-none rounded-2">
                                                    <option value="">-- Semua OPD --</option>
                                                    @foreach($filterOptions['opd_erandis'] ?? [] as $opd)
                                                        <option value="{{ $opd->nama }}">{{ $opd->nama }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small text-secondary fw-semibold">Jenis Kendaraan</label>
                                                <select name="jenis" class="form-select form-select-sm shadow-none rounded-2">
                                                    <option value="">-- Semua Jenis --</option>
                                                    @foreach($filterOptions['vehicle_types'] ?? [] as $type)
                                                        <option value="{{ $type }}">{{ $type }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small text-secondary fw-semibold">Status Kendaraan</label>
                                                <select name="status" class="form-select form-select-sm shadow-none rounded-2">
                                                    <option value="">-- Semua Status --</option>
                                                    @foreach($filterOptions['vehicle_statuses'] ?? [] as $st)
                                                        <option value="{{ $st }}">{{ $st }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- ================= TAB 2: SERTIFIKASI TANAH ================= -->
                        <div class="tab-pane fade" id="tab-land-pane" role="tabpanel" aria-labelledby="tab-land-btn" tabindex="0">
                            <form id="landSearchForm" class="unified-search-form" data-search-endpoint="{{ route('landing.search.land') }}">
                                <div class="row g-2 align-items-stretch">
                                    <!-- Dropdown Cari Berdasarkan -->
                                    <div class="col-md-3 col-lg-3">
                                        <label class="form-label small text-secondary fw-semibold mb-1">Cari Berdasarkan</label>
                                        <select name="search_by" class="form-select form-select-lg fs-6 py-2 shadow-none border-light-subtle rounded-3" id="landSearchBy">
                                            <option value="nibar" selected>NIBAR</option>
                                            <option value="no_sertifikat">Nomor Sertifikat</option>
                                            <option value="nib_nama">NIB / Nama Aset</option>
                                            <option value="all">Semua Kriteria</option>
                                        </select>
                                    </div>

                                    <!-- Input Keyword -->
                                    <div class="col-md-6 col-lg-6">
                                        <label class="form-label small text-secondary fw-semibold mb-1">NIBAR / No Sertifikat / Nama</label>
                                        <div class="position-relative">
                                            <input type="text" name="q" id="landQueryInput" class="form-control form-control-lg fs-6 py-2 ps-3 pe-4 shadow-none border-light-subtle rounded-3" placeholder="Masukkan NIBAR / NIB / Sertifikat...">
                                            <button type="button" class="btn btn-link text-muted position-absolute end-0 top-50 translate-middle-y text-decoration-none clear-input-btn d-none" style="z-index: 5;">
                                                <i class="bi bi-x-circle-fill"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="col-md-3 col-lg-3 d-flex align-items-end">
                                        <button type="submit" class="btn btn-search-land w-100 py-2 fs-6 fw-bold rounded-3 d-flex align-items-center justify-content-center gap-2" style="height: 48px;">
                                            <i class="bi bi-search"></i>
                                            <span>Cari Tanah</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Quick Interactive Suggestion Chips -->
                                <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-2">
                                    <span class="small text-secondary fw-semibold" style="font-size: 0.78rem;">💡 Saran Pencarian:</span>
                                    <div class="quick-search-chips">
                                        <span class="chip-item chip-green quick-query-chip" data-target-input="landQueryInput" data-target-form="landSearchForm" data-query="Banawa">🏞️ Banawa</span>
                                        <span class="chip-item chip-green quick-query-chip" data-target-input="landQueryInput" data-target-form="landSearchForm" data-query="Hak Pakai">🏞️ Hak Pakai</span>
                                        <span class="chip-item chip-green quick-query-chip" data-target-input="landQueryInput" data-target-form="landSearchForm" data-query="Kantor">🏞️ Kantor</span>
                                        <span class="chip-item chip-green quick-query-chip" data-target-input="landQueryInput" data-target-form="landSearchForm" data-query="Sekolah">🏞️ Sekolah</span>
                                    </div>
                                </div>

                                <!-- Collapsible Additional Filters Toggle -->
                                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-light-subtle">
                                    <button class="btn btn-sm btn-link text-decoration-none text-navy fw-semibold p-0 d-flex align-items-center gap-1" type="button" data-bs-toggle="collapse" data-bs-target="#landAdvancedFilter" aria-expanded="false" aria-controls="landAdvancedFilter">
                                        <i class="bi bi-sliders2 text-success"></i>
                                        <span>Filter Lanjutan (OPD, Status Sertifikasi, Wilayah)</span>
                                        <i class="bi bi-chevron-down small transition-transform"></i>
                                    </button>
                                    <span class="text-secondary small d-none d-sm-inline">Pencarian status legalitas & proses sertifikat tanah</span>
                                </div>

                                <!-- Collapsible Filter Form -->
                                <div class="collapse mt-3" id="landAdvancedFilter">
                                    <div class="p-3 bg-light rounded-3 border border-light-subtle">
                                        <div class="row g-3">
                                            <div class="col-md-6 col-lg-3">
                                                <label class="form-label small text-secondary fw-semibold">OPD Pengguna</label>
                                                <select name="opd" class="form-select form-select-sm shadow-none rounded-2">
                                                    <option value="">-- Semua OPD --</option>
                                                    @foreach($filterOptions['opd_sipat'] ?? [] as $opd)
                                                        <option value="{{ $opd->id }}">{{ $opd->nama }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6 col-lg-3">
                                                <label class="form-label small text-secondary fw-semibold">Status Sertifikasi</label>
                                                <select name="status_sertifikasi" class="form-select form-select-sm shadow-none rounded-2">
                                                    <option value="">-- Semua Status --</option>
                                                    <option value="bersertifikat">Sertifikat Terbit</option>
                                                    <option value="proses">Dalam Proses</option>
                                                    <option value="belum_diurus">Belum Terbit</option>
                                                    <option value="kendala">Terkendala / Sengketa</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 col-lg-3">
                                                <label class="form-label small text-secondary fw-semibold">Kecamatan</label>
                                                <input type="text" name="kecamatan" class="form-control form-control-sm shadow-none rounded-2" placeholder="Nama Kecamatan">
                                            </div>
                                            <div class="col-md-6 col-lg-3">
                                                <label class="form-label small text-secondary fw-semibold">Kelurahan / Desa</label>
                                                <input type="text" name="desa" class="form-control form-control-sm shadow-none rounded-2" placeholder="Nama Kelurahan / Desa">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- ================= TAB 3: ARSIP ASET ================= -->
                        <div class="tab-pane fade" id="tab-archive-pane" role="tabpanel" aria-labelledby="tab-archive-btn" tabindex="0">
                            <form id="archiveSearchForm" class="unified-search-form" data-search-endpoint="{{ route('landing.search.archives') }}">
                                <div class="row g-2 align-items-stretch">
                                    <!-- Dropdown Cari Berdasarkan -->
                                    <div class="col-md-3 col-lg-3">
                                        <label class="form-label small text-secondary fw-semibold mb-1">Cari Berdasarkan</label>
                                        <select name="search_by" class="form-select form-select-lg fs-6 py-2 shadow-none border-light-subtle rounded-3" id="archiveSearchBy">
                                            <option value="nibar" selected>NIBAR</option>
                                            <option value="no_dokumen">No Sertifikat / BPKB</option>
                                            <option value="kode_barang">Kode Barang / Plat</option>
                                            <option value="all">Semua Kriteria</option>
                                        </select>
                                    </div>

                                    <!-- Input Keyword -->
                                    <div class="col-md-6 col-lg-6">
                                        <label class="form-label small text-secondary fw-semibold mb-1">NIBAR / No Sertifikat / Plat</label>
                                        <div class="position-relative">
                                            <input type="text" name="q" id="archiveQueryInput" class="form-control form-control-lg fs-6 py-2 ps-3 pe-4 shadow-none border-light-subtle rounded-3" placeholder="Masukkan NIBAR / No Sertifikat...">
                                            <button type="button" class="btn btn-link text-muted position-absolute end-0 top-50 translate-middle-y text-decoration-none clear-input-btn d-none" style="z-index: 5;">
                                                <i class="bi bi-x-circle-fill"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="col-md-3 col-lg-3 d-flex align-items-end">
                                        <button type="submit" class="btn btn-search-archive w-100 py-2 fs-6 fw-bold rounded-3 d-flex align-items-center justify-content-center gap-2" style="height: 48px;">
                                            <i class="bi bi-search"></i>
                                            <span>Cari Arsip</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Quick Interactive Suggestion Chips -->
                                <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-2">
                                    <span class="small text-secondary fw-semibold" style="font-size: 0.78rem;">💡 Saran Pencarian:</span>
                                    <div class="quick-search-chips">
                                        <span class="chip-item chip-amber quick-query-chip" data-target-input="archiveQueryInput" data-target-form="archiveSearchForm" data-query="BPKB">📁 BPKB</span>
                                        <span class="chip-item chip-amber quick-query-chip" data-target-input="archiveQueryInput" data-target-form="archiveSearchForm" data-query="Sertifikat">📁 Sertifikat</span>
                                        <span class="chip-item chip-amber quick-query-chip" data-target-input="archiveQueryInput" data-target-form="archiveSearchForm" data-query="Box">📁 Box Arsip</span>
                                        <span class="chip-item chip-amber quick-query-chip" data-target-input="archiveQueryInput" data-target-form="archiveSearchForm" data-query="Penyerahan">📁 Surat Hibah</span>
                                    </div>
                                </div>

                                <!-- Collapsible Additional Filters Toggle -->
                                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-light-subtle">
                                    <button class="btn btn-sm btn-link text-decoration-none text-navy fw-semibold p-0 d-flex align-items-center gap-1" type="button" data-bs-toggle="collapse" data-bs-target="#archiveAdvancedFilter" aria-expanded="false" aria-controls="archiveAdvancedFilter">
                                        <i class="bi bi-sliders2 text-warning"></i>
                                        <span>Filter Lanjutan (Jenis Dokumen, OPD, Status, Lokasi Box)</span>
                                        <i class="bi bi-chevron-down small transition-transform"></i>
                                    </button>
                                    <span class="text-secondary small d-none d-sm-inline">Pelacakan ketersediaan berkas fisik kepemilikan aset</span>
                                </div>

                                <!-- Collapsible Filter Form -->
                                <div class="collapse mt-3" id="archiveAdvancedFilter">
                                    <div class="p-3 bg-light rounded-3 border border-light-subtle">
                                        <div class="row g-3">
                                            <div class="col-md-6 col-lg-3">
                                                <label class="form-label small text-secondary fw-semibold">Jenis Dokumen</label>
                                                <select name="doc_type" class="form-select form-select-sm shadow-none rounded-2">
                                                    <option value="all">-- Semua Jenis Dokumen --</option>
                                                    <option value="bpkb">BPKB Kendaraan</option>
                                                    <option value="sertifikat">Sertifikat Tanah</option>
                                                    <option value="penyerahan">Surat Penyerahan / Hibah</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 col-lg-3">
                                                <label class="form-label small text-secondary fw-semibold">OPD / Instansi</label>
                                                <select name="opd" class="form-select form-select-sm shadow-none rounded-2">
                                                    <option value="">-- Semua OPD --</option>
                                                    @foreach($filterOptions['opd_sipat'] ?? [] as $opd)
                                                        <option value="{{ $opd->id }}">{{ $opd->nama }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6 col-lg-3">
                                                <label class="form-label small text-secondary fw-semibold">Status Arsip</label>
                                                <select name="status_arsip" class="form-select form-select-sm shadow-none rounded-2">
                                                    <option value="">-- Semua Status --</option>
                                                    <option value="Tersedia">Tersedia di Box Arsip</option>
                                                    <option value="Dipinjam">Sedang Dipinjam</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 col-lg-3">
                                                <label class="form-label small text-secondary fw-semibold">Lokasi Box Penyimpanan</label>
                                                <input type="text" name="box_location" class="form-control form-control-sm shadow-none rounded-2" placeholder="Kode Box / Lokasi Rak">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>

                <!-- Unified Search Dynamic Results Container (Rendered In-Page) -->
                @include('landing.components.search-results')

            </div>
        </div>
    </div>
</section>
