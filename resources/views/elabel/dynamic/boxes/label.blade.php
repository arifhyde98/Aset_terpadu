<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Label Box {{ $box->nomor_box }} - {{ $box->archiveType->nama ?? 'Arsip' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm;
        }
        html, body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #111;
            background: #fff;
        }
        .sheet {
            width: 100%;
            max-width: 194mm;
            min-height: 164mm;
            margin: 0 auto;
            border: 3px solid #111;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .header {
            display: grid;
            grid-template-columns: 100px 1fr 150px;
            gap: 10px;
            align-items: center;
            padding: 12px 16px 10px;
            border-bottom: 3px solid #111;
        }
        .logo {
            width: 85px;
            height: 105px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        .org {
            padding-top: 2px;
        }
        .org h1, .org h2, .org h3 {
            margin: 0;
            line-height: 1.15;
            font-weight: 800;
        }
        .org h1 {
            font-size: 16px;
            margin-bottom: 4px;
        }
        .org h2 {
            font-size: 18px;
            margin-bottom: 4px;
        }
        .org h3 {
            font-size: 20px;
            color: #1e3a8a;
        }
        .qr-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding-top: 4px;
        }
        .qr {
            width: 80px;
            height: 80px;
        }
        .qr img {
            width: 100%;
            height: 100%;
            display: block;
        }
        .box-title-bar {
            background: #f1f5f9;
            padding: 8px 16px;
            border-bottom: 2px solid #111;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .box-title-bar .code {
            font-size: 22px;
            font-weight: 900;
            font-family: monospace;
            color: #0f172a;
        }
        .box-title-bar .category {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            color: #2563eb;
        }
        .content {
            flex: 1;
            padding: 12px 16px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .doc-item {
            font-size: 13px;
            line-height: 1.3;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 4px;
            display: flex;
            gap: 6px;
        }
        .doc-num {
            font-weight: 700;
            color: #64748b;
            min-width: 22px;
        }
        .doc-code {
            font-weight: 700;
            font-family: monospace;
            color: #0f172a;
        }
        .doc-name {
            color: #334155;
            font-size: 12px;
        }
        .footer {
            border-top: 3px solid #111;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 16px;
            background: #fafafa;
        }
        .footer-info {
            font-size: 13px;
            font-weight: 700;
        }
        .print-date {
            font-size: 10px;
            color: #64748b;
        }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .sheet { page-break-inside: avoid; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
<?php
    $logoPath = public_path('assets/logo.png');
    $logoSrc = asset('assets/logo.png');
    if (file_exists($logoPath)) {
        $logoData = base64_encode(file_get_contents($logoPath));
        if ($logoData !== '') {
            $logoSrc = 'data:image/png;base64,' . $logoData;
        }
    }

    $qrPayload = trim(implode("\n", array_filter([
        'Box: ' . ($box->nomor_box ?? ''),
        'Kategori: ' . ($box->archiveType->nama ?? ''),
        'Lokasi: ' . ($box->lokasi_rak ?? '-'),
        'Tahun: ' . ($box->tahun ?? '-'),
        'Total Dokumen: ' . $box->items->count(),
    ])));
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . rawurlencode($qrPayload);
?>
    <div class="sheet">
        <div class="header">
            <div class="logo">
                <img src="{{ $logoSrc }}" alt="Logo Kab Donggala">
            </div>
            <div class="org">
                <h1>Pemerintah Kabupaten Donggala</h1>
                <h2>Badan Pengelolaan Keuangan dan Aset Daerah</h2>
                <h3>Bidang Pengelolaan Barang Milik Daerah</h3>
            </div>
            <div class="qr-wrap">
                <div class="qr">
                    <img src="{{ $qrUrl }}" alt="QR Code Box">
                </div>
            </div>
        </div>

        <div class="box-title-bar">
            <div>
                <span class="category">{{ $box->archiveType->nama ?? 'ARSIP UMUM' }} ({{ $box->archiveType->kode ?? '-' }})</span>
                <div class="code">{{ $box->nomor_box }}</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 13px; font-weight: 700;">Lokasi: {{ $box->lokasi_rak ?: 'Rak Arsip Utama' }}</div>
                <div style="font-size: 12px; color: #64748b;">Tahun: {{ $box->tahun ?: '-' }}</div>
            </div>
        </div>

        <div class="content">
            @forelse($box->items as $idx => $item)
                <div class="doc-item">
                    <span class="doc-num">{{ $idx + 1 }}.</span>
                    <div>
                        <span class="doc-code">{{ $item->nomor_dokumen }}</span>
                        <div class="doc-name">{{ Str::limit($item->nama_dokumen, 45) }}</div>
                    </div>
                </div>
            @empty
                <div style="grid-column: span 2; text-align: center; padding: 30px; color: #94a3b8;">
                    Box ini belum memiliki daftar berkas arsip.
                </div>
            @endforelse
        </div>

        <div class="footer">
            <div class="footer-info">
                <span>Total: <strong>{{ $box->items->count() }} Berkas</strong></span>
                <span style="margin-left: 20px;">Kapasitas: {{ $box->kapasitas_maksimal }}</span>
            </div>
            <div class="print-date">
                Dicetak pada: {{ date('d/m/Y H:i') }} | SIPAT Terpadu eLABEL
            </div>
        </div>
    </div>
</body>
<script>
    window.addEventListener('load', function () {
        if (window.location.search.indexOf('autoprint=1') !== -1) {
            window.print();
        }
    });
</script>
</html>
