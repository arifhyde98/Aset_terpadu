<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak SKPT</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; }
        .no-print { margin-bottom: 12px; }
        @media print { .no-print { display: none; } }
        .center { text-align: center; }
        .title { font-weight: 700; }
        .section { font-size: 12px; line-height: 1.5; }
        table { width: 100%; }
        td { vertical-align: top; }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Print / Save PDF</button>
    </div>

    <?php
        $alamatKantor = trim((string) ($skpt['alamat_kantor'] ?? ''));
        $desaJenisRaw = strtolower(trim((string) ($skpt['desa_jenis'] ?? '')));
        $desaLabel = $desaJenisRaw === 'kelurahan' ? 'Kelurahan' : 'Desa';
        $desaLabelUpper = strtoupper($desaLabel);
        $pejabatLabel = $desaLabel === 'Kelurahan' ? 'Lurah' : 'Kepala Desa';
        $jenisTanah = trim((string) ($skpt['jenis_tanah'] ?? ''));
        if ($jenisTanah === '') {
            $jenisTanah = 'Pekarangan dan Bangunan';
        }
        $statusTanah = trim((string) ($skpt['status_tanah'] ?? ''));
        if ($statusTanah === '') {
            $statusTanah = 'tanah yang dikuasai oleh negara (bekas tanah Swapraja)';
        }
        $lokasiTanah = trim((string) ($skpt['lokasi_tanah'] ?? ''));
        $lokasiText = $lokasiTanah !== '' ? $lokasiTanah . ' ' : '';
        $asalTanah = trim((string) ($skpt['asal_tanah'] ?? ''));
        if ($asalTanah === '') {
            $asalTanah = 'Selanjutnya diterangkan bahwa bidang tanah tersebut berasal dari tanah negara yang dibuka langsung dan dikuasai oleh …………………………... pada tahun ………... kemudian tanah tersebut diserahkan/beralih kepada Pemerintah Kabupaten Donggala secara ' . ($skpt['dasar_perolehan'] ?? 'Jual Beli tanpa surat-surat') . ' pada tahun ………';
        }
        $pernyataanTanah = trim((string) ($skpt['pernyataan_tanah'] ?? ''));
        if ($pernyataanTanah === '') {
            $pernyataanTanah = 'Bahwa tanah tersebut merupakan tanah Non Pertanian milik Pemerintah Kabupaten Donggala serta pihak lain tidak ada yang keberatan/tidak dalam sengketa.';
        }
    ?>

    <div class="center">
        <div class="title">PEMERINTAH KABUPATEN DONGGALA</div>
        <div class="title">KECAMATAN {{ $skpt['kecamatan_nama'] ?? '-' }}</div>
        <div class="title">{{ $desaLabelUpper }} {{ $skpt['desa_nama'] ?? '-' }}</div>
        @if ($alamatKantor !== '')
            <div>Alamat : {{ $alamatKantor }}</div>
        @endif
        <div class="title">SURAT KETERANGAN PENGUASAAN TANAH</div>
        <div class="title">NOMOR : {{ $skpt['nomor_surat'] ?? '-' }}</div>
    </div>

    <hr>

    <div class="section">
        Yang bertanda tangan di Bawah ini {{ $pejabatLabel }} {{ $skpt['desa_nama'] ?? '-' }} Kecamatan {{ $skpt['kecamatan_nama'] ?? '-' }} Kabupaten Donggala Provinsi Sulawesi Tengah menerangkan dengan sebenarnya bahwa:
        <table style="margin-top: 8px; margin-bottom: 12px;">
            <tr><td style="width: 150px;">Nama</td><td style="width: 8px;">:</td><td>{{ $skpt['pemohon_nama'] ?? '-' }}</td></tr>
            <tr><td>NIK</td><td>:</td><td>{{ $skpt['pemohon_nik'] ?? '-' }}</td></tr>
            <tr><td>TTL</td><td>:</td><td>{{ $skpt['pemohon_ttl'] ?? '-' }}</td></tr>
            <tr><td>Umur</td><td>:</td><td>{{ $skpt['pemohon_umur'] ?? '-' }}</td></tr>
            <tr><td>Warga Negara</td><td>:</td><td>{{ $skpt['pemohon_wn'] ?? '-' }}</td></tr>
            <tr><td>Pekerjaan</td><td>:</td><td>{{ $skpt['pemohon_pekerjaan'] ?? '-' }}</td></tr>
            <tr><td>Jabatan</td><td>:</td><td>{{ $skpt['pemohon_jabatan'] ?? '-' }}</td></tr>
            <tr><td>Alamat</td><td>:</td><td>{{ $skpt['pemohon_alamat'] ?? '-' }}</td></tr>
        </table>

        Benar mengusahakan / Menggarap / Menggunakan dan atau menguasai sebidang tanah {{ $jenisTanah }} dengan status tanah {{ $statusTanah }} seluas {{ $skpt['luas_tanah'] ?? '-' }} M2 yang terletak di {{ $lokasiText . $desaLabel }} {{ $skpt['desa_nama'] ?? '-' }} Kecamatan {{ $skpt['kecamatan_nama'] ?? '-' }} dengan batas-batas sebagai berikut :
        <table style="margin-top: 8px; margin-bottom: 12px;">
            <tr><td style="width: 150px;">Sebelah Utara</td><td style="width: 8px;">:</td><td>{{ $skpt['batas_utara'] ?? '-' }}</td></tr>
            <tr><td>Sebelah Timur</td><td>:</td><td>{{ $skpt['batas_timur'] ?? '-' }}</td></tr>
            <tr><td>Sebelah Selatan</td><td>:</td><td>{{ $skpt['batas_selatan'] ?? '-' }}</td></tr>
            <tr><td>Sebelah Barat</td><td>:</td><td>{{ $skpt['batas_barat'] ?? '-' }}</td></tr>
        </table>

        <div>{!! nl2br(e($asalTanah)) !!}</div>
        <div style="margin-top: 8px;">{!! nl2br(e($pernyataanTanah)) !!}</div>
        <div style="margin-top: 8px;">Demikian surat keterangan penguasaan tanah ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya dan mengingat sumpah jabatan.</div>
        @if (!empty($skpt['keterangan']))
            <div style="margin-top: 8px;">Keterangan: {{ $skpt['keterangan'] }}</div>
        @endif

        <div style="margin-top: 16px; text-align: right;">
            Tanggal, {{ $skpt['tanggal_surat'] ?? '-' }}
        </div>

        <table style="margin-top: 24px;">
            <tr>
                <td>Mengetahui,<br>Camat {{ $skpt['kecamatan_nama'] ?? '-' }}</td>
                <td style="text-align:right;">{{ $pejabatLabel }} {{ $skpt['desa_nama'] ?? '-' }}</td>
            </tr>
            <tr>
                <td style="height: 60px;"></td>
                <td></td>
            </tr>
            <tr>
                <td>
                    {{ $skpt['camat_nama'] ?? '-' }}<br>
                    @if (!empty($skpt['camat_nip']))
                        NIP. {{ $skpt['camat_nip'] }}
                    @endif
                </td>
                <td style="text-align:right;">
                    {{ $skpt['kepala_desa_nama'] ?? '-' }}<br>
                    @if (!empty($skpt['kepala_desa_nip']))
                        NIP. {{ $skpt['kepala_desa_nip'] }}
                    @endif
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
