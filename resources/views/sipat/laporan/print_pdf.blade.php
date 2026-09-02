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
            margin: 14px 0 16px;
            text-align: center;
        }
        .report-title h2 {
            margin: 0;
            font-size: 13pt;
            font-weight: bold;
            letter-spacing: .5px;
            text-transform: uppercase;
        }
        .report-title .subtitle {
            margin-top: 4px;
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
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
        .signature-wrap {
            margin-top: 25px;
            width: 320px;
            margin-left: auto;
            text-align: center;
            page-break-inside: avoid;
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
        }
        .signature-space {
            height: 52px;
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

        $fullTitle = strtoupper($selectedTitle ?? 'LAPORAN REKAPITULASI ASET TANAH');
        if (!str_contains($fullTitle, $year)) {
            $fullTitle .= ' ' . $year;
        }
    @endphp

    <!-- KOP SURAT RESMI -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-main">
                    <div class="instansi">{{ $kop['kop_nama_instansi'] ?? 'PEMERINTAH KABUPATEN DONGGALA' }}</div>
                    <div class="unit">{{ $kop['kop_nama_unit'] ?? 'BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH' }}</div>
                    <div class="subunit">{{ $kop['kop_subunit'] ?? 'Bidang Pengelolaan Aset Daerah' }}</div>
                    <div class="meta-line">{{ $kop['kop_alamat'] ?? '' }}</div>
                    <div class="meta-line">{{ $kop['kop_kontak'] ?? '' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- JUDUL LAPORAN KATEGORI -->
    <div class="report-title">
        <h2>{{ $fullTitle }}</h2>
        <div class="subtitle">{{ $kop['kop_nama_instansi'] ?? 'PEMERINTAH KABUPATEN DONGGALA' }}</div>
    </div>

    <!-- TABEL DENGAN KODE ASET / NIBAR (NO., KODE ASET/NIBAR, BIDANG, LUAS, NILAI, KETERANGAN) -->
    <table class="table-report">
        <thead>
            <tr>
                <th rowspan="2" width="5%" style="vertical-align: middle; text-align: center;">NO.</th>
                <th rowspan="2" width="18%" style="vertical-align: middle; text-align: center;">Kode Aset / NIBAR</th>
                <th colspan="3" style="text-align: center;">{{ $groupHeader }}</th>
                <th rowspan="2" width="22%" style="vertical-align: middle; text-align: center;">Keterangan</th>
            </tr>
            <tr>
                <th width="27%">Bidang</th>
                <th width="13%" style="text-align: right;">Luas(m2)</th>
                <th width="15%" style="text-align: right;">Nilai(Rp)</th>
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
                    $bidangText = $row->peruntukan ?? $row->nama_aset ?? '-';
                    $keteranganText = $row->keterangan ?? $row->opdSipat->nama ?? $row->opd ?? '-';
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td style="font-family: monospace; font-size: 8.5pt;">{{ $kodeAsetText }}</td>
                    <td>{{ $bidangText }}</td>
                    <td class="text-right">{{ number_format($luasVal, 2, ',', '.') }}</td>
                    <td class="text-right">{{ $nilaiVal > 0 ? number_format($nilaiVal, 2, ',', '.') : '-' }}</td>
                    <td>{{ $keteranganText }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Tidak ada data aset tanah untuk ditampilkan.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-center">JUMLAH / TOTAL</td>
                <td class="text-right">{{ number_format($totalLuas, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totalNilai, 2, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <!-- LEMBAR PENGESAHAN (TTD) -->
    <div class="signature-wrap">
        <div class="signature-city">{{ $kop['kop_kota_ttd'] ?? 'Banawa' }}, {{ date('d-m-Y') }}</div>
        <div class="signature-job">{{ $kop['kop_pejabat_jabatan'] ?? 'Kepala Bidang Pengelolaan Aset Daerah' }}</div>
        <div class="signature-space"></div>
        <div class="signature-name">{{ $kop['kop_pejabat_nama'] ?? 'H. MUHAMMAD NATSIR, S.E., M.Si.' }}</div>
        <div class="signature-nip">NIP. {{ $kop['kop_pejabat_nip'] ?? '19780512 200501 1 008' }}</div>
    </div>

    <div class="footer">
        {{ $kop['kop_footer'] ?? '' }}
    </div>
</body>
</html>
