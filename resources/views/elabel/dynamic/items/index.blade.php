@extends('layouts.app')

@section('title', ($currentType ? 'Katalog ' . $currentType->nama : 'Semua Berkas Arsip Dinamis') . ' - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Breadcrumbs -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    @if($currentType)
                        <li class="breadcrumb-item text-secondary">{{ $currentType->nama }}</li>
                        <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Katalog Berkas</li>
                    @else
                        <li class="breadcrumb-item text-secondary">Arsip Dinamis</li>
                        <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Semua Berkas</li>
                    @endif
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0 d-flex align-items-center gap-2">
                @if($currentType)
                    <i class="bi {{ $currentType->icon ?: 'bi-folder2-open' }} text-{{ $currentType->warna_badge ?: 'primary' }}"></i>
                    <span>Katalog Berkas: {{ $currentType->nama }}</span>
                    <span class="badge bg-{{ $currentType->warna_badge ?: 'primary' }}-subtle text-{{ $currentType->warna_badge ?: 'primary' }} border border-{{ $currentType->warna_badge ?: 'primary' }}-subtle fs-6 font-monospace">{{ $currentType->kode }}</span>
                @else
                    <i class="bi bi-folder2-open text-primary"></i>
                    <span>Semua Berkas Arsip Dinamis</span>
                @endif
            </h4>
            @if($currentType)
                <p class="text-muted small mb-0 mt-1">
                    @if($currentType->deskripsi && $currentType->deskripsi !== '-') {{ $currentType->deskripsi }} &bull; @endif
                    Total: <strong>{{ $items->total() }}</strong> berkas terarsip
                </p>
            @endif
        </div>
        <div class="action-toolbar d-flex flex-wrap gap-2">
            <a href="{{ route('elabel.dynamic.items.export', request()->query()) }}" class="btn btn-outline-success shadow-sm fw-medium d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </a>
            @if($currentType)
                <a href="{{ route('elabel.dynamic.boxes.index', ['type_id' => $currentType->id]) }}" class="btn btn-outline-warning text-dark shadow-sm fw-medium d-flex align-items-center gap-2">
                    <i class="bi bi-box-seam"></i> Box {{ $currentType->kode ?: 'Arsip' }}
                </a>
                <button type="button" class="btn btn-primary shadow-sm fw-medium d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createItemModal">
                    <i class="bi bi-plus-circle"></i> Input {{ $currentType->kode ?: 'Dokumen' }} Baru
                </button>
            @else
                <a href="{{ route('elabel.dynamic.types.index') }}" class="btn btn-outline-secondary shadow-sm fw-medium d-flex align-items-center gap-2">
                    <i class="bi bi-sliders"></i> Master Kategori
                </a>
                <button type="button" class="btn btn-primary shadow-sm fw-medium d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createItemModal">
                    <i class="bi bi-plus-circle"></i> Input Arsip Baru
                </button>
            @endif
        </div>
    </div>

    @if(!$currentType)
        <!-- Category Pills Quick Filter (Hanya tampil pada mode Semua Berkas) -->
        <div class="d-flex align-items-center gap-2 overflow-x-auto pb-2 mb-3">
            <a href="{{ route('elabel.dynamic.items.index', request()->except('type_id', 'page')) }}" 
               class="btn btn-sm btn-primary rounded-pill px-3 text-nowrap fw-medium">
                <i class="bi bi-grid me-1"></i> Semua Kategori
            </a>
            @foreach($types as $t)
                <a href="{{ route('elabel.dynamic.items.index', array_merge(request()->except('page'), ['type_id' => $t->id])) }}" 
                   class="btn btn-sm btn-light border bg-white text-secondary rounded-pill px-3 text-nowrap fw-medium">
                    <i class="bi {{ $t->icon ?: 'bi-folder' }} me-1"></i> {{ $t->nama }}
                </a>
            @endforeach
        </div>
    @endif

    <!-- Filter & Search Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <form action="{{ route('elabel.dynamic.items.index') }}" method="GET" class="row g-2 align-items-center">
                @if(!empty($selectedType))
                    <input type="hidden" name="type_id" value="{{ $selectedType }}">
                @endif
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" name="q" value="{{ $searchQuery }}" class="form-control border-start-0 shadow-none" placeholder="Cari nomor berkas, nama/uraian, metadata...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="opd_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua OPD / Instansi</option>
                        @foreach($opds as $o)
                            <option value="{{ $o->id }}" {{ $selectedOpd == $o->id ? 'selected' : '' }}>{{ $o->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="Tersedia" {{ $selectedStatus == 'Tersedia' ? 'selected' : '' }}>Tersedia</option>
                        <option value="Dipinjam" {{ $selectedStatus == 'Dipinjam' ? 'selected' : '' }}>Dipinjam</option>
                        <option value="Musnah" {{ $selectedStatus == 'Musnah' ? 'selected' : '' }}>Musnah</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <input type="number" name="year" value="{{ $selectedYear }}" class="form-control" placeholder="Tahun" min="1900" max="2100">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-medium">Filter</button>
                    <a href="{{ route('elabel.dynamic.items.index', !empty($selectedType) ? ['type_id' => $selectedType] : []) }}" class="btn btn-light border bg-white" title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4 text-center" style="width: 50px;">No.</th>
                        <th class="py-3">Nomor Dokumen</th>
                        <th class="py-3">Nama / Uraian Berkas</th>
                        @if(empty($selectedType))
                            <th class="py-3">Kategori</th>
                        @endif
                        <th class="py-3">OPD Pengolah</th>
                        <th class="py-3 text-center">Box & Lokasi</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3 text-center">Scan PDF</th>
                        <th class="py-3 px-4 text-center" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td class="px-4 text-center fw-medium text-secondary">{{ $loop->iteration + ($items->currentPage() - 1) * $items->perPage() }}</td>
                            <td>
                                <div class="fw-bold text-navy font-monospace">{{ $item->nomor_dokumen }}</div>
                                <span class="text-xs text-muted">Tahun {{ $item->tahun_dokumen ?: '-' }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $item->nama_dokumen }}</div>
                                @if(!empty($item->metadata))
                                    <div class="text-xs text-muted mt-1">
                                        @php $shownMeta = 0; @endphp
                                        @foreach($item->metadata as $mKey => $mVal)
                                            @if($mVal && $shownMeta < 3)
                                                <span class="badge bg-light text-secondary border me-1 font-monospace">{{ $mKey }}: {{ is_array($mVal) ? json_encode($mVal) : Str::limit($mVal, 20) }}</span>
                                                @php $shownMeta++; @endphp
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            @if(empty($selectedType))
                                <td>
                                    <span class="badge bg-{{ $item->archiveType->warna_badge ?? 'primary' }}-subtle text-{{ $item->archiveType->warna_badge ?? 'primary' }} border">
                                        <i class="bi {{ $item->archiveType->icon ?? 'bi-folder' }} me-1"></i> {{ $item->archiveType->nama ?? '-' }}
                                    </span>
                                </td>
                            @endif
                            <td>
                                <span class="text-secondary small">{{ $item->opd->nama ?? '-' }}</span>
                            </td>
                            <td class="text-center">
                                @if($item->box)
                                    <a href="{{ route('elabel.dynamic.boxes.show', $item->box->id) }}" class="badge bg-warning-subtle text-dark border text-decoration-none">
                                        <i class="bi bi-box me-1"></i> {{ $item->box->nomor_box }}
                                    </a>
                                    <span class="d-block text-xs text-muted">{{ $item->box->lokasi_rak ?: '' }}</span>
                                @else
                                    <span class="badge bg-light text-muted border">Belum Di-box</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($item->status === 'Tersedia')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Tersedia</span>
                                @elseif($item->status === 'Dipinjam')
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Dipinjam</span>
                                @elseif($item->status === 'Musnah')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Musnah</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border">{{ $item->status }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if(!empty($item->file_scan_pdf))
                                    <a href="{{ route('elabel.dynamic.items.view-pdf', $item->id) }}" target="_blank" class="btn btn-sm btn-outline-danger px-2 py-1" title="Lihat PDF Dokumen">
                                        <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                                    </a>
                                @else
                                    <span class="text-muted text-xs"><i class="bi bi-dash"></i></span>
                                @endif
                            </td>
                            <td class="px-4 text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('elabel.dynamic.items.show', $item->id) }}" class="btn btn-outline-primary" title="Detail Dokumen">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('elabel.dynamic.items.edit', $item->id) }}" class="btn btn-outline-warning" title="Edit Data">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('elabel.dynamic.items.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus dokumen {{ $item->nomor_dokumen }}? Seluruh berkas scan dan lampiran akan dihapus.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Hapus Dokumen">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ empty($selectedType) ? 9 : 8 }}" class="text-center py-5 text-secondary">
                                <i class="bi bi-folder-x fs-1 d-block mb-2 text-muted"></i>
                                @if($currentType)
                                    <div class="fw-medium">Belum ada berkas arsip <strong>{{ $currentType->nama }}</strong> yang tersimpan atau sesuai kriteria pencarian.</div>
                                    <button type="button" class="btn btn-sm btn-primary rounded-pill mt-3 px-3" data-bs-toggle="modal" data-bs-target="#createItemModal">
                                        <i class="bi bi-plus-circle me-1"></i> Input {{ $currentType->kode ?: 'Berkas' }} Baru
                                    </button>
                                @else
                                    <div class="fw-medium">Belum ada berkas arsip yang sesuai dengan kriteria pencarian.</div>
                                    <button type="button" class="btn btn-sm btn-primary rounded-pill mt-3 px-3" data-bs-toggle="modal" data-bs-target="#createItemModal">
                                        <i class="bi bi-plus-circle me-1"></i> Input Arsip Baru
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="card-footer bg-white border-0 py-3 px-4">
                {{ $items->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Modal Input Berkas Baru -->
<div class="modal fade" id="createItemModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form action="{{ route('elabel.dynamic.items.store') }}" method="POST" enctype="multipart/form-data" class="d-flex flex-column h-100 overflow-hidden" style="max-height: 88vh;">
                @csrf
                <div class="modal-header bg-primary-subtle border-bottom px-4 py-3 flex-shrink-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-file-earmark-plus fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0">
                                @if($currentType)
                                    Input Berkas: {{ $currentType->nama }}
                                @else
                                    Input Berkas Arsip Dinamis Baru
                                @endif
                            </h5>
                            <small class="text-primary fw-medium">
                                @if($currentType)
                                    Kategori <span class="font-monospace fw-bold">[{{ $currentType->kode }}]</span> &bull; Pengarsipan Fisik & Digital
                                @else
                                    Pilih jenis kategori dan lengkapi data berkas
                                @endif
                            </small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light overflow-y-auto flex-grow-1">
                    @if($currentType)
                        <input type="hidden" name="archive_type_id" value="{{ $currentType->id }}">
                    @else
                        <div class="card border-0 shadow-sm rounded-4 mb-3 bg-white">
                            <div class="card-body p-3">
                                <label class="form-label fw-semibold text-dark">Kategori / Jenis Arsip <span class="text-danger">*</span></label>
                                <select name="archive_type_id" class="form-select" id="modalCategorySelect" required>
                                    @foreach($types as $t)
                                        <option value="{{ $t->id }}" {{ $selectedType == $t->id ? 'selected' : '' }}>
                                            {{ $t->nama }} ({{ $t->kode }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    <div class="row g-3">
                        <!-- Kolom Kiri: Informasi Utama & Box -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                                <div class="card-header bg-white border-bottom py-3 px-4">
                                    <h6 class="fw-bold text-navy mb-0 d-flex align-items-center gap-2">
                                        <i class="bi bi-info-circle text-primary"></i> Data Utama Dokumen
                                    </h6>
                                </div>
                                <div class="card-body p-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-dark">Nomor Dokumen / Berkas <span class="text-danger">*</span></label>
                                        <input type="text" name="nomor_dokumen" value="{{ old('nomor_dokumen') }}" class="form-control font-monospace" placeholder="Cth: 503/012/IMB-DPMPTSP/2026" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-dark">Nama / Uraian Berkas <span class="text-danger">*</span></label>
                                        <input type="text" name="nama_dokumen" value="{{ old('nama_dokumen') }}" class="form-control" placeholder="Cth: Dokumen Berkas Arsip..." required>
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
                                                <option value="{{ $b->id }}" {{ old('archive_box_id') == $b->id ? 'selected' : '' }}>
                                                    {{ $b->nomor_box }} - {{ $b->lokasi_rak ?: 'Rak Arsip' }} (Isi: {{ $b->items()->count() }}/{{ $b->kapasitas_maksimal }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @if($currentType)
                                            <div class="form-text small">Hanya menampilkan box fisik khusus kategori <strong>{{ $currentType->nama }}</strong>.</div>
                                        @endif
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-dark">Unggah Berkas Scan PDF Utama</label>
                                        <input type="file" name="file_scan_pdf" class="form-control" accept="application/pdf,image/*">
                                        <div class="form-text small">Format PDF atau gambar maksimal 20 MB.</div>
                                    </div>

                                    <div class="mb-0">
                                        <label class="form-label fw-semibold text-dark">Catatan / Keterangan Tambahan</label>
                                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan fisik dokumen, kondisi berkas, dll...">{{ old('keterangan') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Atribut Kustom Sesuai Kategori -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                                    <h6 class="fw-bold text-navy mb-0 d-flex align-items-center gap-2">
                                        <i class="bi bi-ui-checks text-success"></i> Atribut Kustom Kategori {{ $currentType ? '(' . count($currentType->schema_fields ?? []) . ' Kolom)' : '' }}
                                    </h6>
                                </div>
                                <div class="card-body p-4">
                                    @if($currentType && !empty($currentType->schema_fields) && count($currentType->schema_fields) > 0)
                                        @foreach($currentType->schema_fields as $field)
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
                                            @if($currentType)
                                                Kategori ini menggunakan atribut dokumen standar. Seluruh berkas scan dan metadata umum akan tersimpan secara otomatis.
                                            @else
                                                Silakan pilih kategori arsip untuk menyesuaikan atribut kustom jika tersedia.
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-3 px-4 bg-white flex-shrink-0">
                    <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                        <i class="bi bi-check-lg me-1"></i> Simpan Dokumen Arsip
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.text-xs {
    font-size: 0.75rem;
}
</style>
@endsection
