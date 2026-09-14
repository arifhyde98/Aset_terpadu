@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-lg-4 py-3">
    <!-- Header Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 text-muted small">
                    <li class="breadcrumb-item"><a href="{{ route('landing') }}" class="text-decoration-none">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('bangunan.index') }}" class="text-decoration-none">KIB C - Bangunan</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Pusat Laporan</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-pdf text-danger"></i> Pusat Laporan KIB C (Gedung &amp; Bangunan)
            </h3>
            <p class="text-muted small mb-0">Format resmi cetak buku inventaris KIB C standar Permendagri No. 19/2016 dan No. 7/2006.</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('bangunan.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Katalog
            </a>
            <a href="{{ route('bangunan.laporan.pdf', request()->query()) }}" target="_blank" class="btn btn-danger rounded-pill px-3 shadow-sm">
                <i class="bi bi-printer me-1"></i> Cetak Laporan PDF (mPDF)
            </a>
        </div>
    </div>

    <!-- Filter & Action Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('bangunan.laporan.index') }}" method="GET" class="row g-2 align-items-center">
                @if(auth()->user()->role !== \App\Enums\UserRole::OPD)
                <div class="col-12 col-md-4">
                    <select name="opd_id" class="form-select form-select-sm bg-light">
                        <option value="">-- Rekap Seluruh OPD / Gabungan --</option>
                        @foreach($opds as $opd)
                            <option value="{{ $opd->id }}" {{ request('opd_id') == $opd->id ? 'selected' : '' }}>
                                {{ $opd->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-6 col-md-3">
                    <select name="kondisi" class="form-select form-select-sm bg-light">
                        <option value="">-- Semua Kondisi Fisik --</option>
                        <option value="Baik" {{ request('kondisi') === 'Baik' ? 'selected' : '' }}>Baik (B)</option>
                        <option value="Kurang Baik" {{ request('kondisi') === 'Kurang Baik' ? 'selected' : '' }}>Kurang Baik (KB)</option>
                        <option value="Rusak Berat" {{ request('kondisi') === 'Rusak Berat' ? 'selected' : '' }}>Rusak Berat (RB)</option>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <input type="number" name="tahun" class="form-control form-select-sm bg-light" 
                           placeholder="Tahun Perolehan" value="{{ request('tahun') }}">
                </div>

                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill w-100">
                        <i class="bi bi-filter me-1"></i> Tampilkan
                    </button>
                    @if(!empty(array_filter(request()->query())))
                        <a href="{{ route('bangunan.laporan.index') }}" class="btn btn-sm btn-light rounded-pill" title="Reset">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Box -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="p-3 bg-white rounded-4 shadow-sm border-start border-4 border-primary">
                <span class="text-muted small d-block">Total Aset Gedung</span>
                <h4 class="fw-bold text-dark mb-0">{{ number_format($totalUnit, 0, ',', '.') }} Unit</h4>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="p-3 bg-white rounded-4 shadow-sm border-start border-4 border-success">
                <span class="text-muted small d-block">Akumulasi Nilai Perolehan</span>
                <h4 class="fw-bold text-success mb-0">Rp {{ number_format($totalNilai, 0, ',', '.') }}</h4>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="p-3 bg-white rounded-4 shadow-sm border-start border-4 border-info">
                <span class="text-muted small d-block">Akumulasi Luas Lantai</span>
                <h4 class="fw-bold text-info mb-0">{{ number_format($totalLuas, 2, ',', '.') }} m²</h4>
            </div>
        </div>
    </div>

    <!-- Preview Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0">Pratinjau Data Laporan KIB C</h6>
            <span class="text-muted small">Menampilkan {{ $bangunans->count() }} data</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle mb-0" style="font-size: 0.8rem;">
                <thead class="table-light text-center">
                    <tr>
                        <th rowspan="2" style="width: 40px;">No</th>
                        <th rowspan="2">Nama Bangunan</th>
                        <th rowspan="2">Kode Barang</th>
                        <th rowspan="2">No. Reg</th>
                        <th rowspan="2">Kondisi</th>
                        <th colspan="2">Konstruksi</th>
                        <th rowspan="2">Luas Lantai (m²)</th>
                        <th colspan="2">Dokumen PBG/IMB</th>
                        <th rowspan="2">Tanah Dasar</th>
                        <th rowspan="2">Asal Usul</th>
                        <th rowspan="2">Tahun</th>
                        <th rowspan="2">Harga Perolehan (Rp)</th>
                    </tr>
                    <tr>
                        <th>Tingkat</th>
                        <th>Beton</th>
                        <th>Tanggal</th>
                        <th>Nomor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bangunans as $idx => $item)
                        <tr>
                            <td class="text-center">{{ $bangunans->firstItem() + $idx }}</td>
                            <td class="fw-medium">{{ $item->nama_bangunan }}</td>
                            <td class="text-center font-monospace">{{ $item->kode_barang ?: '-' }}</td>
                            <td class="text-center font-monospace">{{ $item->nomor_register ?: '-' }}</td>
                            <td class="text-center">
                                <span class="badge {{ is_object($item->kondisi) ? $item->kondisi->badgeClass() : 'bg-secondary' }}">
                                    {{ is_object($item->kondisi) ? $item->kondisi->value : $item->kondisi }}
                                </span>
                            </td>
                            <td class="text-center">{{ $item->konstruksi_tingkat ? 'Tingkat' : 'Tidak' }}</td>
                            <td class="text-center">{{ $item->konstruksi_beton ? 'Beton' : 'Bukan' }}</td>
                            <td class="text-end">{{ number_format($item->luas_lantai, 2, ',', '.') }}</td>
                            <td class="text-center">{{ $item->tanggal_dokumen_pbg ? $item->tanggal_dokumen_pbg->format('d/m/Y') : '-' }}</td>
                            <td>{{ $item->nomor_dokumen_pbg ?: '-' }}</td>
                            <td>{{ $item->asetTanah?->kode_aset ?? ($item->status_tanah_dasar ?: '-') }}</td>
                            <td class="text-center">{{ $item->asal_usul ?: 'APBD' }}</td>
                            <td class="text-center">{{ $item->tahun_pengadaan ?: '-' }}</td>
                            <td class="text-end fw-bold">Rp {{ number_format($item->harga_perolehan, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="text-center py-4 text-muted">
                                Tidak ada data yang sesuai dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($bangunans->isNotEmpty())
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="7" class="text-center">TOTAL AKUMULASI</td>
                            <td class="text-end">{{ number_format($totalLuas, 2, ',', '.') }} m²</td>
                            <td colspan="5"></td>
                            <td class="text-end">Rp {{ number_format($totalNilai, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        @if($bangunans->hasPages())
            <div class="card-footer bg-white border-0 py-3 px-4">
                {{ $bangunans->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
