<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\OpdMapping;
use App\Models\Opd;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class MasterOpdMappingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function index(Request $request)
    {
        return redirect()->route('opds.index')->with('info', 'Master OPD kini telah disatukan ke dalam satu tabel terpadu.');
    }

    public function destroy($id)
    {
        return redirect()->route('opds.index')->with('info', 'Master OPD kini telah disatukan ke dalam satu tabel terpadu.');
    }

    /**
     * Melakukan sinkronisasi dan pencocokan cerdas otomatis instansi SIPAT ↔ E-RANDIS.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refresh(Request $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('opds.index')->with('info', 'Master OPD kini telah disatukan ke dalam satu tabel terpadu.');
    }

    /**
     * Menghilangkan kata-kata umum administrasi instansi untuk perbandingan kemiripan inti nama.
     *
     * @param string $name
     * @return string
     */
    private function normalizeOpdName(string $name): string
    {
        $name = strtoupper(trim($name));
        $wordsToRemove = ['DINAS', 'BADAN', 'KANTOR', 'KABUPATEN', 'KOTA', 'KAB.', 'KAB', 'UPTD', 'BAGIAN', 'SEKRETARIAT', 'DAN'];
        foreach ($wordsToRemove as $w) {
            $name = preg_replace('/\b' . preg_quote($w, '/') . '\b/i', '', $name);
        }
        return trim(preg_replace('/\s+/', ' ', $name));
    }
}
