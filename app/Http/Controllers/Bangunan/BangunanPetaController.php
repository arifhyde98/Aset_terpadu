<?php

namespace App\Http\Controllers\Bangunan;

use App\Http\Controllers\Controller;
use App\Models\Bangunan;
use App\Models\OpdSipat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class BangunanPetaController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    /**
     * Menampilkan antarmuka peta spasial sebaran gedung GIS.
     */
    public function index(): View
    {
        $opds = OpdSipat::orderBy('nama')->get();
        return view('bangunan.peta', compact('opds'));
    }

    /**
     * Mengembalikan data titik koordinat gedung dalam format GeoJSON FeatureCollection.
     */
    public function geojson(Request $request): JsonResponse
    {
        $query = Bangunan::with(['opdSipat', 'asetTanah'])
            ->forUser(auth()->user())
            ->whereNotNull('lat')
            ->whereNotNull('lng');

        if ($request->filled('opd_id')) {
            $query->where('opd_id', $request->opd_id);
        }

        if ($request->filled('kondisi')) {
            $query->where('kondisi', $request->kondisi);
        }

        $bangunans = $query->get();

        $features = $bangunans->map(function ($b) {
            $kondisiVal = is_object($b->kondisi) ? $b->kondisi->value : $b->kondisi;
            $color = match ($kondisiVal) {
                'Baik'        => '#10B981', // Hijau
                'Kurang Baik' => '#F59E0B', // Kuning/Oranye
                'Rusak Berat' => '#EF4444', // Merah
                default       => '#3B82F6',
            };

            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $b->lng, (float) $b->lat],
                ],
                'properties' => [
                    'id'               => $b->id,
                    'nama'             => $b->nama_bangunan,
                    'kode'             => $b->kode_bangunan,
                    'opd'              => $b->opdSipat?->nama ?? 'Semua OPD',
                    'kondisi'          => $kondisiVal,
                    'luas'             => number_format((float) $b->luas_lantai, 2, ',', '.') . ' m²',
                    'harga'            => 'Rp ' . number_format((float) $b->harga_perolehan, 0, ',', '.'),
                    'tanah_kib_a'      => $b->asetTanah?->kode_aset ?? 'Non-KIB A',
                    'color'            => $color,
                    'url'              => route('bangunan.show', $b),
                ],
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }
}
