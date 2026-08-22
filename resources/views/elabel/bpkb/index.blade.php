@extends('layouts.app')

@section('title', 'Katalog BPKB Kendaraan - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Katalog BPKB</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0">Katalog BPKB Kendaraan ({{ $vehicleLabel }})</h4>
        </div>
        <div class="action-toolbar d-flex flex-wrap gap-2">
            <a href="{{ route('elabel.bpkb.export', ['type' => request('type')]) }}" class="btn btn-outline-success shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </a>
            <button type="button" class="btn btn-outline-primary shadow-sm fw-medium d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-file-earmark-arrow-up"></i> Import Excel
            </button>
            <a href="{{ route('elabel.bpkb.create', ['type' => request('type')]) }}" class="btn btn-primary shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i> Tambah BPKB
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- CATEGORY FILTER TABS -->
    <ul class="nav nav-tabs nav-fill mb-4 border-bottom" role="tablist">
        <li class="nav-item">
            <a href="{{ route('elabel.bpkb.index') }}" class="nav-link fw-bold py-3 d-flex align-items-center justify-content-center gap-2 {{ !$vehicleType ? 'active text-navy border-bottom border-primary border-3' : 'text-secondary' }}">
                <i class="bi bi-collection-fill"></i> Semua BPKB
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('elabel.bpkb.index', ['type' => 'r4']) }}" class="nav-link fw-bold py-3 d-flex align-items-center justify-content-center gap-2 {{ $vehicleType === 'R4' ? 'active text-navy border-bottom border-primary border-3' : 'text-secondary' }}">
                <i class="bi bi-car-front-fill text-primary"></i> R4 (Mobil)
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('elabel.bpkb.index', ['type' => 'r2']) }}" class="nav-link fw-bold py-3 d-flex align-items-center justify-content-center gap-2 {{ $vehicleType === 'R2' ? 'active text-navy border-bottom border-primary border-3' : 'text-secondary' }}">
                <i class="bi bi-bicycle text-success"></i> R2 (Motor)
            </a>
        </li>
    </ul>

    <!-- SEARCH & TABLE CARD -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <form action="{{ route('elabel.bpkb.index') }}" method="GET" class="row g-2 align-items-center">
                @if(request('type'))
                    <input type="hidden" name="type" value="{{ request('type') }}">
                @endif
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control border-start-0 shadow-none" placeholder="Cari nomor polisi, nomor BPKB, NIBAR, rangka, mesin, atau kode box...">
                    </div>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-medium">Cari</button>
                    <a href="{{ route('elabel.bpkb.index', ['type' => request('type')]) }}" class="btn btn-light border bg-white" title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4 text-center" style="width: 50px;">No.</th>
                        <th class="py-3">No. Polisi / Tahun</th>
                        <th class="py-3">Identitas Dokumen (BPKB/NIBAR)</th>
                        <th class="py-3">Spesifikasi (Merk/Tipe/Warna)</th>
                        <th class="py-3">Pemegang / Dinas</th>
                        <th class="py-3 text-center">Box Fisik</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3 px-4 text-center" style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td class="px-4 text-center fw-medium text-secondary">{{ $loop->iteration }}</td>
                            <td>
                                <span class="badge bg-light text-dark border px-3 py-2 fs-6 rounded-3 fw-bold">{{ $item->plate_number }}</span>
                                <div class="small text-secondary mt-1"><i class="bi bi-calendar3 me-1"></i> Tahun {{ $item->year ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold text-navy"><i class="bi bi-file-earmark-text me-1 text-primary"></i> {{ $item->no_bpkb ?: '-' }}</div>
                                <div class="small text-secondary">NIBAR: {{ $item->nibar ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="fw-medium text-dark">{{ $item->merek ?: '-' }} {{ $item->tipe ?: '' }}</div>
                                <div class="small text-secondary">{{ $item->isi_silinder ?: '-' }} · {{ $item->warna ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><i class="bi bi-person-fill text-secondary me-1"></i> {{ $item->pengguna ?: '-' }}</div>
                                <div class="small text-secondary"><i class="bi bi-building me-1"></i> {{ $item->opdSipat ? $item->opdSipat->nama : '-' }}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-3 fw-bold">
                                    <i class="bi bi-archive me-1"></i> {{ $item->box->box_code ?? '-' }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($item->status === 'Tersedia')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-1 rounded-pill fw-medium">Tersedia</span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning text-dark border border-warning border-opacity-25 px-3 py-1 rounded-pill fw-medium">{{ $item->status }}</span>
                                @endif
                            </td>
                            <td class="px-4 text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    @if($item->pdf_path)
                                        <a href="{{ route('elabel.bpkb.view-pdf', $item->id) }}" target="_blank" class="btn btn-sm btn-light border text-danger" title="Lihat Scan PDF">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('elabel.bpkb.show', $item->id) }}" class="btn btn-sm btn-light border text-navy" title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('elabel.bpkb.edit', $item->id) }}" class="btn btn-sm btn-light border text-primary" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-light border text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $item->id }}" title="Keluarkan BPKB">
                                        <i class="bi bi-box-arrow-right"></i>
                                    </button>
                                </div>

                                <!-- DELETE/OUT MODAL FOR EACH BPKB -->
                                <div class="modal fade" id="deleteModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content rounded-4 border-0 shadow">
                                            <form action="{{ route('elabel.bpkb.delete', $item->id) }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <div class="modal-header border-bottom px-4 py-3">
                                                    <h5 class="modal-title fw-bold text-navy"><i class="bi bi-box-arrow-right text-danger me-2"></i> Memindahkan BPKB {{ $item->plate_number }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4 text-start">
                                                    <div class="alert alert-warning border-0 bg-warning bg-opacity-10 text-dark small mb-3">
                                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Data BPKB akan dipindahkan ke daftar <strong>BPKB Keluar</strong>.
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold small">Alasan Penghapusan/Keluar <span class="text-danger">*</span></label>
                                                        <select name="reason" class="form-select" required>
                                                            <option value="Di pinjam">Di pinjam</option>
                                                            <option value="Penjualan">Penjualan</option>
                                                            <option value="Dihibahkan">Dihibahkan</option>
                                                            <option value="Kendaraan hilang">Kendaraan hilang</option>
                                                            <option value="Kendaraan tidak ditemukan">Kendaraan tidak ditemukan</option>
                                                            <option value="Lainnya">Lainnya</option>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold small">Keterangan Tambahan</label>
                                                        <textarea name="reason_detail" class="form-control" rows="2" placeholder="Catatan opsional..."></textarea>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold small">Dokumen Pendukung (PDF/JPG/PNG Max 5MB)</label>
                                                        <input type="file" name="support_doc" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold small text-danger">Konfirmasi Password Login Anda <span class="text-danger">*</span></label>
                                                        <input type="password" name="delete_password" class="form-control" placeholder="Masukkan password anda" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4">
                                                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-danger fw-semibold">Pindahkan ke BPKB Keluar</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-secondary">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i> Belum ada data BPKB terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- IMPORT MODAL -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('elabel.bpkb.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="vehicle_type_context" value="{{ request('type') }}">
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-navy"><i class="bi bi-file-earmark-arrow-up text-primary me-2"></i> Import Data BPKB</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="border rounded-3 p-3 bg-light mb-3">
                        <div class="fw-bold text-dark mb-1">Download Format Import</div>
                        <p class="small text-secondary mb-2">Gunakan format Excel standar untuk mengunggah banyak data sekaligus.</p>
                        <a href="{{ route('elabel.bpkb.template', ['type' => request('type')]) }}" class="btn btn-sm btn-outline-primary fw-medium">
                            <i class="bi bi-download me-1"></i> Download Format XLSX
                        </a>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Pilih File Excel (XLSX, XLS, CSV)</label>
                        <input type="file" name="import_file" class="form-control" accept=".xlsx, .xls, .csv" required>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Proses Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
