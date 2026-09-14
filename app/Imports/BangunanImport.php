<?php

namespace App\Imports;

use App\Enums\BangunanKondisi;
use App\Enums\UserRole;
use App\Models\AsetTanah;
use App\Models\Bangunan;
use App\Models\OpdSipat;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\HasReferencesToOtherSheets;

class BangunanImport implements ToModel, WithStartRow, WithBatchInserts, WithChunkReading, WithCalculatedFormulas, HasReferencesToOtherSheets
{
    private static $sharedRowCount = 0;
    private $opdCache = [];
    private $tanahCache = [];
    private $columnIndexes = [];
    private $headers = [];
    private $mapping = [];
    private $startRow = 2;
    private $currentUserId;
    private $userOpdId;
    private $userRole;

    public static function resetSharedState(): void
    {
        static::$sharedRowCount = 0;
    }

    public function __construct(
        array $mapping = [],
        array $headers = [],
        int $startRow = 2,
        ?int $currentUserId = null,
        ?int $userOpdId = null,
        ?UserRole $userRole = null
    ) {
        $this->mapping = $mapping;
        $this->headers = $headers;
        $this->startRow = $startRow;
        $this->currentUserId = $currentUserId ?? auth()->id();
        $this->userOpdId = $userOpdId ?? auth()->user()?->opd_id;
        $this->userRole = $userRole ?? auth()->user()?->role;

        // Pre-load Master OPD untuk pemetaan instansi
        $this->opdCache = OpdSipat::pluck('id', 'nama')->toArray();

        // Pre-load NIBAR Tanah KIB A
        $this->tanahCache = AsetTanah::whereNotNull('kode_aset')
            ->pluck('id', 'kode_aset')
            ->toArray();

        // Bangun indeks kolom
        foreach ($this->mapping as $targetDb => $excelHeader) {
            if (!empty($excelHeader)) {
                $idx = array_search($excelHeader, $this->headers);
                if ($idx !== false) {
                    $this->columnIndexes[$targetDb] = $idx;
                }
            }
        }
    }

    public function startRow(): int
    {
        return $this->startRow;
    }

