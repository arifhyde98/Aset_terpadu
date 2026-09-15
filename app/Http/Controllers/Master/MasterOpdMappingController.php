<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\OpdMapping;
use App\Models\OpdSipat;
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
        $query = OpdMapping::with(['sipatOpd', 'erandisOpd']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('sipatOpd', function ($q) use ($search) {
                $q->where('nama', 'LIKE', "%{$search}%");
            })->orWhereHas('erandisOpd', function ($q) use ($search) {
                $q->where('nama', 'LIKE', "%{$search}%");
            });
        }

        $mappings = $query->orderBy('id', 'asc')->paginate(20)->withQueryString();
        $totalMappings = OpdMapping::count();
        $totalSipat = OpdSipat::count();
        $totalErandis = Opd::count();

        return view('master.opd_mapping.index', compact('mappings', 'totalMappings', 'totalSipat', 'totalErandis'));
    }

    public function destroy($id)
    {
        $mapping = OpdMapping::findOrFail($id);
        $mapping->delete();

        return redirect()->back()->with('success', 'Pemetaan OPD berhasil dihapus.');
    }

    /**
     * Melakukan sinkronisasi dan pencocokan cerdas otomatis instansi SIPAT ↔ E-RANDIS.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refresh(Request $request): \Illuminate\Http\RedirectResponse
    {
        $sipatOpds = OpdSipat::all();
        $erandisOpds = Opd::all();
        $newMapped = 0;

        foreach ($sipatOpds as $sipat) {
            // Jika sudah terpetakan, lewati
            if (OpdMapping::where('sipat_opd_id', $sipat->id)->exists()) {
                continue;
            }

            $sipatNameUpper = strtoupper(trim($sipat->nama));
            $sipatClean = $this->normalizeOpdName($sipat->nama);

            $matchedErandis = null;

            // 1. Pencocokan Nama Sama Persis (Case-Insensitive)
            foreach ($erandisOpds as $erandis) {
                if ($sipatNameUpper === strtoupper(trim($erandis->nama))) {
                    $matchedErandis = $erandis;
                    break;
                }
            }

            // 2. Pencocokan Normalisasi Nama (Menghilangkan kata dinas, badan, kab, dsb)
            if (!$matchedErandis) {
                foreach ($erandisOpds as $erandis) {
                    $erandisClean = $this->normalizeOpdName($erandis->nama);
                    if ($sipatClean === $erandisClean && !empty($sipatClean)) {
                        $matchedErandis = $erandis;
                        break;
                    }
                }
            }

            // 3. Pencocokan Heuristik & Akronim Khusus (BPKAD, RSUD Tambu, dll)
            if (!$matchedErandis) {
                foreach ($erandisOpds as $erandis) {
                    $eUpper = strtoupper(trim($erandis->nama));
                    // BPKAD / Keuangan dan Aset
                    if (str_contains($sipatNameUpper, 'KEUANGAN') && str_contains($sipatNameUpper, 'ASET') && ($eUpper === 'BPKAD' || str_contains($eUpper, 'KEUANGAN'))) {
                        $matchedErandis = $erandis;
                        break;
                    }
                    // RSUD Tambu / Pendau
                    if (str_contains($sipatNameUpper, 'TAMBU') && str_contains($eUpper, 'TAMBU')) {
                        $matchedErandis = $erandis;
                        break;
                    }
                }
            }

            if ($matchedErandis) {
                // Hindari duplikasi di sisi erandis jika sudah terikat
                $alreadyUsed = OpdMapping::where('erandis_opd_id', $matchedErandis->id)->exists();
                if (!$alreadyUsed) {
                    OpdMapping::create([
                        'sipat_opd_id'      => $sipat->id,
                        'erandis_opd_id'    => $matchedErandis->id,
                        'status_verifikasi' => 'matched',
                    ]);
                    $newMapped++;
                }
            }
        }

        \App\Models\Activity::log("Melakukan refresh dan sinkronisasi pemetaan OPD terpadu [Ditemukan baru: {$newMapped}]", 'info');

        return redirect()->route('master.opd-mapping.index')
            ->with('success', "Sinkronisasi selesai. Berhasil menghubungkan {$newMapped} instansi baru.");
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
