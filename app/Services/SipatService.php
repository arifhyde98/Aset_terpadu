<?php

namespace App\Services;

use App\Models\AsetTanah;
use App\Models\OpdSipat;
use App\Models\ProsesAset;
use App\Models\StatusProses;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;

class SipatService
{
    /**
     * Menghitung durasi hari antara dua tanggal.
     */
    public function calculateDuration(?string $tglMulai, ?string $tglSelesai): ?int
    {
        if (!empty($tglMulai) && !empty($tglSelesai)) {
            $durasi = (int) floor((strtotime($tglSelesai) - strtotime($tglMulai)) / 86400);
            return $durasi >= 0 ? $durasi : null;
        }
        return null;
    }

    /**
     * Memproses bulk update status untuk aset tanah.
     * 
     * @return int Jumlah aset yang berhasil diperbarui
     */
    public function bulkUpdateStatus(
        array $asetIds, 
        string $nibarListRaw, 
        int $idStatus, 
        ?string $tglMulai, 
        ?string $tglSelesai, 
        ?string $keterangan
    ): int {
        if (trim($nibarListRaw) !== '') {
            $nibarItems = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $nibarListRaw))));
            if (!empty($nibarItems)) {
                $rows = DB::table('aset_tanah')
                    ->select('id_aset')
                    ->whereIn('kode_aset', $nibarItems)
                    ->get();
                foreach ($rows as $r) {
                    $asetIds[] = (int) $r->id_aset;
                }
            }
        }

        $asetIds = array_values(array_unique(array_filter(array_map('intval', $asetIds))));

        if (empty($asetIds)) {
            return 0; // Tidak ada aset yang diproses
        }

        $durasi = $this->calculateDuration($tglMulai, $tglSelesai);
        $insertedCount = 0;

        foreach ($asetIds as $idAset) {
            $idAset = (int) $idAset;
            if ($idAset <= 0) continue;

            ProsesAset::create([
                'id_aset'     => $idAset,
                'id_status'   => $idStatus,
                'tgl_mulai'   => $tglMulai ?: null,
                'tgl_selesai' => $tglSelesai ?: null,
                'keterangan'  => $keterangan ?: 'Update status massal',
                'durasi_hari' => $durasi,
            ]);
            $insertedCount++;
        }

        return $insertedCount;
    }

    /**
     * Menghapus cache dashboard SIPAT.
     */
    public function invalidateDashboardCache(): void
    {
        Cache::forget('sipat_dashboard_stats');
    }

    /**
     * Mengambil statistik dashboard, dengan caching 10 menit (otomatis reset jika ada update).
     */
    public function getDashboardStats(): array
    {
        return Cache::remember('sipat_dashboard_stats', 600, function () {
            return $this->computeDashboardStats();
        });
    }

    private function computeDashboardStats(): array
    {
        $totalAset = AsetTanah::count();
        $totalLuas = AsetTanah::sum('luas');
        $statusMaster = StatusProses::orderBy('urutan', 'asc')->get();

        $statusMap = [];
        $statusCategoryMap = [];
        foreach ($statusMaster as $sm) {
            $statusMap[$sm->id_status] = trim($sm->nama_status);
            $statusCategoryMap[$sm->id_status] = trim($sm->kategori);
        }

        // Fetch latest process per asset with Eager Loading concepts / raw optimized
        $latestRows = DB::select("
            SELECT p1.id_aset, p1.id_status, sp.nama_status, sp.kategori
            FROM proses_aset p1
            JOIN (
                SELECT id_aset, MAX(id_proses) AS max_id
                FROM proses_aset
                GROUP BY id_aset
            ) p2 ON p1.id_aset = p2.id_aset AND p1.id_proses = p2.max_id
            LEFT JOIN status_proses sp ON sp.id_status = p1.id_status
        ");

        $latestMap = [];
        foreach ($latestRows as $row) {
            $latestMap[$row->id_aset] = [
                'id_status'   => (int) $row->id_status,
                'nama_status' => trim((string) $row->nama_status),
                'kategori'    => trim((string) $row->kategori),
            ];
        }

        $asetRows = AsetTanah::with('opdSipat')->select('id_aset', 'opd', 'opd_id')->get();
        $asetBersertifikat = 0;
        $asetKendala       = 0;
        $asetProses        = 0;
        $asetBelumDiurus   = 0;

        $statusBreakdowns = [
            'bersertifikat' => [],
            'proses'        => [],
            'kendala'       => [],
            'belum_diurus'  => [],
        ];

        $statusCounts = [];
        foreach ($statusMaster as $status) {
            $name = trim($status->nama_status);
            if ($name !== '') {
                $statusCounts[$name] = 0;
            }
        }
        if (!array_key_exists('Belum Diurus', $statusCounts)) {
            $statusCounts['Belum Diurus'] = 0;
        }

        $opdStats = [];

        foreach ($asetRows as $aset) {
            $idAset = $aset->id_aset;
            $latest = $latestMap[$idAset] ?? null;

            $statusName = $latest['nama_status'] ?? '';
            $statusId   = $latest['id_status'] ?? 0;
            if ($statusName === '') {
                $statusName = 'Belum Diurus';
            }

            if (!array_key_exists($statusName, $statusCounts)) {
                $statusCounts[$statusName] = 0;
            }
            $statusCounts[$statusName]++;

            $explicitCategory = $statusCategoryMap[$statusId] ?? ($latest['kategori'] ?? null);
            $category = $this->getStatusCategory($statusName, $explicitCategory);
            
            $statusBreakdowns[$category][$statusName] = ($statusBreakdowns[$category][$statusName] ?? 0) + 1;

            if ($category === 'kendala') {
                $asetKendala++;
            } elseif ($category === 'bersertifikat') {
                $asetBersertifikat++;
            } elseif ($category === 'belum_diurus') {
                $asetBelumDiurus++;
            } else {
                $asetProses++;
            }

            $opdLabel = $aset->opdSipat->nama ?? trim((string) $aset->opd);
            $opdKey = $opdLabel !== '' ? $opdLabel : 'Tidak Diketahui';
            $opdStats[$opdKey] = ($opdStats[$opdKey] ?? 0) + 1;
        }

        arsort($opdStats);
        $topOpdStats = array_slice($opdStats, 0, 5, true);

        foreach ($statusBreakdowns as $cat => &$items) {
            arsort($items);
        }

        $pctBersertifikat = $totalAset > 0 ? round(($asetBersertifikat / $totalAset) * 100, 1) : 0;
        $pctProses        = $totalAset > 0 ? round(($asetProses / $totalAset) * 100, 1) : 0;
        $pctKendala       = $totalAset > 0 ? round(($asetKendala / $totalAset) * 100, 1) : 0;
        $pctBelumDiurus   = $totalAset > 0 ? round(($asetBelumDiurus / $totalAset) * 100, 1) : 0;

        // Monthly trends
        $year = (int) date('Y');
        $chartSelesai = array_fill(0, 12, 0);
        $chartProses  = array_fill(0, 12, 0);
        $chartBelum   = array_fill(0, 12, 0);

        $allAsetsForChart = DB::table('aset_tanah')->select('id_aset', 'created_at')->get();
        $allProses = DB::table('proses_aset')->select('id_aset', 'id_status', 'tgl_mulai', 'created_at')->orderBy('tgl_mulai', 'asc')->get();

        $prosesByAset = [];
        foreach ($allProses as $p) {
            $prosesByAset[$p->id_aset][] = $p;
        }

        for ($m = 1; $m <= 12; $m++) {
            $endOfMonth = date("Y-m-t 23:59:59", strtotime("$year-$m-01"));
            
            foreach ($allAsetsForChart as $asetChart) {
                $asetCreatedAt = $asetChart->created_at ?? date('Y-01-01');
                
                if ($asetCreatedAt > $endOfMonth) {
                    continue;
                }
                
                $currentStatusName = 'Belum Diurus';
                $explicitCat = 'belum_diurus';
                $asetIdChart = $asetChart->id_aset;
                
                if (isset($prosesByAset[$asetIdChart])) {
                    $latestProsesInMonth = null;
                    foreach ($prosesByAset[$asetIdChart] as $p) {
                        $pDate = $p->tgl_mulai ?? $p->created_at;
                        if ($pDate <= $endOfMonth) {
                            $latestProsesInMonth = $p;
                        }
                    }
                    if ($latestProsesInMonth) {
                        $stId = (int) $latestProsesInMonth->id_status;
                        $currentStatusName = $statusMap[$stId] ?? 'Belum Diurus';
                        $explicitCat = $statusCategoryMap[$stId] ?? null;
                    }
                }
                
                $cat = $this->getStatusCategory($currentStatusName, $explicitCat);
                if ($cat === 'bersertifikat') {
                    $chartSelesai[$m - 1]++;
                } elseif ($cat === 'proses' || $cat === 'kendala') {
                    $chartProses[$m - 1]++;
                } else {
                    $chartBelum[$m - 1]++;
                }
            }
        }

        $recentLogs = [];
        if (Schema::hasTable('audit_logs')) {
            $recentLogs = DB::table('audit_logs')
                ->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')
                ->whereIn('audit_logs.entity', ['aset_tanah', 'proses_aset', 'status_proses', 'dokumen_aset', 'pengamanan_fisik', 'opd', 'users'])
                ->select('audit_logs.*', 'users.name as user_name')
                ->orderBy('audit_logs.id', 'desc')
                ->limit(5)
                ->get();
        }

        if (empty($recentLogs) || count($recentLogs) == 0) {
            $recentLogs = DB::table('proses_aset as p')
                ->join('status_proses as s', 'p.id_status', '=', 's.id_status')
                ->join('aset_tanah as a', 'p.id_aset', '=', 'a.id_aset')
                ->select(
                    'p.id_proses as id',
                    DB::raw("'update' as action"),
                    DB::raw("'proses_aset' as entity"),
                    'p.tgl_mulai as created_at',
                    's.nama_status',
                    'a.nama_aset',
                    DB::raw("COALESCE(o.nama, NULLIF(TRIM(a.opd), ''), 'Tidak Diketahui') as opd"),
                    DB::raw("'Administrator' as user_name")
                )
                ->leftJoin('opd as o', 'o.id', '=', 'a.opd_id')
                ->orderBy('p.id_proses', 'desc')
                ->limit(5)
                ->get();
        }

        return [
            'totalAset'         => $totalAset,
            'totalLuas'         => $totalLuas,
            'asetBersertifikat' => $asetBersertifikat,
            'asetKendala'       => $asetKendala,
            'asetProses'        => $asetProses,
            'asetBelumDiurus'   => $asetBelumDiurus,
            'pctBersertifikat'  => $pctBersertifikat,
            'pctProses'         => $pctProses,
            'pctKendala'        => $pctKendala,
            'pctBelumDiurus'    => $pctBelumDiurus,
            'opdStats'          => $topOpdStats,
            'statusCounts'      => $statusCounts,
            'statusBreakdowns'  => $statusBreakdowns,
            'recentLogs'        => $recentLogs,
            'chartSelesai'      => $chartSelesai,
            'chartProses'       => $chartProses,
            'chartBelum'        => $chartBelum,
            'chartYear'         => $year,
        ];
    }

    private function getStatusCategory(string $statusName, ?string $explicitCategory = null): string
    {
        if (!empty($explicitCategory)) {
            $cat = strtolower(trim($explicitCategory));
            if (in_array($cat, ['belum_diurus', 'proses', 'kendala', 'bersertifikat'], true)) {
                return $cat;
            }
        }

        $normalized = strtolower(trim($statusName));

        if ($normalized === '' || str_contains($normalized, 'belum') || str_contains($normalized, 'tanpa')) {
            return 'belum_diurus';
        }

        if (str_contains($normalized, 'kendala')
            || str_contains($normalized, 'sengketa')
            || str_contains($normalized, 'masalah')
            || str_contains($normalized, 'bermasalah')
            || str_contains($normalized, 'batal')
            || str_contains($normalized, 'ditolak')) {
            return 'kendala';
        }

        if (((str_contains($normalized, 'sertifikat') || str_contains($normalized, 'sertipikat')) && !str_contains($normalized, 'proses'))
            || str_contains($normalized, 'terbit sertifikat')
            || str_contains($normalized, 'terbit sertipikat')
            || $normalized === 'selesai') {
            return 'bersertifikat';
        }

        return 'proses';
    }
}
