<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Rekapitulasi Pensertifikatan Aset Tanah per OPD' }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 8.5pt;
            color: #111827;
            margin: 0;
            padding: 0;
            line-height: 1.3;
        }
        /* Kop Surat */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2.5px solid #000;
            margin-bottom: 8px;
            padding-bottom: 6px;
        }
        .kop-table td {
            vertical-align: middle;
        }
        .kop-instansi {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            margin: 0;
        }
        .kop-unit {
            font-size: 13pt;
            font-weight: 800;
            text-transform: uppercase;
            text-align: center;
            margin: 2px 0;
        }
        .kop-sub {
            font-size: 8pt;
            text-align: center;
            color: #374151;
            margin: 0;
        }

        /* Judul Laporan */
        .report-header {
            text-align: center;
            margin: 10px 0 12px 0;
        }
        .report-title {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 3px 0;
        }
        .report-subtitle {
            font-size: 8.5pt;
            color: #4b5563;
        }

        /* Summary Box */
        .summary-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .summary-box {
            border: 1px solid #d1d5db;
            background-color: #f9fafb;
            padding: 6px 8px;
            text-align: center;
        }
        .summary-label {
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #6b7280;
        }
        .summary-val {
            font-size: 11pt;
            font-weight: bold;
            color: #111827;
        }

        /* Tabel Data */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #9ca3af;
            padding: 4.5px 5px;
            font-size: 7.8pt;
        }
        table.data-table th {
            background-color: #f3f4f6;
            color: #111827;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            text-transform: uppercase;
        }
        .text-center { text-align: center; }
        .text-start { text-align: left; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }

        /* Row total */
        .row-total {
            background-color: #e5e7eb;
            font-weight: bold;
        }

        /* Tanda Tangan */
        .ttd-container {
            width: 100%;
            margin-top: 18px;
            page-break-inside: avoid;
        }
        .ttd-box {
            width: 250px;
            float: right;
            text-align: center;
        }

        /* Print View Tools */
        @media print {
            .no-print { display: none !important; }
        }
        .print-toolbar {
            position: fixed;
            top: 15px;
            right: 20px;
            background: #ffffff;
            padding: 8px 14px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border: 1px solid #e5e7eb;
            z-index: 9999;
        }
        .btn-print {
            background: #1e40af;
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>
<body>

    @if($isPrintView ?? false)
        <div class="print-toolbar no-print">
            <button class="btn-print" onclick="window.print()">🖨️ Cetak Dokumen (Ctrl+P)</button>
        </div>
    @endif

    <!-- KOP SURAT PEMDA -->
    <table class="kop-table">
        <tr>
            <td style="width: 10%; text-align: center;">
                @if(!empty($kop['kop_logo']))
                    <img src="{{ public_path('uploads/report/' . $kop['kop_logo']) }}" style="max-height: 55px; max-width: 55px;" alt="Logo">
                @else
                    <img src="{{ public_path('images/logo-donggala.png') }}" style="max-height: 55px; max-width: 55px;" alt="Logo" onerror="this.style.display='none'">
                @endif
            </td>
            <td style="width: 90%; text-align: center;">
                <div class="kop-instansi">{{ $kop['kop_nama_instansi'] ?? 'PEMERINTAH KABUPATEN DONGGALA' }}</div>
                <div class="kop-unit">{{ $kop['kop_nama_unit'] ?? 'BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH' }}</div>
                <div class="kop-sub">{{ $kop['kop_alamat'] ?? 'Jl. Trans Sulawesi, Banawa, Kabupaten Donggala' }} | {{ $kop['kop_kontak'] ?? 'sipat.donggalakab.go.id' }}</div>
            </td>
        </tr>
    </table>

    <!-- JUDUL DOKUMEN -->
    <div class="report-header">
        <div class="report-title">{{ $title ?? 'LAPORAN REKAPITULASI PENSERTIFIKATAN ASET TANAH PER ORGANISASI PERANGKAT DAERAH (OPD)' }}</div>
        <div class="report-subtitle">Pemerintah Kabupaten Donggala &bull; Posisi Data: {{ date('d F Y') }}</div>
    </div>

    @php
        $gt = $rekapData['grand_total'] ?? [];
    @endphp

    <!-- METRIC SUMMARY BOX -->
    <table class="summary-grid">
        <tr>
            <td class="summary-box" style="width: 16%;">
                <div class="summary-label">Total OPD</div>
                <div class="summary-val">{{ number_format($rekapData['total_opd'] ?? 0) }}</div>
            </td>
            <td class="summary-box" style="width: 16%;">
                <div class="summary-label">Total Bidang</div>
                <div class="summary-val">{{ number_format($gt['total_bidang'] ?? 0) }}</div>
            </td>
            <td class="summary-box" style="width: 18%;">
                <div class="summary-label">Total Luas Aset</div>
                <div class="summary-val">{{ number_format($gt['total_luas'] ?? 0, 0, ',', '.') }} m²</div>
            </td>
            <td class="summary-box" style="width: 18%;">
                <div class="summary-label">Sudah Bersertifikat</div>
                <div class="summary-val" style="color: #065f46;">{{ number_format($gt['sudah_sertifikat'] ?? 0) }} Unit ({{ $gt['persen_sertifikat'] ?? 0 }}%)</div>
            </td>
            <td class="summary-box" style="width: 16%;">
                <div class="summary-label">Dalam Proses BPN</div>
                <div class="summary-val" style="color: #854d0e;">{{ number_format($gt['dalam_proses'] ?? 0) }} Unit</div>
            </td>
            <td class="summary-box" style="width: 16%;">
                <div class="summary-label">Belum Diproses</div>
                <div class="summary-val" style="color: #4b5563;">{{ number_format($gt['belum_diproses'] ?? 0) }} Unit</div>
            </td>
        </tr>
    </table>

    <!-- TABEL REKAPITULASI MATRIKS PER OPD -->
    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 25px;">NO</th>
                <th rowspan="2" class="text-start" style="width: 230px;">ORGANISASI PERANGKAT DAERAH (OPD)</th>
                <th colspan="2">TOTAL ASET TANAH</th>
                <th colspan="2">SUDAH BERSERTIFIKAT</th>
                <th colspan="2">DALAM PROSES BPN</th>
                <th colspan="2">BELUM DIPROSES</th>
                <th rowspan="2" style="width: 45px;">SENGKETA / KENDALA</th>
                <th rowspan="2" style="width: 50px;">CAPAIAN (%)</th>
            </tr>
            <tr>
                <th style="width: 45px;">BIDANG</th>
                <th style="width: 68px;">LUAS (M²)</th>
                <th style="width: 45px;">BIDANG</th>
                <th style="width: 68px;">LUAS (M²)</th>
                <th style="width: 45px;">BIDANG</th>
                <th style="width: 68px;">LUAS (M²)</th>
                <th style="width: 45px;">BIDANG</th>
                <th style="width: 68px;">LUAS (M²)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rekapData['items'] as $row)
                <tr>
                    <td class="text-center">{{ $row['no'] }}</td>
                    <td>{{ $row['nama_opd'] }}</td>
                    <td class="text-end fw-bold">{{ number_format($row['total_bidang']) }}</td>
                    <td class="text-end font-mono">{{ number_format($row['total_luas'], 0, ',', '.') }}</td>
                    
                    <td class="text-end fw-bold" style="color: #047857;">{{ number_format($row['sudah_sertifikat']) }}</td>
                    <td class="text-end font-mono">{{ number_format($row['luas_sertifikat'], 0, ',', '.') }}</td>
                    
                    <td class="text-end fw-bold" style="color: #b45309;">{{ number_format($row['dalam_proses']) }}</td>
                    <td class="text-end font-mono">{{ number_format($row['luas_proses'], 0, ',', '.') }}</td>
                    
                    <td class="text-end">{{ number_format($row['belum_diproses']) }}</td>
                    <td class="text-end font-mono">{{ number_format($row['luas_belum_diproses'], 0, ',', '.') }}</td>
                    
                    <td class="text-end" style="color: #b91c1c;">{{ number_format($row['bermasalah']) }}</td>
                    <td class="text-center fw-bold">{{ $row['persen_sertifikat'] }}%</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="row-total">
                <td colspan="2" class="text-center">TOTAL KABUPATEN DONGGALA</td>
                <td class="text-end fw-bold">{{ number_format($gt['total_bidang'] ?? 0) }}</td>
                <td class="text-end font-mono fw-bold">{{ number_format($gt['total_luas'] ?? 0, 0, ',', '.') }}</td>
                
                <td class="text-end fw-bold" style="color: #047857;">{{ number_format($gt['sudah_sertifikat'] ?? 0) }}</td>
                <td class="text-end font-mono fw-bold">{{ number_format($gt['luas_sertifikat'] ?? 0, 0, ',', '.') }}</td>
                
                <td class="text-end fw-bold" style="color: #b45309;">{{ number_format($gt['dalam_proses'] ?? 0) }}</td>
                <td class="text-end font-mono fw-bold">{{ number_format($gt['luas_proses'] ?? 0, 0, ',', '.') }}</td>
                
                <td class="text-end fw-bold">{{ number_format($gt['belum_diproses'] ?? 0) }}</td>
                <td class="text-end font-mono fw-bold">{{ number_format($gt['luas_belum_diproses'] ?? 0, 0, ',', '.') }}</td>
                
                <td class="text-end fw-bold" style="color: #b91c1c;">{{ number_format($gt['bermasalah'] ?? 0) }}</td>
                <td class="text-center fw-bold">{{ $gt['persen_sertifikat'] ?? 0 }}%</td>
            </tr>
        </tfoot>
    </table>

    <!-- LEMBAR PENGESAHAN TANDA TANGAN -->
    <div class="ttd-container">
        <div class="ttd-box">
            <div>{{ $kop['kop_kota_ttd'] ?? 'Banawa' }}, {{ date('d F Y') }}</div>
            <div style="font-weight: bold; margin-top: 3px;">{{ $kop['kop_pejabat_jabatan'] ?? 'Kepala Bidang Pengelolaan Aset Daerah' }}</div>
            <div style="height: 50px;"></div>
            <div style="font-weight: bold; text-decoration: underline;">{{ $kop['kop_pejabat_nama'] ?? 'H. MUHAMMAD NATSIR, S.E., M.Si.' }}</div>
            <div>NIP. {{ $kop['kop_pejabat_nip'] ?? '-' }}</div>
        </div>
        <div style="clear: both;"></div>
    </div>

    @if($isPrintView ?? false)
        <script>
            window.addEventListener('load', function() {
                // Memberi sedikit jeda agar CSS dan gambar termuat sempurna
                setTimeout(function() {
                    window.print();
                }, 500);
            });
        </script>
    @endif
</body>
</html>
