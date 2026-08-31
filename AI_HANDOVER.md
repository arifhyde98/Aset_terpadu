# 🤖 AI Handover & Architecture Guide: SIPAT Terpadu

Dokumen ini merupakan sumber kebenaran tunggal (*Single Source of Truth*) mengenai arsitektur, jejak rekam fitur, skema database, konvensi antarmuka (UI/UX), dan aturan backend untuk platform **SIPAT Terpadu** yang mengintegrasikan tiga modul utama: **E-RANDIS** (Manajemen Kendaraan Dinas), **SIPAT** (Administrasi Pertanahan/Aset Tanah), dan **eLABEL** (Labelisasi & Pengarsipan Fisik Dokumen).

**Setiap agen AI yang melanjutkan pengembangan proyek ini DIWAJIBKAN membaca dokumen ini terlebih dahulu untuk menjaga konsistensi standar kode dan kelangsungan arsitektur.**

**⚠️ PENTING: Untuk penambahan fitur baru, WAJIB membaca dan mengikuti `ATURAN_PENAMBAHAN_FITUR.md` terlebih dahulu.**

----

## 1. 🛠️ Environment & Technology Stack
- **Framework Core:** Laravel 12 / PHP 8.2+
- **Database:** MySQL / MariaDB (Teroptimasi dengan skema B-tree Indexing)
- **Timezone:** Dikonfigurasi secara terpusat melalui variabel lingkungan `APP_TIMEZONE` di `.env` & `config/app.php`.
- **Frontend / Assets:** 
  - **Bundler:** Vite
  - **UI Framework:** Bootstrap 5 (Customized via SCSS tersentralisasi di `app.scss`)
  - **Iconography:** Bootstrap Icons (Local via NPM/Vite)
  - **Notifications:** SweetAlert2 (Local via NPM/Vite) untuk peringatan, validasi *real-time*, & konfirmasi aksi
  - **Typography:** Plus Jakarta Sans (Local via @fontsource) & Monospace untuk plat nomor/kode box.
  - **GIS & Pemetaan Spasial:** Leaflet.js, Turf.js (`turf.min.js`), Shp.js (`shp.js`), Leaflet.Draw (`leaflet.draw.js`/`css`), serta aset GeoJSON dan Shapefile (.shp/.dbf) untuk render peta interaktif sebaran aset tanah & target pensertifikatan.
- **Data Engine:** Laravel Excel (Maatwebsite/Excel) sebagai mesin utama pengolahan Impor & Ekspor data massal, serta mPDF sebagai mesin render PDF formal server-side pada Modul Laporan, ekspor Surat SKPT, dan cetak dokumen.
- **Infrastruktur / Deployment:** Berjalan secara native di Linux / server lokal (Nginx + PHP-FPM 8.2+ & MySQL/MariaDB).
- **CI/CD Automation:** Menggunakan GitHub Actions (`.github/workflows/deploy.yml`) yang memicu eksekusi remote script `deploy.sh` (Git Pull, Composer Install, Artisan Migrate, NPM Build Vite, & Artisan Optimize) via SSH Key saat ada push ke branch `main`.

---

## 2. Arsitektur & Keamanan (Multi-Role & Multi-Tenancy)
*   **Role System**: Menggunakan Enum `App\Enums\UserRole` (SUPERADMIN, ADMIN, OPD).
*   **Data Isolation & Access Control (Fail-Safe)**: 
    *   **E-RANDIS**: Implementasi `App\Models\Scopes\TenantScope` pada model `Vehicle`. Admin OPD secara otomatis dibatasi aksesnya hanya pada `opd_id` miliknya. Jika `opd_id` hilang/null, sistem tetap mengunci akses (bukan membuka akses global) untuk keamanan maksimal.
    *   **SIPAT & eLABEL**: Data diisolasi berdasarkan instansi OPD. Kolom `opd_id` pada model `AsetTanah` terhubung ke tabel `opd` (`OpdSipat` model). Data `sipat_opd_id` pada tabel `elabel_bpkb`, `elabel_sertifikat_tanah`, dll, menjamin kepemilikan arsip per OPD.
