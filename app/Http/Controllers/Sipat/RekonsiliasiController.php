<?php

namespace App\Http\Controllers\Sipat;

use App\Http\Controllers\Controller;
use App\Models\AsetTanah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class RekonsiliasiController extends Controller
{
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
        // Mockup sementara agar tidak error saat koneksi ke eLabel ditutup sesuai arahan user.
        return [];
        
        // Kode asli yang di-comment out:
        /*
        try {
            $apiUrl = env('ELABEL_API_URL', 'http://elabel.test/api/v1/sertifikat/');
            $baseUrl = str_replace('sertifikat/', '', $apiUrl);
            $endpoint = rtrim($baseUrl, '/') . '/sertifikat-all-nibar';

            $response = Http::withHeaders([
                'X-API-KEY' => 'SIPAT-ELABEL-SECURE-KEY-2026',
                'Accept'    => 'application/json'
            ])->timeout(15)->get($endpoint);

            if ($response->successful()) {
                $body = $response->json();
                if (isset($body['data']) && is_array($body['data'])) {
                    return $body['data'];
                }
            }
            return [];
        } catch (\Exception $e) {
            Log::error('Gagal fetch eLabel Nibar List: ' . $e->getMessage());
            return [];
        }
        */
    }
}
