@extends('layouts.app')

@section('title', 'Edit Dokumen ' . $item->nomor_dokumen . ' - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Breadcrumbs -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dynamic.items.index') }}" class="text-decoration-none text-secondary">Katalog Arsip</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Edit {{ $item->nomor_dokumen }}</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-pencil-square text-warning"></i> Edit Dokumen: {{ $item->nomor_dokumen }}
            </h4>
        </div>
        <div class="action-toolbar d-flex flex-wrap gap-2">
            <a href="{{ route('elabel.dynamic.items.show', $item->id) }}" class="btn btn-outline-secondary shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Kembali ke Detail
            </a>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Periksa kembali formulir:</strong>
            <ul class="mb-0 mt-2 small">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('elabel.dynamic.items.update', $item->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <!-- Kolom Kiri: Informasi Utama & Box -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="fw-bold text-navy mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-info-circle text-primary"></i> Data Utama Dokumen
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-xs fw-bold text-muted text-uppercase d-block">Kategori Arsip</label>
                            <span class="badge bg-{{ $item->archiveType->warna_badge ?? 'primary' }}-subtle text-{{ $item->archiveType->warna_badge ?? 'primary' }} fs-6 fw-bold border">
                                <i class="bi {{ $item->archiveType->icon ?? 'bi-folder' }} me-1"></i> {{ $item->archiveType->nama ?? '-' }} ({{ $item->archiveType->kode ?? '-' }})
                            </span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Nomor Dokumen / Berkas <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_dokumen" value="{{ old('nomor_dokumen', $item->nomor_dokumen) }}" class="form-control font-monospace" placeholder="Cth: 503/012/IMB-DPMPTSP/2023" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Nama / Uraian Berkas <span class="text-danger">*</span></label>
                            <input type="text" name="nama_dokumen" value="{{ old('nama_dokumen', $item->nama_dokumen) }}" class="form-control" placeholder="Cth: IMB Gedung Kantor Dinas Kesehatan" required>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold text-dark">Tahun Dokumen</label>
                                <input type="number" name="tahun_dokumen" value="{{ old('tahun_dokumen', $item->tahun_dokumen) }}" class="form-control" min="1900" max="2100">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold text-dark">Status Berkas</label>
                                <select name="status" class="form-select">
                                    <option value="Tersedia" {{ old('status', $item->status) == 'Tersedia' ? 'selected' : '' }}>Tersedia</option>
                                    <option value="Dipinjam" {{ old('status', $item->status) == 'Dipinjam' ? 'selected' : '' }}>Dipinjam</option>
                                    <option value="Musnah" {{ old('status', $item->status) == 'Musnah' ? 'selected' : '' }}>Musnah</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">OPD / Instansi Pengolah</label>
                            <select name="opd_id" class="form-select">
                                <option value="">-- Pilih OPD Pengolah --</option>
                                @foreach($opds as $o)
                                    <option value="{{ $o->id }}" {{ old('opd_id', $item->opd_id) == $o->id ? 'selected' : '' }}>{{ $o->nama }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Penyimpanan Box Fisik</label>
                            <select name="archive_box_id" class="form-select">
                                <option value="">-- Belum Dimasukkan Box (Simpan Tanpa Box) --</option>
                                @foreach($boxes as $b)
                                    <option value="{{ $b->id }}" {{ old('archive_box_id', $item->archive_box_id) == $b->id ? 'selected' : '' }}>
                                        {{ $b->nomor_box }} - {{ $b->lokasi_rak ?: 'Rak Arsip' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Ganti Berkas Scan PDF Utama (Opsional)</label>
                            <input type="file" name="file_scan_pdf" class="form-control" accept="application/pdf,image/*">
                            @if($item->file_scan_pdf)
                                <div class="mt-2 text-xs text-success d-flex align-items-center gap-1">
                                    <i class="bi bi-file-earmark-check-fill"></i> Sudah ada berkas scan tersimpan. Unggah jika ingin mengganti.
                                    <a href="{{ route('elabel.dynamic.items.view-pdf', $item->id) }}" target="_blank" class="ms-2 text-decoration-underline">Pratinjau</a>
                                </div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Catatan / Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan fisik dokumen...">{{ old('keterangan', $item->keterangan) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Atribut Dinamis Sesuai Schema Type -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="fw-bold text-navy mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-ui-checks text-success"></i> Atribut Kustom Kategori
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        @php
                            $schemaFields = $item->archiveType->schema_fields ?? [];
                            $meta = $item->metadata ?? [];
                        @endphp

                        @if(!empty($schemaFields) && count($schemaFields) > 0)
                            @foreach($schemaFields as $field)
                                @php
                                    $fName = $field['name'];
                                    $fLabel = $field['label'];
                                    $fType = $field['type'] ?? 'text';
                                    $fReq = !empty($field['required']);
                                    $fPlace = $field['placeholder'] ?? '';
                                    $fHelp = $field['help_text'] ?? '';
                                    $fOpts = $field['options'] ?? [];
                                    $val = old("meta_{$fName}", $meta[$fName] ?? '');
                                @endphp

                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-dark">
                                        {{ $fLabel }}
                                        @if($fReq) <span class="text-danger">*</span> @endif
                                    </label>

                                    @if($fType === 'textarea')
                                        <textarea name="meta_{{ $fName }}" class="form-control" rows="3" placeholder="{{ $fPlace }}" {{ $fReq ? 'required' : '' }}>{{ $val }}</textarea>
                                    @elseif($fType === 'number')
                                        <input type="number" step="any" name="meta_{{ $fName }}" value="{{ $val }}" class="form-control" placeholder="{{ $fPlace }}" {{ $fReq ? 'required' : '' }}>
                                    @elseif($fType === 'date')
                                        <input type="date" name="meta_{{ $fName }}" value="{{ $val }}" class="form-control" {{ $fReq ? 'required' : '' }}>
                                    @elseif($fType === 'select')
                                        <select name="meta_{{ $fName }}" class="form-select" {{ $fReq ? 'required' : '' }}>
                                            <option value="">-- Pilih {{ $fLabel }} --</option>
                                            @foreach($fOpts as $opt)
                                                <option value="{{ $opt }}" {{ $val == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                    @elseif($fType === 'file')
                                        <input type="file" name="meta_{{ $fName }}" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                                        @php
                                            $existAtt = $item->attachments->firstWhere('field_name', $fName);
                                        @endphp
                                        @if($existAtt)
                                            <div class="mt-1 text-xs text-muted">
                                                <i class="bi bi-paperclip"></i> Lampiran saat ini: <strong>{{ $existAtt->file_title }}</strong> ({{ $existAtt->formatted_size }})
                                            </div>
                                        @endif
                                    @else
                                        <input type="text" name="meta_{{ $fName }}" value="{{ $val }}" class="form-control" placeholder="{{ $fPlace }}" {{ $fReq ? 'required' : '' }}>
                                    @endif

                                    @if($fHelp)
                                        <div class="form-text small">{{ $fHelp }}</div>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-info-circle fs-2 d-block mb-2 text-secondary"></i>
                                Tidak ada kolom atribut kustom tambahan untuk jenis arsip ini.
                            </div>
                        @endif
                    </div>
                    <div class="card-footer bg-white border-top py-3 px-4 d-flex justify-content-end gap-2">
                        <a href="{{ route('elabel.dynamic.items.show', $item->id) }}" class="btn btn-light border px-4">Batal</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

</div>
<style>
.text-xs {
    font-size: 0.75rem;
}
</style>
@endsection
