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
        $rawStatus = $input['status'] ?? null;
        $statusIds = is_array($rawStatus) ? array_filter($rawStatus) : ($rawStatus ? [$rawStatus] : []);

        return [
            'opd_id' => $input['opd_id'] ?? ($input['opd'] ?? ''),
            'status' => $statusIds,
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

        if (!empty($filters['status'])) {
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
            if (!empty($stName) && strtolower($stName) !== 'belum diurus') {
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
     * Generate Excel file menggunakan native PhpSpreadsheet agar format 100% sama dengan SIPAT lama
     */
    public function exportExcel($rows, array $filters, array $summary, array $kop, string $selectedTitle)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Aset');
        $summarySheet = $spreadsheet->createSheet();
        $summarySheet->setTitle('Ringkasan');

        $startColumn = 'A';
        $endColumn = 'K';
        $titleStartColumn = 'B';
        $sheet->mergeCells($titleStartColumn . '1:' . $endColumn . '1');
        $sheet->mergeCells($titleStartColumn . '2:' . $endColumn . '2');
        $sheet->mergeCells($titleStartColumn . '3:' . $endColumn . '3');
        $sheet->mergeCells($titleStartColumn . '4:' . $endColumn . '4');
        $sheet->mergeCells($startColumn . '5:' . $endColumn . '5');
        $sheet->mergeCells($startColumn . '6:' . $endColumn . '6');

        $sheet->setCellValue($titleStartColumn . '1', (string) ($kop['kop_nama_instansi'] ?? ''));
        $sheet->setCellValue($titleStartColumn . '2', (string) ($kop['kop_nama_unit'] ?? ''));
        $sheet->setCellValue($titleStartColumn . '3', (string) ($kop['kop_subunit'] ?? ''));
        $sheet->setCellValue($titleStartColumn . '4', (string) ($selectedTitle));
        $sheet->setCellValue('A5', trim((string) (($kop['kop_alamat'] ?? '') . ' | ' . ($kop['kop_kontak'] ?? '')), ' |'));
        
        $generatedAt = date('d-m-Y H:i');
        $sheet->setCellValue('A6', 'Dicetak pada: ' . $generatedAt);

        $filterText = 'Filter aktif: ';
        if (!empty($summary['activeFilters'])) {
            $filterText .= implode(' | ', array_map(
                static fn ($item) => ($item['label'] ?? '') . ': ' . ($item['value'] ?? ''),
                $summary['activeFilters']
            ));
        } else {
            $filterText .= 'Semua data aset tanah';
        }
        $sheet->mergeCells('A8:K8');
        $sheet->setCellValue('A8', $filterText);

        $sheet->mergeCells('A10:B10');
        $sheet->mergeCells('D10:E10');
        $sheet->mergeCells('G10:H10');
        $sheet->setCellValue('A10', 'Total Data');
        $sheet->setCellValue('C10', (int) ($summary['total_data'] ?? 0));
        $sheet->setCellValue('D10', 'Total Nilai');
        // Parse the formatted money string back to float for Excel, or just pass it if it's already clean
        $rawNilai = (float) str_replace(['Rp', '.', ',', ' '], ['', '', '.', ''], $summary['total_nilai'] ?? '0');
        $sheet->setCellValue('F10', $rawNilai);
        $sheet->setCellValue('G10', 'Sudah Berstatus');
        $sheet->setCellValue('I10', (int) ($summary['total_berstatus'] ?? 0));

        $headers = ['No', 'Kode Aset', 'Nama Aset', 'Peruntukan', 'OPD', 'Luas (m2)', 'Nilai Perolehan', 'Tanggal Perolehan', 'Status', 'Durasi', 'Keterangan'];
        $headerRow = 12;
        foreach ($headers as $index => $header) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column . $headerRow, $header);
        }

        $rowNumber = $headerRow + 1;
        $no = 1;
        foreach ($rows as $row) {
            $sheet->setCellValue('A' . $rowNumber, $no++);
            $sheet->setCellValue('B' . $rowNumber, (string) ($row->kode_aset ?? ''));
            $sheet->setCellValue('C' . $rowNumber, (string) ($row->nama_aset ?? ''));
            $sheet->setCellValue('D' . $rowNumber, (string) ($row->peruntukan ?? '-'));
            $sheet->setCellValue('E' . $rowNumber, (string) ($row->opdSipat->nama ?? $row->opd ?? '-'));
            $sheet->setCellValue('F' . $rowNumber, (float) ($row->luas ?? 0));
            $sheet->setCellValue('G' . $rowNumber, (float) ($row->harga_perolehan ?? 0));
            $sheet->setCellValue('H' . $rowNumber, $row->tanggal_perolehan ? date('d-m-Y', strtotime($row->tanggal_perolehan)) : '-');
            $sheet->setCellValue('I' . $rowNumber, (string) ($row->latestProses->statusProses->nama_status ?? 'Belum Diurus'));
            $sheet->setCellValue('J' . $rowNumber, (string) ($row->latestProses->durasi_hari ?? '-'));
            $sheet->setCellValue('K' . $rowNumber, (string) ($row->keterangan ?? ''));
            $rowNumber++;
        }

        $lastDataRow = max($headerRow + 1, $rowNumber - 1);

        $sheet->getStyle('B1:K1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('B2:K2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '0B4F84']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('B3:K3')->applyFromArray([
            'font' => ['size' => 11],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('B4:K4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 15],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A5:K8')->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => '334155']],
        ]);
        $sheet->getStyle('A10:I10')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => 'E8EEF5'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '9FB3C8'],
                ],
            ],
        ]);
        $sheet->getStyle('A12:K12')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => '163A63'],
            ],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A12:K' . $lastDataRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'AAB7C4'],
                ],
            ],
        ]);
        
        for ($row = $headerRow + 1; $row <= $lastDataRow; $row++) {
            if (($row - ($headerRow + 1)) % 2 === 0) {
                $sheet->getStyle('A' . $row . ':K' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheet->getStyle('A' . $row . ':K' . $row)->getFill()->getStartColor()->setRGB('F8FAFC');
            }
        }

        $sheet->getStyle('F13:G' . $lastDataRow)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('F10')->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A13:A' . $lastDataRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F13:G' . $lastDataRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('H13:H' . $lastDataRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('J13:J' . $lastDataRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:K' . $lastDataRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->getRowDimension(1)->setRowHeight(26);
        $sheet->getRowDimension(2)->setRowHeight(28);
        $sheet->getRowDimension(3)->setRowHeight(22);
        $sheet->getRowDimension(4)->setRowHeight(24);
        $sheet->freezePane('A13');

        // ==== SUMMARY SHEET ====
        $summarySheet->mergeCells('A1:F1');
        $summarySheet->mergeCells('A2:F2');
        $summarySheet->mergeCells('A3:F3');
        $summarySheet->setCellValue('A1', (string) ($kop['kop_nama_instansi'] ?? ''));
        $summarySheet->setCellValue('A2', (string) ($selectedTitle));
        $summarySheet->setCellValue('A3', 'Ringkasan Export');
        $summarySheet->setCellValue('A5', 'Dicetak pada');
        $summarySheet->setCellValue('B5', $generatedAt);
        $summarySheet->setCellValue('A7', 'Total Data');
        $summarySheet->setCellValue('B7', (int) ($summary['total_data'] ?? 0));
        $summarySheet->setCellValue('A8', 'Total Nilai');
        $summarySheet->setCellValue('B8', $rawNilai);
        $summarySheet->setCellValue('A9', 'Sudah Berstatus');
        $summarySheet->setCellValue('B9', (int) ($summary['total_berstatus'] ?? 0));
        $summarySheet->setCellValue('A11', 'Filter Aktif');

        $row = 12;
        if (!empty($summary['activeFilters'])) {
            foreach ($summary['activeFilters'] as $filter) {
                $summarySheet->setCellValue('A' . $row, (string) ($filter['label'] ?? ''));
                $summarySheet->setCellValue('B' . $row, (string) ($filter['value'] ?? ''));
                $row++;
            }
        } else {
            $summarySheet->setCellValue('A' . $row, 'Semua data aset tanah');
        }

        $summarySheet->getStyle('A1:F3')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $summarySheet->getStyle('A7:B9')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'AAB7C4'],
                ],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => 'F8FAFC'],
            ],
        ]);
        $summarySheet->getStyle('B8')->getNumberFormat()->setFormatCode('#,##0.00');
        foreach (range('A', 'F') as $column) {
            $summarySheet->getColumnDimension($column)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'Laporan_Aset_Tanah_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
