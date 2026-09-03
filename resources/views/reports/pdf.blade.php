<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle ?? 'Laporan Kendaraan Dinas' }}</title>
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
            color: #1E40AF;
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
            text-transform: uppercase;
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
            color: #1E40AF;
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
            background: #1E40AF;
            color: #fff;
            text-transform: uppercase;
            font-size: 8pt;
            letter-spacing: .4px;
            text-align: center;
        }
        .table-report tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        tr.dup-highlight td {
            background-color: #fef2f2 !important;
            border-color: #fca5a5 !important;
        }
        .plate-number {
            font-family: monospace;
            font-weight: bold;
            font-size: 9pt;
            color: #0f172a;
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

    <!-- KOP SURAT BAPENDA SULAWESI TENGAH (PRESISI GAYA SIPAT) -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-wrap">
                    @php
                        $logoFile = $docSettings['letterhead']['logo_path'] ?? 'images/logo-sulteng.png';
                        $logoPath = public_path($logoFile);
                        $logoSrc = (file_exists($logoPath) && is_file($logoPath) && filesize($logoPath) > 0) ? $logoPath : '';
                    @endphp
                    @if($logoSrc)
                        <img class="logo" src="{{ $logoSrc }}" alt="Logo">
                    @endif
                </td>
                <td class="header-main" style="padding-right: 80px;">
                    <div class="instansi">{{ $docSettings['letterhead']['nama_pemerintah'] }}</div>
                    <div class="unit">{{ $docSettings['letterhead']['nama_instansi'] }}</div>
                    @if(!empty($docSettings['letterhead']['nama_unit']))
                        <div class="subunit">{{ $docSettings['letterhead']['nama_unit'] }}</div>
                    @endif
                    <div class="meta-line">{{ $docSettings['letterhead']['alamat'] }}</div>
                    <div class="meta-line">
                        @if(!empty($docSettings['letterhead']['telepon'])) Telp. {{ $docSettings['letterhead']['telepon'] }} @endif
                        @if(!empty($docSettings['letterhead']['email'])) | Email: {{ $docSettings['letterhead']['email'] }} @endif
                        @if(!empty($docSettings['letterhead']['website'])) | Web: {{ $docSettings['letterhead']['website'] }} @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- JUDUL DOKUMEN LAPORAN -->
    <div class="report-title">
        <h2>{{ $reportTitle }}</h2>
        <div class="subtitle">Sistem Informasi Manajemen Kendaraan Dinas (E-RANDIS){{ ($filters['source'] ?? 'real') === 'ebmd' ? ' - Database Aset e-BMD' : '' }}</div>
    </div>

    <!-- KALKULASI RINGKASAN METRIK SECARA DINAMIS -->
    @php
        $totalData = $data->count();
        $totalNilai = $data->sum('nilai_perolehan');
        
        $type = $filters['type'] ?? 'status';
        if ($type === 'duplicate') {
            $statLabel = 'Terindikasi Ganda';
            $statValue = number_format($data->filter(fn($row) => !str_starts_with($row->duplicate_group_key ?? '', 'none_'))->count()) . ' Kendaraan';
        } else {
            $statLabel = 'Kondisi Baik';
            $statValue = number_format($data->filter(fn($r) => in_array(strtoupper(trim((string)$r->kondisi)), ['BAIK', 'B']))->count()) . ' Kendaraan';
        }
    @endphp

    <!-- METRIK METADATA / SUMMARY BOX ala SIPAT -->
    @if($docSettings['settings']['show_summary'] ?? true)
        <table class="meta-grid">
            <tr>
                <td width="33.33%">
                    <div class="meta-box">
                        <div class="meta-label">Total Data</div>
                        <div class="meta-value">{{ number_format($totalData) }} Unit</div>
                    </div>
                </td>
                <td width="33.33%">
                    <div class="meta-box">
                        <div class="meta-label">Total Nilai Perolehan</div>
                        <div class="meta-value">
                            @if($totalNilai > 0)
                                Rp{{ number_format($totalNilai, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </div>
                    </div>
                </td>
                <td width="33.33%">
                    <div class="meta-box">
                        <div class="meta-label">{{ $statLabel }}</div>
                        <div class="meta-value">{{ $statValue }}</div>
                    </div>
                </td>
            </tr>
        </table>
    @endif

    <!-- FILTER AKTIF ala SIPAT -->
    <div class="filter-box">
        <h4>Filter Aktif</h4>
        <span class="filter-chip">Sumber Data: {{ ($filters['source'] ?? 'real') === 'ebmd' ? 'Data e-BMD' : 'Data Real (Operasional)' }}</span>
        <span class="filter-chip">Kondisi: {{ $filters['kondisi'] ?? 'Semua Kondisi' }}</span>
        <span class="filter-chip">Tahun: {{ $filters['tahun'] ?? 'Semua Tahun' }}</span>
        <span class="filter-chip">Tipe Laporan: {{ $reportTitle }}</span>
        @if(!empty($filters['opd_id']))
            @php
                $selectedOpd = \App\Models\Opd::find($filters['opd_id']);
            @endphp
            @if($selectedOpd)
                <span class="filter-chip">OPD: {{ $selectedOpd->nama }}</span>
            @endif
        @endif
        <span class="filter-chip" style="background: #f1f5f9; color: #475569;">Dicetak: {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }} WITA</span>
    </div>

    <!-- TABEL DATA UTAMA -->
    <table class="table-report">
        <thead>
            <tr>
                <th width="4%">No</th>
                @foreach($headers as $key => $label)
                    <th {!! $key === 'no_polisi' ? 'nowrap="nowrap" style="white-space: nowrap; width: 100px;"' : '' !!}>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                $groupColors = [];
                $colorIndex = 0;
            @endphp
            @forelse($data as $index => $row)
                @php
                    $rowClass = '';
                    $type = $filters['type'] ?? 'status';
                    if ($type === 'duplicate') {
                        $groupKey = $row->duplicate_group_key;
                        if (!isset($groupColors[$groupKey])) {
                            $groupColors[$groupKey] = ($colorIndex++ % 2 === 0);
                        }
                        
                        if ($groupColors[$groupKey] && !str_starts_with($groupKey, 'none_')) {
                            $rowClass = 'dup-highlight';
                        }
                    }
                @endphp
                <tr class="{{ $rowClass }}">
                    <td class="text-center">{{ $index + 1 }}</td>
                    @foreach($headers as $key => $label)
                        @php
                            $isNoWrap = in_array($key, ['no_polisi', 'nilai_perolehan', 'tgl_stnk', 'tgl_perolehan']);
                            $tdAttributes = $isNoWrap ? 'nowrap="nowrap" style="white-space: nowrap;"' : '';
                        @endphp
                        <td class="{{ $key === 'nilai_perolehan' ? 'text-right' : '' }} {{ $key === 'no_polisi' || $key === 'tgl_stnk' || $key === 'tgl_perolehan' ? 'text-center' : '' }}" {!! $tdAttributes !!}>
                            @if($key === 'no_polisi')
                                <span class="plate-number">{{ strtoupper(trim($row->{$key})) }}</span>
                            @elseif($key === 'nilai_perolehan')
                                Rp{{ number_format($row->{$key}, 0, ',', '.') }}
                            @elseif($key === 'tgl_stnk' || $key === 'tgl_perolehan')
                                {{ $row->{$key} ? \Carbon\Carbon::parse($row->{$key})->translatedFormat('d-m-Y') : '-' }}
                            @else
                                {{ $row->{$key} ?? '-' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="100" class="text-center" style="padding: 20px; color: #64748b;">Belum ada data kendaraan dinas yang sesuai dengan kriteria filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- TANDA TANGAN KEPALA DINAS (PRESISI GAYA SIPAT) -->
    @if($docSettings['settings']['show_signature'] ?? true)
        <div class="signature-wrap">
            <div class="signature-city">{{ $docSettings['signatory']['kota_ttd'] ?? 'Palu' }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
            <div class="signature-job">{{ $docSettings['signatory']['jabatan'] }},</div>
            
            @if(!empty($docSettings['signatory']['signature_image_path']))
                @php
                    $sigImgFile = $docSettings['signatory']['signature_image_path'];
                    $sigImgPath = public_path($sigImgFile);
                    $sigSrc = (file_exists($sigImgPath) && is_file($sigImgPath) && filesize($sigImgPath) > 0) ? $sigImgPath : '';
                @endphp
                @if($sigSrc)
                    <div style="margin: 6px 0; text-align: center;">
                        <img src="{{ $sigSrc }}" style="max-height: 52px; max-width: 140px; object-fit: contain;">
                    </div>
                @else
                    <div class="signature-space"></div>
                @endif
            @else
                <div class="signature-space"></div>
            @endif

            <div class="signature-name">{{ $docSettings['signatory']['nama'] }}</div>
            @if(!empty($docSettings['signatory']['nip']))
                <div class="signature-nip">NIP. {{ $docSettings['signatory']['nip'] }}</div>
            @endif
        </div>
    @endif

    <!-- FOOTER LAPORAN -->
    <div class="footer">
        Dokumen ini dihasilkan otomatis secara resmi oleh Sistem E-RANDIS.
    </div>

</body>
</html>
