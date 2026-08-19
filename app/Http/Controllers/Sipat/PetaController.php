<?php

namespace App\Http\Controllers\Sipat;

use App\Http\Controllers\Controller;
use App\Models\AsetTanah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PetaController extends Controller
{
    public function index()
    {
        $asetSipat = AsetTanah::select('aset_tanah.id_aset as id', 'aset_tanah.kode_aset', 'aset_tanah.nama_aset', 'aset_tanah.lat', 'aset_tanah.lng', 'sp.nama_status', 'sp.warna')
            ->leftJoin(DB::raw('(SELECT p1.id_aset, p1.id_status
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
            ->whereNotNull('aset_tanah.lat')
            ->whereNotNull('aset_tanah.lng')
            ->get();

        $markers = [];
        foreach ($asetSipat as $row) {
            $markers[] = [
                'id'           => $row->id,
                'kode'         => $row->kode_aset,
                'nama'         => $row->nama_aset,
                'lat'          => (float) $row->lat,
                'lng'          => (float) $row->lng,
                'status'       => $row->nama_status ?? 'Belum Diurus',
                'warna_status' => $row->warna ?? 'secondary',
            ];
        }

        return view('sipat.peta.index', [
            'markers' => $markers,
        ]);
    }
}
