<?php

namespace App\Services;

use App\Models\AsetTanah;
use App\Models\OpdSipat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LaporanService
{
    /**
     * Mengekstrak dan membersihkan filter dari input user.
     */
    public function getFilters(array $input): array
    {
        $rawStatus = $input['kategori_status'] ?? ($input['status'] ?? null);
        $kategoriStatus = '';
        $statusIds = [];

        if (is_array($rawStatus)) {
            $rawFirst = reset($rawStatus);
            if (is_numeric($rawFirst)) {
                $statusIds = array_filter($rawStatus);
            } else {
                $kategoriStatus = (string) $rawFirst;
            }
        } elseif (is_numeric($rawStatus)) {
            $statusIds = [(int) $rawStatus];
        } elseif (is_string($rawStatus)) {
            $kategoriStatus = $rawStatus;
        }

        return [
            'opd_id' => $input['opd_id'] ?? ($input['opd'] ?? ''),
            'kecamatan_id' => $input['kecamatan_id'] ?? '',
            'status' => $statusIds,
            'kategori_status' => $kategoriStatus,
            'tanggal_perolehan' => $input['tanggal_perolehan'] ?? '',
            'q' => $input['q'] ?? '',
            'title_mode' => $input['title_mode'] ?? 'auto',
            'report_title_id' => $input['report_title_id'] ?? '',
            'manual_title' => $input['manual_title'] ?? '',
        ];
    }

    /**
     * Membangun Query Builder berdasarkan filter pencarian.
     */
    public function buildQuery(array $filters)
    {
        $query = AsetTanah::with(['latestProses.statusProses', 'opdSipat', 'wilayahKecamatan', 'sertifikatElabel']);

        $opdFilter = $filters['opd_id'] ?? '';
        if ($opdFilter !== '') {
            if ($opdFilter === 'KOSONG') {
                $query->where(function($q) {
                    $q->whereNull('opd_id')
                      ->where(function ($q2) {
                          $q2->whereNull('opd')->orWhere('opd', '');
                      });
                });
            } elseif (is_numeric($opdFilter)) {
                $query->where('opd_id', (int) $opdFilter);
            } else {
                $query->where('opd', $opdFilter);
            }
        }

        $kecFilter = $filters['kecamatan_id'] ?? '';
        if ($kecFilter !== '') {
            if ($kecFilter === 'KOSONG') {
                $query->whereNull('kecamatan_id');
            } elseif (is_numeric($kecFilter)) {
                $query->where('kecamatan_id', (int) $kecFilter);
            }
        }

        // Filter Kategori Status (Belum Diproses, Dalam Proses, Sudah Bersertifikat, Bermasalah, Belum Bersertifikat)
        $kat = $filters['kategori_status'] ?? '';
        if ($kat !== '') {
            $query->filterKategoriStatus($kat);
        } elseif (!empty($filters['status'])) {
            $query->whereHas('latestProses', function($q) use ($filters) {
                $q->whereIn('id_status', $filters['status']);
            });
        }

        if (!empty($filters['tanggal_perolehan'])) {
            $query->whereDate('tanggal_perolehan', $filters['tanggal_perolehan']);
        }

        if (!empty($filters['q'])) {
            $search = '%' . $filters['q'] . '%';
            $query->where(function($q) use ($search) {
                $q->where('kode_aset', 'LIKE', $search)
                  ->orWhere('nama_aset', 'LIKE', $search)
                  ->orWhere('peruntukan', 'LIKE', $search)
                  ->orWhere('opd', 'LIKE', $search)
                  ->orWhereHas('opdSipat', function ($opdQuery) use ($search) {
                      $opdQuery->where('nama', 'LIKE', $search);
                  })
                  ->orWhere('alamat', 'LIKE', $search);
            });
        }

        return $query->orderBy('id_aset', 'desc');
    }

    /**
     * Menghitung total dan agregasi ringkasan data.
     */
    public function buildSummary($rows, array $filters): array
    {
        $totalData = count($rows);
        $totalNilai = 0;
        $totalBerstatus = 0;

        foreach ($rows as $row) {
            $totalNilai += (float) ($row->harga_perolehan ?? 0);
            $statusObj = $row->latestProses?->statusProses;
            if ($statusObj && $statusObj->hasCategory('bersertifikat')) {
                $totalBerstatus++;
            }
        }

        $activeFilters = [];
        if (!empty($filters['opd_id'])) {
            $opdLabel = 'OPD';
            if ($filters['opd_id'] === 'KOSONG') {
                $opdLabel = 'Tanpa OPD';
                $opdValue = 'Kosong';
            } elseif (is_numeric($filters['opd_id'])) {
                $opd = OpdSipat::find((int) $filters['opd_id']);
                $opdValue = $opd->nama ?? (string) $filters['opd_id'];
            } else {
                $opdValue = (string) $filters['opd_id'];
            }
            $activeFilters[] = ['label' => $opdLabel, 'value' => $opdValue];
        }

        if (!empty($filters['kecamatan_id'])) {
            $kecLabel = 'Kecamatan';
            if ($filters['kecamatan_id'] === 'KOSONG') {
                $kecValue = 'Luar Wilayah / Lainnya';
            } elseif (is_numeric($filters['kecamatan_id'])) {
                $kec = \App\Models\Kecamatan::find((int) $filters['kecamatan_id']);
                $kecValue = $kec->nama ?? (string) $filters['kecamatan_id'];
            } else {
                $kecValue = (string) $filters['kecamatan_id'];
            }
            $activeFilters[] = ['label' => $kecLabel, 'value' => $kecValue];
        }

        if (!empty($filters['kategori_status'])) {
            $katLabels = [
                'belum_diproses' => 'Belum Diproses',
                'dalam_proses' => 'Dalam Proses',
                'sudah_bersertifikat' => 'Sudah Bersertifikat',
                'bermasalah' => 'Bermasalah / Sengketa',
                'belum_bersertifikat' => 'Belum Bersertifikat (Gabungan)',
            ];
            $katValue = $katLabels[$filters['kategori_status']] ?? $filters['kategori_status'];
            $activeFilters[] = ['label' => 'Kategori Status', 'value' => $katValue];
        }

        if (!empty($filters['tanggal_perolehan'])) {
            $activeFilters[] = ['label' => 'Tanggal Perolehan', 'value' => $filters['tanggal_perolehan']];
        }
        if (!empty($filters['q'])) {
            $activeFilters[] = ['label' => 'Pencarian', 'value' => $filters['q']];
        }

        return [
            'total_data' => $totalData,
            'total_nilai' => 'Rp ' . number_format($totalNilai, 2, ',', '.'),
            'total_berstatus' => $totalBerstatus,
            'activeFilters' => $activeFilters,
        ];
    }

    /**
     * Menghasilkan susunan baris judul laporan yang rapi dan terstruktur (Tata Naskah Dinas).
     * Contoh:
     * Baris 1: LAPORAN ASET TANAH SUDAH BERSERTIFIKAT
     * Baris 2: DINAS PENDIDIKAN DAN KEBUDAYAAN
     * Baris 3: KECAMATAN BANAWA 2026
     */
    public function resolveReportTitleLines(array $filters): array
    {
        $mode = $filters['title_mode'] ?? 'auto';
        $year = !empty($filters['tahun_perolehan'])
            ? $filters['tahun_perolehan']
            : (!empty($filters['tanggal_perolehan']) ? date('Y', strtotime($filters['tanggal_perolehan'])) : date('Y'));

        // 1. Mode Manual (Ketik Sendiri)
        if ($mode === 'manual' && !empty(trim($filters['manual_title'] ?? ''))) {
            $manual = strtoupper(trim($filters['manual_title']));
            $hasYear = preg_match('/\b(19|20)\d{2}\b/', $manual);
            if ($hasYear) {
                return [$manual];
            }
            return [$manual, 'TAHUN ' . $year];
        }

        // 2. Mode Master Judul (Pilih dari Master)
        if ($mode === 'master' && !empty($filters['report_title_id'])) {
            $rt = null;
            if (Schema::hasTable('report_titles')) {
                $rt = DB::table('report_titles')->where('id', $filters['report_title_id'])->first();
            }
            if ($rt && !empty($rt->judul)) {
                $masterJudul = strtoupper(trim($rt->judul));
                $hasYear = preg_match('/\b(19|20)\d{2}\b/', $masterJudul);
                if ($hasYear) {
                    return [$masterJudul];
                }
                return [$masterJudul, 'TAHUN ' . $year];
            }
        }

        // 3. Mode Dinamis Otomatis (Filter-based)
        // Baris 1: Kategori / Status Laporan
        $kat = $filters['kategori_status'] ?? '';
        $line1 = match ($kat) {
            'sudah_bersertifikat' => 'LAPORAN ASET TANAH SUDAH BERSERTIFIKAT',
            'dalam_proses'        => 'LAPORAN ASET TANAH DALAM PROSES PENSERTIFIKATAN BPN',
            'belum_diproses'      => 'LAPORAN ASET TANAH BELUM DIPROSES PENSERTIFIKATAN',
            'bermasalah'          => 'LAPORAN ASET TANAH BERMASALAH / SENGKETA',
            'belum_bersertifikat' => 'LAPORAN REKAPITULASI ASET TANAH BELUM BERSERTIFIKAT (GABUNGAN)',
            default               => 'LAPORAN REKAPITULASI ASET TANAH',
        };

        // Deteksi OPD
        $opdText = null;
        $opdFilter = $filters['opd_id'] ?? '';
        if ($opdFilter !== '') {
            if ($opdFilter === 'KOSONG') {
                $opdText = 'TANPA OPD';
            } elseif (is_numeric($opdFilter)) {
                $opd = OpdSipat::find((int) $opdFilter);
                if ($opd && !empty($opd->nama)) {
                    $opdText = strtoupper(trim($opd->nama));
                }
            } else {
                $opdText = strtoupper(trim((string) $opdFilter));
            }
        }

        // Deteksi Kecamatan
        $kecText = null;
        $kecFilter = $filters['kecamatan_id'] ?? '';
        if ($kecFilter !== '') {
            if ($kecFilter === 'KOSONG') {
                $kecText = 'LUAR WILAYAH';
            } elseif (is_numeric($kecFilter)) {
                $kec = \App\Models\Kecamatan::find((int) $kecFilter);
                if ($kec && !empty($kec->nama)) {
                    $kecText = 'KECAMATAN ' . strtoupper(trim($kec->nama));
                }
            } else {
                $kecText = 'KECAMATAN ' . strtoupper(trim((string) $kecFilter));
            }
        }

        $lines = [$line1];

        // Baris 2: OPD (atau jika tanpa OPD, sebutkan PEMERINTAH KABUPATEN DONGGALA)
        if ($opdText) {
            $lines[] = $opdText;
        } else {
            $lines[] = 'PEMERINTAH KABUPATEN DONGGALA';
        }

        // Baris 3: Lokasi Kecamatan + Tahun (format: "KECAMATAN BANAWA 2026")
        if ($kecText) {
            $lines[] = $kecText . ' ' . $year;
        } else {
            $lines[] = 'TAHUN ' . $year;
        }

        return $lines;
    }

    /**
     * Memecahkan judul laporan yang aktif secara dinamis sebagai satu string utuh.
     */
    public function resolveReportTitle(array $filters): string
    {
        return implode(' ', $this->resolveReportTitleLines($filters));
    }

    /**
     * Mengambil setting Kop Surat Pemda.
     */
    public function getKopSettings(): array
    {
        $defaults = [
            'kop_nama_instansi' => 'PEMERINTAH KABUPATEN DONGGALA',
            'kop_nama_unit' => 'BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH',
            'kop_subunit' => 'Bidang Pengelolaan Aset Daerah',
            'kop_alamat' => 'Jl. Trans Sulawesi, Banawa, Kabupaten Donggala, Sulawesi Tengah',
            'kop_kontak' => 'Email: bpkad@donggalakab.go.id | Web: sipat.donggalakab.go.id',
            'kop_logo' => '',
            'kop_nama_laporan_aset' => 'LAPORAN REKAPITULASI ASET TANAH',
            'kop_footer' => 'Dokumen ini dihasilkan secara resmi oleh Aplikasi SIPAT Terpadu Kabupaten Donggala.',
            'kop_kota_ttd' => 'Banawa',
            // Penanda Tangan 1 (Kiri - Pejabat Bidang)
            'kop_pejabat1_jabatan' => 'KEPALA BIDANG ASET DAERAH',
            'kop_pejabat1_nama' => 'YENI SJ AMIR, SH.MSi',
            'kop_pejabat1_nip' => 'NIP.',
            // Penanda Tangan 2 (Kanan - Kepala Badan)
            'kop_pejabat2_jabatan' => 'KEPALA BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH',
            'kop_pejabat2_nama' => 'YENI SJ AMIR, SH.MSi',
            'kop_pejabat2_nip' => 'NIP.',
            // Legacy Fallback
            'kop_pejabat_jabatan' => 'KEPALA BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH',
            'kop_pejabat_nama' => 'YENI SJ AMIR, SH.MSi',
            'kop_pejabat_nip' => 'NIP.',
        ];

        if (Schema::hasTable('settings')) {
            $rows = DB::table('settings')->whereIn('key', array_keys($defaults))->get();
            foreach ($rows as $row) {
                $val = trim($row->value ?? '');
                if ($val !== '') {
                    $defaults[$row->key] = $val;
                }
            }
        }

        // Backward compatibility fallback
        if (empty($defaults['kop_pejabat2_jabatan']) && !empty($defaults['kop_pejabat_jabatan'])) {
            $defaults['kop_pejabat2_jabatan'] = $defaults['kop_pejabat_jabatan'];
        }
        if (empty($defaults['kop_pejabat2_nama']) && !empty($defaults['kop_pejabat_nama'])) {
            $defaults['kop_pejabat2_nama'] = $defaults['kop_pejabat_nama'];
        }
        if (empty($defaults['kop_pejabat2_nip']) && !empty($defaults['kop_pejabat_nip'])) {
            $defaults['kop_pejabat2_nip'] = $defaults['kop_pejabat_nip'];
        }

        return $defaults;
    }

    /**
     * Memformat string NIP agar rapi dan tidak duplikat dengan awalan NIP.
     */
    public function formatNip(?string $nip): string
    {
        $nip = trim((string)$nip);
        if ($nip === '' || $nip === '-') {
            return 'NIP. -';
        }
        if (str_starts_with(strtoupper($nip), 'NIP')) {
            return $nip;
        }
        return 'NIP. ' . $nip;
    }

    /**
     * Menyelesaikan absolute file path logo KOP surat Pemda yang valid.
     */
    public function resolveLogoPath(array $kop): ?string
    {
        if (!empty($kop['kop_logo'])) {
            $storagePublic = public_path('storage/' . $kop['kop_logo']);
            if (file_exists($storagePublic)) {
                return $storagePublic;
            }
            $storageApp = storage_path('app/public/' . $kop['kop_logo']);
            if (file_exists($storageApp)) {
                return $storageApp;
            }
            $reportUpload = public_path('uploads/report/' . $kop['kop_logo']);
            if (file_exists($reportUpload)) {
                return $reportUpload;
            }
        }

        if (file_exists(public_path('images/logo.png'))) {
            return public_path('images/logo.png');
        }
        if (file_exists(public_path('assets/logo.png'))) {
            return public_path('assets/logo.png');
        }

        return null;
    }

    /**
     * Generate Excel file dengan layout simpel 5 kolom (NO., Bidang, Luas(m2), Nilai(Rp), Keterangan)
     */
    /**
     * Generate Excel file dengan layout kolom lengkap (NO., Kode Aset / NIBAR, Bidang, Luas(m2), Nilai(Rp), Keterangan)
     * serta mempertahankan KOP Resmi Pemda dan Lembar Pengesahan (TTD).
     */
    public function exportExcel($rows, array $filters, array $summary, array $kop, string $selectedTitle)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Aset');

        $titleLines = $this->resolveReportTitleLines($filters);

        // Grouped header label
        $kat = $filters['kategori_status'] ?? '';
        $groupHeader = 'Aset Tanah';
        if ($kat === 'sudah_bersertifikat') {
            $groupHeader = 'Aset Sudah Sertifikat';
        } elseif ($kat === 'belum_diproses') {
            $groupHeader = 'Aset Belum Diproses';
        } elseif ($kat === 'dalam_proses') {
            $groupHeader = 'Aset Dalam Proses';
        } elseif ($kat === 'bermasalah') {
            $groupHeader = 'Aset Bermasalah / Sengketa';
        } elseif ($kat === 'belum_bersertifikat') {
            $groupHeader = 'Aset Belum Bersertifikat';
        }

        // Khusus laporan aset bersertifikat, tambahkan sub-kolom No. Sertifikat (Total 12 Kolom A s.d. L)
        $isBersertifikat = ($kat === 'sudah_bersertifikat');
        $lastCol = $isBersertifikat ? 'L' : 'K';

        // --- 1. KOP SURAT RESMI ---
        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->mergeCells('A2:' . $lastCol . '2');
        $sheet->mergeCells('A3:' . $lastCol . '3');
        $sheet->setCellValue('A1', strtoupper($kop['kop_nama_instansi'] ?? 'PEMERINTAH KABUPATEN DONGGALA'));
        $sheet->setCellValue('A2', strtoupper($kop['kop_nama_unit'] ?? 'BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH'));
        $sheet->setCellValue('A3', strtoupper($kop['kop_subunit'] ?? 'Bidang Pengelolaan Aset Daerah'));

        $logoPath = $this->resolveLogoPath($kop);
        if ($logoPath && file_exists($logoPath)) {
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setName('Logo KOP');
            $drawing->setDescription('Logo KOP Pemda');
            $drawing->setPath($logoPath);
            $drawing->setHeight(52);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(10);
            $drawing->setOffsetY(4);
            $drawing->setWorksheet($sheet);
        }

        // --- 2. JUDUL LAPORAN KATEGORI (BERJENJANG TATA NASKAH DINAS) ---
        $curRow = 5;
        foreach ($titleLines as $idx => $line) {
            $sheet->mergeCells('A' . $curRow . ':' . $lastCol . $curRow);
            $sheet->setCellValue('A' . $curRow, $line);
            $fontSize = ($idx === 0) ? 12 : 11;
            $sheet->getStyle('A' . $curRow . ':' . $lastCol . $curRow)->applyFromArray([
                'font' => ['bold' => true, 'size' => $fontSize, 'name' => 'Arial'],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ]);
            $curRow++;
        }

        // 1 baris spasi kosong pemisah
        $headerStartRow = $curRow + 1;
        $headerRow2 = $headerStartRow + 1;

        // --- 3. HEADER TABEL ---
        $sheet->mergeCells('A' . $headerStartRow . ':A' . $headerRow2);
        $sheet->mergeCells('B' . $headerStartRow . ':B' . $headerRow2);
        $sheet->mergeCells('C' . $headerStartRow . ':C' . $headerRow2);
        $sheet->mergeCells('D' . $headerStartRow . ':D' . $headerRow2);

        $sheet->setCellValue('A' . $headerStartRow, 'NO.');
        $sheet->setCellValue('B' . $headerStartRow, 'Kode Aset / NIBAR');
        $sheet->setCellValue('C' . $headerStartRow, 'Nama Barang');
        $sheet->setCellValue('D' . $headerStartRow, 'Lokasi');

        if ($isBersertifikat) {
            // Sub-kolom khusus aset bersertifikat: Bidang | No. Sertifikat | Luas(m2) | Nilai(Rp)
            $sheet->mergeCells('E' . $headerStartRow . ':H' . $headerStartRow);
            $sheet->setCellValue('E' . $headerStartRow, 'Aset Sudah Bersertifikat');
            $sheet->setCellValue('E' . $headerRow2, 'Bidang');
            $sheet->setCellValue('F' . $headerRow2, 'No. Sertifikat');
            $sheet->setCellValue('G' . $headerRow2, 'Luas(m2)');
            $sheet->setCellValue('H' . $headerRow2, 'Nilai(Rp)');

            $sheet->mergeCells('I' . $headerStartRow . ':I' . $headerRow2);
            $sheet->mergeCells('J' . $headerStartRow . ':J' . $headerRow2);
            $sheet->mergeCells('K' . $headerStartRow . ':K' . $headerRow2);
            $sheet->mergeCells('L' . $headerStartRow . ':L' . $headerRow2);

            $sheet->setCellValue('I' . $headerStartRow, 'Tanggal Perolehan');
            $sheet->setCellValue('J' . $headerStartRow, 'Cara Perolehan');
            $sheet->setCellValue('K' . $headerStartRow, 'Status');
            $sheet->setCellValue('L' . $headerStartRow, 'Keterangan');
        } else {
            // Standar 11 kolom untuk laporan lainnya: Bidang | Luas(m2) | Nilai(Rp)
            $sheet->mergeCells('E' . $headerStartRow . ':G' . $headerStartRow);
            $sheet->setCellValue('E' . $headerStartRow, $groupHeader);
            $sheet->setCellValue('E' . $headerRow2, 'Bidang');
            $sheet->setCellValue('F' . $headerRow2, 'Luas(m2)');
            $sheet->setCellValue('G' . $headerRow2, 'Nilai(Rp)');

            $sheet->mergeCells('H' . $headerStartRow . ':H' . $headerRow2);
            $sheet->mergeCells('I' . $headerStartRow . ':I' . $headerRow2);
            $sheet->mergeCells('J' . $headerStartRow . ':J' . $headerRow2);
            $sheet->mergeCells('K' . $headerStartRow . ':K' . $headerRow2);

            $sheet->setCellValue('H' . $headerStartRow, 'Tanggal Perolehan');
            $sheet->setCellValue('I' . $headerStartRow, 'Cara Perolehan');
            $sheet->setCellValue('J' . $headerStartRow, 'Status');
            $sheet->setCellValue('K' . $headerStartRow, 'Keterangan');
        }

        // --- 4. DATA ROWS ---
        $rowNumber = $headerRow2 + 1;
        $dataStartRow = $rowNumber;
        $no = 1;
        $totalLuas = 0;
        $totalNilai = 0;

        foreach ($rows as $row) {
            $luasVal = (float) ($row->luas ?? 0);
            $nilaiVal = (float) ($row->harga_perolehan ?? 0);

            $totalLuas += $luasVal;
            $totalNilai += $nilaiVal;

            $kodeAsetText = $row->kode_aset ?? '-';
            $namaBarangText = $row->nama_aset ?? '-';
            $lokasiText = $row->alamat ?? '-';
            $bidangText = $row->peruntukan ?? $row->nama_aset ?? '-';
            $noSertifikatText = !empty($row->sertifikatElabel?->no_sertipikat) ? trim($row->sertifikatElabel->no_sertipikat) : '-';
            $tglPerolehanText = !empty($row->tanggal_perolehan) ? \Carbon\Carbon::parse($row->tanggal_perolehan)->format('d/m/Y') : '-';
            $caraPerolehanText = $row->dasar_perolehan ?? '-';
            // Status proses pensertifikatan tanah
            $statusProsesText = $row->latestProses?->statusProses?->nama_status ?? 'Belum Diproses';
            // Kolom keterangan murni berisi catatan keterangan, BUKAN nama barang
            $keteranganText = !empty(trim((string) ($row->keterangan ?? ''))) ? trim((string) $row->keterangan) : '-';

            $sheet->setCellValue('A' . $rowNumber, $no++);
            $sheet->setCellValue('B' . $rowNumber, $kodeAsetText);
            $sheet->setCellValue('C' . $rowNumber, $namaBarangText);
            $sheet->setCellValue('D' . $rowNumber, $lokasiText);
            $sheet->setCellValue('E' . $rowNumber, $bidangText);

            if ($isBersertifikat) {
                $sheet->setCellValue('F' . $rowNumber, $noSertifikatText);
                $sheet->setCellValue('G' . $rowNumber, $luasVal);
                $sheet->setCellValue('H' . $rowNumber, $nilaiVal);
                $sheet->setCellValue('I' . $rowNumber, $tglPerolehanText);
                $sheet->setCellValue('J' . $rowNumber, $caraPerolehanText);
                $sheet->setCellValue('K' . $rowNumber, $statusProsesText);
                $sheet->setCellValue('L' . $rowNumber, $keteranganText);
            } else {
                $sheet->setCellValue('F' . $rowNumber, $luasVal);
                $sheet->setCellValue('G' . $rowNumber, $nilaiVal);
                $sheet->setCellValue('H' . $rowNumber, $tglPerolehanText);
                $sheet->setCellValue('I' . $rowNumber, $caraPerolehanText);
                $sheet->setCellValue('J' . $rowNumber, $statusProsesText);
                $sheet->setCellValue('K' . $rowNumber, $keteranganText);
            }

            $rowNumber++;
        }

        // --- 5. TOTAL ROW ---
        $totalRow = $rowNumber;
        if ($isBersertifikat) {
            $sheet->mergeCells('A' . $totalRow . ':F' . $totalRow);
            $sheet->setCellValue('A' . $totalRow, 'JUMLAH / TOTAL');
            $sheet->setCellValue('G' . $totalRow, $totalLuas);
            $sheet->setCellValue('H' . $totalRow, $totalNilai);
        } else {
            $sheet->mergeCells('A' . $totalRow . ':E' . $totalRow);
            $sheet->setCellValue('A' . $totalRow, 'JUMLAH / TOTAL');
            $sheet->setCellValue('F' . $totalRow, $totalLuas);
            $sheet->setCellValue('G' . $totalRow, $totalNilai);
        }

        // --- 6. LEMBAR PENGESAHAN (TTD RESMI DUA SISI) ---
        $ttdStartRow = $totalRow + 3;

        $pejabat1Jabatan = $kop['kop_pejabat1_jabatan'] ?? ($kop['kop_pejabat_jabatan'] ?? 'KEPALA BIDANG ASET DAERAH');
        $pejabat1Nama = $kop['kop_pejabat1_nama'] ?? ($kop['kop_pejabat_nama'] ?? 'YENI SJ AMIR, SH.MSi');
        $pejabat1Nip = $this->formatNip($kop['kop_pejabat1_nip'] ?? '');

        $pejabat2Jabatan = $kop['kop_pejabat2_jabatan'] ?? ($kop['kop_pejabat_jabatan'] ?? 'KEPALA BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH');
        $pejabat2Nama = $kop['kop_pejabat2_nama'] ?? ($kop['kop_pejabat_nama'] ?? 'YENI SJ AMIR, SH.MSi');
        $pejabat2Nip = $this->formatNip($kop['kop_pejabat2_nip'] ?? '');

        $ttdRightColStart = $isBersertifikat ? 'I' : 'H';

        // Berikan ruang tanda tangan fisik yang lapang (4 baris kosong)
        for ($r = $ttdStartRow + 2; $r <= $ttdStartRow + 5; $r++) {
            $sheet->getRowDimension($r)->setRowHeight(16);
        }

        // 1. Sisi Kiri: Penanda Tangan 1 (B:E)
        $sheet->mergeCells('B' . ($ttdStartRow + 1) . ':E' . ($ttdStartRow + 1));
        $sheet->mergeCells('B' . ($ttdStartRow + 6) . ':E' . ($ttdStartRow + 6));
        $sheet->mergeCells('B' . ($ttdStartRow + 7) . ':E' . ($ttdStartRow + 7));

        $sheet->setCellValue('B' . ($ttdStartRow + 1), $pejabat1Jabatan);
        $sheet->setCellValue('B' . ($ttdStartRow + 6), $pejabat1Nama);
        $sheet->setCellValue('B' . ($ttdStartRow + 7), $pejabat1Nip);

        // 2. Sisi Kanan: Penanda Tangan 2 ($ttdRightColStart:$lastCol)
        $sheet->mergeCells($ttdRightColStart . $ttdStartRow . ':' . $lastCol . $ttdStartRow);
        $sheet->mergeCells($ttdRightColStart . ($ttdStartRow + 1) . ':' . $lastCol . ($ttdStartRow + 1));
        $sheet->mergeCells($ttdRightColStart . ($ttdStartRow + 6) . ':' . $lastCol . ($ttdStartRow + 6));
        $sheet->mergeCells($ttdRightColStart . ($ttdStartRow + 7) . ':' . $lastCol . ($ttdStartRow + 7));

        $sheet->setCellValue($ttdRightColStart . $ttdStartRow, ($kop['kop_kota_ttd'] ?? 'Banawa') . ', ' . date('d-m-Y'));
        $sheet->setCellValue($ttdRightColStart . ($ttdStartRow + 1), $pejabat2Jabatan);
        $sheet->setCellValue($ttdRightColStart . ($ttdStartRow + 6), $pejabat2Nama);
        $sheet->setCellValue($ttdRightColStart . ($ttdStartRow + 7), $pejabat2Nip);

        // --- 7. STYLING ---
        // KOP Styling
        $sheet->getStyle('A1:' . $lastCol . '3')->applyFromArray([
            'font' => ['bold' => true, 'name' => 'Arial'],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A1')->getFont()->setSize(11);
        $sheet->getStyle('A2')->getFont()->setSize(13);
        $sheet->getStyle('A3')->getFont()->setSize(10);

        // Header Table Styling
        $sheet->getStyle('A' . $headerStartRow . ':' . $lastCol . $headerRow2)->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'name' => 'Arial'],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => 'D9D9D9'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Data Table Styling
        $sheet->getStyle('A' . $dataStartRow . ':' . $lastCol . $totalRow)->applyFromArray([
            'font' => ['size' => 10, 'name' => 'Arial'],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Total Row Styling
        $sheet->getStyle('A' . $totalRow . ':' . $lastCol . $totalRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'name' => 'Arial'],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => 'EAEAEA'],
            ],
        ]);

        // Alignments & Number Formats
        $sheet->getStyle('A' . $dataStartRow . ':A' . $totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B' . $dataStartRow . ':E' . $totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

        if ($isBersertifikat) {
            $sheet->getStyle('F' . $dataStartRow . ':F' . $totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $dataStartRow . ':H' . $totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('I' . $dataStartRow . ':K' . $totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('L' . $dataStartRow . ':L' . $totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

            $sheet->getStyle('G' . $dataStartRow . ':G' . $totalRow)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('H' . $dataStartRow . ':H' . $totalRow)->getNumberFormat()->setFormatCode('#,##0.00');
        } else {
            $sheet->getStyle('F' . $dataStartRow . ':G' . $totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('H' . $dataStartRow . ':J' . $totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('K' . $dataStartRow . ':K' . $totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

            $sheet->getStyle('F' . $dataStartRow . ':F' . $totalRow)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('G' . $dataStartRow . ':G' . $totalRow)->getNumberFormat()->setFormatCode('#,##0.00');
        }

        // TTD Styling Sisi Kiri (Penanda Tangan 1)
        $sheet->getStyle('B' . ($ttdStartRow + 1) . ':E' . ($ttdStartRow + 7))->applyFromArray([
            'font' => ['size' => 10, 'name' => 'Arial'],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('B' . ($ttdStartRow + 1))->getFont()->setBold(true);
        $sheet->getStyle('B' . ($ttdStartRow + 6))->getFont()->setBold(true)->setUnderline(true);

        // TTD Styling Sisi Kanan (Penanda Tangan 2)
        $sheet->getStyle($ttdRightColStart . $ttdStartRow . ':' . $lastCol . ($ttdStartRow + 7))->applyFromArray([
            'font' => ['size' => 10, 'name' => 'Arial'],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle($ttdRightColStart . ($ttdStartRow + 1))->getFont()->setBold(true);
        $sheet->getStyle($ttdRightColStart . ($ttdStartRow + 6))->getFont()->setBold(true)->setUnderline(true);

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(24);
        $sheet->getColumnDimension('C')->setWidth(26);
        $sheet->getColumnDimension('D')->setWidth(28);
        $sheet->getColumnDimension('E')->setWidth(28);

        if ($isBersertifikat) {
            $sheet->getColumnDimension('F')->setWidth(22); // No. Sertifikat
            $sheet->getColumnDimension('G')->setWidth(14); // Luas(m2)
            $sheet->getColumnDimension('H')->setWidth(20); // Nilai(Rp)
            $sheet->getColumnDimension('I')->setWidth(18); // Tanggal
            $sheet->getColumnDimension('J')->setWidth(18); // Cara
            $sheet->getColumnDimension('K')->setWidth(20); // Status
            $sheet->getColumnDimension('L')->setWidth(30); // Keterangan
        } else {
            $sheet->getColumnDimension('F')->setWidth(14); // Luas(m2)
            $sheet->getColumnDimension('G')->setWidth(20); // Nilai(Rp)
            $sheet->getColumnDimension('H')->setWidth(18); // Tanggal
            $sheet->getColumnDimension('I')->setWidth(18); // Cara
            $sheet->getColumnDimension('J')->setWidth(22); // Status
            $sheet->getColumnDimension('K')->setWidth(30); // Keterangan
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'Daftar_Aset_Tanah_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Menghasilkan data rekapitulasi matriks pensertifikatan aset tanah per OPD.
     */
    public function getRekapPerOpd(array $filters = []): array
    {
        $query = AsetTanah::with(['opdSipat', 'latestProses.statusProses']);

        if (!empty($filters['q'])) {
            $search = trim((string) $filters['q']);
            $query->where(function($q) use ($search) {
                $q->where('opd', 'LIKE', "%{$search}%")
                  ->orWhereHas('opdSipat', function($oq) use ($search) {
                      $oq->where('nama', 'LIKE', "%{$search}%");
                  });
            });
        }

        $asetList = $query->get();
        $grouped = [];

        foreach ($asetList as $aset) {
            $opdName = $aset->opdSipat->nama ?? trim((string) $aset->opd);
            if ($opdName === '') {
                $opdName = '[Tanpa OPD / Belum Terpetakan]';
            }

            if (!isset($grouped[$opdName])) {
                $grouped[$opdName] = [
                    'opd_id'              => $aset->opd_id,
                    'nama_opd'            => $opdName,
                    'total_bidang'        => 0,
                    'total_luas'          => 0.0,
                    'total_nilai'         => 0.0,
                    'sudah_sertifikat'    => 0,
                    'luas_sertifikat'     => 0.0,
                    'dalam_proses'        => 0,
                    'luas_proses'         => 0.0,
                    'belum_diproses'      => 0,
                    'luas_belum_diproses' => 0.0,
                    'bermasalah'          => 0,
                    'luas_bermasalah'     => 0.0,
                ];
            }

            $luas = (float) ($aset->luas ?? 0);
            $nilai = (float) ($aset->harga_perolehan ?? 0);

            $grouped[$opdName]['total_bidang']++;
            $grouped[$opdName]['total_luas'] += $luas;
            $grouped[$opdName]['total_nilai'] += $nilai;

            // Evaluasi kategori status BPN
            $latest = $aset->latestProses;
            $stObj = $latest ? $latest->statusProses : null;
            $kategori = strtolower(trim((string) ($stObj->kategori ?? '')));
            $statusName = strtolower(trim((string) ($stObj->nama_status ?? '')));

            if (!$latest || str_contains($kategori, 'belum_diurus') || $statusName === 'belum diurus' || $statusName === 'belum diproses') {
                $grouped[$opdName]['belum_diproses']++;
                $grouped[$opdName]['luas_belum_diproses'] += $luas;
            } elseif (str_contains($kategori, 'bersertifikat') || str_contains($statusName, 'sertifikat') || str_contains($statusName, 'selesai')) {
                $grouped[$opdName]['sudah_sertifikat']++;
                $grouped[$opdName]['luas_sertifikat'] += $luas;
            } elseif (str_contains($kategori, 'kendala') || str_contains($statusName, 'masalah') || str_contains($statusName, 'sengketa')) {
                $grouped[$opdName]['bermasalah']++;
                $grouped[$opdName]['luas_bermasalah'] += $luas;
            } else {
                $grouped[$opdName]['dalam_proses']++;
                $grouped[$opdName]['luas_proses'] += $luas;
            }
        }

        // Urutkan dari total bidang terbanyak
        uasort($grouped, fn($a, $b) => $b['total_bidang'] <=> $a['total_bidang']);

        $no = 1;
        $items = [];
        $grandTotal = [
            'total_bidang'        => 0,
            'total_luas'          => 0.0,
            'total_nilai'         => 0.0,
            'sudah_sertifikat'    => 0,
            'luas_sertifikat'     => 0.0,
            'dalam_proses'        => 0,
            'luas_proses'         => 0.0,
            'belum_diproses'      => 0,
            'luas_belum_diproses' => 0.0,
            'bermasalah'          => 0,
            'luas_bermasalah'     => 0.0,
            'persen_sertifikat'   => 0.0,
        ];

        foreach ($grouped as $row) {
            $row['no'] = $no++;
            $row['persen_sertifikat'] = $row['total_bidang'] > 0 
                ? round(($row['sudah_sertifikat'] / $row['total_bidang']) * 100, 1) 
                : 0.0;

            $grandTotal['total_bidang'] += $row['total_bidang'];
            $grandTotal['total_luas'] += $row['total_luas'];
            $grandTotal['total_nilai'] += $row['total_nilai'];
            $grandTotal['sudah_sertifikat'] += $row['sudah_sertifikat'];
            $grandTotal['luas_sertifikat'] += $row['luas_sertifikat'];
            $grandTotal['dalam_proses'] += $row['dalam_proses'];
            $grandTotal['luas_proses'] += $row['luas_proses'];
            $grandTotal['belum_diproses'] += $row['belum_diproses'];
            $grandTotal['luas_belum_diproses'] += $row['luas_belum_diproses'];
            $grandTotal['bermasalah'] += $row['bermasalah'];
            $grandTotal['luas_bermasalah'] += $row['luas_bermasalah'];

            $items[] = $row;
        }

        $grandTotal['persen_sertifikat'] = $grandTotal['total_bidang'] > 0 
            ? round(($grandTotal['sudah_sertifikat'] / $grandTotal['total_bidang']) * 100, 1) 
            : 0.0;

        return [
            'items'       => $items,
            'grand_total' => $grandTotal,
            'total_opd'   => count($items),
        ];
    }

    /**
     * Ekspor Rekapitulasi per OPD ke format Excel (.xlsx) dengan formula dan styling resmi.
     */
    public function exportRekapOpdExcel(array $rekapData, array $kop, string $title)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekapitulasi OPD');

        // KOP SURAT
        $sheet->setCellValue('A1', $kop['kop_nama_instansi'] ?? 'PEMERINTAH KABUPATEN DONGGALA');
        $sheet->setCellValue('A2', $kop['kop_nama_unit'] ?? 'BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH');
        $sheet->setCellValue('A3', $kop['kop_alamat'] ?? 'Jl. Trans Sulawesi, Banawa, Kabupaten Donggala');
        $sheet->setCellValue('A4', $kop['kop_kontak'] ?? 'Web: sipat.donggalakab.go.id');
        
        $sheet->mergeCells('A1:L1');
        $sheet->mergeCells('A2:L2');
        $sheet->mergeCells('A3:L3');
        $sheet->mergeCells('A4:L4');

        $logoPath = $this->resolveLogoPath($kop);
        if ($logoPath && file_exists($logoPath)) {
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setName('Logo KOP');
            $drawing->setDescription('Logo KOP Pemda');
            $drawing->setPath($logoPath);
            $drawing->setHeight(52);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(10);
            $drawing->setOffsetY(4);
            $drawing->setWorksheet($sheet);
        }

        $sheet->getStyle('A1:L4')->applyFromArray([
            'font' => ['bold' => true, 'name' => 'Arial', 'size' => 11],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A1')->getFont()->setSize(13);
        $sheet->getStyle('A3:A4')->applyFromArray([
            'font' => ['bold' => false, 'size' => 9, 'italic' => true],
        ]);

        $sheet->getStyle('A4:L4')->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE);

        // JUDUL LAPORAN
        $sheet->setCellValue('A6', $title);
        $sheet->mergeCells('A6:L6');
        $sheet->getStyle('A6')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'name' => 'Arial'],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->setCellValue('A7', 'Per Tanggal: ' . date('d F Y') . ' | Total OPD: ' . ($rekapData['total_opd'] ?? 0));
        $sheet->mergeCells('A7:L7');
        $sheet->getStyle('A7')->applyFromArray([
            'font' => ['size' => 9, 'italic' => true, 'name' => 'Arial'],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        // HEADER TABEL (Row 9 & 10)
        $sheet->setCellValue('A9', 'NO');
        $sheet->setCellValue('B9', 'ORGANISASI PERANGKAT DAERAH (OPD)');
        $sheet->setCellValue('C9', 'TOTAL BIDANG');
        $sheet->setCellValue('D9', 'TOTAL LUAS (M²)');
        $sheet->setCellValue('E9', 'SUDAH BERSERTIFIKAT');
        $sheet->setCellValue('G9', 'DALAM PROSES BPN');
        $sheet->setCellValue('I9', 'BELUM DIPROSES');
        $sheet->setCellValue('K9', 'BERMASALAH');
        $sheet->setCellValue('L9', 'CAPAIAN (%)');

        $sheet->setCellValue('E10', 'BIDANG');
        $sheet->setCellValue('F10', 'LUAS (M²)');
        $sheet->setCellValue('G10', 'BIDANG');
        $sheet->setCellValue('H10', 'LUAS (M²)');
        $sheet->setCellValue('I10', 'BIDANG');
        $sheet->setCellValue('J10', 'LUAS (M²)');
        $sheet->setCellValue('K10', 'BIDANG');

        $sheet->mergeCells('A9:A10');
        $sheet->mergeCells('B9:B10');
        $sheet->mergeCells('C9:C10');
        $sheet->mergeCells('D9:D10');
        $sheet->mergeCells('E9:F9');
        $sheet->mergeCells('G9:H9');
        $sheet->mergeCells('I9:J9');
        $sheet->mergeCells('K9:K10');
        $sheet->mergeCells('L9:L10');

        $sheet->getStyle('A9:L10')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'name' => 'Arial', 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => '1E40AF'], // Navy BPKAD
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);

        // DATA ROWS
        $currentRow = 11;
        $items = $rekapData['items'] ?? [];
        foreach ($items as $row) {
            $sheet->setCellValue('A' . $currentRow, $row['no']);
            $sheet->setCellValue('B' . $currentRow, $row['nama_opd']);
            $sheet->setCellValue('C' . $currentRow, $row['total_bidang']);
            $sheet->setCellValue('D' . $currentRow, $row['total_luas']);
            $sheet->setCellValue('E' . $currentRow, $row['sudah_sertifikat']);
            $sheet->setCellValue('F' . $currentRow, $row['luas_sertifikat']);
            $sheet->setCellValue('G' . $currentRow, $row['dalam_proses']);
            $sheet->setCellValue('H' . $currentRow, $row['luas_proses']);
            $sheet->setCellValue('I' . $currentRow, $row['belum_diproses']);
            $sheet->setCellValue('J' . $currentRow, $row['luas_belum_diproses']);
            $sheet->setCellValue('K' . $currentRow, $row['bermasalah']);
            $sheet->setCellValue('L' . $currentRow, $row['persen_sertifikat'] . '%');

            $currentRow++;
        }

        $lastDataRow = $currentRow - 1;

        // TOTAL ROW
        $totalRow = $currentRow;
        $sheet->setCellValue('A' . $totalRow, 'TOTAL KABUPATEN DONGGALA');
        $sheet->mergeCells('A' . $totalRow . ':B' . $totalRow);

        $sheet->setCellValue('C' . $totalRow, "=SUM(C11:C{$lastDataRow})");
        $sheet->setCellValue('D' . $totalRow, "=SUM(D11:D{$lastDataRow})");
        $sheet->setCellValue('E' . $totalRow, "=SUM(E11:E{$lastDataRow})");
        $sheet->setCellValue('F' . $totalRow, "=SUM(F11:F{$lastDataRow})");
        $sheet->setCellValue('G' . $totalRow, "=SUM(G11:G{$lastDataRow})");
        $sheet->setCellValue('H' . $totalRow, "=SUM(H11:H{$lastDataRow})");
        $sheet->setCellValue('I' . $totalRow, "=SUM(I11:I{$lastDataRow})");
        $sheet->setCellValue('J' . $totalRow, "=SUM(J11:J{$lastDataRow})");
        $sheet->setCellValue('K' . $totalRow, "=SUM(K11:K{$lastDataRow})");
        $sheet->setCellValue('L' . $totalRow, "=ROUND((E{$totalRow}/C{$totalRow})*100, 1)&\"%\"");

        // Styling Data & Total
        $sheet->getStyle('A11:L' . $totalRow)->applyFromArray([
            'font' => ['size' => 9, 'name' => 'Arial'],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'D0D5DD']]],
        ]);

        $sheet->getStyle('A' . $totalRow . ':L' . $totalRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'name' => 'Arial'],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => 'E2E8F0'],
            ],
        ]);

        // Alignment & Number Formats
        $sheet->getStyle('A11:A' . $totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C11:C' . $totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('D11:D' . $totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('E11:L' . $totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('L11:L' . $totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('C11:C' . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('D11:D' . $totalRow)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('E11:E' . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('F11:F' . $totalRow)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('G11:G' . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('H11:H' . $totalRow)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('I11:I' . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('J11:J' . $totalRow)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('K11:K' . $totalRow)->getNumberFormat()->setFormatCode('#,##0');

        // TTD Row
        // TTD Row (Dua Sisi)
        $ttdRow = $totalRow + 3;

        $pejabat1Jabatan = $kop['kop_pejabat1_jabatan'] ?? ($kop['kop_pejabat_jabatan'] ?? 'KEPALA BIDANG ASET DAERAH');
        $pejabat1Nama = $kop['kop_pejabat1_nama'] ?? ($kop['kop_pejabat_nama'] ?? 'YENI SJ AMIR, SH.MSi');
        $pejabat1Nip = $this->formatNip($kop['kop_pejabat1_nip'] ?? '');

        $pejabat2Jabatan = $kop['kop_pejabat2_jabatan'] ?? ($kop['kop_pejabat_jabatan'] ?? 'KEPALA BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH');
        $pejabat2Nama = $kop['kop_pejabat2_nama'] ?? ($kop['kop_pejabat_nama'] ?? 'YENI SJ AMIR, SH.MSi');
        $pejabat2Nip = $this->formatNip($kop['kop_pejabat2_nip'] ?? '');

        // Berikan ruang tanda tangan fisik yang lapang (4 baris kosong)
        for ($r = $ttdRow + 2; $r <= $ttdRow + 5; $r++) {
            $sheet->getRowDimension($r)->setRowHeight(16);
        }

        // Sisi Kiri: Penanda Tangan 1 (B:D)
        $sheet->setCellValue('B' . ($ttdRow + 1), $pejabat1Jabatan);
        $sheet->setCellValue('B' . ($ttdRow + 6), $pejabat1Nama);
        $sheet->setCellValue('B' . ($ttdRow + 7), $pejabat1Nip);

        $sheet->mergeCells('B' . ($ttdRow + 1) . ':D' . ($ttdRow + 1));
        $sheet->mergeCells('B' . ($ttdRow + 6) . ':D' . ($ttdRow + 6));
        $sheet->mergeCells('B' . ($ttdRow + 7) . ':D' . ($ttdRow + 7));

        $sheet->getStyle('B' . ($ttdRow + 1) . ':D' . ($ttdRow + 7))->applyFromArray([
            'font' => ['size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('B' . ($ttdRow + 1))->getFont()->setBold(true);
        $sheet->getStyle('B' . ($ttdRow + 6))->getFont()->setBold(true)->setUnderline(true);

        // Sisi Kanan: Penanda Tangan 2 (I:L)
        $sheet->setCellValue('I' . $ttdRow, ($kop['kop_kota_ttd'] ?? 'Banawa') . ', ' . date('d F Y'));
        $sheet->setCellValue('I' . ($ttdRow + 1), $pejabat2Jabatan);
        $sheet->setCellValue('I' . ($ttdRow + 6), $pejabat2Nama);
        $sheet->setCellValue('I' . ($ttdRow + 7), $pejabat2Nip);

        $sheet->mergeCells('I' . $ttdRow . ':L' . $ttdRow);
        $sheet->mergeCells('I' . ($ttdRow + 1) . ':L' . ($ttdRow + 1));
        $sheet->mergeCells('I' . ($ttdRow + 6) . ':L' . ($ttdRow + 6));
        $sheet->mergeCells('I' . ($ttdRow + 7) . ':L' . ($ttdRow + 7));

        $sheet->getStyle('I' . $ttdRow . ':L' . ($ttdRow + 7))->applyFromArray([
            'font' => ['size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('I' . ($ttdRow + 1))->getFont()->setBold(true);
        $sheet->getStyle('I' . ($ttdRow + 6))->getFont()->setBold(true)->setUnderline(true);

        // Column Widths
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(36);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(16);
        $sheet->getColumnDimension('I')->setWidth(12);
        $sheet->getColumnDimension('J')->setWidth(16);
        $sheet->getColumnDimension('K')->setWidth(12);
        $sheet->getColumnDimension('L')->setWidth(14);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'Rekapitulasi_Aset_Tanah_Per_OPD_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
