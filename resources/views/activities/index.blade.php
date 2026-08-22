@extends('layouts.app')

@section('title', 'Riwayat Aktivitas')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
        <div class="mb-3 mb-md-0">
            <h3 class="fw-bold text-navy mb-1">Log Aktivitas Terpadu</h3>
            <p class="text-secondary mb-0 small">Memantau jejak aktivitas E-RANDIS, SIPAT, dan eLABEL dalam satu tempat.</p>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('activities.clear') }}" method="POST" class="clear-logs-confirm">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger d-flex align-items-center gap-2 rounded-3 shadow-sm">
                    <i class="bi bi-trash3"></i> Bersihkan Log
                </button>
            </form>
        </div>
    </div>

    <div class="admin-card p-3 mb-3">
        <form method="GET" action="{{ route('activities.index') }}" class="row g-2 align-items-center">
            <div class="col-md-4 col-lg-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Modul</label>
                <select name="module" class="form-select" onchange="this.form.submit()">
                    @foreach($modules as $key => $label)
                        <option value="{{ $key }}" {{ $selectedModule === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto align-self-end">
                <a href="{{ route('activities.index') }}" class="btn btn-light border">
                    <i class="bi bi-arrow-clockwise me-1"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <div class="admin-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="py-3 px-4 text-center" style="width: 80px;">Tipe</th>
                        <th class="py-3" style="width: 140px;">Modul</th>
                        <th class="py-3">Aktivitas</th>
                        <th class="py-3">Dilakukan Oleh</th>
                        <th class="py-3">Waktu</th>
                        <th class="py-3 text-center" style="width: 110px;">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activities as $activity)
                        <tr>
                            <td class="px-4 text-center">
                                @php
                                    $icon = match($activity->type) {
                                        'success' => 'bi-check-circle-fill text-success',
                                        'danger' => 'bi-exclamation-octagon-fill text-danger',
                                        'warning' => 'bi-exclamation-triangle-fill text-warning',
                                        default => 'bi-info-circle-fill text-info',
                                    };
                                @endphp
                                <i class="bi {{ $icon }} fs-5"></i>
                            </td>
                            <td class="py-3">
                                @php
                                    $moduleClass = match($activity->module_key) {
                                        'sipat' => 'bg-primary-subtle text-primary',
                                        'elabel' => 'bg-info-subtle text-info',
                                        default => 'bg-warning-subtle text-dark',
                                    };
                                @endphp
                                <span class="badge {{ $moduleClass }} px-2 py-1">{{ $activity->module_label }}</span>
                            </td>
                            <td class="py-3 fw-medium text-dark">
                                {{ $activity->description }}
                            </td>
                            <td class="py-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                        <i class="bi bi-person text-secondary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold small">{{ $activity->user->name ?? 'Sistem' }}</div>
                                        <small class="text-secondary">{{ $activity->user->email ?? '-' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 text-secondary small">
                                <div>{{ $activity->created_at->translatedFormat('d F Y') }}</div>
                                <div>{{ $activity->created_at->format('H:i:s') }} ({{ $activity->created_at->diffForHumans() }})</div>
                            </td>
                            <td class="py-3 text-center">
                                <button type="button"
                                    class="btn btn-sm btn-outline-primary detail-activity-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#activityDetailModal"
                                    data-source="{{ $activity->source }}"
                                    data-module="{{ $activity->module_label }}"
                                    data-description="{{ e($activity->description) }}"
                                    data-user="{{ e($activity->user->name ?? 'Sistem') }}"
                                    data-created-at="{{ $activity->created_at->translatedFormat('d F Y H:i:s') }}"
                                    data-before='@json($activity->before_data)'
                                    data-after='@json($activity->after_data)'
                                    data-audit-action="{{ $activity->audit_action ?? '' }}"
                                    data-audit-entity="{{ $activity->audit_entity ?? '' }}"
                                    data-audit-id="{{ $activity->audit_entity_id ?? '' }}"
                                >
                                    <i class="bi bi-eye me-1"></i> Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="py-4">
                                    <i class="bi bi-journal-x fs-1 text-light"></i>
                                    <p class="text-secondary mt-3">Belum ada riwayat aktivitas yang tercatat.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($activities->hasPages())
            <div class="px-4 py-3 bg-light border-top">
                {{ $activities->links() }}
            </div>
        @endif
    </div>
</div>

<div class="modal fade" id="activityDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold mb-1">Detail Aktivitas</h5>
                    <small class="text-secondary" id="activityDetailMeta"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="text-secondary small mb-1">Deskripsi</div>
                    <div class="fw-medium text-dark" id="activityDetailDescription"></div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="text-secondary small mb-1">Modul</div>
                        <div class="fw-semibold" id="activityDetailModule"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-secondary small mb-1">Dilakukan Oleh</div>
                        <div class="fw-semibold" id="activityDetailUser"></div>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="text-secondary small mb-1">Waktu</div>
                    <div class="fw-semibold" id="activityDetailTime"></div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100 bg-light">
                            <div class="fw-semibold mb-2 text-danger"><i class="bi bi-arrow-left-circle me-1"></i> Sebelum</div>
                            <pre class="mb-0 small text-dark activity-detail-code" id="activityDetailBefore">-</pre>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100 bg-light">
                            <div class="fw-semibold mb-2 text-success"><i class="bi bi-arrow-right-circle me-1"></i> Sesudah</div>
                            <pre class="mb-0 small text-dark activity-detail-code" id="activityDetailAfter">-</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.activity-detail-code {
    white-space: pre-wrap;
    word-break: break-word;
}
</style>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (event) {
        const button = event.target.closest('.detail-activity-btn');
        if (!button) return;

        const parseJSON = (value) => {
            if (!value || value === 'null' || value === 'undefined') return null;
            try {
                return JSON.parse(value);
            } catch (e) {
                return value;
            }
        };

        const formatValue = (value) => {
            if (value === null || value === undefined || value === '') return '-';
            if (typeof value === 'string') return value;
            return JSON.stringify(value, null, 2);
        };

        document.getElementById('activityDetailMeta').textContent =
            `${button.dataset.source} • ${button.dataset.createdAt}`;
        document.getElementById('activityDetailDescription').textContent = button.dataset.description || '-';
        document.getElementById('activityDetailModule').textContent = button.dataset.module || '-';
        document.getElementById('activityDetailUser').textContent = button.dataset.user || '-';
        document.getElementById('activityDetailTime').textContent = button.dataset.createdAt || '-';

        const before = parseJSON(button.dataset.before);
        const after = parseJSON(button.dataset.after);

        document.getElementById('activityDetailBefore').textContent = formatValue(before);
        document.getElementById('activityDetailAfter').textContent = formatValue(after);
    });

    // Clear Logs Confirmation with Checklist Popup
    const clearLogsForm = document.querySelector('.clear-logs-confirm');
    if (clearLogsForm) {
        clearLogsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const root = document.getElementById('theme-root');
            const theme = root ? root.getAttribute('data-theme') : 'light';
            
            Swal.fire({
                title: 'Bersihkan Log Aktivitas',
                html: `
                    <p class="text-start mb-3 small text-secondary">Pilih modul log aktivitas yang ingin Anda bersihkan:</p>
                    <div class="text-start px-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="clear_erandis" checked>
                            <label class="form-check-label fw-medium" for="clear_erandis">
                                E-RANDIS (Log Sistem/Kendaraan)
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="clear_sipat">
                            <label class="form-check-label fw-medium" for="clear_sipat">
                                SIPAT (Log Audit Tanah)
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="clear_elabel">
                            <label class="form-check-label fw-medium" for="clear_elabel">
                                eLABEL (Log QR Code Aset)
                            </label>
                        </div>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Bersihkan!',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                background: theme === 'dark' ? '#1e293b' : '#ffffff',
                color: theme === 'dark' ? '#f1f5f9' : '#1e293b',
                preConfirm: () => {
                    const erandis = document.getElementById('clear_erandis').checked;
                    const sipat = document.getElementById('clear_sipat').checked;
                    const elabel = document.getElementById('clear_elabel').checked;

                    if (!erandis && !sipat && !elabel) {
                        Swal.showValidationMessage('Harap pilih minimal satu modul!');
                        return false;
                    }

                    return { erandis, sipat, elabel };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const data = result.value;
                    Object.keys(data).forEach(key => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `modules[${key}]`;
                        input.value = data[key] ? '1' : '0';
                        clearLogsForm.appendChild(input);
                    });

                    clearLogsForm.submit();
                }
            });
        });
    }
});
</script>
@endpush
