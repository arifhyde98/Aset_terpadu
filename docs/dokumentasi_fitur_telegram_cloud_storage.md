# 📚 DOKUMENTASI TEKNIS SISTEM TELEGRAM CLOUD STORAGE & DUAL-REDUNDANCY
## Aplikasi SIPAT Terpadu (Sistem Informasi Peralatan & Aset Terpadu)

> **Dokumen Referensi Sistem & Arsitektur**  
> *Tanggal Penyelesaian:* 24 Agustus 2026  
> *Status Integrasi:* 100% Selesai & Aktif di Lingkungan Produksi  

---

## 1. Ringkasan Arsitektur (Dual-Redundancy Policy)

Sistem Penyimpanan SIPAT Terpadu menerapkan kebijakan **Dual-Redundancy Cloud Storage**:
1. **Salinan Fisik Lokal (Local Backup)**: Seluruh berkas scan PDF (BPKB & Sertifikat Tanah) **TIDAK AKAN PERNAH DIHAPUS** dari harddisk server (`storage/app/public/elabel/`).
2. **Akses Utama Cloud Storage (Telegram Cloud)**: Berkas diunggah ke Telegram Cloud Storage dan jalurnya disimpan di database dengan format `tg:<file_id>`.
3. **Fallback Otomatis**: Jika koneksi internet server terputus/offline, aplikasi secara otomatis beralih membaca berkas cadangan lokal dari harddisk tanpa pesan error.

---

## 2. Fitur & Komponen Utama

### A. Layanan Storage (`App\Services\TelegramStorageService`)
- `uploadFile(UploadedFile $file, string $caption)`: Mengunggah berkas ke Telegram Bot via API `sendDocument` dengan fitur **Auto-Retry 3x (`Http::retry(3, 2000)`)** dan penanganan log yang aman dari *Permission Denied*.
- `getDirectUrl(string $fileId)`: Mengambil tautan unduh langsung (*Direct CDN URL*) dari Telegram API.
- `streamToBrowserWithFallback(string $fileId, ?string $fallbackLocalPath, ?string $downloadName)`: Menayangkan berkas PDF secara **Streaming Inline (`Content-Disposition: inline`)** langsung di dalam tab browser pengguna.

### B. Perintah CLI Artisan
1. **Sinkronisasi Berkas Masal**:
   ```bash
   php artisan storage:sync-to-telegram
   ```
   - Mengunggah seluruh berkas BPKB dan Sertifikat Tanah lokal yang belum ada di Telegram.
   - **Bersifat Resumable**: Dapat dihentikan dan dilanjutkan kapan saja tanpa mengulang dari awal.
   - **Background Ready**: Dapat dijalankan di background dengan `Ctrl+Z` $\rightarrow$ `bg` $\rightarrow$ `disown -h`.

2. **Backup Otomatis Tengah Malam**:
   ```bash
   php artisan backup:run-bg
   ```
   - Berjalan otomatis setiap jam **00:00 WITA**.
   - Membuat arsip `.zip` database & file, lalu mengirimkan salinannya langsung ke Telegram Bot `@Sipat_backup`.

---

## 3. Komponen Antarmuka pengguna (UI/UX Badging)

Untuk memberikan transparansi lokasi penyimpanan berkas kepada pengguna, telah ditambahkan **Badge Penanda Visual**:

1. **Pada Tabel Katalog (BPKB & Sertifikat Tanah)**:
   - 🔵 **Tombol Biru (`Telegram`)**: Berkas tersimpan & disajikan secara utama dari Telegram Cloud.
   - ⚪ **Tombol Abu-abu (`Lokal`)**: Berkas disajikan dari cadangan Harddisk Lokal.
2. **Pada Halaman Detail Dokumen (Pojok Kanan Atas)**:
   - ☁️ `Telegram Cloud` (Badge Pill Biru)
   - 📁 `Harddisk Lokal` (Badge Pill Abu-abu)

---


---

## 5. Riwayat Poin Penting Perbaikan (Troubleshooting Log)

| Tanggal | Masalah | Solusi |
| :--- | :--- | :--- |
| 24/08/2026 | *Permission Denied* pada Monolog File Log saat CLI crash | Membungkus pencatatan log dengan *safe try-catch* di `TelegramStorageService`. |
| 24/08/2026 | cURL Timeout 28 / Kedipan Jaringan | Memasang `Http::retry(3, 2000)->timeout(120)` pada Telegram HTTP Client. |
| 24/08/2026 | Browser mengunduh PDF secara otomatis (bukan tayang di tab) | Memasang header HTTP `Content-Disposition: inline` pada fungsi streaming response. |

---
*Dokumen Dokumentasi Resmi SIPAT Terpadu v2.0*
