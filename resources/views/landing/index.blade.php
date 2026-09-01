<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="SIPAT Terpadu - Portal Informasi & Pencarian Aset Pemerintah Daerah: Kendaraan Dinas, Sertifikat Tanah, dan Arsip Berkas Kepemilikan.">
    
    <title>{{ $settings['site_name'] ?? 'SIPAT TERPADU' }} | Portal Informasi & Pencarian Aset Daerah</title>
    
    <link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.ico') }}">
    
    <!-- Vite Style & Scripts -->
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
</head>
<body data-bs-spy="scroll" data-bs-target="#navbar-main" class="landing-body">

    <!-- 1. Header / Navbar -->
    @include('landing.components.header')

    <!-- 2. Hero Section -->
    @include('landing.components.hero')

    <!-- 3. Unified Asset Search (Main Focal Point) -->
    @include('landing.components.unified-search')

    <!-- 4. Quick Services (Shortcuts) -->
    @include('landing.components.quick-services')

    <!-- 5. Live Statistics Section -->
    @include('landing.components.statistics')

    <!-- 6. Land Certification Overview -->
    @include('landing.components.certification-overview')

    <!-- 7. About SIPAT Terpadu -->
    @include('landing.components.about')

    <!-- 8. Footer -->
    @include('landing.components.footer')

    <!-- Interactive Client Script for Unified Search & Quick Services -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Navbar Scroll Effect
            const navbar = document.querySelector('.navbar');
            window.addEventListener('scroll', function () {
                if (window.scrollY > 30) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });

            // Tab Switching Helpers
            const searchTabs = document.querySelectorAll('#unifiedSearchTabs button[data-bs-toggle="pill"]');
            const resultsContainer = document.getElementById('unifiedSearchResultsContainer');
            const resultsBody = document.getElementById('searchResultsBody');
            const resultHeading = document.getElementById('resultHeading');
            const resultSummaryText = document.getElementById('resultSummaryText');
            const resultModuleBadge = document.getElementById('resultModuleBadge');
            const dismissResultsBtn = document.getElementById('dismissResultsBtn');

            // Quick Services Button / Link Handler
            const quickServiceTriggers = document.querySelectorAll('.quick-service-btn, .quick-service-link');
            quickServiceTriggers.forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const targetTab = this.dataset.targetTab;
                    if (!targetTab) return;

                    const tabButton = document.getElementById(`tab-${targetTab}-btn`);
                    if (tabButton) {
                        const tabInstance = bootstrap.Tab.getOrCreateInstance(tabButton);
                        tabInstance.show();

                        // Scroll smooth to search card
                        const searchSection = document.getElementById('search-section');
                        if (searchSection) {
                            searchSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }

                        // Focus input field
                        setTimeout(() => {
                            const input = document.getElementById(`${targetTab}QueryInput`);
                            if (input) input.focus();
                        }, 400);
                    }
                });
            });

            // Clear Input Buttons
            document.querySelectorAll('.clear-input-btn').forEach(btn => {
                const input = btn.previousElementSibling;
                if (!input) return;

                input.addEventListener('input', () => {
                    if (input.value.trim() !== '') {
                        btn.classList.remove('d-none');
                    } else {
                        btn.classList.add('d-none');
                    }
                });

                btn.addEventListener('click', () => {
                    input.value = '';
                    btn.classList.add('d-none');
                    input.focus();
                });
            });

            // Dynamic Placeholder Switcher per Domain & Criteria
            const setupDynamicPlaceholder = (selectId, inputId, placeholderMap) => {
                const select = document.getElementById(selectId);
                const input = document.getElementById(inputId);
                if (!select || !input) return;

                select.addEventListener('change', function () {
                    const placeholder = placeholderMap[this.value] || 'Masukkan kata kunci...';
                    input.placeholder = placeholder;
                });
            };

            setupDynamicPlaceholder('vehicleSearchBy', 'vehicleQueryInput', {
                'no_polisi': 'Contoh: DN 1234 XX atau B 1234 XX',
                'nibar': 'Contoh NIBAR: 02.01.01.001.0001',
                'kode_barang': 'Contoh: Toyota Avanza, Mitsubishi, dll.',
                'all': 'Masukkan nomor polisi / NIBAR / merk...'
            });

            setupDynamicPlaceholder('landSearchBy', 'landQueryInput', {
                'nibar': 'Contoh NIBAR: 02.01.01.001.0001',
                'no_sertifikat': 'Contoh: Hak Pakai No. 12 / Banawa',
                'nib_nama': 'Contoh: Kantor Bupati, Lapangan, dll.',
                'all': 'Masukkan NIBAR / no sertifikat / nama aset...'
            });

            setupDynamicPlaceholder('archiveSearchBy', 'archiveQueryInput', {
                'nibar': 'Contoh NIBAR: 02.01.01.001.0001',
                'no_dokumen': 'Contoh: 12345/BPKB/2020 atau Sertifikat No. 10',
                'kode_barang': 'Contoh: DN 1234 XX atau kode barang',
                'all': 'Masukkan NIBAR / no dokumen / plat...'
            });

            // Dismiss Results Button
            if (dismissResultsBtn) {
                dismissResultsBtn.addEventListener('click', () => {
                    resultsContainer.classList.add('d-none');
                });
            }

            // HTML Sanitizer
            const escapeHtml = function (str) {
                return String(str ?? '').replace(/[&<>"']/g, function (m) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
                });
            };

            // Loading Skeleton HTML Generator
            const getSkeletonHtml = () => `
                <div class="d-flex flex-column gap-3 py-2">
                    <div class="p-3 bg-light rounded-3 border border-light-subtle skeleton-shimmer">
                        <div class="skeleton-block mb-2" style="width: 35%; height: 20px;"></div>
                        <div class="skeleton-block mb-3" style="width: 60%; height: 16px;"></div>
                        <div class="row g-2">
                            <div class="col-md-4"><div class="skeleton-block" style="height: 38px;"></div></div>
                            <div class="col-md-4"><div class="skeleton-block" style="height: 38px;"></div></div>
                            <div class="col-md-4"><div class="skeleton-block" style="height: 38px;"></div></div>
                        </div>
                    </div>
                    <div class="p-3 bg-light rounded-3 border border-light-subtle skeleton-shimmer">
                        <div class="skeleton-block mb-2" style="width: 40%; height: 20px;"></div>
                        <div class="skeleton-block mb-3" style="width: 50%; height: 16px;"></div>
                        <div class="row g-2">
                            <div class="col-md-4"><div class="skeleton-block" style="height: 38px;"></div></div>
                            <div class="col-md-4"><div class="skeleton-block" style="height: 38px;"></div></div>
                            <div class="col-md-4"><div class="skeleton-block" style="height: 38px;"></div></div>
                        </div>
                    </div>
                </div>
            `;

            // Empty State HTML Generator
            const getEmptyStateHtml = (query, moduleName) => `
                <div class="text-center py-5">
                    <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle mb-3" style="width: 64px; height: 64px;">
                        <i class="bi bi-search fs-3 text-secondary"></i>
                    </div>
                    <h5 class="fw-bold text-navy mb-1">Data Tidak Ditemukan</h5>
                    <p class="text-secondary small mx-auto mb-3" style="max-width: 480px;">
                        Tidak ada data ${escapeHtml(moduleName)} yang cocok dengan kata kunci <strong>"${escapeHtml(query)}"</strong> atau filter yang dipilih.
                    </p>
                    <div class="d-inline-flex flex-wrap justify-content-center gap-2 small text-secondary">
                        <span class="badge bg-light text-dark border">💡 Tips: Periksa kembali nomor plat, NIBAR, atau gunakan kata kunci yang lebih umum.</span>
                    </div>
                </div>
            `;

            // Error State HTML Generator
            const getErrorStateHtml = () => `
                <div class="text-center py-5">
                    <div class="d-inline-flex align-items-center justify-content-center bg-danger-subtle text-danger rounded-circle mb-3" style="width: 64px; height: 64px;">
                        <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-danger mb-1">Terjadi Kendala Koneksi</h5>
                    <p class="text-secondary small mx-auto mb-0" style="max-width: 440px;">
                        Gagal memuat hasil pencarian dari server. Silakan periksa koneksi internet Anda atau coba beberapa saat lagi.
                    </p>
                </div>
            `;

            // RENDER VEHICLES RESULTS
            const renderVehicleResults = (items, query) => {
                if (!items || items.length === 0) {
                    resultsBody.innerHTML = getEmptyStateHtml(query, 'Kendaraan Dinas');
                    return;
                }

                let html = '<div class="row g-3">';
                items.forEach(v => {
                    const photos = v.foto_kendaraan;
                    const hasPhotos = Array.isArray(photos) && photos.length > 0;
                    const photoUrl = hasPhotos ? `/storage/${photos[0]}` : null;

                    html += `
                        <div class="col-lg-6">
                            <div class="p-3 p-md-4 rounded-4 bg-light border border-light-subtle h-100 d-flex flex-column justify-content-between hover-elevate transition-all">
                                <div>
                                    <!-- Header Info -->
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                        <div>
                                            <span class="badge bg-navy text-white fs-6 px-3 py-1 font-monospace rounded-3 mb-1">
                                                ${escapeHtml(v.no_polisi)}
                                            </span>
                                            <h6 class="fw-bold text-navy mb-0 fs-6 mt-1">${escapeHtml(v.nama)}</h6>
                                            <div class="small text-secondary font-monospace" style="font-size: 0.75rem;">
                                                Register / NIBAR: ${escapeHtml(v.nomor_register || '-')}
                                            </div>
                                        </div>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small fw-medium">
                                            ${escapeHtml(v.status)}
                                        </span>
                                    </div>

                                    <!-- Grid Info -->
                                    <div class="row g-2 mb-3 small">
                                        <div class="col-sm-6">
                                            <div class="p-2 bg-white rounded-2 border border-light-subtle h-100">
                                                <div class="text-secondary" style="font-size: 0.75rem;">OPD / Instansi</div>
                                                <div class="fw-semibold text-dark text-truncate" title="${escapeHtml(v.opd)}">${escapeHtml(v.opd)}</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="p-2 bg-white rounded-2 border border-light-subtle h-100">
                                                <div class="text-secondary" style="font-size: 0.75rem;">Pemegang / Pengguna</div>
                                                <div class="fw-semibold text-dark text-truncate" title="${escapeHtml(v.pemegang)}">${escapeHtml(v.pemegang)}</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="p-2 bg-white rounded-2 border border-light-subtle h-100">
                                                <div class="text-secondary" style="font-size: 0.75rem;">Jenis & Tahun</div>
                                                <div class="fw-semibold text-dark">${escapeHtml(v.jenis || '-')} (${escapeHtml(v.tahun || '-')})</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="p-2 bg-white rounded-2 border border-light-subtle h-100">
                                                <div class="text-secondary" style="font-size: 0.75rem;">Kondisi Fisik</div>
                                                <div class="fw-semibold text-dark">${escapeHtml(v.kondisi)}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cross-Module Status Badge (EARSIP / BPKB) -->
                                <div class="pt-2 border-top border-light-subtle d-flex flex-wrap align-items-center justify-content-between gap-2">
                                    <div class="small">
                                        <span class="badge ${v.arsip_bpkb.tersedia ? 'bg-amber-subtle text-dark border border-warning-subtle' : 'bg-secondary-subtle text-secondary'} rounded-pill px-2 py-1">
                                            <i class="bi bi-folder-check me-1"></i>${escapeHtml(v.arsip_bpkb.status_label)}
                                        </span>
                                    </div>
                                    ${photoUrl ? `
                                        <a href="${photoUrl}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2 small" style="font-size: 0.75rem;">
                                            <i class="bi bi-image me-1"></i>Foto Fisik
                                        </a>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                resultsBody.innerHTML = html;
            };

            // RENDER LAND RESULTS
            const renderLandResults = (items, query) => {
                if (!items || items.length === 0) {
                    resultsBody.innerHTML = getEmptyStateHtml(query, 'Sertifikat Tanah');
                    return;
                }

                let html = '<div class="row g-3">';
                items.forEach(item => {
                    html += `
                        <div class="col-lg-6">
                            <div class="p-3 p-md-4 rounded-4 bg-light border border-light-subtle h-100 d-flex flex-column justify-content-between hover-elevate transition-all">
                                <div>
                                    <!-- Header Info -->
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                        <div>
                                            <div class="small text-secondary font-monospace" style="font-size: 0.75rem;">
                                                NIBAR: <strong class="text-navy">${escapeHtml(item.nibar)}</strong>
                                            </div>
                                            <h6 class="fw-bold text-navy mb-0 fs-6 mt-1">${escapeHtml(item.nama_aset)}</h6>
                                            <div class="small text-secondary">${escapeHtml(item.peruntukan)}</div>
                                        </div>
                                        <span class="badge ${escapeHtml(item.status_badge_class)} px-2 py-1 rounded-pill small fw-medium text-nowrap">
                                            ${escapeHtml(item.status_sertifikasi)}
                                        </span>
                                    </div>

                                    <!-- Grid Info -->
                                    <div class="row g-2 mb-3 small">
                                        <div class="col-sm-6">
                                            <div class="p-2 bg-white rounded-2 border border-light-subtle h-100">
                                                <div class="text-secondary" style="font-size: 0.75rem;">OPD Pengguna</div>
                                                <div class="fw-semibold text-dark text-truncate" title="${escapeHtml(item.opd)}">${escapeHtml(item.opd)}</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="p-2 bg-white rounded-2 border border-light-subtle h-100">
                                                <div class="text-secondary" style="font-size: 0.75rem;">Luas Bidang</div>
                                                <div class="fw-semibold text-dark">${escapeHtml(item.luas)}</div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="p-2 bg-white rounded-2 border border-light-subtle">
                                                <div class="text-secondary" style="font-size: 0.75rem;">Lokasi / Alamat</div>
                                                <div class="fw-semibold text-dark text-truncate" title="${escapeHtml(item.alamat)}">
                                                    <i class="bi bi-geo-alt text-danger me-1"></i>${escapeHtml(item.alamat)}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cross-Module Status Badge (EARSIP / Sertifikat Fisik) -->
                                <div class="pt-2 border-top border-light-subtle d-flex flex-wrap align-items-center justify-content-between gap-2">
                                    <div class="small">
                                        <span class="badge ${item.arsip_sertifikat.tersedia ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary'} rounded-pill px-2 py-1">
                                            <i class="bi bi-file-earmark-check me-1"></i>${escapeHtml(item.arsip_sertifikat.status_label)}
                                        </span>
                                    </div>
                                    ${item.arsip_sertifikat.no_sertipikat ? `
                                        <span class="small text-secondary font-monospace" style="font-size: 0.75rem;">
                                            No: ${escapeHtml(item.arsip_sertifikat.no_sertipikat)}
                                        </span>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                resultsBody.innerHTML = html;
            };

            // RENDER ARCHIVE RESULTS
            const renderArchiveResults = (items, query) => {
                if (!items || items.length === 0) {
                    resultsBody.innerHTML = getEmptyStateHtml(query, 'Arsip Aset');
                    return;
                }

                let html = '<div class="row g-3">';
                items.forEach(doc => {
                    html += `
                        <div class="col-lg-6">
                            <div class="p-3 p-md-4 rounded-4 bg-light border border-light-subtle h-100 d-flex flex-column justify-content-between hover-elevate transition-all">
                                <div>
                                    <!-- Header Info -->
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                        <div>
                                            <span class="badge ${escapeHtml(doc.type_badge)} px-2 py-1 rounded-pill small fw-semibold mb-1">
                                                <i class="bi ${escapeHtml(doc.type_icon)} me-1"></i>${escapeHtml(doc.type)}
                                            </span>
                                            <h6 class="fw-bold text-navy mb-0 fs-6 mt-1 font-monospace">${escapeHtml(doc.no_dokumen)}</h6>
                                            <div class="small text-secondary">Aset: <strong>${escapeHtml(doc.nama_aset)}</strong></div>
                                        </div>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small fw-medium">
                                            ${escapeHtml(doc.status_label)}
                                        </span>
                                    </div>

                                    <!-- Grid Info -->
                                    <div class="row g-2 mb-3 small">
                                        <div class="col-sm-6">
                                            <div class="p-2 bg-white rounded-2 border border-light-subtle h-100">
                                                <div class="text-secondary" style="font-size: 0.75rem;">NIBAR / Register</div>
                                                <div class="fw-semibold text-dark font-monospace text-truncate">${escapeHtml(doc.nibar)}</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="p-2 bg-white rounded-2 border border-light-subtle h-100">
                                                <div class="text-secondary" style="font-size: 0.75rem;">OPD / Instansi</div>
                                                <div class="fw-semibold text-dark text-truncate" title="${escapeHtml(doc.opd)}">${escapeHtml(doc.opd)}</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="p-2 bg-white rounded-2 border border-light-subtle h-100">
                                                <div class="text-secondary" style="font-size: 0.75rem;">Lokasi Box Arsip</div>
                                                <div class="fw-bold text-navy">
                                                    <i class="bi bi-box-seam me-1 text-primary"></i>Box ${escapeHtml(doc.box_code)}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="p-2 bg-white rounded-2 border border-light-subtle h-100">
                                                <div class="text-secondary" style="font-size: 0.75rem;">Ruang / Rak</div>
                                                <div class="fw-semibold text-dark text-truncate">${escapeHtml(doc.lokasi_box || 'Gudang Arsip')}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer Info -->
                                <div class="pt-2 border-top border-light-subtle d-flex flex-wrap align-items-center justify-content-between gap-2 small text-secondary">
                                    <span>Tahun Perolehan: <strong>${escapeHtml(doc.tahun)}</strong></span>
                                    <span class="badge bg-amber-subtle text-dark border border-warning-subtle rounded-pill px-2 py-1">
                                        <i class="bi bi-shield-check me-1"></i>Fisik Tersimpan
                                    </span>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                resultsBody.innerHTML = html;
            };

            // FORM SUBMISSION HANDLERS
            const attachSearchFormHandler = (formId, moduleName, badgeClass, renderFn) => {
                const form = document.getElementById(formId);
                if (!form) return;

                form.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    const formData = new FormData(form);
                    const params = new URLSearchParams(formData);
                    const endpoint = form.dataset.searchEndpoint;
                    const query = formData.get('q') || '';

                    // UI Loading State
                    resultsContainer.classList.remove('d-none');
                    resultModuleBadge.className = `badge ${badgeClass} fw-semibold px-2 py-1 rounded-pill`;
                    resultModuleBadge.textContent = moduleName;
                    resultHeading.textContent = `Pencarian ${moduleName}`;
                    resultSummaryText.innerHTML = `<span class="spinner-border spinner-border-sm me-1 text-primary"></span> Memuat data aset...`;
                    resultsBody.innerHTML = getSkeletonHtml();

                    // Scroll smooth into results
                    resultsContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                    try {
                        const response = await fetch(`${endpoint}?${params.toString()}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Gagal memuat data dari server');
                        }

                        const data = await response.json();

                        resultSummaryText.textContent = `Ditemukan ${data.count || 0} aset ${query ? 'untuk kata kunci "' + query + '"' : ''}`;
                        renderFn(data.data || [], query);

                    } catch (error) {
                        resultSummaryText.textContent = 'Terjadi kesalahan saat memuat data.';
                        resultsBody.innerHTML = getErrorStateHtml();
                    }
                });
            };

            // Attach to 3 Forms
            attachSearchFormHandler(
                'vehicleSearchForm',
                'Kendaraan Dinas (E-RANDIS)',
                'bg-primary text-white',
                renderVehicleResults
            );

            attachSearchFormHandler(
                'landSearchForm',
                'Sertifikat Tanah (SIPAT)',
                'bg-success text-white',
                renderLandResults
            );

            attachSearchFormHandler(
                'archiveSearchForm',
                'Arsip Aset (EARSIP / eLABEL)',
                'bg-amber text-dark',
                renderArchiveResults
            );

            // Quick Search Suggestion Chips Click
            document.querySelectorAll('.quick-query-chip').forEach(chip => {
                chip.addEventListener('click', function () {
                    const inputId = this.dataset.targetInput;
                    const formId = this.dataset.targetForm;
                    const query = this.dataset.query;

                    const input = document.getElementById(inputId);
                    const form = document.getElementById(formId);
                    if (input && form) {
                        input.value = query;
                        // Trigger submit
                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit();
                        } else {
                            form.dispatchEvent(new Event('submit', { cancelable: true }));
                        }
                    }
                });
            });

            // Animated Counters using IntersectionObserver
            const counters = document.querySelectorAll('.counter-animate');
            let countersAnimated = false;

            const animateCounters = () => {
                counters.forEach(counter => {
                    const target = parseInt(counter.dataset.target, 10) || 0;
                    const duration = 1500;
                    const startTime = performance.now();

                    const updateCount = (currentTime) => {
                        const elapsed = currentTime - startTime;
                        const progress = Math.min(elapsed / duration, 1);
                        // Ease out cubic
                        const easeProgress = 1 - Math.pow(1 - progress, 3);
                        const currentCount = Math.floor(easeProgress * target);

                        counter.textContent = new Intl.NumberFormat('id-ID').format(currentCount);

                        if (progress < 1) {
                            requestAnimationFrame(updateCount);
                        } else {
                            counter.textContent = new Intl.NumberFormat('id-ID').format(target);
                        }
                    };

                    requestAnimationFrame(updateCount);
                });
            };

            if ('IntersectionObserver' in window && counters.length > 0) {
                const statsSection = document.getElementById('statistics-section');
                if (statsSection) {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting && !countersAnimated) {
                                countersAnimated = true;
                                animateCounters();
                                observer.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.2 });
                    observer.observe(statsSection);
                } else {
                    animateCounters();
                }
            } else {
                animateCounters();
            }
        });
    </script>
</body>
</html>
