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
        $query = AsetTanah::with(['latestProses.statusProses', 'opdSipat']);

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

        // Filter Kategori Status (Belum Diproses, Dalam Proses, Sudah Bersertifikat, Bermasalah, Belum Bersertifikat)
        $kat = $filters['kategori_status'] ?? '';
        if ($kat !== '') {
            if ($kat === 'sudah_bersertifikat') {
                $query->whereHas('latestProses', function($q) {
                    $q->whereIn('id_status', [1, 4, 10])
                      ->orWhereHas('statusProses', function($sq) {
                          $sq->where('kategori', 'bersertifikat');
                      });
                });
            } elseif ($kat === 'dalam_proses') {
                $query->whereHas('latestProses', function($q) {
                    $q->whereIn('id_status', [5, 6, 7, 8, 9, 11])
                      ->orWhereHas('statusProses', function($sq) {
                          $sq->where('kategori', 'proses');
                      });
                });
            } elseif ($kat === 'belum_diproses') {
                $query->where(function($q) {
                    $q->doesntHave('latestProses')
                      ->orWhereHas('latestProses', function($lq) {
                          $lq->where('id_status', 2)
                            ->orWhereHas('statusProses', function($sq) {
                                $sq->where('kategori', 'belum_diurus');
                            });
                      });
                });
            } elseif ($kat === 'bermasalah') {
                $query->whereHas('latestProses', function($q) {
                    $q->where('id_status', 3)
                      ->orWhereHas('statusProses', function($sq) {
                          $sq->where('kategori', 'kendala');
                      });
                });
            } elseif ($kat === 'belum_bersertifikat') {
                // Aturan Baku User: Tanah Belum Bersertifikat = Tanah Seluruhnya - Tanah Bersertifikat - Tanah Bermasalah - Tanah Target
                $targetAsetIds = DB::table('sipat_target_sertifikat')->pluck('aset_tanah_id')->filter()->toArray();
                if (!empty($targetAsetIds)) {
                    $query->whereNotIn('id_aset', $targetAsetIds);
                }
                $query->where(function($q) {
                    $q->doesntHave('latestProses')
                      ->orWhereHas('latestProses', function($lq) {
                          $lq->whereNotIn('id_status', [1, 4, 10, 3]) // Exclude Bersertifikat & Bermasalah
                            ->whereHas('statusProses', function($sq) {
                                $sq->whereNotIn('kategori', ['bersertifikat', 'kendala']);
                            });
                      });
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
            $stName = $row->latestProses->statusProses->nama_status ?? '';
            $stKat = $row->latestProses->statusProses->kategori ?? '';
            $stId = $row->latestProses->id_status ?? 0;

            if (in_array($stId, [1, 4, 10]) || $stKat === 'bersertifikat') {
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
     * Generate Excel file dengan layout simpel 5 kolom (NO., Bidang, Luas(m2), Nilai(Rp), Keterangan)
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
        $sheet->mergeCells('A1:E1');
        $sheet->mergeCells('A2:E2');
        $sheet->mergeCells('A3:E3');
        $sheet->setCellValue('A1', strtoupper($kop['kop_nama_instansi'] ?? 'PEMERINTAH KABUPATEN DONGGALA'));
        $sheet->setCellValue('A2', strtoupper($kop['kop_nama_unit'] ?? 'BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH'));
        $sheet->setCellValue('A3', strtoupper($kop['kop_subunit'] ?? 'Bidang Pengelolaan Aset Daerah'));

        // --- 2. JUDUL LAPORAN KATEGORI ---
        $sheet->mergeCells('A5:E5');
        $sheet->mergeCells('A6:E6');
        
        $fullTitle = strtoupper($selectedTitle);
        if (!str_contains($fullTitle, $year)) {
            $fullTitle .= ' ' . $year;
        }

        $sheet->setCellValue('A5', $fullTitle);
        $sheet->setCellValue('A6', strtoupper($kop['kop_nama_instansi'] ?? 'PEMERINTAH KABUPATEN DONGGALA'));

        // --- 3. HEADER TABEL SIMPEL 5 KOLOM (ROW 8 & 9) ---
        $sheet->mergeCells('A8:A9');
        $sheet->mergeCells('B8:D8');
        $sheet->mergeCells('E8:E9');

        $sheet->setCellValue('A8', 'NO.');
        $sheet->setCellValue('B8', $groupHeader);
        $sheet->setCellValue('E8', 'Keterangan');

        $sheet->setCellValue('B9', 'Bidang');
        $sheet->setCellValue('C9', 'Luas(m2)');
        $sheet->setCellValue('D9', 'Nilai(Rp)');

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

            $bidangText = $row->peruntukan ?? $row->nama_aset ?? '-';
            $keteranganText = $row->keterangan ?? $row->opdSipat->nama ?? $row->opd ?? '-';

            $sheet->setCellValue('A' . $rowNumber, $no++);
            $sheet->setCellValue('B' . $rowNumber, $bidangText);
            $sheet->setCellValue('C' . $rowNumber, $luasVal);
            $sheet->setCellValue('D' . $rowNumber, $nilaiVal);
            $sheet->setCellValue('E' . $rowNumber, $keteranganText);

            $rowNumber++;
        }

        // --- 5. TOTAL ROW ---
        $totalRow = $rowNumber;
        $sheet->mergeCells('A' . $totalRow . ':B' . $totalRow);
        $sheet->setCellValue('A' . $totalRow, 'JUMLAH / TOTAL');
        $sheet->setCellValue('C' . $totalRow, $totalLuas);
        $sheet->setCellValue('D' . $totalRow, $totalNilai);
        $sheet->setCellValue('E' . $totalRow, '');

        // --- 6. LEMBAR PENGESAHAN (TTD RESMI) ---
        $ttdStartRow = $totalRow + 3;
        $sheet->mergeCells('D' . $ttdStartRow . ':E' . $ttdStartRow);
        $sheet->mergeCells('D' . ($ttdStartRow + 1) . ':E' . ($ttdStartRow + 1));
        $sheet->mergeCells('D' . ($ttdStartRow + 4) . ':E' . ($ttdStartRow + 4));
        $sheet->mergeCells('D' . ($ttdStartRow + 5) . ':E' . ($ttdStartRow + 5));

        $sheet->setCellValue('D' . $ttdStartRow, ($kop['kop_kota_ttd'] ?? 'Banawa') . ', ' . date('d-m-Y'));
        $sheet->setCellValue('D' . ($ttdStartRow + 1), $kop['kop_pejabat_jabatan'] ?? 'Kepala Bidang Pengelolaan Aset Daerah');
        $sheet->setCellValue('D' . ($ttdStartRow + 4), $kop['kop_pejabat_nama'] ?? 'H. MUHAMMAD NATSIR, S.E., M.Si.');
        $sheet->setCellValue('D' . ($ttdStartRow + 5), 'NIP. ' . ($kop['kop_pejabat_nip'] ?? '19780512 200501 1 008'));

        // --- 7. STYLING ---
        // KOP Styling
        $sheet->getStyle('A1:E3')->applyFromArray([
            'font' => ['bold' => true, 'name' => 'Arial'],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A1')->getFont()->setSize(11);
        $sheet->getStyle('A2')->getFont()->setSize(13);
        $sheet->getStyle('A3')->getFont()->setSize(10);

        // Judul Styling
        $sheet->getStyle('A5:E6')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'name' => 'Arial'],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        // Header Table Styling (Row 8 & 9) matching user screenshot
        $sheet->getStyle('A8:E9')->applyFromArray([
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
        $sheet->getStyle('A10:E' . $totalRow)->applyFromArray([
            'font' => ['size' => 10, 'name' => 'Arial'],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Total Row Styling
        $sheet->getStyle('A' . $totalRow . ':E' . $totalRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'name' => 'Arial'],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => 'EAEAEA'],
            ],
        ]);

        // Alignments & Number Formats
        $sheet->getStyle('A10:A' . $totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C10:C' . $totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('D10:D' . $totalRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle('C10:C' . $totalRow)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('D10:D' . $totalRow)->getNumberFormat()->setFormatCode('#,##0.00');

        // TTD Styling
        $sheet->getStyle('D' . $ttdStartRow . ':E' . ($ttdStartRow + 5))->applyFromArray([
            'font' => ['size' => 10, 'name' => 'Arial'],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('D' . ($ttdStartRow + 1))->getFont()->setBold(true);
        $sheet->getStyle('D' . ($ttdStartRow + 4))->getFont()->setBold(true)->setUnderline(true);

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(42);
        $sheet->getColumnDimension('C')->setWidth(16);
        $sheet->getColumnDimension('D')->setWidth(24);
        $sheet->getColumnDimension('E')->setWidth(30);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'Daftar_Aset_Tanah_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
