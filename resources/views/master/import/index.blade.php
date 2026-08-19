@extends('layouts.app')

@section('content')
<style>
    .import-card {
        border-radius: 1.25rem;
        border: 1px solid var(--border-color, rgba(0,0,0,0.08));
        background: var(--bs-card-bg, #ffffff);
    }
    .nav-tabs-custom {
        border-bottom: 2px solid var(--border-color, rgba(0,0,0,0.08));
        gap: 0.5rem;
    }
    .nav-tabs-custom .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        color: var(--bs-secondary-color, #6c757d);
        font-weight: 600;
        padding: 0.85rem 1.35rem;
        border-radius: 0.5rem 0.5rem 0 0;
        transition: all 0.2s ease;
    }
    .nav-tabs-custom .nav-link:hover {
        color: var(--bs-primary, #0d6efd);
        background: rgba(13, 110, 253, 0.05);
    }
    .nav-tabs-custom .nav-link.active {
        color: var(--bs-primary, #0d6efd);
        border-bottom-color: var(--bs-primary, #0d6efd);
        background: transparent;
    }
    .upload-box {
        border: 2px dashed rgba(13, 110, 253, 0.3);
        border-radius: 1rem;
        padding: 1.75rem;
        text-align: center;
        background: rgba(13, 110, 253, 0.02);
        transition: border-color 0.2s ease, background-color 0.2s ease;
    }
    .upload-box:hover {
        border-color: var(--bs-primary, #0d6efd);
        background: rgba(13, 110, 253, 0.05);
    }
</style>

<div class="container-fluid px-0">
    <!-- Header Page -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary fw-semibold px-2.5 py-1 rounded-pill" style="font-size: 0.75rem;">
                    <i class="bi bi-shield-check me-1"></i> MASTER & SISTEM
                </span>
                <span class="text-secondary small">&bull;</span>
                <span class="text-secondary small">Integrasi Data SIPAT</span>
            </div>
            <h2 class="fw-bold mb-1">Master Import SIPAT</h2>
            <p class="text-secondary mb-0 small">Fasilitas unggah massal Import Status Proses SIPAT dan Import Aset Tanah SIPAT.</p>
        </div>
        <a href="{{ route('sipat.aset.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Aset
        </a>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert">
            <i class="bi bi-exclamation-octagon-fill me-2"></i> <strong>Terdapat kesalahan:</strong>
            <ul class="mb-0 mt-1 small">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @php
        $activeTab = session('active_tab', 'status');
    @endphp

    <!-- Card Principal -->
    <div class="card import-card shadow-sm">
        <div class="card-header bg-transparent border-bottom px-4 pt-3 pb-0">
            <ul class="nav nav-tabs nav-tabs-custom card-header-tabs" id="importTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'status' ? 'active' : '' }}" id="tab-status-tab" data-bs-toggle="tab" data-bs-target="#tab-status" type="button" role="tab" aria-controls="tab-status" aria-selected="{{ $activeTab === 'status' ? 'true' : 'false' }}">
                        <i class="bi bi-layers me-2 text-primary"></i> Import Status Proses SIPAT
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'aset' ? 'active' : '' }}" id="tab-aset-tab" data-bs-toggle="tab" data-bs-target="#tab-aset" type="button" role="tab" aria-controls="tab-aset" aria-selected="{{ $activeTab === 'aset' ? 'true' : 'false' }}">
                        <i class="bi bi-file-earmark-plus me-2 text-success"></i> Import Aset Tanah SIPAT
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content" id="importTabContent">

                <!-- ── TAB 1: Import Status Proses SIPAT ── -->
                <div class="tab-pane fade {{ $activeTab === 'status' ? 'show active' : '' }}" id="tab-status" role="tabpanel" aria-labelledby="tab-status-tab">
                    <div class="row g-4 align-items-start">
                        <div class="col-lg-7">
                            <h5 class="fw-bold text-body mb-2">Import Status Proses SIPAT</h5>
                            <p class="text-secondary small mb-3">
                                Unggah file Excel (<code>.xlsx</code>) atau CSV berisi daftar <strong>NIBAR (Kode Aset)</strong> dan <strong>Status Proses</strong> untuk memperbarui riwayat status proses pensertifikatan aset secara otomatis.
                            </p>

                            <!-- Pratinjau Format Header Kolom Excel -->
                            <div class="card bg-body-tertiary border-0 rounded-3 p-3 mb-4">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                    <span class="fw-semibold text-body small"><i class="bi bi-file-earmark-spreadsheet text-success me-1"></i> Format Header Kolom Excel:</span>
                                    <a href="{{ route('master.import.template-status') }}" class="btn btn-sm btn-outline-primary rounded-pill">
                                        <i class="bi bi-download me-1"></i> Download Template CSV/Excel
                                    </a>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered bg-white text-center align-middle mb-0" style="font-size: 0.78rem;">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-primary">nibar <span class="text-danger">*</span></th>
                                                <th class="text-primary">status_proses <span class="text-danger">*</span></th>
                                                <th class="text-secondary">tgl_mulai</th>
                                                <th class="text-secondary">tgl_selesai</th>
                                                <th class="text-secondary">keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="font-monospace">12.01.02.01.001</td>
                                                <td><span class="badge bg-success">Sertifikat</span></td>
                                                <td>2026-08-14</td>
                                                <td>-</td>
                                                <td class="text-muted">Update via Excel</td>
                                            </tr>
                                            <tr>
                                                <td class="font-monospace">12.01.02.01.002</td>
                                                <td><span class="badge bg-warning text-dark">Proses BPN</span></td>
                                                <td>2026-08-14</td>
                                                <td>-</td>
                                                <td class="text-muted">Update via Excel</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <form action="{{ route('master.import.update') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="upload-box mb-3">
                                    <i class="bi bi-cloud-arrow-up display-5 text-primary mb-2 d-block"></i>
                                    <label class="form-label fw-semibold text-body mb-1">Pilih File Excel / CSV (.xlsx, .csv)</label>
                                    <p class="text-secondary small mb-2">Maksimal ukuran file: 10 MB</p>
                                    <input type="file" name="file" class="form-control form-control-sm w-75 mx-auto" accept=".csv,.xlsx,.xls" required>
                                </div>

                                <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                                    <i class="bi bi-cloud-upload me-1"></i> Proses Upload Status Proses
                                </button>
                            </form>
                        </div>

                        <div class="col-lg-5">
                            <div class="alert alert-info border-0 rounded-4 p-3.5 mb-0" style="font-size: 0.85rem;">
                                <h6 class="fw-bold alert-heading mb-2"><i class="bi bi-lightbulb-fill text-warning me-1"></i> Petunjuk Pengisian:</h6>
                                <ul class="mb-0 ps-3">
                                    <li class="mb-2"><strong>Boleh Tanpa Header (Langsung Baris 1):</strong> Kolom A = <code>NIBAR</code>, Kolom B = <code>Status Proses</code>.</li>
                                    <li class="mb-2"><strong>Boleh Dengan Header:</strong> Baris 1 berisi nama kolom <code>nibar</code> (atau <code>kode_aset</code>) dan <code>status_proses</code>.</li>
                                    <li class="mb-2">Nama <code>status_proses</code> harus diisi sesuai Master Status (contoh: <em>Proses Pengukuran, Proses BPN, Sertifikat, Selesai, dll.</em>).</li>
                                    <li>Baris data yang berhasil diunggah akan langsung memperbarui status aset & mencatat audit log sistem.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── TAB 2: Import Aset Tanah SIPAT ── -->
                <div class="tab-pane fade {{ $activeTab === 'aset' ? 'show active' : '' }}" id="tab-aset" role="tabpanel" aria-labelledby="tab-aset-tab">
                    <div class="row g-4 align-items-start">
                        <div class="col-lg-7">
                            <h5 class="fw-bold text-body mb-2">Import Aset Tanah SIPAT</h5>
                            <p class="text-secondary small mb-3">
                                Unggah data aset tanah baru secara massal beserta detail OPD, peruntukan, luas, harga perolehan, dll.
                            </p>

                            <div class="d-flex align-items-center justify-content-between bg-body-tertiary rounded-3 p-3 mb-4 border">
                                <div>
                                    <h6 class="fw-semibold text-body mb-1">Unduh Template Excel Data Aset Tanah</h6>
                                    <span class="text-muted small">Template standar pengisian data aset tanah baru.</span>
                                </div>
                                <a href="{{ route('master.import.template-data') }}" class="btn btn-sm btn-outline-primary rounded-pill">
                                    <i class="bi bi-download me-1"></i> Download Template (.csv)
                                </a>
                            </div>

                            <form action="{{ route('master.import.data') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="upload-box mb-3" style="border-color: rgba(25, 135, 84, 0.3); background: rgba(25, 135, 84, 0.02);">
                                    <i class="bi bi-file-earmark-plus display-5 text-success mb-2 d-block"></i>
                                    <label class="form-label fw-semibold text-body mb-1">File CSV / Excel (.xlsx, .csv)</label>
                                    <p class="text-secondary small mb-2">Maksimal ukuran file: 10 MB</p>
                                    <input type="file" name="file" class="form-control form-control-sm w-75 mx-auto" accept=".csv,.xlsx,.xls" required>
                                </div>

                                <button type="submit" class="btn btn-success text-white fw-semibold rounded-pill px-4 shadow-sm">
                                    <i class="bi bi-cloud-upload me-1"></i> Proses Import Aset Tanah
                                </button>
                            </form>
                        </div>

                        <div class="col-lg-5">
                            <div class="p-3.5 bg-body-tertiary rounded-3 border" style="font-size: 0.85rem;">
                                <h6 class="fw-bold text-body mb-2"><i class="bi bi-info-circle text-success me-1"></i> Format Kolom Data Aset Baru:</h6>
                                <ul class="list-unstyled small text-body mb-0" style="font-size: 0.82rem;">
                                    <li class="mb-1.5"><code class="text-success">kode_aset</code> : <span class="fw-semibold text-danger">Wajib</span> (NIBAR / Kode Aset Unik)</li>
                                    <li class="mb-1.5"><code class="text-success">nama_aset</code> : <span class="fw-semibold text-danger">Wajib</span> (Nama bidang tanah)</li>
                                    <li class="mb-1.5"><code class="text-success">peruntukan</code> : Opsional (Fasilitas Umum / Gedung)</li>
                                    <li class="mb-1.5"><code class="text-success">luas</code> : Opsional (Luas m²)</li>
                                    <li class="mb-1.5"><code class="text-success">opd</code> : Opsional (Nama Instansi Pengguna)</li>
                                    <li class="mb-1.5"><code class="text-success">harga_perolehan</code> : Opsional (Angka Rupiah)</li>
                                    <li class="mb-1.5"><code class="text-success">tanggal_perolehan</code> : Opsional (YYYY-MM-DD)</li>
                                    <li class="mb-0"><code class="text-success">status_proses</code> : Opsional (Status BPN Awal)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
