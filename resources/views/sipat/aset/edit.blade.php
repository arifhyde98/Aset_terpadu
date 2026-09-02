@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Edit Data Aset Tanah</h2>
            <p class="text-secondary small mb-0">Perbarui rincian bidang tanah {{ $aset->kode_aset }}</p>
        </div>
        <a href="{{ route('sipat.aset.index', session('sipat_aset_filters', [])) }}" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
            <ul class="mb-0 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card clean-card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <form action="{{ route('sipat.aset.update', $aset->id_aset) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Kode Aset (NIBAR / KIB) <span class="text-danger">*</span></label>
                        <input type="text" name="kode_aset" class="form-control" required value="{{ old('kode_aset', $aset->kode_aset) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Nama Aset / Bidang Tanah <span class="text-danger">*</span></label>
                        <input type="text" name="nama_aset" class="form-control" required value="{{ old('nama_aset', $aset->nama_aset) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">OPD Pengelola</label>
                        <select name="opd_id" class="form-select">
                            <option value="">-- Pilih OPD Pengelola --</option>
                            @foreach($opdList as $opd)
                                <option value="{{ $opd->id }}" {{ old('opd_id', $aset->opd_id) == $opd->id ? 'selected' : '' }}>{{ $opd->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Peruntukan / Penggunaan</label>
                        <input type="text" name="peruntukan" class="form-control" value="{{ old('peruntukan', $aset->peruntukan) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Luas Tanah (m²)</label>
                        <input type="number" step="0.01" name="luas" class="form-control" value="{{ old('luas', $aset->luas) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Harga Perolehan (Rp)</label>
                        <input type="number" step="0.01" name="harga_perolehan" class="form-control" value="{{ old('harga_perolehan', $aset->harga_perolehan) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Tanggal Perolehan</label>
                        <input type="date" name="tanggal_perolehan" class="form-control" value="{{ old('tanggal_perolehan', $aset->tanggal_perolehan) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Dasar Perolehan</label>
                        <input type="text" name="dasar_perolehan" class="form-control" value="{{ old('dasar_perolehan', $aset->dasar_perolehan) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Wilayah Kecamatan</label>
                        <select name="kecamatan_id" class="form-select">
                            <option value="">-- Pilih Kecamatan --</option>
                            @if(isset($kecamatanList))
                                @foreach($kecamatanList as $kec)
                                    <option value="{{ $kec->id }}" {{ old('kecamatan_id', $aset->kecamatan_id) == $kec->id ? 'selected' : '' }}>{{ $kec->nama }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Alamat Lengkap / Lokasi</label>
                        <textarea name="alamat" class="form-control" rows="2">{{ old('alamat', $aset->alamat) }}</textarea>
                    </div>

                    <!-- Batas Bidang Tanah & Koordinat GIS (Poligon / SHP / GeoJSON) -->
                    <div class="col-12 mt-4">
                        <div class="card border rounded-4 bg-light p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">
                                        <i class="bi bi-geo-alt-fill text-danger me-1"></i> Batas Bidang Tanah & Koordinat GIS
                                    </h6>
                                    <small class="text-secondary">Impor batas bidang dari file <strong>Shapefile (.zip)</strong>, <strong>GeoJSON (.geojson/.json)</strong>, atau gambar manual poligon batas tanah.</small>
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    <label class="btn btn-sm btn-primary rounded-pill shadow-sm mb-0 px-3 cursor-pointer">
                                        <i class="bi bi-file-earmark-arrow-up me-1"></i> Impor SHP (.zip) / GeoJSON
                                        <input type="file" id="gisFileInput" accept=".zip,.geojson,.json" class="d-none">
                                    </label>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" id="btnClearPolygon" title="Hapus Poligon">
                                        <i class="bi bi-trash me-1"></i> Hapus Batas
                                    </button>
                                </div>
                            </div>

                            <input type="hidden" name="geojson" id="geojsonInput" value="{{ old('geojson', $aset->geojson) }}">

                            <!-- Container Peta Interaktif -->
                            <div id="asetMap" style="height: 380px; width: 100%; border-radius: 12px; z-index: 1;" class="border mb-3 shadow-sm"></div>

                            <div class="row g-2 align-items-center">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Latitude (Centroid)</label>
                                    <input type="text" name="lat" id="latInput" class="form-control form-control-sm" value="{{ old('lat', $aset->lat) }}" placeholder="-0.123456">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Longitude (Centroid)</label>
                                    <input type="text" name="lng" id="lngInput" class="form-control form-control-sm" value="{{ old('lng', $aset->lng) }}" placeholder="119.123456">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Estimasi Luas Spasial Poligon</label>
                                    <div class="form-control form-control-sm bg-white font-monospace text-primary fw-bold" id="calculatedAreaText">
                                        {{ $aset->geojson ? 'Tersimpan (Memiliki Poligon)' : 'Belum Ada Poligon' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mt-3">
                        <label class="form-label small fw-semibold">Keterangan Tambahan</label>
                        <textarea name="keterangan" class="form-control" rows="2">{{ old('keterangan', $aset->keterangan) }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('sipat.aset.index', session('sipat_aset_filters', [])) }}" class="btn btn-secondary rounded-pill px-4">Batal</a>
                    <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="bi bi-save me-1"></i> Update Data Aset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Local Leaflet & SHP/GeoJSON Scripts -->
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/leaflet-draw/leaflet.draw.css') }}">
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script src="{{ asset('vendor/leaflet-draw/leaflet.draw.js') }}"></script>
<script src="{{ asset('vendor/shpjs/shp.js') }}"></script>
<script src="{{ asset('vendor/turf/turf.min.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const latInput = document.getElementById('latInput');
    const lngInput = document.getElementById('lngInput');
    const geojsonInput = document.getElementById('geojsonInput');
    const areaText = document.getElementById('calculatedAreaText');
    const fileInput = document.getElementById('gisFileInput');
    const btnClear = document.getElementById('btnClearPolygon');

    // Default view: Sulawesi Tengah / Donggala (-0.68, 119.75)
    let defaultLat = parseFloat(latInput.value) || -0.68;
    let defaultLng = parseFloat(lngInput.value) || 119.75;
    const initialZoom = (latInput.value && lngInput.value) ? 16 : 10;

    const map = L.map('asetMap').setView([defaultLat, defaultLng], initialZoom);

    // Tile Layers
    const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    });

    const googleSatellite = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        attribution: '&copy; Google Satellite'
    });

    googleSatellite.addTo(map);

    L.control.layers({
        'Satelit Google (Hybrid)': googleSatellite,
        'Peta Jalan OSM': osmLayer
    }, null, { position: 'topright' }).addTo(map);

    // FeatureGroup to store editable layers
    const drawnItems = new L.FeatureGroup();
    map.addLayer(drawnItems);

    let markerPoint = null;

    // Inisialisasi marker jika ada lat/lng
    function updateMarkerPoint(lat, lng) {
        if (markerPoint) {
            markerPoint.setLatLng([lat, lng]);
        } else {
            markerPoint = L.marker([lat, lng], { draggable: true }).addTo(map);
            markerPoint.on('dragend', function (e) {
                const pos = e.target.getLatLng();
                latInput.value = pos.lat.toFixed(7);
                lngInput.value = pos.lng.toFixed(7);
            });
        }
    }

    if (latInput.value && lngInput.value) {
        updateMarkerPoint(parseFloat(latInput.value), parseFloat(lngInput.value));
    }

    // Inisialisasi Poligon jika ada geojson lama
    function loadExistingGeojson() {
        const rawGeojson = geojsonInput.value.trim();
        if (!rawGeojson) return;

        try {
            const parsed = JSON.parse(rawGeojson);
            const geoLayer = L.geoJSON(parsed, {
                style: {
                    color: '#2563eb',
                    weight: 3,
                    fillColor: '#3b82f6',
                    fillOpacity: 0.35
                }
            });

            geoLayer.eachLayer(layer => {
                drawnItems.addLayer(layer);
            });

            if (drawnItems.getLayers().length > 0) {
                map.fitBounds(drawnItems.getBounds(), { padding: [30, 30] });
                calcSpatialMetrics();
            }
        } catch (err) {
            console.warn('Gagal parse GeoJSON tersimpan:', err);
        }
    }

    loadExistingGeojson();

    // Leaflet Draw Control
    const drawControl = new L.Control.Draw({
        edit: {
            featureGroup: drawnItems,
            remove: true
        },
        draw: {
            polygon: {
                allowIntersection: false,
                showArea: true,
                shapeOptions: {
                    color: '#2563eb',
                    fillColor: '#3b82f6',
                    fillOpacity: 0.35,
                    weight: 3
                }
            },
            polyline: false,
            rectangle: {
                shapeOptions: {
                    color: '#2563eb',
                    fillColor: '#3b82f6',
                    fillOpacity: 0.35,
                    weight: 3
                }
            },
            circle: false,
            circlemarker: false,
            marker: true
        }
    });
    map.addControl(drawControl);

    // Event Created
    map.on(L.Draw.Event.CREATED, function (e) {
        const type = e.layerType;
        const layer = e.layer;

        if (type === 'marker') {
            const pos = layer.getLatLng();
            latInput.value = pos.lat.toFixed(7);
            lngInput.value = pos.lng.toFixed(7);
            updateMarkerPoint(pos.lat, pos.lng);
        } else {
            drawnItems.clearLayers();
            drawnItems.addLayer(layer);
            syncGeojsonFromLayers();
        }
    });

    // Event Edited & Deleted
    map.on(L.Draw.Event.EDITED, function () {
        syncGeojsonFromLayers();
    });

    map.on(L.Draw.Event.DELETED, function () {
        syncGeojsonFromLayers();
    });

    // Sinkronisasi GeoJSON & Hitung Area / Centroid
    function syncGeojsonFromLayers() {
        const layers = drawnItems.getLayers();
        if (layers.length === 0) {
            geojsonInput.value = '';
            areaText.textContent = 'Belum Ada Poligon';
            return;
        }

        const geojsonObj = layers[0].toGeoJSON();
        geojsonInput.value = JSON.stringify(geojsonObj);
        calcSpatialMetrics();
    }

    function calcSpatialMetrics() {
        if (!geojsonInput.value) return;
        try {
            const geoObj = JSON.parse(geojsonInput.value);
            if (typeof turf !== 'undefined') {
                // Hitung Area (m²)
                const area = turf.area(geoObj);
                areaText.textContent = Number(area.toFixed(2)).toLocaleString('id-ID') + ' m²';

                // Hitung Centroid jika lat/lng kosong
                const centroid = turf.centroid(geoObj);
                const coords = centroid.geometry.coordinates;
                const cLng = coords[0];
                const cLat = coords[1];

                if (!latInput.value || !lngInput.value) {
                    latInput.value = cLat.toFixed(7);
                    lngInput.value = cLng.toFixed(7);
                    updateMarkerPoint(cLat, cLng);
                }
            }
        } catch (e) {
            console.error('Error turf calculation:', e);
        }
    }

    // Event File Input (SHP Zip / GeoJSON)
    fileInput.addEventListener('change', async function (e) {
        const file = e.target.files[0];
        if (!file) return;

        const fileName = file.name.toLowerCase();

        try {
            if (fileName.endsWith('.zip')) {
                // Parse Shapefile
                const arrayBuffer = await file.arrayBuffer();
                const geojson = await shp(arrayBuffer);
                applyImportedGeojson(geojson);
            } else if (fileName.endsWith('.geojson') || fileName.endsWith('.json')) {
                // Parse GeoJSON
                const text = await file.text();
                const geojson = JSON.parse(text);
                applyImportedGeojson(geojson);
            } else {
                alert('Format file tidak didukung. Harap unggah file .zip (Shapefile) atau .geojson/.json');
            }
        } catch (err) {
            console.error('Gagal memproses file GIS:', err);
            alert('Gagal memproses file spasial: ' + err.message);
        } finally {
            fileInput.value = '';
        }
    });

    function applyImportedGeojson(geojson) {
        drawnItems.clearLayers();

        let targetFeature = geojson;
        if (geojson.type === 'FeatureCollection' && geojson.features && geojson.features.length > 0) {
            targetFeature = geojson.features[0];
        }

        const geoLayer = L.geoJSON(targetFeature, {
            style: {
                color: '#10b981',
                weight: 3,
                fillColor: '#34d399',
                fillOpacity: 0.4
            }
        });

        geoLayer.eachLayer(l => drawnItems.addLayer(l));

        if (drawnItems.getLayers().length > 0) {
            map.fitBounds(drawnItems.getBounds(), { padding: [30, 30] });
            syncGeojsonFromLayers();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Batas Spasial Berhasil Dimuat',
                    text: 'Batas bidang tanah berhasil diimpor ke peta. Jangan lupa klik "Update Data Aset" untuk menyimpan.',
                    timer: 3000,
                    showConfirmButton: false
                });
            }
        }
    }

    // Tombol Hapus Poligon
    btnClear.addEventListener('click', function () {
        if (confirm('Hapus data batas poligon bidang tanah ini?')) {
            drawnItems.clearLayers();
            geojsonInput.value = '';
            areaText.textContent = 'Belum Ada Poligon';
        }
    });

    setTimeout(() => map.invalidateSize(), 300);
});
</script>
@endsection
