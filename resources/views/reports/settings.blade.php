@extends('layouts.app')

@section('title', 'Pengaturan Dokumen Cetak Laporan')

@section('content')
<div class="container-fluid px-0 report-settings-page">
    
    <!-- BREADCRUMB & PAGE HEADER -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('settings.index') }}" class="text-decoration-none text-secondary">Pengaturan Sistem</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Pengaturan Dokumen Cetak</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-navy mb-0"><i class="bi bi-printer-fill me-2 text-primary"></i>Pengaturan Dokumen Cetak Laporan</h3>
            <p class="text-muted small mb-0">Konfigurasikan tata letak Kop instansi, logo, tanda tangan pejabat, dan ukuran kertas ekspor secara terpusat.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('settings.index') }}" class="btn btn-sm btn-outline-secondary px-3">
                <i class="bi bi-gear me-1"></i> Pengaturan Konten
            </a>
            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-outline-primary px-3">
                <i class="bi bi-file-earmark-text me-1"></i> Laporan Kendaraan
            </a>
        </div>
    </div>

    <!-- ALERT MESSAGES -->
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <!-- MAIN SETTINGS CARD WITH TABS -->
    <div class="card border-0 shadow-sm mb-4 report-settings-card">
        <div class="card-header bg-white border-bottom py-3 report-settings-tabs">
            <ul class="nav nav-tabs card-header-tabs" id="reportSettingTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold" id="letterhead-tab" data-bs-toggle="tab" data-bs-target="#letterhead" type="button" role="tab" aria-controls="letterhead" aria-selected="true">
                        <i class="bi bi-envelope-paper me-2 text-primary"></i>KOP Surat Resmi
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold" id="signatory-tab" data-bs-toggle="tab" data-bs-target="#signatory" type="button" role="tab" aria-controls="signatory" aria-selected="false">
                        <i class="bi bi-person-badge me-2 text-primary"></i>Pejabat Penanda Tangan
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold" id="export-tab" data-bs-toggle="tab" data-bs-target="#export" type="button" role="tab" aria-controls="export" aria-selected="false">
                        <i class="bi bi-file-earmark-pdf me-2 text-primary"></i>Pengaturan Jenis Laporan
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-4">
            <div class="tab-content" id="reportSettingTabsContent">
                
                <!-- TAB 1: KOP SURAT -->
                <div class="tab-pane fade show active" id="letterhead" role="tabpanel" aria-labelledby="letterhead-tab">
                    <div class="row">
                        <!-- Form Section -->
                        <div class="col-lg-7">
                            <h5 class="fw-bold mb-4 text-navy"><i class="bi bi-pen me-2"></i>Edit Informasi Kop Surat</h5>
                            
                            <form action="{{ route('reports.settings.letterhead') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label fw-semibold text-secondary small">Nama Pemerintah Provinsi / Kabupaten</label>
                                        <input type="text" name="nama_pemerintah" class="form-control @error('nama_pemerintah') is-invalid @enderror" value="{{ old('nama_pemerintah', $letterhead->nama_pemerintah) }}" required>
                                        @error('nama_pemerintah')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-semibold text-secondary small">Nama Instansi Utama</label>
                                        <input type="text" name="nama_instansi" class="form-control @error('nama_instansi') is-invalid @enderror" value="{{ old('nama_instansi', $letterhead->nama_instansi) }}" required>
                                        @error('nama_instansi')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-semibold text-secondary small">Nama Unit / UPTD (Opsional)</label>
                                        <input type="text" name="nama_unit" class="form-control @error('nama_unit') is-invalid @enderror" value="{{ old('nama_unit', $letterhead->nama_unit) }}">
                                        @error('nama_unit')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-semibold text-secondary small">Alamat Instansi</label>
                                        <textarea name="alamat" class="form-control @error('alamat') is-invalid @enderror" rows="2" required>{{ old('alamat', $letterhead->alamat) }}</textarea>
                                        @error('alamat')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold text-secondary small">Telepon</label>
                                        <input type="text" name="telepon" class="form-control @error('telepon') is-invalid @enderror" value="{{ old('telepon', $letterhead->telepon) }}">
                                        @error('telepon')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold text-secondary small">Email</label>
                                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $letterhead->email) }}">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold text-secondary small">Website</label>
                                        <input type="text" name="website" class="form-control @error('website') is-invalid @enderror" value="{{ old('website', $letterhead->website) }}">
                                        @error('website')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-semibold text-secondary small">Ganti Logo Instansi (PNG/JPG/WEBP, Max 2MB)</label>
                                        <input type="file" name="logo" class="form-control @error('logo') is-invalid @enderror">
                                        @error('logo')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="mt-4 pt-2">
                                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                                        <i class="bi bi-save me-1"></i> Simpan Perubahan Kop
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Visual Preview Section -->
                        <div class="col-lg-5 mt-4 mt-lg-0 border-start ps-lg-4 report-settings-side-panel">
                            <h5 class="fw-bold mb-4 text-navy"><i class="bi bi-eye me-2"></i>Pratinjau KOP Surat</h5>
                            
                            <div class="border rounded p-3 shadow-sm report-preview-paper" style="font-size: 8pt; line-height: 1.4;">
                                <div class="d-flex align-items-center border-bottom pb-2 mb-2">
                                    @php
                                        $logoFile = $letterhead->logo_path ?? 'images/logo-sulteng.png';
                                    @endphp
                                    <img src="{{ asset($logoFile) }}" class="me-3" style="max-height: 52px; max-width: 52px; object-fit: contain;">
                                    <div class="text-center w-100 pe-5">
                                        <div class="fw-bold text-dark" style="font-size: 8.5pt;">{{ $letterhead->nama_pemerintah ?? 'PEMERINTAH PROVINSI SULAWESI TENGAH' }}</div>
                                        <div class="fw-bold text-primary" style="font-size: 9.5pt;">{{ $letterhead->nama_instansi ?? 'BADAN PENDAPATAN DAERAH (BAPENDA)' }}</div>
                                        @if($letterhead->nama_unit)
                                            <div class="text-secondary fw-semibold">{{ $letterhead->nama_unit }}</div>
                                        @endif
                                        <div class="text-muted" style="font-size: 7.5pt;">{{ $letterhead->alamat ?? 'Jalan Cik Ditiro No. 23, Kota Palu, Sulawesi Tengah' }}</div>
                                        <div class="text-muted" style="font-size: 7pt;">
                                            @if($letterhead->telepon) Telp: {{ $letterhead->telepon }} @endif
                                            @if($letterhead->email) | Email: {{ $letterhead->email }} @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="report-preview-divider"></div>
                            </div>
                            
                            <div class="alert alert-info border-0 shadow-sm mt-4 small report-settings-note">
                                <i class="bi bi-info-circle-fill me-2 fs-6"></i>
                                Pratinjau di atas merepresentasikan struktur visual yang akan otomatis tercetak di header PDF, Excel, dan halaman pratinjau cetak printer.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: PEJABAT PENANDA TANGAN -->
                <div class="tab-pane fade" id="signatory" role="tabpanel" aria-labelledby="signatory-tab">
                    <div class="row">
                        <!-- Form Section -->
                        <div class="col-lg-7">
                            <h5 class="fw-bold mb-4 text-navy"><i class="bi bi-pen me-2"></i>Edit Informasi Pejabat Penanda Tangan</h5>
                            
                            <form action="{{ route('reports.settings.signatory') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label fw-semibold text-secondary small">Nama Lengkap & Gelar Pejabat</label>
                                        <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama', $signatory->nama) }}" required>
                                        @error('nama')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-semibold text-secondary small">Jabatan Dinas Resmi</label>
                                        <input type="text" name="jabatan" class="form-control @error('jabatan') is-invalid @enderror" value="{{ old('jabatan', $signatory->jabatan) }}" required>
                                        @error('jabatan')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Nomor Induk Pegawai (NIP)</label>
                                        <input type="text" name="nip" class="form-control @error('nip') is-invalid @enderror" value="{{ old('nip', $signatory->nip) }}">
                                        @error('nip')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Pangkat / Golongan</label>
                                        <input type="text" name="pangkat_golongan" class="form-control @error('pangkat_golongan') is-invalid @enderror" value="{{ old('pangkat_golongan', $signatory->pangkat_golongan) }}">
                                        @error('pangkat_golongan')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-semibold text-secondary small">Kota Penandatanganan (Tempat Cetak Surat)</label>
                                        <input type="text" name="kota_ttd" class="form-control @error('kota_ttd') is-invalid @enderror" value="{{ old('kota_ttd', $signatory->kota_ttd) }}" required>
                                        @error('kota_ttd')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-semibold text-secondary small">Tanda Tangan Digital Elektronik (PNG/JPG/WEBP Transparan, Max 2MB)</label>
                                        <input type="file" name="signature_image" class="form-control @error('signature_image') is-invalid @enderror">
                                        <span class="text-muted small style-normal" style="font-size: 8pt;"><i class="bi bi-info-circle me-1"></i>Kosongkan jika ingin menggunakan ruang tanda tangan basah (tanda tangan manual).</span>
                                        @error('signature_image')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="mt-4 pt-2">
                                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                                        <i class="bi bi-save me-1"></i> Simpan Data Pejabat
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Visual Preview Section -->
                        <div class="col-lg-5 mt-4 mt-lg-0 border-start ps-lg-4 report-settings-side-panel">
                            <h5 class="fw-bold mb-4 text-navy"><i class="bi bi-eye me-2"></i>Pratinjau Blok Tanda Tangan</h5>
                            
                            <div class="border rounded p-4 shadow-sm text-center mx-auto report-signature-preview" style="width: 250px; font-size: 9pt; line-height: 1.4;">
                                <div class="text-secondary">{{ $signatory->kota_ttd ?? 'Palu' }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
                                <div class="fw-bold text-dark mb-1">{{ $signatory->jabatan ?? 'Plt. Kepala Badan Pendapatan Daerah' }},</div>
                                
                                @if(!empty($signatory->signature_image_path))
                                    <div class="my-2">
                                        <img src="{{ asset($signatory->signature_image_path) }}" class="img-fluid" style="max-height: 52px; object-fit: contain;">
                                    </div>
                                @else
                                    <div class="py-4 text-muted small"><i class="bi bi-vector-pen me-1"></i>(Tanda Tangan Basah)</div>
                                @endif

                                <div class="fw-bold text-dark text-decoration-underline">{{ $signatory->nama ?? 'Drs. H. ARIF HYDE, M.Si' }}</div>
                                @if($signatory->nip)
                                    <div class="text-secondary small" style="font-size: 8pt;">NIP. {{ $signatory->nip }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: PENGATURAN JENIS LAPORAN -->
                <div class="tab-pane fade" id="export" role="tabpanel" aria-labelledby="export-tab">
                    <h5 class="fw-bold mb-4 text-navy"><i class="bi bi-sliders me-2"></i>Konfigurasi Format Ekspor Per Jenis Laporan</h5>
                    
                    <div class="row g-4">
                        @foreach($reportTypes as $reportType => $reportLabel)
                            @php
                                $setting = $exportSettings->get($reportType);
                                $fieldId = \Illuminate\Support\Str::slug($reportType);
                                $paperSize = $setting?->paper_size ?? 'A4';
                                $orientation = $setting?->orientation ?? 'L';
                                $showSummary = $setting ? $setting->show_summary : true;
                                $showSignature = $setting ? $setting->show_signature : true;
                            @endphp

                            <div class="col-md-6">
                                <div class="card h-100 border border-light-subtle shadow-sm report-export-card">
                                    <div class="card-header bg-navy text-white fw-bold py-3 report-export-card-header">
                                        <i class="bi bi-file-earmark-text-fill me-2"></i>{{ $reportLabel }}
                                    </div>
                                    <div class="card-body p-4">
                                        <form action="{{ route('reports.settings.export') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="report_type" value="{{ $reportType }}">
                                            
                                            <div class="row g-3">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold text-secondary">Ukuran Kertas</label>
                                                    <select name="paper_size" class="form-select form-select-sm">
                                                        <option value="A4" {{ $paperSize === 'A4' ? 'selected' : '' }}>A4 (Standar)</option>
                                                        <option value="F4" {{ $paperSize === 'F4' ? 'selected' : '' }}>F4 / Folio</option>
                                                        <option value="Letter" {{ $paperSize === 'Letter' ? 'selected' : '' }}>Letter</option>
                                                        <option value="Legal" {{ $paperSize === 'Legal' ? 'selected' : '' }}>Legal</option>
                                                    </select>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold text-secondary">Orientasi</label>
                                                    <select name="orientation" class="form-select form-select-sm">
                                                        <option value="L" {{ $orientation === 'L' ? 'selected' : '' }}>Landscape (Mendatar)</option>
                                                        <option value="P" {{ $orientation === 'P' ? 'selected' : '' }}>Portrait (Tegak)</option>
                                                    </select>
                                                </div>
                                                <div class="col-12 mt-3">
                                                    <div class="form-check form-switch mb-2">
                                                        <input class="form-check-input" type="checkbox" name="show_summary" id="{{ $fieldId }}_show_summary" value="1" {{ $showSummary ? 'checked' : '' }}>
                                                        <label class="form-check-label small fw-medium text-dark" for="{{ $fieldId }}_show_summary">Tampilkan Summary Cards (Total Unit & Aset)</label>
                                                    </div>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="show_signature" id="{{ $fieldId }}_show_signature" value="1" {{ $showSignature ? 'checked' : '' }}>
                                                        <label class="form-check-label small fw-medium text-dark" for="{{ $fieldId }}_show_signature">Tampilkan Tanda Tangan Pejabat Resmi</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-4 border-top pt-3 text-end">
                                                <button type="submit" class="btn btn-sm btn-primary px-3 fw-semibold">
                                                    <i class="bi bi-save me-1"></i> Update Format
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </div>
    
</div>
@endsection
