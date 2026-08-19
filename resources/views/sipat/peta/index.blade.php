@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 fw-bold mb-1 text-body">Peta Aset Tanah</h1>
        <small class="text-secondary">Sebaran peta geografis & status pensertifikatan aset</small>
    </div>
</div>

<style>
    #map {
        height: 70vh;
        min-height: 480px;
        width: 100%;
        border-radius: 16px;
        z-index: 1;
    }
    @media (max-width: 767.98px) {
        #map {
            height: calc(100vh - var(--admin-header-height, 64px) - var(--mobile-nav-height, 64px) - 60px) !important;
            min-height: 380px;
            border-radius: 14px;
        }
    }
</style>

<div class="card clean-card border-0 shadow-sm overflow-hidden position-relative">
    <div class="card-body p-2 position-relative">
        <div id="map"></div>
        <!-- Mobile Map Floating Action Buttons -->
        <div class="mobile-map-fab-group">
            <button type="button" class="mobile-map-fab" id="btnFitBounds" title="Reset Tampilan Peta">
                <i class="bi bi-arrows-fullscreen"></i>
            </button>
            <button type="button" class="mobile-map-fab text-primary" id="btnLocateMe" title="Lokasi Saya">
                <i class="bi bi-geo-alt-fill"></i>
            </button>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof L === 'undefined') {
            console.error('Leaflet gagal dimuat.');
            return;
        }

        const sipatEscape = (str) => String(str).replace(/[&<>'"]/g, match => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
        }[match]));

        const map = L.map('map').setView([-2.5, 117.5], 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const markers = {!! json_encode($markers) !!} || [];
        const bounds = [];
        markers.forEach(item => {
            const lat = parseFloat(item.lat);
            const lng = parseFloat(item.lng);
            if (Number.isNaN(lat) || Number.isNaN(lng)) return;
            const marker = L.marker([lat, lng]).addTo(map);
            marker.bindPopup(
                `<div style="font-family:sans-serif; padding:4px;">
                    <span class="badge bg-primary mb-1">${sipatEscape(item.kode)}</span>
                    <h6 class="fw-bold mb-1 text-body" style="font-size:14px;">${sipatEscape(item.nama)}</h6>
                    <div class="small text-secondary mb-2">Status: <strong>${sipatEscape(item.status)}</strong></div>
                    <a href="{!! url('sipat/aset') !!}/${item.id}" class="btn btn-xs btn-primary text-white w-100 text-decoration-none">
                        <i class="bi bi-eye me-1"></i> Lihat Detail Aset
                    </a>
                </div>`
            );
            bounds.push([lat, lng]);
        });

        const resetView = () => {
            if (bounds.length) {
                map.fitBounds(bounds, { padding: [30, 30] });
            }
        };
        resetView();

        document.getElementById('btnFitBounds')?.addEventListener('click', resetView);
        document.getElementById('btnLocateMe')?.addEventListener('click', () => {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(pos => {
                    map.setView([pos.coords.latitude, pos.coords.longitude], 15);
                    L.marker([pos.coords.latitude, pos.coords.longitude]).addTo(map)
                        .bindPopup('<b>Posisi Anda Saat Ini</b>').openPopup();
                }, () => {
                    if (typeof Swal !== 'undefined') Swal.fire('Lokasi', 'Gagal mengakses GPS perangkat.', 'info');
                });
            }
        });

        setTimeout(() => map.invalidateSize(), 300);
    });
</script>
@endsection
