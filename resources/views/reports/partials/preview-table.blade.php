@php
    $empty = $data->isEmpty();
    
    // Inisialisasi variabel bantu untuk pewarnaan grup ganda cerdas
    $groupColors = [];
    $colorIndex = 0;
@endphp


<x-table-card
    title="Hasil Pratinjau Laporan"
    subtitle="Data ter-agregasi berdasarkan filter aktif saat ini."
    :collection="$data"
    :empty="$empty"
    emptyText="Tidak ada kendaraan dinas yang cocok dengan kriteria filter."
    emptyIcon="bi-search"
>
    <!-- Slot Header -->
    <x-slot:thead>
        <tr>
            <th class="text-center align-middle" style="width: 60px;">#</th>
            @foreach($headers as $key => $label)
                @php
                    $isSorted = ($sort_by ?? '') === $key;
                @endphp
                <th class="align-middle">
                    <button type="button" 
                            onclick="sortByField('{{ $key }}')" 
                            class="btn btn-link text-navy text-decoration-none fw-semibold p-0 border-0 d-inline-flex align-items-center gap-1 shadow-none">
                        <span>{{ $label }}</span>
                        @if($isSorted)
                            @if(in_array($key, ['nilai_perolehan', 'tgl_stnk', 'tahun_pembuatan']))
                                <i class="bi bi-sort-numeric-{{ ($sort_order ?? 'asc') === 'asc' ? 'down' : 'up' }} text-primary"></i>
                            @else
                                <i class="bi bi-sort-alpha-{{ ($sort_order ?? 'asc') === 'asc' ? 'down' : 'up' }} text-primary"></i>
                            @endif
                        @else
                            <i class="bi bi-arrow-down-up text-secondary opacity-50 small" style="font-size: 0.75rem;"></i>
                        @endif
                    </button>
                </th>
            @endforeach
        </tr>
    </x-slot:thead>

    <!-- Slot Action (Tombol Ekspor Terproteksi) -->
    <x-slot:actions>
        @if(!$empty)
            <div class="action-toolbar d-flex gap-2">
                <button type="button" onclick="exportExcel()" class="btn btn-action btn-action-success btn-sm shadow-sm fw-semibold d-inline-flex align-items-center gap-2">
                    <span class="btn-action-icon"><i class="bi bi-file-earmark-excel"></i></span>
                    <span>Excel</span>
                </button>
                <button type="button" onclick="exportPdf()" class="btn btn-action btn-action-danger btn-sm shadow-sm fw-semibold d-inline-flex align-items-center gap-2">
                    <span class="btn-action-icon"><i class="bi bi-file-earmark-pdf"></i></span>
                    <span>Unduh PDF</span>
                </button>
                <button type="button" onclick="printReport()" class="btn btn-action btn-action-primary btn-sm shadow-sm fw-semibold d-inline-flex align-items-center gap-2">
                    <span class="btn-action-icon"><i class="bi bi-printer"></i></span>
                    <span>Cetak</span>
                </button>
            </div>
        @endif
    </x-slot:actions>

    <!-- Daftar Isi Data -->
    @foreach($data as $index => $row)
        @php
            $rowClass = '';
            if (isset($type) && $type === 'duplicate') {
                $groupKey = $row->duplicate_group_key;
                if (!isset($groupColors[$groupKey])) {
                    $groupColors[$groupKey] = ($colorIndex++ % 2 === 0);
                }
                
                // Gunakan class dup-highlight jika bernilai true dan merupakan grup ganda riil
                if ($groupColors[$groupKey] && !str_starts_with($groupKey, 'none_')) {
                    $rowClass = 'class="dup-highlight"';
                }
            }
        @endphp
        <tr {!! $rowClass !!}>
            <td class="text-center text-secondary small fw-medium">
                {{ $data->firstItem() + $index }}
            </td>
            @foreach($headers as $key => $label)
                <td>
                    @if($key === 'no_polisi')
                        <span class="plate-number">{{ strtoupper(trim($row->{$key})) }}</span>
                    @elseif($key === 'nilai_perolehan')
                        <span class="fw-bold text-navy">
                            Rp{{ number_format($row->{$key}, 0, ',', '.') }}
                        </span>
                    @elseif($key === 'kondisi')
                        @php
                            $val = strtoupper(trim((string)$row->{$key}));
                            $badgeClass = match(true) {
                                in_array($val, ['BAIK', 'B']) => 'bg-success-subtle text-success border border-success-subtle',
                                in_array($val, ['RUSAK RINGAN', 'RR']) => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                in_array($val, ['RUSAK BERAT', 'RB']) => 'bg-danger-subtle text-danger border border-danger-subtle',
                                in_array($val, ['HILANG', 'H']) => 'bg-dark-subtle text-dark border border-dark-subtle',
                                default => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }} px-2.5 py-1.5 rounded-pill small fw-semibold">
                            {{ $row->{$key} }}
                        </span>
                    @elseif($key === 'status')
                        @php
                            $val = strtolower(trim((string)$row->{$key}));
                            $badgeClass = match(true) {
                                in_array($val, ['tersedia', 'aktif', 'available']) => 'bg-info-subtle text-info border border-info-subtle',
                                in_array($val, ['digunakan', 'dipinjam', 'in_use', 'used']) => 'bg-primary-subtle text-primary border border-primary-subtle',
                                default => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }} px-2.5 py-1.5 rounded-pill small fw-semibold">
                            {{ $row->{$key} }}
                        </span>
                    @elseif($key === 'stnk_ada' || $key === 'bpkb_ada')
                        @php
                            $isAda = strtolower($row->{$key}) === 'ada';
                            $badgeClass = $isAda ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
                        @endphp
                        <span class="badge {{ $badgeClass }} px-2.5 py-1.5 rounded small fw-semibold">
                            <i class="bi {{ $isAda ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }} me-1"></i>
                            {{ $row->{$key} }}
                        </span>
                    @elseif($key === 'tgl_stnk')
                        @if($row->{$key})
                            @php
                                $date = \Carbon\Carbon::parse($row->{$key});
                                $isExpired = $date->isPast();
                            @endphp
                            <span class="{{ $isExpired ? 'text-danger fw-semibold' : 'text-secondary' }}">
                                {{ $date->translatedFormat('d M Y') }}
                                @if($isExpired)
                                    <span class="badge bg-danger ms-1 text-white small px-1.5 py-0.5 rounded" style="font-size: 0.65rem;">Mati</span>
                                @endif
                            </span>
                        @else
                            <span class="text-secondary small italic">-</span>
                        @endif
                    @else
                        {{ $row->{$key} ?? '-' }}
                    @endif
                </td>
            @endforeach
        </tr>
    @endforeach

    <!-- Slot Paginasi -->
    <x-slot:pagination>
        {!! $data->links('pagination::bootstrap-5') !!}
    </x-slot:pagination>
</x-table-card>
