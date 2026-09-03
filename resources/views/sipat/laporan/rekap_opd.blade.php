@extends('layouts.app')

@section('content')
<style>
    .report-tabs .nav-link {
        font-size: 0.92rem;
        font-weight: 600;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 0.75rem 1.25rem;
        color: var(--bs-secondary-color, #64748b);
        background: transparent;
        transition: all 0.2s ease;
    }
    .report-tabs .nav-link:hover {
        color: #1e40af;
    }
    .report-tabs .nav-link.active {
        color: #1e40af;
        border-bottom-color: #1e40af;
        background: transparent;
    }
    .stat-mini-card {
        border: 1px solid var(--border-color, rgba(0, 0, 0, 0.08));
        border-radius: 1rem;
        background: var(--bs-card-bg, #ffffff);
        padding: 1.1rem 1.25rem;
        position: relative;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .stat-mini-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
    }
    .stat-mini-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--bs-secondary-color, #64748b);
        margin-bottom: 0.35rem;
    }
    .stat-mini-val {
        font-size: 1.45rem;
        font-weight: 800;
        line-height: 1.2;
    }
    .table-rekap-opd th {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        vertical-align: middle;
        text-align: center;
    }
    .table-rekap-opd td {
        font-size: 0.86rem;
        vertical-align: middle;
    }
</style>

<div class="container-fluid px-0">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary fw-semibold px-2.5 py-1 rounded-pill" style="font-size: 0.75rem;">
                    <i class="bi bi-file-earmark-bar-graph me-1"></i> MODUL LAPORAN SIPAT
                </span>
                <span class="text-secondary small">&bull;</span>
                <span class="text-secondary small">Rekapitulasi Agregat Organisasi Perangkat Daerah</span>
            </div>
            <h2 class="fw-bold mb-1">Rekapitulasi Pensertifikatan per OPD</h2>
            <p class="text-secondary mb-0 small">Ringkasan kepemilikan dan progres status legalitas BPN aset tanah Pemerintah Kabupaten Donggala berdasarkan OPD pengguna barang</p>
        </div>

        <!-- Tombol Aksi Ekspor & Cetak -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('sipat.laporan.rekapOpd.exportXlsx', request()->all()) }}" class="btn btn-outline-success d-flex align-items-center gap-2 rounded-3 shadow-sm px-3 py-2">
                <i class="bi bi-file-earmark-excel"></i> <span class="fw-semibold">Ekspor Excel</span>
            </a>
            <a href="{{ route('sipat.laporan.rekapOpd.downloadPdf', request()->all()) }}" class="btn btn-outline-danger d-flex align-items-center gap-2 rounded-3 shadow-sm px-3 py-2">
                <i class="bi bi-file-earmark-pdf"></i> <span class="fw-semibold">Unduh PDF</span>
            </a>
            <a href="{{ route('sipat.laporan.rekapOpd.print', request()->all()) }}" target="_blank" class="btn btn-primary d-flex align-items-center gap-2 rounded-3 shadow-sm px-3 py-2">
                <i class="bi bi-printer"></i> <span class="fw-semibold">Cetak Dokumen</span>
            </a>
        </div>
    </div>

    <!-- Navigasi Tab Laporan -->
    <div class="border-bottom mb-4">
        <ul class="nav nav-tabs report-tabs border-bottom-0">
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('sipat.laporan.rekapOpd') }}">
                    <i class="bi bi-building me-1.5"></i>Rekapitulasi per OPD
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('sipat.laporan.index') }}">
                    <i class="bi bi-list-columns-reverse me-1.5"></i>Rincian Daftar Aset KIB A
                </a>
            </li>
        </ul>
    </div>

    @php
        $gt = $rekapData['grand_total'] ?? [];
    @endphp

    <!-- KPI Metric Summary Grid -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-mini-card">
                <div class="stat-mini-label">Total OPD</div>
                <div class="stat-mini-val text-dark">{{ number_format($rekapData['total_opd'] ?? 0) }}</div>
                <small class="text-secondary" style="font-size: 0.72rem;">Unit Pengelola</small>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-mini-card">
                <div class="stat-mini-label">Total Bidang</div>
                <div class="stat-mini-val text-primary">{{ number_format($gt['total_bidang'] ?? 0) }}</div>
                <small class="text-secondary" style="font-size: 0.72rem;">{{ number_format($gt['total_luas'] ?? 0, 0, ',', '.') }} m²</small>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-mini-card border-success-subtle bg-success bg-opacity-10">
                <div class="stat-mini-label text-success">Bersertifikat</div>
                <div class="stat-mini-val text-success">{{ number_format($gt['sudah_sertifikat'] ?? 0) }}</div>
                <small class="text-success fw-bold" style="font-size: 0.72rem;">{{ $gt['persen_sertifikat'] ?? 0 }}% Terpenuhi</small>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-mini-card border-warning-subtle bg-warning bg-opacity-10">
                <div class="stat-mini-label text-warning-emphasis">Dalam Proses BPN</div>
                <div class="stat-mini-val text-warning-emphasis">{{ number_format($gt['dalam_proses'] ?? 0) }}</div>
                <small class="text-secondary" style="font-size: 0.72rem;">{{ number_format($gt['luas_proses'] ?? 0, 0, ',', '.') }} m²</small>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-mini-card border-secondary-subtle">
                <div class="stat-mini-label">Belum Diproses</div>
                <div class="stat-mini-val text-secondary">{{ number_format($gt['belum_diproses'] ?? 0) }}</div>
                <small class="text-secondary" style="font-size: 0.72rem;">{{ number_format($gt['luas_belum_diproses'] ?? 0, 0, ',', '.') }} m²</small>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-mini-card border-danger-subtle bg-danger bg-opacity-10">
                <div class="stat-mini-label text-danger">Bermasalah</div>
                <div class="stat-mini-val text-danger">{{ number_format($gt['bermasalah'] ?? 0) }}</div>
                <small class="text-danger" style="font-size: 0.72rem;">Sengketa / Kendala</small>
            </div>
        </div>
    </div>

    <!-- Filter & Pencarian OPD -->
    <div class="card clean-card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('sipat.laporan.rekapOpd') }}" class="row g-2 align-items-center">
                <div class="col-12 col-md-6 col-lg-5">
                    <div class="input-group">
                        <span class="input-group-text bg-body border-end-0 text-secondary"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" class="form-control border-start-0" placeholder="Cari nama OPD pengelola..." value="{{ $filters['q'] ?? '' }}">
                        <button type="submit" class="btn btn-primary px-3">Cari OPD</button>
                    </div>
                </div>
                @if(!empty($filters['q']))
                    <div class="col-auto">
                        <a href="{{ route('sipat.laporan.rekapOpd') }}" class="btn btn-outline-secondary d-flex align-items-center gap-1 rounded-3">
                            <i class="bi bi-x-circle"></i> Reset Filter
                        </a>
                    </div>
                @endif
                <div class="col-12 col-md text-md-end text-secondary small">
                    Menampilkan <strong>{{ count($rekapData['items'] ?? []) }}</strong> Organisasi Perangkat Daerah
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Matriks Rekapitulasi per OPD -->
    <div class="card clean-card border-0 shadow-sm table-container-sipat overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-rekap-opd">
                <thead class="bg-body text-secondary border-bottom">
                    <tr>
                        <th rowspan="2" style="width: 45px;">NO</th>
                        <th rowspan="2" class="text-start" style="min-width: 250px;">ORGANISASI PERANGKAT DAERAH (OPD)</th>
                        <th colspan="2" class="bg-light">TOTAL ASET TANAH</th>
                        <th colspan="2" class="bg-success-subtle text-success">SUDAH BERSERTIFIKAT</th>
                        <th colspan="2" class="bg-warning-subtle text-warning-emphasis">DALAM PROSES BPN</th>
                        <th colspan="2" class="bg-light text-secondary">BELUM DIPROSES</th>
                        <th rowspan="2" class="bg-danger-subtle text-danger" style="width: 80px;">BERMASALAH</th>
                        <th rowspan="2" style="width: 110px;">CAPAIAN (%)</th>
                        <th rowspan="2" class="pe-3" style="width: 100px;">AKSI</th>
                    </tr>
                    <tr>
                        <th class="bg-light border-top" style="width: 75px;">BIDANG</th>
                        <th class="bg-light border-top" style="width: 110px;">LUAS (M²)</th>
                        <th class="bg-success-subtle text-success border-top" style="width: 75px;">BIDANG</th>
                        <th class="bg-success-subtle text-success border-top" style="width: 110px;">LUAS (M²)</th>
                        <th class="bg-warning-subtle text-warning-emphasis border-top" style="width: 75px;">BIDANG</th>
                        <th class="bg-warning-subtle text-warning-emphasis border-top" style="width: 110px;">LUAS (M²)</th>
                        <th class="bg-light text-secondary border-top" style="width: 75px;">BIDANG</th>
                        <th class="bg-light text-secondary border-top" style="width: 110px;">LUAS (M²)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rekapData['items'] as $row)
                        <tr>
                            <td class="text-center fw-medium text-secondary">{{ $row['no'] }}</td>
                            <td>
                                <div class="fw-bold text-body">{{ $row['nama_opd'] }}</div>
                                @if(!empty($row['total_nilai']) && $row['total_nilai'] > 0)
                                    <small class="text-secondary" style="font-size: 0.75rem;">Nilai Perolehan: Rp {{ number_format($row['total_nilai'], 0, ',', '.') }}</small>
                                @endif
                            </td>
                            <td class="text-end fw-bold text-dark">{{ number_format($row['total_bidang']) }}</td>
                            <td class="text-end font-monospace text-secondary">{{ number_format($row['total_luas'], 0, ',', '.') }}</td>

                            <!-- Sudah Bersertifikat -->
                            <td class="text-end fw-bold text-success bg-success bg-opacity-10">{{ number_format($row['sudah_sertifikat']) }}</td>
                            <td class="text-end font-monospace text-success bg-success bg-opacity-10">{{ number_format($row['luas_sertifikat'], 0, ',', '.') }}</td>

                            <!-- Dalam Proses BPN -->
                            <td class="text-end fw-bold text-warning-emphasis bg-warning bg-opacity-10">{{ number_format($row['dalam_proses']) }}</td>
                            <td class="text-end font-monospace text-warning-emphasis bg-warning bg-opacity-10">{{ number_format($row['luas_proses'], 0, ',', '.') }}</td>

                            <!-- Belum Diproses -->
                            <td class="text-end fw-medium text-secondary">{{ number_format($row['belum_diproses']) }}</td>
                            <td class="text-end font-monospace text-secondary">{{ number_format($row['luas_belum_diproses'], 0, ',', '.') }}</td>

                            <!-- Bermasalah -->
                            <td class="text-end fw-medium text-danger bg-danger bg-opacity-10">{{ number_format($row['bermasalah']) }}</td>

                            <!-- Persentase Capaian -->
                            <td class="text-center">
                                @php
                                    $pct = $row['persen_sertifikat'];
                                    $badge = 'bg-danger-subtle text-danger';
                                    if ($pct >= 75) {
                                        $badge = 'bg-success-subtle text-success border border-success-subtle';
                                    } elseif ($pct >= 40) {
                                        $badge = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                                    }
                                @endphp
                                <span class="badge {{ $badge }} rounded-pill px-2 py-1 font-monospace" style="font-size: 0.78rem;">
                                    {{ $pct }}%
                                </span>
                            </td>

                            <!-- Aksi Filter Rincian -->
                            <td class="text-center pe-3">
                                @if(!empty($row['opd_id']))
                                    <a href="{{ route('sipat.aset.index', ['opd_id' => $row['opd_id']]) }}" class="btn btn-xs btn-outline-primary rounded-pill px-2.5 py-1" style="font-size: 0.75rem;" title="Lihat daftar aset tanah OPD ini">
                                        <i class="bi bi-arrow-right-circle me-1"></i>Aset
                                    </a>
                                @else
                                    <a href="{{ route('sipat.aset.index', ['opd_id' => 'KOSONG']) }}" class="btn btn-xs btn-outline-secondary rounded-pill px-2.5 py-1" style="font-size: 0.75rem;" title="Lihat aset tanpa OPD">
                                        <i class="bi bi-arrow-right-circle me-1"></i>Aset
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center py-5 text-secondary">
                                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                Tidak ada data rekapitulasi OPD yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <!-- GRAND TOTAL FOOTER -->
                <tfoot class="border-top-2 border-dark" style="border-top: 2px solid #1e293b;">
                    <tr class="bg-body-secondary fw-bold" style="font-size: 0.9rem;">
                        <td colspan="2" class="text-center py-3">TOTAL KABUPATEN DONGGALA</td>
                        <td class="text-end py-3 text-dark">{{ number_format($gt['total_bidang'] ?? 0) }}</td>
                        <td class="text-end py-3 font-monospace">{{ number_format($gt['total_luas'] ?? 0, 0, ',', '.') }}</td>
                        
                        <td class="text-end py-3 text-success bg-success bg-opacity-10">{{ number_format($gt['sudah_sertifikat'] ?? 0) }}</td>
                        <td class="text-end py-3 font-monospace text-success bg-success bg-opacity-10">{{ number_format($gt['luas_sertifikat'] ?? 0, 0, ',', '.') }}</td>
                        
                        <td class="text-end py-3 text-warning-emphasis bg-warning bg-opacity-10">{{ number_format($gt['dalam_proses'] ?? 0) }}</td>
                        <td class="text-end py-3 font-monospace text-warning-emphasis bg-warning bg-opacity-10">{{ number_format($gt['luas_proses'] ?? 0, 0, ',', '.') }}</td>
                        
                        <td class="text-end py-3 text-secondary">{{ number_format($gt['belum_diproses'] ?? 0) }}</td>
                        <td class="text-end py-3 font-monospace text-secondary">{{ number_format($gt['luas_belum_diproses'] ?? 0, 0, ',', '.') }}</td>
                        
                        <td class="text-end py-3 text-danger bg-danger bg-opacity-10">{{ number_format($gt['bermasalah'] ?? 0) }}</td>
                        
                        <td class="text-center py-3">
                            <span class="badge bg-primary text-white rounded-pill px-2.5 py-1 font-monospace" style="font-size: 0.82rem;">
                                {{ $gt['persen_sertifikat'] ?? 0 }}%
                            </span>
                        </td>
                        <td class="pe-3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
