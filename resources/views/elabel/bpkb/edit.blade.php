@extends('layouts.app')

@section('title', 'Edit BPKB Kendaraan - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.bpkb.index', ['type' => $vehicleRoute]) }}" class="text-decoration-none text-secondary">Katalog BPKB</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Edit BPKB</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0">Edit Data BPKB {{ $item->plate_number }}</h4>
        </div>
        <div>
            <a href="{{ route('elabel.bpkb.index', ['type' => $vehicleRoute]) }}" class="btn btn-light border fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> Mohon periksa kembali inputan anda:
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <form action="{{ route('elabel.bpkb.update', $item->id) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Tahun Dokumen <span class="text-danger">*</span></label>
                        <select name="year" class="form-select" required>
                            <option value="">-- Pilih Tahun --</option>
                            @foreach($years as $yr)
                                <option value="{{ $yr }}" {{ old('year', $item->year) == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Jenis Kendaraan <span class="text-danger">*</span></label>
                        <select name="vehicle_type" class="form-select" required>
                            <option value="R4" {{ old('vehicle_type', $vehicleType) === 'R4' ? 'selected' : '' }}>R4 (Mobil)</option>
                            <option value="R2" {{ old('vehicle_type', $vehicleType) === 'R2' ? 'selected' : '' }}>R2 (Motor)</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">No. Polisi <span class="text-danger">*</span></label>
                        <input type="text" name="plate_number" value="{{ old('plate_number', $item->plate_number) }}" class="form-control" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">No. BPKB</label>
                        <input type="text" name="no_bpkb" value="{{ old('no_bpkb', $item->no_bpkb) }}" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">NIBAR</label>
                        <input type="text" name="nibar" value="{{ old('nibar', $item->nibar) }}" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">No. Rangka</label>
                        <input type="text" name="no_rangka" value="{{ old('no_rangka', $item->no_rangka) }}" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">No. Mesin</label>
                        <input type="text" name="no_mesin" value="{{ old('no_mesin', $item->no_mesin) }}" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Merek</label>
                        <input type="text" name="merek" value="{{ old('merek', $item->merek) }}" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Tipe / Model</label>
                        <input type="text" name="tipe" value="{{ old('tipe', $item->tipe) }}" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Isi Silinder (CC)</label>
                        <input type="text" name="isi_silinder" value="{{ old('isi_silinder', $item->isi_silinder) }}" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Warna</label>
                        <input type="text" name="warna" value="{{ old('warna', $item->warna) }}" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Pemegang Kendaraan (Personal)</label>
                        <input type="text" name="pengguna" value="{{ old('pengguna', $item->pengguna) }}" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Dinas / OPD (SIPAT)</label>
                        <select name="sipat_opd_id" class="form-select">
                            <option value="">-- Pilih Dinas / OPD --</option>
                            @foreach($opds as $opd)
                                <option value="{{ $opd->id }}" {{ old('sipat_opd_id', $item->sipat_opd_id) == $opd->id ? 'selected' : '' }}>{{ $opd->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Ganti File Scan BPKB (PDF, JPG, PNG Max 20MB)</label>
                        <input type="file" name="pdf" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        @if($item->pdf_path)
                            <div class="small text-success mt-1"><i class="bi bi-file-earmark-check"></i> File scan saat ini tersedia. Upload file baru untuk mengganti.</div>
                        @endif
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-4">
                    <a href="{{ route('elabel.bpkb.index', ['type' => $vehicleRoute]) }}" class="btn btn-light border">Batal</a>
                    <button type="submit" class="btn btn-primary fw-semibold px-4"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
