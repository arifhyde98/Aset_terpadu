# SPESIFIKASI FITUR: Silent Background OCR Staging & Selective Review BPKB

## 1. Ringkasan Eksekutif
- **Nama Fitur**: Silent Background OCR Staging & Selective Review BPKB
- **Modul Terkait**: eLABEL (Modul Arsip Fisik BPKB)
- **Tujuan Bisnis**: Membaca otomatis berkas scan BPKB (PDF) yang sudah ada di server secara senyap di latar belakang, menampung hasil ekstrak di buffer staging, dan menyajikan modal review komparasi interaktif dengan checklist per kolom sebelum di-commit ke database utama. Mencegah input manual berulang dan memastikan integritas data produksi 100% aman (human-in-the-loop).
- **User Target**: Superadmin, Admin Aset / Petugas eLABEL
- **Tingkat Prioritas**: P1 - Tinggi

## 2. User Story
Sebagai Petugas eLABEL / Admin Aset,
Saya ingin berkas scan PDF BPKB yang sudah tersimpan di server diproses otomatis secara silent di latar belakang ke dalam daftar saran hasil scan,
Sehingga saat saya membuka katalog BPKB, saya dapat langsung meninjau perbandingan data sekarang vs hasil scan dan memilih kolom mana saja yang ingin disalin melalui checklist tanpa harus menunggu proses scan 1 per 1 atau mengetik manual 10+ field.

## 3. Kriteria Keberterimaan (Acceptance Criteria)
- [x] Ekstraksi di latar belakang tidak memblokir user dan tidak mengubah data `elabel_bpkb` secara langsung (zero breaking changes).
- [x] Tabel staging `elabel_bpkb_ocr_staging` mengisolasi hasil scan dengan status `pending`, `applied`, `rejected`, `failed`.
- [x] Indikator pill / filter cepat di halaman BPKB menampilkan jumlah data yang memiliki rekomendasi scan siap ditinjau.
- [x] Modal review menampilkan perbandingan berdampingan (*Side-by-Side*): data DB saat ini vs data hasil scan, lengkap dengan tautan pratinjau PDF.
- [x] Terdapat checklist selektif interaktif: kolom yang kosong di DB otomatis tercentang; kolom yang tidak ingin diubah dapat di-uncheck.
- [x] Aksi approval menerapkan hanya kolom yang dicentang ke tabel `elabel_bpkb` dan mencatat riwayat ke `elabel_activity_logs`.
- [x] Tidak merusak fungsionalitas CRUD, ekspor, impor, dan modal edit/detail yang sudah berjalan di production.

## 4. Analisis Kebutuhan Arsitektur
- **Tabel Baru**: `elabel_bpkb_ocr_staging`
- **Model Baru**: `App\Models\Elabel\ElabelBpkbOcrStaging`
- **Service Baru**: `App\Services\Elabel\BpkbOcrExtractionService`
- **Artisan Command**: `App\Console\Commands\ElabelBpkbScanWorker` (`php artisan elabel:bpkb-scan-worker`)
- **Endpoint Rute Baru**:
  - `GET /elabel/bpkb-staging/summary` (JSON jumlah & daftar pending)
  - `GET /elabel/bpkb-staging/{id}/review` (JSON detail staging vs data DB)
  - `POST /elabel/bpkb-staging/{id}/apply` (Commit kolom terpilih ke database)
  - `POST /elabel/bpkb-staging/{id}/reject` (Tolak/abaikan saran scan)
  - `POST /elabel/bpkb/{id}/trigger-scan` (Trigger scan on-demand untuk 1 BPKB jika diperlukan)
- **Relasi Foreign Key**: `bpkb_id` -> `elabel_bpkb.id` (`onDelete: cascade`), `reviewed_by` -> `users.id` (`onDelete: set null`).
- **Audit Trail**: Dicatat ke `elabel_activity_logs` dengan format action `update_from_ocr`.
