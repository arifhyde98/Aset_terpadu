# SPESIFIKASI FITUR: CRUD RIWAYAT PROSES BPN (SIPAT ASET TANAH)

## 1. Ringkasan Eksekutif
- **Nama Fitur**: CRUD Riwayat Proses BPN
- **Modul Terkait**: SIPAT (Pertanahan & Sertifikasi BPN)
- **Tujuan Bisnis**: Memungkinkan pengelola aset tanah memutakhirkan, memperbaiki, atau menghapus entri histori kronologi pengurusan sertifikat di BPN jika terjadi kesalahan pencatatan atau kebutuhan koreksi data.
- **User Target**: Superadmin, Admin, dan Pengelola Aset OPD/KPB pengampu
- **Tingkat Prioritas**: P1 - Tinggi

## 2. User Story
Sebagai Pengelola Aset Tanah (Superadmin / Admin / OPD),
Saya ingin mengedit dan menghapus entri riwayat status proses BPN pada detail aset tanah,
Sehingga data kronologi pensertifikatan aset tanah selalu akurat dan berurutan sesuai fakta lapangan.

## 3. Kriteria Keberterimaan (Acceptance Criteria)
- [x] Pengguna berwenang dapat melihat tombol Edit dan Hapus pada setiap item timeline di Tab 2 (Riwayat & Proses BPN).
- [x] Pengeditan status BPN memperbarui `id_status`, `tanggal_proses`, dan `keterangan`.
- [x] Penghapusan entri riwayat BPN menghapus record dari `proses_aset`.
- [x] Setiap aksi edit dan hapus diverifikasi dengan hak akses multi-tenancy OPD/KPB yang sama.
- [x] Setiap aksi edit dan hapus mencatat jejak audit (`audit_logs` / `Activity::logSipat`) dengan memuat diff `old_data` dan `new_data`.
- [x] Dashboard cache dibersihkan secara otomatis pasca pembaruan data.

## 4. Analisis Kebutuhan Arsitektur
- **Tabel Baru**: Tidak ada (menggunakan tabel `proses_aset`).
- **Kolom Baru**: Tidak ada.
- **Endpoint Rute**:
  - `PUT /sipat/aset/{aset}/proses/{proses}` (`sipat.aset.updateProses`)
  - `DELETE /sipat/aset/{aset}/proses/{proses}` (`sipat.aset.destroyProses`)
- **Kebutuhan Cache**: Memicu `SipatService::invalidateDashboardCache()` pada penciptaan, pengubahan, atau penghapusan riwayat proses BPN.
