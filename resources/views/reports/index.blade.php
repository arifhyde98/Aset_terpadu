@extends('layouts.app')

@section('title', 'Modul Laporan Aset')

@section('content')
<div class="container-fluid px-0">
    
    <!-- BREADCRUMB & PAGE HEADER -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">Laporan Aset</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-navy mb-0">Laporan & Rekapitulasi Aset</h3>
        </div>
        @if(auth()->user()->role->value === 'superadmin')
            <div class="mt-2 mt-md-0">
                <a href="{{ route('reports.settings.index') }}" class="btn btn-sm btn-outline-primary px-3 fw-semibold">
                    <i class="bi bi-gear-fill me-1"></i> Pengaturan Cetak
                </a>
            </div>
        @endif
    </div>

    <!-- AREA RINGKASAN METRIK (3 STAT CARDS RESMI) -->
    <div class="row g-3 mb-4">
        <div class="col-sm-12 col-md-4">
            <x-stat-card 
                title="Total Unit Kendaraan" 
                :value="$summary['total_unit']" 
                icon="car-front" 
                gradient="primary" 
                subtitle="Terdaftar di E-RANDIS" 
            />
        </div>
        <div class="col-sm-6 col-md-4">
            <x-stat-card 
                title="Layak Operasional" 
                :value="$summary['layak_jalan']" 
                icon="check-circle" 
                gradient="success" 
                subtitle="Kondisi Fisik Baik" 
            />
        </div>
        <div class="col-sm-6 col-md-4">
            <x-stat-card 
                title="Masalah Dokumen" 
                :value="$summary['surat_mati']" 
                icon="exclamation-triangle" 
                gradient="danger" 
                subtitle="STNK Kedaluwarsa / Hilang" 
            />
        </div>
    </div>

    <!-- AREA FORM FILTER MODULAR (NAVY, PUTIH, ABU-ABU) -->
    <div class="card report-filter-card mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-funnel-fill text-navy fs-5"></i>
                <h5 class="fw-bold text-navy mb-0">Saring Kriteria Laporan</h5>
            </div>
        </div>
        <div class="card-body p-4 bg-white">
            <form id="filter-form" onsubmit="submitFilterForm(event)">
                <input type="hidden" name="sort_by" id="sort_by" value="">
                <input type="hidden" name="sort_order" id="sort_order" value="asc">
                <div class="row g-3">
                    
                    <!-- 1. Pilihan Jenis Laporan -->
                    <div class="col-md-3">
                        <label for="type" class="form-label fw-semibold text-secondary small">Jenis Laporan</label>
                        <select name="type" id="type" class="form-select border-slate" required onchange="handleTypeChange()">
                            @foreach($reportTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 2. Pilihan Kondisi Fisik -->
                    <div class="col-md-3">
                        <label for="kondisi" class="form-label fw-semibold text-secondary small">Kondisi Fisik Aset</label>
                        <select name="kondisi" id="kondisi" class="form-select border-slate">
                            <option value="">-- Semua Kondisi --</option>
                            @foreach(\App\Enums\VehicleCondition::cases() as $case)
                                <option value="{{ $case->value }}">{{ $case->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 3. Pilihan OPD / Instansi (Hanya Admin/Superadmin) -->
                    @if(!$isOpd)
                        <div class="col-md-3" id="opd-filter-group">
                            <label for="opd_id" class="form-label fw-semibold text-secondary small">Instansi Pengelola (OPD)</label>
                            <select name="opd_id" id="opd_id" class="form-select border-slate">
                                <option value="">-- Semua Instansi (Global) --</option>
                                @foreach($opds as $opd)
                                    <option value="{{ $opd->id }}">{{ $opd->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <!-- 4. Pilihan Tahun Perolehan -->
                    <div class="col-md-3">
                        <label for="tahun" class="form-label fw-semibold text-secondary small">Tahun Perolehan</label>
                        <select name="tahun" id="tahun" class="form-select border-slate">
                            <option value="">-- Semua Tahun --</option>
                            @for($y = now()->year; $y >= 2010; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                </div>

                <!-- Tombol Eksekusi Filter -->
                <div class="report-filter-actions d-flex justify-content-end gap-2 mt-4 pt-3 border-top border-light">
                    <button
                        type="button"
                        onclick="resetFilterForm()"
                        class="btn btn-light border bg-white report-icon-btn shadow-sm"
                        title="Reset filter"
                        aria-label="Reset filter"
                    >
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                    <button type="submit" class="btn btn-primary fw-semibold d-inline-flex align-items-center gap-2 shadow-sm">
                        <i class="bi bi-search"></i>
                        <span>Tampilkan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- AREA HASIL PRATINJAU DILENGKAPI SPINNER (AJAX RENDERED BINDING) -->
    <div id="report-table-wrapper">
        <div class="card border border-light-subtle rounded-3 overflow-hidden shadow-sm">
            <div class="py-5 text-center bg-white">
                <div class="py-4">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                        <i class="bi bi-funnel text-secondary opacity-50 fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-navy mb-1">Filter Belum Diterapkan</h5>
                    <p class="text-secondary mb-0 small">Silakan pilih kriteria filter di atas lalu klik "Tampilkan Pratinjau".</p>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Otomatis picu penarikan data pertama kali halaman dimuat untuk kenyamanan UX
        fetchPreview();

        // Daftarkan penanganan AJAX paginasi menggunakan event delegation
        document.getElementById('report-table-wrapper').addEventListener('click', function(e) {
            const paginationLink = e.target.closest('.pagination a, .pagination-modern a');
            if (paginationLink) {
                e.preventDefault();
                const url = paginationLink.getAttribute('href');
                if (url) {
                    fetchPreview(url);
                }
            }
        });
    });

    /**
     * Mengambil pratinjau tabel laporan secara asinkron (AJAX HTML Partial).
     *
     * @param {string|null} url URL kueri opsional (untuk navigasi paginasi)
     */
    function fetchPreview(url = null) {
        const wrapper = document.getElementById('report-table-wrapper');
        const form = document.getElementById('filter-form');
        
        // Tentukan target URL (jika paginasi memakai URL bawaan Laravel links)
        let targetUrl = url;
        if (targetUrl) {
            try {
                // Sinkronkan form inputs dengan parameter di URL jika ada
                const urlObj = new URL(targetUrl, window.location.origin || 'http://localhost');
                const sortByParam = urlObj.searchParams.get('sort_by');
                const sortOrderParam = urlObj.searchParams.get('sort_order');
                if (sortByParam !== null) {
                    document.getElementById('sort_by').value = sortByParam;
                }
                if (sortOrderParam !== null) {
                    document.getElementById('sort_order').value = sortOrderParam;
                }
            } catch (e) {
                console.error('Gagal mensinkronisasi URL paginasi:', e);
            }
        } else {
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            targetUrl = "{{ route('reports.preview') }}?" + params.toString();
        }

        // Tampilkan visual loading spinner yang premium
        wrapper.innerHTML = `
            <div class="card border border-light-subtle rounded-3 overflow-hidden bg-white shadow-sm">
                <div class="preview-loading">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 2.5rem; height: 2.5rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h6 class="fw-bold text-navy mb-1">Sedang Menyusun Laporan...</h6>
                    <p class="text-secondary small mb-0">Sistem sedang mengagregasi data aset kendaraan dinas Bapenda.</p>
                </div>
            </div>
        `;

        // Lakukan pemanggilan Ajax via Fetch API
        fetch(targetUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Terjadi kesalahan saat menyusun pratinjau laporan.');
            }
            return response.text();
        })
        .then(html => {
            // Pasang HTML parsial yang dirender dari server
            wrapper.innerHTML = html;

            // Gulir secara halus agar tabel pratinjau berada di tengah layar di perangkat mobile
            if (url) {
                wrapper.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        })
        .catch(error => {
            console.error(error);
            wrapper.innerHTML = `
                <div class="card border border-danger-subtle rounded-3 bg-white p-5 text-center shadow-sm">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-1 mb-3"></i>
                    <h5 class="fw-bold text-danger mb-1">Gagal Memuat Pratinjau</h5>
                    <p class="text-secondary small mb-3">${error.message}</p>
                    <div>
                        <button type="button" onclick="fetchPreview()" class="btn btn-primary btn-sm fw-semibold d-inline-flex align-items-center gap-2">
                            <i class="bi bi-arrow-clockwise"></i>
                            <span>Coba Lagi</span>
                        </button>
                    </div>
                </div>
            `;
        });
    }

    /**
     * Penanganan submit form filter.
     */
    function submitFilterForm(event) {
        event.preventDefault();
        fetchPreview();
    }

    /**
     * Penanganan reset form filter.
     */
    function resetFilterForm() {
        const form = document.getElementById('filter-form');
        form.reset();
        document.getElementById('sort_by').value = '';
        document.getElementById('sort_order').value = 'asc';
        fetchPreview();
    }

    /**
     * Memperbarui parameter pengurutan tersembunyi dan memicu reload data.
     *
     * @param {string} fieldName Nama kolom untuk pengurutan
     */
    function sortByField(fieldName) {
        const sortByInput = document.getElementById('sort_by');
        const sortOrderInput = document.getElementById('sort_order');
        
        if (sortByInput.value === fieldName) {
            sortOrderInput.value = sortOrderInput.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortByInput.value = fieldName;
            sortOrderInput.value = 'asc';
        }
        
        fetchPreview();
    }

    /**
     * Aksi pemicu Ekspor ke Excel (Dinas / Global terproteksi).
     */
    function exportExcel() {
        const form = document.getElementById('filter-form');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData).toString();
        
        window.location.href = "{{ route('reports.export') }}?" + params;
    }

    /**
     * Aksi pemicu Ekspor PDF (Server-Side mPDF).
     */
    function exportPdf() {
        const form = document.getElementById('filter-form');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData).toString();
        
        window.open("{{ route('reports.pdf') }}?" + params, '_blank');
    }

    /**
     * Aksi pemicu tab Cetak Laporan ramah tinta printer.
     */
    function printReport() {
        const form = document.getElementById('filter-form');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData).toString();
        
        window.open("{{ route('reports.print') }}?" + params, '_blank');
    }

    /**
     * Penanganan dinamis jika tipe laporan berubah (antisipasi filter kustom di masa depan).
     */
    function handleTypeChange() {
        // Tipe laporan terpilih
        const type = document.getElementById('type').value;
        
        // Kita bisa menyembunyikan atau memunculkan filter tertentu jika dibutuhkan nanti
        const kondisiFilter = document.getElementById('kondisi');
        if (type === 'document') {
            // Contoh: Laporan dokumen mungkin tidak butuh filter kondisi fisik tertentu pada kasus khusus,
            // tapi saat ini kita biarkan aktif secara default
        }
    }
</script>
@endpush
@endsection
