<?php

namespace App\Imports;

use App\Models\AsetTanah;
use App\Models\ProsesAset;
use App\Models\StatusProses;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Class untuk Pembaruan Status & Data Sertifikat (Porting dari SIPAT importStatusProcess)
 */
class SipatUpdateImport
{
    private int $updatedCount = 0;
    private int $notFoundCount = 0;
    private array $statusMap = [];

    public function __construct()
    {
        // Pre-load status map (case-insensitive)
        $statuses = StatusProses::all();
        foreach ($statuses as $st) {
            $nameKey = strtolower(trim($st->nama_status));
            $this->statusMap[$nameKey] = (int)$st->id_status;
        }
    }

    /**
     * Memproses baris data (dukungan untuk file ber-header maupun tanpa header)
     */
    public function processRows(array $rows): array
    {
        if (empty($rows)) {
            return ['updated' => 0, 'notFound' => 0];
        }

        // Auto-detect apakah baris 1 adalah header atau langsung data NIBAR
        $firstRow = $rows[0];
        $col0 = strtolower(trim((string)($firstRow[0] ?? $firstRow['nibar'] ?? $firstRow['kode_aset'] ?? '')));
        $col1 = strtolower(trim((string)($firstRow[1] ?? $firstRow['status_proses'] ?? $firstRow['status'] ?? '')));

        $hasHeader = ($col0 === 'nibar' || $col0 === 'kode_aset' || $col0 === 'kode' || $col1 === 'status_proses' || $col1 === 'status');

        if ($hasHeader) {
            $header = array_shift($rows);
            $header = array_map(static fn ($h) => strtolower(trim((string) $h)), $header);

            $nibarIndex = array_search('nibar', $header, true);
            if ($nibarIndex === false) {
                $nibarIndex = array_search('kode_aset', $header, true);
            }
            if ($nibarIndex === false) {
                $nibarIndex = array_search('kode', $header, true);
            }

            $statusIndex = array_search('status_proses', $header, true);
            if ($statusIndex === false) {
                $statusIndex = array_search('status', $header, true);
            }

            $tglProsesIndex = array_search('tanggal_proses', $header, true);
            if ($tglProsesIndex === false) {
                $tglProsesIndex = array_search('tanggal', $header, true);
            }
            $tglMulaiIndex = array_search('tgl_mulai', $header, true);
            $tglSelesaiIndex = array_search('tgl_selesai', $header, true);
            $keteranganIndex = array_search('keterangan', $header, true);
            $sertifikatAdaIndex = array_search('sertifikat_ada', $header, true);
        } else {
            // Tanpa header: Kolom A = NIBAR, Kolom B = Status Proses, C = Tanggal Proses, D = Keterangan
            $nibarIndex = 0;
            $statusIndex = 1;
            $tglProsesIndex = 2;
            $tglMulaiIndex = 2;
            $tglSelesaiIndex = false;
            $keteranganIndex = 3;
            $sertifikatAdaIndex = false;
        }

        foreach ($rows as $row) {
            // Filter baris kosong
            if (count(array_filter($row, static fn ($v) => trim((string)$v) !== '')) === 0) {
                continue;
            }

            $nibarVal = $nibarIndex !== false && isset($row[$nibarIndex]) ? trim((string)$row[$nibarIndex]) : '';
            $statusVal = $statusIndex !== false && isset($row[$statusIndex]) ? trim((string)$row[$statusIndex]) : '';

            if ($nibarVal === '') {
                $this->notFoundCount++;
                continue;
            }

            $aset = AsetTanah::where('kode_aset', $nibarVal)->first();
            if (!$aset) {
                $this->notFoundCount++;
                continue;
            }

            $hasAction = false;

            // Update status proses pensertifikatan jika diisi
            if ($statusVal !== '') {
                $statusId = $this->resolveStatusId($statusVal);
                if ($statusId) {
                    $tglProsesVal = $tglProsesIndex !== false && isset($row[$tglProsesIndex]) ? $this->parseDate($row[$tglProsesIndex]) : null;
                    if (!$tglProsesVal && $tglMulaiIndex !== false && isset($row[$tglMulaiIndex])) {
                        $tglProsesVal = $this->parseDate($row[$tglMulaiIndex]);
                    }
                    $tglEff = $tglProsesVal ?: date('Y-m-d');
                    $tglSelesai = $tglSelesaiIndex !== false && isset($row[$tglSelesaiIndex]) ? $this->parseDate($row[$tglSelesaiIndex]) : null;
                    $keterangan = $keteranganIndex !== false && isset($row[$keteranganIndex]) ? trim((string)$row[$keteranganIndex]) : 'Update status via Excel (SIPAT)';

                    ProsesAset::create([
                        'id_aset'        => $aset->id_aset,
                        'id_status'      => $statusId,
                        'tanggal_proses' => $tglEff,
                        'tgl_mulai'      => $tglEff,
                        'tgl_selesai'    => $tglSelesai ?: null,
                        'keterangan'     => $keterangan,
                        'durasi_hari'    => null,
                    ]);
                    $hasAction = true;
                }
            }

            // Update status fisik sertifikat jika disediakan
            if ($sertifikatAdaIndex !== false && isset($row[$sertifikatAdaIndex]) && trim((string)$row[$sertifikatAdaIndex]) !== '') {
                $sVal = strtolower(trim((string)$row[$sertifikatAdaIndex]));
                $ada = in_array($sVal, ['1', 'ya', 'ada', 'true', 'yes'], true) ? 1 : 0;

                DB::table('pengamanan_fisik')->updateOrInsert(
                    ['id_aset' => $aset->id_aset],
                    [
                        'sertifikat_ada' => $ada,
                        'tgl_cek'        => date('Y-m-d'),
                        'catatan'        => 'Diperbarui via impor kolektif',
                        'updated_at'     => now(),
                    ]
                );
                $hasAction = true;
            }

            if ($hasAction) {
                $this->updatedCount++;
            } else {
                $this->notFoundCount++;
            }
        }

        return [
            'updated' => $this->updatedCount,
            'notFound' => $this->notFoundCount,
        ];
    }

