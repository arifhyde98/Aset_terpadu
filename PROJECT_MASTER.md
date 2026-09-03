# PROJECT MASTER DOCUMENTATION: SIPAT TERPADU (E-RANDIS, SIPAT, & eLABEL)

Dokumen ini adalah ringkasan master proyek untuk platform **SIPAT Terpadu**. Untuk rincian implementasi kode, skema database lengkap, dan daftar komponen UI, WAJIB merujuk ke file AI_HANDOVER.md.

---

## 1. Project Overview
- **Nama Project**: SIPAT Terpadu (Sistem Informasi Pertanahan Aset Tanah, Kendaraan Dinas, dan Labelisasi Dokumen).
- **Tujuan**: Mengintegrasikan pengelolaan aset tanah (SIPAT), kendaraan dinas (E-RANDIS), serta sistem labelisasi/pengarsipan fisik dokumen aset (eLABEL) dalam satu platform terpadu, real-time, dan akuntabel di lingkungan Pemerintah Provinsi/Daerah.
- **Modul Utama**:
  1. **E-RANDIS (Modul Kendaraan Dinas)**: Pendataan, pelacakan kondisi fisik, riwayat penggunaan, audit log, serta import/export data kendaraan dinas lintas OPD.
  2. **SIPAT (Modul Aset Tanah & Pertanahan)**: Pengelolaan sertifikasi aset tanah, pencatatan progres pensertifikatan, pengelolaan berkas legalitas seperti SKPT (Surat Keterangan Pendaftaran Tanah) dan Surat Pernyataan Batas, visualisasi peta sebaran aset tanah, serta rekapitulasi data pertanahan.
  3. **eLABEL (Modul Labelisasi & Pengarsipan)**: Digitalisasi dan pengarsipan fisik dokumen berharga (BPKB Kendaraan, Sertifikat Tanah, dan Surat Penyerahan) ke dalam box arsip khusus. Dilengkapi fitur cetak label barcode box, pemisahan/penggabungan isi box, dan alur permohonan peminjaman/scan dokumen (Scan Request) dengan persetujuan admin.
- **User Target**: 
  - Superadmin (Developer/Root/Administrator Global)
  - Admin BMD (Pengelola Aset BPKAD Global)
  - Admin OPD / Instansi (Pengelola Aset tingkat Dinas/OPD)
- **Status Project**: Production-Ready (Fase Optimasi & Integrasi Terpadu).

---

## 2. Tech Stack
- **Framework Core**: Laravel 12
- **PHP Version**: 8.2+
- **Database**: MySQL / MariaDB (Teroptimasi indeks B-Tree)
- **Asset Bundler**: Vite
- **UI Framework**: Bootstrap 5 (Customized via SCSS modular)
- **Package Penting**: 
  - `Maatwebsite/Excel` (Laravel Excel untuk Import/Export data massal)
  - `mPDF` (Render PDF formal server-side untuk dokumen resmi seperti SKPT dan laporan)
  - `Leaflet.js` & `turf.min.js` / `shp.js` (Rendering peta GIS interaktif, GeoJSON, dan Shapefile spasial)
  - `SweetAlert2` (Notifikasi interaktif dan konfirmasi CRUD)
  - `Bootstrap Icons` (Ikonografi antarmuka)
- **Deployment Target**: Server lokal / VPS Linux (Nginx + PHP-FPM 8.2+ & MySQL/MariaDB).

---

## 3. System Architecture
- **Arsitektur Umum**: Monolith (MVC Laravel) terintegrasi dengan pemisahan rute per modul di folder `routes/` (`erandis.php`, `sipat.php`, `elabel.php`).
- **Pola Desain & Integrasi Utama**:
  - **Service Layer**: Logika bisnis kompleks diletakkan di Service (contoh: `VehicleService`, `AsetTanahService`) untuk memisahkan database queries dari Controller.
  - **OPD Mapping (Inter-Module Bridge)**: Menjembatani perbedaan data instansi antara Modul Pertanahan (`opd` / `OpdSipat`) dan Modul Kendaraan Dinas (`opds` / `Opd`) melalui tabel perantara `opd_mappings` sehingga data aset tetap konsisten dan terisolasi dengan benar.
  - **Data Isolation (Tenant Isolation)**: Penerapan `TenantScope` (Global Scope) pada modul E-RANDIS dan pembatasan akses data berdasarkan unit kerja OPD pada SIPAT dan eLABEL guna mencegah kebocoran data antar-OPD.
  - **Observer Pattern**: Automasi sistem pencatatan riwayat (Audit Log) dikendalikan penuh oleh *Eloquent Observers* (`VehicleObserver`, `UserObserver`, dsb) yang mencatat aktivitas ke tabel `activities`.
- **Auth & Security Flow**:
  - Seluruh rute internal dilindungi otorisasi berbasis tipe *Enum* `UserRole`.
  - Keamanan akses controller pada seluruh modul diperkuat menggunakan interface `HasMiddleware` Laravel 12 dengan sintaks deklarasi eksplisit `new Middleware('auth')` untuk mencegah celah bypass otorisasi.

