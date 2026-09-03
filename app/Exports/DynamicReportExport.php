<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Kelas Induk Ekspor Laporan Dinamis Premium (Abstract Base Excel Exporter)
 * 
 * Mengatur tata letak visual premium, Kop instansi, penyematan logo Bapenda,
 * filter aktif, kalkulasi ringkasan, zebra striping, formatting data, dan tanda tangan dinamis.
 */
abstract class DynamicReportExport implements WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithCustomStartCell, WithColumnFormatting, WithEvents
{
    /**
     * Pemetaan header kolom laporan.
     */
    protected array $headers;

    /**
     * Parameter filter aktif yang digunakan.
     */
    protected array $filters;

    /**
     * Judul/tipe laporan saat ini.
     */
    protected string $reportTitle;

    /**
     * Pengaturan dokumen dinamis (kop, ttd, dll.)
     */
    protected array $docSettings;

    /**
     * Counter nomor urut baris excel.
     */
    protected static int $rowNumber = 0;

    /**
     * Inisiasi ekspor dinamis premium.
     * 
     * @param array<string, string> $headers
     * @param array $filters
     * @param string $reportTitle
     * @param array $docSettings
     */
    public function __construct(array $headers, array $filters = [], string $reportTitle = 'Laporan Laporan', array $docSettings = [])
    {
        $this->headers = $headers;
        $this->filters = $filters;
        $this->reportTitle = $reportTitle;

        // Sediakan fallback jika $docSettings tidak dilewatkan
        if (empty($docSettings)) {
            $docSettings = app(\App\Services\ReportDocumentSettingService::class)->getSettingsForReportType('status');
        }
        $this->docSettings = $docSettings;
        self::$rowNumber = 0;
    }

    /**
     * Tentukan sel awal penulisan tabel utama agar kop surat dan filter tidak menimpa data.
     *
     * @return string
     */
    public function startCell(): string
    {
        return 'A12';
    }

    /**
     * Mendapatkan daftar label header untuk kolom tabel.
     *
     * @return array
     */
    public function headings(): array
    {
        // Menyisipkan kolom nomor urut "No" di awal kolom data
        return array_merge(['No'], array_values($this->headers));
    }

    /**
     * Memetakan baris data model ke baris Excel dengan format presisi.
     *
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        self::$rowNumber++;
        $mapped = [self::$rowNumber];

        foreach (array_keys($this->headers) as $key) {
            $value = $row->{$key};

            if ($key === 'nilai_perolehan') {
                $mapped[] = $value ? (float) $value : 0;
            } elseif ($key === 'tgl_stnk' || $key === 'tgl_perolehan') {
                $mapped[] = $value ? \Carbon\Carbon::parse($value)->format('d-m-Y') : '-';
            } else {
                $mapped[] = $value ?? '-';
            }
        }

        return $mapped;
    }

    /**
     * Format kolom numerik agar dikenali Excel sebagai mata uang (Rupiah).
     *
     * @return array
     */
    public function columnFormats(): array
    {
        $formats = [];
        $keys = array_merge(['no'], array_keys($this->headers));
        foreach ($keys as $index => $key) {
            if ($key === 'nilai_perolehan') {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                $formats[$columnLetter] = '"Rp"#,##0';
            }
        }
        return $formats;
    }

