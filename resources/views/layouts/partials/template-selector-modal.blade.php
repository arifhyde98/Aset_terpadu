<!-- MODAL SELEKTOR TEMPLATE ADMIN LAYOUT -->
<div class="modal fade" id="templateSelectorModal" tabindex="-1" aria-labelledby="templateSelectorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom px-4 py-3 bg-light">
                <div>
                    <h5 class="modal-title fw-bold text-navy d-flex align-items-center gap-2" id="templateSelectorModalLabel">
                        <i class="bi bi-layout-text-window-reverse text-primary"></i> Pengaturan Template Admin Layout
                    </h5>
                    <p class="text-secondary small mb-0">Pilih variasi tampilan antarmuka (UI/UX) sesuai selera Anda.</p>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-body-tertiary">
                @php
                    $currentTemplate = 'classic';
                    if (auth()->check() && !empty(auth()->user()->admin_template)) {
                        $currentTemplate = auth()->user()->admin_template;
                    } elseif (session()->has('admin_template')) {
                        $currentTemplate = session('admin_template');
                    }
                @endphp

                <div class="row g-3">
                    <!-- TEMPLATE 1: CLASSIC DEFAULT -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-2 rounded-4 template-card-option transition-all {{ $currentTemplate === 'classic' ? 'border-primary shadow' : 'border-border' }}" 
                             data-template-id="classic" style="cursor: pointer;">
                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1 rounded-3 small">Classic Default</span>
                                        @if($currentTemplate === 'classic')
                                            <span class="badge bg-primary text-white rounded-pill"><i class="bi bi-check-circle-fill me-1"></i> Aktif</span>
                                        @endif
                                    </div>
                                    <h6 class="fw-bold text-navy mb-1">Standar Bawaan Aplikasi</h6>
                                    <p class="text-secondary small mb-3">Tampilan asli bawaan aplikasi dengan sidebar Slate Dark 900, navbar atas putih bersih, dan kontras navigasi yang sempurna.</p>
                                </div>
                                <!-- Mockup Preview -->
                                <div class="border rounded-3 p-2 bg-white shadow-sm overflow-hidden" style="height: 90px;">
                                    <div class="d-flex h-100 gap-1">
                                        <div class="rounded-1" style="width: 25%; background: #0f172a;">
                                            <div class="p-1 text-white opacity-75" style="font-size: 0.5rem;"><i class="bi bi-building"></i> SIPAT</div>
                                        </div>
                                        <div class="d-flex flex-column flex-grow-1 gap-1">
                                            <div class="bg-light border rounded-1 p-1 d-flex justify-content-between">
                                                <div class="bg-secondary rounded-1" style="width: 30%; height: 6px; opacity: 0.4;"></div>
                                                <div class="bg-primary rounded-1" style="width: 15%; height: 6px;"></div>
                                            </div>
                                            <div class="bg-light border rounded-1 p-1 flex-grow-1">
                                                <div class="bg-secondary rounded-1 mb-1" style="width: 60%; height: 6px; opacity: 0.3;"></div>
                                                <div class="bg-secondary rounded-1" style="width: 80%; height: 6px; opacity: 0.2;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TEMPLATE 2: SLEEK EXECUTIVE DARK -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-2 rounded-4 template-card-option transition-all {{ $currentTemplate === 'executive' ? 'border-primary shadow' : 'border-border' }}" 
                             data-template-id="executive" style="cursor: pointer;">
                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-dark text-cyan fw-bold px-2 py-1 rounded-3 small" style="background: #0f172a !important; color: #38bdf8 !important;">Executive Dark</span>
                                        @if($currentTemplate === 'executive')
                                            <span class="badge bg-primary text-white rounded-pill"><i class="bi bi-check-circle-fill me-1"></i> Aktif</span>
                                        @endif
                                    </div>
                                    <h6 class="fw-bold text-navy mb-1">Sidebar Dark Slate & Cyan</h6>
                                    <p class="text-secondary small mb-3">Tampilan eksekutif dengan warna sidebar seram gelap ala dashboard modern premium, aksen cyan & emerald.</p>
                                </div>
                                <!-- Mockup Preview -->
                                <div class="border rounded-3 p-2 shadow-sm overflow-hidden" style="height: 90px; background: #0f172a;">
                                    <div class="d-flex h-100 gap-1">
                                        <div class="rounded-1" style="width: 25%; background: #1e293b;">
                                            <div class="p-1" style="font-size: 0.5rem; color: #38bdf8;"><i class="bi bi-shield-lock"></i> EXEC</div>
                                        </div>
                                        <div class="d-flex flex-column flex-grow-1 gap-1">
                                            <div class="rounded-1 p-1 d-flex justify-content-between" style="background: #1e293b;">
                                                <div class="rounded-1" style="width: 30%; height: 6px; background: #94a3b8;"></div>
                                                <div class="rounded-1" style="width: 15%; height: 6px; background: #38bdf8;"></div>
                                            </div>
                                            <div class="rounded-1 p-1 flex-grow-1" style="background: #1e293b;">
                                                <div class="rounded-1 mb-1" style="width: 60%; height: 6px; background: #94a3b8;"></div>
                                                <div class="rounded-1" style="width: 80%; height: 6px; background: #475569;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TEMPLATE 3: HORIZONTAL TOPBAR FLOATING -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-2 rounded-4 template-card-option transition-all {{ $currentTemplate === 'horizontal' ? 'border-primary shadow' : 'border-border' }}" 
                             data-template-id="horizontal" style="cursor: pointer;">
                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-success-subtle text-success fw-bold px-2 py-1 rounded-3 small">Floating Topbar</span>
                                        @if($currentTemplate === 'horizontal')
                                            <span class="badge bg-primary text-white rounded-pill"><i class="bi bi-check-circle-fill me-1"></i> Aktif</span>
                                        @endif
                                    </div>
                                    <h6 class="fw-bold text-navy mb-1">Navigasi Horizontal Atas</h6>
                                    <p class="text-secondary small mb-3">Tanpa sidebar kiri; seluruh menu dipindahkan ke Floating Topbar horizontal untuk area kerja tabel data maksimal di desktop.</p>
                                </div>
                                <!-- Mockup Preview -->
                                <div class="border rounded-3 p-2 bg-white shadow-sm overflow-hidden" style="height: 90px;">
                                    <div class="d-flex flex-column h-100 gap-1">
                                        <div class="bg-primary rounded-1 p-1 d-flex justify-content-between align-items-center text-white" style="height: 22px;">
                                            <div style="font-size: 0.5rem;"><i class="bi bi-grid-fill"></i> SIPAT TOPBAR</div>
                                            <div class="d-flex gap-1" style="font-size: 0.45rem;">
                                                <span class="bg-white bg-opacity-25 px-1 rounded">Menu 1</span>
                                                <span class="bg-white bg-opacity-25 px-1 rounded">Menu 2</span>
                                            </div>
                                        </div>
                                        <div class="bg-light border rounded-1 p-1 flex-grow-1">
                                            <div class="bg-secondary rounded-1 mb-1" style="width: 70%; height: 6px; opacity: 0.3;"></div>
                                            <div class="bg-secondary rounded-1" style="width: 90%; height: 6px; opacity: 0.2;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TEMPLATE 4: GLASSMORPHISM MINIMAL -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-2 rounded-4 template-card-option transition-all {{ $currentTemplate === 'glass' ? 'border-primary shadow' : 'border-border' }}" 
                             data-template-id="glass" style="cursor: pointer;">
                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-purple-subtle text-purple fw-bold px-2 py-1 rounded-3 small" style="background: #f3e8ff !important; color: #7e22ce !important;">Glassmorphism</span>
                                        @if($currentTemplate === 'glass')
                                            <span class="badge bg-primary text-white rounded-pill"><i class="bi bi-check-circle-fill me-1"></i> Aktif</span>
                                        @endif
                                    </div>
                                    <h6 class="fw-bold text-navy mb-1">Transparan Kaca & Indigo</h6>
                                    <p class="text-secondary small mb-3">Tampilan futuristik dengan sidebar efek kaca semi-transparan (*backdrop blur*), warna aksen indigo/purple, dan sudut serba membulat.</p>
                                </div>
                                <!-- Mockup Preview -->
                                <div class="border rounded-3 p-2 shadow-sm overflow-hidden" style="height: 90px; background: linear-gradient(135deg, #e0e7ff 0%, #f3e8ff 100%);">
                                    <div class="d-flex h-100 gap-1">
                                        <div class="rounded-1" style="width: 25%; background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.8);">
                                            <div class="p-1 text-purple" style="font-size: 0.5rem; color: #6b21a8;"><i class="bi bi-stars"></i> GLASS</div>
                                        </div>
                                        <div class="d-flex flex-column flex-grow-1 gap-1">
                                            <div class="rounded-1 p-1" style="background: rgba(255, 255, 255, 0.7);">
                                                <div class="rounded-1" style="width: 30%; height: 6px; background: #7e22ce;"></div>
                                            </div>
                                            <div class="rounded-1 p-1 flex-grow-1" style="background: rgba(255, 255, 255, 0.7);">
                                                <div class="rounded-1 mb-1" style="width: 60%; height: 6px; background: #a855f7;"></div>
                                                <div class="rounded-1" style="width: 80%; height: 6px; background: #cbd5e1;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TEMPLATE 5: ULTRA-MINIMALIST CANVAS -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-2 rounded-4 template-card-option transition-all {{ $currentTemplate === 'minimalist' ? 'border-primary shadow' : 'border-border' }}" 
                             data-template-id="minimalist" style="cursor: pointer;">
                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-dark text-white fw-bold px-2 py-1 rounded-pill small" style="background: #18181b !important;">Minimalist Canvas</span>
                                        @if($currentTemplate === 'minimalist')
                                            <span class="badge bg-primary text-white rounded-pill"><i class="bi bi-check-circle-fill me-1"></i> Aktif</span>
                                        @endif
                                    </div>
                                    <h6 class="fw-bold text-navy mb-1">Canvas Soft Off-White & Dark Pill</h6>
                                    <p class="text-secondary small mb-3">Inspirasi Pinterest UI: Kanvas putih halus, topbar pill melayang, tombol hitam obsidian, dan sudut serba 18px.</p>
                                </div>
                                <!-- Mockup Preview -->
                                <div class="border rounded-3 p-2 shadow-sm overflow-hidden" style="height: 90px; background: #f4f5f7;">
                                    <div class="d-flex flex-column h-100 gap-1.5">
                                        <div class="bg-white border rounded-pill px-2 py-1 d-flex justify-content-between align-items-center shadow-xs" style="height: 22px;">
                                            <div class="bg-dark rounded-circle me-1" style="width: 10px; height: 10px; background: #18181b !important;"></div>
                                            <div class="d-flex gap-1" style="font-size: 0.45rem;">
                                                <span class="bg-dark text-white px-2 py-0.5 rounded-pill" style="background: #18181b !important; font-size: 0.42rem;">Payment</span>
                                                <span class="text-secondary px-1">Details</span>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1 flex-grow-1">
                                            <div class="bg-white border rounded-3 p-1 flex-grow-1">
                                                <div class="bg-secondary rounded-1 mb-1" style="width: 60%; height: 5px; opacity: 0.3;"></div>
                                                <div class="bg-dark rounded-pill" style="width: 30%; height: 5px; background: #18181b !important;"></div>
                                            </div>
                                            <div class="bg-white border rounded-3 p-1" style="width: 35%;">
                                                <div class="bg-secondary rounded-1 mb-1" style="width: 80%; height: 5px; opacity: 0.3;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top bg-white px-4 py-3">
                <button type="button" class="btn btn-light border fw-medium" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary fw-bold px-4" id="btnApplyTemplate">
                    <i class="bi bi-check2-circle me-1"></i> Terapkan Layout
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        let selectedTemplate = '{{ $currentTemplate }}';
        const cardOptions = document.querySelectorAll('.template-card-option');
        const btnApply = document.getElementById('btnApplyTemplate');

        cardOptions.forEach(card => {
            card.addEventListener('click', function () {
                cardOptions.forEach(c => {
                    c.classList.remove('border-primary', 'shadow');
                    c.classList.add('border-border');
                });
                this.classList.remove('border-border');
                this.classList.add('border-primary', 'shadow');
                selectedTemplate = this.getAttribute('data-template-id');
            });
        });

        if (btnApply) {
            btnApply.addEventListener('click', function () {
                btnApply.disabled = true;
                btnApply.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Menerapkan...';

                // Simpan ke localStorage & panggil backend API
                localStorage.setItem('admin_template', selectedTemplate);
                document.documentElement.setAttribute('data-admin-template', selectedTemplate);
                if (document.getElementById('theme-root')) {
                    document.getElementById('theme-root').setAttribute('data-admin-template', selectedTemplate);
                }

                fetch("{{ route('settings.admin-template') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ template: selectedTemplate })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Gagal memperbarui template layout.');
                        btnApply.disabled = false;
                        btnApply.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Terapkan Layout';
                    }
                })
                .catch(err => {
                    console.error(err);
                    window.location.reload();
                });
            });
        }
    });
</script>
