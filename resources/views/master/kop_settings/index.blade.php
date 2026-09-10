@extends('layouts.app')

@section('content')
<style>
    .kop-shell {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(340px, 0.8fr);
        gap: 1.5rem;
    }
    @media (max-width: 991.98px) { .kop-shell { grid-template-columns: 1fr; } }

    .kop-card {
        border-radius: 1.25rem;
        border: 1px solid var(--border-color, rgba(0,0,0,0.08));
        background: var(--bs-card-bg, #ffffff);
    }
    .kop-preview-paper {
        background: #ffffff;
        border-radius: 1.25rem;
        padding: 1.5rem;
        border: 1px solid var(--border-color, rgba(0,0,0,0.12));
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    }
    .kop-header-preview {
        display: grid;
        grid-template-columns: 70px 1fr;
        gap: 1rem;
        align-items: center;
        padding-bottom: 0.75rem;
        border-bottom: 4px double #0f172a;
        margin-bottom: 1rem;
    }
    .kop-title-instansi { font-size: 0.95rem; font-weight: 800; color: #0f172a; text-transform: uppercase; }
    .kop-title-unit { font-size: 1.05rem; font-weight: 800; color: #1e3a8a; text-transform: uppercase; }
    .kop-title-sub { font-size: 0.82rem; color: #475569; }
</style>

<div class="container-fluid px-0">
    <!-- Flash Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary fw-semibold px-2.5 py-1 rounded-pill" style="font-size: 0.75rem;">
                    <i class="bi bi-shield-check me-1"></i> MASTER & SISTEM
                </span>
                <span class="text-secondary small">&bull;</span>
                <span class="text-secondary small">Identitas Dokumen Resmi</span>
            </div>
            <h2 class="fw-bold mb-1">Master KOP Surat Pemda</h2>
            <p class="text-secondary mb-0 small">Atur identitas resmi instansi, KOP surat, dan pejabat penandatangan untuk ekspor laporan PDF</p>
        </div>
    </div>

    <div class="kop-shell">
        <!-- Form Pengaturan -->
        <div class="card kop-card shadow-sm p-4">
            <h5 class="fw-bold text-body mb-3"><i class="bi bi-sliders text-primary me-2"></i>Form Pengaturan KOP</h5>
            <form action="{{ route('master.kop-settings.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary mb-1">Nama Instansi Pemda</label>
                        <input type="text" name="kop_nama_instansi" class="form-control bg-body-tertiary border-0" value="{{ $settings['kop_nama_instansi'] ?? $defaults['kop_nama_instansi'] }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary mb-1">Nama Unit / OPD</label>
                        <input type="text" name="kop_nama_unit" class="form-control bg-body-tertiary border-0" value="{{ $settings['kop_nama_unit'] ?? $defaults['kop_nama_unit'] }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold text-secondary mb-1">Sub Unit / Bidang</label>
                        <input type="text" name="kop_subunit" class="form-control bg-body-tertiary border-0" value="{{ $settings['kop_subunit'] ?? $defaults['kop_subunit'] }}">
                    </div>
                    <div class="col-md-7">
                        <label class="form-label small fw-semibold text-secondary mb-1">Alamat Kantor</label>
                        <textarea name="kop_alamat" class="form-control bg-body-tertiary border-0" rows="2">{{ $settings['kop_alamat'] ?? $defaults['kop_alamat'] }}</textarea>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold text-secondary mb-1">Kontak & Website</label>
                        <textarea name="kop_kontak" class="form-control bg-body-tertiary border-0" rows="2">{{ $settings['kop_kontak'] ?? $defaults['kop_kontak'] }}</textarea>
                    </div>
                    
                    <div class="col-12"><hr class="text-secondary opacity-25 my-1"></div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary mb-1">Nama Laporan Bawaan</label>
                        <input type="text" name="kop_nama_laporan_aset" class="form-control bg-body-tertiary border-0" value="{{ $settings['kop_nama_laporan_aset'] ?? $defaults['kop_nama_laporan_aset'] }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary mb-1">Upload Logo KOP (PNG/JPG)</label>
                        <input type="file" name="kop_logo" class="form-control bg-body-tertiary border-0">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold text-secondary mb-1">Footer Dokumen PDF</label>
                        <input type="text" name="kop_footer" class="form-control bg-body-tertiary border-0" value="{{ $settings['kop_footer'] ?? $defaults['kop_footer'] }}">
                    </div>

                    <div class="col-12"><hr class="text-secondary opacity-25 my-2"></div>

                    <!-- PENANDA TANGAN 1 (SISI KIRI) -->
                    <div class="col-12">
                        <div class="p-3 rounded-3 border bg-body-secondary bg-opacity-25">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="badge bg-primary text-white rounded-pill px-2 py-1 small">1</span>
                                <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-person-badge me-1"></i> Penanda Tangan 1 (Sisi Kiri - Bidang / Teknis)</h6>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-12">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Jabatan Pejabat 1</label>
                                    <input type="text" name="kop_pejabat1_jabatan" class="form-control bg-white border" placeholder="Contoh: KEPALA BIDANG ASET DAERAH" value="{{ $settings['kop_pejabat1_jabatan'] ?? ($defaults['kop_pejabat1_jabatan'] ?? '') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Nama Pejabat 1 (Beserta Gelar)</label>
                                    <input type="text" name="kop_pejabat1_nama" class="form-control bg-white border" placeholder="Nama Pejabat" value="{{ $settings['kop_pejabat1_nama'] ?? ($defaults['kop_pejabat1_nama'] ?? '') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary mb-1">NIP Pejabat 1</label>
                                    <input type="text" name="kop_pejabat1_nip" class="form-control bg-white border" placeholder="NIP. ..." value="{{ $settings['kop_pejabat1_nip'] ?? ($defaults['kop_pejabat1_nip'] ?? '') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PENANDA TANGAN 2 (SISI KANAN) -->
                    <div class="col-12">
                        <div class="p-3 rounded-3 border bg-body-secondary bg-opacity-25">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="badge bg-success text-white rounded-pill px-2 py-1 small">2</span>
                                <h6 class="fw-bold mb-0 text-success"><i class="bi bi-person-check-fill me-1"></i> Penanda Tangan 2 (Sisi Kanan - Kepala Badan / Pengesah)</h6>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Kota Tempat TTD</label>
                                    <input type="text" name="kop_kota_ttd" class="form-control bg-white border" placeholder="Banawa" value="{{ $settings['kop_kota_ttd'] ?? $defaults['kop_kota_ttd'] }}">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Jabatan Pejabat 2</label>
                                    <input type="text" name="kop_pejabat2_jabatan" class="form-control bg-white border" placeholder="Contoh: KEPALA BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH" value="{{ $settings['kop_pejabat2_jabatan'] ?? ($settings['kop_pejabat_jabatan'] ?? ($defaults['kop_pejabat2_jabatan'] ?? '')) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Nama Pejabat 2 (Beserta Gelar)</label>
                                    <input type="text" name="kop_pejabat2_nama" class="form-control bg-white border" placeholder="Nama Pejabat" value="{{ $settings['kop_pejabat2_nama'] ?? ($settings['kop_pejabat_nama'] ?? ($defaults['kop_pejabat2_nama'] ?? '')) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-secondary mb-1">NIP Pejabat 2</label>
                                    <input type="text" name="kop_pejabat2_nip" class="form-control bg-white border" placeholder="NIP. ..." value="{{ $settings['kop_pejabat2_nip'] ?? ($settings['kop_pejabat_nip'] ?? ($defaults['kop_pejabat2_nip'] ?? '')) }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-2 border-top text-end">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="bi bi-save me-1"></i> Simpan Master KOP
                    </button>
                </div>
            </form>
        </div>

        <!-- Pratinjau KOP -->
        <div>
            <div class="card kop-card shadow-sm p-4">
                <h6 class="fw-bold text-body mb-3"><i class="bi bi-eye text-success me-2"></i>Pratinjau KOP Surat</h6>
                <div class="kop-preview-paper">
                    <div class="kop-header-preview">
                        <div class="text-center">
                            @if(!empty($settings['kop_logo']))
                                <img src="{{ asset('storage/' . $settings['kop_logo']) }}" style="max-height: 65px;" alt="Logo KOP">
                            @else
                                <div class="badge bg-primary-subtle text-primary p-2">LOGO</div>
                            @endif
                        </div>
                        <div class="text-center">
                            <div class="kop-title-instansi">{{ $settings['kop_nama_instansi'] ?? $defaults['kop_nama_instansi'] }}</div>
                            <div class="kop-title-unit">{{ $settings['kop_nama_unit'] ?? $defaults['kop_nama_unit'] }}</div>
                            <div class="kop-title-sub fw-semibold">{{ $settings['kop_subunit'] ?? $defaults['kop_subunit'] }}</div>
                            <div class="kop-title-sub">{{ $settings['kop_alamat'] ?? $defaults['kop_alamat'] }}</div>
                            <div class="kop-title-sub" style="font-size: 0.72rem;">{{ $settings['kop_kontak'] ?? $defaults['kop_kontak'] }}</div>
                        </div>
                    </div>

                    <div class="text-center my-3">
                        <div class="fw-bold text-decoration-underline text-body fs-6">{{ $settings['kop_nama_laporan_aset'] ?? $defaults['kop_nama_laporan_aset'] }}</div>
                    </div>

                    <!-- Dual Signatories Preview -->
                    <div class="mt-4 pt-3 border-top" style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; text-align: center; font-size: 0.75rem; line-height: 1.3;">
                        <div>
                            <div class="text-muted" style="visibility: hidden;">&nbsp;</div>
                            <div class="fw-bold text-uppercase text-body">{{ $settings['kop_pejabat1_jabatan'] ?? ($defaults['kop_pejabat1_jabatan'] ?? 'KEPALA BIDANG ASET DAERAH') }}</div>
                            <div style="height: 60px;"></div>
                            <div class="fw-bold text-decoration-underline text-body">{{ $settings['kop_pejabat1_nama'] ?? ($defaults['kop_pejabat1_nama'] ?? 'YENI SJ AMIR, SH.MSi') }}</div>
                            <div class="text-secondary">{{ $settings['kop_pejabat1_nip'] ?? ($defaults['kop_pejabat1_nip'] ?? 'NIP.') }}</div>
                        </div>
                        <div>
                            <div class="text-secondary">{{ $settings['kop_kota_ttd'] ?? 'Banawa' }}, {{ date('d-m-Y') }}</div>
                            <div class="fw-bold text-uppercase text-body">{{ $settings['kop_pejabat2_jabatan'] ?? ($settings['kop_pejabat_jabatan'] ?? ($defaults['kop_pejabat2_jabatan'] ?? 'KEPALA BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH')) }}</div>
                            <div style="height: 60px;"></div>
                            <div class="fw-bold text-decoration-underline text-body">{{ $settings['kop_pejabat2_nama'] ?? ($settings['kop_pejabat_nama'] ?? ($defaults['kop_pejabat2_nama'] ?? 'YENI SJ AMIR, SH.MSi')) }}</div>
                            <div class="text-secondary">{{ $settings['kop_pejabat2_nip'] ?? ($settings['kop_pejabat_nip'] ?? ($defaults['kop_pejabat2_nip'] ?? 'NIP.')) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
