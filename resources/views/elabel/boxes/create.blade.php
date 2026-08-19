@extends('layouts.app')

@section('title', 'Tambah Box BPKB - eLABEL')

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.dashboard') }}" class="text-decoration-none text-secondary">eLABEL</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('elabel.boxes.index') }}" class="text-decoration-none text-secondary">Box BPKB</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Tambah Box</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-navy mb-0">Tambah Box BPKB Kendaraan {{ $vehicleLabel }}</h4>
        </div>
        <div>
            <a href="{{ route('elabel.boxes.index', ['type' => strtolower($vehicleType ?? '')]) }}" class="btn btn-light border fw-medium">
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
            <form action="{{ route('elabel.boxes.store') }}" method="POST">
                @csrf

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Jenis Kendaraan <span class="text-danger">*</span></label>
                        @if(!empty($vehicleType))
                            <input type="hidden" name="vehicle_type" id="vehicle_type" value="{{ $vehicleType }}">
                            <input type="text" class="form-control bg-light" value="{{ $vehicleLabel }}" readonly>
                        @else
                            <select name="vehicle_type" id="vehicle_type" class="form-select" required>
                                <option value="">-- Pilih Jenis Kendaraan --</option>
                                <option value="R4" {{ old('vehicle_type') === 'R4' ? 'selected' : '' }}>R4 (Mobil)</option>
                                <option value="R2" {{ old('vehicle_type') === 'R2' ? 'selected' : '' }}>R2 (Motor)</option>
                            </select>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Kode Box <span class="text-danger">*</span></label>
                        <input type="text" name="box_code" id="box_code" class="form-control" value="{{ old('box_code', $nextBoxCodes[$vehicleType] ?? '') }}" placeholder="Pilih jenis kendaraan terlebih dahulu" required>
                        <div class="form-text text-muted small">Kode box terisi otomatis setelah jenis kendaraan dipilih. Contoh: R4-01, R2-01.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Lokasi Ruang / Rak</label>
                        <input type="text" name="location" class="form-control" value="{{ old('location') }}" placeholder="Rak A1 / B2 / Gedung BPKAD">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Tahun yang Ditampung <span class="text-danger">*</span></label>
                        <input type="text" name="years" class="form-control" value="{{ old('years') }}" placeholder="Contoh: 2021-2022-2023 atau 2024" required>
                        <div class="form-text text-muted small">Pisahkan tahun dengan tanda strip (-).</div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-4">
                    <a href="{{ route('elabel.boxes.index') }}" class="btn btn-light border">Batal</a>
                    <button type="submit" class="btn btn-primary fw-semibold px-4"><i class="bi bi-save me-1"></i> Simpan Box</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const vehicleTypeInput = document.getElementById('vehicle_type');
    const boxCodeInput = document.getElementById('box_code');
    const nextBoxCodes = @json($nextBoxCodes ?? []);

    if (!boxCodeInput || !vehicleTypeInput) return;

    let lastAutoValue = '';
    const buildBoxCode = function (vType) {
        if (!vType) return '';
        return nextBoxCodes[vType] || (vType + '-01');
    };

    const syncBoxCode = function (vType) {
        const nextValue = buildBoxCode(vType);
        const currentValue = boxCodeInput.value.trim();
        if (currentValue === '' || currentValue === lastAutoValue) {
            boxCodeInput.value = nextValue;
            lastAutoValue = nextValue;
        }
    };

    if (vehicleTypeInput.tagName === 'SELECT') {
        vehicleTypeInput.addEventListener('change', function () {
            syncBoxCode(vehicleTypeInput.value);
        });
    }
});
</script>
@endsection
