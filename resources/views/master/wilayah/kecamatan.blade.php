@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Master Kecamatan</h4>
            <p class="text-secondary mb-0">Kelola data kecamatan untuk keperluan SKPT.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-circle me-1"></i> Tambah Kecamatan
        </button>
    </div>

    <div class="card border-0 shadow-sm clean-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary">
                    <tr>
                        <th class="ps-4">NO</th>
                        <th>NAMA KECAMATAN</th>
                        <th class="text-end pe-4">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $i => $row)
                        <tr>
                            <td class="ps-4">{{ $i + 1 }}</td>
                            <td class="fw-medium">{{ $row->nama }}</td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $row->id_kecamatan }}">Edit</button>
                                <form action="{{ route('master.kecamatan.destroy', $row->id_kecamatan) }}" method="POST" class="d-inline delete-confirm">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>

                        <!-- Modal Edit -->
                        <div class="modal fade" id="modalEdit{{ $row->id_kecamatan }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('master.kecamatan.update', $row->id_kecamatan) }}" method="POST">
                                        @csrf @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Kecamatan</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Nama Kecamatan</label>
                                                <input type="text" name="nama" class="form-control" value="{{ $row->nama }}" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary">Simpan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-4 text-secondary">Data kosong</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('master.kecamatan.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kecamatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kecamatan</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