*   **Keamanan Pengontrol (Auth Security)**: Sesuai hasil Audit Keamanan terbaru, seluruh controller modul SIPAT, eLABEL, dan Master Data wajib menggunakan interface `HasMiddleware` dengan sintaks standar Laravel 12 `new Middleware('auth')` untuk mencegah bypass otorisasi.
*   **OPD Mapping Hub**: Untuk menjembatani data instansi lintas modul, tabel `opd_mappings` memetakan ID OPD dari modul SIPAT (`sipat_opd_id`) ke ID OPD modul E-RANDIS (`erandis_opd_id`).
*   **Otomasi Akun (Observer Level)**: Logika pembuatan akun admin OPD dijalankan melalui `OpdObserver::created()`. Hal ini menjamin setiap OPD baru (lewat Form atau Import Excel) selalu memiliki akun admin secara otomatis.
*   **Sistem Log Aktivitas (Audit Trail)**: Menggunakan tabel `activities` dan model `Activity`. Log audit mencatat riwayat aktivitas terpadu untuk aksi E-RANDIS, SIPAT, dan eLABEL yang dipicu oleh Observers pada model-model inti.
*   **Mekanisme Caching**: Statistik dashboard menggunakan *cache key* dinamis: `dashboard.stats.[role].[opd_id]`, sedangkan ringkasan Modul Laporan menggunakan `reports.summary.{role}.{scope}`. Seluruh aksi CRUD pada kendaraan dan OPD menggunakan helper terpusat `invalidateDashboardStats()` di `VehicleService` untuk melakukan *targeted invalidation* (bukan `Cache::flush()` global), sekaligus menyelaraskan pembersihan cache summary laporan agar cache pengaturan sistem (`setting.{key}`) tetap terjaga dan performa lebih optimal.
*   **Integritas Data (Hardened)**: 
    *   Database: `onDelete('cascade')` pada relasi `opd_id` di tabel `users` (telah disinkronkan ke mesin database MariaDB/MySQL).
    *   Audit: `onDelete('set null')` pada `user_id` di tabel `activities` untuk menjaga riwayat log tetap utuh meski akun dihapus.
    *   Storage: Eloquent Observer pada model `User` (Event `deleting`) otomatis menghapus file fisik `avatar` saat akun dihapus.

---

## 3. Skema Database Utama

### A. Tabel Core & E-RANDIS
*   **users**: Penambahan kolom `role` (string), `opd_id` (foreignId - Cascade ke `opds`), dan `avatar` (string - nullable).
*   **vehicles**: Penambahan kolom `opd_id` (foreignId) dan integrasi Global Scope.
*   **opds**: Master data instansi yang terhubung 1-to-1 dengan user admin OPD.
*   **activities**: Tabel log audit dengan relasi `user_id` (Set Null), menyimpan `description` dan `type` (untuk UI badging).
*   **settings**: Konfigurasi CMS yang dapat diubah melalui antarmuka admin.

### Tabel `vehicles`
- `id` (PK, BigInt)
- `no_polisi` (String, Unique) — Nomor plat kendaraan.
- `nomor_register` (String, Nullable, Unique) - Nomor register internal aset. Boleh kosong, tetapi jika diisi wajib unik global lintas OPD.
- `merk`, `tipe`, `warna`, `no_rangka`, `no_mesin` — Detail fisik aset.
- `tahun_pembuatan`, `tgl_perolehan`, `nilai_perolehan` — Akuntansi aset.
- `stnk_ada`, `bpkb_ada` (String: 'Ada' / 'Tidak') — Status kelengkapan dokumen.
- `status` (String) — Status operasional (Tersedia, Dipinjam, Nonaktif).
- `kondisi` (String) — Kondisi fisik kendaraan (Baik, Rusak Ringan, Rusak Berat, Hilang, Dalam Penelusuran).
- `opd` (String) & `pemegang` (String) — Teks penanggung jawab historis.
- **Foreign Keys:**
  - `opd_id` (Nullable FK ke `opds.id`, ON DELETE SET NULL)
  - `vehicle_type_id` (Nullable FK ke `vehicle_types.id`, ON DELETE SET NULL)

