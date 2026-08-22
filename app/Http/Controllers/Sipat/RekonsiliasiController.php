<?php

namespace App\Http\Controllers\Sipat;

use App\Http\Controllers\Controller;
use App\Models\AsetTanah;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class RekonsiliasiController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function index()
    {
        // 1. Ambil semua NIB dari eLabel melalui API (MOCKUP SEMENTARA)
        $eLabelNibarList = $this->fetchElabelNibarList();

        // 2. Ambil aset dari SIPAT yang berstatus "Bersertifikat" atau "Bersertifikat (Duplikat)"
        // Query menggunakan Eloquent / Query Builder
        $asetSipat = AsetTanah::select('aset_tanah.id_aset as id', 'aset_tanah.kode_aset', 'aset_tanah.nama_aset', 'aset_tanah.alamat', 'sp.nama_status as status_saat_ini')
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
            ->where('sp.nama_status', 'like', '%Bersertifikat%')
            ->whereNotNull('aset_tanah.kode_aset')
            ->where('aset_tanah.kode_aset', '!=', '')
            ->get();

        $matchList = [];
        $missList = [];

        foreach ($asetSipat as $aset) {
            $nibar = trim($aset->kode_aset);
            if (in_array($nibar, $eLabelNibarList, true)) {
                $matchList[] = $aset;
            } else {
                $missList[] = $aset;
            }
        }

        return view('sipat.rekonsiliasi.index', [
            'matchList' => $matchList,
            'missList'  => $missList,
            'totalElabel' => count($eLabelNibarList)
        ]);
    }

    private function fetchElabelNibarList(): array
    {
        // Karena SIPAT dan eLabel sudah TERPADU dalam satu database,
        // kita tidak perlu lagi memanggil API. Langsung query ke tabel eLabel!
        return \App\Models\Elabel\ElabelSertifikat::whereNotNull('nibar')
            ->where('nibar', '!=', '')
            ->pluck('nibar')
            ->toArray();
    }
}
