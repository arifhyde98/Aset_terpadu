@extends('layouts.app')

@section('content')
<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

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
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(8px);
        border-radius: 0.75rem;
        padding: 10px 16px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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

        <!-- Legend -->
        <div class="legend-card small">
            <h6 class="fw-bold mb-2 text-dark" style="font-size: 0.78rem;">Indikator Kondisi Gedung</h6>
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pusat Kabupaten Donggala
    const map = L.map('mapGedung').setView([-0.6800, 119.8900], 10);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors | SIPAT Terpadu'
    }).addTo(map);

    let markersLayer = L.layerGroup().addTo(map);

    function loadGeoJson() {
        const opdId = document.getElementById('filterOpd')?.value || '';
        const kondisi = document.getElementById('filterKondisi').value;

        const url = new URL('{{ route("bangunan.peta.geojson") }}', window.location.origin);
        if (opdId) url.searchParams.append('opd_id', opdId);
        if (kondisi) url.searchParams.append('kondisi', kondisi);

        fetch(url)
            .then(res => res.json())
            .then(data => {
                markersLayer.clearLayers();

                if (!data.features || data.features.length === 0) {
                    return;
                }

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
                            <h6 style="margin: 0 0 4px 0; font-weight: bold; color: #1e293b;">${props.nama}</h6>
                            <div style="font-size: 11px; color: #64748b; margin-bottom: 6px;">${props.opd}</div>
                            <div style="font-size: 11px; margin-bottom: 4px;">
                                <strong>Kondisi:</strong> <span style="color: ${props.color}; font-weight: bold;">${props.kondisi}</span>
                            </div>
                            <div style="font-size: 11px; margin-bottom: 4px;"><strong>Luas Lantai:</strong> ${props.luas}</div>
                            <div style="font-size: 11px; margin-bottom: 6px;"><strong>Nilai Aset:</strong> ${props.harga}</div>
                            <div style="border-top: 1px solid #e2e8f0; padding-top: 6px; text-align: right;">
                                <a href="${props.url}" class="btn btn-sm btn-primary" style="font-size: 10px; padding: 2px 8px; border-radius: 20px; color: white; text-decoration: none;">
                                    Lihat Profil
                                </a>
                            </div>
                        </div>
                    `;

                    marker.bindPopup(popupContent);
                    markersLayer.addLayer(marker);
                });

                if (bounds.length > 0) {
                    map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
                }
            });
    }

    document.getElementById('btnRefreshMap').addEventListener('click', loadGeoJson);
    loadGeoJson();
});
</script>
@endsection
