@props([
    'title' => null,
    'subtitle' => null,
    'actions' => null,
    'filters' => null,
    'thead' => null,
    'pagination' => null,
    'collection' => null, // Tambahkan properti untuk menampung objek paginasi
    'empty' => false,
    'emptyText' => 'Belum ada data yang tersedia.',
    'emptyIcon' => 'bi-inbox'
])

<div class="admin-card overflow-hidden">
    <!-- Header Bagian Atas (Opsi) -->
    @if($title || $actions)
        <div class="p-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                @if($title)
                    <h5 class="fw-bold text-navy mb-0">{{ $title }}</h5>
                @endif
                @if($subtitle)
                    <small class="text-secondary">{{ $subtitle }}</small>
                @endif
            </div>
            @if($actions)
                <div class="d-flex gap-2">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <!-- Area Filter & Search (Opsi) -->
    @if($filters)
        <div class="bg-light p-3 border-bottom border-light">
            {{ $filters }}
            
            <!-- Mobile Swipe Hint -->
            <div class="d-md-none mt-2 text-center">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 bg-white border rounded-pill shadow-sm">
                    <i class="bi bi-arrow-left-right text-primary small"></i>
                    <span class="text-secondary" style="font-size: 0.7rem;">Geser tabel untuk melihat detail</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Konten Utama Tabel -->
    <div class="table-responsive table-responsive-mobile">
        <table class="table table-hover align-middle mb-0">
            @if($thead)
                <thead class="bg-white text-secondary small text-uppercase">
                    {{ $thead }}
                </thead>
            @endif
            <tbody class="border-top-0 bg-white">
                @if($empty)
                    <tr>
                        <td colspan="100" class="text-center py-5">
                            <div class="py-4">
                                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <i class="bi {{ $emptyIcon }} text-secondary opacity-50" style="font-size: 2.5rem;"></i>
                                </div>
                                <h5 class="fw-bold text-navy mb-1">{{ $emptyText }}</h5>
                                <p class="text-secondary mb-0 small">Silakan tambahkan data baru atau sesuaikan filter pencarian Anda.</p>
                            </div>
                        </td>
                    </tr>
                @else
                    {{ $slot }}
                @endif
            </tbody>
        </table>
    </div>

    <!-- Area Pagination (Opsi) -->
    @if($pagination || $collection)
        <div class="p-4 border-top bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <!-- Metadata Paginasi -->
                <div class="small text-secondary fw-medium order-2 order-md-1 text-center text-md-start">
                    @if($collection instanceof \Illuminate\Pagination\LengthAwarePaginator || $collection instanceof \Illuminate\Pagination\Paginator)
                        Menampilkan 
                        <span class="text-navy fw-bold">{{ $collection->firstItem() ?? 0 }}</span> 
                        sampai 
                        <span class="text-navy fw-bold">{{ $collection->lastItem() ?? 0 }}</span> 
                        dari 
                        <span class="text-navy fw-bold">{{ $collection->total() }}</span> data
                    @elseif($collection)
                        Total: <span class="text-navy fw-bold">{{ count($collection) }}</span> data
                    @endif
                </div>

                <!-- Tombol Navigasi -->
                <div class="pagination-modern order-1 order-md-2">
                    {{ $pagination }}
                </div>
            </div>
        </div>
    @endif
</div>

<style>
    /* Styling Paginasi agar lebih bersih dan profesional */
    .pagination-modern .pagination {
        margin-bottom: 0;
        gap: 4px;
    }
    .pagination-modern .page-link {
        border-radius: 8px !important;
        border: 1px solid #e2e8f0;
        color: #475569;
        padding: 0.45rem 0.85rem;
        font-weight: 500;
        font-size: 0.875rem;
        transition: all 0.2s;
        background: white;
    }
    .pagination-modern .page-item.active .page-link {
        background-color: #1e40af;
        border-color: #1e40af;
        color: white;
        box-shadow: 0 4px 6px -1px rgba(30, 64, 175, 0.1), 0 2px 4px -1px rgba(30, 64, 175, 0.06);
    }
    .pagination-modern .page-link:hover:not(.active) {
        background-color: #f1f5f9;
        color: #1e40af;
    }
    /* Sembunyikan label "Showing" bawaan Bootstrap jika masih muncul di dalam links() */
    .pagination-modern nav .flex.items-center.justify-between,
    .pagination-modern nav div:first-child {
        display: none !important;
    }
    .pagination-modern nav div:last-child {
        margin-top: 0 !important;
    }
</style>