### B. Tabel Modul SIPAT (Pertanahan)
*   **opd**: Master data OPD khusus modul SIPAT / Pertanahan (`id`, `nama`, `aktif`). Model: `OpdSipat`.
*   **aset_tanah**: Menyimpan data aset tanah dengan kolom:
  - `id_aset` (PK, BigInt)
  - `kode_aset` (String)
  - `status_pencatatan` (Enum: `TERCATAT_KIB_A`, `USULAN_BELUM_TERCATAT`) — Penanda status pendaftaran KIB A resmi vs usulan/draft.
  - `nama_aset` (String)
  - `peruntukan` (String)
  - `luas` (Double)
  - `alamat` (Text)
  - `lat`, `lng` (Double) — Koordinat GPS
  - `geojson` (Text / JSON) — Data batas poligon GIS bidang tanah (GeoJSON Polygon)
  - `opd_id` (FK ke `opd.id`)
  - `opd` (String fallback)
  - `dasar_perolehan`, `harga_perolehan`, `tanggal_perolehan`, `keterangan`
*   **sipat_target_sertifikat**: Menyimpan penetapan kuota/target pensertifikatan tanah tahunan (`id`, `tahun`, `aset_tanah_id` [FK ke `sipat_aset_tanah.id`], `target_jumlah`, `keterangan`, `created_at`, `updated_at`). *(Relasi OPD diambil langsung dari `asetTanah->opdSipat` pasca-refaktorisasi).*
*   **proses_aset**: Tahapan progres sertifikasi tanah (`id_proses`, `id_aset`, `status_proses_id`, `tanggal`, `keterangan`, `dokumen`).
*   **surat_skpt**: Dokumen Surat Keterangan Pendaftaran Tanah (`id`, `aset_tanah_id`, `nomor_surat`, `tanggal_surat`, `pemohon_id`, `camat_id`, `kades_id`, `keterangan`).
*   **opd_mappings**: Tabel jembatan pemetaan OPD (`id`, `sipat_opd_id`, `erandis_opd_id`, `status_verifikasi`).

### C. Tabel Modul eLABEL (Pengarsipan Dokumen)
- **elabel_boxes**: Box penyimpanan fisik berkas BPKB.
- **elabel_box_years**: Tahun berkas di dalam box BPKB.
- **elabel_bpkb**: Katalog berkas BPKB (`id`, `box_id`, `plate_number`, `no_bpkb`, `nibar`, `vehicle_type`, `status`, `sipat_opd_id`, `pdf_path`).
- **elabel_bpkb_deletes**: Riwayat penyerahan/penghapusan BPKB keluar.
- **elabel_sertifikat_boxes**: Box penyimpanan fisik berkas sertifikat tanah.
- **elabel_sertifikat_tanah**: Katalog berkas sertifikat tanah.
- **elabel_surat_penyerahan**: Dokumen berita acara penyerahan berkas.
- **elabel_loans**: Log peminjaman berkas atau pengajuan scan request oleh OPD.

---

## 4. ⚙️ Backend Architecture & Aturan Validasi

