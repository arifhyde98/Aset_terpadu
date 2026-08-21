@extends('layouts.app')

@section('content')
<div class="page-header-global mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">
            <i class="bi bi-arrow-left-right text-primary me-2"></i> Rekonsiliasi Arsip Sertifikat
        </h1>
        <p class="text-muted small mb-0">Pencocokan data aset bersertifikat di SIPAT dengan fisik arsip di eLabel</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card clean-card h-100 border-0 bg-primary text-white">
            <div class="card-body p-4 d-flex align-items-center">
                <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-box-seam fs-1"></i>
                </div>
                <div>
                    <h2 class="fw-bold mb-0">{{ $totalElabel }}</h2>
                    <div class="small text-white-50">Total Fisik di eLabel</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card clean-card h-100 border-0 bg-success text-white">
            <div class="card-body p-4 d-flex align-items-center">
                <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-check-circle fs-1"></i>
                </div>
                <div>
                    <h2 class="fw-bold mb-0">{!! count($matchList) !!}</h2>
                    <div class="small text-white-50">Aset Cocok (Match)</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card clean-card h-100 border-0 bg-danger text-white">
            <div class="card-body p-4 d-flex align-items-center">
                <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-exclamation-circle fs-1"></i>
                </div>
                <div>
                    <h2 class="fw-bold mb-0">{!! count($missList) !!}</h2>
                    <div class="small text-white-50">Aset Selisih (Miss)</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card clean-card border-0 mb-4">
    <div class="card-header bg-transparent border-bottom p-3 d-flex justify-content-between align-items-center">
        <ul class="nav nav-pills" id="rekonTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-pill fw-semibold px-4" id="miss-tab" data-bs-toggle="tab" data-bs-target="#miss-tab-pane" type="button" role="tab">
                    Selisih (Belum Diarsipkan) <span class="badge bg-danger ms-1">{!! count($missList) !!}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill fw-semibold px-4 ms-2" id="match-tab" data-bs-toggle="tab" data-bs-target="#match-tab-pane" type="button" role="tab">
                    Cocok (Sudah Diarsipkan) <span class="badge bg-success ms-1">{!! count($matchList) !!}</span>
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body p-0">
        <div class="tab-content" id="rekonTabsContent">
            
            <!-- Tab Selisih -->
            <div class="tab-pane fade show active" id="miss-tab-pane" role="tabpanel">
                <div class="p-4 bg-light text-muted small border-bottom">
                    <i class="bi bi-info-circle me-1"></i> <strong>Aset Selisih:</strong> Aset di bawah ini tercatat berstatus "Bersertifikat" di SIPAT, namun NIB-nya <strong>belum ditemukan</strong> di dalam gudang arsip fisik (eLabel).
                </div>
                <div class="table-responsive">
                    <table class="table table-premium table-hover align-middle mb-0 js-datatable">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="25%">NIB (Kode Aset)</th>
                                <th>Nama Aset & Lokasi</th>
                                <th width="15%" class="text-center">Status SIPAT</th>
                                <th width="10%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $no = 1; @endphp
@foreach ($missList as $aset)
                            <tr>
                                <td class="text-center text-muted">{!! $no++ !!}</td>
                                <td class="font-monospace text-primary fw-semibold">{{ $aset->kode_aset }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $aset->nama_aset }}</div>
                                    <div class="text-muted small"><i class="bi bi-geo-alt me-1"></i> {{ $aset->alamat }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3">
                                        {{ $aset->status_saat_ini }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{!! url('sipat/aset/' . $aset->id) !!}" data-modal-aset data-modal-url="{!! url('sipat/aset/' . $aset->id . '/modal') !!}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                            @if (empty($missList))
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="empty-state border-0 bg-transparent">
                                        <i class="bi bi-shield-check text-success fs-1 mb-2 d-block"></i>
                                        <h5 class="fw-bold text-dark">Data Sempurna!</h5>
                                        <p class="text-muted mb-0">Semua aset bersertifikat di SIPAT sudah memiliki arsip fisik di eLabel.</p>
                                    </div>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Cocok -->
            <div class="tab-pane fade" id="match-tab-pane" role="tabpanel">
                <div class="p-4 bg-light text-muted small border-bottom">
                    <i class="bi bi-check-circle text-success me-1"></i> <strong>Aset Cocok:</strong> Aset di bawah ini tercatat berstatus "Bersertifikat" di SIPAT dan fisiknya <strong>sudah aman diarsipkan</strong> di eLabel.
                </div>
                <div class="table-responsive">
                    <table class="table table-premium table-hover align-middle mb-0 js-datatable">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="25%">NIB (Kode Aset)</th>
                                <th>Nama Aset & Lokasi</th>
                                <th width="15%" class="text-center">Status Arsip</th>
                                <th width="10%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $no = 1; @endphp
@foreach ($matchList as $aset)
                            <tr>
                                <td class="text-center text-muted">{!! $no++ !!}</td>
                                <td class="font-monospace text-primary fw-semibold">{{ $aset->kode_aset }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $aset->nama_aset }}</div>
                                    <div class="text-muted small"><i class="bi bi-geo-alt me-1"></i> {{ $aset->alamat }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3">
                                        Tersedia
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{!! url('sipat/aset/' . $aset->id) !!}" data-modal-aset data-modal-url="{!! url('sipat/aset/' . $aset->id . '/modal') !!}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                            @if (empty($matchList))
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    Belum ada data yang cocok.
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade modal-modern" id="modalRemote" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content"></div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.addEventListener('click', async function (e) {
            const link = e.target.closest('[data-modal-aset]');
            if (link) {
                e.preventDefault();
                const url      = link.getAttribute('data-modal-url') || link.getAttribute('href');
                const fallback = link.getAttribute('href');
                const modalEl  = document.getElementById('modalRemote');
                if (!modalEl || typeof bootstrap === 'undefined') { window.location.href = fallback; return; }
                const modal   = bootstrap.Modal.getOrCreateInstance(modalEl);
                const content = modalEl.querySelector('.modal-content');
                content.innerHTML = '<div class="modal-body p-4 text-center"><div class="spinner-border text-primary me-2" role="status"></div> Memuat detail aset...</div>';
                modal.show();
                try {
                    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!res.ok) { window.location.href = fallback; return; }
                    const html = await res.text();
                    content.innerHTML = html;
                    content.querySelectorAll('script').forEach(function(oldScript) {
                        const newScript = document.createElement('script');
                        if (oldScript.src) {
                            newScript.src = oldScript.src;
                        } else {
                            newScript.textContent = oldScript.textContent;
                        }
                        oldScript.parentNode.replaceChild(newScript, oldScript);
                    });
                } catch (err) {
                    window.location.href = fallback;
                }
            }
        });
    });
</script>
@endsection
