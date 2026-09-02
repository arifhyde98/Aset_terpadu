@extends('layouts.app')

@section('title', 'Input Berkas Arsip Baru - eLABEL')

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
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Input Berkas Baru</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-plus text-primary"></i> Input Berkas Arsip: {{ $selectedType->nama }}
            </h4>
        </div>
        <div class="action-toolbar d-flex flex-wrap gap-2">
            <a href="{{ route('elabel.dynamic.items.index') }}" class="btn btn-outline-secondary shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Kembali
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

    <form action="{{ route('elabel.dynamic.items.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="archive_type_id" value="{{ $selectedType->id }}">

        <div class="row g-4">
            <!-- Kolom Kiri: Informasi Utama & Box -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-navy mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-info-circle text-primary"></i> Data Utama Dokumen
                        </h6>
                        <!-- Category Switcher -->
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Kategori: {{ $selectedType->nama }}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                @foreach($types as $t)
                                    <li>
                                        <a class="dropdown-item py-2 {{ $selectedType->id == $t->id ? 'active' : '' }}" 
                                           href="{{ route('elabel.dynamic.items.create', ['type_id' => $t->id]) }}">
                                            <i class="bi {{ $t->icon ?: 'bi-folder' }} me-1"></i> {{ $t->nama }} ({{ $t->kode }})
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Nomor Dokumen / Berkas <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_dokumen" value="{{ old('nomor_dokumen') }}" class="form-control font-monospace" placeholder="Cth: 503/012/IMB-DPMPTSP/2023" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Nama / Uraian Berkas <span class="text-danger">*</span></label>
                            <input type="text" name="nama_dokumen" value="{{ old('nama_dokumen') }}" class="form-control" placeholder="Cth: IMB Gedung Kantor Dinas Kesehatan" required>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold text-dark">Tahun Dokumen</label>
                                <input type="number" name="tahun_dokumen" value="{{ old('tahun_dokumen', date('Y')) }}" class="form-control" min="1900" max="2100">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold text-dark">Status Berkas</label>
                                <select name="status" class="form-select">
                                    <option value="Tersedia" {{ old('status') == 'Tersedia' ? 'selected' : '' }}>Tersedia</option>
                                    <option value="Dipinjam" {{ old('status') == 'Dipinjam' ? 'selected' : '' }}>Dipinjam</option>
                                    <option value="Musnah" {{ old('status') == 'Musnah' ? 'selected' : '' }}>Musnah</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">OPD / Instansi Pengolah</label>
                            <select name="opd_id" class="form-select">
                                <option value="">-- Pilih OPD Pengolah --</option>
                                @foreach($opds as $o)
                                    <option value="{{ $o->id }}" {{ old('opd_id') == $o->id ? 'selected' : '' }}>{{ $o->nama }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Penyimpanan Box Fisik</label>
                            <select name="archive_box_id" class="form-select">
                                <option value="">-- Belum Dimasukkan Box (Simpan Tanpa Box) --</option>
                                @foreach($boxes as $b)
                                    <option value="{{ $b->id }}" {{ (old('archive_box_id', request('box_id')) == $b->id) ? 'selected' : '' }}>
                                        {{ $b->nomor_box }} - {{ $b->lokasi_rak ?: 'Rak Arsip' }} (Isi: {{ $b->items()->count() }}/{{ $b->kapasitas_maksimal }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text small">Hanya menampilkan box fisik khusus kategori <strong>{{ $selectedType->nama }}</strong>.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Unggah Berkas Scan PDF Utama</label>
                            <input type="file" name="file_scan_pdf" class="form-control" accept="application/pdf,image/*">
                            <div class="form-text small">Format PDF atau gambar maksimal 20 MB.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Catatan / Keterangan Tambahan</label>
                            <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan fisik dokumen, kondisi berkas, dll...">{{ old('keterangan') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Atribut Dinamis Sesuai Schema Type -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-navy mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-ui-checks text-success"></i> Atribut Kustom Kategori ({{ count($selectedType->schema_fields ?? []) }})
                        </h6>
                        <a href="{{ route('elabel.dynamic.types.edit', $selectedType->id) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil me-1"></i> Sesuaikan Form
                        </a>
                    </div>
                    <div class="card-body p-4">
                        @if(!empty($selectedType->schema_fields) && count($selectedType->schema_fields) > 0)
                            @foreach($selectedType->schema_fields as $field)
                                @php
                                    $fName = $field['name'];
                                    $fLabel = $field['label'];
                                    $fType = $field['type'] ?? 'text';
                                    $fReq = !empty($field['required']);
                                    $fPlace = $field['placeholder'] ?? '';
                                    $fHelp = $field['help_text'] ?? '';
                                    $fOpts = $field['options'] ?? [];
                                    $oldVal = old("meta_{$fName}");
                                @endphp

                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-dark">
                                        {{ $fLabel }}
                                        @if($fReq) <span class="text-danger">*</span> @endif
                                    </label>

                                    @if($fType === 'textarea')
                                        <textarea name="meta_{{ $fName }}" class="form-control" rows="3" placeholder="{{ $fPlace }}" {{ $fReq ? 'required' : '' }}>{{ $oldVal }}</textarea>
                                    @elseif($fType === 'number')
                                        <input type="number" step="any" name="meta_{{ $fName }}" value="{{ $oldVal }}" class="form-control" placeholder="{{ $fPlace }}" {{ $fReq ? 'required' : '' }}>
                                    @elseif($fType === 'date')
                                        <input type="date" name="meta_{{ $fName }}" value="{{ $oldVal }}" class="form-control" {{ $fReq ? 'required' : '' }}>
                                    @elseif($fType === 'select')
                                        <select name="meta_{{ $fName }}" class="form-select" {{ $fReq ? 'required' : '' }}>
                                            <option value="">-- Pilih {{ $fLabel }} --</option>
                                            @foreach($fOpts as $opt)
                                                <option value="{{ $opt }}" {{ $oldVal == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                    @elseif($fType === 'file')
                                        <input type="file" name="meta_{{ $fName }}" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg" {{ $fReq ? 'required' : '' }}>
                                    @else
                                        <input type="text" name="meta_{{ $fName }}" value="{{ $oldVal }}" class="form-control" placeholder="{{ $fPlace }}" {{ $fReq ? 'required' : '' }}>
                                    @endif

                                    @if($fHelp)
                                        <div class="form-text small">{{ $fHelp }}</div>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-info-circle fs-2 d-block mb-2 text-secondary"></i>
                                Kategori ini tidak memiliki kolom atribut kustom tambahan. Data akan disimpan dengan kolom standar.
                            </div>
                        @endif
                    </div>
                    <div class="card-footer bg-white border-top py-3 px-4 d-flex justify-content-end gap-2">
                        <a href="{{ route('elabel.dynamic.items.index') }}" class="btn btn-light border px-4">Batal</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-check-lg me-1"></i> Simpan Dokumen Arsip
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

</div>
@endsection
