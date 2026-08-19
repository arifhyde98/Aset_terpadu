@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Edit Data Aset Tanah</h2>
            <p class="text-secondary small mb-0">Perbarui rincian bidang tanah {{ $aset->kode_aset }}</p>
        </div>
        <a href="{{ route('sipat.aset.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
            <ul class="mb-0 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card clean-card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <form action="{{ route('sipat.aset.update', $aset->id_aset) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Kode Aset (NIBAR / KIB) <span class="text-danger">*</span></label>
                        <input type="text" name="kode_aset" class="form-control" required value="{{ old('kode_aset', $aset->kode_aset) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Nama Aset / Bidang Tanah <span class="text-danger">*</span></label>
                        <input type="text" name="nama_aset" class="form-control" required value="{{ old('nama_aset', $aset->nama_aset) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">OPD Pengelola</label>
                        <select name="opd" class="form-select">
                            <option value="">-- Pilih OPD Pengelola --</option>
                            @foreach($opdList as $opd)
                                <option value="{{ $opd->nama }}" {{ old('opd', $aset->opd) == $opd->nama ? 'selected' : '' }}>{{ $opd->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Peruntukan / Penggunaan</label>
                        <input type="text" name="peruntukan" class="form-control" value="{{ old('peruntukan', $aset->peruntukan) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Luas Tanah (m²)</label>
                        <input type="number" step="0.01" name="luas" class="form-control" value="{{ old('luas', $aset->luas) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Harga Perolehan (Rp)</label>
                        <input type="number" step="0.01" name="harga_perolehan" class="form-control" value="{{ old('harga_perolehan', $aset->harga_perolehan) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Tanggal Perolehan</label>
                        <input type="date" name="tanggal_perolehan" class="form-control" value="{{ old('tanggal_perolehan', $aset->tanggal_perolehan) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Dasar Perolehan</label>
                        <input type="text" name="dasar_perolehan" class="form-control" value="{{ old('dasar_perolehan', $aset->dasar_perolehan) }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Alamat Lengkap / Lokasi</label>
                        <textarea name="alamat" class="form-control" rows="2">{{ old('alamat', $aset->alamat) }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Latitude (GIS)</label>
                        <input type="text" name="lat" class="form-control" value="{{ old('lat', $aset->lat) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Longitude (GIS)</label>
                        <input type="text" name="lng" class="form-control" value="{{ old('lng', $aset->lng) }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Keterangan Tambahan</label>
                        <textarea name="keterangan" class="form-control" rows="2">{{ old('keterangan', $aset->keterangan) }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('sipat.aset.index') }}" class="btn btn-secondary rounded-pill px-4">Batal</a>
                    <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="bi bi-save me-1"></i> Update Data Aset</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
