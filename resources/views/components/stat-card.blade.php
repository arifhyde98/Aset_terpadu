@props([
    'title',
    'value',
    'icon',
    'gradient' => 'primary',
    'subtitle' => null
])

@php
    $accentMap = [
        'primary'   => ['border' => '#2563EB', 'bg' => '#EFF6FF', 'icon' => '#1D4ED8'],
        'success'   => ['border' => '#10B981', 'bg' => '#ECFDF5', 'icon' => '#047857'],
        'warning'   => ['border' => '#F59E0B', 'bg' => '#FFFBEB', 'icon' => '#B45309'],
        'danger'    => ['border' => '#EF4444', 'bg' => '#FEF2F2', 'icon' => '#B91C1C'],
        'info'      => ['border' => '#06B6D4', 'bg' => '#ECFEFF', 'icon' => '#0E7490'],
        'secondary' => ['border' => '#64748B', 'bg' => '#F8FAFC', 'icon' => '#475569'],
        'dark'      => ['border' => '#334155', 'bg' => '#F1F5F9', 'icon' => '#0F172A'],
    ];
    $theme = $accentMap[$gradient] ?? $accentMap['primary'];
@endphp

<div {{ $attributes->merge(['class' => "card stat-card border-0 shadow-sm p-3 position-relative overflow-hidden bg-white"]) }} style="border-left: 4px solid {{ $theme['border'] }} !important; border-radius: 8px;">
    <div class="d-flex justify-content-between align-items-start">
        <div class="pe-2">
            <div class="text-muted fw-semibold small text-uppercase mb-1" style="font-size: 0.72rem; letter-spacing: 0.04em;">{{ $title }}</div>
            <h3 class="fw-bold mb-0 text-dark" style="font-size: 1.5rem; letter-spacing: -0.02em;">{{ $value }}</h3>
            @if($subtitle)
                <div class="text-secondary small fw-normal mt-1" style="font-size: 0.78rem;">{{ $subtitle }}</div>
            @endif
        </div>
        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; background-color: {{ $theme['bg'] }}; color: {{ $theme['icon'] }};">
            <i class="bi bi-{{ $icon }} fs-5"></i>
        </div>
    </div>
</div>
