@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Master Data Wilayah & Pejabat</h4>
            <p class="text-secondary mb-0">Pengaturan terpadu data Kecamatan, Desa, Camat, Kades, Pemohon SKPT, dan Judul Laporan.</p>
        </div>
    </div>    @php
        $activeTab = session('active_tab', 'kecamatan');
    @endphp

    <div class="card border-0 shadow-sm clean-card">
        <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
            <ul class="nav nav-tabs card-header-tabs" id="masterTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link {{ $activeTab === 'kecamatan' ? 'active fw-bold' : 'text-secondary' }}" data-bs-toggle="tab" data-bs-target="#tab-kecamatan" type="button">Kecamatan</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link {{ $activeTab === 'desa' ? 'active fw-bold' : 'text-secondary' }}" data-bs-toggle="tab" data-bs-target="#tab-desa" type="button">Desa / Kelurahan</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link {{ $activeTab === 'camat' ? 'active fw-bold' : 'text-secondary' }}" data-bs-toggle="tab" data-bs-target="#tab-camat" type="button">Camat</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link {{ $activeTab === 'kades' ? 'active fw-bold' : 'text-secondary' }}" data-bs-toggle="tab" data-bs-target="#tab-kades" type="button">Kepala Desa</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link {{ $activeTab === 'pemohon' ? 'active fw-bold' : 'text-secondary' }}" data-bs-toggle="tab" data-bs-target="#tab-pemohon" type="button">Pemohon SKPT</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link {{ $activeTab === 'judul' ? 'active fw-bold' : 'text-secondary' }}" data-bs-toggle="tab" data-bs-target="#tab-judul" type="button">Judul Laporan</button>
                </li>
            </ul>
        </div>
        
        <div class="card-body p-0">
            <div class="tab-content" id="masterTabsContent">
                
                <!-- TAB KECAMATAN -->
                <div class="tab-pane fade {{ $activeTab === 'kecamatan' ? 'show active' : '' }}" id="tab-kecamatan">
                    <div class="p-4 d-flex justify-content-end">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahKec"><i class="bi bi-plus-circle me-1"></i> Tambah Kecamatan</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary">
                                <tr>
                                    <th class="ps-4" width="8%">NO</th>
                                    <th>NAMA KECAMATAN</th>
                                    <th class="text-end pe-4" width="15%">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($kecamatan as $i => $row)
                                <tr>
                                    <td class="ps-4">{{ $i + 1 }}</td>
                                    <td class="fw-medium">{{ $row->nama }}</td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditKec{{ $row->id }}">Edit</button>
                                        <form action="{{ route('master.kecamatan.destroy', $row->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus kecamatan ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center py-4 text-secondary">Data belum tersedia.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB DESA -->
                <div class="tab-pane fade {{ $activeTab === 'desa' ? 'show active' : '' }}" id="tab-desa">
                    <div class="p-4 d-flex justify-content-end">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahDesa"><i class="bi bi-plus-circle me-1"></i> Tambah Desa/Kel</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary">
                                <tr>
                                    <th class="ps-4" width="8%">NO</th>
                                    <th>NAMA DESA/KEL</th>
                                    <th>JENIS</th>
                                    <th>KECAMATAN</th>
                                    <th class="text-end pe-4" width="15%">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($desa as $i => $row)
                                <tr>
                                    <td class="ps-4">{{ $i + 1 }}</td>
                                    <td class="fw-medium">{{ $row->nama }}</td>
                                    <td><span class="badge bg-secondary">{{ $row->jenis }}</span></td>
                                    <td>{{ $row->kecamatan->nama ?? '-' }}</td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditDesa{{ $row->id }}">Edit</button>
                                        <form action="{{ route('master.desa.destroy', $row->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus desa ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center py-4 text-secondary">Data belum tersedia.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB CAMAT -->
                <div class="tab-pane fade {{ $activeTab === 'camat' ? 'show active' : '' }}" id="tab-camat">
                    <div class="p-4 d-flex justify-content-end">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahCamat"><i class="bi bi-plus-circle me-1"></i> Tambah Camat</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary">
                                <tr>
                                    <th class="ps-4" width="8%">NO</th>
                                    <th>NAMA CAMAT</th>
                                    <th>NIP</th>
                                    <th>KECAMATAN</th>
                                    <th>STATUS PJ</th>
                                    <th class="text-end pe-4" width="15%">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($camat as $i => $row)
                                <tr>
                                    <td class="ps-4">{{ $i + 1 }}</td>
                                    <td class="fw-medium">{{ $row->nama }}</td>
                                    <td>{{ $row->nip ?: '-' }}</td>
                                    <td>{{ $row->kecamatan->nama ?? '-' }}</td>
                                    <td>{!! $row->aktif ? '<span class="badge bg-warning text-dark">PJ / Plt</span>' : '<span class="badge bg-success">Definitif</span>' !!}</td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditCamat{{ $row->id }}">Edit</button>
                                        <form action="{{ route('master.camat.destroy', $row->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus camat ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center py-4 text-secondary">Data belum tersedia.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB KADES -->
                <div class="tab-pane fade {{ $activeTab === 'kades' ? 'show active' : '' }}" id="tab-kades">
                    <div class="p-4 d-flex justify-content-end">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahKades"><i class="bi bi-plus-circle me-1"></i> Tambah Kades</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary">
                                <tr>
                                    <th class="ps-4" width="8%">NO</th>
                                    <th>NAMA KADES/LURAH</th>
                                    <th>NIP</th>
                                    <th>DESA/KEL</th>
                                    <th>STATUS PJ</th>
                                    <th class="text-end pe-4" width="15%">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($kades as $i => $row)
                                <tr>
                                    <td class="ps-4">{{ $i + 1 }}</td>
                                    <td class="fw-medium">{{ $row->nama }}</td>
                                    <td>{{ $row->nip ?: '-' }}</td>
                                    <td>{{ $row->desa->nama ?? '-' }} <br><small class="text-secondary">{{ $row->desa->kecamatan->nama ?? '' }}</small></td>
                                    <td>{!! $row->aktif ? '<span class="badge bg-warning text-dark">PJ / Plt</span>' : '<span class="badge bg-success">Definitif</span>' !!}</td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditKades{{ $row->id }}">Edit</button>
                                        <form action="{{ route('master.kades.destroy', $row->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus pejabat ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center py-4 text-secondary">Data belum tersedia.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB PEMOHON -->
                <div class="tab-pane fade {{ $activeTab === 'pemohon' ? 'show active' : '' }}" id="tab-pemohon">
                    <div class="p-4 d-flex justify-content-end">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahPemohon"><i class="bi bi-plus-circle me-1"></i> Tambah Pemohon</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary">
                                <tr>
                                    <th class="ps-4" width="8%">NO</th>
                                    <th>NAMA PEMOHON</th>
                                    <th>NIK</th>
                                    <th>JABATAN</th>
                                    <th class="text-end pe-4" width="15%">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pemohon as $i => $row)
                                <tr>
                                    <td class="ps-4">{{ $i + 1 }}</td>
                                    <td class="fw-medium">{{ $row->nama }}</td>
                                    <td>{{ $row->nik ?: '-' }}</td>
                                    <td>{{ $row->jabatan ?: '-' }}</td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditPemohon{{ $row->id }}">Edit</button>
                                        <form action="{{ route('master.pemohon.destroy', $row->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus pemohon ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center py-4 text-secondary">Data belum tersedia.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB JUDUL LAPORAN -->
                <div class="tab-pane fade {{ $activeTab === 'judul' ? 'show active' : '' }}" id="tab-judul">
                    <div class="p-4 d-flex justify-content-end">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahJudul"><i class="bi bi-plus-circle me-1"></i> Tambah Judul</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary">
                                <tr>
                                    <th class="ps-4" width="8%">NO</th>
                                    <th>JUDUL LAPORAN</th>
                                    <th>STATUS</th>
                                    <th class="text-end pe-4" width="15%">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($judul as $i => $row)
                                <tr>
                                    <td class="ps-4">{{ $i + 1 }}</td>
                                    <td class="fw-medium">{{ $row->judul }}</td>
                                    <td>{!! $row->aktif ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>' !!}</td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditJudul{{ $row->id }}">Edit</button>
                                        <form action="{{ route('master.judul.destroy', $row->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus judul ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center py-4 text-secondary">Data belum tersedia.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!--                 MODALS SECTION                -->
<!-- ============================================= -->

@push('modals')
<!-- KECAMATAN -->
<div class="modal fade" id="modalTambahKec" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.kecamatan.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Tambah Kecamatan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><div class="mb-3"><label class="form-label">Nama Kecamatan</label><input type="text" name="nama" class="form-control" required></div></div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@foreach($kecamatan as $row)
<div class="modal fade" id="modalEditKec{{ $row->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.kecamatan.update', $row->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header"><h5 class="modal-title">Edit Kecamatan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><div class="mb-3"><label class="form-label">Nama Kecamatan</label><input type="text" name="nama" class="form-control" value="{{ $row->nama }}" required></div></div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@endforeach

<!-- DESA -->
<div class="modal fade" id="modalTambahDesa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.desa.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Tambah Desa/Kelurahan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kecamatan</label>
                        <select name="kecamatan_id" class="form-select" required>
                            <option value="">-- Pilih Kecamatan --</option>
                            @foreach($kecamatan as $k) <option value="{{ $k->id }}">{{ $k->nama }}</option> @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Nama Desa/Kel</label><input type="text" name="nama" class="form-control" required></div>
                    <div class="mb-3">
                        <label class="form-label">Jenis</label>
                        <select name="jenis" class="form-select" required><option value="Desa">Desa</option><option value="Kelurahan">Kelurahan</option></select>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@foreach($desa as $row)
<div class="modal fade" id="modalEditDesa{{ $row->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.desa.update', $row->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header"><h5 class="modal-title">Edit Desa/Kelurahan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kecamatan</label>
                        <select name="kecamatan_id" class="form-select" required>
                            @foreach($kecamatan as $k) <option value="{{ $k->id }}" {{ $k->id == $row->kecamatan_id ? 'selected' : '' }}>{{ $k->nama }}</option> @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Nama Desa/Kel</label><input type="text" name="nama" class="form-control" value="{{ $row->nama }}" required></div>
                    <div class="mb-3">
                        <label class="form-label">Jenis</label>
                        <select name="jenis" class="form-select" required><option value="Desa" {{ $row->jenis == 'Desa' ? 'selected' : '' }}>Desa</option><option value="Kelurahan" {{ $row->jenis == 'Kelurahan' ? 'selected' : '' }}>Kelurahan</option></select>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@endforeach

<!-- CAMAT -->
<div class="modal fade" id="modalTambahCamat" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.camat.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Tambah Camat</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kecamatan</label>
                        <select name="kecamatan_id" class="form-select" required>
                            <option value="">-- Pilih Kecamatan --</option>
                            @foreach($kecamatan as $k) <option value="{{ $k->id }}">{{ $k->nama }}</option> @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Nama Camat</label><input type="text" name="nama" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">NIP</label><input type="text" name="nip" class="form-control"></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="aktif" id="pjCamatAdd"><label class="form-check-label" for="pjCamatAdd">Status PJ / Plt</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@foreach($camat as $row)
<div class="modal fade" id="modalEditCamat{{ $row->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.camat.update', $row->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header"><h5 class="modal-title">Edit Camat</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kecamatan</label>
                        <select name="kecamatan_id" class="form-select" required>
                            @foreach($kecamatan as $k) <option value="{{ $k->id }}" {{ $k->id == $row->kecamatan_id ? 'selected' : '' }}>{{ $k->nama }}</option> @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Nama Camat</label><input type="text" name="nama" class="form-control" value="{{ $row->nama }}" required></div>
                    <div class="mb-3"><label class="form-label">NIP</label><input type="text" name="nip" class="form-control" value="{{ $row->nip }}"></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="aktif" id="pjCamatEdit{{ $row->id }}" {{ $row->aktif ? 'checked' : '' }}><label class="form-check-label" for="pjCamatEdit{{ $row->id }}">Status PJ / Plt</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@endforeach

<!-- KADES -->
<div class="modal fade" id="modalTambahKades" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.kades.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Tambah Kades/Lurah</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Desa / Kelurahan</label>
                        <select name="desa_id" class="form-select" required>
                            <option value="">-- Pilih Desa --</option>
                            @foreach($desa as $d) <option value="{{ $d->id }}">{{ $d->nama }} ({{ $d->kecamatan->nama ?? '' }})</option> @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Nama Kades/Lurah</label><input type="text" name="nama" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">NIP</label><input type="text" name="nip" class="form-control"></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="aktif" id="pjKadesAdd"><label class="form-check-label" for="pjKadesAdd">Status PJ / Plt</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@foreach($kades as $row)
<div class="modal fade" id="modalEditKades{{ $row->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.kades.update', $row->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header"><h5 class="modal-title">Edit Kades/Lurah</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Desa / Kelurahan</label>
                        <select name="desa_id" class="form-select" required>
                            @foreach($desa as $d) <option value="{{ $d->id }}" {{ $d->id == $row->desa_id ? 'selected' : '' }}>{{ $d->nama }} ({{ $d->kecamatan->nama ?? '' }})</option> @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Nama Kades/Lurah</label><input type="text" name="nama" class="form-control" value="{{ $row->nama }}" required></div>
                    <div class="mb-3"><label class="form-label">NIP</label><input type="text" name="nip" class="form-control" value="{{ $row->nip }}"></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="aktif" id="pjKadesEdit{{ $row->id }}" {{ $row->aktif ? 'checked' : '' }}><label class="form-check-label" for="pjKadesEdit{{ $row->id }}">Status PJ / Plt</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@endforeach

<!-- PEMOHON -->
<div class="modal fade" id="modalTambahPemohon" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.pemohon.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Tambah Pemohon</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" name="nama" class="form-control" required></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">NIK</label><input type="text" name="nik" class="form-control"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Pekerjaan</label><input type="text" name="pekerjaan" class="form-control"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Jabatan (Opsional)</label><input type="text" name="jabatan" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@foreach($pemohon as $row)
<div class="modal fade" id="modalEditPemohon{{ $row->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.pemohon.update', $row->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header"><h5 class="modal-title">Edit Pemohon</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" name="nama" class="form-control" value="{{ $row->nama }}" required></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">NIK</label><input type="text" name="nik" class="form-control" value="{{ $row->nik }}"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Pekerjaan</label><input type="text" name="pekerjaan" class="form-control" value="{{ $row->pekerjaan }}"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Jabatan (Opsional)</label><input type="text" name="jabatan" class="form-control" value="{{ $row->jabatan }}"></div>
                    <div class="mb-3"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" rows="2">{{ $row->alamat }}</textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@endforeach

<!-- JUDUL LAPORAN -->
<div class="modal fade" id="modalTambahJudul" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.judul.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Tambah Judul Laporan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Judul Laporan</label><input type="text" name="judul" class="form-control" required></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="aktif" id="aktifJudulAdd" checked><label class="form-check-label" for="aktifJudulAdd">Aktif</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@foreach($judul as $row)
<div class="modal fade" id="modalEditJudul{{ $row->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.judul.update', $row->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header"><h5 class="modal-title">Edit Judul Laporan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Judul Laporan</label><input type="text" name="judul" class="form-control" value="{{ $row->judul }}" required></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="aktif" id="aktifJudulEdit{{ $row->id }}" {{ $row->aktif ? 'checked' : '' }}><label class="form-check-label" for="aktifJudulEdit{{ $row->id }}">Aktif</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endpush

<style>
    .clean-card .nav-tabs .nav-link {
        border: none;
        border-bottom: 2px solid transparent;
        padding: 1rem 1.5rem;
        color: #64748b;
    }
    .clean-card .nav-tabs .nav-link.active {
        color: #0f172a;
        border-bottom: 2px solid #0d6efd;
        background: transparent;
    }
    .clean-card .nav-tabs .nav-link:hover:not(.active) {
        border-bottom: 2px solid #cbd5e1;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const hash = window.location.hash;
        if(hash) {
            const tabButton = document.querySelector(`button[data-bs-target="${hash}"]`);
            if(tabButton) {
                const tab = new bootstrap.Tab(tabButton);
                tab.show();
            }
        }
        
        const tabs = document.querySelectorAll('button[data-bs-toggle="tab"]');
        tabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', function (event) {
                const target = event.target.getAttribute('data-bs-target');
                history.pushState(null, null, target);
            });
        });
    });
</script>
@endsection
