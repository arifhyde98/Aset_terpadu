@extends('layouts.app')

@section('title', 'Tambah Kendaraan')

@section('content')
<div class="container-fluid px-0">
    <!-- PAGE HEADER -->
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}" class="text-decoration-none text-secondary">Data Kendaraan</a></li>
                <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Tambah Baru</li>
            </ol>
        </nav>
        <h3 class="fw-bold text-navy mb-0">Tambah Kendaraan Baru</h3>
        <p class="text-secondary small">Lengkapi formulir di bawah ini untuk mendaftarkan aset kendaraan operasional.</p>
    </div>

    <form action="{{ route('vehicles.store') }}" method="POST">
        @csrf
        <div class="row g-4">
            <!-- Data Utama -->
            <div class="col-lg-8">
                <div class="admin-card p-4 h-100">
                    <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
                        <i class="bi bi-car-front-fill text-primary fs-5"></i>
                        <h6 class="fw-bold text-navy mb-0">Informasi Kendaraan</h6>
                    </div>
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">No. Polisi (Plat)</label>
                            <input type="text" name="no_polisi" class="form-control @error('no_polisi') is-invalid @enderror" value="{{ old('no_polisi') }}" placeholder="Contoh: DN 1234 XY" required>
                            @error('no_polisi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Nomor Register</label>
                            <input type="text" name="nomor_register" class="form-control @error('nomor_register') is-invalid @enderror" value="{{ old('nomor_register') }}" placeholder="Contoh: REG-1234567">
                            @error('nomor_register') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Jenis Kendaraan</label>
                            <select name="vehicle_type_id" class="form-select @error('vehicle_type_id') is-invalid @enderror" required onchange="document.getElementById('jenis_text').value = this.options[this.selectedIndex].text">
                                <option value="">Pilih Jenis</option>
                                @foreach($vehicleTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('vehicle_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="jenis" id="jenis_text" value="{{ old('jenis') }}">
                            @error('vehicle_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Merk Kendaraan</label>
                            <input type="text" name="merk" class="form-control @error('merk') is-invalid @enderror" value="{{ old('merk') }}" placeholder="Contoh: Toyota" required>
                            @error('merk') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Tipe / Model</label>
                            <input type="text" name="tipe" class="form-control @error('tipe') is-invalid @enderror" value="{{ old('tipe') }}" placeholder="Contoh: Innova Zenix" required>
                            @error('tipe') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-dark">Tahun Pembuatan</label>
                            <input type="number" name="tahun_pembuatan" class="form-control @error('tahun_pembuatan') is-invalid @enderror" value="{{ old('tahun_pembuatan') }}" placeholder="Contoh: 2024">
                            @error('tahun_pembuatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-dark">Warna</label>
                            <input type="text" name="warna" class="form-control @error('warna') is-invalid @enderror" value="{{ old('warna') }}" placeholder="Contoh: Hitam Metalik">
                            @error('warna') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-dark">Tgl. Jatuh Tempo STNK</label>
                            <input type="date" name="tgl_stnk" class="form-control @error('tgl_stnk') is-invalid @enderror" value="{{ old('tgl_stnk') }}">
                            @error('tgl_stnk') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">No. Rangka</label>
                            <input type="text" name="no_rangka" class="form-control @error('no_rangka') is-invalid @enderror" value="{{ old('no_rangka') }}">
                            @error('no_rangka') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">No. Mesin</label>
                            <input type="text" name="no_mesin" class="form-control @error('no_mesin') is-invalid @enderror" value="{{ old('no_mesin') }}">
                            @error('no_mesin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Dokumen STNK</label>
                            <select name="stnk_ada" class="form-select @error('stnk_ada') is-invalid @enderror" required>
                                <option value="Ada" {{ old('stnk_ada') == 'Ada' ? 'selected' : '' }}>Ada</option>
                                <option value="Tidak" {{ old('stnk_ada') == 'Tidak' ? 'selected' : '' }}>Tidak Ada</option>
                            </select>
                            @error('stnk_ada') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Dokumen BPKB</label>
                            <select name="bpkb_ada" class="form-select @error('bpkb_ada') is-invalid @enderror" required>
                                <option value="Ada" {{ old('bpkb_ada') == 'Ada' ? 'selected' : '' }}>Ada</option>
                                <option value="Tidak" {{ old('bpkb_ada') == 'Tidak' ? 'selected' : '' }}>Tidak Ada</option>
                            </select>
                            @error('bpkb_ada') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Tanggal Perolehan</label>
                            <input type="date" name="tgl_perolehan" class="form-control @error('tgl_perolehan') is-invalid @enderror" value="{{ old('tgl_perolehan') }}">
                            @error('tgl_perolehan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Nilai Perolehan (Rp)</label>
                            <input type="number" name="nilai_perolehan" class="form-control @error('nilai_perolehan') is-invalid @enderror" value="{{ old('nilai_perolehan') }}" placeholder="Contoh: 250000000">
                            @error('nilai_perolehan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold text-dark">Keterangan Tambahan</label>
                            <textarea name="keterangan" class="form-control @error('keterangan') is-invalid @enderror" rows="3" placeholder="Tambahkan catatan jika ada...">{{ old('keterangan') }}</textarea>
                            @error('keterangan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Penanggung Jawab & Status -->
            <div class="col-lg-4">
                <div class="admin-card p-4 mb-4">
                    <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
                        <i class="bi bi-person-badge-fill text-primary fs-5"></i>
                        <h6 class="fw-bold text-navy mb-0">Alokasi & Status</h6>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-dark">Unit Kerja / OPD</label>
                        <select name="opd_id" id="opd_select" class="form-select @error('opd_id') is-invalid @enderror" required
                                onchange="document.getElementById('opd_text').value = this.options[this.selectedIndex].text">
                            <option value="">-- Pilih OPD --</option>
                            @foreach($opds as $o)
                                <option value="{{ $o->id }}"
                                    {{ old('opd_id') == $o->id ? 'selected' : '' }}>
                                    {{ $o->nama }}
                                </option>
                            @endforeach
                        </select>
                        {{-- Hidden field untuk tetap menyimpan nama OPD ke kolom 'opd' string --}}
                        <input type="hidden" name="opd" id="opd_text" value="{{ old('opd') }}">
                        @error('opd_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-dark">Nama Penanggung Jawab</label>
                        <input type="text" name="pemegang" class="form-control @error('pemegang') is-invalid @enderror" value="{{ old('pemegang') }}" placeholder="Contoh: Budi Santoso" required>
                        @error('pemegang') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-dark">Status Kendaraan</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}" {{ old('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="admin-card p-4">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary py-2 fw-medium d-flex justify-content-center align-items-center gap-2">
                            <i class="bi bi-save"></i> Simpan Data Kendaraan
                        </button>
                        <a href="{{ route('vehicles.index') }}" class="btn btn-light border py-2 fw-medium text-secondary">
                            Batal & Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const noPolisiInput = document.querySelector('input[name="no_polisi"]');
        if (noPolisiInput) {
            noPolisiInput.addEventListener('input', function(e) {
                // Hapus titik dan paksa huruf besar
                this.value = this.value.replace(/\./g, '').toUpperCase();
            });
        }
    });
</script>
@endpush
