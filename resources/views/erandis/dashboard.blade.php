@extends('layouts.app')

@section('title', 'Dashboard eRANDIS - Kendaraan Dinas')

@section('content')
<div class="container-fluid px-0">
    
    <!-- SECTION 1: Welcome Header eRANDIS -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
        <div class="mb-3 mb-md-0">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 px-3 py-1.5 rounded-pill fw-bold">
                    <i class="bi bi-car-front-fill me-1 text-warning"></i> MODUL eRANDIS
                </span>
                <span class="text-secondary small">| {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>
            </div>
            <h3 class="fw-bold text-navy mb-1">Dashboard Monitoring Kendaraan Dinas</h3>
            <p class="text-secondary mb-0 small">
                Overview operasional armada, kondisi kelayakan, dan jadwal pemeliharaan kendaraan pemda.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('vehicles.index') }}" class="btn btn-warning fw-bold text-dark rounded-3 shadow-sm d-flex align-items-center gap-2">
                <i class="bi bi-truck"></i> Kelola Kendaraan
            </a>
            <a href="{{ route('reports.index') }}" class="btn btn-light border fw-semibold rounded-3 shadow-sm d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-bar-graph"></i> Laporan
            </a>
        </div>
    </div>

    <!-- SECTION 2: Statistic Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <x-stat-card title="Total Kendaraan" :value="$stats['total']" icon="car-front-fill" gradient="primary" subtitle="Seluruh Aset Armada" />
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat-card title="Kondisi Baik" :value="$stats['baik']" icon="check-circle-fill" gradient="success" subtitle="Aset Layak Pakai" />
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat-card title="Rusak Ringan" :value="$stats['rusak_ringan']" icon="exclamation-triangle-fill" gradient="warning" subtitle="Butuh Maintenance" />
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat-card title="Rusak Berat" :value="$stats['rusak_berat']" icon="x-octagon-fill" gradient="danger" subtitle="Tidak Operasional" />
        </div>
    </div>

    <!-- Content Split -->
    <div class="row g-4">
        
        <!-- Main Column (Table) -->
        <div class="col-xl-8">
            <!-- SECTION 4: Table Kendaraan Terbaru -->
            <x-table-card 
                title="Status Armada Kendaraan Terkini" 
                subtitle="Pantau aktivitas operasional armada kendaraan dinas terkini."
                :collection="$latestVehicles"
                :empty="$latestVehicles->isEmpty()"
                emptyText="Belum ada data operasional"
                emptyIcon="bi-inbox">
                
                <x-slot:actions>
                    <a href="{{ route('vehicles.index') }}" class="btn btn-sm btn-light border small fw-bold">Lihat Semua</a>
                </x-slot:actions>

                <x-slot:thead>
                    <tr>
                        <th class="py-3 px-4 border-bottom-0 fw-semibold text-center" style="width: 50px;">#</th>
                        <th class="py-3 border-bottom-0 fw-semibold">Kendaraan</th>
                        <th class="py-3 border-bottom-0 fw-semibold">Pengguna / OPD</th>
                        <th class="py-3 border-bottom-0 fw-semibold text-center">Kondisi</th>
                        <th class="py-3 border-bottom-0 fw-semibold text-center">Status</th>
                        <th class="py-3 px-4 border-bottom-0 fw-semibold text-end">Aksi</th>
                    </tr>
                </x-slot:thead>

                @foreach($latestVehicles as $v)
                    <tr>
                        <td data-label="#" class="px-4 py-3 text-center fw-medium text-secondary">{{ $loop->iteration }}</td>
                        <td data-label="Kendaraan" class="py-3">
                            <div class="fw-bold text-navy plate-number">{{ $v->no_polisi }}</div>
                            <small class="text-secondary">{{ $v->merk }} {{ $v->tipe }}</small>
                        </td>
                        <td data-label="Pengguna / OPD" class="py-3">
                            <div class="fw-medium text-dark"><i class="bi bi-person-fill text-secondary me-1"></i> {{ $v->pemegang }}</div>
                            <small class="text-secondary">{{ Str::limit($v->opd, 30) }}</small>
                        </td>
                        <td data-label="Kondisi" class="py-3 text-center">
                            <x-condition-badge :kondisi="$v->kondisi" />
                        </td>
                        <td data-label="Status" class="py-3 text-center">
                            <x-status-badge :status="$v->status" />
                        </td>
                        <td data-label="Aksi" class="px-4 py-3 text-end">
                            <a href="{{ route('vehicles.index', ['q' => $v->no_polisi]) }}" class="btn btn-sm btn-light rounded-3 text-primary border shadow-sm">
                                Detail <i class="bi bi-chevron-right ms-1" style="font-size:0.7rem;"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach

                <x-slot:pagination>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-secondary">Menampilkan data terbaru kendaraan dinas eRANDIS</small>
                        <a href="{{ route('vehicles.index') }}" class="small fw-bold text-decoration-none">Kelola Semua <i class="bi bi-arrow-right"></i></a>
                    </div>
                </x-slot:pagination>
            </x-table-card>
        </div>

        <!-- Side Column (Quick Monitoring & Activity) -->
        <div class="col-xl-4 d-flex flex-column gap-4">
            
            <!-- SECTION 3: Quick Monitoring -->
            <div class="admin-card p-4 hover-elevate">
                <h6 class="fw-bold text-navy mb-3"><i class="bi bi-radar text-warning me-2"></i> Monitor Cepat Kendaraan</h6>
                
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center border-bottom border-light">
                        <div>
                            <div class="fw-semibold text-dark mb-1">Sedang Digunakan</div>
                            <small class="text-secondary d-block">Kendaraan dalam perjalanan dinas</small>
                        </div>
                        <span class="badge bg-info text-white rounded-pill px-3 py-2">{{ $stats['borrowed'] }} Unit</span>
                    </li>
                    <li class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center border-bottom border-light">
                        <div>
                            <div class="fw-semibold text-dark mb-1">Rusak Ringan</div>
                            <small class="text-secondary d-block">Butuh maintenance rutin</small>
                        </div>
                        <span class="badge bg-warning text-dark rounded-pill px-3 py-2">{{ $stats['rusak_ringan'] }} Unit</span>
                    </li>
                    <li class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center border-bottom border-light">
                        <div>
                            <div class="fw-semibold text-danger mb-1">Rusak Berat / Hilang</div>
                            <small class="text-secondary d-block">Aset tidak operasional</small>
                        </div>
                        <span class="badge bg-danger text-white rounded-pill px-3 py-2">{{ $stats['rusak_berat'] + $stats['hilang'] }} Unit</span>
                    </li>
                </ul>
            </div>

            @if(auth()->user()->role === \App\Enums\UserRole::SUPERADMIN)
            <!-- RIGHT PANEL: Aktivitas Terbaru -->
            <div class="admin-card p-4 flex-grow-1 hover-elevate">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-activity text-warning me-2"></i> Aktivitas Terbaru eRANDIS</h6>
                </div>
                
                @if($activities->isEmpty())
                    <div class="text-center py-5">
                        <i class="bi bi-journal-text fs-1 text-light"></i>
                        <p class="text-secondary small mt-2">Belum ada riwayat aktivitas</p>
                    </div>
                @else
                    <div class="position-relative border-start border-2 border-light ms-3 ps-4 pb-1">
                        @foreach($activities as $activity)
                            <div class="position-relative mb-4">
                                <span class="position-absolute top-0 start-0 translate-middle bg-warning border border-white border-3 rounded-circle" style="width: 16px; height: 16px; margin-left: -25px;"></span>
                                <div class="fw-medium text-dark small">{{ $activity->description }}</div>
                                <div class="text-secondary mb-1" style="font-size: 0.8rem;">
                                    Oleh: <span class="fw-semibold">{{ $activity->user->name ?? 'Sistem' }}</span>
                                </div>
                                <small class="text-muted" style="font-size: 0.7rem;">{{ $activity->created_at->diffForHumans() }}</small>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            @endif

        </div>
    </div>
</div>
@endsection
