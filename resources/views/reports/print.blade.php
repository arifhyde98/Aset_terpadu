@php
    $paperSize = $docSettings['settings']['paper_size'] ?? 'A4';
    $paperOrientation = ($docSettings['settings']['orientation'] ?? 'L') === 'P' ? 'portrait' : 'landscape';
    $paperCssSize = $paperSize === 'F4'
        ? ($paperOrientation === 'landscape' ? '330mm 210mm' : '210mm 330mm')
        : $paperSize . ' ' . $paperOrientation;
    $paperLabel = $paperSize . ' ' . ucfirst($paperOrientation);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan - E-RANDIS</title>

    <!-- Hubungkan Google Fonts Inter & Bootstrap Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Hubungkan Bootstrap 5 Core via Vite -->
    @vite(['resources/css/app.scss'])

    <style>
        body {
            background-color: #ffffff;
            color: #0f172a;
            font-family: 'Inter', sans-serif;
            font-size: 9.5pt;
            line-height: 1.5;
            padding: 20px;
        }

        /* Styling Kop Surat Premium ala SIPAT (Struktur Stabil via Table Layout) */
        .header {
            border-bottom: 3px double #1e3a8a; /* Navy E-RANDIS */
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo-wrap {
            width: 85px;
        }

        .logo {
            width: 74px;
            height: 74px;
            object-fit: contain;
        }

        .header-main {
            text-align: center;
            padding-right: 85px; /* Offset logo-wrap agar judul tetap seimbang di tengah */
        }

        .instansi {
            font-size: 12pt;
            font-weight: bold;
            letter-spacing: 1px;
            color: #1e293b;
        }

        .unit {
            font-size: 14pt;
            font-weight: bold;
            color: #1E40AF; /* Navy Blue E-RANDIS */
            margin-top: 3px;
        }

        .meta-line {
            font-size: 8.5pt;
            color: #475569;
            margin-top: 2px;
        }

        /* Judul Laporan */
        .report-title {
            margin: 16px 0 10px;
            text-align: center;
        }

        .report-title h2 {
            margin: 0;
            font-size: 14pt;
            font-weight: 700;
            letter-spacing: .5px;
            color: #0f172a;
            text-transform: uppercase;
        }

        .report-title .subtitle {
            margin-top: 4px;
            color: #64748b;
            font-size: 9pt;
        }

        /* Grid Ringkasan / Summary Metrik */
        .meta-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
            margin: 12px 0 16px;
        }

        .meta-box {
            background: #f8fafc;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            padding: 8px 12px;
        }

        .meta-label {
            color: #64748b;
            font-size: 8pt;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: .5px;
        }

        .meta-value {
            font-size: 11pt;
            font-weight: 700;
            margin-top: 2px;
            color: #0f172a;
        }

        /* Box Filter Aktif */
        .filter-box {
            border: 1px solid #e2e8f0;
            background: #ffffff;
            border-radius: 8px;
            padding: 8px 12px;
            margin-bottom: 16px;
        }

        .filter-box h4 {
            margin: 0 0 6px;
            font-size: 9pt;
            font-weight: 700;
            color: #1E40AF;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .filter-chip {
            display: inline-block;
            margin: 0 6px 4px 0;
            padding: 3px 8px;
            border-radius: 12px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 8pt;
            font-weight: 500;
        }

        /* Desain Tabel Premium Baru */
        .table-report {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-bottom: 25px;
        }

        .table-report th,
        .table-report td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            vertical-align: middle;
        }

        .table-report thead th {
            background: #1E40AF !important; /* Premium Navy */
            color: #ffffff !important;
            text-transform: uppercase;
            font-size: 8pt;
            font-weight: 700;
            letter-spacing: .4px;
            text-align: center;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .table-report tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        /* Highlight khusus baris duplikat identik */
        tr.dup-highlight td {
            background-color: rgba(239, 68, 68, 0.08) !important; /* Soft Red Highlight */
            border-color: #fca5a5 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Format Monospace Khusus Plat Nomor Resmi */
        .plate-number {
            font-family: 'Courier New', Courier, monospace;
            font-weight: 700;
            border: 1px solid #94a3b8;
            padding: 2px 6px;
            background-color: #ffffff;
            border-radius: 4px;
            font-size: 8.5pt;
            color: #0f172a;
            display: inline-block;
            text-align: center;
            min-width: 80px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .fw-semibold {
            font-weight: 600;
        }

        /* Box Tanda Tangan */
        .signature-wrap {
            margin-top: 30px;
            width: 280px;
            margin-left: auto;
            text-align: center;
            page-break-inside: avoid;
        }

        .signature-city {
            font-size: 9pt;
            color: #475569;
            margin-bottom: 4px;
        }

        .signature-job {
            font-size: 9pt;
            color: #0f172a;
            font-weight: 700;
        }

        .signature-space {
            height: 60px;
        }

        .signature-name {
            font-size: 9.5pt;
            font-weight: 700;
            text-decoration: underline;
            color: #0f172a;
        }

        .signature-nip {
            font-size: 8.5pt;
            color: #475569;
            margin-top: 2px;
        }

        /* Kontrol Halaman Printer */
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            .no-print {
                display: none !important;
            }
            @page {
                size: {{ $paperCssSize }};
                margin: 1.2cm;
            }
            .table-report thead th {
                background-color: #1E40AF !important;
                color: #ffffff !important;
            }
            .table-report tbody tr:nth-child(even) {
                background-color: #f8fafc !important;
            }
        }
    </style>
</head>
<body>

    <!-- TOMBOL PREVIEW KONTROL MANUAL (HANYA MUNCUL DI LAYAR BROWSER) -->
    <div class="no-print d-flex justify-content-between align-items-center bg-light p-3 border rounded mb-4 shadow-sm">
        <div class="d-flex align-items-center">
            <i class="bi bi-printer-fill text-primary fs-5 me-2"></i>
            <div>
                <span class="fw-bold text-navy">Pratinjau Cetak Laporan</span>
                <span class="text-muted ms-2" style="font-size: 8pt;">Dokumen siap dicetak ke {{ $paperLabel }} / Simpan PDF</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.close()" class="btn btn-sm btn-outline-secondary px-3 fw-medium">Tutup Tab</button>
            <button onclick="window.print()" class="btn btn-sm btn-primary px-4 fw-semibold">Cetak / Simpan PDF</button>
        </div>
    </div>

    <!-- KOP SURAT PEMERINTAH (SULAWESI TENGAH) -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-wrap">
                    @php
                        $logoFile = $docSettings['letterhead']['logo_path'] ?? 'images/logo-sulteng.png';
                        $logoSrc = asset($logoFile);
                    @endphp
                    <img class="logo" src="{{ $logoSrc }}" alt="Logo Instansi">
                </td>
                <td class="header-main">
                    <div class="instansi">{{ $docSettings['letterhead']['nama_pemerintah'] }}</div>
                    <div class="unit">{{ $docSettings['letterhead']['nama_instansi'] }}</div>
                    @if(!empty($docSettings['letterhead']['nama_unit']))
                        <div class="subunit">{{ $docSettings['letterhead']['nama_unit'] }}</div>
                    @endif
                    <div class="meta-line">
                        {{ $docSettings['letterhead']['alamat'] }}
                        @if(!empty($docSettings['letterhead']['telepon'])) - Telepon: {{ $docSettings['letterhead']['telepon'] }} @endif
                    </div>
                    <div class="meta-line">
                        @if(!empty($docSettings['letterhead']['email'])) Email: {{ $docSettings['letterhead']['email'] }} @endif
                        @if(!empty($docSettings['letterhead']['website'])) | Website: {{ $docSettings['letterhead']['website'] }} @endif
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
                        <div class="meta-label">Total Kendaraan</div>
                        <div class="meta-value">{{ number_format($totalData) }} Unit</div>
                    </div>
                </td>
                <td width="33.33%">
                    <div class="meta-box">
                        <div class="meta-label">Total Nilai Aset</div>
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

    <!-- CHIP FILTER AKTIF ala SIPAT -->
    <div class="filter-box">
        <h4>Kriteria Penyaringan (Active Filters)</h4>
        <span class="filter-chip"><i class="bi bi-database me-1"></i>Sumber Data: {{ ($filters['source'] ?? 'real') === 'ebmd' ? 'Data e-BMD' : 'Data Real (Operasional)' }}</span>
        <span class="filter-chip"><i class="bi bi-funnel-fill me-1"></i>Kondisi: {{ $filters['kondisi'] ?? 'Semua Kondisi' }}</span>
        <span class="filter-chip"><i class="bi bi-calendar-event me-1"></i>Tahun Pengadaan: {{ $filters['tahun'] ?? 'Semua Tahun' }}</span>
        <span class="filter-chip"><i class="bi bi-tag-fill me-1"></i>Tipe Laporan: {{ $reportTitle }}</span>

        @if(!empty($filters['opd_id']))
            @php
                $selectedOpd = \App\Models\Opd::find($filters['opd_id']);
            @endphp
            @if($selectedOpd)
                <span class="filter-chip"><i class="bi bi-building me-1"></i>Instansi: {{ $selectedOpd->nama }}</span>
            @endif
        @endif

        <span class="filter-chip bg-light text-dark border"><i class="bi bi-clock me-1"></i>Dicetak: {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }} WITA</span>
    </div>

    <!-- TABEL DATA UTAMA -->
    <table class="table-report">
        <thead>
            <tr>
                <th style="width: 45px;">No</th>
                @foreach($headers as $key => $label)
                    <th>{{ $label }}</th>
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
                            $rowClass = 'class="dup-highlight"';
                        }
                    }
                @endphp
                <tr {!! $rowClass !!}>
                    <td class="text-center fw-medium">{{ $index + 1 }}</td>
                    @foreach($headers as $key => $label)
                        <td class="{{ $key === 'nilai_perolehan' ? 'text-right' : '' }}">
                            @if($key === 'no_polisi')
                                <span class="plate-number">{{ strtoupper(trim($row->{$key})) }}</span>
                            @elseif($key === 'nilai_perolehan')
                                Rp{{ number_format($row->{$key}, 0, ',', '.') }}
                            @elseif($key === 'tgl_stnk' || $key === 'tgl_perolehan')
                                {{ $row->{$key} ? \Carbon\Carbon::parse($row->{$key})->translatedFormat('d F Y') : '-' }}
                            @else
                                {{ $row->{$key} ?? '-' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="100" class="text-center py-4 text-muted">Belum ada data kendaraan dinas yang sesuai dengan kriteria filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- TANDA TANGAN KEPALA DINAS (TANDA TANGAN RESMI) -->
    @if($docSettings['settings']['show_signature'] ?? true)
        <div class="signature-wrap">
            <div class="signature-city">{{ $docSettings['signatory']['kota_ttd'] ?? 'Palu' }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
            <div class="signature-job">{{ $docSettings['signatory']['jabatan'] }},</div>

            @if(!empty($docSettings['signatory']['signature_image_path']))
                @php
                    $sigImgSrc = asset($docSettings['signatory']['signature_image_path']);
                @endphp
                <div style="margin: 6px 0; text-align: center;">
                    <img src="{{ $sigImgSrc }}" style="max-height: 52px; max-width: 140px; object-fit: contain;">
                </div>
            @else
                <div class="signature-space"></div>
            @endif

            <div class="signature-name">{{ $docSettings['signatory']['nama'] }}</div>
            @if(!empty($docSettings['signatory']['nip']))
                <div class="signature-nip">NIP. {{ $docSettings['signatory']['nip'] }}</div>
            @endif
        </div>
    @endif

    <!-- SCRIPT WINDOW.PRINT DIKENDALIKAN BROWSER SECARA OTOMATIS -->
    <script>
        window.addEventListener('DOMContentLoaded', (event) => {
            // Berikan jeda 800ms agar browser merender dokumen/tabel secara sempurna sebelum dialog print terbuka
            setTimeout(() => {
                window.print();
            }, 800);
        });
    </script>
</body>
</html>
