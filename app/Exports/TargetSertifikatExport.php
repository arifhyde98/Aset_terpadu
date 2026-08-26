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
    protected string $statusCapaian;
    protected string $search;
    private int $rowNum = 0;

    public function __construct(int $tahun, ?int $opdId = null, string $statusCapaian = '', string $search = '')
    {
        $this->tahun = $tahun;
        $this->opdId = $opdId;
        $this->statusCapaian = $statusCapaian;
        $this->search = trim($search);
    }

    public function collection()
    {
        $query = SipatTargetSertifikat::with([
            'asetTanah.opdSipat',
            'asetTanah.latestProses.statusProses',
        ])->where('tahun', $this->tahun);

        if ($this->opdId) {
            $query->whereHas('asetTanah', function ($q) {
                $q->where('opd_id', $this->opdId);
            });
        }

        if (!empty($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('keterangan', 'LIKE', "%{$search}%")
                  ->orWhereHas('asetTanah', function ($q2) use ($search) {
                      $q2->where('kode_aset', 'LIKE', "%{$search}%")
                        ->orWhere('nama_aset', 'LIKE', "%{$search}%")
                        ->orWhere('peruntukan', 'LIKE', "%{$search}%")
                        ->orWhere('alamat', 'LIKE', "%{$search}%");
                  });
            });
        }

        $results = $query->get();

        if (!empty($this->statusCapaian)) {
            $results = $results->filter(function ($target) {
                $aset = $target->asetTanah;
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

                if ($this->statusCapaian === 'tercapai') {
                    return $isAchieved;
                } elseif ($this->statusCapaian === 'proses') {
                    return !$isAchieved;
                } elseif ($this->statusCapaian === 'belum_diurus') {
                    return $statusName === 'Belum Diurus';
                }

                return true;
            })->values();
        }

        return $results;
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
        $opdNama = $aset?->opdSipat?->nama ?? $aset?->opd ?? '-';
        
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
