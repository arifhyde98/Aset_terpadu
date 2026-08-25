<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $selectedTitle }}</title>
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
        .subunit, .meta-line {
            font-size: 8.5pt;
            color: #334155;
        }
        .report-title {
            margin: 14px 0 10px;
            text-align: center;
        }
        .report-title h2 {
            margin: 0;
            font-size: 13pt;
            letter-spacing: .5px;
        }
        .report-title .subtitle {
            margin-top: 4px;
            color: #475569;
            font-size: 8.5pt;
        }
        .summary-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .summary-box {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            text-align: center;
        }
        .summary-label {
            color: #64748b;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .summary-value {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 2px;
        }
        .table-report {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
        }
        .table-report th,
        .table-report td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
        }
        .table-report thead th {
            background: #0b4f84;
            color: #fff;
            text-transform: uppercase;
            font-size: 7.8pt;
            letter-spacing: .3px;
            text-align: center;
        }
        .table-report tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .badge-achieved {
            color: #15803d;
            font-weight: bold;
        }
        .badge-process {
            color: #b45309;
            font-weight: bold;
        }
        .signature-wrap {
            margin-top: 24px;
            width: 320px;
            margin-left: auto;
            text-align: center;
            page-break-inside: avoid;
        }
        .signature-city { font-size: 8.5pt; color: #334155; margin-bottom: 4px; }
        .signature-job { font-size: 9pt; color: #0f172a; font-weight: bold; }
        .signature-space { height: 48px; }
        .signature-name { font-size: 9.5pt; font-weight: bold; text-decoration: underline; }
        .signature-nip { font-size: 8.5pt; color: #475569; margin-top: 2px; }
    </style>
</head>
<body>
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

    <div class="report-title">
        <h2>{{ $selectedTitle }}</h2>
        <div class="subtitle">Dicetak secara resmi pada {{ date('d-m-Y H:i') }}</div>
    </div>

    <table class="summary-grid">
        <tr>
            <td width="25%" class="summary-box">
                <div class="summary-label">Total Target Bidang</div>
                <div class="summary-value">{{ number_format($totalTarget) }}</div>
            </td>
            <td width="25%" class="summary-box">
                <div class="summary-label" style="color: #15803d;">Realisasi (Terbit)</div>
                <div class="summary-value" style="color: #15803d;">{{ number_format($totalRealisasi) }}</div>
            </td>
            <td width="25%" class="summary-box">
                <div class="summary-label" style="color: #b45309;">Dalam Pengurusan</div>
                <div class="summary-value" style="color: #b45309;">{{ number_format($totalProses) }}</div>
            </td>
            <td width="25%" class="summary-box">
                <div class="summary-label">Persentase Capaian</div>
                <div class="summary-value">{{ $persentaseCapaian }}%</div>
            </td>
        </tr>
    </table>

    <table class="table-report">
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="6%">Tahun</th>
                <th width="12%">Kode Aset (NIBAR)</th>
                <th width="20%">Nama Aset Tanah / Peruntukan</th>
                <th width="18%">OPD Pengelola</th>
                <th width="14%">Status BPN Terakhir</th>
                <th width="12%">Capaian Target</th>
                <th width="14%">Catatan Target</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($targetItems as $index => $t)
                @php
                    $aset = $t->asetTanah;
                    $opdNama = $aset?->opdSipat?->nama ?? $aset?->opd ?? '-';
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $t->tahun }}</td>
                    <td><strong>{{ $aset->kode_aset ?? '-' }}</strong></td>
                    <td>
                        <div><strong>{{ $aset->nama_aset ?? '-' }}</strong></div>
                        <div style="font-size: 7.5pt; color: #64748b;">{{ $aset->peruntukan ?? '-' }}</div>
                    </td>
                    <td>{{ $opdNama }}</td>
                    <td>{{ $t->computed_status_name }}</td>
                    <td class="text-center">
                        @if($t->is_achieved)
                            <span class="badge-achieved">TERCAPAI</span>
                        @else
                            <span class="badge-process">Dalam Proses</span>
                        @endif
                    </td>
                    <td>{{ $t->keterangan ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Tidak ada target pensertifikatan untuk ditampilkan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature-wrap">
        <div class="signature-city">{{ $kop['kop_kota_ttd'] ?? 'Banawa' }}, {{ date('d F Y') }}</div>
        <div class="signature-job">{{ $kop['kop_pejabat_jabatan'] ?? 'Kepala Bidang Pengelolaan Aset Daerah' }}</div>
        <div class="signature-space"></div>
        <div class="signature-name">{{ $kop['kop_pejabat_nama'] ?? 'H. MUHAMMAD NATSIR, S.E., M.Si.' }}</div>
        <div class="signature-nip">{{ $kop['kop_pejabat_nip'] ?? '' }}</div>
    </div>
</body>
</html>
