@extends('layouts.app')

@section('title', 'Kendaraan Pinjam Pakai')

@section('content')
<div class="container-fluid px-0">

    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ Route::has('vehicles.index') ? route('vehicles.index') : '#' }}" class="text-decoration-none text-secondary">Kendaraan Dinas</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Pinjam Pakai BMD</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-navy mb-0">Manajemen Kendaraan Dinas Pinjam Pakai</h3>
            <p class="text-secondary small mb-0">Pendataan Naskah Perjanjian Pinjam Pakai (NPPP / BAST) untuk Instansi Vertikal dan Antar OPD.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('vehicles.pinjam-pakai.import.template') }}" class="btn btn-light border shadow-sm fw-medium d-flex align-items-center gap-1.5" title="Download Format Excel Sample">
                <i class="bi bi-download text-success"></i> Template Excel
            </a>
            <button type="button" class="btn btn-outline-success shadow-sm fw-medium d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#importPinjamPakaiModal">
                <i class="bi bi-file-earmark-excel-fill"></i> AI Smart Import Excel
            </button>
            <a href="{{ route('vehicles.pinjam-pakai.create') }}" class="btn btn-primary shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i> Tambah Perjanjian Pinjam Pakai
            </a>
        </div>
    </div>

    <!-- STATS SUMMARY CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-secondary fw-semibold text-uppercase" style="font-size: 0.72rem;">Total Perjanjian</small>
                        <h3 class="fw-bold text-navy mb-0 mt-1">{{ number_format($totalPinjamPakai) }}</h3>
                    </div>
                    <div class="rounded-circle p-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-journal-text fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-secondary fw-semibold text-uppercase" style="font-size: 0.72rem;">Status Aktif</small>
                        <h3 class="fw-bold text-success mb-0 mt-1">{{ number_format($totalAktif) }}</h3>
                    </div>
                    <div class="rounded-circle p-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-shield-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-secondary fw-semibold text-uppercase" style="font-size: 0.72rem;">Akan Jatuh Tempo (≤30 Hari)</small>
                        <h3 class="fw-bold text-warning mb-0 mt-1">{{ number_format($totalJatuhTempo) }}</h3>
                    </div>
                    <div class="rounded-circle p-3 bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-clock-history fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-secondary fw-semibold text-uppercase" style="font-size: 0.72rem;">Selesai / Dikembalikan</small>
                        <h3 class="fw-bold text-secondary mb-0 mt-1">{{ number_format($totalSelesai) }}</h3>
                    </div>
                    <div class="rounded-circle p-3 bg-secondary bg-opacity-10 text-secondary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-check2-circle fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN TABLE SECTION -->
    <x-table-card 
        :empty="$pinjamPakais->isEmpty()" 
        :collection="$pinjamPakais"
        emptyText="Belum ada data kendaraan pinjam pakai" 
        emptyIcon="bi-car-front">
        
        <x-slot:filters>
            <form action="{{ route('vehicles.pinjam-pakai.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control border-start-0 bg-white shadow-none" placeholder="Cari nopol, merk, instansi, NPPP...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="kategori" class="form-select form-select-sm bg-white searchable-select">
                        <option value="">-- Semua Kategori Peminjam --</option>
                        <option value="Instansi Vertikal" {{ request('kategori') === 'Instansi Vertikal' ? 'selected' : '' }}>Instansi Vertikal (Kejaksaan/Polres/dll)</option>
                        <option value="Antar OPD" {{ request('kategori') === 'Antar OPD' ? 'selected' : '' }}>Antar OPD Pemda</option>
                        <option value="Lainnya" {{ request('kategori') === 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm bg-white searchable-select">
                        <option value="">-- Semua Status --</option>
                        <option value="Aktif" {{ request('status') === 'Aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="jatuh_tempo" {{ request('status') === 'jatuh_tempo' ? 'selected' : '' }}>Jatuh Tempo (≤30 Hari)</option>
                        <option value="Selesai / Dikembalikan" {{ request('status') === 'Selesai / Dikembalikan' ? 'selected' : '' }}>Selesai / Dikembalikan</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-medium">Filter</button>
                    <a href="{{ route('vehicles.pinjam-pakai.index') }}" class="btn btn-light border btn-sm bg-white" title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
                </div>
            </form>
        </x-slot:filters>

        <x-slot:thead>
            <tr>
                <th class="py-3 px-3 border-bottom-0 fw-semibold text-center" style="width: 50px;">NO</th>
                <th class="py-3 px-3 border-bottom-0 fw-semibold" style="width: 25%;">Kendaraan Dinas</th>
                <th class="py-3 px-3 border-bottom-0 fw-semibold" style="width: 22%;">Instansi & Penanggung Jawab</th>
                <th class="py-3 px-3 border-bottom-0 fw-semibold" style="width: 20%;">Perjanjian NPPP / BAST</th>
                <th class="py-3 px-3 border-bottom-0 fw-semibold text-center" style="width: 15%;">Masa Berlaku</th>
                <th class="py-3 px-3 border-bottom-0 fw-semibold text-center" style="width: 12%;">Status</th>
                <th class="py-3 px-3 border-bottom-0 fw-semibold text-center" style="width: 80px;">Aksi</th>
            </tr>
        </x-slot:thead>

        @foreach($pinjamPakais as $index => $item)
            <tr>
                <td class="px-3 py-3 text-center text-secondary small font-monospace fw-medium">
                    {{ ($pinjamPakais->currentPage() - 1) * $pinjamPakais->perPage() + $loop->iteration }}
                </td>
                <td class="py-3 px-3">
                    @if($item->vehicle)
                        <div class="d-flex align-items-center gap-2">
                            <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3 text-center" style="min-width: 36px;">
                                <i class="bi bi-car-front-fill fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-navy mb-0.5" style="font-size: 0.9rem;">
                                    {{ $item->vehicle->no_polisi ?? 'Tanpa Nopol' }}
                                </div>
                                <div class="text-secondary small" style="font-size: 0.78rem;">
                                    {{ $item->vehicle->merk_tipe ?? 'Kendaraan' }} @if($item->vehicle->tahun_pembuatan) ({{ $item->vehicle->tahun_pembuatan }}) @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <span class="text-muted italic small"><i class="bi bi-exclamation-circle me-1"></i> Data Kendaraan Terhapus</span>
                    @endif
                </td>
                <td class="py-3 px-3">
                    <div class="fw-bold text-navy" style="font-size: 0.88rem;">{{ $item->nama_instansi_peminjam }}</div>
                    <div class="small text-secondary mt-0.5">
                        <i class="bi bi-person me-1"></i> {{ $item->nama_penanggung_jawab }}
                        @if($item->kontak_penanggung_jawab)
                            <span class="text-muted">({{ $item->kontak_penanggung_jawab }})</span>
                        @endif
                    </div>
                    <span class="badge bg-light text-dark border rounded-pill mt-1" style="font-size: 0.68rem;">{{ $item->kategori_peminjam }}</span>
                </td>
                <td class="py-3 px-3">
                    <div class="fw-bold text-dark small">{{ $item->nomor_nppp_bast }}</div>
                    <div class="text-secondary small mt-0.5" style="font-size: 0.75rem;">
                        <i class="bi bi-calendar-event me-1"></i> Tgl NPPP: {{ $item->tanggal_nppp_bast ? $item->tanggal_nppp_bast->format('d M Y') : '-' }}
                    </div>
                    @if($item->file_dokumen_pdf)
                        <a href="{{ $item->dokumen_url }}" target="_blank" class="btn btn-xs btn-outline-danger py-0 px-2 rounded-pill mt-1 d-inline-flex align-items-center gap-1" style="font-size: 0.7rem;">
                            <i class="bi bi-file-earmark-pdf-fill"></i> Dokumen PDF
                        </a>
                    @endif
                </td>
                <td class="py-3 px-3 text-center">
                    <div class="small fw-semibold text-navy">
                        {{ $item->tanggal_mulai ? $item->tanggal_mulai->format('d M Y') : '-' }}
                    </div>
                    <div class="text-muted small" style="font-size: 0.7rem;">s/d</div>
                    <div class="small fw-semibold text-danger">
                        {{ $item->tanggal_selesai ? $item->tanggal_selesai->format('d M Y') : '-' }}
                    </div>
                </td>
                <td class="py-3 px-3 text-center">
                    {!! $item->status_badge !!}
                </td>
                <td class="py-3 px-3 text-center">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light border shadow-sm rounded-circle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-3 py-1">
                            <li>
                                <a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2" href="{{ route('vehicles.pinjam-pakai.show', $item->id) }}">
                                    <i class="bi bi-eye text-primary"></i> Detail Perjanjian
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2" href="{{ route('vehicles.pinjam-pakai.edit', $item->id) }}">
                                    <i class="bi bi-pencil-square text-warning"></i> Edit Data
                                </a>
                            </li>
                            @if($item->status_perjanjian === 'Aktif')
                            <li>
                                <form action="{{ route('vehicles.pinjam-pakai.kembalikan', $item->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 text-success fw-semibold" onclick="return confirm('Apakah Anda yakin kendaraan ini telah dikembalikan?')">
                                        <i class="bi bi-box-arrow-in-left text-success"></i> Pengembalian Aset
                                    </button>
                                </form>
                            </li>
                            @endif
                            <li><hr class="dropdown-divider my-1 opacity-25"></li>
                            <li>
                                <form action="{{ route('vehicles.pinjam-pakai.destroy', $item->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item py-2 px-3 small d-flex align-items-center gap-2 text-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus data perjanjian pinjam pakai ini?')">
                                        <i class="bi bi-trash text-danger"></i> Hapus Data
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        @endforeach

    </x-table-card>

</div>

<!-- MODAL AI SMART SEMANTIC IMPORT EXCEL -->
<div class="modal fade" id="importPinjamPakaiModal" tabindex="-1" aria-labelledby="importPinjamPakaiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom px-4 py-3 bg-light">
                <div>
                    <h5 class="modal-title fw-bold text-navy d-flex align-items-center gap-2" id="importPinjamPakaiModalLabel">
                        <i class="bi bi-file-earmark-excel-fill text-success fs-4"></i> AI Smart Import Data Pinjam Pakai
                    </h5>
                    <p class="text-secondary small mb-0">Impor data perjanjian NPPP / BAST secara otomatis dari berkas Excel / CSV.</p>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('vehicles.pinjam-pakai.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4 bg-white">
                    <div class="alert alert-info border-0 rounded-3 small mb-3">
                        <div class="fw-bold mb-1"><i class="bi bi-magic me-1"></i> AI Smart Semantic Header Matching:</div>
                        <ul class="mb-0 ps-3">
                            <li>Kunci kolom Excel dikenali secara fleksibel: <code>No. Polisi</code>, <code>Instansi Peminjam</code>, <code>Penanggung Jawab</code>, <code>NPPP / BAST</code>, <code>Tanggal Mulai</code>, <code>Tanggal Selesai</code>.</li>
                            <li>Aplikasi otomatis memadankan kendaraan dinas di database berdasarkan plat nomor polisi.</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <label for="import_file" class="form-label fw-semibold small">Pilih Berkas Excel / CSV <span class="text-danger">*</span></label>
                        <input type="file" name="import_file" id="import_file" accept=".xlsx,.xls,.csv" class="form-control" required>
                        <small class="text-muted d-block mt-1">Format yang didukung: <code>.xlsx</code>, <code>.xls</code>, <code>.csv</code> (Maks. 10MB)</small>
                    </div>

                    <div class="p-3 bg-light rounded-3 d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-bold text-navy small">Belum punya format Excel?</div>
                            <small class="text-secondary">Unduh format sampel yang disiapkan sistem.</small>
                        </div>
                        <a href="{{ route('vehicles.pinjam-pakai.import.template') }}" class="btn btn-sm btn-outline-success rounded-pill fw-semibold">
                            <i class="bi bi-download me-1"></i> Unduh Format
                        </a>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light px-4 py-3">
                    <button type="button" class="btn btn-light border fw-medium" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success fw-bold px-4">
                        <i class="bi bi-upload me-1"></i> Proses Impor Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