    /**
     * Menerapkan style dasar untuk header tabel.
     *
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            12 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 10
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E40AF'] // Bapenda Navy
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ]
            ],
        ];
    }

    /**
     * Mendaftarkan event PhpSpreadsheet untuk mendesain Kop Surat, Logo, Zebra Striping, & Gridlines.
     *
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();

                $paperSizes = [
                    'A4' => PageSetup::PAPERSIZE_A4,
                    'F4' => PageSetup::PAPERSIZE_FOLIO,
                    'Letter' => PageSetup::PAPERSIZE_LETTER,
                    'Legal' => PageSetup::PAPERSIZE_LEGAL,
                ];
                $paperSize = $this->docSettings['settings']['paper_size'] ?? 'A4';
                $orientation = ($this->docSettings['settings']['orientation'] ?? 'L') === 'P'
                    ? PageSetup::ORIENTATION_PORTRAIT
                    : PageSetup::ORIENTATION_LANDSCAPE;

                $sheet->getPageSetup()
                    ->setPaperSize($paperSizes[$paperSize] ?? PageSetup::PAPERSIZE_A4)
                    ->setOrientation($orientation)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);

                // 1. PENYEMATAN LOGO DINAMIS (Coordinates A1)
                try {
                    $logoFile = $this->docSettings['letterhead']['logo_path'] ?? 'images/logo-sulteng.png';
                    $logoPath = public_path($logoFile);
                    if (file_exists($logoPath) && is_file($logoPath) && filesize($logoPath) > 0) {
                        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                        $drawing->setName('Logo KOP');
                        $drawing->setDescription('Logo Instansi');
                        $drawing->setPath($logoPath);
                        $drawing->setCoordinates('A1');
                        $drawing->setHeight(70);
                        $drawing->setWorksheet($sheet);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Gagal menggambar logo KOP pada Excel: ' . $e->getMessage());
                }

                // 2. KOP SURAT (Rows 1 to 4) - Di-merge dari kolom B hingga kolom akhir
                $sheet->mergeCells("B1:{$highestColumn}1");
                $sheet->mergeCells("B2:{$highestColumn}2");
                $sheet->mergeCells("B3:{$highestColumn}3");
                $sheet->mergeCells("B4:{$highestColumn}4");

                $sheet->setCellValue('B1', $this->docSettings['letterhead']['nama_pemerintah']);
                $sheet->setCellValue('B2', $this->docSettings['letterhead']['nama_instansi']);
                $sheet->setCellValue('B3', $this->docSettings['letterhead']['nama_unit'] ?? '');

                $contactInfo = $this->docSettings['letterhead']['alamat'] ?? '';
                if (!empty($this->docSettings['letterhead']['telepon'])) {
                    $contactInfo .= ' | Telp: ' . $this->docSettings['letterhead']['telepon'];
                }
                if (!empty($this->docSettings['letterhead']['email'])) {
                    $contactInfo .= ' | Email: ' . $this->docSettings['letterhead']['email'];
                }
                if (!empty($this->docSettings['letterhead']['website'])) {
                    $contactInfo .= ' | Web: ' . $this->docSettings['letterhead']['website'];
                }
                $sheet->setCellValue('B4', $contactInfo);

                // Align Kop Tengah
                $kopRange = "B1:{$highestColumn}4";
                $sheet->getStyle($kopRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($kopRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                // Penataan Font Kop Surat
                $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(12)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));
                $sheet->getStyle('B2')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1E40AF'));
                $sheet->getStyle('B3')->getFont()->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('334155'));
                $sheet->getStyle('B4')->getFont()->setSize(9)->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));

                // Garis ganda pembatas Kop (Double Underline) di baris 5
                $sheet->getStyle("A5:{$highestColumn}5")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE)->getColor()->setRGB('1E293B');

                // 3. JUDUL LAPORAN (Row 6)
                $source = $this->filters['source'] ?? 'real';
                $sourceLabel = $source === 'ebmd' ? ' (E-BMD)' : ' (DATA RIIL)';
                $sheet->mergeCells("A6:{$highestColumn}6");
                $sheet->setCellValue('A6', strtoupper($this->reportTitle) . $sourceLabel);
                $sheet->getStyle('A6')->getFont()->setBold(true)->setSize(13)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));
                $sheet->getStyle('A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A6')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                // 4. INFO CETAK (Row 7)
                $sheet->mergeCells("A7:{$highestColumn}7");
                $sheet->setCellValue('A7', 'Dicetak pada: ' . \Carbon\Carbon::now()->translatedFormat('d F Y H:i') . ' WITA');
                $sheet->getStyle('A7')->getFont()->setSize(9)->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));
                $sheet->getStyle('A7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // 5. FILTER AKTIF (Row 9)
                $sheet->mergeCells("A9:{$highestColumn}9");

                $sourceName = $source === 'ebmd' ? 'Data e-BMD' : 'Data Real (Operasional)';
                $cond = $this->filters['kondisi'] ?? 'Semua';
                $yr = $this->filters['tahun'] ?? 'Semua';
                $filterText = "FILTER AKTIF -> Sumber: " . strtoupper($sourceName) . " | Kondisi: " . strtoupper($cond) . " | Tahun: " . $yr;
                if (!empty($this->filters['opd_id'])) {
                    $selectedOpd = \App\Models\Opd::find($this->filters['opd_id']);
                    if ($selectedOpd) {
                        $filterText .= " | OPD: " . strtoupper($selectedOpd->nama);
                    }
                }
                $sheet->setCellValue('A9', $filterText);
                $sheet->getStyle("A9:{$highestColumn}9")->getFont()->setBold(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1D4ED8'));
                $sheet->getStyle("A9:{$highestColumn}9")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EFF6FF');
                $sheet->getStyle("A9:{$highestColumn}9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A9:{$highestColumn}9")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                // 6. TINGGI BARIS (Row Heights)
                $sheet->getRowDimension(1)->setRowHeight(20);
                $sheet->getRowDimension(2)->setRowHeight(24);
                $sheet->getRowDimension(3)->setRowHeight(18);
                $sheet->getRowDimension(4)->setRowHeight(18);
                $sheet->getRowDimension(6)->setRowHeight(26);
                $sheet->getRowDimension(9)->setRowHeight(24);
                $sheet->getRowDimension(12)->setRowHeight(26);

                // 7. BORDER TIPIS TABEL DATA (Thin Gridlines)
                $tableRange = "A12:{$highestColumn}{$highestRow}";
                $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

                // 8. ALIGNMENT DATA & ZEBRA STRIPING
                $keys = array_merge(['no'], array_keys($this->headers));
                for ($row = 13; $row <= $highestRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(20);

                    // Zebra stripe pada baris genap (#F8FAFC)
                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                    }

                    // Format alignment sel secara dinamis
                    foreach ($keys as $index => $key) {
                        $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                        $cellCoord = "{$columnLetter}{$row}";

                        if ($key === 'no' || $key === 'no_polisi' || $key === 'tgl_stnk' || $key === 'tgl_perolehan') {
                            $sheet->getStyle($cellCoord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        } elseif ($key === 'nilai_perolehan') {
                            $sheet->getStyle($cellCoord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        } else {
                            $sheet->getStyle($cellCoord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        }

                        $sheet->getStyle($cellCoord)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    }
                }

                // 9. TANDA TANGAN DINAMIS DI BAGIAN BAWAH KANAN EXCEL
                if ($this->docSettings['settings']['show_signature'] ?? true) {
                    $sigRowStart = $highestRow + 3;

                    // Cari indeks kolom penempatan tanda tangan (2 kolom sebelum kolom akhir agar rata kanan bagus)
                    $sigColIndex = max(1, \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn) - 2);
                    $sigCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($sigColIndex);

                    // Kota TTD dan Tanggal
                    $sheet->setCellValue("{$sigCol}{$sigRowStart}", ($this->docSettings['signatory']['kota_ttd'] ?? 'Palu') . ', ' . \Carbon\Carbon::now()->translatedFormat('d F Y'));
                    $sheet->getStyle("{$sigCol}{$sigRowStart}")->getFont()->setSize(9.5)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('334155'));

                    // Jabatan Pejabat
                    $sheet->setCellValue("{$sigCol}" . ($sigRowStart + 1), ($this->docSettings['signatory']['jabatan'] ?? 'Plt. Kepala Badan Pendapatan Daerah') . ',');
                    $sheet->getStyle("{$sigCol}" . ($sigRowStart + 1))->getFont()->setBold(true)->setSize(9.5)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));

                    // Menyematkan TTD gambar jika di-upload
                    if (!empty($this->docSettings['signatory']['signature_image_path'])) {
                        $sigImgFile = $this->docSettings['signatory']['signature_image_path'];
                        $sigImgPath = public_path($sigImgFile);
                        if (file_exists($sigImgPath) && is_file($sigImgPath) && filesize($sigImgPath) > 0) {
                            try {
                                $drawingSig = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                                $drawingSig->setName('Tanda Tangan Pejabat');
                                $drawingSig->setDescription('TTD');
                                $drawingSig->setPath($sigImgPath);
                                $drawingSig->setCoordinates("{$sigCol}" . ($sigRowStart + 2));
                                $drawingSig->setHeight(50);
                                $drawingSig->setWorksheet($sheet);
                            } catch (\Throwable $e) {
                                \Illuminate\Support\Facades\Log::warning('Gagal menggambar TTD pada Excel: ' . $e->getMessage());
                            }
                        }
                    }

                    // Nama Pejabat
                    $sheet->setCellValue("{$sigCol}" . ($sigRowStart + 5), $this->docSettings['signatory']['nama']);
                    $sheet->getStyle("{$sigCol}" . ($sigRowStart + 5))->getFont()->setBold(true)->setSize(9.5)->setUnderline(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));

                    // NIP Pejabat
                    if (!empty($this->docSettings['signatory']['nip'])) {
                        $sheet->setCellValue("{$sigCol}" . ($sigRowStart + 6), 'NIP. ' . $this->docSettings['signatory']['nip']);
                        $sheet->getStyle("{$sigCol}" . ($sigRowStart + 6))->getFont()->setSize(8.5)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('475569'));
                    }
                }
            }
        ];
    }
}