---

## 4. Folder Structure
Struktur direktori disesuaikan untuk menampung tiga modul yang saling terintegrasi:

```text
app/
 ├── Enums/        # Nilai statis sistem (UserRole, VehicleStatus, VehicleCondition).
 ├── Http/
 │    ├── Controllers/
 │    │    ├── Elabel/ # Controller khusus modul pengarsipan eLABEL.
 │    │    ├── Sipat/  # Controller khusus modul pertanahan SIPAT.
 │    │    └── ...     # Controller E-RANDIS & Administrasi Umum.
 │    └── Requests/    # FormRequests sentralisasi logika validasi input (StoreSuratSkptRequest, dll).
 ├── Models/       # Model Eloquent (AsetTanah, OpdSipat, OpdMapping, dsb).
 │    └── Elabel/  # Model khusus modul eLABEL (ElabelBpkb, ElabelBox, ElabelLoan, dll).
 ├── Observers/    # Trigger otomatis database untuk audit log & sinkronisasi data.
 └── Services/     # Logika bisnis inti dan Helper Cache.

resources/
 ├── css/          # Arsitektur Modular SCSS (7-1 Pattern):
 │    ├── components/# Tombol, Kartu, Tabel, Modal Bouncy, dll.
 │    ├── abstracts/ # Variabel & Mixins SCSS.
 │    └── app.scss   # Titik masuk utama kompilasi CSS.
 ├── js/           # app.js (Inisialisasi Vite & library frontend).
 └── views/
      ├── elabel/     # Template Blade Modul eLABEL.
      ├── sipat/      # Template Blade Modul SIPAT.
      └── ...
```

---

## 5. Database Architecture
- **Skema Relasional Modul Terpadu**:
  - `opd_mappings` menghubungkan tabel `opd` (modul SIPAT) dengan `opds` (modul E-RANDIS).
  - `aset_tanah` terhubung ke `opd` (SIPAT) via `opd_id` (foreign key) untuk isolasi data instansi pertanahan, serta dilengkapi kolom `geojson` untuk batas poligon peta GIS.
  - `sipat_target_sertifikat` mencatat penetapan kuota/target pensertifikatan tanah tahunan KIB A terelasi dengan `aset_tanah`.
  - `proses_aset` mencatat riwayat langkah pensertifikatan yang menunjuk ke `aset_tanah`.
  - `surat_skpt` mencatat surat keterangan pendaftaran tanah yang terelasi dengan data `aset_tanah`.
  - `elabel_bpkb` dan `elabel_sertifikat_tanah` menunjuk ke box arsipnya masing-masing (`elabel_boxes` / `elabel_sertifikat_boxes`) dan terelasi ke `opd` (`sipat_opd_id`) untuk isolasi kepemilikan dokumen.
  - `elabel_loans` mengelola peminjaman/request scan BPKB/Sertifikat oleh user dengan persetujuan admin.
  - `archive_types`, `archive_boxes`, `archive_items`, `archive_attachments`, `archive_loans` mengelola mesin arsip dinamis (*Universal Dynamic Archive Engine*) dengan form builder kustom JSON.
  - `users` menyimpan kredensial pengguna, dilengkapi kolom `plain_password` terenkripsi dua arah (AES-256 via cast `encrypted`) untuk pemulihan dan distribusi akun OPD oleh Superadmin.
- **Indexing Strategy**: B-Tree Index diterapkan pada kolom relasi penting seperti `opd_id`, `sipat_opd_id`, `box_id`, `archive_type_id`, serta kolom status operasional untuk menjamin kecepatan kueri jutaan baris data.

---

## 6. Coding Convention
- **Implicit Route Model Binding**: Seluruh controller di modul SIPAT (`AsetTanahController`, dsb) dan eLABEL menggunakan Route Model Binding (misal: `AsetTanah $aset`, `SuratSkpt $skpt`) agar kode controller lebih bersih.
- **Validasi Terpusat**: Seluruh form input wajib divalidasi melalui `FormRequest` khusus (seperti `StoreSuratSkptRequest`). Dilarang menggunakan validasi inline `$request->validate()`.
- **Return Type Hints**: Semua metode controller dan service wajib mendeklarasikan tipe data parameter dan nilai kembalian secara ketat.
- **Dokumentasi (PHPDoc)**: Seluruh blok PHPDoc ditulis menggunakan **Bahasa Indonesia** baku untuk menjaga keseragaman tim.

---

## 7. Frontend Rules
- **Design System & Animasi Mikro Premium**:
  - Warna utama Navy (`#1E40AF`), Putih, dan Gray stabil profesional.
  - Seluruh visual mewah/animasi mikro kustom diletakkan secara terisolasi di `_vanilla-touches.scss` (Elevasi kartu `.hover-elevate`, dropdown menu smooth, sapuan kilat tombol `.btn-premium-glow`, skeleton shimmer loading, dan modal elastis sekelas aplikasi premium).
