@extends('layouts.app')

@section('content')
<style>
    .gis-card {
        border: 1px solid var(--border-color, rgba(0, 0, 0, 0.08));
        border-radius: 1.25rem;
        background: var(--bs-card-bg, #ffffff);
        box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        position: relative;
    }
    #gisMap {
        height: 75vh;
        min-height: 560px;
        width: 100%;
        border-radius: 1rem;
        z-index: 1;
    }
    .gis-legend {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        padding: 12px 16px;
        border-radius: 14px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        font-size: 12px;
        line-height: 1.6;
        border: 1px solid rgba(0,0,0,0.08);
        max-width: 250px;
    }
    .legend-color-box {
        width: 14px;
        height: 14px;
        display: inline-block;
        border-radius: 3px;
        margin-right: 6px;
        vertical-align: middle;
    }
    .search-result-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        max-height: 280px;
        overflow-y: auto;
        z-index: 1050;
        background: #ffffff;
        border-radius: 0.75rem;
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        border: 1px solid #e2e8f0;
    }
    .search-result-item {
        padding: 9px 14px;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s;
    }
    .search-result-item:hover {
        background: #f8fafc;
    }
    
    /* Slide-over Asset Detail Drawer */
    .gis-drawer {
        position: absolute;
        top: 12px;
        right: 12px;
        bottom: 12px;
        width: 380px;
        max-width: calc(100% - 24px);
        background: rgba(255, 255, 255, 0.97);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: 18px;
        box-shadow: -10px 15px 35px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        display: flex;
        flex-direction: column;
        transform: translateX(420px);
        transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.2s ease;
        overflow: hidden;
        pointer-events: none;
        opacity: 0;
        visibility: hidden;
    }
    .gis-drawer.active {
        transform: translateX(0);
        pointer-events: auto;
        opacity: 1;
        visibility: visible;
    }
    .gis-drawer-header {
        padding: 16px 20px;
        border-bottom: 1px solid #f1f5f9;
        background: var(--bs-tertiary-bg, #f8fafc);
    }
    .gis-drawer-body {
        padding: 18px 20px;
        overflow-y: auto;
        flex-grow: 1;
    }
    .gis-drawer-footer {
        padding: 14px 20px;
        border-top: 1px solid #f1f5f9;
        background: #fafafa;
    }

    /* Floating Stats Bar */
    .gis-floating-stats {
        position: absolute;
        bottom: 24px;
        left: 24px;
        z-index: 500;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        pointer-events: none;
    }
    .floating-stat-pill {
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 30px;
        padding: 6px 14px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        font-size: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
        pointer-events: auto;
    }

    /* Quick Tools Toolbar */
    .map-quick-tools {
        position: absolute;
        top: 20px;
        left: 60px;
        z-index: 500;
        display: flex;
        gap: 6px;
        pointer-events: auto;
    }
    .btn-map-tool {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 600;
        color: #334155;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: all 0.2s ease;
    }
    .btn-map-tool:hover, .btn-map-tool.active {
        background: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
    }

    /* Loading Overlay */
    #mapLoadingOverlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        background: rgba(255, 255, 255, 0.85);
        z-index: 1050;
        border-radius: 1rem;
        transition: opacity 0.25s ease, visibility 0.25s ease;
        pointer-events: auto;
    }
    #mapLoadingOverlay.hidden {
        opacity: 0 !important;
        visibility: hidden !important;
        pointer-events: none !important;
    }

    @media (max-width: 767.98px) {
        #gisMap {
            height: calc(100vh - var(--admin-header-height, 64px) - var(--mobile-nav-height, 64px) - 140px) !important;
            min-height: 420px;
        }
        .map-quick-tools {
            display: none;
        }
        .gis-floating-stats {
            display: none;
        }
    }
</style>

