# Nama Fitur: Nomor Register Kendaraan

## 1. Tujuan
Melengkapi identitas aset kendaraan dinas pemerintah daerah selain nomor polisi (plat nomor). Nomor register digunakan untuk identifikasi internal inventaris aset.

## 2. Target User
- **Superadmin**: Mengelola data global, diagnosis data ganda, dan pengaturan ekspor/impor.
- **Admin BMD**: Mengelola data seluruh OPD, melakukan pencarian, ekspor/impor, dan melihat laporan.
- **Admin OPD**: Mengelola data milik instansi sendiri (terisolasi secara multi-tenant).

## 3. Aturan Bisnis & Kriteria Penerimaan (Acceptance Criteria)
1. **Fleksibilitas Pengisian**:
   - Kolom `nomor_register` bersifat **opsional (nullable)**. Kendaraan dapat disimpan tanpa mengisi nomor register.
2. **Keunikan Data (Uniqueness)**:
   - Jika kolom `nomor_register` diisi, nilainya harus **unik secara global** di seluruh database (lintas OPD).
   - Kendaraan tidak boleh menggunakan nomor register yang sudah dipakai oleh kendaraan lain.
3. **Pencarian Admin**:
   - Pencarian pada dashboard admin dan halaman index kendaraan harus dapat menemukan data berdasarkan nomor register.
4. **CRUD Lengkap**:
   - Mendukung input/edit nomor register melalui form Single Page (Modal) maupun halaman terpisah (`create`/`edit`).
   - Tampil pada popup modal Detail dan halaman `show`.
5. **Ekspor & Impor Excel**:
   - Kolom "Nomor Register" disisipkan setelah "Nomor Polisi" pada berkas ekspor dan template impor.
   - Fitur **AI Smart Import** mendukung pemetaan otomatis header sinonim (seperti `kode register`, `no. register`, dsb).
   - Saat impor massal, nilai `nomor_register` yang kosong, `-`, atau `?` dianggap `null`.
   - Jika `nomor_register` terdeteksi duplikat di database atau sesama berkas Excel, proses impor ditolak dengan pesan error yang jelas.
   - Sistem tidak menambahkan suffix otomatis pada `nomor_register` karena nomor register adalah identitas unik aset.
6. **Keamanan Multi-Tenancy**:
   - Data instansi OPD tetap aman terisolasi di bawah `TenantScope`.
7. **Modul Laporan**:
   - Kolom Nomor Register disertakan pada seluruh strategi laporan kendaraan dinas (Status & Kondisi, Distribusi Aset OPD, Masa Berlaku Dokumen, dan Laporan Ganda).
