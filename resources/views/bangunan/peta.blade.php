@extends('layouts.app')

@section('content')
<!-- Leaflet CSS & JS (Aset Lokal - Konsisten dengan Peta SIPAT) -->
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>

<style>
    #mapGedung {
        height: calc(100vh - 210px);
        min-height: 500px;
        width: 100%;
        border-radius: 1rem;
        z-index: 1;
    }
    .legend-card {
        position: absolute;
        bottom: 24px;
        right: 24px;
        z-index: 1000;
        background: var(--bs-body-bg, rgba(255, 255, 255, 0.95));
        color: var(--bs-body-color, #212529);
        backdrop-filter: blur(8px);
        border-radius: 0.75rem;
        padding: 10px 16px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border: 1px solid var(--bs-border-color, rgba(0,0,0,0.08));
    }
    /* Loading Overlay */
    #mapGedungLoadingOverlay {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        display: flex; flex-direction: column;
        justify-content: center; align-items: center;
        background: var(--bs-body-bg, rgba(255, 255, 255, 0.85));
        color: var(--bs-body-color, #212529);
        z-index: 1050;
        border-radius: 1rem;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }
    #mapGedungLoadingOverlay.hidden {
        opacity: 0; visibility: hidden; pointer-events: none;
    }
    /* Empty State */
    .map-empty-state {
        position: absolute;
        top: 50%; left: 50%; transform: translate(-50%, -50%);
        z-index: 600;
        text-align: center;
        padding: 24px;
        display: none;
    }
    .map-empty-state.active { display: block; }
    /* Dark Mode Overrides untuk Leaflet Popups */
    [data-bs-theme="dark"] .leaflet-popup-content-wrapper,
    [data-bs-theme="dark"] .leaflet-popup-tip {
        background: var(--bs-body-bg, #1e293b) !important;
        color: var(--bs-body-color, #f8fafc) !important;
        border: 1px solid var(--bs-border-color, #334155);
    }
    [data-bs-theme="dark"] .leaflet-container {
        background-color: #0f172a !important;
    }
    /* Floating Stats */
    .bangunan-floating-stats {
        position: absolute;
        bottom: 24px;
        left: 24px;
        z-index: 500;
    }
    .bangunan-stat-pill {
        background: var(--bs-body-bg, rgba(255, 255, 255, 0.92));
        color: var(--bs-body-color, #212529);
        backdrop-filter: blur(8px);
        border: 1px solid var(--bs-border-color, rgba(0, 0, 0, 0.08));
        border-radius: 30px;
        padding: 6px 14px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
</style>

<div class="container-fluid px-3 px-lg-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 text-muted small">
                    <li class="breadcrumb-item"><a href="{{ route('landing') }}" class="text-decoration-none">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('bangunan.index') }}" class="text-decoration-none">KIB C - Bangunan</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Peta Spasial</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-geo-alt-fill text-danger"></i> Peta Spasial Sebaran Gedung &amp; Bangunan
            </h4>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('bangunan.index') }}" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">
                <i class="bi bi-table me-1"></i> Kembali ke Tabel
            </a>
            <a href="{{ route('bangunan.create') }}" class="btn btn-primary rounded-pill px-3 shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Tambah Bangunan
            </a>
        </div>
    </div>

    <!-- Filter Mini Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body p-2 px-3">
            <div class="row g-2 align-items-center">
                @if(auth()->user()->role !== \App\Enums\UserRole::OPD)
                <div class="col-12 col-md-4">
                    <select id="filterOpd" class="form-select form-select-sm bg-light">
                        <option value="">-- Semua OPD / Instansi --</option>
                        @foreach($opds as $opd)
                            <option value="{{ $opd->id }}">{{ $opd->nama }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-6 col-md-3">
                    <select id="filterKondisi" class="form-select form-select-sm bg-light">
                        <option value="">-- Semua Kondisi Fisik --</option>
                        <option value="Baik">Baik (B)</option>
                        <option value="Kurang Baik">Kurang Baik (KB)</option>
                        <option value="Rusak Berat">Rusak Berat (RB)</option>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <button type="button" id="btnRefreshMap" class="btn btn-sm btn-primary rounded-pill w-100">
                        <i class="bi bi-arrow-repeat me-1"></i> Terapkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Container -->
    <div class="position-relative">
        <div id="mapGedung" class="shadow-sm border"></div>

        <!-- Loading Overlay -->
        <div id="mapGedungLoadingOverlay">
            <div class="spinner-border text-primary mb-2" role="status">
                <span class="visually-hidden">Memuat...</span>
            </div>
            <div class="small fw-semibold text-secondary">Memuat Titik Sebaran Bangunan...</div>
        </div>

        <!-- Empty State -->
        <div class="map-empty-state" id="mapGedungEmptyState">
            <i class="bi bi-building-x text-secondary" style="font-size: 2.5rem;"></i>
            <div class="fw-semibold text-secondary mt-2">Tidak ada data gedung dengan koordinat</div>
            <div class="text-muted small">Ubah filter atau tambahkan koordinat pada data bangunan</div>
        </div>

        <!-- Legend -->
        <div class="legend-card small">
            <h6 class="fw-bold mb-2" style="font-size: 0.78rem;">Indikator Kondisi Gedung</h6>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="rounded-circle d-inline-block" style="width: 12px; height: 12px; background: #10B981;"></span>
                <span class="text-muted">Kondisi Baik (B)</span>
            </div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="rounded-circle d-inline-block" style="width: 12px; height: 12px; background: #F59E0B;"></span>
                <span class="text-muted">Kurang Baik (KB)</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="rounded-circle d-inline-block" style="width: 12px; height: 12px; background: #EF4444;"></span>
                <span class="text-muted">Rusak Berat (RB)</span>
            </div>
        </div>

        <!-- Floating Stats -->
        <div class="bangunan-floating-stats">
            <span class="bangunan-stat-pill">
                <i class="bi bi-buildings text-primary"></i>
                Total: <strong id="statGedungTotal">0</strong> Gedung
            </span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loadingOverlay = document.getElementById('mapGedungLoadingOverlay');
    const emptyState = document.getElementById('mapGedungEmptyState');
    const statTotal = document.getElementById('statGedungTotal');

    // Pusat Kabupaten Donggala
    const map = L.map('mapGedung', {
        zoomControl: true,
        attributionControl: true,
        minZoom: 8,
        maxZoom: 20
    }).setView([-0.6800, 119.8900], 10);

    // Basemaps Tile Layers (Konsisten dengan Peta SIPAT)
    const googleSatellite = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        attribution: '&copy; Google Hybrid Satellite'
    });

    const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors | SIPAT Terpadu'
    });

    const esriTopo = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19,
        attribution: '&copy; Esri Topographic'
    });

    const cartoDark = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        maxZoom: 19,
        attribution: '&copy; CartoDB Dark Matter'
    });

    osmLayer.addTo(map);

    L.control.layers({
        'Peta Jalan (OpenStreetMap)': osmLayer,
        'Citra Satelit (Google Hybrid)': googleSatellite,
        'Peta Topografi Relief (Esri)': esriTopo,
        'Mode Gelap (CartoDB Dark)': cartoDark
    }, null, { position: 'topright' }).addTo(map);

    let markersLayer = L.layerGroup().addTo(map);

    function loadGeoJson() {
        const opdId = document.getElementById('filterOpd')?.value || '';
        const kondisi = document.getElementById('filterKondisi').value;

        // Tampilkan loading, sembunyikan empty state
        if (loadingOverlay) { loadingOverlay.classList.remove('hidden'); loadingOverlay.style.display = 'flex'; }
        if (emptyState) emptyState.classList.remove('active');

        const url = new URL('{{ route("bangunan.peta.geojson") }}', window.location.origin);
        if (opdId) url.searchParams.append('opd_id', opdId);
        if (kondisi) url.searchParams.append('kondisi', kondisi);

        fetch(url)
            .then(res => res.json())
            .then(data => {
                markersLayer.clearLayers();

                if (!data.features || data.features.length === 0) {
                    if (statTotal) statTotal.textContent = '0';
                    if (emptyState) emptyState.classList.add('active');
                    return;
                }

                if (statTotal) statTotal.textContent = data.features.length;
                if (emptyState) emptyState.classList.remove('active');

                const bounds = [];

                data.features.forEach(feat => {
                    const coords = feat.geometry.coordinates;
                    const latLng = [coords[1], coords[0]];
                    bounds.push(latLng);

                    const props = feat.properties;

                    const marker = L.circleMarker(latLng, {
                        radius: 8,
                        fillColor: props.color,
                        color: '#ffffff',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.85
                    });

                    const popupContent = `
                        <div style="min-width: 200px;">
                            <h6 style="margin: 0 0 4px 0; font-weight: bold; color: var(--bs-body-color, #1e293b);">${props.nama}</h6>
                            <div style="font-size: 11px; color: var(--bs-secondary-color, #64748b); margin-bottom: 6px;">${props.opd}</div>
                            <div style="font-size: 11px; margin-bottom: 4px;">
                                <strong>Kondisi:</strong> <span style="color: ${props.color}; font-weight: bold;">${props.kondisi}</span>
                            </div>
                            <div style="font-size: 11px; margin-bottom: 4px;"><strong>Luas Lantai:</strong> ${props.luas}</div>
                            <div style="font-size: 11px; margin-bottom: 6px;"><strong>Nilai Aset:</strong> ${props.harga}</div>
                            <div style="border-top: 1px solid var(--bs-border-color, #e2e8f0); padding-top: 6px; text-align: right;">
                                <a href="${props.url}" class="btn btn-sm btn-primary" style="font-size: 10px; padding: 2px 8px; border-radius: 20px; color: white; text-decoration: none;">
                                    Lihat Profil
                                </a>
                            </div>
                        </div>
                    `;

                    marker.bindPopup(popupContent);
                    marker.bindTooltip(props.nama, { direction: 'top', offset: [0, -10] });
                    markersLayer.addLayer(marker);
                });

                if (bounds.length > 0) {
                    map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
                }
            })
            .catch(err => {
                console.error('Gagal memuat data peta bangunan:', err);
            })
            .finally(() => {
                // Sembunyikan loading overlay
                if (loadingOverlay) {
                    loadingOverlay.classList.add('hidden');
                    setTimeout(() => { loadingOverlay.style.display = 'none'; }, 300);
                }
            });
    }

    // Auto-apply filter on change (tanpa tombol Terapkan)
    document.getElementById('filterOpd')?.addEventListener('change', loadGeoJson);
    document.getElementById('filterKondisi').addEventListener('change', loadGeoJson);
    document.getElementById('btnRefreshMap').addEventListener('click', loadGeoJson);
    loadGeoJson();

    setTimeout(() => map.invalidateSize(), 200);
});
</script>
@endsection