### Lapisan Layanan (*Service Layer*)
Logika bisnis dan kalkulasi diletakkan di dalam kelas *Service*:
- `VehicleService`: statistik dashboard, helper cache kendaraan, pencarian, dan utilitas bisnis kendaraan.
- `ReportService`: ringkasan laporan, orkestrasi preview terpaginasi, dan integrasi strategi laporan modular.
- `AsetTanahService`: ringkasan dan query pencarian aset tanah SIPAT. Kueri Master Aset Tanah diurutkan menggunakan `CASE` SQL agar aset yang memiliki NIBAR resmi selalu berada di posisi paling atas, sedangkan tanah usulan / NIBAR sementara (`DRAFT-`, `BELUM-`, null, `-`) berada di posisi paling bawah.
- `ElabelSmartBpkbExtractorController`: modul terisolasi pada rute `/elabel/bpkb-smart-extractor` untuk pembacaan isi dokumen PDF BPKB otomatis (*Smart PDF Extractor & OCR*) dengan verifikasi 4 aturan presisi (Pencocokan Nopol 100% Persis, Proteksi Berkas Ganda, dan Dry-Run Audit Preview).
- `BackupController@restoreSql`: utilitas upload dan restore database secara menyeluruh dari berkas `.sql`, `.gz`, atau `.zip` dump database MySQL.
- `BackupController@syncDb`, `BackupController@syncDbStream` & `RunSyncDbBg`: utilitas replikasi/sinkronisasi penuh database staging dari `db_sipat_terpadu` ke `db_sipat_staging` menggunakan arsitektur **Server-Sent Events (SSE) streaming per-tabel** via `mysqldump --single-transaction --quick --extended-insert --add-drop-table` yang mengalirkan progress real-time per tabel ke antarmuka pengguna tanpa delay, bebas dari risiko HTTP 504 Gateway Timeout Cloudflare, dan menjadikan staging 100% identik dengan database sumber.

### Arsitektur Modul Laporan E-RANDIS (*Reporting Architecture*)
Modul Laporan dibangun secara modular menggunakan kombinasi **Service Layer**, **Registry Pattern**, dan **Strategy Pattern**:
- `ReportController`: menangani halaman laporan, preview AJAX, ekspor Excel, dan cetak browser.
- `ReportService`: mengorkestrasi summary laporan serta pemanggilan strategy aktif.
- `ReportSettingController`: menangani halaman pengaturan dokumen laporan khusus superadmin, termasuk kop surat, pejabat penanda tangan, dan aturan ekspor per tipe laporan.
- `ReportDocumentSettingService`: membaca konfigurasi dokumen per tipe laporan, menghubungkan `ReportExportSetting` dengan kop/pejabat aktif, dan menyediakan hardcoded fallback agar ekspor PDF tidak gagal ketika database kosong/bermasalah.
- `ReportRegistry`: memetakan tipe laporan ke strategy yang sesuai.
- `ReportStrategy`: kontrak bersama untuk seluruh jenis laporan, mendukung `referenceQuery` opsional untuk kueri referensi kustom global lintas OPD.
- `VehicleStatusReport`, `OpdAssetReport`, `DocumentValidityReport`, `DuplicateVehicleReport`: empat strategy laporan modular.
- `DynamicReportExport`: kelas induk abstrak untuk penataan dan pemetaan kolom Excel.
- `DynamicQueryReportExport` & `DynamicCollectionReportExport`: dua subclass yang membedakan kueri streaming hemat memori (`FromQuery`) untuk laporan standar dan ekspor berbasis koleksi (`FromCollection`) untuk laporan dengan pengayaan data.

### Validasi Kelas Permintaan (*Form Request Validation*)
Penyimpanan dan pembaruan data wajib menggunakan kelas validasi terpisah demi menjaga keamanan dan kebersihan pengontrol:
- `StoreVehicleRequest` / `UpdateVehicleRequest`: Validasi data kendaraan dinas dan isolasi `opd_id` untuk admin OPD.
- `StoreUserRequest` / `UpdateUserRequest`: Validasi manajemen pengguna dan role.
- `StoreSuratSkptRequest`: Validasi pembuatan SKPT dengan data relasi berjenjang (camat, kades, pemohon).
- `ReportFilterRequest`: Memvalidasi filter laporan dan memaksa `opd_id` user OPD kembali ke instansinya sendiri agar parameter URL tidak dapat dipakai untuk mengintip data tenant lain.

