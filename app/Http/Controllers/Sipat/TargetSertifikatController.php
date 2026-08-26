<?php

namespace App\Http\Controllers\Sipat;

use App\Http\Controllers\Controller;
use App\Models\AsetTanah;
use App\Models\OpdSipat;
use App\Models\SipatTargetSertifikat;
use App\Models\Activity;
use App\Services\LaporanService;
use App\Exports\TargetSertifikatExport;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class TargetSertifikatController extends Controller implements HasMiddleware
{
    protected LaporanService $laporanService;

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function __construct(LaporanService $laporanService)
    {
        $this->laporanService = $laporanService;
    }

    /**
     * Menampilkan dashboard kinerja target pensertifikatan tanah tahunan.
     */
    public function index(Request $request): View
    {
        $tahun = (int) $request->input('tahun', date('Y'));
        $opdId = $request->filled('opd_id') ? (int) $request->input('opd_id') : null;
        $statusCapaian = $request->input('status_capaian', '');
        $search = trim((string) $request->input('search', ''));

        $availableYears = range(date('Y') - 2, date('Y') + 4);
        $opdList = OpdSipat::where('aktif', 1)->orderBy('nama', 'asc')->get();

        // Query Target Tahunan
        $targetQuery = SipatTargetSertifikat::with([
            'asetTanah.opdSipat',
            'asetTanah.latestProses.statusProses',
        ])->where('tahun', $tahun);

        if ($opdId) {
            $targetQuery->whereHas('asetTanah', function ($q) use ($opdId) {
                $q->where('opd_id', $opdId);
            });
        }

        if (!empty($search)) {
            $targetQuery->where(function ($q) use ($search) {
                $q->where('keterangan', 'LIKE', "%{$search}%")
                  ->orWhereHas('asetTanah', function ($q2) use ($search) {
                      $q2->where('kode_aset', 'LIKE', "%{$search}%")
                        ->orWhere('nama_aset', 'LIKE', "%{$search}%")
                        ->orWhere('peruntukan', 'LIKE', "%{$search}%")
                        ->orWhere('alamat', 'LIKE', "%{$search}%");
                  });
            });
        }

        $targets = $targetQuery->get();

        // Hitung Kinerja Keseluruhan (sebelum filter status_capaian)
        $totalTarget = $targets->count();
        $totalRealisasi = 0;
        $totalProses = 0;

        $mappedItems = $targets->map(function ($t) use (&$totalRealisasi, &$totalProses) {
            $aset = $t->asetTanah;
            $latestStatus = $aset?->latestProses?->statusProses;
            $statusName = $latestStatus?->nama_status ?? 'Belum Diurus';
            $category = strtolower(trim($latestStatus?->kategori ?? ''));

            if (empty($category)) {
                $norm = strtolower($statusName);
                if (str_contains($norm, 'terbit') || str_contains($norm, 'selesai') || ($statusName !== 'Belum Diurus' && str_contains($norm, 'sertifikat') && !str_contains($norm, 'proses'))) {
                    $category = 'bersertifikat';
                }
            }

            $isAchieved = ($category === 'bersertifikat');
            if ($isAchieved) {
                $totalRealisasi++;
            } else {
                $totalProses++;
            }

            $t->computed_status_name = $statusName;
            $t->computed_category = $category;
            $t->is_achieved = $isAchieved;

            return $t;
        });

        // Filter berdasarkan status capaian jika dipilih
        if ($statusCapaian === 'tercapai') {
            $targetItems = $mappedItems->filter(fn($t) => $t->is_achieved)->values();
        } elseif ($statusCapaian === 'proses') {
            $targetItems = $mappedItems->filter(fn($t) => !$t->is_achieved)->values();
        } elseif ($statusCapaian === 'belum_diurus') {
            $targetItems = $mappedItems->filter(fn($t) => $t->computed_status_name === 'Belum Diurus')->values();
        } else {
            $targetItems = $mappedItems;
        }

        $persentaseCapaian = $totalTarget > 0 ? round(($totalRealisasi / $totalTarget) * 100, 1) : 0;

        if ($persentaseCapaian >= 80) {
            $progressColor = 'success';
            $progressBadge = 'Sangat Baik';
        } elseif ($persentaseCapaian >= 50) {
            $progressColor = 'warning';
            $progressBadge = 'Cukup / Memuaskan';
        } else {
            $progressColor = 'danger';
            $progressBadge = 'Perlu Perhatian Khusus';
        }

        // Summary per OPD
        $opdSummaries = [];
        foreach ($mappedItems as $t) {
            $opdObj = $t->opdSipat ?? $t->asetTanah?->opdSipat;
            $opdNama = $opdObj?->nama ?? $t->asetTanah?->opd ?? 'Lainnya / Belum Ditentukan';
            
            if (!isset($opdSummaries[$opdNama])) {
                $opdSummaries[$opdNama] = [
                    'nama' => $opdNama,
                    'total' => 0,
                    'realisasi' => 0,
                    'proses' => 0,
                ];
            }
            $opdSummaries[$opdNama]['total']++;
            if ($t->is_achieved) {
                $opdSummaries[$opdNama]['realisasi']++;
            } else {
                $opdSummaries[$opdNama]['proses']++;
            }
        }

        foreach ($opdSummaries as $k => $sum) {
            $pct = $sum['total'] > 0 ? round(($sum['realisasi'] / $sum['total']) * 100, 1) : 0;
            $opdSummaries[$k]['persentase'] = $pct;
            if ($pct >= 80) {
                $opdSummaries[$k]['badge_class'] = 'bg-success';
                $opdSummaries[$k]['status_label'] = 'Optimal';
            } elseif ($pct >= 50) {
                $opdSummaries[$k]['badge_class'] = 'bg-warning text-dark';
                $opdSummaries[$k]['status_label'] = 'Sedang';
            } else {
                $opdSummaries[$k]['badge_class'] = 'bg-danger';
                $opdSummaries[$k]['status_label'] = 'Rendah';
            }
        }
        usort($opdSummaries, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        // Ambil ID aset yang sudah terdaftar di target tahun ini
        $existingAsetIds = SipatTargetSertifikat::where('tahun', $tahun)->pluck('aset_tanah_id')->toArray();

        // Ambil daftar aset calon target (KIB A yang belum masuk target tahun ini)
        $candidateAsets = AsetTanah::with(['opdSipat', 'latestProses.statusProses'])
            ->whereNotIn('id_aset', $existingAsetIds)
            ->orderBy('nama_aset', 'asc')
            ->get();

        return view('sipat.target_sertifikat.index', compact(
            'tahun',
            'opdId',
            'statusCapaian',
            'search',
            'availableYears',
            'opdList',
            'targetItems',
            'totalTarget',
            'totalRealisasi',
            'totalProses',
            'persentaseCapaian',
            'progressColor',
            'progressBadge',
            'opdSummaries',
            'candidateAsets'
        ));
    }

    /**
     * Menyimpan penetapan target pensertifikatan baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tahun' => 'required|integer|min:2000|max:2100',
            'aset_ids' => 'required|array|min:1',
            'aset_ids.*' => 'required|integer|exists:aset_tanah,id_aset',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $tahun = (int) $validated['tahun'];
        $asetIds = $validated['aset_ids'];
        $keterangan = $validated['keterangan'] ?? null;
        $insertedCount = 0;

        foreach ($asetIds as $idAset) {
            $aset = AsetTanah::find($idAset);
            if (!$aset) continue;

            SipatTargetSertifikat::updateOrCreate(
                [
                    'tahun' => $tahun,
                    'aset_tanah_id' => $idAset,
                ],
                [
                    'target_jumlah' => 1,
                    'keterangan' => $keterangan,
                ]
            );
            $insertedCount++;
        }

        if (class_exists(Activity::class)) {
            Activity::logSipat("Menetapkan {$insertedCount} bidang tanah sebagai target pensertifikatan tahun {$tahun}", 'success');
        }

        return redirect()->route('sipat.target-pensertifikatan.index', ['tahun' => $tahun])
            ->with('success', "Berhasil menambahkan {$insertedCount} bidang tanah ke dalam Target Pensertifikatan Tahun {$tahun}.");
    }

    /**
     * Memperbarui tahun atau keterangan target pensertifikatan.
     */
    public function update(Request $request, SipatTargetSertifikat $target): RedirectResponse
    {
        $validated = $request->validate([
            'tahun' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
                Rule::unique('sipat_target_sertifikat', 'tahun')
                    ->where('aset_tanah_id', $target->aset_tanah_id)
                    ->ignore($target->id),
            ],
            'keterangan' => 'nullable|string|max:500',
        ], [
            'tahun.unique' => 'Aset tanah ini sudah terdaftar sebagai target pada tahun tersebut.',
        ]);

        $tahunLama = $target->tahun;
        $tahunBaru = (int) $validated['tahun'];
        $namaAset = $target->asetTanah?->nama_aset ?? 'Aset';

        $target->update([
            'tahun' => $tahunBaru,
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        if (class_exists(Activity::class)) {
            $pesanLog = $tahunLama !== $tahunBaru
                ? "Memindahkan target pensertifikatan aset '{$namaAset}' dari tahun {$tahunLama} ke tahun {$tahunBaru}"
                : "Memperbarui keterangan target pensertifikatan aset '{$namaAset}' tahun {$tahunBaru}";
            Activity::logSipat($pesanLog, 'info');
        }

        return redirect()->route('sipat.target-pensertifikatan.index', ['tahun' => $tahunBaru])
            ->with('success', "Target pensertifikatan untuk aset '{$namaAset}' berhasil diperbarui.");
    }

    /**
     * Menghapus bidang tanah dari target tahunan.
     */
    public function destroy(SipatTargetSertifikat $target): RedirectResponse
    {
        $tahun = $target->tahun;
        $namaAset = $target->asetTanah?->nama_aset ?? 'Aset';
        $target->delete();

        if (class_exists(Activity::class)) {
            Activity::logSipat("Menghapus aset '{$namaAset}' dari target pensertifikatan tahun {$tahun}", 'warning');
        }

        return redirect()->route('sipat.target-pensertifikatan.index', ['tahun' => $tahun])
            ->with('success', "Aset tanah '{$namaAset}' berhasil dihapus dari daftar target tahun {$tahun}.");
    }

    /**
     * Mengunduh laporan target & realisasi dalam format Excel (.xlsx).
     */
    public function exportExcel(Request $request)
    {
        $tahun = (int) $request->input('tahun', date('Y'));
        $opdId = $request->filled('opd_id') ? (int) $request->input('opd_id') : null;
        $statusCapaian = (string) $request->input('status_capaian', '');
        $search = (string) $request->input('search', '');

        $fileName = 'Target_Pensertifikatan_SIPAT_' . $tahun . '_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new TargetSertifikatExport($tahun, $opdId, $statusCapaian, $search), $fileName);
    }

    /**
     * Mencetak laporan kinerja target pensertifikatan dalam format PDF (.pdf).
     */
    public function exportPdf(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        $tahun = (int) $request->input('tahun', date('Y'));
        $opdId = $request->filled('opd_id') ? (int) $request->input('opd_id') : null;
        $statusCapaian = $request->input('status_capaian', '');
        $search = trim((string) $request->input('search', ''));

        $kop = $this->laporanService->getKopSettings();

        $targetQuery = SipatTargetSertifikat::with([
            'asetTanah.opdSipat',
            'asetTanah.latestProses.statusProses',
        ])->where('tahun', $tahun);

        if ($opdId) {
            $targetQuery->whereHas('asetTanah', function ($q) use ($opdId) {
                $q->where('opd_id', $opdId);
            });
        }

        if (!empty($search)) {
            $targetQuery->where(function ($q) use ($search) {
                $q->where('keterangan', 'LIKE', "%{$search}%")
                  ->orWhereHas('asetTanah', function ($q2) use ($search) {
                      $q2->where('kode_aset', 'LIKE', "%{$search}%")
                        ->orWhere('nama_aset', 'LIKE', "%{$search}%")
                        ->orWhere('peruntukan', 'LIKE', "%{$search}%")
                        ->orWhere('alamat', 'LIKE', "%{$search}%");
                  });
            });
        }

        $targets = $targetQuery->get();
        $totalTarget = $targets->count();
        $totalRealisasi = 0;
        $totalProses = 0;

        $mappedItems = $targets->map(function ($t) use (&$totalRealisasi, &$totalProses) {
            $aset = $t->asetTanah;
            $latestStatus = $aset?->latestProses?->statusProses;
            $statusName = $latestStatus?->nama_status ?? 'Belum Diurus';
            $category = strtolower(trim($latestStatus?->kategori ?? ''));

            if (empty($category)) {
                $norm = strtolower($statusName);
                if (str_contains($norm, 'terbit') || str_contains($norm, 'selesai') || ($statusName !== 'Belum Diurus' && str_contains($norm, 'sertifikat') && !str_contains($norm, 'proses'))) {
                    $category = 'bersertifikat';
                }
            }

            $isAchieved = ($category === 'bersertifikat');
            if ($isAchieved) {
                $totalRealisasi++;
            } else {
                $totalProses++;
            }

            $t->computed_status_name = $statusName;
            $t->is_achieved = $isAchieved;

            return $t;
        });

        if ($statusCapaian === 'tercapai') {
            $targetItems = $mappedItems->filter(fn($t) => $t->is_achieved)->values();
        } elseif ($statusCapaian === 'proses') {
            $targetItems = $mappedItems->filter(fn($t) => !$t->is_achieved)->values();
        } elseif ($statusCapaian === 'belum_diurus') {
            $targetItems = $mappedItems->filter(fn($t) => $t->computed_status_name === 'Belum Diurus')->values();
        } else {
            $targetItems = $mappedItems;
        }

        $persentaseCapaian = $totalTarget > 0 ? round(($totalRealisasi / $totalTarget) * 100, 1) : 0;

        $selectedTitle = "LAPORAN KINERJA TARGET PENSERTIFIKATAN TANAH TAHUN {$tahun}";

        $pdfView = view('sipat.target_sertifikat.pdf', compact(
            'tahun',
            'kop',
            'selectedTitle',
            'targetItems',
            'totalTarget',
            'totalRealisasi',
            'totalProses',
            'persentaseCapaian'
        ))->render();

        if (!class_exists(\Mpdf\Mpdf::class)) {
            return response($pdfView);
        }

        $pdfTempDir = storage_path('framework/cache/mpdf');
        if (!is_dir($pdfTempDir)) {
            @mkdir($pdfTempDir, 0775, true);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_top' => 10,
            'margin_bottom' => 24,
            'margin_left' => 10,
            'margin_right' => 10,
            'tempDir' => $pdfTempDir,
        ]);

        $mpdf->SetTitle($selectedTitle);
        $mpdf->SetHTMLFooter('<div style="font-size:9pt;color:#64748b;border-top:1px solid #dbe3ef;padding-top:6px;text-align:center;">Halaman {PAGENO} dari {nbpg} | ' . htmlspecialchars((string) ($kop['kop_footer'] ?? ''), ENT_QUOTES, 'UTF-8') . '</div>');
        $mpdf->WriteHTML($pdfView);

        $filename = 'Laporan_Target_Pensertifikatan_' . $tahun . '_' . date('Ymd_His') . '.pdf';

        return response($mpdf->Output($filename, 'S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }
}
