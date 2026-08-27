<?php

namespace App\Http\Controllers\Sipat;

use App\Http\Controllers\Controller;
use App\Models\AsetTanah;
use App\Models\OpdSipat;
use App\Models\StatusProses;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class PetaController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function index()
    {
        $totalPoligon = AsetTanah::whereNotNull('geojson')->where('geojson', '!=', '')->count();
        $totalMarker = AsetTanah::where(function($q) {
            $q->whereNull('geojson')->orWhere('geojson', '');
        })->whereNotNull('lat')->whereNotNull('lng')->count();

        $totalTotal = AsetTanah::where(function($q) {
            $q->where(function($q2) {
                $q2->whereNotNull('lat')->whereNotNull('lng');
            })->orWhere(function($q3) {
                $q3->whereNotNull('geojson')->where('geojson', '!=', '');
            });
        })->count();

        $opdList = OpdSipat::where('aktif', 1)->orderBy('nama', 'asc')->get();
        $statusList = StatusProses::orderBy('urutan', 'asc')->get();
        $allAsetNibar = AsetTanah::select('id_aset', 'kode_aset', 'nama_aset')->orderBy('kode_aset', 'asc')->get();

        return view('sipat.peta.index', [
            'totalPoligon' => $totalPoligon,
            'totalMarker'  => $totalMarker,
            'totalTotal'   => $totalTotal,
            'opdList'      => $opdList,
            'statusList'   => $statusList,
            'allAsetNibar' => $allAsetNibar,
        ]);
    }

    /**
     * Endpoint API JSON untuk memuat data spasial bidang tanah (Poligon & Marker) secara asinkron.
     */
    public function data(Request $request): JsonResponse
    {
        $asetSipat = AsetTanah::leftJoin(DB::raw('(SELECT p1.id_aset, p1.id_status
                                FROM proses_aset p1
                                JOIN (
                                    SELECT id_aset, MAX(id_proses) AS max_id
                                    FROM proses_aset
                                    GROUP BY id_aset
                                ) p2 ON p1.id_aset = p2.id_aset AND p1.id_proses = p2.max_id) p'), 
                function($join) {
                    $join->on('p.id_aset', '=', 'aset_tanah.id_aset');
                }
            )
            ->leftJoin('status_proses as sp', 'sp.id_status', '=', 'p.id_status')
            ->leftJoin('opd', 'opd.id', '=', 'aset_tanah.opd_id')
            ->select(
                'aset_tanah.id_aset as id',
                'aset_tanah.kode_aset',
                'aset_tanah.nama_aset',
                'aset_tanah.peruntukan',
                'aset_tanah.luas',
                'aset_tanah.alamat',
                'aset_tanah.lat',
                'aset_tanah.lng',
                'aset_tanah.geojson',
                'aset_tanah.opd_id',
                'aset_tanah.opd as legacy_opd',
                'opd.nama as nama_opd',
                'sp.id_status',
                'sp.nama_status',
                'sp.kategori',
                'sp.warna'
            )
            ->where(function($q) {
                $q->where(function($q2) {
                    $q2->whereNotNull('aset_tanah.lat')->whereNotNull('aset_tanah.lng');
                })->orWhere(function($q3) {
                    $q3->whereNotNull('aset_tanah.geojson')->where('aset_tanah.geojson', '!=', '');
                });
            })
            ->get();

        $features = [];
        $totalPoligon = 0;
        $totalMarker = 0;

        foreach ($asetSipat as $row) {
            $rawGeojson = trim((string) $row->geojson);
            $hasPolygon = !empty($rawGeojson);
            $parsedGeojson = null;

            if ($hasPolygon) {
                $totalPoligon++;
                $parsedGeojson = json_decode($rawGeojson, true);
            } else {
                $totalMarker++;
            }

            $opdLabel = $row->nama_opd ?? $row->legacy_opd ?? 'Belum Ditentukan';
            $statusLabel = $row->nama_status ?? 'Belum Diurus';
            $statusColor = $row->warna ?? 'secondary';

            $kategoriRaw = strtolower(trim($row->kategori ?? ''));
            $statusNamaRaw = strtolower(trim($row->nama_status ?? ''));
            $sigeoStatus = 'belumbersertifikat';
            if ($kategoriRaw === 'bersertifikat' || str_contains($statusNamaRaw, 'terbit') || str_contains($statusNamaRaw, 'selesai')) {
                $sigeoStatus = 'bersertifikat';
            } elseif (str_contains($statusNamaRaw, 'sengketa') || str_contains($statusNamaRaw, 'konflik') || str_contains($statusNamaRaw, 'bermasalah')) {
                $sigeoStatus = 'sengketa';
            } elseif (str_contains($statusNamaRaw, 'idle') || str_contains($statusNamaRaw, 'pasif')) {
                $sigeoStatus = 'idle';
            }

            $features[] = [
                'id'            => $row->id,
                'kode'          => $row->kode_aset ?? '-',
                'nama'          => $row->nama_aset ?? 'Tanpa Nama',
                'jenis_aset'    => $row->peruntukan ?? 'Tanah',
                'peruntukan'    => $row->peruntukan ?? '-',
                'luas'          => (float) ($row->luas ?? 0),
                'alamat'        => $row->alamat ?? '-',
                'kecamatan'     => null,
                'opd_id'        => $row->opd_id,
                'opd_nama'      => $opdLabel,
                'status_id'     => $row->id_status,
                'status_nama'   => $statusLabel,
                'status_warna'  => $statusColor,
                'status_sigeo'  => $sigeoStatus,
                'kategori'      => $kategoriRaw,
                'lat'           => $row->lat !== null ? (float) $row->lat : null,
                'lng'           => $row->lng !== null ? (float) $row->lng : null,
                'has_polygon'   => $hasPolygon && !empty($parsedGeojson),
                'geojson_data'  => $parsedGeojson,
            ];
        }

        return response()->json([
            'success'  => true,
            'features' => $features,
            'summary'  => [
                'total'   => count($features),
                'poligon' => $totalPoligon,
                'marker'  => $totalMarker,
            ]
        ]);
    }

    /**
     * Menyimpan batch poligon GIS dari impor SHP atau GeoJSON berdasarkan NIBAR.
     */
    public function importPoligon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.kode_aset' => 'required|string',
            'items.*.geojson' => 'required|string',
            'items.*.lat' => 'nullable|numeric',
            'items.*.lng' => 'nullable|numeric',
        ]);

        $items = $validated['items'];
        $updatedCount = 0;
        $notFoundKodes = [];

        foreach ($items as $item) {
            $kode = trim((string) $item['kode_aset']);
            $aset = AsetTanah::where('kode_aset', $kode)
                ->orWhere('kode_aset', ltrim($kode, '0'))
                ->first();

            if (!$aset) {
                $notFoundKodes[] = $kode;
                continue;
            }

            $updateData = [
                'geojson' => $item['geojson'],
            ];

            // Isi centroid jika lat/lng masih kosong
            if (!empty($item['lat']) && !empty($item['lng']) && (empty($aset->lat) || empty($aset->lng))) {
                $updateData['lat'] = (float) $item['lat'];
                $updateData['lng'] = (float) $item['lng'];
            }

            $aset->update($updateData);
            $updatedCount++;
        }

        if (class_exists(Activity::class)) {
            Activity::logSipat("Mengimpor {$updatedCount} poligon batas bidang tanah GIS (SHP/GeoJSON)", 'success');
        }

        return response()->json([
            'success' => true,
            'message' => "Berhasil memperbarui {$updatedCount} bidang tanah dengan data poligon GIS.",
            'updated_count' => $updatedCount,
            'not_found_count' => count($notFoundKodes),
            'not_found_kodes' => array_slice($notFoundKodes, 0, 20),
        ]);
    }

    /**
     * Ekspor seluruh data poligon bidang tanah dalam format GeoJSON.
     */
    public function exportGeojson()
    {
        $asets = AsetTanah::with(['opdSipat', 'latestProses.statusProses'])
            ->whereNotNull('geojson')
            ->get();

        $geojsonFeatures = [];
        foreach ($asets as $aset) {
            $geo = json_decode($aset->geojson, true);
            if (!$geo) continue;

            $geometry = null;
            if (isset($geo['type']) && $geo['type'] === 'Feature') {
                $geometry = $geo['geometry'] ?? null;
            } elseif (isset($geo['type']) && in_array($geo['type'], ['Polygon', 'MultiPolygon'])) {
                $geometry = $geo;
            } elseif (isset($geo['geometry'])) {
                $geometry = $geo['geometry'];
            }

            if (!$geometry) continue;

            $geojsonFeatures[] = [
                'type' => 'Feature',
                'properties' => [
                    'id_aset'     => $aset->id_aset,
                    'nibar'       => $aset->kode_aset,
                    'nama_aset'   => $aset->nama_aset,
                    'peruntukan'  => $aset->peruntukan,
                    'luas'        => (float) $aset->luas,
                    'opd'         => $aset->opdSipat->nama ?? $aset->opd ?? '-',
                    'status_bpn'  => $aset->latestProses->statusProses->nama_status ?? 'Belum Diurus',
                    'alamat'      => $aset->alamat,
                ],
                'geometry' => $geometry,
            ];
        }

        $featureCollection = [
            'type' => 'FeatureCollection',
            'crs' => [
                'type' => 'name',
                'properties' => ['name' => 'urn:ogc:def:crs:OGC:1.3:CRS84']
            ],
            'features' => $geojsonFeatures,
        ];

        $fileName = 'Peta_Bidang_Tanah_SIPAT_' . date('Ymd_His') . '.geojson';

        return response()->json($featureCollection)
            ->header('Content-Type', 'application/geo+json')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }
}

