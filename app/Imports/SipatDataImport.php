<?php

namespace App\Imports;

use App\Models\AsetTanah;
use App\Models\ProsesAset;
use App\Models\StatusProses;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Class untuk Mengimport Data Sertifikat Tanah Baru (SIPAT)
 */
class SipatDataImport implements ToModel, WithHeadingRow, WithChunkReading
{
    private $importedCount = 0;
    private $skippedCount = 0;
    private $statusMap = [];

    public function __construct()
    {
        // Cache master status proses
        $statuses = StatusProses::all();
        foreach ($statuses as $st) {
            $this->statusMap[strtolower(trim($st->nama_status))] = $st->id_status;
        }
    }

    public function model(array $row)
    {
        // Ambil Kode Aset / NIBAR
        $kodeAset = trim((string)($row['kode_aset'] ?? $row['nibar'] ?? $row['kode_barang'] ?? ''));
        $namaAset = trim((string)($row['nama_aset'] ?? $row['nama_barang'] ?? $row['nama'] ?? ''));

        if (empty($kodeAset) || empty($namaAset)) {
            $this->skippedCount++;
            return null;
        }

        // Cek duplikasi kode aset
        if (AsetTanah::where('kode_aset', $kodeAset)->exists()) {
            $this->skippedCount++;
            return null;
        }

        $luas = isset($row['luas']) && is_numeric($row['luas']) ? (float)$row['luas'] : null;
        $hargaPerolehan = isset($row['harga_perolehan']) && is_numeric($row['harga_perolehan']) ? (float)$row['harga_perolehan'] : null;
        $tglPerolehan = $this->parseDate($row['tanggal_perolehan'] ?? $row['tgl_perolehan'] ?? null);

        $aset = AsetTanah::create([
            'kode_aset'         => $kodeAset,
            'nama_aset'         => $namaAset,
            'peruntukan'        => $row['peruntukan'] ?? null,
            'luas'              => $luas,
            'opd'               => $row['opd'] ?? $row['instansi'] ?? null,
            'alamat'            => $row['alamat'] ?? $row['lokasi'] ?? null,
            'lat'               => isset($row['lat']) && is_numeric($row['lat']) ? (float)$row['lat'] : null,
            'lng'               => isset($row['lng']) && is_numeric($row['lng']) ? (float)$row['lng'] : null,
            'dasar_perolehan'   => $row['dasar_perolehan'] ?? null,
            'harga_perolehan'   => $hargaPerolehan,
            'tanggal_perolehan' => $tglPerolehan,
            'keterangan'        => $row['keterangan'] ?? null,
        ]);

        // Simpan status awal jika ada kolom status_proses
        $statusInput = trim((string)($row['status_proses'] ?? $row['status'] ?? ''));
        if (!empty($statusInput)) {
            $statusKey = strtolower($statusInput);
            $statusId = $this->statusMap[$statusKey] ?? null;

            if (!$statusId) {
                // Try keyword match
                foreach ($this->statusMap as $stName => $stId) {
                    if (str_contains($stName, $statusKey) || str_contains($statusKey, $stName)) {
                        $statusId = $stId;
                        break;
                    }
                }
            }

            if ($statusId) {
                ProsesAset::create([
                    'id_aset'    => $aset->id_aset,
                    'id_status'  => $statusId,
                    'tgl_mulai'  => $tglPerolehan ?? date('Y-m-d'),
                    'keterangan' => 'Status awal dari impor data sertifikat'
                ]);
            }
        }

        $this->importedCount++;
        return null;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    private function parseDate($value)
    {
        if (empty($value)) return null;
        try {
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            }
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