### Konvensi Middleware & Akses Rute (Laravel 12 Standard)
- Semua *Controller* wajib mengimplementasikan antarmuka `HasMiddleware` standar Laravel 12 dengan metode statis `middleware()`.
- **WAJIB menggunakan `new Middleware()` syntax** sesuai Laravel 12 best practice. Dilarang menggunakan string middleware langsung:
```php
// ✅ BENAR (Laravel 12 Best Practice)
public static function middleware(): array
{
    return [
        new Middleware('auth'),
        new Middleware('role:superadmin,admin'),
    ];
}
```

---

## 5. 🎨 Design System, Estetika & Standar UI
Aplikasi **menggunakan sentuhan visual premium & animasi mikro kustom** secara bijak (tanpa merusak nuansa formal pemerintahan) demi mengutamakan kenyamanan interaksi, kejelasan data, dan identitas formal instansi pemerintah.

### Palet & Gaya Visual
- **Skema Warna Formal:** Memprioritaskan **Navy (`#1E40AF`), Putih, dan Abu-abu (Gray)** yang stabil dan profesional.
- **Sentuhan Vanilla SCSS & Animasi Mikro Premium:** Seluruh peningkatan visual kustom diletakkan secara terisolasi di [_vanilla-touches.scss](file:///home/arif/Projek/SIPAT_Terpadu/resources/css/components/_vanilla-touches.scss):
  1. **Elevasi Kartu (`.hover-elevate`):** Kartu statistik dashboard, monitor cepat, dan aktivitas terbaru terangkat secara organik (`translateY(-5px)`) dibarengi bayangan halus yang melebar saat didekati kursor.
  2. **Dropdown Liquid Smooth (`.dropdown-menu`):** Dropdown Bootstrap bertransisi anggun meluncur dari atas (`translateY(12px)`) sembari memudar transparan saat dibuka.
  3. **Efek Sapuan Kilat (`.btn-premium-glow`):** Tombol Call-To-Action utama memancarkan efek sapuan kilatan cahaya metalik dari kiri ke kanan saat disorot kursor.
  4. **Skeleton Shimmer (`.skeleton-shimmer`):** Kerangka bayangan visual berkilau (efek loading shimmer) menggantikan pemutar spinner kaku saat proses pemuatan AJAX/pencarian data berlangsung.
  5. **Bouncy Liquid Modal (`.modal`):** Seluruh modal dialog aplikasi muncul dengan efek mengembang elastis yang mewah (bouncing transition dari `scale(0.96)` ke `scale(1)` dengan kurva `cubic-bezier(0.34, 1.56, 0.64, 1)`).
  6. **Glassmorphism Navbar (`#navbar-main`):** Navbar landing page bertransisi mulus menjadi kaca semi-transparan (`backdrop-filter: blur(12px)`) saat halaman digulir ke bawah (`.scrolled`).

---

## 6. 📦 Peta Fitur Penuh (*Full Feature Stack*)

### A. Modul E-RANDIS
- **Pencarian Publik Landing Page:** Antarmuka pencarian bagi masyarakat di rute `/` dan `/vehicle-search` yang diformat otomatis oleh `VehicleService::formatPlateNumber()`.
- **Impor Excel Massal AI (`/vehicles/import`):** Menggunakan kelas `VehicleImport` yang mendukung **AI Smart Import** (pemetaan kolom dinamis). Sistem menganalisis header Excel secara otomatis, mencocokkannya menggunakan algoritma kemiripan teks semantik, memilih sheet valid pertama, menampilkan visualisasi pratinjau data (3 baris sampel), lalu mengeksekusi impor menggunakan `import_token` aman.
- **Diagnosis & Resolusi Duplikasi Data:** Modul pendeteksi dan penyelesai duplikasi plat ganda hasil impor serta nomor mesin ganda secara global. Dilengkapi fitur resolusi gabung (*merge*) kendaraan dan penggabungan instansi OPD dengan kemiripan nama untuk mencegah inkonsistensi data.

### B. Modul SIPAT (Pertanahan)
- **Katalog Aset Tanah**: Pencatatan data tanah daerah, luas, dasar perolehan, harga, dan koordinat peta.
- **Target Pensertifikatan & Pemetaan GIS**: Pengelolaan KPI penetapan target pensertifikatan tanah tahunan (penetapan target, modal edit/update target, filter tahun/OPD), pelacakan progres real-time, ekspor rekapitulasi, dan visualisasi spasial interaktif menggunakan Leaflet GIS & Shapefile/GeoJSON.
- **Tanah Belum / Tak Tercatat**: Pengelolaan khusus bidang tanah yang belum masuk KIB A atau belum memiliki NIBAR resmi. Dilengkapi penomoran otomatis NIBAR Draft (`DRAFT-YYYYMMDD-XXXX`), modal pendaftaran cepat, dan fitur update sekali-klik ke NIBAR Resmi BPKAD.
- **Progres Sertifikasi**: Melacak status sertifikat tanah dari proses pendaftaran, pengukuran, hingga penerbitan.
- **Modul Surat Tanah (SKPT & Batas)**: Pembuatan dokumen SKPT formal dengan ekspor berkas PDF (mPDF), Word (.docx), dan cetak langsung. Sisa fungsi legacy `esc()` dan syntax error kurung pada template SKPT telah diganti standar Laravel `e()`.
- **Peta Aset**: Visualisasi marker sebaran aset tanah pada peta interaktif.
- **Target Pensertifikatan Tanah Tahunan**: Fitur penetapan KPI target pensertifikatan tahunan per OPD, pemantauan realisasi sertifikat BPN, filter multi-kriteria (Tahun, OPD, Status Capaian, Kata Kunci NIBAR/Peruntukan), serta ekspor Excel (.xlsx) dan PDF (mPDF) presisi.
- **Import Pertanahan**: Unggah massal progres sertifikat dan data aset tanah.

### C. Modul eLABEL (Pengarsipan & Labelisasi)
- **Katalog & Box BPKB**: Pengarsipan BPKB ke dalam box fisik, pencetakan stiker label barcode box, dan penggabungan/merge box BPKB.
- **Smart BPKB PDF Folder Scanner**: Pemindaian folder lokal server/PC dengan dry-run audit, penautan otomatis PDF ke record BPKB DB (`elabel/bpkb/`), timer elapsed pemindaian (S1), checkbox selektif (S2), pratinjau PDF di tab baru (S3), export hasil audit CSV (S4), dukungan nopol multi-prefix Sulawesi (S5), serta reset hasil audit (S6).
- **Sertifikat Tanah & Box**: Pengarsipan fisik sertifikat tanah dengan integrasi otomatis ke Master Aset Tanah (SIPAT), pemilihan lokasi/kecamatan terstandarisasi via dropdown Master Kecamatan SIPAT, dan operasi split (pecah) and merge (gabung) box.
- **Peminjaman Dokumen (Scan Request)**: Alur permohonan peminjaman berkas fisik atau file scan dokumen oleh operator OPD dengan validasi status persetujuan dari admin global.

---

## 7. 🗺️ Peta Rute Aplikasi (*Route Map*)

| Modul | Metode | URI | Controller@Method | Akses | Keterangan |
|---|---|---|---|---|---|
| **E-RANDIS** | GET | `/` | `VehicleController@search` | Publik | Landing page + pencarian |
| **E-RANDIS** | Resource | `/vehicles` | `VehicleController` | Auth | CRUD Kendaraan Dinas |
| **E-RANDIS** | POST | `/vehicles/import` | `VehicleController@import` | Auth | Eksekusi AI Smart Import |
| **E-RANDIS** | GET | `/reports` | `ReportController@index` | Auth | Dashboard Modul Laporan |
| **E-RANDIS** | GET | `/reports/pdf` | `ReportController@pdf` | Auth | Unduh PDF formal mPDF |
| **E-RANDIS** | GET | `/reports/settings` | `ReportSettingController@index` | Superadmin | Pengaturan kop, TTD, & ekspor |
| **SIPAT** | GET | `/sipat/aset` | `Sipat\AsetTanahController@index` | Auth | Daftar Aset Tanah |
| **SIPAT** | GET | `/sipat/tanah-tak-tercatat` | `Sipat\TanahTakTercatatController@index` | Auth | Tanah Belum / Tak Tercatat |
| **SIPAT** | POST/PUT | `/sipat/tanah-tak-tercatat/*` | `Sipat\TanahTakTercatatController` | Auth | CRUD Tanah Belum Tercatat & NIBAR Draft |
| **SIPAT** | GET | `/sipat/target-pensertifikatan` | `Sipat\TargetSertifikatController@index` | Auth | Target Pensertifikatan & GIS Map |
| **SIPAT** | POST/PUT/DEL | `/sipat/target-pensertifikatan/*` | `Sipat\TargetSertifikatController` | Auth | CRUD Target Pensertifikatan |
| **SIPAT** | GET | `/sipat/surat/skpt` | `Sipat\SuratController@skpt` | Auth | Modul Pembuatan SKPT |
| **SIPAT** | GET | `/sipat/peta` | `Sipat\PetaController@index` | Auth | Peta Interaktif Aset |
| **SIPAT** | GET | `/master-data/opd-sipat` | `MasterSipatOpdController` | Auth | CRUD OPD Modul SIPAT |
| **eLABEL** | GET | `/elabel/dashboard` | `Elabel\ElabelDashboardController@index` | Auth | Dashboard eLABEL |
| **eLABEL** | GET | `/elabel/bpkb` | `Elabel\ElabelBpkbController@index` | Auth | Katalog BPKB eLABEL |
| **eLABEL** | GET | `/elabel/bpkb-smart-extractor` | `Elabel\ElabelSmartBpkbExtractorController@index` | Auth | Halaman Smart BPKB Extractor |
| **eLABEL** | GET | `/elabel/bpkb-smart-extractor/preview` | `Elabel\ElabelSmartBpkbExtractorController@previewPdf` | Auth | Pratinjau PDF lokal di tab baru |
| **eLABEL** | GET | `/elabel/boxes/{id}/label` | `Elabel\ElabelBoxController@label` | Auth | Cetak Barcode Label Box |
| **eLABEL** | GET | `/elabel/sertifikat` | `Elabel\ElabelSertifikatController@index` | Auth | Katalog Sertifikat Tanah |
| **eLABEL** | GET | `/elabel/peminjaman` | `Elabel\ElabelLoanController@index` | Auth | Request Peminjaman Dokumen |
| **System** | POST | `/settings/backups/sync-db` | `BackupController@syncDb` | Auth | Trigger background sinkronisasi DB Staging |
| **System** | GET | `/settings/backups/sync-db-status` | `BackupController@syncDbStatus` | Auth | Polling status sinkronisasi DB Staging |
| **System** | GET | `/settings/backups/sync-db-stream` | `BackupController@syncDbStream` | Auth | Real-time SSE streaming sinkronisasi DB Staging |

---

## 8. 🚨 Aturan Kritis untuk Sesi AI Berikutnya
1. **Jangan asumsikan konteks:** Selalu gunakan `view_file` sebelum memodifikasi.
2. **Kepatuhan Desain:** Visual kustom wajib ditempatkan di `_vanilla-touches.scss`. Patuhi token warna tema.
3. **Bahasa Indonesia Wajib:** Seluruh interaksi UI, notifikasi, dan anotasi kode wajib menggunakan Bahasa Indonesia yang profesional.
4. **Keamanan HasMiddleware:** Controller baru wajib mengimplementasikan interface `HasMiddleware` standar Laravel 12.
5. **No Destructive DB Ops:** Jangan menyarankan *Soft Deletes* jika tidak ada di skema awal. Hormati arsitektur `Set Null` pada tabel Audit.
6. **Wajib Update Dokumentasi (.md):** Setiap kali ada perubahan rute, database/migration, controller/service, atau penambahan fitur baru, agen AI WAJIB langsung memperbarui `AI_HANDOVER.md`, `PROJECT_MASTER.md`, dan dokumen spesifikasi terkait (`docs/*.md`) sebelum mengakhiri sesi.
