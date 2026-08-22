<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $selectedTitle ?? 'Laporan Aset Tanah' }}</title>
    <style>
        body {
            font-family: sans-serif;
            color: #0f172a;
            font-size: 10pt;
        }
        .header {
            border-bottom: 3px double #1e293b;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .logo-wrap {
            width: 90px;
        }
        .logo {
            width: 74px;
            height: 74px;
            object-fit: contain;
        }
        .header-main {
            text-align: center;
        }
        .instansi {
            font-size: 13pt;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .unit {
            font-size: 15pt;
            font-weight: bold;
            color: #0b4f84;
            margin-top: 3px;
        }
        .subunit,
        .meta-line {
            font-size: 9pt;
            color: #334155;
        }
        .report-title {
            margin: 16px 0 8px;
            text-align: center;
        }
        .report-title h2 {
            margin: 0;
            font-size: 15pt;
            letter-spacing: .8px;
        }
        .report-title .subtitle {
            margin-top: 4px;
            color: #475569;
            font-size: 9pt;
        }
        .meta-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
            margin: 8px 0 12px;
        }
        .meta-box {
            background: #f8fafc;
            border: 1px solid #dbe3ef;
            border-radius: 10px;
            padding: 10px 12px;
        }
        .meta-label {
            color: #64748b;
            font-size: 8.5pt;
            text-transform: uppercase;
        }
        .meta-value {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 2px;
        }
        .filter-box {
            border: 1px solid #dbe3ef;
            background: #ffffff;
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 12px;
        }
        .filter-box h4 {
            margin: 0 0 8px;
            font-size: 10.5pt;
            color: #0b4f84;
        }
        .filter-chip {
            display: inline-block;
            margin: 0 6px 6px 0;
            padding: 5px 8px;
            border-radius: 14px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 8.5pt;
        }
        .table-report {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.8pt;
        }
        .table-report th,
        .table-report td {
            border: 1px solid #cbd5e1;
            padding: 7px 8px;
        }
        .table-report thead th {
            background: #0b4f84;
            color: #fff;
            text-transform: uppercase;
            font-size: 8pt;
            letter-spacing: .4px;
            text-align: center;
        }
        .table-report tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .signature-wrap {
            margin-top: 20px;
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
            margin-top: 12px;
            font-size: 8.5pt;
            color: #64748b;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-wrap">
                    {{-- Logo dinonaktifkan sementara untuk menghindari isu cache mPDF --}}
                </td>
                <td class="header-main">
                    <div class="instansi">{{ $kop['kop_nama_instansi'] ?? '' }}</div>
                    <div class="unit">{{ $kop['kop_nama_unit'] ?? '' }}</div>
                    <div class="subunit">{{ $kop['kop_subunit'] ?? '' }}</div>
                    <div class="meta-line">{{ $kop['kop_alamat'] ?? '' }}</div>
                    <div class="meta-line">{{ $kop['kop_kontak'] ?? '' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="report-title">
        <h2>{{ $selectedTitle ?? 'LAPORAN ASET TANAH' }}</h2>
        <div class="subtitle">Dicetak pada {{ date('d-m-Y H:i') }}</div>
    </div>

    <table class="meta-grid">
        <tr>
            <td width="33.33%">
                <div class="meta-box">
                    <div class="meta-label">Total Data</div>
                    <div class="meta-value">{{ number_format((int) ($summary['total_data'] ?? 0), 0, ',', '.') }}</div>
                </div>
            </td>
            <td width="33.33%">
                <div class="meta-box">
                    <div class="meta-label">Total Nilai Perolehan</div>
                    <div class="meta-value">{{ $summary['total_nilai'] ?? '-' }}</div>
                </div>
            </td>
            <td width="33.33%">
                <div class="meta-box">
                    <div class="meta-label">Sudah Berstatus</div>
                    <div class="meta-value">{{ number_format((int) ($summary['total_berstatus'] ?? 0), 0, ',', '.') }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="filter-box">
        <h4>Filter Aktif</h4>
        @if (!empty($summary['activeFilters']))
            @foreach ($summary['activeFilters'] as $filter)
                <span class="filter-chip">{{ $filter['label'] }}: {{ $filter['value'] }}</span>
            @endforeach
        @else
            <span class="filter-chip">Semua data aset tanah</span>
        @endif
    </div>

    <table class="table-report">
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="10%">Kode</th>
                <th width="16%">Nama Aset</th>
                <th width="12%">Peruntukan</th>
                <th width="11%">OPD</th>
                <th width="8%">Luas (m2)</th>
                <th width="11%">Nilai Perolehan</th>
                <th width="9%">Tanggal</th>
                <th width="11%">Status</th>
                <th width="6%">Durasi</th>
                <th width="12%">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $row->kode_aset ?? '-' }}</td>
                    <td>{{ $row->nama_aset }}</td>
                    <td>{{ $row->peruntukan ?? '-' }}</td>
                    <td>{{ $row->opdSipat->nama ?? $row->opd ?? '-' }}</td>
                    <td class="text-right">{{ number_format($row->luas ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right">{{ $row->harga_perolehan ? number_format($row->harga_perolehan, 2, ',', '.') : '-' }}</td>
                    <td class="text-center">{{ $row->tanggal_perolehan ? date('d-m-Y', strtotime($row->tanggal_perolehan)) : '-' }}</td>
                    <td>{{ $row->latestProses->statusProses->nama_status ?? 'Belum Diurus' }}</td>
                    <td class="text-center">{{ $row->latestProses->durasi_hari ?? '-' }}</td>
                    <td>{{ $row->keterangan ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center">Tidak ada data untuk ditampilkan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature-wrap">
        <div class="signature-city">{{ $kop['kop_kota_ttd'] ?? '' }}, {{ date('d-m-Y H:i') }}</div>
        <div class="signature-job">{{ $kop['kop_pejabat_jabatan'] ?? '' }}</div>
        <div class="signature-space"></div>
        <div class="signature-name">{{ $kop['kop_pejabat_nama'] ?? '' }}</div>
        <div class="signature-nip">{{ $kop['kop_pejabat_nip'] ?? '' }}</div>
    </div>

    <div class="footer">
        {{ $kop['kop_footer'] ?? '' }}
    </div>
</body>
</html>
