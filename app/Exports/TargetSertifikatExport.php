<?php

namespace App\Exports;

use App\Models\SipatTargetSertifikat;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TargetSertifikatExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected int $tahun;
    protected ?int $opdId;
    private int $rowNum = 0;

    public function __construct(int $tahun, ?int $opdId = null)
    {
        $this->tahun = $tahun;
        $this->opdId = $opdId;
    }

    public function collection()
    {
        $query = SipatTargetSertifikat::with([
            'asetTanah.opdSipat',
            'asetTanah.latestProses.statusProses',
            'opdSipat'
        ])->where('tahun', $this->tahun);

        if ($this->opdId) {
            $query->where(function ($q) {
                $q->where('opd_id', $this->opdId)
                  ->orWhereHas('asetTanah', function ($q2) {
                      $q2->where('opd_id', $this->opdId);
                  });
            });
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Tahun Target',
            'NIBAR / Kode Aset',
            'Nama Aset Tanah',
            'OPD Pengelola',
            'Luas (m²)',
            'Status BPN Terakhir',
            'Capaian Target',
            'Catatan Target',
        ];
    }

    public function map($target): array
    {
        $this->rowNum++;
        $aset = $target->asetTanah;
        $opdNama = $target->opdSipat?->nama ?? $aset?->opdSipat?->nama ?? $aset?->opd ?? '-';
        
        $latestStatus = $aset?->latestProses?->statusProses;
        $statusName = $latestStatus?->nama_status ?? 'Belum Diurus';
        $category = strtolower(trim($latestStatus?->kategori ?? ''));

        if (empty($category)) {
            $norm = strtolower($statusName);
            if (str_contains($norm, 'terbit') || str_contains($norm, 'selesai') || ($statusName !== 'Belum Diurus' && str_contains($norm, 'sertifikat') && !str_contains($norm, 'proses'))) {
                $category = 'bersertifikat';
            }
        }

        $isAchieved = ($category === 'bersertifikat');
        $capaianText = $isAchieved ? 'TERCAPAI (Sertifikat Terbit)' : 'DALAM PROSES / BELUM';

        return [
            $this->rowNum,
            $target->tahun,
            $aset->kode_aset ?? '-',
            $aset->nama_aset ?? '-',
            $opdNama,
            $aset->luas ?? 0,
            $statusName,
            $capaianText,
            $target->keterangan ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E3A8A']
                ]
            ],
        ];
    }
}
