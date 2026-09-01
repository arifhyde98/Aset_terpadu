<!-- Dynamic Search Results Container -->
<div id="unifiedSearchResultsContainer" class="mt-4 d-none">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        
        <!-- Results Header -->
        <div class="card-header bg-white border-bottom border-light-subtle px-4 py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold px-2 py-1 rounded-pill" id="resultModuleBadge">
                        Kendaraan Dinas
                    </span>
                    <h5 class="fw-bold mb-0 text-navy" id="resultHeading">Hasil Pencarian</h5>
                </div>
                <div class="small text-secondary mt-1" id="resultSummaryText">
                    Ditemukan 0 aset
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1 d-flex align-items-center gap-1" id="dismissResultsBtn">
                    <i class="bi bi-x-lg small"></i>
                    <span>Tutup Hasil</span>
                </button>
            </div>
        </div>

        <!-- Results Body -->
        <div class="card-body p-4" id="searchResultsBody">
            <!-- Dynamic HTML will be injected here via JavaScript -->
        </div>

        <!-- Results Footer Note -->
        <div class="card-footer bg-light border-top border-light-subtle px-4 py-2 text-center small text-secondary">
            <i class="bi bi-info-circle me-1"></i> Data di atas adalah informasi publik aset daerah. Untuk pengelolaan berkas resmi internal, silakan login melalui portal petugas.
        </div>
    </div>
</div>

<!-- Modal Detail Publik (Opsional untuk inspeksi visual aset seperti foto/peta publik tanpa auth) -->
<div class="modal fade" id="publicAssetDetailModal" tabindex="-1" aria-labelledby="publicAssetDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 bg-navy text-white px-4 py-3">
                <div>
                    <span class="badge bg-white bg-opacity-10 text-white-90 small px-2 py-0 rounded-pill mb-1" id="modalAssetTypeBadge">Aset Daerah</span>
                    <h5 class="modal-title fw-bold text-white mb-0" id="publicAssetDetailModalLabel">Detail Informasi Aset</h5>
                </div>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="publicAssetDetailModalBody">
                <!-- Content injected via JS -->
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-secondary px-4 rounded-3 fw-semibold" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