- **Format Akuntansi**: Tampilan nilai uang wajib menggunakan format titik ribuan (`Rp 150.000.000`).
- **Nomor Dokumen & Plat**: Nomor polisi dan kode box menggunakan kelas monospace (`.plate-number` atau kode khusus) untuk kejelasan visual.
- **Responsivitas**: Kolom pertama tabel penting dikunci (`position: sticky`) di mode seluler agar tetap dapat dipindai dengan nyaman.

---

## 8. Existing Features
Berikut adalah status fitur yang telah diimplementasikan penuh pada platform SIPAT Terpadu:

| Modul | Fitur | Status | Keterangan |
|---|---|---|---|
| **E-RANDIS** | Manajemen Kendaraan (CRUD) | DONE | Mendukung modal CRUD tersentralisasi & plat nomor unik. |
| **E-RANDIS** | AI Smart Import Excel | DONE | Pemetaan dinamis header Excel berbasis kesamaan semantik. |
| **E-RANDIS** | Diagnosis & Resolusi Duplikasi | DONE | Merge plat/OPD identik lintas instansi secara atomik. |
| **E-RANDIS** | Modul Laporan Modular | DONE | Ekspor Excel streaming, cetak browser, dan PDF via mPDF. |
| **SIPAT** | Master Aset Tanah (CRUD) | DONE | Pengelolaan aset tanah, koordinat GPS, dan detil perolehan. |
| **SIPAT** | Tanah Belum/Tak Tercatat | DONE | Pengelolaan tanah usulan/baru, penomoran otomatis NIBAR draft, & update NIBAR resmi. |
| **SIPAT** | Target Pensertifikatan & GIS Map | DONE | Penetapan target pensertifikatan, modal edit, & peta GIS Leaflet. |
| **SIPAT** | Progres Pensertifikatan | DONE | Rekam langkah pensertifikatan tanah dari awal hingga terbit. |
| **SIPAT** | Modul Surat Tanah (SKPT) | DONE | Pembuatan SKPT, ekspor Word/PDF formal (mPDF), & cetak. |
| **SIPAT** | Peta Interaktif & Wilayah | DONE | Visualisasi sebaran koordinat aset tanah & master wilayah. |
| **SIPAT** | Import Aset Tanah & Status | DONE | Pengunggahan massal data sertifikat & status proses tanah. |
| **SIPAT** | Target Pensertifikatan | DONE | KPI target tahunan, filter multi-kriteria (Tahun/OPD/Status/Pencarian), & ekspor Excel/PDF. |
| **eLABEL** | Katalog BPKB (R4 / R2) | DONE | Penyimpanan BPKB, import template, & cetak status BPKB. |
| **eLABEL** | Manajemen Box Arsip BPKB | DONE | Penggabungan box BPKB dan pencetakan label barcode box. |
| **eLABEL** | Sertifikat & Box Sertifikat | DONE | Penyimpanan sertifikat tanah fisik, split/merge box sertifikat. |
| **eLABEL** | Surat Penyerahan & Box | DONE | Pencatatan dokumen penyerahan aset & manajemen box terkait. |
| **eLABEL** | Alur Peminjaman (Scan Request) | DONE | Pengajuan pinjam/scan BPKB/Sertifikat & approval admin. |
| **eLABEL** | Universal Dynamic Archive Engine | DONE | e-Arsip dinamis, visual form builder, custom schema, box barcode, PDF viewer, multi-attachment, & loan workflow. |
| **Terpadu** | Unified Asset Portal & Search | DONE | Mesin pencarian publik 3 modul (Kendaraan, Tanah, Arsip) + statistik live. |
| **Terpadu** | OPD Mapping (Hub) | DONE | Jembatan pemetaan instansi antara E-RANDIS dan SIPAT. |
| **Terpadu** | Audit Trail / Log Aktivitas | DONE | Log aktivitas terintegrasi E-RANDIS, SIPAT, dan eLABEL. |
| **System** | Sinkronisasi DB Staging | DONE | Utility sinkronisasi data dari db_sipat_terpadu ke db_sipat_staging. |
| **System** | Manajemen Pengguna & Kredensial | DONE | Detail akun, lihat password dengan Ikon Mata (AES-256 encrypted), copy password, dan auto reset. |

---

## 9. Deployment
- **Storage Link**: Jalankan `php artisan storage:link` untuk akses foto kendaraan dan pratinjau dokumen eLABEL.
- **Upload Directories**: Pastikan folder `public/uploads/report/`, `public/uploads/settings/`, dan path file eLABEL (`public/uploads/elabel/`) memiliki izin tulis (writable).
- **Vite Build**: Jalankan `npm run build` setelah memperbarui berkas SCSS/CSS agar perubahan visual terkompilasi bersih.
- **Seeder Awal**: Pastikan `ReportSettingSeeder` dan `DynamicArchiveSeeder` dijalankan agar layout default kop surat dan kategori awal e-Arsip terisi di database.
