<?php

namespace App\Services;

use App\Reports\ReportRegistry;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Layanan khusus untuk agregasi statistik dan orkestrasi Laporan di E-RANDIS.
 * 
 * Bertindak sebagai perantara yang menanyakan tipe strategi ke Registry
 * dan memproses kueri data terpaginasi maupun ringkasan agregat.
 */
class ReportService
{
    /**
     * Instansi Registry pendaftar strategi laporan.
     */
    protected ReportRegistry $registry;

    /**
     * Injeksi dependensi ReportRegistry secara otomatis.
     */
    public function __construct(ReportRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Mendapatkan ringkasan statistik ringkas kendaraan dinas dalam satu kueri agregasi tunggal.
     *
     * Data di-cache selama 5 menit (300 detik) untuk performa optimal.
     *
     * @param int|null $opdId ID instansi OPD (null untuk data global)
     * @return array{total_unit: int, layak_jalan: int, surat_mati: int}
     */
    public function getQuickSummary(?int $opdId = null): array
    {
        $user = auth()->user();
        $role = $user ? $user->role->value : 'guest';
        
        // Buat cache key yang aman berdasarkan role dan scope OPD untuk mencegah pencemaran lintas-role
        $scopeKey = $opdId ? "opd_{$opdId}" : ($user && $user->role->value === 'opd' ? 'opd_null' : 'global');
        $cacheKey = "reports.summary.{$role}.{$scopeKey}";

        return cache()->remember($cacheKey, 300, function () use ($opdId) {
            $query = \App\Models\Vehicle::query();

            if ($opdId) {
                $query->where('opd_id', $opdId);
            }

            // Kueri Agregasi Tunggal (Single Raw Query) teroptimasi B-Tree
            $result = $query->selectRaw("
                COUNT(*) as total_unit,
                SUM(CASE WHEN kondisi = 'Baik' THEN 1 ELSE 0 END) as layak_jalan,
                SUM(CASE WHEN stnk_ada = 'Tidak' OR (tgl_stnk IS NOT NULL AND tgl_stnk < CURRENT_DATE) THEN 1 ELSE 0 END) as surat_mati
            ")->first();

            return [
                'total_unit'  => (int) ($result->total_unit ?? 0),
                'layak_jalan' => (int) ($result->layak_jalan ?? 0),
                'surat_mati'  => (int) ($result->surat_mati ?? 0),
            ];
        });
    }

    /**
     * Membersihkan cache summary laporan secara terarah.
     * 
     * Membantu memicu kesegaran data baru jika terjadi modifikasi di kendaraan dinas.
     *
     * @param int|null $opdId ID OPD terdampak
     * @param int|null $oldOpdId ID OPD lama jika terjadi perpindahan instansi
     * @param bool $invalidateAllOpd Jika true, hapus semua cache ringkasan seluruh OPD
     * @return void
     */
    public function invalidateSummaryCache(?int $opdId = null, ?int $oldOpdId = null, bool $invalidateAllOpd = false): void
    {
        cache()->forget('reports.summary.superadmin.global');
        cache()->forget('reports.summary.admin.global');
        cache()->forget('reports.summary.guest.global');
        cache()->forget('reports.summary.opd.opd_null');

        if ($opdId) {
            cache()->forget("reports.summary.opd.opd_{$opdId}");
        }
        if ($oldOpdId && $oldOpdId !== $opdId) {
            cache()->forget("reports.summary.opd.opd_{$oldOpdId}");
        }
        if ($invalidateAllOpd) {
            $opdIds = \App\Models\Opd::pluck('id');
            foreach ($opdIds as $id) {
                cache()->forget("reports.summary.opd.opd_{$id}");
            }
        }
    }

    /**
     * Menghasilkan data pratinjau (preview) laporan terpaginasi secara dinamis.
     *
     * Sesuai keputusan PM, pratinjau data ini TIDAK di-cache untuk menjamin kesegaran data filter.
     *
     * @param array<string, mixed> $filters Filter pencarian ter-validasi
     * @return array{data: LengthAwarePaginator, headers: array<string, string>, type: string}
     */
    public function generatePreview(array $filters): array
    {
        $type = $filters['type'] ?? 'status';
        
        // Selesaikan strategi berdasarkan tipe laporan via registry
        $strategy = $this->registry->resolve($type);
        
        // Dapatkan query builder dari kelas strategi
        $queryBuilder = $strategy->query($filters);

        // Eksekusi query dengan paginasi (15 baris per halaman)
        // Terapkan pengurutan dinamis secara aman
        $queryBuilder = $this->applySorting($queryBuilder, $filters);

        // Eksekusi query dengan paginasi (15 baris per halaman)
        $paginatedData = $queryBuilder->paginate(15)->withQueryString();

        // Jalankan pengayaan data in-memory jika strategi mengimplementasikan PostProcessesReportRows
        if ($strategy instanceof \App\Reports\Contracts\PostProcessesReportRows) {
            $refQuery = method_exists($strategy, 'referenceQuery')
                ? $strategy->referenceQuery($filters)
                : $strategy->query($filters);
            $refQuery = $this->applySorting($refQuery, $filters);
            $referenceRows = $refQuery->get();

            $strategy->postProcess($paginatedData->getCollection(), $referenceRows);
        }

        return [
            'data'       => $paginatedData,
            'headers'    => $strategy->headers(),
            'type'       => $type,
            'sort_by'    => $filters['sort_by'] ?? null,
            'sort_order' => $filters['sort_order'] ?? 'asc',
        ];
    }

    /**
     * Menerapkan pengurutan (sorting) dinamis berbasis whitelist pada Query Builder Laporan.
     *
     * @param \Illuminate\Database\Eloquent\Builder $queryBuilder
     * @param array<string, mixed> $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applySorting(\Illuminate\Database\Eloquent\Builder $queryBuilder, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $sortBy = $filters['sort_by'] ?? null;
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $allowedSorts = [
            'no_polisi', 'nomor_register', 'merk', 'tipe', 'status', 'kondisi', 
            'opd', 'pemegang', 'nilai_perolehan', 'stnk_ada', 'tgl_stnk', 
            'bpkb_ada', 'no_mesin', 'no_rangka'
        ];

        if ($sortBy && in_array($sortBy, $allowedSorts)) {
            $queryBuilder->reorder($sortBy, $sortOrder);
        }

        return $queryBuilder;
    }
}
