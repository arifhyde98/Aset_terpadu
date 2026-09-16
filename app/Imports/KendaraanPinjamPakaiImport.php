<?php

namespace App\Imports;

use App\Models\KendaraanPinjamPakai;
use App\Models\Vehicle;
use App\Models\Opd;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Carbon\Carbon;

/**
 * Importer Semantik untuk Kendaraan Dinas Pinjam Pakai (eRANDIS)
 */
class KendaraanPinjamPakaiImport implements ToModel, WithHeadingRow, WithChunkReading
{
    private $importedCount = 0;
    private $skippedCount = 0;
    private $vehiclesMap = [];
    private $opdsMap = [];

    public function __construct()
    {
        // Cache data kendaraan untuk pencocokan semantik nopol
        $vehicles = Vehicle::select('id', 'no_polisi', 'opd_id')->get();
        foreach ($vehicles as $v) {
            $cleanNopol = $this->cleanNopol($v->no_polisi);
            if ($cleanNopol) {
                $this->vehiclesMap[$cleanNopol] = $v;
            }
        }

        // Cache OPD
        $opds = Opd::select('id', 'nama', 'singkatan')->get();
        foreach ($opds as $o) {
            $this->opdsMap[strtolower(trim($o->nama))] = $o->id;
            if ($o->singkatan) {
                $this->opdsMap[strtolower(trim($o->singkatan))] = $o->id;
            }
        }
    }

    public function model(array $row)
    {
        // Normalisasi Kunci Header Excel (Smart Semantic Mapping)
        $val = function(...$keys) use ($row) {
            foreach ($keys as $k) {
                if (isset($row[$k]) && !empty(trim((string)$row[$k]))) {
                    return trim((string)$row[$k]);
                }
            }
            return null;
        };

        $nopolInput = $val('no_polisi', 'nopol', 'plat', 'nomor_polisi', 'kendaraan');
        $namaInstansi = $val('nama_instansi_peminjam', 'instansi', 'nama_instansi', 'peminjam', 'lembaga');
        $namaPj = $val('nama_penanggung_jawab', 'penanggung_jawab', 'nama_pj', 'pj', 'nama');
        $nomorNppp = $val('nomor_nppp_bast', 'no_nppp', 'no_bast', 'nomor_dokumen', 'no_surat', 'nomor_nppp');

        // Wajib memiliki minimal Instansi Peminjam atau Penanggung Jawab & No. NPPP
        if (empty($namaInstansi) && empty($namaPj)) {
            $this->skippedCount++;
            return null;
        }

        // Pencocokan Kendaraan Dinas via Nopol
        $vehicleId = null;
        $opdId = null;
        if ($nopolInput) {
            $cleanInput = $this->cleanNopol($nopolInput);
            if (isset($this->vehiclesMap[$cleanInput])) {
                $matchedVehicle = $this->vehiclesMap[$cleanInput];
                $vehicleId = $matchedVehicle->id;
                $opdId = $matchedVehicle->opd_id;
            }
        }

        // Tanggal Parsing
        $tglMulaiStr = $val('tanggal_mulai', 'tgl_mulai', 'mulai');
        $tglSelesaiStr = $val('tanggal_selesai', 'tgl_selesai', 'selesai', 'tgl_akhir');
        $tglNpppStr = $val('tanggal_nppp_bast', 'tgl_nppp', 'tgl_bast', 'tgl_dokumen');

        $tglMulai = $this->parseDate($tglMulaiStr) ?: Carbon::now();
        $tglSelesai = $this->parseDate($tglSelesaiStr) ?: Carbon::now()->addYear();
        $tglNppp = $this->parseDate($tglNpppStr) ?: $tglMulai;

        // Kategori Peminjam
        $kategoriInput = $val('kategori_peminjam', 'kategori', 'jenis_peminjam');
        $kategori = 'Instansi Vertikal';
        if ($kategoriInput) {
            if (stripos($kategoriInput, 'opd') !== false) {
                $kategori = 'Antar OPD';
            } elseif (stripos($kategoriInput, 'lain') !== false) {
                $kategori = 'Lainnya';
            }
        }

        // Status Perjanjian
        $statusInput = $val('status_perjanjian', 'status');
        $status = 'Aktif';
        if ($statusInput && (stripos($statusInput, 'selesai') !== false || stripos($statusInput, 'kembali') !== false)) {
            $status = 'Selesai / Dikembalikan';
        } else {
            $diffDays = Carbon::now()->startOfDay()->diffInDays($tglSelesai, false);
            if ($diffDays <= 30 && $diffDays >= 0) {
                $status = 'Akan Jatuh Tempo';
            }
        }

        $this->importedCount++;

        return new KendaraanPinjamPakai([
            'vehicle_id' => $vehicleId,
            'opd_id' => $opdId,
            'kategori_peminjam' => $kategori,
            'nama_instansi_peminjam' => $namaInstansi ?? 'Instansi Peminjam',
            'nama_penanggung_jawab' => $namaPj ?? 'Penanggung Jawab',
            'nip_penanggung_jawab' => $val('nip_penanggung_jawab', 'nip', 'nrp'),
            'jabatan_penanggung_jawab' => $val('jabatan_penanggung_jawab', 'jabatan'),
            'kontak_penanggung_jawab' => $val('kontak_penanggung_jawab', 'kontak', 'hp', 'telepon', 'no_hp'),
            'nomor_nppp_bast' => $nomorNppp ?? ('NPPP/' . date('Ymd') . '/' . $this->importedCount),
            'tanggal_nppp_bast' => $tglNppp,
            'tanggal_mulai' => $tglMulai,
            'tanggal_selesai' => $tglSelesai,
            'status_perjanjian' => $status,
            'keterangan' => $val('keterangan', 'catatan', 'ket') ?? 'Diimpor secara otomatis via AI Smart Semantic Import',
            'created_by' => auth()->id(),
        ]);
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    private function cleanNopol(?string $str): ?string
    {
        if (!$str) return null;
        return preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($str)));
    }

    private function parseDate(?string $dateStr): ?Carbon
    {
        if (empty($dateStr)) return null;

        try {
            // Excel numeric date check
            if (is_numeric($dateStr)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateStr));
            }
            return Carbon::parse($dateStr);
        } catch (\Exception $e) {
            return null;
        }
    }
}
