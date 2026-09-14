<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $selectedTitle ?? 'Laporan Aset Tanah' }}</title>
    <style>
        body {
            font-family: sans-serif;
            color: #0f172a;
            font-size: 9.5pt;
        }
        .header {
            border-bottom: 3px double #1e293b;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-main {
            text-align: center;
        }
        .instansi {
            font-size: 12pt;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .unit {
            font-size: 14pt;
            font-weight: bold;
            color: #0b4f84;
            margin-top: 2px;
        }
        .subunit,
        .meta-line {
            font-size: 8.5pt;
            color: #334155;
        }
        .report-title {
            margin: 12px 0 16px;
            text-align: center;
        }
        .report-title .title-main {
            font-size: 13pt;
            font-weight: bold;
            letter-spacing: .5px;
            text-transform: uppercase;
            color: #000000;
            line-height: 1.3;
            margin-bottom: 3px;
        }
        .report-title .title-sub {
            font-size: 11pt;
            font-weight: bold;
            letter-spacing: .3px;
            text-transform: uppercase;
            color: #000000;
            line-height: 1.3;
            margin-bottom: 2px;
        }
        .table-report {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 15px;
        }
        .table-report th,
        .table-report td {
            border: 1px solid #000000;
            padding: 6px 8px;
        }
        .table-report thead th {
            background: #d9d9d9;
            color: #000000;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }
        .table-report tbody tr:nth-child(even) {
            background: #fdfdfd;
        }
        .table-report tfoot tr {
            background: #eaeaea;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin-top: 25px;
            page-break-inside: avoid;
        }
        .signature-table td {
            border: none;
            padding: 0;
            vertical-align: top;
        }
        .signature-col {
            text-align: center;
        }
        .signature-city {
            font-size: 9pt;
            color: #334155;
            margin-bottom: 4px;
        }
        .signature-job {
            font-size: 9.5pt;
            color: #0f172a;
            font-weight: bold;
            text-transform: uppercase;
        }
        .signature-space-row td {
            height: 75px;
            line-height: 75px;
            vertical-align: middle;
        }
        .signature-name {
            font-size: 10pt;
            font-weight: bold;
            text-decoration: underline;
        }
        .signature-nip {
            font-size: 8.8pt;
            color: #475569;
            margin-top: 2px;
        }
        .footer {
            margin-top: 15px;
            font-size: 8pt;
            color: #64748b;
            text-align: right;
        }
    </style>
</head>
<body>
    @php
        $year = date('Y');
        $kat = $filters['kategori_status'] ?? '';
        $isBersertifikat = ($kat === 'sudah_bersertifikat');
        $groupHeader = 'Aset Tanah';
        if ($isBersertifikat) {
            $groupHeader = 'Aset Sudah Bersertifikat';
        } elseif ($kat === 'belum_diproses') {
            $groupHeader = 'Aset Belum Diproses';
        } elseif ($kat === 'dalam_proses') {
            $groupHeader = 'Aset Dalam Proses';
        } elseif ($kat === 'bermasalah') {
            $groupHeader = 'Aset Bermasalah / Sengketa';
        } elseif ($kat === 'belum_bersertifikat') {
            $groupHeader = 'Aset Belum Bersertifikat';
        }

        $service = $service ?? app(\App\Services\Sipat\LaporanService::class);
        $titleLines = $titleLines ?? $service->resolveReportTitleLines($filters);
        $logoPath = $logoPath ?? $service->resolveLogoPath($kop);
    @endphp

    <!-- KOP SURAT RESMI -->
    <div class="header">
        <table class="header-table">
            <tr>
                @if(!empty($logoPath) && file_exists($logoPath))
                    <td style="width: 10%; text-align: center; vertical-align: middle;">
                        <img src="{{ $logoPath }}" style="max-height: 65px; max-width: 65px;" alt="Logo">
                    </td>
                    <td class="header-main" style="width: 90%; text-align: center;">
                @else
                    <td class="header-main" style="width: 100%; text-align: center;">
                @endif
                        <div class="instansi">{{ $kop['kop_nama_instansi'] ?? 'PEMERINTAH KABUPATEN DONGGALA' }}</div>
                        <div class="unit">{{ $kop['kop_nama_unit'] ?? 'BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH' }}</div>
                        <div class="subunit">{{ $kop['kop_subunit'] ?? 'Bidang Pengelolaan Aset Daerah' }}</div>
                        <div class="meta-line">{{ $kop['kop_alamat'] ?? '' }}</div>
                        <div class="meta-line">{{ $kop['kop_kontak'] ?? '' }}</div>
                    </td>
            </tr>
        </table>
    </div>

    <!-- JUDUL LAPORAN RESMI (TATA NASKAH DINAS PEMKAB DONGGALA) -->
    <div class="report-title">
        @foreach($titleLines as $idx => $line)
            <div class="{{ $idx === 0 ? 'title-main' : 'title-sub' }}">{{ $line }}</div>
        @endforeach
    </div>

    <!-- TABEL DENGAN 11/12 KOLOM RESMI (NO., KODE ASET/NIBAR, NAMA BARANG, LOKASI, [BIDANG, (NO. SERTIFIKAT), LUAS, NILAI], TANGGAL PEROLEHAN, CARA PEROLEHAN, STATUS, KETERANGAN) -->
    <table class="table-report">
        <thead>
            <tr>
                <th rowspan="2" width="3%" style="vertical-align: middle; text-align: center;">NO.</th>
                <th rowspan="2" width="{{ $isBersertifikat ? '12%' : '13%' }}" style="vertical-align: middle; text-align: center;">Kode Aset / NIBAR</th>
                <th rowspan="2" width="11%" style="vertical-align: middle; text-align: center;">Nama Barang</th>
                <th rowspan="2" width="{{ $isBersertifikat ? '11%' : '12%' }}" style="vertical-align: middle; text-align: center;">Lokasi</th>
                <th colspan="{{ $isBersertifikat ? '4' : '3' }}" style="text-align: center;">{{ $groupHeader }}</th>
                <th rowspan="2" width="{{ $isBersertifikat ? '7%' : '8%' }}" style="vertical-align: middle; text-align: center;">Tanggal Perolehan</th>
                <th rowspan="2" width="7%" style="vertical-align: middle; text-align: center;">Cara Perolehan</th>
                <th rowspan="2" width="{{ $isBersertifikat ? '7%' : '8%' }}" style="vertical-align: middle; text-align: center;">Status</th>
                <th rowspan="2" width="{{ $isBersertifikat ? '8%' : '10%' }}" style="vertical-align: middle; text-align: center;">Keterangan</th>
            </tr>
            <tr>
                <th width="{{ $isBersertifikat ? '10%' : '12%' }}">Bidang</th>
                @if($isBersertifikat)
                    <th width="10%" style="text-align: center;">No. Sertifikat</th>
                @endif
                <th width="{{ $isBersertifikat ? '6%' : '7%' }}" style="text-align: right;">Luas(m2)</th>
                <th width="{{ $isBersertifikat ? '8%' : '9%' }}" style="text-align: right;">Nilai(Rp)</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $totalLuas = 0; 
                $totalNilai = 0; 
            @endphp
            @forelse ($rows as $index => $row)
                @php
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
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td style="font-family: monospace; font-size: 8pt; word-break: break-all;">{{ $kodeAsetText }}</td>
                    <td>{{ $namaBarangText }}</td>
                    <td>{{ $lokasiText }}</td>
                    <td>{{ $bidangText }}</td>
                    @if($isBersertifikat)
                        <td class="text-center" style="font-family: monospace; font-size: 8pt;">{{ $noSertifikatText }}</td>
                    @endif
                    <td class="text-right">{{ number_format($luasVal, 2, ',', '.') }}</td>
                    <td class="text-right">{{ $nilaiVal > 0 ? number_format($nilaiVal, 2, ',', '.') : '0,00' }}</td>
                    <td class="text-center">{{ $tglPerolehanText }}</td>
                    <td class="text-center">{{ $caraPerolehanText }}</td>
                    <td class="text-center" style="font-size: 8.5pt;">{{ $statusProsesText }}</td>
                    <td>{{ $keteranganText }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $isBersertifikat ? '12' : '11' }}" class="text-center">Tidak ada data aset tanah untuk ditampilkan.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="{{ $isBersertifikat ? '6' : '5' }}" class="text-center">JUMLAH / TOTAL</td>
                <td class="text-right">{{ number_format($totalLuas, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totalNilai, 2, ',', '.') }}</td>
                <td colspan="4"></td>
            </tr>
        </tfoot>
    </table>

    @php
        $formatNip = function (?string $nip): string {
            $nip = trim((string)$nip);
            if ($nip === '' || $nip === '-') {
                return 'NIP. -';
            }
            if (str_starts_with(strtoupper($nip), 'NIP')) {
                return $nip;
            }
            return 'NIP. ' . $nip;
        };

        $pejabat1Jabatan = $kop['kop_pejabat1_jabatan'] ?? ($kop['kop_pejabat_jabatan'] ?? 'KEPALA BIDANG ASET DAERAH');
        $pejabat1Nama = $kop['kop_pejabat1_nama'] ?? ($kop['kop_pejabat_nama'] ?? 'YENI SJ AMIR, SH.MSi');
        $pejabat1Nip = $formatNip($kop['kop_pejabat1_nip'] ?? '');

        $pejabat2Jabatan = $kop['kop_pejabat2_jabatan'] ?? ($kop['kop_pejabat_jabatan'] ?? 'KEPALA BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH');
        $pejabat2Nama = $kop['kop_pejabat2_nama'] ?? ($kop['kop_pejabat_nama'] ?? 'YENI SJ AMIR, SH.MSi');
        $pejabat2Nip = $formatNip($kop['kop_pejabat2_nip'] ?? '');
    @endphp

    <!-- LEMBAR PENGESAHAN (TTD DUA SISI) -->
    <table class="signature-table">
        <tr>
            <td style="width: 44%;" class="signature-col">
                <div class="signature-city" style="color: transparent;">&nbsp;</div>
                <div class="signature-job">{{ $pejabat1Jabatan }}</div>
            </td>
            <td style="width: 12%;"></td>
            <td style="width: 44%;" class="signature-col">
                <div class="signature-city">{{ $kop['kop_kota_ttd'] ?? 'Banawa' }}, {{ date('d-m-Y') }}</div>
                <div class="signature-job">{{ $pejabat2Jabatan }}</div>
            </td>
        </tr>
        <tr class="signature-space-row">
            <td style="height: 75px;">&nbsp;</td>
            <td></td>
            <td style="height: 75px;">&nbsp;</td>
        </tr>
        <tr>
            <td class="signature-col">
                <div class="signature-name">{{ $pejabat1Nama }}</div>
                <div class="signature-nip">{{ $pejabat1Nip }}</div>
            </td>
            <td></td>
            <td class="signature-col">
                <div class="signature-name">{{ $pejabat2Nama }}</div>
                <div class="signature-nip">{{ $pejabat2Nip }}</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        {{ $kop['kop_footer'] ?? '' }}
    </div>
</body>
</html>
