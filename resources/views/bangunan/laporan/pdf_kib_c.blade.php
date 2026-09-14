<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $kop['kop_line3'] }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 8pt;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }
        .header-title {
            text-align: center;
        }
        .header-title h4 {
            margin: 0 0 2px 0;
            font-size: 11pt;
            font-weight: bold;
            letter-spacing: 0.05em;
        }
        .header-title h3 {
            margin: 0 0 3px 0;
            font-size: 13pt;
            font-weight: bold;
        }
        .header-title h5 {
            margin: 0;
            font-size: 10pt;
            font-weight: bold;
            color: #1e40af;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 8px;
            font-size: 8pt;
        }
        table.kib-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
        }
        table.kib-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: bold;
            text-align: center;
            padding: 4px;
            border: 1px solid #475569;
        }
        table.kib-table td {
            padding: 4px;
            border: 1px solid #475569;
            vertical-align: middle;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .font-mono { font-family: monospace; }
        
        .sig-table {
            width: 100%;
            margin-top: 24px;
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <!-- KOP Surat -->
    <table class="header-table">
        <tr>
            <td class="header-title">
                <h4>{{ $kop['kop_line1'] }}</h4>
                <h3>{{ $kop['kop_line2'] }}</h3>
                <h5>{{ $kop['kop_line3'] }}</h5>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td style="width: 12%;"><strong>PROVINSI</strong></td>
            <td style="width: 2%;">:</td>
            <td style="width: 46%;">SULAWESI TENGAH</td>
            <td style="width: 15%;"><strong>KODE LOKASI</strong></td>
            <td style="width: 2%;">:</td>
            <td style="width: 23%;">72.03.00.00</td>
        </tr>
        <tr>
            <td><strong>KABUPATEN</strong></td>
            <td>:</td>
            <td>DONGGALA</td>
            <td><strong>TAHUN ANGGARAN</strong></td>
            <td>:</td>
            <td>{{ date('Y') }}</td>
        </tr>
        @if($selectedOpd)
        <tr>
            <td><strong>OPD / SKPD</strong></td>
            <td>:</td>
            <td colspan="4">{{ strtoupper($selectedOpd->nama) }}</td>
        </tr>
        @endif
    </table>

    <!-- Tabel KIB C (18 Kolom Standar Permendagri) -->
    <table class="kib-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 22px;">No</th>
                <th rowspan="2" style="width: 120px;">Jenis Barang / Nama Bangunan</th>
                <th rowspan="2" style="width: 60px;">Kode Barang</th>
                <th rowspan="2" style="width: 35px;">No. Reg</th>
                <th rowspan="2" style="width: 45px;">Kondisi (B/KB/RB)</th>
                <th colspan="2">Konstruksi</th>
                <th rowspan="2" style="width: 45px;">Luas Lantai (m²)</th>
                <th rowspan="2" style="width: 110px;">Letak / Alamat</th>
                <th colspan="2">Dokumen Gedung</th>
                <th rowspan="2" style="width: 45px;">Luas Tanah (m²)</th>
                <th rowspan="2" style="width: 65px;">Status Tanah</th>
                <th rowspan="2" style="width: 65px;">No. Kode Tanah</th>
                <th rowspan="2" style="width: 50px;">Asal Usul</th>
                <th rowspan="2" style="width: 65px;">Harga Perolehan (Rp)</th>
                <th rowspan="2" style="width: 50px;">Ket</th>
            </tr>
            <tr>
                <th style="width: 30px;">Tingkat</th>
                <th style="width: 30px;">Beton</th>
                <th style="width: 45px;">Tanggal</th>
                <th style="width: 70px;">Nomor PBG/IMB</th>
            </tr>
            <tr style="background-color: #e2e8f0; font-size: 6.5pt;">
                @for($col = 1; $col <= 17; $col++)
                    <th>{{ $col }}</th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @forelse($items as $i => $b)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td class="fw-bold">{{ $b->nama_bangunan }}</td>
                    <td class="text-center font-mono">{{ $b->kode_barang ?: '-' }}</td>
                    <td class="text-center font-mono">{{ $b->nomor_register ?: '-' }}</td>
                    <td class="text-center">
                        {{ is_object($b->kondisi) ? $b->kondisi->value : $b->kondisi }}
                    </td>
                    <td class="text-center">{{ $b->konstruksi_tingkat ? 'Tingkat' : 'Tidak' }}</td>
                    <td class="text-center">{{ $b->konstruksi_beton ? 'Beton' : 'Bukan' }}</td>
                    <td class="text-end">{{ number_format($b->luas_lantai, 2, ',', '.') }}</td>
                    <td>{{ $b->alamat ?: ($b->kecamatan?->nama ?? '-') }}</td>
                    <td class="text-center">{{ $b->tanggal_dokumen_pbg ? $b->tanggal_dokumen_pbg->format('d/m/Y') : '-' }}</td>
                    <td>{{ $b->nomor_dokumen_pbg ?: '-' }}</td>
                    <td class="text-end">{{ $b->asetTanah ? number_format($b->asetTanah->luas, 0) : ($b->luas_tanah_dasar ? number_format($b->luas_tanah_dasar, 0) : '-') }}</td>
                    <td>{{ $b->status_tanah_dasar ?: ($b->asetTanah ? 'Milik Pemkab' : '-') }}</td>
                    <td class="font-mono text-center">{{ $b->asetTanah?->kode_aset ?: '-' }}</td>
                    <td class="text-center">{{ $b->asal_usul ?: 'APBD' }}</td>
                    <td class="text-end fw-bold">{{ number_format($b->harga_perolehan, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $b->tahun_pengadaan ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="17" class="text-center" style="padding: 20px;">
                        Tidak ada data inventaris gedung dan bangunan pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($items->isNotEmpty())
            <tfoot>
                <tr style="background-color: #f1f5f9; font-weight: bold;">
                    <td colspan="7" class="text-center">JUMLAH TOTAL</td>
                    <td class="text-end">{{ number_format($totalLuas, 2, ',', '.') }}</td>
                    <td colspan="7"></td>
                    <td class="text-end">{{ number_format($totalNilai, 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <!-- Tanda Tangan Ganda Pejabat -->
    <table class="sig-table">
        <tr>
            <td style="width: 50%; text-align: center; vertical-align: top;">
                Mengetahui,<br>
                <strong>{{ $signatories['kadis_jabatan'] }}</strong>
                <br><br><br><br><br>
                <strong><u>{{ $signatories['kadis_nama'] }}</u></strong><br>
                NIP. {{ $signatories['kadis_nip'] }}
            </td>
            <td style="width: 50%; text-align: center; vertical-align: top;">
                Banawa, {{ date('d F Y') }}<br>
                <strong>{{ $signatories['pengurus_nama'] }}</strong>
                <br><br><br><br><br>
                <strong><u>{{ $signatories['pengurus_nama'] }}</u></strong><br>
                NIP. {{ $signatories['pengurus_nip'] }}
            </td>
        </tr>
    </table>
</body>
</html>
