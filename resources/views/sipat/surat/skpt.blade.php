@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-semibold mb-1">Generate Surat Tanah - SKPT</h1>
        <small class="text-secondary">Isi data pemohon dan detail tanah, lalu simpan untuk preview.</small>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger rounded-3 mb-4" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card clean-card border-0 mb-3">
            <div class="card-body">
                <form method="post" action="{!! url('sipat/surat/skpt') !!}">
                    {!! csrf_field() !!}
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nomor Surat (opsional)</label>
                            <input type="text" name="nomor_surat" class="form-control" placeholder="SKPT-20260206-1234">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Surat</label>
                            <input type="date" name="tanggal_surat" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat Kantor (Kop Surat)</label>
                            <input type="text" name="alamat_kantor" class="form-control" placeholder="Contoh: Jl. Poros Palu – Mamuju Kel. Kabonga Kecil Kec. Banawa">
                        </div>
                        <div class="col-12">
                            <h6 class="text-primary">Data Pemohon</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pilih Pemohon</label>
                            <select name="pemohon_id" id="pemohonSelect" class="form-select" required>
                                <option value="">- pilih pemohon -</option>
                                @foreach ($pemohonList ?? [] as $pemohon)
                                    <option
                                        value="{{ $pemohon['id'] }}"
                                        data-nama="{{ $pemohon['nama'] }}"
                                        data-nik="{{ $pemohon['nik'] ?? '' }}"
                                        data-ttl="{{ $pemohon['ttl'] ?? '' }}"
                                        data-umur="{{ $pemohon['umur'] ?? '' }}"
                                        data-jk="{{ $pemohon['jenis_kelamin'] ?? '' }}"
                                        data-wn="{{ $pemohon['warga_negara'] ?? '' }}"
                                        data-agama="{{ $pemohon['agama'] ?? '' }}"
                                        data-pekerjaan="{{ $pemohon['pekerjaan'] ?? '' }}"
                                        data-jabatan="{{ $pemohon['jabatan'] ?? '' }}"
                                        data-alamat="{{ $pemohon['alamat'] ?? '' }}"
                                    >
                                        {{ $pemohon['nama'] }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-secondary">Jika dipilih, data pemohon otomatis terisi.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Pemohon</label>
                            <input type="text" id="pemohonNama" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NIK</label>
                            <input type="text" id="pemohonNik" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tempat, Tgl Lahir</label>
                            <input type="text" id="pemohonTtl" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Umur</label>
                            <input type="text" id="pemohonUmur" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jenis Kelamin</label>
                            <select id="pemohonJk" class="form-select" disabled>
                                <option value="">- pilih -</option>
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Warga Negara</label>
                            <input type="text" id="pemohonWn" class="form-control" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Agama</label>
                            <input type="text" id="pemohonAgama" class="form-control" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pekerjaan</label>
                            <input type="text" id="pemohonPekerjaan" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jabatan</label>
                            <input type="text" id="pemohonJabatan" class="form-control" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat Pemohon</label>
                            <textarea id="pemohonAlamat" class="form-control" rows="2" readonly></textarea>
                        </div>
                        <div class="col-12">
                            <h6 class="text-primary">Data Tanah</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kecamatan</label>
                            <select name="kecamatan_id" id="kecamatanSelect" class="form-select" required>
                                <option value="">- pilih kecamatan -</option>
                                @foreach ($kecamatanList ?? [] as $kecamatan)
                                    <option value="{{ $kecamatan['id'] }}">{{ $kecamatan['nama'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Desa/Kelurahan</label>
                            <select name="desa_id" id="desaSelect" class="form-select" required>
                                <option value="">- pilih desa -</option>
                                @foreach ($desaList ?? [] as $desa)
                                    <option value="{{ $desa['id'] }}" data-kec="{{ $desa['kecamatan_id'] ?? '' }}">
                                        {{ $desa['nama'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Lokasi Tanah</label>
                            <textarea name="lokasi_tanah" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jenis Tanah</label>
                            <input type="text" name="jenis_tanah" class="form-control" placeholder="Pekarangan dan Bangunan">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status Tanah</label>
                            <input type="text" name="status_tanah" class="form-control" placeholder="Tanah negara (bekas tanah Swapraja)">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Luas (m2)</label>
                            <input type="number" step="0.01" name="luas_tanah" class="form-control">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Dasar Perolehan</label>
                            <input type="text" name="dasar_perolehan" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Batas Utara</label>
                            <input type="text" name="batas_utara" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Batas Timur</label>
                            <input type="text" name="batas_timur" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Batas Selatan</label>
                            <input type="text" name="batas_selatan" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Batas Barat</label>
                            <input type="text" name="batas_barat" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Asal / Riwayat Tanah</label>
                            <textarea name="asal_tanah" class="form-control" rows="2" placeholder="Selanjutnya diterangkan bahwa bidang tanah tersebut berasal dari ..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Pernyataan Tanah</label>
                            <textarea name="pernyataan_tanah" class="form-control" rows="2" placeholder="Bahwa tanah tersebut merupakan tanah Non Pertanian ..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <h6 class="text-primary">Pejabat</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pilih Kepala Desa / Lurah</label>
                            <select name="kepala_desa_id" id="kepalaDesaSelect" class="form-select" required>
                                <option value="">- pilih kepala desa -</option>
                                @foreach ($kepalaList ?? [] as $kepala)
                                    <option
                                        value="{{ $kepala['id'] }}"
                                        data-desa="{{ $kepala['desa_id'] }}"
                                        data-nama="{{ $kepala['nama'] }}"
                                        data-nip="{{ $kepala['nip'] ?? '' }}"
                                    >
                                        {{ $kepala['nama'] }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-secondary">Jika dipilih, nama & NIP otomatis terisi.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Kepala Desa / Lurah</label>
                            <input type="text" id="kepalaDesaNama" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NIP Kepala Desa / Lurah</label>
                            <input type="text" id="kepalaDesaNip" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pilih Camat</label>
                            <select name="camat_id" id="camatSelect" class="form-select" required>
                                <option value="">- pilih camat -</option>
                                @foreach ($camatList ?? [] as $camat)
                                    <option
                                        value="{{ $camat['id'] }}"
                                        data-kec="{{ $camat['kecamatan_id'] ?? '' }}"
                                        data-nama="{{ $camat['nama'] }}"
                                        data-nip="{{ $camat['nip'] ?? '' }}"
                                    >
                                        {{ $camat['nama'] }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-secondary">Jika dipilih, nama & NIP otomatis terisi.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Camat</label>
                            <input type="text" id="camatNama" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NIP Camat</label>
                            <input type="text" id="camatNip" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="mt-3 d-flex justify-content-end">
                        <button class="btn btn-primary">Simpan & Preview</button>
                    </div>
                </form>
            </div>
        </div>

        @if (!empty($recent))
            <div class="card clean-card border-0">
                <div class="card-body">
                    <h6 class="fw-semibold">SKPT Terbaru</h6>
                    <form method="get" action="{!! url()->current() !!}" class="row g-2 align-items-end mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Filter Kecamatan</label>
                            <select name="kecamatan_id" class="form-select">
                                <option value="">- semua kecamatan -</option>
                                @foreach ($kecamatanList ?? [] as $kecamatan)
                                    <option value="{{ $kecamatan['id'] }}" {!! ((int) ($filterKecamatan ?? 0) === (int) $kecamatan['id']) ? 'selected' : '' !!}>
                                        {{ $kecamatan['nama'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-outline-primary w-100">Terapkan</button>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 table-premium">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nomor Surat</th>
                                    <th>Pemohon</th>
                                    <th>Kecamatan</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recent as $r)
                                    <tr>
                                        <td>{{ $r['id'] }}</td>
                                        <td>{{ $r['nomor_surat'] }}</td>
                                        <td>{{ $r['pemohon_nama'] ?? '-' }}</td>
                                        <td>{{ $r['kecamatan_nama'] ?? '-' }}</td>
                                        <td>{{ $r['tanggal_surat'] ?? '-' }}</td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                <a href="{!! url('sipat/surat/skpt/' . $r['id']) !!}" class="btn btn-sm btn-outline-primary">Preview</a>
                                                <a href="{!! url('sipat/surat/skpt/' . $r['id'] . '/pdf') !!}" class="btn btn-sm btn-outline-danger">PDF</a>
                                                <a href="{!! url('sipat/surat/skpt/' . $r['id'] . '/word') !!}" class="btn btn-sm btn-outline-primary">Word</a>
                                                <form action="{!! url('sipat/surat/skpt/' . $r['id']) !!}" method="post" data-confirm="Hapus SKPT ini?">
                                                    {!! csrf_field() !!}
                                                    <input type="hidden" name="_method" value="DELETE">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-5">
        <div class="card clean-card border-0">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Preview SKPT</h6>
                @if (empty($skpt))
                    <p class="text-secondary mb-0">Isi form dan simpan untuk melihat preview.</p>
                @else
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <a href="{!! url('sipat/surat/skpt/' . $skpt['id'] . '/pdf') !!}" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-file-earmark-pdf"></i> Download PDF
                        </a>
                        <a href="{!! url('sipat/surat/skpt/' . $skpt['id'] . '/word') !!}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-file-earmark-word"></i> Export Word
                        </a>
                    </div>
                    <div class="paper-sheet">
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
                        <div class="text-center" style="font-size: 12px; line-height: 1.4;">
                            <div class="fw-semibold">PEMERINTAH KABUPATEN DONGGALA</div>
                            <div class="fw-semibold">KECAMATAN {{ $skpt['kecamatan_nama'] ?? '-' }}</div>
                            <div class="text-danger fw-semibold">{{ $desaLabelUpper }} {{ $skpt['desa_nama'] ?? '-' }}</div>
                            @if ($alamatKantor !== '')
                                <div>Alamat : {{ $alamatKantor }}</div>
                            @endif
                            <div class="fw-semibold">SURAT KETERANGAN PENGUASAAN TANAH</div>
                            <div class="mt-2">NOMOR : {{ $skpt['nomor_surat'] }}</div>
                        </div>
                        <hr>
                        <div style="font-size: 12px; line-height: 1.5;">
                            Yang bertanda tangan di Bawah ini {{ $pejabatLabel }} {{ $skpt['desa_nama'] ?? '-' }} Kecamatan {{ $skpt['kecamatan_nama'] ?? '-' }} Kabupaten Donggala Provinsi Sulawesi Tengah menerangkan dengan sebenarnya bahwa:
                            <table class="table table-borderless mt-2 mb-3" style="font-size: 12px;">
                                <tr>
                                    <td style="width: 150px;">Nama</td>
                                    <td style="width: 8px;">:</td>
                                    <td>{{ $skpt['pemohon_nama'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>NIK</td>
                                    <td>:</td>
                                    <td>{{ $skpt['pemohon_nik'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>TTL</td>
                                    <td>:</td>
                                    <td>{{ $skpt['pemohon_ttl'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>Umur</td>
                                    <td>:</td>
                                    <td>{{ $skpt['pemohon_umur'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>Warga Negara</td>
                                    <td>:</td>
                                    <td>{{ $skpt['pemohon_wn'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>Pekerjaan</td>
                                    <td>:</td>
                                    <td>{{ $skpt['pemohon_pekerjaan'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>Jabatan</td>
                                    <td>:</td>
                                    <td>{{ $skpt['pemohon_jabatan'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>Alamat</td>
                                    <td>:</td>
                                    <td>{{ $skpt['pemohon_alamat'] ?? '-' }}</td>
                                </tr>
                            </table>
                            Benar mengusahakan / Menggarap / Menggunakan dan atau menguasai sebidang tanah {{ $jenisTanah }} dengan status tanah {{ $statusTanah }} seluas {{ $skpt['luas_tanah'] ?? '-' }} M2 yang terletak di {{ $lokasiText . $desaLabel }} {{ $skpt['desa_nama'] ?? '-' }} Kecamatan {{ $skpt['kecamatan_nama'] ?? '-' }} dengan batas-batas sebagai berikut:
                            <table class="table table-borderless mt-2 mb-3" style="font-size: 12px;">
                                <tr>
                                    <td style="width: 150px;">Sebelah Utara</td>
                                    <td style="width: 8px;">:</td>
                                    <td>{{ $skpt['batas_utara'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>Sebelah Timur</td>
                                    <td>:</td>
                                    <td>{{ $skpt['batas_timur'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>Sebelah Selatan</td>
                                    <td>:</td>
                                    <td>{{ $skpt['batas_selatan'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>Sebelah Barat</td>
                                    <td>:</td>
                                    <td>{{ $skpt['batas_barat'] ?? '-' }}</td>
                                </tr>
                            </table>
                            <div class="mt-2">{!! nl2br(e($asalTanah)) !!}</div>
                            <div class="mt-2">{!! nl2br(e($pernyataanTanah)) !!}</div>
                            <div class="mt-2">Demikian surat keterangan penguasaan tanah ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya dan mengingat sumpah jabatan.</div>
                            @if (!empty($skpt['keterangan']))
                                <div class="mt-2">Keterangan: {{ $skpt['keterangan'] }}</div>
                            @endif
                            <div class="mt-4 text-end">
                                Tanggal, {{ $skpt['tanggal_surat'] ?? '-' }}
                            </div>
                            <div class="mt-4 d-flex justify-content-between">
                                <div class="text-center">
                                    Mengetahui,<br>
                                    Camat {{ $skpt['kecamatan_nama'] ?? '-' }}<br><br><br>
                                    <strong>{{ $skpt['camat_nama'] ?? '-' }}</strong><br>
                                    @if (!empty($skpt['camat_nip']))
                                        NIP. {{ $skpt['camat_nip'] }}
                                    @endif
                                </div>
                                <div class="text-center">
                                    {{ $pejabatLabel }} {{ $skpt['desa_nama'] ?? '-' }}<br><br><br>
                                    <strong>{{ $skpt['kepala_desa_nama'] ?? '-' }}</strong><br>
                                    @if (!empty($skpt['kepala_desa_nip']))
                                        NIP. {{ $skpt['kepala_desa_nip'] }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const kecamatanSelect = document.getElementById('kecamatanSelect');
        const desaSelect = document.getElementById('desaSelect');
        const kepalaSelect = document.getElementById('kepalaDesaSelect');
        const pemohonSelect = document.getElementById('pemohonSelect');
        const camatSelect = document.getElementById('camatSelect');
        if (!desaSelect || !kepalaSelect || !kecamatanSelect) return;

        const allKepalaOptions = Array.from(kepalaSelect.options);
        const allDesaOptions = Array.from(desaSelect.options);
        const allCamatOptions = camatSelect ? Array.from(camatSelect.options) : [];
        const filterKepala = (selectedValue) => {
            const desaId = desaSelect.value;
            const keepValue = selectedValue || kepalaSelect.value;
            kepalaSelect.innerHTML = '';
            allKepalaOptions.forEach(opt => {
                if (opt.value === '') {
                    kepalaSelect.appendChild(opt.cloneNode(true));
                    return;
                }
                if (desaId === '' || opt.dataset.desa === desaId) {
                    kepalaSelect.appendChild(opt.cloneNode(true));
                }
            });
            if (keepValue) {
                const exists = Array.from(kepalaSelect.options).some(o => o.value === keepValue);
                if (exists) kepalaSelect.value = keepValue;
            }
        };

        const filterDesa = (selectedValue) => {
            const kecId = kecamatanSelect.value;
            const keepValue = selectedValue || desaSelect.value;
            desaSelect.innerHTML = '';
            allDesaOptions.forEach(opt => {
                if (opt.value === '') {
                    desaSelect.appendChild(opt.cloneNode(true));
                    return;
                }
                if (kecId === '' || opt.dataset.kec === kecId) {
                    desaSelect.appendChild(opt.cloneNode(true));
                }
            });
            if (keepValue) {
                const exists = Array.from(desaSelect.options).some(o => o.value === keepValue);
                if (exists) desaSelect.value = keepValue;
            }
        };

        const filterCamat = (selectedValue) => {
            if (!camatSelect) return;
            const kecId = kecamatanSelect.value;
            const keepValue = selectedValue || camatSelect.value;
            camatSelect.innerHTML = '';
            allCamatOptions.forEach(opt => {
                if (opt.value === '') {
                    camatSelect.appendChild(opt.cloneNode(true));
                    return;
                }
                if (kecId === '' || opt.dataset.kec === kecId) {
                    camatSelect.appendChild(opt.cloneNode(true));
                }
            });
            if (keepValue) {
                const exists = Array.from(camatSelect.options).some(o => o.value === keepValue);
                if (exists) camatSelect.value = keepValue;
            }
        };

        const kepalaNama = document.getElementById('kepalaDesaNama');
        const kepalaNip = document.getElementById('kepalaDesaNip');
        const camatNama = document.getElementById('camatNama');
        const camatNip = document.getElementById('camatNip');

        const updateKepalaInfo = () => {
            const opt = kepalaSelect.selectedOptions[0];
            if (!opt || opt.value === '') {
                if (kepalaNama) kepalaNama.value = '';
                if (kepalaNip) kepalaNip.value = '';
                return;
            }
            if (kepalaNama) kepalaNama.value = opt.dataset.nama || '';
            if (kepalaNip) kepalaNip.value = opt.dataset.nip || '';
        };

        const updateCamatInfo = () => {
            const opt = camatSelect ? camatSelect.selectedOptions[0] : null;
            if (!opt || opt.value === '') {
                if (camatNama) camatNama.value = '';
                if (camatNip) camatNip.value = '';
                return;
            }
            if (camatNama) camatNama.value = opt.dataset.nama || '';
            if (camatNip) camatNip.value = opt.dataset.nip || '';
        };

        const updatePemohonInfo = () => {
            if (!pemohonSelect) return;
            const opt = pemohonSelect.selectedOptions[0];
            const nama = document.getElementById('pemohonNama');
            const nik = document.getElementById('pemohonNik');
            const ttl = document.getElementById('pemohonTtl');
            const umur = document.getElementById('pemohonUmur');
            const jk = document.getElementById('pemohonJk');
            const wn = document.getElementById('pemohonWn');
            const agama = document.getElementById('pemohonAgama');
            const pekerjaan = document.getElementById('pemohonPekerjaan');
            const jabatan = document.getElementById('pemohonJabatan');
            const alamat = document.getElementById('pemohonAlamat');

            if (!opt || opt.value === '') {
                if (nama) nama.value = '';
                if (nik) nik.value = '';
                if (ttl) ttl.value = '';
                if (umur) umur.value = '';
                if (jk) jk.value = '';
                if (wn) wn.value = '';
                if (agama) agama.value = '';
                if (pekerjaan) pekerjaan.value = '';
                if (jabatan) jabatan.value = '';
                if (alamat) alamat.value = '';
                return;
            }
            if (nama) nama.value = opt.dataset.nama || '';
            if (nik) nik.value = opt.dataset.nik || '';
            if (ttl) ttl.value = opt.dataset.ttl || '';
            if (umur) umur.value = opt.dataset.umur || '';
            if (jk) jk.value = opt.dataset.jk || '';
            if (wn) wn.value = opt.dataset.wn || '';
            if (agama) agama.value = opt.dataset.agama || '';
            if (pekerjaan) pekerjaan.value = opt.dataset.pekerjaan || '';
            if (jabatan) jabatan.value = opt.dataset.jabatan || '';
            if (alamat) alamat.value = opt.dataset.alamat || '';
        };

        kecamatanSelect.addEventListener('change', () => {
            filterDesa();
            filterCamat();
            filterKepala();
            updateKepalaInfo();
            updateCamatInfo();
        });
        desaSelect.addEventListener('change', () => {
            const opt = desaSelect.selectedOptions[0];
            if (opt && opt.dataset.kec) {
                kecamatanSelect.value = opt.dataset.kec;
                filterDesa(desaSelect.value);
                filterCamat(camatSelect ? camatSelect.value : '');
            }
            filterKepala(kepalaSelect.value);
            updateKepalaInfo();
        });
        kepalaSelect.addEventListener('change', updateKepalaInfo);
        if (camatSelect) camatSelect.addEventListener('change', updateCamatInfo);
        if (pemohonSelect) pemohonSelect.addEventListener('change', updatePemohonInfo);

        filterDesa();
        filterCamat();
        filterKepala();
        updateKepalaInfo();
        updateCamatInfo();
        updatePemohonInfo();
    });
</script>
@endsection
