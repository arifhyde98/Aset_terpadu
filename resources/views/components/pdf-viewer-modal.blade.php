{{-- Reusable Universal PDF Viewer Modal for eLABEL / SIPAT --}}
<div class="modal fade" id="universalPdfModal" tabindex="-1" aria-labelledby="universalPdfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-lg-down my-lg-4">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2 overflow-hidden me-3">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-3 p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                        <i class="bi bi-file-earmark-pdf fs-5"></i>
                    </div>
                    <div class="text-truncate">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h6 class="modal-title fw-bold text-navy mb-0 text-truncate" id="universalPdfModalLabel">
                                Pratinjau Dokumen
                            </h6>
                            <span id="universalPdfModalBadge" class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2.5 py-1 rounded-pill small">
                                Dokumen
                            </span>
                        </div>
                        <small id="universalPdfModalSubtitle" class="text-secondary d-block text-truncate">
                            Memuat berkas scan fisik...
                        </small>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <button type="button" id="universalPdfModalPrintBtn" class="btn btn-sm btn-light border text-secondary d-none d-sm-inline-flex align-items-center gap-1 shadow-sm" title="Cetak Dokumen">
                        <i class="bi bi-printer"></i>
                        <span>Cetak</span>
                    </button>
                    <a id="universalPdfModalExternalBtn" href="#" target="_blank" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 shadow-sm" title="Buka di Tab Baru / Layar Penuh">
                        <i class="bi bi-box-arrow-up-right"></i>
                        <span class="d-none d-sm-inline">Tab Baru</span>
                    </a>
                    <button type="button" class="btn-close shadow-none ms-1" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <!-- Modal Body with Loader & Iframe -->
            <div class="modal-body p-0 position-relative bg-light" style="height: 78vh; min-height: 480px;">
                <!-- Loading State -->
                <div id="universalPdfModalLoader" class="position-absolute top-50 start-50 translate-middle text-center p-4">
                    <div class="spinner-border text-danger mb-3" role="status" style="width: 2.5rem; height: 2.5rem;">
                        <span class="visually-hidden">Memuat Dokumen...</span>
                    </div>
                    <div class="fw-semibold text-navy">Memuat Dokumen PDF...</div>
                    <div class="small text-secondary mt-1">Mengambil berkas arsip resmi dari penyimpanan lokal</div>
                </div>

                <!-- Error State (Fallback) -->
                <div id="universalPdfModalError" class="position-absolute top-50 start-50 translate-middle text-center p-4 d-none">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-3 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <i class="bi bi-exclamation-triangle fs-3"></i>
                    </div>
                    <h6 class="fw-bold text-navy">Gagal Membuka Berkas</h6>
                    <p class="text-secondary small mb-3">Dokumen tidak dapat dimuat di viewer interaktif atau format tidak didukung.</p>
                    <a id="universalPdfModalFallbackLink" href="#" target="_blank" class="btn btn-sm btn-primary">
                        <i class="bi bi-download me-1"></i> Unduh / Buka Langsung
                    </a>
                </div>

                <!-- PDF Viewer Iframe -->
                <iframe id="universalPdfModalIframe" 
                        src="about:blank" 
                        class="w-100 h-100 border-0 d-block" 
                        style="opacity: 0; transition: opacity 0.25s ease;"
                        title="Pratinjau PDF">
                </iframe>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('universalPdfModal');
    if (!modalEl) return;

    const modalTitle = document.getElementById('universalPdfModalLabel');
    const modalSubtitle = document.getElementById('universalPdfModalSubtitle');
    const modalBadge = document.getElementById('universalPdfModalBadge');
    const modalExternalBtn = document.getElementById('universalPdfModalExternalBtn');
    const modalPrintBtn = document.getElementById('universalPdfModalPrintBtn');
    const modalIframe = document.getElementById('universalPdfModalIframe');
    const modalLoader = document.getElementById('universalPdfModalLoader');
    const modalError = document.getElementById('universalPdfModalError');
    const modalFallbackLink = document.getElementById('universalPdfModalFallbackLink');

    // Event Delegation for clicking any PDF preview button
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-pdf-preview="true"]');
        if (!btn) return;

        e.preventDefault();

        const url = btn.getAttribute('data-pdf-url') || btn.getAttribute('href');
        const title = btn.getAttribute('data-pdf-title') || 'Pratinjau Dokumen';
        const subtitle = btn.getAttribute('data-pdf-subtitle') || 'Arsip Fisik eLABEL';
        const badge = btn.getAttribute('data-pdf-badge') || 'Dokumen';

        if (!url) return;

        // Reset state
        modalTitle.textContent = title;
        modalSubtitle.textContent = subtitle;
        modalBadge.textContent = badge;
        modalExternalBtn.href = url;
        modalFallbackLink.href = url;

        modalLoader.classList.remove('d-none');
        modalError.classList.add('d-none');
        modalIframe.style.opacity = '0';
        modalIframe.src = url;

        // Open modal via Bootstrap instance
        const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        bsModal.show();
    });

    // Iframe load handler
    modalIframe.addEventListener('load', function () {
        if (modalIframe.src && modalIframe.src !== 'about:blank') {
            modalLoader.classList.add('d-none');
            modalIframe.style.opacity = '1';
        }
    });

    modalIframe.addEventListener('error', function () {
        modalLoader.classList.add('d-none');
        modalError.classList.remove('d-none');
    });

    // Print handler
    if (modalPrintBtn) {
        modalPrintBtn.addEventListener('click', function () {
            try {
                if (modalIframe.contentWindow) {
                    modalIframe.contentWindow.focus();
                    modalIframe.contentWindow.print();
                } else {
                    window.open(modalExternalBtn.href, '_blank');
                }
            } catch (err) {
                window.open(modalExternalBtn.href, '_blank');
            }
        });
    }

    // Free memory when modal is closed
    modalEl.addEventListener('hidden.bs.modal', function () {
        modalIframe.src = 'about:blank';
        modalIframe.style.opacity = '0';
        modalLoader.classList.remove('d-none');
        modalError.classList.add('d-none');
    });
});
</script>