<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary fw-semibold px-2.5 py-1 rounded-pill" style="font-size: 0.75rem;">
                    <i class="bi bi-geo-alt-fill me-1"></i> GIS & SPASIAL SIPAT
                </span>
                <span class="text-secondary small">&bull;</span>
                <span class="text-secondary small">Wilayah Kabupaten Donggala (16 Kecamatan)</span>
            </div>
            <h2 class="fw-bold mb-1">Peta Sebaran & Batas Bidang Tanah</h2>
            <p class="text-secondary mb-0 small">Visualisasi batas spasial (Poligon/SHP), batas administrasi kecamatan, dan status sertifikat BPN</p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button" class="btn btn-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalImportGis">
                <i class="bi bi-file-earmark-arrow-up me-1"></i> Impor SHP / GeoJSON
            </button>
            <a href="{{ route('sipat.peta.export-geojson') }}" class="btn btn-outline-success rounded-pill px-3">
                <i class="bi bi-download me-1"></i> Ekspor GeoJSON
            </a>
            <button type="button" class="btn btn-outline-secondary rounded-pill px-3" id="btnToggleFullscreen" title="Layar Penuh">
                <i class="bi bi-arrows-fullscreen me-1"></i> Fullscreen
            </button>
        </div>
    </div>

    <!-- Filter & Search Toolbar Bar -->
    <div class="card gis-card mb-3">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <!-- Search Live -->
                <div class="col-lg-4 col-md-6 position-relative">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" id="gisSearchInput" class="form-control form-control-sm border-start-0" placeholder="Cari NIBAR / Nama Aset / Kecamatan..." autocomplete="off">
                        <button class="btn btn-outline-secondary" type="button" id="btnClearSearch" style="display: none;">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <div id="searchResultsDropdown" class="search-result-dropdown" style="display: none;"></div>
                </div>

                <!-- Filter OPD -->
                <div class="col-lg-3 col-md-3 col-sm-6">
                    <select id="filterOpdSelect" class="form-select form-select-sm">
                        <option value="">-- Semua OPD Pengelola --</option>
                        @foreach($opdList as $opd)
                            <option value="{{ $opd->id }}">{{ $opd->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Status BPN -->
                <div class="col-lg-3 col-md-3 col-sm-6">
                    <select id="filterStatusSelect" class="form-select form-select-sm">
                        <option value="">-- Semua Status BPN --</option>
                        @foreach($statusList as $st)
                            <option value="{{ $st->id_status }}">{{ $st->nama_status }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Reset & Layer Toggles -->
                <div class="col-lg-2 col-md-12 d-flex justify-content-lg-end justify-content-between align-items-center gap-2">
                    <button type="button" id="btnResetFilter" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5" title="Reset Semua Filter">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                    <span class="badge bg-light text-dark border px-2.5 py-1.5 rounded-pill small">
                        <span id="renderedCount" class="fw-bold text-primary">{{ $totalTotal }}</span> Bidang
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Canvas Card -->
    <div class="card gis-card p-2" id="mapCardContainer">
        <!-- Quick Tools Toolbar (Top Left) -->
        <div class="map-quick-tools">
            <button type="button" class="btn-map-tool active" id="toggleKecamatanBtn" title="Tampilkan/Sembunyikan Batas Kecamatan">
                <i class="bi bi-grid-1x2-fill me-1"></i> Batas 16 Kecamatan
            </button>
            <button type="button" class="btn-map-tool" id="toggleLabelsBtn" title="Tampilkan Label Nama Aset">
                <i class="bi bi-tag-fill me-1"></i> Label Aset
            </button>
            <button type="button" class="btn-map-tool" id="btnMeasureDistance" title="Ukur Jarak Batas (Penggaris)">
                <i class="bi bi-rulers me-1"></i> Ukur Jarak
            </button>
            <button type="button" class="btn-map-tool" id="btnMeasureArea" title="Ukur Luas Area">
                <i class="bi bi-bounding-box me-1"></i> Ukur Luas
            </button>
        </div>

        <div id="gisMap"></div>

        <!-- Loading Overlay -->
        <div id="mapLoadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center bg-white bg-opacity-75" style="z-index: 1050; border-radius: 1rem; transition: opacity 0.3s ease;">
            <div class="spinner-border text-primary mb-2" role="status">
                <span class="visually-hidden">Memuat...</span>
            </div>
            <div class="small fw-semibold text-secondary">Memuat Bidang Tanah & Poligon GIS...</div>
        </div>

        <!-- Floating Stats Pill (Bottom Left) -->
        <div class="gis-floating-stats">
            <div class="floating-stat-pill text-dark">
                <i class="bi bi-buildings text-primary"></i>
                <span>Total: <strong id="statTotalBidang">{{ $totalTotal }}</strong> Bidang</span>
            </div>
            <div class="floating-stat-pill text-success">
                <i class="bi bi-bounding-box-circles"></i>
                <span>Poligon: <strong id="statPoligon">{{ $totalPoligon }}</strong> Bidang</span>
            </div>
            <div class="floating-stat-pill text-secondary">
                <i class="bi bi-geo-alt"></i>
                <span>Titik: <strong id="statMarker">{{ $totalMarker }}</strong> Bidang</span>
            </div>
        </div>

        <!-- Mobile Floating Buttons -->
        <div class="mobile-map-fab-group">
            <button type="button" class="mobile-map-fab" id="btnFitBounds" title="Reset Tampilan Peta">
                <i class="bi bi-arrows-fullscreen"></i>
            </button>
            <button type="button" class="mobile-map-fab text-primary" id="btnLocateMe" title="Lokasi Saya">
                <i class="bi bi-geo-alt-fill"></i>
            </button>
        </div>

        <!-- Slide-over Drawer Detail Aset -->
        <div class="gis-drawer" id="assetDetailDrawer">
            <div class="gis-drawer-header d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge bg-primary font-monospace fw-bold" id="drawerNibarBadge">-</span>
                    <span class="badge ms-1" id="drawerStatusBadge" style="background:#10b981; color:#fff;">-</span>
                </div>
                <button type="button" class="btn-close" id="btnCloseDrawer" aria-label="Close"></button>
            </div>
            <div class="gis-drawer-body">
                <h5 class="fw-bold text-dark mb-1" id="drawerAsetNama" style="line-height: 1.3;">-</h5>
                <p class="text-secondary small mb-3" id="drawerAsetPeruntukan">-</p>

                <div class="card bg-light border-0 rounded-3 p-3 mb-3">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="small text-secondary fw-semibold">LUAS DOKUMEN</div>
                            <div class="fs-6 fw-bold text-dark font-monospace" id="drawerLuasDokumen">- m²</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-secondary fw-semibold">LUAS SPASIAL</div>
                            <div class="fs-6 fw-bold text-primary font-monospace" id="drawerLuasSpasial">- m²</div>
                        </div>
                    </div>
                </div>

                <div class="mb-2">
                    <div class="small text-secondary fw-bold text-uppercase">OPD Pengelola</div>
                    <div class="text-body fw-semibold" id="drawerOpdNama">-</div>
                </div>

                <div class="mb-2">
                    <div class="small text-secondary fw-bold text-uppercase">Alamat / Lokasi</div>
                    <div class="text-body small" id="drawerAlamat">-</div>
                </div>

                <div class="mb-3">
                    <div class="small text-secondary fw-bold text-uppercase">Koordinat Centroid</div>
                    <div class="font-monospace text-secondary small" id="drawerKoordinat">-</div>
                </div>

                <div id="drawerKecamatanInfo" class="alert alert-primary-subtle p-2.5 rounded-3 mb-0 small d-flex align-items-center gap-2">
                    <i class="bi bi-geo-fill text-primary fs-5"></i>
                    <div>
                        <div class="fw-bold text-primary" id="drawerKecamatanNama">Wilayah Donggala</div>
                        <div class="text-secondary" style="font-size: 11px;">Kabupaten Donggala, Sulawesi Tengah</div>
                    </div>
                </div>
            </div>
            <div class="gis-drawer-footer d-flex gap-2">
                <a href="#" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill flex-fill" id="btnDrawerGoogleMaps">
                    <i class="bi bi-compass me-1"></i> Rute Maps
                </a>
                <a href="#" class="btn btn-sm btn-primary rounded-pill flex-fill" id="btnDrawerEditAset">
                    <i class="bi bi-pencil me-1"></i> Edit Aset
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal Impor SHP / GeoJSON Massal -->
<div class="modal fade" id="modalImportGis" tabindex="-1" aria-labelledby="modalImportGisLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-primary text-white p-3">
                <h5 class="modal-title fw-bold" id="modalImportGisLabel">
                    <i class="bi bi-file-earmark-arrow-up me-1"></i> Impor Poligon GIS (SHP / GeoJSON) Berdasarkan NIBAR
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info border-0 rounded-3 mb-3 small">
                    <i class="bi bi-info-circle-fill me-1"></i> <strong>Petunjuk Impor:</strong>
                    Unggah file <strong>Shapefile (.zip)</strong> dari BPN/ArcGIS atau file <strong>GeoJSON (.geojson/.json)</strong>. Sistem akan otomatis mencocokkan poligon dengan aset tanah di database berdasarkan nomor <strong>NIBAR (Kode Aset)</strong> pada atribut data spasial.
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-body">Pilih File SHP (.zip) atau GeoJSON (.geojson/.json) <span class="text-danger">*</span></label>
                    <input type="file" id="batchGisFileInput" accept=".zip,.geojson,.json" class="form-control">
                </div>

                <!-- Field Mapper -->
                <div id="fieldMatcherContainer" class="mb-3" style="display: none;">
                    <label class="form-label small fw-bold text-body">Pilih Atribut yang Berisi Nomor NIBAR / Kode Aset:</label>
                    <select id="nibarFieldSelector" class="form-select form-select-sm"></select>
                </div>

                <!-- Preview Table Pencocokan -->
                <div id="importPreviewContainer" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-bold text-dark">Hasil Pencocokan Data:</span>
                        <span class="badge bg-success" id="matchedCountBadge">0 Cocok</span>
                    </div>
                    <div class="table-responsive border rounded-3" style="max-height: 260px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0" id="previewMatchingTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 40px;">No</th>
                                    <th>NIBAR (Dari File)</th>
                                    <th>Status Cocok di DB</th>
                                    <th>Nama Aset di SIPAT</th>
                                    <th>Luas Spasial</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light p-3">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" id="btnSaveBatchGis" disabled>
                    <i class="bi bi-save me-1"></i> Simpan Poligon ke Database
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Local Leaflet, Turf.js & SHP.js Scripts -->
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script src="{{ asset('vendor/shpjs/shp.js') }}"></script>
<script src="{{ asset('vendor/turf/turf.min.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof L === 'undefined') {
        console.error('Leaflet gagal dimuat.');
        return;
    }

    let rawFeatures = [];
    const allDbAsets = {!! json_encode($allAsetNibar) !!} || [];

    const sipatEscape = (str) => String(str ?? '').replace(/[&<>'"]/g, match => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
    }[match]));

    // Batas Wilayah Kabupaten Donggala & Pusat Banawa
    const donggalaBounds = L.latLngBounds(
        L.latLng(-1.50, 119.30), // Southwest
        L.latLng(0.90, 120.50)   // Northeast
    );
    const donggalaCenter = [-0.685, 119.745]; // Pusat Ibukota Donggala (Banawa)

    // Inisialisasi Peta - Terfokus Khusus Kabupaten Donggala
    const map = L.map('gisMap', {
        zoomControl: true,
        attributionControl: true,
        minZoom: 8,
        maxZoom: 20,
        maxBounds: donggalaBounds,
        maxBoundsViscosity: 0.8
    }).setView(donggalaCenter, 11);

    setTimeout(() => map.invalidateSize(), 200);

    // Basemaps Tile Layers
    const googleSatellite = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        attribution: '&copy; Google Hybrid Satellite'
    });

    const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    });

    const esriTopo = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19,
        attribution: '&copy; Esri Topographic'
    });

    const cartoDark = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        maxZoom: 19,
        attribution: '&copy; CartoDB Dark Matter'
    });

    googleSatellite.addTo(map);

    L.control.layers({
        'Citra Satelit (Google Hybrid)': googleSatellite,
        'Peta Jalan (OpenStreetMap)': osmLayer,
        'Peta Topografi Relief (Esri)': esriTopo,
        'Mode Gelap (CartoDB Dark)': cartoDark
    }, null, { position: 'topright' }).addTo(map);

    // Custom Panes agar urutan z-index layer Leaflet teratur
    map.createPane('kecamatanPane');
    map.getPane('kecamatanPane').style.zIndex = 350;

    map.createPane('assetPolygonPane');
    map.getPane('assetPolygonPane').style.zIndex = 450;

    // Layer Group untuk Kecamatan & Bidang Aset
    const kecamatanLayerGroup = L.featureGroup().addTo(map);
    const activeLayerGroup = L.featureGroup().addTo(map);
    const measurementLayerGroup = L.featureGroup().addTo(map);

    // Muat Batas 16 Kecamatan Kabupaten Donggala
    let isKecamatanVisible = true;
    fetch("{{ asset('geojson/donggala_kecamatan.geojson') }}")
        .then(res => res.json())
        .then(kecData => {
            L.geoJSON(kecData, {
                pane: 'kecamatanPane',
                style: function (feature) {
                    return {
                        color: feature.properties.warna || '#2563eb',
                        weight: 2.5,
                        fillColor: feature.properties.fill_warna || '#93c5fd',
                        fillOpacity: 0.50
                    };
                },
                onEachFeature: function (feature, layer) {
                    const p = feature.properties;
                    layer.bindTooltip(`<b>${p.nama}</b><br><small class="text-secondary">${p.kabupaten || 'Kab. Donggala'}</small>`, {
                        sticky: true,
                        direction: 'center',
                        className: 'bg-white text-dark shadow-sm border px-2 py-1 rounded fw-semibold'
                    });

                    layer.on('mouseover', function () {
                        this.setStyle({ fillOpacity: 0.75, weight: 4 });
                    });
                    layer.on('mouseout', function () {
                        this.setStyle({ fillOpacity: 0.50, weight: 2.5 });
                    });
                }
            }).addTo(kecamatanLayerGroup);
        })
        .catch(err => console.warn('Gagal memuat batas kecamatan:', err));

    // Toggle Batas Kecamatan
    const toggleKecamatanBtn = document.getElementById('toggleKecamatanBtn');
    toggleKecamatanBtn.addEventListener('click', function () {
        isKecamatanVisible = !isKecamatanVisible;
        if (isKecamatanVisible) {
            map.addLayer(kecamatanLayerGroup);
            this.classList.add('active');
        } else {
            map.removeLayer(kecamatanLayerGroup);
            this.classList.remove('active');
        }
    });

    // Legend Control
    const legend = L.control({ position: 'bottomleft' });
    legend.onAdd = function () {
        const div = L.DomUtil.create('div', 'gis-legend');
        div.innerHTML = `
            <div class="fw-bold text-dark mb-1.5"><i class="bi bi-layers-fill me-1 text-primary"></i> Status Pensertifikatan</div>
            <div><span class="legend-color-box" style="background:#10b981;"></span> Bersertifikat Terbit</div>
            <div><span class="legend-color-box" style="background:#f59e0b;"></span> Pengukuran / PBT BPN</div>
            <div><span class="legend-color-box" style="background:#3b82f6;"></span> Permohonan Hak / Proses</div>
            <div><span class="legend-color-box" style="background:#64748b;"></span> Belum Diurus / Draft</div>
        `;
        return div;
    };
    legend.addTo(map);

    // Helper Penentu Warna
    function getStatusColor(item) {
        const cat = (item.kategori || '').toLowerCase();
        const norm = (item.status_nama || '').toLowerCase();

        if (cat === 'bersertifikat' || norm.includes('terbit') || norm.includes('selesai')) {
            return { color: '#059669', fill: '#10b981' };
        }
        if (norm.includes('ukur') || norm.includes('pbt') || norm.includes('peta bidang')) {
            return { color: '#d97706', fill: '#f59e0b' };
        }
        if (norm.includes('permohonan') || norm.includes('proses') || norm.includes('berkas')) {
            return { color: '#2563eb', fill: '#3b82f6' };
        }
        return { color: '#475569', fill: '#64748b' };
    }

    // Drawer Elements
    const drawer = document.getElementById('assetDetailDrawer');
    const drawerNibarBadge = document.getElementById('drawerNibarBadge');
    const drawerStatusBadge = document.getElementById('drawerStatusBadge');
    const drawerAsetNama = document.getElementById('drawerAsetNama');
    const drawerAsetPeruntukan = document.getElementById('drawerAsetPeruntukan');
    const drawerLuasDokumen = document.getElementById('drawerLuasDokumen');
    const drawerLuasSpasial = document.getElementById('drawerLuasSpasial');
    const drawerOpdNama = document.getElementById('drawerOpdNama');
    const drawerAlamat = document.getElementById('drawerAlamat');
    const drawerKoordinat = document.getElementById('drawerKoordinat');
    const btnDrawerGoogleMaps = document.getElementById('btnDrawerGoogleMaps');
    const btnDrawerEditAset = document.getElementById('btnDrawerEditAset');
    const btnCloseDrawer = document.getElementById('btnCloseDrawer');

    btnCloseDrawer.addEventListener('click', () => drawer.classList.remove('active'));

    function openAssetDrawer(item, spatialAreaStr) {
        const colors = getStatusColor(item);
        drawerNibarBadge.textContent = item.kode;
        drawerStatusBadge.textContent = item.status_nama;
        drawerStatusBadge.style.background = colors.fill;

        drawerAsetNama.textContent = item.nama;
        drawerAsetPeruntukan.textContent = item.peruntukan || 'Peruntukan belum ditentukan';
        drawerLuasDokumen.textContent = Number(item.luas || 0).toLocaleString('id-ID') + ' m²';
        drawerLuasSpasial.textContent = spatialAreaStr || '- m²';
        drawerOpdNama.textContent = item.opd_nama;
        drawerAlamat.textContent = item.alamat || 'Alamat fisik belum diisi';

        const latStr = item.lat ? item.lat.toFixed(6) : '-';
        const lngStr = item.lng ? item.lng.toFixed(6) : '-';
        drawerKoordinat.textContent = `${latStr}, ${lngStr}`;

        if (item.lat && item.lng) {
            btnDrawerGoogleMaps.href = `https://www.google.com/maps/search/?api=1&query=${item.lat},${item.lng}`;
            btnDrawerGoogleMaps.classList.remove('disabled');
        } else {
            btnDrawerGoogleMaps.href = '#';
            btnDrawerGoogleMaps.classList.add('disabled');
        }

        btnDrawerEditAset.href = `{!! url('sipat/aset') !!}/${item.id}/edit`;
        drawer.classList.add('active');
    }

    // Lookup Layer by Aset ID
    const layerById = new Map();
    let isLabelsPermanent = false;

    // Render Data Aset Tanah ke Peta
    function renderMapFeatures(filterOpd = '', filterStatus = '') {
        activeLayerGroup.clearLayers();
        layerById.clear();

        let count = 0;
        const bounds = [];

        rawFeatures.forEach(item => {
            if (filterOpd && String(item.opd_id) !== String(filterOpd)) return;
            if (filterStatus && String(item.status_id) !== String(filterStatus)) return;

            const colors = getStatusColor(item);

            let spatialArea = null;
            let spatialAreaStr = '-';
            if (item.has_polygon && typeof turf !== 'undefined' && item.geojson_data) {
                try {
                    spatialArea = turf.area(item.geojson_data);
                    spatialAreaStr = Number(spatialArea.toFixed(1)).toLocaleString('id-ID') + ' m²';
                } catch (e) {}
            }

            if (item.has_polygon && item.geojson_data) {
                // Render Poligon Aset Tanah
                try {
                    const polyLayer = L.geoJSON(item.geojson_data, {
                        pane: 'assetPolygonPane',
                        style: {
                            color: colors.color,
                            weight: 2.5,
                            fillColor: colors.fill,
                            fillOpacity: 0.6
                        }
                    });

                    polyLayer.bindTooltip(`<b>${sipatEscape(item.kode)}</b> - ${sipatEscape(item.nama)}`, {
                        sticky: true,
                        permanent: isLabelsPermanent
                    });

                    polyLayer.on('mouseover', function () {
                        this.setStyle({ weight: 4.5, fillOpacity: 0.85 });
                    });
                    polyLayer.on('mouseout', function () {
                        this.setStyle({ weight: 2.5, fillOpacity: 0.6 });
                    });

                    polyLayer.on('click', function () {
                        openAssetDrawer(item, spatialAreaStr);
                    });

                    activeLayerGroup.addLayer(polyLayer);
                    layerById.set(item.id, polyLayer);
                    bounds.push(polyLayer.getBounds());
                    count++;
                } catch (e) {
                    console.warn('Gagal render poligon ID ' + item.id, e);
                }
            } else if (item.lat !== null && item.lng !== null && !Number.isNaN(item.lat) && !Number.isNaN(item.lng)) {
                // Render Marker Titik
                const marker = L.circleMarker([item.lat, item.lng], {
                    radius: 7.5,
                    fillColor: colors.fill,
                    color: '#ffffff',
                    weight: 2.5,
                    opacity: 1,
                    fillOpacity: 0.95
                });

                marker.bindTooltip(`<b>${sipatEscape(item.kode)}</b> - ${sipatEscape(item.nama)}`, {
                    sticky: true,
                    permanent: isLabelsPermanent
                });

                marker.on('click', function () {
                    openAssetDrawer(item, '-');
                });

                activeLayerGroup.addLayer(marker);
                layerById.set(item.id, marker);
                bounds.push([item.lat, item.lng]);
                count++;
            }
        });

        document.getElementById('renderedCount').textContent = count;

        if (bounds.length > 0) {
            try {
                map.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });
            } catch (err) {}
        } else {
            map.setView(donggalaCenter, 11);
        }
    }

    // Memuat data spasial secara asinkron dari endpoint /sipat/peta/data
    async function loadSpatialData() {
        const loadingOverlay = document.getElementById('mapLoadingOverlay');
        try {
            const resp = await fetch("{{ route('sipat.peta.data') }}");
            const res = await resp.json();
            if (res.success && Array.isArray(res.features)) {
                rawFeatures = res.features;
                if (res.summary) {
                    const elTotal = document.getElementById('statTotalBidang');
                    const elPoly = document.getElementById('statPoligon');
                    const elMark = document.getElementById('statMarker');
                    if (elTotal) elTotal.textContent = res.summary.total;
                    if (elPoly) elPoly.textContent = res.summary.poligon;
                    if (elMark) elMark.textContent = res.summary.marker;
                }
                renderMapFeatures();
            }
        } catch (err) {
            console.error('Gagal memuat data spasial peta:', err);
        } finally {
            if (loadingOverlay) {
                loadingOverlay.classList.add('hidden');
                loadingOverlay.style.pointerEvents = 'none';
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                }, 300);
            }
        }
    }

    loadSpatialData();

    // Toggle Label Permanen
    const toggleLabelsBtn = document.getElementById('toggleLabelsBtn');
    toggleLabelsBtn.addEventListener('click', function () {
        isLabelsPermanent = !isLabelsPermanent;
        this.classList.toggle('active', isLabelsPermanent);
        renderMapFeatures(opdSelect.value, statusSelect.value);
    });

    // Filter Listeners
    const opdSelect = document.getElementById('filterOpdSelect');
    const statusSelect = document.getElementById('filterStatusSelect');
    const btnReset = document.getElementById('btnResetFilter');

    function applyFilter() {
        renderMapFeatures(opdSelect.value, statusSelect.value);
    }

    opdSelect.addEventListener('change', applyFilter);
    statusSelect.addEventListener('change', applyFilter);

    btnReset.addEventListener('click', function () {
        opdSelect.value = '';
        statusSelect.value = '';
        searchInput.value = '';
        btnSearchClear.style.display = 'none';
        searchResultsDropdown.style.display = 'none';
        drawer.classList.remove('active');
        renderMapFeatures();
    });

    // Search Autocomplete & Fly-To
    const searchInput = document.getElementById('gisSearchInput');
    const btnSearchClear = document.getElementById('btnClearSearch');
    const searchResultsDropdown = document.getElementById('searchResultsDropdown');

    searchInput.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        if (!q) {
            btnSearchClear.style.display = 'none';
            searchResultsDropdown.style.display = 'none';
            return;
        }

        btnSearchClear.style.display = 'block';

        const matched = rawFeatures.filter(f => 
            f.kode.toLowerCase().includes(q) || f.nama.toLowerCase().includes(q) || (f.opd_nama && f.opd_nama.toLowerCase().includes(q))
        ).slice(0, 8);

        if (matched.length === 0) {
            searchResultsDropdown.innerHTML = `<div class="p-2.5 text-center text-secondary small">Tidak ada aset tanah yang cocok.</div>`;
            searchResultsDropdown.style.display = 'block';
            return;
        }

        searchResultsDropdown.innerHTML = matched.map(m => `
            <div class="search-result-item" data-id="${m.id}">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="font-monospace fw-bold text-primary small">${sipatEscape(m.kode)}</span>
                    <span class="badge bg-light text-dark border" style="font-size:0.65rem;">${m.has_polygon ? 'Poligon' : 'Marker'}</span>
                </div>
                <div class="fw-semibold text-dark small">${sipatEscape(m.nama)}</div>
                <div class="text-secondary" style="font-size:0.7rem;">${sipatEscape(m.opd_nama)} &bull; ${sipatEscape(m.status_nama)}</div>
            </div>
        `).join('');
        searchResultsDropdown.style.display = 'block';
    });

    searchResultsDropdown.addEventListener('click', function (e) {
        const itemEl = e.target.closest('.search-result-item');
        if (!itemEl) return;

        const id = parseInt(itemEl.getAttribute('data-id'));
        searchResultsDropdown.style.display = 'none';

        const itemData = rawFeatures.find(f => f.id === id);
        const layer = layerById.get(id);

        if (layer) {
            if (layer.getBounds) {
                map.fitBounds(layer.getBounds(), { padding: [60, 60], maxZoom: 18 });
            } else if (layer.getLatLng) {
                map.setView(layer.getLatLng(), 18);
            }
            if (itemData) {
                let spatialStr = '-';
                if (itemData.has_polygon && typeof turf !== 'undefined' && itemData.geojson_data) {
                    try {
                        const a = turf.area(itemData.geojson_data);
                        spatialStr = Number(a.toFixed(1)).toLocaleString('id-ID') + ' m²';
                    } catch (e) {}
                }
                openAssetDrawer(itemData, spatialStr);
            }
        } else {
            alert('Aset ini belum memiliki koordinat atau poligon spasial.');
        }
    });

    btnSearchClear.addEventListener('click', function () {
        searchInput.value = '';
        btnSearchClear.style.display = 'none';
        searchResultsDropdown.style.display = 'none';
    });

    // Close search dropdown on click outside
    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !searchResultsDropdown.contains(e.target)) {
            searchResultsDropdown.style.display = 'none';
        }
    });

    // Fullscreen Mode Toggle
    const btnFullscreen = document.getElementById('btnToggleFullscreen');
    const mapCardContainer = document.getElementById('mapCardContainer');
    btnFullscreen?.addEventListener('click', function () {
        if (!document.fullscreenElement) {
            mapCardContainer.requestFullscreen().catch(err => console.warn(err));
        } else {
            document.exitFullscreen();
        }
    });

    // Mobile FABs
    document.getElementById('btnFitBounds')?.addEventListener('click', () => {
        if (activeLayerGroup.getLayers().length > 0) {
            map.fitBounds(activeLayerGroup.getBounds(), { padding: [40, 40], maxZoom: 16 });
        } else {
            map.setView(donggalaCenter, 11);
        }
    });

    document.getElementById('btnLocateMe')?.addEventListener('click', () => {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(pos => {
                map.setView([pos.coords.latitude, pos.coords.longitude], 16);
                L.marker([pos.coords.latitude, pos.coords.longitude]).addTo(map)
                    .bindPopup('<b>Posisi Anda Saat Ini</b>').openPopup();
            }, () => {
                if (typeof Swal !== 'undefined') Swal.fire('Lokasi', 'Gagal mengakses GPS perangkat.', 'info');
            });
        }
    });

    // ==========================================
    // SPATIAL MEASUREMENT TOOLS (Turf.js)
    // ==========================================
    let isMeasuringDistance = false;
    let isMeasuringArea = false;
    let measurePoints = [];
    const btnMeasureDistance = document.getElementById('btnMeasureDistance');
    const btnMeasureArea = document.getElementById('btnMeasureArea');

    function resetMeasurement() {
        isMeasuringDistance = false;
        isMeasuringArea = false;
        measurePoints = [];
        measurementLayerGroup.clearLayers();
        btnMeasureDistance.classList.remove('active');
        btnMeasureArea.classList.remove('active');
    }

    btnMeasureDistance.addEventListener('click', function () {
        if (isMeasuringDistance) {
            resetMeasurement();
        } else {
            resetMeasurement();
            isMeasuringDistance = true;
            this.classList.add('active');
            alert('Mode Ukur Jarak: Klik titik-titik di peta untuk mengukur panjang garis.');
        }
    });

    btnMeasureArea.addEventListener('click', function () {
        if (isMeasuringArea) {
            resetMeasurement();
        } else {
            resetMeasurement();
            isMeasuringArea = true;
            this.classList.add('active');
            alert('Mode Ukur Luas: Klik minimal 3 titik di peta untuk mengukur luas area.');
        }
    });

    map.on('click', function (e) {
        if (!isMeasuringDistance && !isMeasuringArea) return;

        measurePoints.push([e.latlng.lng, e.latlng.lat]);
        L.circleMarker(e.latlng, { radius: 5, color: '#e11d48', fillColor: '#f43f5e', fillOpacity: 1 }).addTo(measurementLayerGroup);

        if (isMeasuringDistance && measurePoints.length >= 2) {
            const line = turf.lineString(measurePoints);
            const lengthKm = turf.length(line, { units: 'kilometers' });
            const lengthM = (lengthKm * 1000).toFixed(1);

            L.polyline(measurePoints.map(p => [p[1], p[0]]), { color: '#e11d48', weight: 3, dashArray: '5, 5' }).addTo(measurementLayerGroup);
            L.popup().setLatLng(e.latlng).setContent(`<b>Panjang Jarak:</b> ${lengthM} meter (${lengthKm.toFixed(3)} km)`).openOn(map);
        } else if (isMeasuringArea && measurePoints.length >= 3) {
            const polyCoords = [...measurePoints, measurePoints[0]];
            const poly = turf.polygon([polyCoords]);
            const areaM2 = turf.area(poly);
            const areaHa = (areaM2 / 10000).toFixed(2);

            L.polygon(polyCoords.map(p => [p[1], p[0]]), { color: '#e11d48', fillColor: '#f43f5e', fillOpacity: 0.35 }).addTo(measurementLayerGroup);
            L.popup().setLatLng(e.latlng).setContent(`<b>Luas Wilayah:</b> ${Number(areaM2.toFixed(1)).toLocaleString('id-ID')} m² (${areaHa} Ha)`).openOn(map);
        }
    });

    // ==========================================
    // BATCH / BULK SHP & GeoJSON IMPORTER LOGIC
    // ==========================================
    const batchFileInput = document.getElementById('batchGisFileInput');
    const fieldMatcherContainer = document.getElementById('fieldMatcherContainer');
    const nibarFieldSelector = document.getElementById('nibarFieldSelector');
    const importPreviewContainer = document.getElementById('importPreviewContainer');
    const previewTableBody = document.querySelector('#previewMatchingTable tbody');
    const matchedCountBadge = document.getElementById('matchedCountBadge');
    const btnSaveBatchGis = document.getElementById('btnSaveBatchGis');

    let parsedFeaturesList = [];
    let payloadToSave = [];

    batchFileInput.addEventListener('change', async function (e) {
        const file = e.target.files[0];
        if (!file) return;

        fieldMatcherContainer.style.display = 'none';
        importPreviewContainer.style.display = 'none';
        btnSaveBatchGis.disabled = true;
        parsedFeaturesList = [];
        payloadToSave = [];

        try {
            let geojson = null;
            if (file.name.toLowerCase().endsWith('.zip')) {
                const arrayBuffer = await file.arrayBuffer();
                geojson = await shp(arrayBuffer);
            } else {
                const text = await file.text();
                geojson = JSON.parse(text);
            }

            if (Array.isArray(geojson)) {
                geojson = geojson[0];
            }

            let features = [];
            if (geojson.type === 'FeatureCollection' && geojson.features) {
                features = geojson.features;
            } else if (geojson.type === 'Feature') {
                features = [geojson];
            } else {
                throw new Error('Struktur GeoJSON / SHP tidak valid.');
            }

            parsedFeaturesList = features;

            if (features.length > 0 && features[0].properties) {
                const props = Object.keys(features[0].properties);
                nibarFieldSelector.innerHTML = props.map(p => {
                    const isDefault = ['nibar', 'kode_aset', 'kode', 'niba', 'id_aset', 'no_register', 'nib'].includes(p.toLowerCase());
                    return `<option value="${p}" ${isDefault ? 'selected' : ''}>${p}</option>`;
                }).join('');
                fieldMatcherContainer.style.display = 'block';
            }

            processFeatureMatching();

        } catch (err) {
            console.error(err);
            alert('Gagal membaca file: ' + err.message);
        }
    });

    nibarFieldSelector.addEventListener('change', processFeatureMatching);

    function processFeatureMatching() {
        if (parsedFeaturesList.length === 0) return;

        const selectedField = nibarFieldSelector.value || 'nibar';
        previewTableBody.innerHTML = '';
        payloadToSave = [];

        const dbMap = new Map();
        allDbAsets.forEach(a => {
            if (a.kode_aset) {
                dbMap.set(a.kode_aset.trim().toLowerCase(), a);
                dbMap.set(a.kode_aset.trim().toLowerCase().replace(/^0+/, ''), a);
            }
        });

        let matchedCount = 0;

        parsedFeaturesList.forEach((feat, idx) => {
            const rawVal = feat.properties ? String(feat.properties[selectedField] ?? '').trim() : '';
            const cleanVal = rawVal.toLowerCase();
            const matchedAset = dbMap.get(cleanVal) || dbMap.get(cleanVal.replace(/^0+/, ''));

            let areaStr = '-';
            let centroidLat = null;
            let centroidLng = null;

            if (typeof turf !== 'undefined' && feat.geometry) {
                try {
                    const area = turf.area(feat);
                    areaStr = Number(area.toFixed(1)).toLocaleString('id-ID') + ' m²';
                    const c = turf.centroid(feat);
                    centroidLng = c.geometry.coordinates[0];
                    centroidLat = c.geometry.coordinates[1];
                } catch (e) {}
            }

            const tr = document.createElement('tr');
            if (matchedAset) {
                matchedCount++;
                tr.innerHTML = `
                    <td class="text-secondary">${idx + 1}</td>
                    <td><span class="font-monospace fw-bold text-dark">${sipatEscape(rawVal)}</span></td>
                    <td><span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle-fill me-1"></i> Cocok</span></td>
                    <td class="fw-semibold text-primary">${sipatEscape(matchedAset.nama_aset)}</td>
                    <td class="font-monospace text-secondary">${areaStr}</td>
                `;

                payloadToSave.push({
                    kode_aset: matchedAset.kode_aset,
                    geojson: JSON.stringify(feat),
                    lat: centroidLat,
                    lng: centroidLng
                });
            } else {
                tr.innerHTML = `
                    <td class="text-secondary">${idx + 1}</td>
                    <td><span class="font-monospace text-muted">${sipatEscape(rawVal || '(Kosong)')}</span></td>
                    <td><span class="badge bg-secondary-subtle text-secondary">Tidak Ditemukan</span></td>
                    <td class="text-muted">-</td>
                    <td class="font-monospace text-secondary">${areaStr}</td>
                `;
            }
            previewTableBody.appendChild(tr);
        });

        matchedCountBadge.textContent = `${matchedCount} dari ${parsedFeaturesList.length} Bidang Cocok`;
        importPreviewContainer.style.display = 'block';

        if (matchedCount > 0) {
            btnSaveBatchGis.disabled = false;
            btnSaveBatchGis.textContent = `Simpan ${matchedCount} Poligon ke Database`;
        } else {
            btnSaveBatchGis.disabled = true;
            btnSaveBatchGis.textContent = 'Tidak Ada Data yang Cocok';
        }
    }

    // Save Batch GIS ke Database via AJAX
    btnSaveBatchGis.addEventListener('click', async function () {
        if (payloadToSave.length === 0) return;

        btnSaveBatchGis.disabled = true;
        btnSaveBatchGis.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Menyimpan...`;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const resp = await fetch("{{ route('sipat.peta.import-poligon') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ items: payloadToSave })
            });

            const res = await resp.json();
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalImportGis')).hide();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Impor Berhasil!',
                        text: res.message,
                    }).then(() => location.reload());
                } else {
                    alert(res.message);
                    location.reload();
                }
            } else {
                throw new Error(res.message || 'Terjadi kesalahan saat menyimpan.');
            }
        } catch (err) {
            alert('Gagal menyimpan poligon: ' + err.message);
            btnSaveBatchGis.disabled = false;
            btnSaveBatchGis.textContent = 'Simpan Poligon ke Database';
        }
    });

    setTimeout(() => map.invalidateSize(), 300);
});
</script>
@endsection