    public function model(array $row)
    {
        // Ambil nama bangunan
        $namaBangunan = $this->getValue($row, 'nama_bangunan');
        if (empty($namaBangunan)) {
            return null; // Lewati baris kosong
        }

        static::$sharedRowCount++;

        // 1. Resolusi OPD (Multi-Tenancy)
        $opdId = null;
        if ($this->userRole === UserRole::OPD && $this->userOpdId) {
            $opdId = $this->userOpdId;
        } else {
            $opdText = $this->getValue($row, 'opd');
            if (!empty($opdText)) {
                $opdId = $this->findOpdId($opdText);
            }
        }

        // 2. Resolusi Tanah KIB A (Pengamanan Hukum)
        $asetTanahId = null;
        $kodeTanah = $this->getValue($row, 'nomor_kode_tanah');
        if (!empty($kodeTanah)) {
            $cleanNibar = trim($kodeTanah);
            $asetTanahId = $this->tanahCache[$cleanNibar] ?? null;
        }

        // 3. Resolusi Kondisi Fisik
        $kondisiRaw = strtolower(trim((string) $this->getValue($row, 'kondisi')));
        $kondisi = BangunanKondisi::BAIK->value;
        if (str_contains($kondisiRaw, 'rusak berat') || str_contains($kondisiRaw, 'rb') || str_contains($kondisiRaw, 'berat')) {
            $kondisi = BangunanKondisi::RUSAK_BERAT->value;
        } elseif (str_contains($kondisiRaw, 'kurang') || str_contains($kondisiRaw, 'kb') || str_contains($kondisiRaw, 'ringan') || str_contains($kondisiRaw, 'sedang')) {
            $kondisi = BangunanKondisi::KURANG_BAIK->value;
        }

        // 4. Konstruksi & Tingkat
        $tingkatRaw = strtolower(trim((string) $this->getValue($row, 'konstruksi_tingkat')));
        $isTingkat = in_array($tingkatRaw, ['ya', 'tingkat', '1', 'true', 'bertingkat']);
        $betonRaw = strtolower(trim((string) $this->getValue($row, 'konstruksi_beton')));
        $isBeton = !in_array($betonRaw, ['tidak', 'bukan', '0', 'false', 'kayu']);

        // 5. Luas Lantai & Harga
        $luasLantai = $this->parseNumeric($this->getValue($row, 'luas_lantai'));
        $luasDasar = $this->parseNumeric($this->getValue($row, 'luas_dasar'));
        $hargaPerolehan = $this->parseNumeric($this->getValue($row, 'harga_perolehan'));

        // 6. Tanggal PBG / IMB
        $tglPbg = $this->parseDate($this->getValue($row, 'tanggal_dokumen_pbg'));

        // 7. Generate Kode Bangunan Unik
        $kodeBangunan = 'BG-' . date('Ymd') . '-' . sprintf('%04d', static::$sharedRowCount) . '-' . strtoupper(Str::random(3));

        return new Bangunan([
            'kode_bangunan'       => $kodeBangunan,
            'kode_barang'         => $this->getValue($row, 'kode_barang'),
            'nama_bangunan'       => $namaBangunan,
            'nomor_register'      => $this->getValue($row, 'nomor_register'),
            'opd_id'              => $opdId,
            'aset_tanah_id'       => $asetTanahId,
            'status_tanah_dasar'  => $this->getValue($row, 'status_tanah_dasar'),
            'luas_tanah_dasar'    => $this->parseNumeric($this->getValue($row, 'luas_tanah_dasar')),
            'jenis_bangunan'      => 'Gedung Kantor',
            'kondisi'             => $kondisi,
            'konstruksi_tingkat'  => $isTingkat,
            'jumlah_lantai'       => $isTingkat ? 2 : 1,
            'konstruksi_beton'    => $isBeton,
            'tipe_konstruksi'     => $isBeton ? 'Permanen' : 'Semi-Permanen',
            'luas_lantai'         => $luasLantai,
            'luas_dasar'          => $luasDasar > 0 ? $luasDasar : $luasLantai,
            'alamat'              => $this->getValue($row, 'alamat'),
            'nomor_dokumen_pbg'   => $this->getValue($row, 'nomor_dokumen_pbg'),
            'tanggal_dokumen_pbg' => $tglPbg,
            'asal_usul'           => $this->getValue($row, 'asal_usul') ?: 'APBD Kab',
            'tahun_pengadaan'     => (int) ($this->getValue($row, 'tahun_pengadaan') ?: date('Y')),
            'harga_perolehan'     => $hargaPerolehan,
            'status_penggunaan'   => 'Digunakan Sendiri',
            'keterangan'          => $this->getValue($row, 'keterangan'),
            'created_by'          => $this->currentUserId,
        ]);
    }

    private function getValue(array $row, string $key)
    {
        if (isset($this->columnIndexes[$key])) {
            $idx = $this->columnIndexes[$key];
            if (isset($row[$idx])) {
                $val = trim((string) $row[$idx]);
                return $val !== '' ? $val : null;
            }
        }
        return null;
    }

    private function parseNumeric($val): float
    {
        if (is_null($val)) return 0.0;
        $cleaned = preg_replace('/[^0-9.,]/', '', (string) $val);
        // Tangani format desimal Indonesia (koma) atau internasional (titik)
        if (str_contains($cleaned, ',') && str_contains($cleaned, '.')) {
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
        } elseif (str_contains($cleaned, ',')) {
            $cleaned = str_replace(',', '.', $cleaned);
        }
        return (float) $cleaned;
    }

    private function parseDate($val): ?string
    {
        if (empty($val)) return null;
        try {
            if (is_numeric($val)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val))->format('Y-m-d');
            }
            return Carbon::parse($val)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function findOpdId(string $opdName): ?int
    {
        $clean = strtolower(trim($opdName));
        foreach ($this->opdCache as $nama => $id) {
            if (strtolower(trim($nama)) === $clean || str_contains($clean, strtolower(trim($nama)))) {
                return $id;
            }
        }
        return null;
    }

    public function batchSize(): int
    {
        return 200;
    }

    public function chunkSize(): int
    {
        return 200;
    }
}
