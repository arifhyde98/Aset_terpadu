@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary fw-semibold px-2.5 py-1 rounded-pill" style="font-size: 0.75rem;">
                    <i class="bi bi-shield-check me-1"></i> MASTER & SISTEM
                </span>
                <span class="text-secondary small">&bull;</span>
                <span class="text-secondary small">Audit Trail & Riwayat Pengguna</span>
            </div>
            <h2 class="fw-bold mb-1">Log Aktivitas Sistem</h2>
            <p class="text-secondary mb-0 small">Rekam jejak perubahan data, entri baru, dan riwayat aktivitas pengguna di aplikasi terpadu</p>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('master.logs.index') }}" id="filterLogForm">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-sm-4 col-md-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Entitas / Modul</label>
                        <select name="entity" class="form-select bg-body-tertiary border-0" onchange="document.getElementById('filterLogForm').submit()">
                            <option value="">-- Semua Entitas --</option>
                            @foreach($entities as $ent)
                                <option value="{{ $ent }}" {{ request('entity') == $ent ? 'selected' : '' }}>{{ strtoupper($ent) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-sm-4 col-md-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Jenis Aksi</label>
                        <select name="action" class="form-select bg-body-tertiary border-0" onchange="document.getElementById('filterLogForm').submit()">
                            <option value="">-- Semua Aksi --</option>
                            @foreach($actions as $act)
                                <option value="{{ $act }}" {{ request('action') == $act ? 'selected' : '' }}>{{ strtoupper($act) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-sm-4 col-md-6">
                        <label class="form-label small fw-semibold text-secondary mb-1">Pencarian Log</label>
                        <div class="input-group">
                            <span class="input-group-text bg-body-tertiary border-0 text-secondary"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control bg-body-tertiary border-0" placeholder="Cari nama user, detail data..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary px-3">Cari</button>
                            @if(request()->hasAny(['search', 'entity', 'action']))
                                <a href="{{ route('master.logs.index') }}" class="btn btn-outline-secondary px-3"><i class="bi bi-x-circle"></i> Reset</a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-body-tertiary text-secondary small">
                    <tr>
                        <th class="ps-4 py-3" style="width: 60px;">NO</th>
                        <th class="py-3">WAKTU & IP</th>
                        <th class="py-3">PENGGUNA</th>
                        <th class="py-3">AKSI</th>
                        <th class="py-3">ENTITAS / MODUL</th>
                        <th class="py-3">IDENTIFIER</th>
                        <th class="text-end pe-4 py-3">DETAIL JSON</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $index => $log)
                        <tr>
                            <td class="ps-4 text-secondary small font-monospace">
                                {{ $logs->firstItem() + $index }}
                            </td>
                            <td>
                                <div class="fw-semibold text-body small">{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y, H:i') }}</div>
                                <small class="text-secondary font-monospace" style="font-size: 0.72rem;">IP: {{ $log->ip_address ?? '127.0.0.1' }}</small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="badge bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-body small">{{ $log->user_name ?? 'Sistem / Admin' }}</div>
                                        <small class="text-secondary" style="font-size: 0.72rem;">{{ $log->user_email ?? 'admin@sipat.go.id' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @php
                                    $actionBadge = 'bg-secondary-subtle text-secondary';
                                    if ($log->action === 'create' || $log->action === 'insert') {
                                        $actionBadge = 'bg-success-subtle text-success border border-success-subtle';
                                    } elseif ($log->action === 'update') {
                                        $actionBadge = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                                    } elseif ($log->action === 'delete') {
                                        $actionBadge = 'bg-danger-subtle text-danger border border-danger-subtle';
                                    }
                                @endphp
                                <span class="badge {{ $actionBadge }} font-monospace px-2.5 py-1" style="font-size: 0.72rem;">
                                    {{ strtoupper($log->action) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-body-tertiary text-body border px-2.5 py-1" style="font-size: 0.75rem;">
                                    {{ strtoupper($log->entity) }}
                                </span>
                            </td>
                            <td class="font-monospace text-body small">
                                #{{ $log->entity_id ?? '-' }}
                            </td>
                            <td class="text-end pe-4">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="viewLogDetail({{ $log->id }})">
                                    <i class="bi bi-code-slash me-1"></i> Inspeksi
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-secondary">
                                <i class="bi bi-clock-history fs-1 d-block mb-2 opacity-50"></i>
                                Belum ada log aktivitas yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="card-footer bg-transparent border-0 p-3 d-flex align-items-center justify-content-between">
                <span class="small text-secondary">Menampilkan {{ $logs->firstItem() }} sampai {{ $logs->lastItem() }} dari {{ $logs->total() }} log</span>
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>

@push('modals')
<!-- Modal Inspeksi Detail Log -->
<div class="modal fade" id="modalDetailLog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom px-4 py-3">
                <h5 class="modal-title fw-bold text-body" id="modalLogTitle">Detail Payload Audit Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-body-tertiary">
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <small class="text-secondary d-block fw-semibold">PENGGUNA</small>
                        <span class="fw-bold text-body" id="logUser">-</span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-secondary d-block fw-semibold">WAKTU PERUBAHAN</small>
                        <span class="fw-bold text-body" id="logTime">-</span>
                    </div>
                </div>

                <div class="mb-3">
                    <small class="text-secondary d-block fw-semibold mb-1">DATA BARU (NEW DATA)</small>
                    <pre class="bg-body p-3 rounded-3 border text-success font-monospace mb-0" id="logNewData" style="max-height: 200px; overflow-y: auto; font-size: 0.8rem;"></pre>
                </div>

                <div>
                    <small class="text-secondary d-block fw-semibold mb-1">DATA LAMA (OLD DATA)</small>
                    <pre class="bg-body p-3 rounded-3 border text-secondary font-monospace mb-0" id="logOldData" style="max-height: 200px; overflow-y: auto; font-size: 0.8rem;"></pre>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
    let logModalInstance = null;

    function viewLogDetail(id) {
        const modalEl = document.getElementById('modalDetailLog');
        if (modalEl && modalEl.parentNode !== document.body) {
            document.body.appendChild(modalEl);
        }

        if (!logModalInstance) {
            logModalInstance = new bootstrap.Modal(modalEl);
        }

        fetch(`{{ url('master-data/log-aktivitas') }}/${id}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('modalLogTitle').innerText = `Audit Log #${data.id} - ${data.action} ${data.entity}`;
                document.getElementById('logUser').innerText = data.user;
                document.getElementById('logTime').innerText = data.created_at;
                document.getElementById('logNewData').innerText = JSON.stringify(data.new_data, null, 2) || '(Kosong)';
                document.getElementById('logOldData').innerText = JSON.stringify(data.old_data, null, 2) || '(Kosong)';
                logModalInstance.show();
            })
            .catch(err => alert('Gagal memuat detail log.'));
    }
</script>
@endpush
@endsection
