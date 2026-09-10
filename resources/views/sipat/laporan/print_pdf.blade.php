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

        $titleLines = $titleLines ?? null;
        if (!$titleLines) {
            $service = app(\App\Services\LaporanService::class);
            $titleLines = $service->resolveReportTitleLines($filters);
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