    /**
     * Pencocokan status presisi & pencocokan parsial (fuzzy matching)
     */
    private function resolveStatusId(string $statusVal): ?int
    {
        $cleanVal = strtolower(trim($statusVal));
        if ($cleanVal === '') return null;

        // 1. Presisi langsung
        if (isset($this->statusMap[$cleanVal])) {
            return $this->statusMap[$cleanVal];
        }

        // 2. Pencocokan kata kunci/sub-string
        foreach ($this->statusMap as $stName => $stId) {
            if (str_contains($stName, $cleanVal) || str_contains($cleanVal, $stName)) {
                return $stId;
            }
        }

        // 3. Keyword heuristic fallback
        if (str_contains($cleanVal, 'sertifikat') || str_contains($cleanVal, 'sertipikat')) {
            foreach ($this->statusMap as $stName => $stId) {
                if (str_contains($stName, 'sertifikat')) return $stId;
            }
        }

        if (str_contains($cleanVal, 'pengukuran')) {
            foreach ($this->statusMap as $stName => $stId) {
                if (str_contains($stName, 'pengukuran')) return $stId;
            }
        }

        if (str_contains($cleanVal, 'pertek')) {
            foreach ($this->statusMap as $stName => $stId) {
                if (str_contains($stName, 'pertek')) return $stId;
            }
        }

        if (str_contains($cleanVal, 'kkpr') || str_contains($cleanVal, 'pkkpr')) {
            foreach ($this->statusMap as $stName => $stId) {
                if (str_contains($stName, 'pkkpr') || str_contains($stName, 'kkpr')) return $stId;
            }
        }

        return null;
    }

    private function parseDate($value)
    {
        $value = trim((string)$value);
        if ($value === '' || $value === '-') return null;

        try {
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value)->format('Y-m-d');
            }
            if (str_contains($value, '/')) {
                $dt = \DateTime::createFromFormat('d/m/Y', $value);
                if ($dt) return $dt->format('Y-m-d');
            }
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
