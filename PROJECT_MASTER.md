# 📘 PROJECT MASTER DOCUMENTATION: SIPAT TERPADU
### (E-RANDIS, SIPAT, & eLABEL)

Dokumen ini merupakan ringkasan eksekutif dan arsitektur tingkat tinggi (*High-Level Architecture Master*) untuk platform **SIPAT Terpadu**. 

> **🔗 REFERENSI TEKNIS UTAMA:**
> - Untuk rincian baris kode, detail controller/service, skema database lengkap per kolom, dan aturan teknis mendalam: **WAJIB merujuk ke [`AI_HANDOVER.md`](file:///home/arifhyde98/Projek/Aset_terpadu/AI_HANDOVER.md)** (*Single Source of Truth*).
> - Untuk alur dan protokol penambahan fitur baru: **WAJIB membaca [`ATURAN_PENAMBAHAN_FITUR.md`](file:///home/arifhyde98/Projek/Aset_terpadu/ATURAN_PENAMBAHAN_FITUR.md)**.

---

## 📑 Daftar Isi
1. [Project Overview](#1-project-overview)
2. [Tech Stack](#2-tech-stack)
3. [System Architecture & Core Patterns](#3-system-architecture--core-patterns)
4. [Folder Structure](#4-folder-structure)
5. [Database Architecture & Relations](#5-database-architecture--relations)
6. [Coding Conventions & Standards](#6-coding-conventions--standards)
7. [Frontend Rules & Design System](#7-frontend-rules--design-system)
8. [Existing Features & Implementation Status](#8-existing-features--implementation-status)
9. [Deployment & Operations](#9-deployment--operations)

---

## 1. Project Overview

- **Nama Project:** SIPAT Terpadu (Sistem Informasi Pertanahan Aset Tanah, Kendaraan Dinas, dan Labelisasi Dokumen).
- **Tujuan Utama:** Mengintegrasikan pengelolaan aset tanah (SIPAT), kendaraan dinas (E-RANDIS), serta sistem labelisasi dan pengarsipan fisik dokumen berharga (eLABEL) dalam satu platform terpadu, *real-time*, akuntabel, dan aman di lingkungan Pemerintah Daerah (Pemerintah Kabupaten Donggala).
- **Modul Utama:**
  1. **E-RANDIS (Modul Kendaraan Dinas):** Pendataan fisik, riwayat penggunaan/pemegang, pelacakan kondisi, audit trail mutasi, rekonsiliasi berkas BPKB, serta impor/ekspor data kendaraan dinas lintas OPD.
  2. **SIPAT (Modul Aset Tanah & Pertanahan):** Pengelolaan legalitas sertifikasi aset tanah KIB A, pencatatan histori progres pensertifikatan BPN, penerbitan dokumen resmi SKPT (Surat Keterangan Pendaftaran Tanah), audit rekonsiliasi sertifikat e-Label, peta GIS sebaran spasial, serta pusat pelaporan resmi 11/12 kolom.
  3. **eLABEL (Modul Labelisasi & Universal Dynamic Archive Engine):** Digitalisasi dan pengarsipan fisik dokumen berharga (BPKB Kendaraan, Sertifikat Tanah, dan Surat Penyerahan) ke dalam box arsip khusus. Dilengkapi cetak barcode label, manajemen BPKB keluar (*soft deleted & restore*), split/merge box, scan request/peminjaman dokumen, OCR/PDF Smart Extractor BPKB, serta mesin arsip dinamis multi-kategori berbasis form builder kustom JSON.
  4. **Modul Gedung & Bangunan (KIB C):** Pendataan inventaris bangunan dan gedung pemerintah daerah sesuai Permendagri No. 19/2016 jo. Permendagri No. 7/2024, pengamanan hukum tertaut ke tanah KIB A (SIPAT), dokumen perizinan PBG/IMB ke eLABEL, klasifikasi standarisasi rumah jabatan/dinas tipe Khusus s/d E, visualisasi peta GIS sebaran gedung, mesin AI Smart Semantic Import, dan pusat pelaporan KIB C resmi 18 kolom mPDF.
- **User Target & Tingkatan Akses:**
  - `Superadmin`: Developer / Administrator Root Global (akses seluruh sistem, sinkronisasi staging DB, dan konfigurasi master).
  - `Admin`: Pengelola Aset BPKAD / Administrator BMD Daerah (verifikasi target, kontrol data lintas instansi, dan ekspor laporan resmi).
  - `OPD`: Operator Pengelola Aset tingkat unit kerja instansi/dinas (Pengguna Barang, akses instansi induk).
  - `KPB`: Operator Kuasa Pengguna Barang (Sub-OPD seperti Puskesmas, UPTD, Bagian Setda, terisolasi ketat pada data unit kerjanya).
- **Status Project:** Production-Ready (Fase Pemeliharaan, Optimasi Performa, & Integrasi Terpadu).

---

## 2. Tech Stack

- **Framework Core:** Laravel 12
- **PHP Version:** PHP 8.2+
- **Database Engine:** MySQL / MariaDB (Teroptimasi indeks B-Tree) & PostgreSQL / Supabase Compatible
  - Seluruh migrasi Laravel Blueprint bersifat idempoten (`Schema::hasTable(...)`).
  - *Aturan Baku:* Dilarang keras mengeksekusi `migrate:fresh` atau `migrate:reset` pada environment aktif.
- **Asset Bundler:** Vite
- **UI Framework:** Bootstrap 5 (Disesuaikan secara terpusat via SCSS modular)
- **Library & Package Esensial:**
  - `Maatwebsite/Excel` (Laravel Excel): Pengolahan impor dan ekspor data massal (.xlsx, .csv).
  - `mPDF`: Render dokumen PDF formal server-side (Laporan KIB A, Laporan Kendaraan, Surat SKPT, dan Laporan Target Sertifikat).
  - `Leaflet.js`, `turf.min.js`, & `shp.js`: Rendering peta spasial interaktif GIS, batas poligon GeoJSON, dan Shapefile (.shp/.dbf).
  - `SweetAlert2`: Sistem peringatan interaktif, modal diff, dan konfirmasi aksi CRUD tunggal.
  - `Bootstrap Icons`: Ikonografi antarmuka terpadu (NPM/Vite lokal).
  - `@fontsource/plus-jakarta-sans`: Tipografi utama modern.
- **Artificial Intelligence Engine:**
  - **Google Gemini Cloud AI (`GeminiAiService`):** Menggunakan API model stabil Google Gemini (seperti `gemini-1.5-flash`) via `GEMINI_API_KEY` untuk asisten cerdas konsultasi BMD, tanya-jawab aset, dan pembuatan ringkasan otomatis pada floating widget interaktif.
  - **Ollama Engine (`OllamaService`):** Fallback LLM lokal (seperti model `qwen2.5:7b`) untuk lingkungan tanpa koneksi internet langsung.
- **Deployment Target:** Server Lokal / VPS Linux (Nginx + PHP-FPM 8.2+ & MySQL/MariaDB).
- **CI/CD Pipeline:** GitHub Actions (`.github/workflows/deploy.yml`) memicu script `deploy.sh` via remote SSH Key pada branch `main`.

---

## 3. System Architecture & Core Patterns

- **Arsitektur Dasar:** Monolith (MVC Laravel) terintegrasi dengan pemisahan rute per modul di direktori `routes/`:
  - `routes/web.php` (Landing Page, Portal Publik Terpadu, Auth, User, AI Assistant, Health Check, & System Settings)
  - `routes/erandis.php` (Kendaraan Dinas, Rekon BPKB, & Laporan E-RANDIS)
  - `routes/sipat.php` (Aset Tanah, Target Pensertifikatan, Rekonsiliasi, SKPT, Master Wilayah, & Peta GIS)
  - `routes/elabel.php` (Katalog BPKB, BPKB Keluar, Sertifikat, Box Arsip, Smart Extractor, & Dynamic Archive)

- **Pola Desain & Integrasi Utama:**
  1. **Service Layer Pattern:** Memisahkan kalkulasi bisnis rumit dan query dari Controller ke kelas Service khusus:
     - `UnifiedAssetSearchService`: Layanan pencarian portal publik terpadu 3 modul dengan perlindungan privasi data.
     - `VehicleService`: Bisnis dan cache kendaraan dinas.
     - `AsetTanahService` (`App\Services\Sipat\`): Query katalog pertanahan (SQL Case NIBAR, query scopes, dan relasi kanonikal e-Label).
     - `ReportService`: Orkestrasi laporan modular E-RANDIS.
     - `LaporanService`: Mesin pengolah laporan KIB A resmi, resolusi judul 3 baris dinamis, dan penataan lembar pengesahan tanda tangan ganda.
     - `DynamicArchiveService` (`App\Services\Elabel\`): Engine formulir dan penyimpanan berkas arsip dinamis multi-lampiran.
     - `GeminiAiService`: Integrasi Google Gemini Cloud AI untuk floating widget asisten cerdas BMD.
  2. **Strategy & Registry Pattern (Modul Laporan E-RANDIS):**
     - Memetakan jenis laporan ke kelas strategi mandiri (`VehicleStatusReport`, `OpdAssetReport`, `DocumentValidityReport`, `DuplicateVehicleReport`) via `ReportRegistry`.
     - Mendukung pemisahan sumber data riil (`vehicles`) vs data e-BMD (`ebmd_vehicles`).
  3. **OPD Mapping Hub (Inter-Module Bridge):**
     - Menjembatani heterogenitas ID instansi antara Modul Pertanahan (`opd` / `OpdSipat`) dan Modul Kendaraan Dinas (`opds` / `Opd`) via tabel jembatan `opd_mappings`.
     - Didukung mesin sinkronisasi cerdas otomatis (`MasterOpdMappingController@refresh`) berbasis normalisasi teks dan heuristik akronim.
  4. **Data Isolation (Tenant Isolation):**
     - E-RANDIS: Implementasi `TenantScope` (Global Scope) pada model `Vehicle`. Jika `opd_id` bernilai null, sistem mengunci akses (*fail-safe*).
     - SIPAT & eLABEL: Isolasi data berdasarkan `opd_id` pada `AsetTanah` dan `sipat_opd_id` pada seluruh berkas arsip.
  5. **Observer Pattern & Audit Trail Terpadu:**
     - `VehicleObserver`, `UserObserver`, dan `OpdObserver` menangani perekaman snapshot data sebelum (`old_data`) dan sesudah (`new_data`) ke tabel `activities`.
     - Sanitasi otomatis membersihkan data kredensial (`password`, `plain_password`, `remember_token`).
     - Modul SIPAT menggunakan helper `Activity::logSipat()`, dan modul eLABEL mencatat ke `elabel_activity_logs`.
  6. **Auth & Security Flow (Laravel 12 Standard & SSO):**
     - Seluruh controller menggunakan interface `HasMiddleware` dengan deklarasi `new Middleware('auth')` dan `new Middleware('role:superadmin,admin')`.
     - Mendukung integrasi Single Sign-On (SSO) Pemerintah Kabupaten Donggala via `App\Http\Middleware\SsoAuthenticate` (alias `sso`).
     - Superadmin dan Admin memegang otoritas penuh mutasi kritis; pengguna ber-role OPD berstatus *read-only* pada master data dan target penetapan.
  7. **Bidirectional Cross-Module Integrity (SIPAT ↔ eLABEL):**
     - Sinkronisasi dua arah otomatis antara data aset tanah KIB A (`AsetTanah`) dan sertifikat fisik e-Label (`ElabelSertifikat`) untuk luas fisik tanah serta instansi kepemilikan OPD via `AsetTanahObserver` dan `ElabelSertifikatObserver`.
     - Didukung utilitas terminal: `php artisan sipat:sync-luas-sertifikat` dan `php artisan sipat:sync-opd-sertifikat {--dry-run}`.

---

## 4. Folder Structure

Struktur direktori proyek mengadopsi arsitektur modular yang rapi:

```text
Aset_terpadu/
├── app/
│   ├── Enums/            # Nilai enum statis (UserRole, VehicleStatus, VehicleCondition).
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/    # Controller sistem & administrasi (User, Profile, Setting, Backup, Activity, AuditLogs).
│   │   │   ├── Bangunan/ # Controller modul bangunan/gedung (KIB C).
│   │   │   ├── Elabel/   # Controller pengarsipan & Dynamic Archive (Bpkb, Sertifikat, Box, Smart Extractor).
│   │   │   ├── Erandis/  # Controller kendaraan dinas & laporan (Vehicle, VehicleType, Report, ReportSetting, SubOpd).
│   │   │   ├── Master/   # Controller data master lintas modul (MasterData, Wilayah, OpdMapping, MasterSipatOpd, StatusProses, KopSettings).
│   │   │   ├── Sipat/    # Controller pertanahan (AsetTanah, Dashboard, Import, TargetSertifikat, Surat, Laporan, Peta, Rekonsiliasi).
│   │   │   └── ...       # Controller root (Auth, LandingPage, AiAssistant, HealthCheck).
│   │   ├── Middleware/   # Middleware aplikasi, CheckRole, dan SsoAuthenticate.
│   │   └── Requests/     # FormRequest validasi terpusat (Admin/, Bangunan/, Elabel/, Erandis/, Sipat/, dan Shared).
│   ├── Models/           # Model Eloquent (Vehicle, AsetTanah, Opd, SubOpd, OpdSipat, OpdMapping, SuratSkpt, dsb).
│   │   ├── Elabel/       # Model khusus eLABEL (ElabelBpkb, ElabelBox, ElabelLoan, dll).
│   │   └── Dynamic/      # Model Universal Dynamic Archive (ArchiveType, ArchiveBox, ArchiveItem, dll).
│   ├── Observers/        # Observer database (VehicleObserver, UserObserver, OpdObserver, AsetTanahObserver, dll).
│   ├── Reports/          # Kelas Strategy, Registry, dan Export Excel laporan E-RANDIS.
│   └── Services/         # Lapisan logika bisnis inti.
│       ├── Bangunan/     # Service khusus Bangunan KIB C.
│       ├── Elabel/       # Service khusus eLABEL (DynamicArchiveService, ElabelDuplicateService).
│       ├── Erandis/      # Service khusus E-RANDIS (VehicleService, VehicleImport, VehicleQuery, ReportService, dll).
│       ├── Sipat/        # Service khusus SIPAT (AsetTanahService, SipatService, LaporanService).
│       └── ...           # Shared Service (UnifiedAssetSearchService, GeminiAiService, OllamaService).
│
├── database/
│   ├── migrations/       # Skema migrasi idempoten (Schema::hasTable).
│   └── seeders/          # Database seeders (ReportSettingSeeder, DynamicArchiveSeeder, dll).
│
├── resources/
│   ├── sass/             # SCSS Modular (7-1 Pattern):
│   │   ├── components/   # Tombol, Kartu, Tabel, Modal Bouncy, & _vanilla-touches.scss.
│   │   ├── abstracts/    # Variabel warna & mixins.
│   │   └── app.scss      # Titik masuk utama kompilasi SCSS.
│   ├── js/               # Inisialisasi Vite, Leaflet, SweetAlert2, dan script interaktif.
│   └── views/
│       ├── elabel/       # Template Blade modul pengarsipan dokumen fisik & dinamis.
│       ├── sipat/        # Template Blade modul pertanahan KIB A, peta, & laporan resmi.
│       ├── reports/      # Template Blade pratinjau & PDF laporan kendaraan dinas.
│       ├── layouts/      # Layout bersama & partials (ai-floating-widget, sidebar, footer).
│       └── ...           # Landing page, dashboard, dan halaman autentikasi.
│
├── routes/
│   ├── web.php           # Portal publik, autentikasi, AI assistant, health-check, dan pengaturan sistem.
│   ├── erandis.php       # Rute manajemen kendaraan dinas & rekon BPKB.
│   ├── sipat.php         # Rute manajemen pertanahan SIPAT, rekap OPD, & target sertifikat.
│   └── elabel.php        # Rute pengarsipan eLABEL & e-Arsip dinamis.
│
└── storage/
    └── app/public/       # Berkas upload fisik (avatar, scan PDF, KOP surat, dan lampiran arsip).
```

---

## 5. Database Architecture & Relations

### 5.1 Skema Relasional Modul Terpadu
- **Jembatan OPD:** `opd_mappings` menghubungkan `opd` (modul SIPAT/eLABEL) dengan `opds` (modul E-RANDIS) via `sipat_opd_id` dan `erandis_opd_id`.
- **Pertanahan (SIPAT):**
  - `aset_tanah` terhubung ke `opd` (SIPAT) via `opd_id`, dilengkapi kolom koordinat GPS (`lat`, `lng`), `geojson` batas poligon, dan relasi wilayah `kecamatan_id` & `desa_id`.
  - `sipat_target_sertifikat` mencatat target pensertifikatan tahunan yang berelasi dengan `aset_tanah`.
  - `proses_aset` mencatat histori langkah pensertifikatan BPN yang menunjuk ke `aset_tanah`.
  - `surat_skpt` mencatat berkas Surat Keterangan Pendaftaran Tanah yang terhubung ke `aset_tanah` serta pejabat pengesah (camat, kepala desa, dan pemohon).
- **Pengarsipan Berkas Fisik (eLABEL):**
  - `elabel_bpkb` dan `elabel_sertifikat_tanah` menunjuk ke box arsip fisiknya (`elabel_boxes` / `elabel_sertifikat_boxes`) dan terhubung ke `opd` (`sipat_opd_id`) untuk kepemilikan dokumen. Khusus `elabel_sertifikat_tanah`, model `ElabelSertifikat` memiliki relasi kanonikal `asetTanah()` (`nibar = kode_aset`) dengan sinkronisasi luas tanah dan kepemilikan OPD dua arah otomatis.
  - `elabel_bpkb_deletes` mencatat histori berkas BPKB keluar (*soft deleted*).
  - `elabel_loans` mengelola peminjaman/request scan berkas fisik oleh OPD dengan persetujuan admin.
- **Universal Dynamic Archive Engine (e-Arsip Dinamis):**
  - `archive_types`: Master tipe arsip dengan form builder kustom berbasis skema JSON (`schema_fields`).
  - `archive_boxes`: Box penyimpanan fisik dinamis universal dengan kode barcode otomatis (`BOX-{KODE}-{NUM}`).
  - `archive_items`: Data berkas arsip dengan metadata fleksibel (`metadata` JSON) dan berkas scan PDF utama.
  - `archive_attachments`: Multi-lampiran dokumen pendukung.
  - `archive_loans`: Alur permohonan pinjam berkas fisik dan scan berkas dinamis.
- **Akun & Kredensial:**
  - `users` menyimpan akun pengguna, dilengkapi kolom `plain_password` terenkripsi dua arah (AES-256 via cast `encrypted`) untuk kebutuhan distribusi akun ke OPD oleh Superadmin.

### 5.2 Strategi Pengindeksan (*Indexing Strategy*)
Indeks B-Tree diterapkan secara presisi pada kolom kunci asing dan filter pencarian: `opd_id`, `sipat_opd_id`, `box_id`, `archive_type_id`, `kecamatan_id`, `kode_aset`, nomor plat polisi, serta kolom status operasional guna menjaga latensi kueri tetap di bawah 100ms pada dataset besar.

---

## 6. Coding Conventions & Standards

1. **Implicit Route Model Binding:** Seluruh controller di modul SIPAT (`AsetTanahController`, dll) dan eLABEL wajib menggunakan Route Model Binding (contoh: `AsetTanah $aset`, `SuratSkpt $skpt`) agar kode controller ringkas dan deklaratif.
2. **Validasi Terpusat (Form Request):** Seluruh input formulir wajib divalidasi melalui kelas `FormRequest` terpisah (seperti `StoreVehicleRequest`, `StoreSuratSkptRequest`). Dilarang menggunakan validasi *inline* mentah `$request->all()`.
3. **Strict Type Declarations:** Semua method controller, service, dan repository wajib mendeklarasikan tipe parameter dan *return type* secara eksplisit.
4. **Bahasa Indonesia Baku (Dokumentasi & UI):** Seluruh blok PHPDoc, label antarmuka, notifikasi SweetAlert2, dan judul tabel ditulis dalam Bahasa Indonesia baku yang profesional.
5. **Idempoten & Non-Destructive Migrations:** Modifikasi skema tabel selalu menggunakan pengecekan keberadaan tabel/kolom (`Schema::hasTable`, `Schema::hasColumn`).

---

## 7. Frontend Rules & Design System

- **Palet Warna Formal Instansi:**
  - Warna Dominan: **Navy (`#1E40AF`)**, Putih Bersih, dan Slate Gray yang stabil dan berwibawa.
  - Tidak menggunakan warna neon atau skema warna yang merusak citra formal pemerintahan.
- **Sentuhan Mikro & Animasi Premium ([`_vanilla-touches.scss`](file:///home/arifhyde98/Projek/Aset_terpadu/resources/sass/components/_vanilla-touches.scss)):**
  1. Elevasi Kartu Halus (`.hover-elevate`): Kartu statistik terangkat `translateY(-5px)` dengan bayangan lembut saat disentuh kursor.
  2. Dropdown Liquid Smooth (`.dropdown-menu`): Animasi transisi meluncur lembut dari atas (`translateY(12px)`).
  3. Efek Sapuan Kilat (`.btn-premium-glow`): Sapuan kilatan cahaya metalik halus pada tombol aksi utama.
  4. Skeleton Shimmer (`.skeleton-shimmer`): Kerangka visual berkilau untuk feedback loading data AJAX.
  5. Bouncy Liquid Modal (`.modal`): Dialog modal mengembang elastis dengan kurva transisi mewah.
  6. Glassmorphism Navbar (`#navbar-main`): Panel kaca semi-transparan (`blur(12px)`) saat halaman digulir.
- **Format Tampilan Data:**
  - Mata Uang: Format rupiah akuntansi baku (`Rp 150.000.000`).
  - Identitas Aset: Plat nomor kendaraan, NIBAR, dan kode register menggunakan font monospace (`.plate-number`).
- **Ergonomi & Aksesibilitas:**
  - Kolom pertama tabel penting dikunci (`position: sticky`) pada perangkat seluler.
  - Header tabel laporan menggunakan latar solid (`--bs-tertiary-bg`) agar tidak tembus pandang saat digulir vertikal.
  - Konfirmasi hapus konsisten menggunakan modal SweetAlert2 tunggal (menghapus konfirmasi bawaan browser ganda).

---

## 8. Existing Features & Implementation Status

Seluruh fitur berikut telah selesai diimplementasikan (**DONE**) dan beroperasi penuh di lingkungan produksi:

### 8.1 Modul E-RANDIS (Kendaraan Dinas)
| Fitur | Status | Deskripsi & Implementasi |
|---|:---:|---|
| **Manajemen Kendaraan (CRUD)** | `DONE` | Pengelolaan data kendaraan dinas, filter status operasional & kondisi fisik, plat nomor unik, modal CRUD tersentralisasi, dan pembatasan isolasi `TenantScope` OPD. |
| **AI Smart Import Excel** | `DONE` | Impor data massal cerdas via `VehicleImport` dengan pencocokan kesamaan semantik header Excel, pratinjau 3 sampel baris, dan eksekusi aman berbasis `import_token`. |
| **Diagnosis & Resolusi Duplikasi** | `DONE` | Algoritma deteksi duplikasi komprehensif pada Data Real & e-BMD (suffix `(2)`, plat identik, nomor rangka identik, dan nomor mesin identik) dengan aksi konsolidasi/merge data aman. |
| **Sanitasi Identifier & Tukar Posisi** | `DONE` | Fitur 'Generate' pembersihan massal karakter khusus nomor rangka/mesin dan perbaikan posisi tertukar dengan isolasi target tabel (`real` dan `ebmd`). |
| **Rekonsiliasi BPKB Kendaraan** | `DONE` | Fitur verifikasi silang kepemilikan berkas fisik BPKB di eLABEL dengan data inventaris fisik kendaraan di E-RANDIS (`/vehicles/rekon-bpkb`). |
| **Modul Laporan Modular** | `DONE` | Format laporan fleksibel berbasis Strategy Pattern (Status, Distribusi OPD, STNK, Duplikasi), kolom jenis kendaraan terpadu, pratinjau AJAX, cetak browser, dan PDF mPDF ber-chunking. |

### 8.2 Modul SIPAT (Administrasi Pertanahan)
| Fitur | Status | Deskripsi & Implementasi |
|---|:---:|---|
| **Master Aset Tanah (CRUD)** | `DONE` | Pendataan lengkap tanah daerah KIB A, luas m², peruntukan, dasar & harga perolehan, koordinat GPS, serta batas poligon spasial. |
| **Tanah Belum / Tak Tercatat** | `DONE` | Pengelolaan tanah usulan non-KIB A, generate NIBAR sementara format `DRAFT-YYYYMMDD-XXXX`, dan promosi status ke NIBAR resmi oleh Superadmin/Admin. |
| **Target Pensertifikatan & GIS Map** | `DONE` | Penetapan KPI target sertifikasi tahunan, 5 kotak metrik ringkasan (Total Target, Belum Diproses, Sedang Proses, Realisasi, Capaian %), filter 3 arah, ekspor Excel & PDF resmi, dan visualisasi spasial Leaflet GIS. |
| **Rekonsiliasi Sertifikat SIPAT vs eLABEL** | `DONE` | Audit silang pencocokan NIBAR aset tanah bersertifikat di SIPAT dengan fisik berkas sertifikat yang tersimpan di eLABEL (`/sipat/rekonsiliasi`). |
| **Progres Pensertifikatan BPN** | `DONE` | Pencatatan rekam jejak tahapan pengurusan sertifikat BPN (Pengukuran, PBT, SK, hingga Terbit Sertifikat) dengan dokumen lampiran. |
| **Modul Surat Tanah (SKPT)** | `DONE` | Pembuatan Surat Keterangan Pendaftaran Tanah resmi dengan integrasi data pejabat pengesah (camat & kepala desa) serta ekspor mPDF, Word (.docx), dan cetak langsung. |
| **Peta Interaktif Spasial** | `DONE` | Visualisasi interaktif sebaran marker aset tanah dan rendering batas poligon bidang tanah di peta wilayah Kabupaten Donggala. |
| **Impor Aset Tanah & Status BPN** | `DONE` | Modal impor 2 tab: Unggah aset baru dan pembaruan massal tahapan status proses sertifikasi BPN via template Excel resmi. |
| **Widget Distribusi OPD (Breakdown)** | `DONE` | Widget dashboard Top 5 OPD dengan doughnut chart representasi 100%, multi-segment progress bar, breakdown status (Bersertifikat, Proses, Belum), dan link filter cepat. |
| **Tabel Sebaran Aset per OPD & Wilayah** | `DONE` | Rekapitulasi komprehensif seluruh 54 OPD dan 16 Kecamatan di Dashboard SIPAT, dilengkapi live search JavaScript, progres bar sertifikasi, dan footer total akumulasi. |
| **Pusat Laporan & Ekspor 11/12 Kolom** | `DONE` | Format cetak standar Pemkab Donggala 11 kolom dan 12 kolom dinamis (sub-kolom `No. Sertifikat` terhubung ke e-Label), antarmuka web modern 2-kolom (`.report-shell`) dengan kartu ringkasan dan kartu aksi ekspor, mesin judul dinamis 3 baris (mode auto/master/manual), ekspor Excel streaming, serta pengesahan penanda tangan ganda (*Dual Signatories*) berdampingan yang terintegrasi dengan Master KOP Surat Pemda (`/master-data/kop-surat`). |

### 8.3 Modul eLABEL (Pengarsipan Dokumen & Dynamic Archive)
| Fitur | Status | Deskripsi & Implementasi |
|---|:---:|---|
| **Katalog & Box BPKB** | `DONE` | Penyimpanan fisik berkas BPKB ke dalam box arsip, paginasi dinamis & optimasi query N+1 (eager loading `opdSipat`, kontrol `per_page`: 15, 50, 100, Semua, dan pagination bar mirip modul SIPAT), integrasi nopol kendaraan, penggabungan (*merge*) box, dan pencetakan stiker label barcode box. |
| **Katalog BPKB Keluar (*Soft Deleted*)** | `DONE` | Modul pencatatan arsip BPKB yang keluar/diserahkan dengan bukti tanda terima, ekspor data, dan fitur pemulihan (*restore*) kembali ke katalog aktif (`/elabel/bpkb-deleted`). |
| **Smart BPKB PDF Extractor & OCR** | `DONE` | Pemindaian otomatis dokumen PDF BPKB pada server/PC lokal dengan pencocokan nopol 100% presisi, proteksi berkas ganda, pratinjau PDF tab baru, dan verifikasi dry-run. |
| **Sertifikat Tanah Fisik & Box** | `DONE` | Penyimpanan fisik sertifikat tanah, sinkronisasi otomatis luas tanah dan kepemilikan instansi OPD dua arah dengan modul SIPAT, operasi split/merge box, impor Excel, penguncian OPD form edit sesuai KIB A, serta audit command `sipat:sync-opd-sertifikat`. |
| **Surat Penyerahan Dokumen & Box** | `DONE` | Administrasi berita acara penyerahan fisik berkas aset dan penataan box arsip terkait (`/elabel/surat-penyerahan`). |
| **Alur Peminjaman (Scan Request)** | `DONE` | Pengajuan peminjaman fisik atau request scan dokumen BPKB/Sertifikat oleh operator OPD dengan alur persetujuan admin BPKAD. |
| **Universal Dynamic Archive Engine** | `DONE` | Mesin e-Arsip dinamis multi-kategori dengan form builder kustom berbasis skema JSON, manajemen box universal barcode (`BOX-{KODE}-{NUM}`), viewer scan PDF, multi-lampiran, layanan peminjaman berkas, integrasi menu otomatis sidebar & mobile nav dengan caching terversi tanpa beban database, serta proteksi menu dan form builder terpusat khusus untuk role **Superadmin**. |

### 8.4 Portal Terpadu, Asisten AI & Fitur Lintas Modul
| Fitur | Status | Deskripsi & Implementasi |
|---|:---:|---|
| **Asisten Pintar AI (Google Gemini Cloud)** | `DONE` | Floating widget interaktif di seluruh halaman internal untuk Q&A seputar regulasi BMD dan pembuatan ringkasan data aset ditenagai oleh `GeminiAiService`. |
| **Unified Asset Portal & Search** | `DONE` | Portal pencarian publik landing page lintas 3 modul (Kendaraan, Tanah, Arsip) via `UnifiedAssetSearchService`, proteksi data privat, dan statistik live berbasis cache. |
| **Landing Sebaran OPD & Kecamatan** | `DONE` | Dua diagram Donut interaktif berdampingan di halaman muka dengan modal pop-up interaktif full-featured (tabel 54 OPD & 16 Kecamatan tanpa memenuhi halaman). |
| **OPD Mapping Hub** | `DONE` | Tabel pemetaan relasi ID instansi antara modul SIPAT/eLABEL dan E-RANDIS guna memastikan konsistensi kepemilikan aset. |
| **Audit Trail / Log Aktivitas Terpadu** | `DONE` | Konsolidasi rekaman aktivitas 3 modul ke `activities` dengan penyimpanan diff sebelum vs sesudah (`old_data` & `new_data`), sanitasi kredensial, dan modal diff dual-mode. |

### 8.5 Modul Administrasi Sistem & Keamanan
| Fitur | Status | Deskripsi & Implementasi |
|---|:---:|---|
| **Replikasi Staging Real-Time (SSE)** | `DONE` | Utilitas sinkronisasi basis data dari `db_sipat_terpadu` ke `db_sipat_staging` berbasis Server-Sent Events (SSE) streaming per-tabel yang bebas dari batas timeout HTTP. |
| **Manajemen Pengguna & Kredensial** | `DONE` | Pengelolaan akun pengguna, modal kredensial interaktif dengan tombol mata (`bi-eye`), penyimpanan terenkripsi dua arah (AES-256 via cast `encrypted`), 1-klik salin akun, dan reset password otomatis. |
| **Master KOP Surat & Pejabat Pemda** | `DONE` | Pengaturan terpusat KOP surat resmi instansi, spesimen nama/NIP dua pejabat penanda tangan, titimangsa, dan logo daerah di `/master-data/kop-surat`. |
| **Monitoring API & Health Check** | `DONE` | Endpoint pengecekan status server dan kesehatan koneksi basis data di `/api/health-check`. |

---

## 9. Deployment & Operations

1. **Simbolik Tautan Storage:**
   Jalankan perintah ini setelah instalasi pertama di server baru untuk membuka akses berkas publik:
   ```bash
   php artisan storage:link
   ```
2. **Izin Tulis Direktori (File Permissions):**
   Pastikan direktori unggahan berkas memiliki izin tulis (*writable*) oleh user web server (`www-data` / `nginx`):
   - `storage/app/public/`
   - `public/uploads/report/`
   - `public/uploads/settings/`
   - `public/uploads/elabel/`
3. **Kompilasi Aset Frontend (Vite):**
   Setiap kali melakukan pembaruan berkas SCSS di `resources/sass/` atau JS di `resources/js/`, kompilasi bundle produksi:
   ```bash
   npm run build
   ```
4. **Database Seeder Awal:**
   Pastikan seeder konfigurasi default dijalankan pada database yang baru disiapkan:
   ```bash
   php artisan db:seed --class=ReportSettingSeeder
   php artisan db:seed --class=DynamicArchiveSeeder
   ```
5. **Optimasi Aplikasi Produksi:**
   Jalankan komando optimasi cache Laravel untuk performa maksimal:
   ```bash
   php artisan optimize
   ```
