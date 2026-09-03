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
            'title_mode' => $input['title_mode'] ?? 'master',
            'report_title_id' => $input['report_title_id'] ?? '',
            'manual_title' => $input['manual_title'] ?? '',
        ];
    }

    /**
     * Membangun Query Builder berdasarkan filter pencarian.
     */
    public function buildQuery(array $filters)
    {
        $query = AsetTanah::with(['latestProses.statusProses', 'opdSipat', 'wilayahKecamatan']);

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
            if ($kat === 'sudah_bersertifikat') {
                $query->whereHas('latestProses.statusProses', function($sq) {
                    $sq->where('kategori', 'LIKE', '%bersertifikat%');
                });
            } elseif ($kat === 'dalam_proses') {
                $query->whereHas('latestProses.statusProses', function($sq) {
                    $sq->where('kategori', 'LIKE', '%proses%');
                });
            } elseif ($kat === 'belum_diproses') {
                $query->where(function($q) {
                    $q->doesntHave('latestProses')
                      ->orWhereHas('latestProses.statusProses', function($sq) {
                          $sq->where('kategori', 'LIKE', '%belum_diurus%');
                      });
                });
            } elseif ($kat === 'bermasalah') {
                $query->whereHas('latestProses.statusProses', function($sq) {
                    $sq->where('kategori', 'LIKE', '%kendala%');
                });
            } elseif ($kat === 'belum_bersertifikat') {
                // Aturan Baku User: Tanah Belum Bersertifikat = Tanah Seluruhnya - Tanah Bersertifikat - Tanah Bermasalah - Tanah Target
                $targetAsetIds = DB::table('sipat_target_sertifikat')->pluck('aset_tanah_id')->filter()->toArray();
                if (!empty($targetAsetIds)) {
                    $query->whereNotIn('id_aset', $targetAsetIds);
                }
                $query->where(function($q) {
                    $q->doesntHave('latestProses')
                      ->orWhereHas('latestProses.statusProses', function($sq) {
                          $sq->where('kategori', 'NOT LIKE', '%bersertifikat%')
                            ->where('kategori', 'NOT LIKE', '%kendala%');
                      });
                });
            } else {
                $query->whereHas('latestProses.statusProses', function($sq) use ($kat) {
                    $sq->where('kategori', 'LIKE', "%{$kat}%");
                });
            }
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
     * Memecahkan judul laporan yang aktif sesuai filter dan kategori status.
     */
    public function resolveReportTitle(array $filters): string
    {
        if (($filters['title_mode'] ?? 'master') === 'manual' && !empty($filters['manual_title'])) {
            return strtoupper($filters['manual_title']);
        }

        if (!empty($filters['report_title_id'])) {
            if (Schema::hasTable('report_titles')) {
                $titleRow = DB::table('report_titles')->where('id', $filters['report_title_id'])->first();
                if ($titleRow) {
                    return strtoupper($titleRow->judul);
                }
            }
        }

        // Auto-select matching title based on kategori_status
        $kat = $filters['kategori_status'] ?? '';
        if (!empty($kat) && Schema::hasTable('report_titles')) {
            $titleRow = DB::table('report_titles')->where('kategori_status', $kat)->where('aktif', 1)->first();
            if ($titleRow) {
                return strtoupper($titleRow->judul);
            }
        }

        $kop = $this->getKopSettings();
        return strtoupper($kop['kop_nama_laporan_aset'] ?? 'LAPORAN REKAPITULASI ASET TANAH KABUPATEN DONGGALA');
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
            'kop_pejabat_jabatan' => 'Kepala Bidang Pengelolaan Aset Daerah',
            'kop_pejabat_nama' => 'H. MUHAMMAD NATSIR, S.E., M.Si.',
            'kop_pejabat_nip' => '19780512 200501 1 008',
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

        return $defaults;
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

        $year = date('Y');
        
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

        // --- 1. KOP SURAT RESMI ---
        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');
        $sheet->mergeCells('A3:F3');
        $sheet->setCellValue('A1', strtoupper($kop['kop_nama_instansi'] ?? 'PEMERINTAH KABUPATEN DONGGALA'));
        $sheet->setCellValue('A2', strtoupper($kop['kop_nama_unit'] ?? 'BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH'));
        $sheet->setCellValue('A3', strtoupper($kop['kop_subunit'] ?? 'Bidang Pengelolaan Aset Daerah'));

        // --- 2. JUDUL LAPORAN KATEGORI ---
        $sheet->mergeCells('A5:F5');
        $sheet->mergeCells('A6:F6');
        
        $fullTitle = strtoupper($selectedTitle);
        if (!str_contains($fullTitle, $year)) {
            $fullTitle .= ' ' . $year;
        }

        $sheet->setCellValue('A5', $fullTitle);
        $sheet->setCellValue('A6', strtoupper($kop['kop_nama_instansi'] ?? 'PEMERINTAH KABUPATEN DONGGALA'));

        // --- 3. HEADER TABEL 6 KOLOM (ROW 8 & 9) ---
        $sheet->mergeCells('A8:A9');
        $sheet->mergeCells('B8:B9');
        $sheet->mergeCells('C8:E8');
        $sheet->mergeCells('F8:F9');

        $sheet->setCellValue('A8', 'NO.');
        $sheet->setCellValue('B8', 'Kode Aset / NIBAR');
        $sheet->setCellValue('C8', $groupHeader);
        $sheet->setCellValue('F8', 'Keterangan');

        $sheet->setCellValue('C9', 'Bidang');
        $sheet->setCellValue('D9', 'Luas(m2)');
        $sheet->setCellValue('E9', 'Nilai(Rp)');

        // --- 4. DATA ROWS (ROW 10 ONWARDS) ---
        $rowNumber = 10;
        $no = 1;
        $totalLuas = 0;
        $totalNilai = 0;

        foreach ($rows as $row) {
            $luasVal = (float) ($row->luas ?? 0);
            $nilaiVal = (float) ($row->harga_perolehan ?? 0);

            $totalLuas += $luasVal;
            $totalNilai += $nilaiVal;

            $kodeAsetText = $row->kode_aset ?? '-';
            $bidangText = $row->peruntukan ?? $row->nama_aset ?? '-';
            $keteranganText = $row->keterangan ?? $row->opdSipat->nama ?? $row->opd ?? '-';

            $sheet->setCellValue('A' . $rowNumber, $no++);
            $sheet->setCellValue('B' . $rowNumber, $kodeAsetText);
            $sheet->setCellValue('C' . $rowNumber, $bidangText);
            $sheet->setCellValue('D' . $rowNumber, $luasVal);
            $sheet->setCellValue('E' . $rowNumber, $nilaiVal);
            $sheet->setCellValue('F' . $rowNumber, $keteranganText);

            $rowNumber++;
        }

        // --- 5. TOTAL ROW ---
        $totalRow = $rowNumber;
        $sheet->mergeCells('A' . $totalRow . ':C' . $totalRow);
        $sheet->setCellValue('A' . $totalRow, 'JUMLAH / TOTAL');
        $sheet->setCellValue('D' . $totalRow, $totalLuas);
        $sheet->setCellValue('E' . $totalRow, $totalNilai);
        $sheet->setCellValue('F' . $totalRow, '');

        // --- 6. LEMBAR PENGESAHAN (TTD RESMI) ---
        $ttdStartRow = $totalRow + 3;
        $sheet->mergeCells('E' . $ttdStartRow . ':F' . $ttdStartRow);
        $sheet->mergeCells('E' . ($ttdStartRow + 1) . ':F' . ($ttdStartRow + 1));
        $sheet->mergeCells('E' . ($ttdStartRow + 4) . ':F' . ($ttdStartRow + 4));
        $sheet->mergeCells('E' . ($ttdStartRow + 5) . ':F' . ($ttdStartRow + 5));

        $sheet->setCellValue('E' . $ttdStartRow, ($kop['kop_kota_ttd'] ?? 'Banawa') . ', ' . date('d-m-Y'));
        $sheet->setCellValue('E' . ($ttdStartRow + 1), $kop['kop_pejabat_jabatan'] ?? 'Kepala Bidang Pengelolaan Aset Daerah');
        $sheet->setCellValue('E' . ($ttdStartRow + 4), $kop['kop_pejabat_nama'] ?? 'H. MUHAMMAD NATSIR, S.E., M.Si.');
        $sheet->setCellValue('E' . ($ttdStartRow + 5), 'NIP. ' . ($kop['kop_pejabat_nip'] ?? '19780512 200501 1 008'));

        // --- 7. STYLING ---
        // KOP Styling
        $sheet->getStyle('A1:F3')->applyFromArray([
            'font' => ['bold' => true, 'name' => 'Arial'],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A1')->getFont()->setSize(11);
        $sheet->getStyle('A2')->getFont()->setSize(13);
        $sheet->getStyle('A3')->getFont()->setSize(10);

        // Judul Styling
        $sheet->getStyle('A5:F6')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'name' => 'Arial'],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        // Header Table Styling (Row 8 & 9)
        $sheet->getStyle('A8:F9')->applyFromArray([
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
        $sheet->getStyle('A10:F' . $totalRow)->applyFromArray([
            'font' => ['size' => 10, 'name' => 'Arial'],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Total Row Styling
        $sheet->getStyle('A' . $totalRow . ':F' . $totalRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'name' => 'Arial'],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => 'EAEAEA'],
            ],
        ]);

        // Alignments & Number Formats
        $sheet->getStyle('A10:A' . $totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B10:B' . $totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('D10:D' . $totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('E10:E' . $totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle('D10:D' . $totalRow)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('E10:E' . $totalRow)->getNumberFormat()->setFormatCode('#,##0.00');

        // TTD Styling
        $sheet->getStyle('E' . $ttdStartRow . ':F' . ($ttdStartRow + 5))->applyFromArray([
            'font' => ['size' => 10, 'name' => 'Arial'],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('E' . ($ttdStartRow + 1))->getFont()->setBold(true);
        $sheet->getStyle('E' . ($ttdStartRow + 4))->getFont()->setBold(true)->setUnderline(true);

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(24);
        $sheet->getColumnDimension('C')->setWidth(38);
        $sheet->getColumnDimension('D')->setWidth(16);
        $sheet->getColumnDimension('E')->setWidth(24);
        $sheet->getColumnDimension('F')->setWidth(30);

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
        $ttdRow = $totalRow + 3;
        $sheet->setCellValue('I' . $ttdRow, ($kop['kop_kota_ttd'] ?? 'Banawa') . ', ' . date('d F Y'));
        $sheet->setCellValue('I' . ($ttdRow + 1), $kop['kop_pejabat_jabatan'] ?? 'Kepala Bidang Pengelolaan Aset Daerah');
        $sheet->setCellValue('I' . ($ttdRow + 5), $kop['kop_pejabat_nama'] ?? 'H. MUHAMMAD NATSIR, S.E., M.Si.');
        $sheet->setCellValue('I' . ($ttdRow + 6), 'NIP. ' . ($kop['kop_pejabat_nip'] ?? '-'));

        $sheet->mergeCells('I' . $ttdRow . ':L' . $ttdRow);
        $sheet->mergeCells('I' . ($ttdRow + 1) . ':L' . ($ttdRow + 1));
        $sheet->mergeCells('I' . ($ttdRow + 5) . ':L' . ($ttdRow + 5));
        $sheet->mergeCells('I' . ($ttdRow + 6) . ':L' . ($ttdRow + 6));

        $sheet->getStyle('I' . $ttdRow . ':L' . ($ttdRow + 6))->applyFromArray([
            'font' => ['size' => 9, 'name' => 'Arial'],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('I' . ($ttdRow + 1))->getFont()->setBold(true);
        $sheet->getStyle('I' . ($ttdRow + 5))->getFont()->setBold(true)->setUnderline(true);

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
