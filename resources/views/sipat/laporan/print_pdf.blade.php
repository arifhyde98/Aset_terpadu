<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $selectedTitle }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            color: #0f172a;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 3px double #000;
            margin-bottom: 12px;
            padding-bottom: 8px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .kop-title-instansi {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .kop-title-unit {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            color: #1e3a8a;
            text-transform: uppercase;
        }
        .kop-subunit {
            font-size: 10pt;
            text-align: center;
            color: #475569;
        }
        .kop-alamat {
            font-size: 8pt;
            text-align: center;
            color: #64748b;
        }
        .report-title-box {
            text-align: center;
            margin: 15px 0 10px 0;
        }
        .report-title-main {
            font-size: 13pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .report-sub {
            font-size: 9pt;
            color: #475569;
            margin-top: 2px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 8pt;
        }
        .data-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 6px 4px;
            border: 1px solid #334155;
            text-transform: uppercase;
        }
        .data-table td {
            padding: 5px 4px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: monospace; }
        .signature-table {
            width: 100%;
            margin-top: 25px;
            page-break-inside: avoid;
        }
        .signature-table td {
            vertical-align: top;
            font-size: 9pt;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="position: fixed; top: 10px; right: 10px; z-index: 999;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 20px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
            🖨️ Cetak Dokumen / PDF
        </button>
    </div>

    <!-- KOP SURAT PEMDA -->
    <table class="header-table">
        <tr>
            <td style="width: 10%; text-align: center;">
                <img src="https://upload.wikimedia.org/wikipedia/commons/ thumb/8/87/Lambang_Kabupaten_Donggala.png/120px-Lambang_Kabupaten_Donggala.png" style="max-height: 70px;" alt="Logo" onerror="this.style.display='none'">
            </td>
            <td style="width: 90%;">
                <div class="kop-title-instansi">{{ $kop['kop_nama_instansi'] }}</div>
                <div class="kop-title-unit">{{ $kop['kop_nama_unit'] }}</div>
                <div class="kop-subunit">{{ $kop['kop_subunit'] }}</div>
                <div class="kop-alamat">{{ $kop['kop_alamat'] }} | {{ $kop['kop_kontak'] }}</div>
            </td>
        </tr>
    </table>

    <!-- JUDUL LAPORAN -->
    <div class="report-title-box">
        <div class="report-title-main">{{ $selectedTitle }}</div>
        <div class="report-sub">Dicetak pada: {{ date('d-m-Y H:i') }} WIB | Total Data: {{ count($rows) }} Bidang Tanah</div>
    </div>

    <!-- TABEL DATA LAPORAN -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;">NO</th>
                <th style="width: 160px;">KODE ASET (NIBAR)</th>
                <th>NAMA ASET TANAH</th>
                <th>PERUNTUKAN / PENGGUNAAN</th>
                <th>OPD PENGELOLA</th>
                <th style="width: 70px;">LUAS (M²)</th>
                <th style="width: 110px;">HARGA PEROLEHAN</th>
                <th style="width: 80px;">TGL PEROLEHAN</th>
                <th style="width: 110px;">STATUS BPN</th>
                <th>ALAMAT / LOKASI</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-mono text-center">{{ $item->kode_aset ?? '-' }}</td>
                    <td style="font-weight: bold;">{{ $item->nama_aset }}</td>
                    <td>{{ $item->peruntukan ?? '-' }}</td>
                    <td>{{ $item->opd ?? 'BPKAD' }}</td>
                    <td class="text-right font-mono">{{ number_format($item->luas ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right font-mono">{{ $item->harga_perolehan ? 'Rp ' . number_format($item->harga_perolehan, 2, ',', '.') : '-' }}</td>
                    <td class="text-center">{{ $item->tanggal_perolehan ?? '-' }}</td>
                    <td class="text-center">
                        <strong>{{ $item->latestProses->statusProses->nama_status ?? 'Belum Diurus' }}</strong>
                    </td>
                    <td>{{ $item->alamat ?? 'Kabupaten Donggala' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 20px;">Tidak ada data aset tanah yang memenuhi kriteria filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- TANDA TANGAN PEJABAT -->
    <table class="signature-table">
        <tr>
            <td style="width: 60%;"></td>
            <td style="width: 40%; text-align: center;">
                <div>{{ $kop['kop_kota_ttd'] }}, {{ \Carbon\Carbon::now()->isoFormat('D MMMM Y') }}</div>
                <div style="font-weight: bold; margin-bottom: 60px;">{{ $kop['kop_pejabat_jabatan'] }}</div>
                <div style="font-weight: bold; text-decoration: underline;">{{ $kop['kop_pejabat_nama'] }}</div>
                <div>{{ $kop['kop_pejabat_nip'] }}</div>
            </td>
        </tr>
    </table>

</body>
</html>
