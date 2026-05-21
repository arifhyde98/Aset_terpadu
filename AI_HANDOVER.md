# 🤖 AI Handover & Architecture Guide: E-RANDIS

Dokumen ini merupakan sumber kebenaran tunggal (*Single Source of Truth*) mengenai arsitektur, jejak rekam fitur, skema database, konvensi antarmuka (UI/UX), dan aturan backend untuk sistem **E-RANDIS** (Sistem Informasi Manajemen Kendaraan Dinas Pemerintah / Bapenda Sulteng).

**Setiap agen AI yang melanjutkan pengembangan proyek ini DIWAJIBKAN membaca dokumen ini terlebih dahulu untuk menjaga konsistensi standar kode dan kelangsungan arsitektur.**

**⚠️ PENTING: Untuk penambahan fitur baru, WAJIB membaca dan mengikuti `ATURAN_PENAMBAHAN_FITUR.md` terlebih dahulu.**

---

## 1. 🛠️ Environment & Technology Stack
- **Framework Core:** Laravel 12 / PHP 8.2+
- **Database:** MySQL / MariaDB (Teroptimasi dengan skema B-tree Indexing)
- **Frontend / Assets:** 
  - **Bundler:** Vite
  - **UI Framework:** Bootstrap 5 (Customized via SCSS tersentralisasi di `app.scss`)
  - **Iconography:** Bootstrap Icons (Local via NPM/Vite)
  - **Notifications:** SweetAlert2 (Local via NPM/Vite) untuk peringatan, validasi *real-time*, & konfirmasi aksi
  - **Typography:** Plus Jakarta Sans (Local via @fontsource)
- **Data Engine:** Laravel Excel (Maatwebsite/Excel) sebagai mesin utama pengolahan Impor & Ekspor data massal, serta mPDF sebagai mesin render PDF formal server-side pada Modul Laporan.
- **Infrastruktur / Deployment:** Mendukung eksekusi lokal berbasis **Laragon** serta telah disiapkan konfigurasi **Docker** (`Dockerfile` & `compose.yaml`) untuk kemudahan kontainerisasi.

---

## 2. Arsitektur & Keamanan (Multi-Role & Multi-Tenancy)
*   **Role System**: Menggunakan Enum `App\Enums\UserRole` (SUPERADMIN, ADMIN, OPD).
*   **Data Isolation (Fail-Safe)**: Implementasi `App\Models\Scopes\TenantScope` pada model `Vehicle`. Admin OPD secara otomatis dibatasi aksesnya hanya pada `opd_id` miliknya. Jika `opd_id` hilang/null, sistem tetap mengunci akses (bukan membuka akses global) untuk keamanan maksimal.
*   **Otomasi Akun (Observer Level)**: Logika pembuatan akun admin OPD dijalankan melalui `OpdObserver::created()`. Hal ini menjamin setiap OPD baru (lewat Form atau Import Excel) selalu memiliki akun admin secara otomatis.
*   **Sistem Log Aktivitas (Audit Trail)**: Menggunakan tabel `activities` dan model `Activity`. Log dicatat secara otomatis melalui **Eloquent Observers** (`created`, `deleted`) pada model `Vehicle`, `Opd`, dan `User`.
*   **Mekanisme Caching**: Statistik dashboard menggunakan *cache key* dinamis: `dashboard.stats.[role].[opd_id]`, sedangkan ringkasan Modul Laporan menggunakan `reports.summary.{role}.{scope}`. Seluruh aksi CRUD pada kendaraan dan OPD kini menggunakan helper terpusat `invalidateDashboardStats()` di `VehicleService` untuk melakukan *targeted invalidation* (bukan `Cache::flush()` global), sekaligus menyelaraskan pembersihan cache summary laporan agar cache pengaturan sistem (`setting.{key}`) tetap terjaga dan performa lebih optimal.
*   **Integritas Data (Hardened)**: 
    *   Database: `onDelete('cascade')` pada relasi `opd_id` di tabel `users` (telah disinkronkan ke mesin database MariaDB/MySQL).
    *   Audit: `onDelete('set null')` pada `user_id` di tabel `activities` untuk menjaga riwayat log tetap utuh meski akun dihapus.
    *   Storage: Eloquent Observer pada model `User` (Event `deleting`) otomatis menghapus file fisik `avatar` saat akun dihapus.

---

## 3. Skema Database Utama
*   **users**: Penambahan kolom `role` (string), `opd_id` (foreignId - Cascade), dan `avatar` (string - nullable).
*   **vehicles**: Penambahan kolom `opd_id` (foreignId) dan integrasi Global Scope.
*   **opds**: Master data instansi yang terhubung 1-to-1 dengan user admin OPD.
*   **activities**: Tabel log audit dengan relasi `user_id` (Set Null), menyimpan `description` dan `type` (untuk UI badging).

### Tabel `vehicles`
Menyimpan entitas aset utama dengan arsitektur kolom ternormalisasi:
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

### Tabel `vehicle_types`
Master data klasifikasi jenis kendaraan:
- `id` (PK, BigInt)
- `name` (String, Unique) — Nama tipe/kategori kendaraan.
- `description` (Text, Nullable) — Deskripsi opsional.

### Tabel `opds`
Master data Organisasi Perangkat Daerah (instansi pemerintah):
- `id` (PK, BigInt)
- `nama` (String, Unique) — Nama lengkap instansi.
- `singkatan` (String, Nullable) — Singkatan resmi instansi.
- `alamat` (Text, Nullable) — Alamat kantor.

### Tabel `settings`
Konfigurasi CMS yang dapat diubah melalui antarmuka admin:
- `id` (PK, BigInt)
- `key` (String, Unique) — Kunci pengaturan (misal: `app_name`, `logo`).
- `value` (Text, Nullable) — Nilai pengaturan.
- `type` (String, Default: 'text') — Tipe input: `text`, `image`, `textarea`.
- `group` (String, Default: 'general') — Grup pengelompokan: `general`, `landing`, `login`.

### Tabel `report_letterheads`
Konfigurasi kop surat untuk dokumen resmi Modul Laporan:
- `id` (PK, BigInt)
- `nama_pemerintah`, `nama_instansi` (String) - Identitas utama kop surat.
- `nama_unit`, `alamat`, `telepon`, `email`, `website` - Detail kontak dan unit kerja.
- `logo_path` (String, Nullable) - Path logo publik, default fallback ke `images/logo-sulteng.png`.
- `is_active`, `is_default` (Boolean) - Penanda konfigurasi aktif/default.

### Tabel `report_signatories`
Konfigurasi pejabat penanda tangan laporan:
- `id` (PK, BigInt)
- `nama`, `jabatan`, `kota_ttd` (String) - Identitas wajib pejabat dan kota tanda tangan.
- `nip`, `pangkat_golongan` (String, Nullable) - Detail kepegawaian.
- `signature_image_path` (String, Nullable) - Path gambar tanda tangan publik.
- `is_active`, `is_default` (Boolean) - Penanda konfigurasi aktif/default.

### Tabel `report_export_settings`
Aturan ekspor per tipe laporan:
- `id` (PK, BigInt)
- `report_type` (String, Unique) - Kode tipe laporan dari `ReportRegistry`.
- `letterhead_id` (FK Nullable) - Relasi ke `report_letterheads`, `nullOnDelete()`.
- `signatory_id` (FK Nullable) - Relasi ke `report_signatories`, `nullOnDelete()`.
- `paper_size` (String) - `A4`, `F4`, `Letter`, atau `Legal`.
- `orientation` (String) - `L` untuk landscape, `P` untuk portrait.
- `show_summary`, `show_signature` (Boolean) - Kontrol tampilan ringkasan dan blok tanda tangan PDF.

### Strategi Indeksasi Database (*Query Optimization*)
Telah diterapkan indeks lapis ganda melalui *migration* `2026_05_14_151900` untuk mencegah *Full Table Scan*:
- **B-tree Index:** Pada kolom `status`, `opd_id`, dan `vehicle_type_id`.
- **Composite Index:** Pada kombinasi `['no_polisi', 'status']` untuk kueri pencarian yang difilter.

---

## 4. ⚙️ Backend Architecture & Aturan Validasi

### Lapisan Layanan (*Service Layer*)
Logika bisnis dan kalkulasi diletakkan di dalam kelas *Service*:
- `VehicleService`: statistik dashboard, helper cache kendaraan, pencarian, dan utilitas bisnis kendaraan.
- `ReportService`: ringkasan laporan, orkestrasi preview terpaginasi, dan integrasi strategi laporan modular.

### Arsitektur Modul Laporan (*Reporting Architecture*)
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
- `ReportLetterhead`, `ReportSignatory`, dan `ReportExportSetting`: model konfigurasi dokumen resmi laporan. Ketiganya dipakai oleh PDF mPDF dan halaman `/reports/settings`.

### Validasi Kelas Permintaan (*Form Request Validation*)
Penyimpanan dan pembaruan data wajib menggunakan kelas validasi terpisah demi menjaga keamanan dan kebersihan pengontrol:
- `StoreVehicleRequest`: Menjamin `no_polisi` unik, atribut wajib terisi, serta mengunci `opd_id` dan teks `opd` ke instansi milik user jika pembuatnya ber-role `OPD`.
- `UpdateVehicleRequest`: Memvalidasi keunikan `no_polisi`, mengecualikan ID kendaraan yang sedang diperbarui, serta mencegah user OPD memindahkan kendaraan ke instansi lain melalui penguncian `opd_id` dan teks `opd`.
- `StoreOpdRequest` / `UpdateOpdRequest`: Mengelola validasi master data OPD, termasuk keunikan nama instansi.
- `StoreVehicleTypeRequest` / `UpdateVehicleTypeRequest`: Mengelola validasi master data jenis kendaraan.
- `StoreUserRequest` / `UpdateUserRequest`: Mengelola validasi manajemen pengguna, termasuk validasi enum `UserRole` dan relasi `opd_id`.
- `UpdateProfileRequest`: Mengelola validasi pembaruan profil pengguna, email unik, kata sandi terkonfirmasi, dan avatar.
- `UpdateSettingRequest`: Mengelola validasi pembaruan pengaturan CMS secara dinamis berdasarkan tipe setting (`text`, `textarea`, `image`).
- `ImportVehicleRequest`: Memvalidasi unggahan berkas Excel untuk pratinjau dan jalur impor legacy.
- `ExecuteSmartImportRequest`: Memvalidasi eksekusi AI Smart Import, termasuk token sesi impor, struktur mapping, larangan mapping ganda, dan kewajiban kolom identitas minimum.
- `ResolveDuplicateVehicleRequest`: Memvalidasi resolusi kendaraan ganda dan memastikan pasangan `original_id` / `duplicate_id` benar-benar pasangan duplikasi sah menurut hasil diagnosis sistem.
- `ResolveDuplicateOpdRequest`: Memvalidasi penggabungan OPD ganda serta memastikan pasangan target/sumber memang pasangan OPD terindikasi ganda yang sah.
- `ReportFilterRequest`: Memvalidasi filter laporan dan memaksa `opd_id` user OPD kembali ke instansinya sendiri agar parameter URL tidak dapat dipakai untuk mengintip data tenant lain.

### Konvensi Validasi & FormRequest
- **Wajib FormRequest**: Dilarang menggunakan validasi inline `$request->validate()` di dalam Controller.
- **Cakupan Luas**: Standar ini berlaku untuk operasi CRUD dasar hingga operasi data massal seperti import Excel (contoh: `ImportVehicleRequest`).
- **Status Implementasi**: Controller `OpdController`, `VehicleTypeController`, `UserController`, `ProfileController`, dan `SettingController` telah sepenuhnya menggunakan pola ini.
- **Penanganan Enum**: Validasi status dan kondisi wajib menggunakan `Rule::enum()` untuk menjamin sinkronisasi dengan Domain Model.

### Konvensi Middleware & Akses Rute
- Semua *Controller* wajib mengimplementasikan antarmuka `HasMiddleware` standar Laravel 12 dengan metode statis `middleware()`.
- **WAJIB menggunakan `new Middleware()` syntax** sesuai Laravel 12 best practice. Dilarang menggunakan string middleware langsung:
```php
// ✅ BENAR (Laravel 12 Best Practice)
public static function middleware(): array
{
    return [
        new Middleware('auth'),
        new Middleware('role:superadmin,admin'),
        new Middleware('role:superadmin', only: ['truncate']),
    ];
}

// ❌ SALAH (Cara Lama - Deprecated)
public static function middleware(): array
{
    return [
        'auth',
        'role:superadmin,admin',
    ];
}
```
- Seluruh aturan otorisasi (`auth`, `role`, `only`, `except`) menjadi **sumber kebenaran di level Controller** untuk memudahkan audit akses per modul.
- Berkas `routes/web.php` difokuskan untuk deklarasi URI, nama rute, dan pemetaan Controller, tanpa duplikasi grup middleware besar yang berlapis.
- Karena operasi menggunakan antarmuka *Modal*, rute halaman formulir tradisional wajib dibatasi:
```php
Route::resource('vehicles', VehicleController::class)->except(['create', 'edit', 'show']);
```

### Standar Kode & Enum
Aplikasi menggunakan Enum (PHP 8.1+) untuk menjaga integritas data:
- `VehicleStatus`: Mengelola status operasional (Tersedia, Dipinjam, Nonaktif).
- `VehicleCondition`: Mengelola kondisi fisik, dilengkapi method `fromImport()` untuk normalisasi singkatan Excel (RB, RR, B, dll).

### Logika Smart Import (Normalisasi)
Sistem melakukan pembersihan data otomatis saat import Excel:
1. **Penerjemahan Singkatan**: Mengonversi variasi teks mentah (misal: "RB", "RR", "B") menjadi standar "Rusak Berat", "Rusak Ringan", dsb.
2. **Penentuan Status Otomatis**: Jika kondisi fisik adalah 'Rusak Berat' atau 'Hilang', sistem otomatis mengatur status operasional ke 'Nonaktif'.
3. **Strategi Import Aman & Cepat (Optimasi Fase 3)**:
    - **Batching & Memory Caching**: Menggunakan `WithBatchInserts` (100 baris) dan *in-memory caching* untuk master data (OPD/Jenis) guna memangkas kueri database hingga 90%.
    - **Global Duplicate Detection**: Menggunakan `withoutGlobalScopes()` pada pengecekan plat nomor agar deteksi duplikat bersifat global lintas instansi (menghindari error SQL Unique Constraint).
    - **Data Ownership**: Memastikan field `user_id` selalu terisi otomatis dengan ID pengunggah agar data "diakui" oleh sistem statistik.
    - **Null-Safety**: Menggunakan akses *null-safe* pada relasi user-OPD untuk mencegah kegagalan sistem saat mengolah data yang tidak lengkap.
4. **Keamanan Alur AI Smart Import**:
    - **Preview Multi-Sheet Dinamis**: `importPreview()` menelusuri seluruh sheet dan memilih sheet pertama yang valid memiliki header terdeteksi, lalu mengembalikan `active_sheet_name` ke frontend.
    - **Token Sesi Impor**: Setelah preview berhasil, sistem membuat `import_token` acak yang disimpan di cache server selama 30 menit; frontend tidak pernah menerima path berkas fisik secara mentah.
    - **Validasi Kepemilikan Token**: Eksekusi impor hanya diterima jika token masih aktif, dimiliki oleh user yang sama, dan berkas sementara masih tersedia.
    - **Pembersihan Otomatis**: Setelah impor berhasil atau token kedaluwarsa, berkas sementara dan cache token dibersihkan otomatis.
    - **Legacy Fallback Terpisah**: Jalur template tradisional menggunakan endpoint khusus `/vehicles/import-legacy` dengan mapping template standar otomatis, terpisah dari jalur AI Smart Import.
    - **Normalisasi Dokumen**: Nilai STNK/BPKB dari Excel dinormalisasi ke format baku `Ada` / `Tidak`.

### Logika Diagnosis & Resolusi Duplikasi
Sistem memiliki modul diagnosis duplikasi data untuk membantu membersihkan inkonsistensi hasil impor tanpa membuka akses global kepada user OPD:
1. **Akses Terbatas**: Endpoint diagnosis dan resolusi duplikasi hanya dapat diakses role `SUPERADMIN` dan `ADMIN`.
2. **Deteksi Kendaraan Ganda**:
   - Mendeteksi suffix plat hasil impor seperti `DN 2806 B (2)` terhadap plat induk exact match `DN 2806 B`.
   - Mendeteksi kendaraan dengan `no_mesin` identik secara global lintas tenant.
3. **Validasi Pasangan Aman**:
   - Setiap aksi merge/delete kendaraan wajib melalui `ResolveDuplicateVehicleRequest`.
   - Request hanya diterima bila pasangan `original_id` dan `duplicate_id` cocok dengan pasangan duplikasi sah hasil diagnosis service.
4. **Resolusi Atomik**:
   - `mergeVehicles()` dan `mergeOpds()` dibungkus transaksi database agar tidak meninggalkan perubahan parsial jika terjadi kegagalan.
   - `nomor_register` tidak digunakan sebagai kriteria deteksi duplikasi dan tidak ikut auto-merge kendaraan karena merupakan identitas unik aset. Perubahan nomor register harus dilakukan manual melalui form edit.
   - Saat merge OPD, sistem menyinkronkan **dua kolom sekaligus** pada kendaraan terdampak: `opd_id` dan teks historis `opd`.
5. **Catatan Kualitas Data**:
   - Untuk duplikasi berbasis `no_mesin`, pemilihan record induk saat ini masih mengikuti record pertama hasil kueri. Ini aman secara teknis, tetapi kebijakan bisnis pemilihan induk terbaik masih menjadi area penyempurnaan lanjutan.

### Strategi Caching
- **Statistik Dashboard**: Menggunakan cache key dinamis berbasis role dan instansi: `dashboard.stats.{role}.{opd_id}` dengan TTL 5 menit.
- **Ringkasan Modul Laporan**: Menggunakan cache key `reports.summary.{role}.{scope}` dengan TTL 5 menit dan key berbeda untuk `superadmin`, `admin`, `guest`, user OPD valid, serta user OPD dengan `opd_id = null`.
- **Cache Invalidation**: Seluruh mutasi data kendaraan dan OPD (**Store, Update, Destroy, Import, Truncate**) wajib menggunakan helper terpusat `VehicleService::invalidateDashboardStats()` untuk *targeted invalidation* (global + OPD terdampak), bukan `Cache::flush()` global. Helper ini juga menyelaraskan invalidasi cache summary laporan secara otomatis.
- **Pengaturan Global**: Di-cache via `cache()->remember('setting.{key}', 3600)` (1 jam) dengan penghapusan otomatis (`cache()->forget`) saat data diperbarui.

---

## 5. 🎨 Design System, Estetika & Standar UI
Aplikasi **menggunakan sentuhan visual premium & animasi mikro kustom** secara bijak (tanpa merusak nuansa formal pemerintahan) demi mengutamakan kenyamanan interaksi, kejelasan data, dan identitas formal instansi pemerintah.

### Palet & Gaya Visual
- **Skema Warna Formal:** Memprioritaskan **Navy, Putih, dan Abu-abu (Gray)** yang stabil dan profesional.
- **Sentuhan Vanilla CSS & Animasi Mikro Premium:** Seluruh peningkatan visual kustom wajib diletakkan di dalam modul terisolasi [_vanilla-touches.scss](file:///d:/arif/laragon/www/E-RANDIS_PHP/resources/css/components/_vanilla-touches.scss) dan tidak boleh mengotori berkas SCSS bawaan lainnya. Peningkatan ini meliputi:
  1. **Elevasi Kartu (`.hover-elevate`):** Kartu statistik dashboard (`stat-card.blade.php`), monitor cepat, dan aktivitas terbaru terangkat secara organik (`translateY(-5px)`) dibarengi bayangan halus yang melebar saat didekati kursor, dengan transisi kurva *bezier* yang meluncur halus.
  2. **Dropdown Liquid Smooth (`.dropdown-menu`):** Dropdown Bootstrap bertransisi anggun meluncur dari atas (`translateY(12px)`) sembari memudar transparan saat dibuka.
  3. **Efek Sapuan Kilat (`.btn-premium-glow`):** Tombol Call-To-Action utama (Unduh Laporan, tombol Cari nopol, dsb) memancarkan efek sapuan kilatan cahaya metalik dari kiri ke kanan saat disorot kursor.
  4. **Skeleton Shimmer (`.skeleton-shimmer`):** Kerangka bayangan visual berkilau (efek loading shimmer) menggantikan pemutar spinner kaku saat proses pemuatan AJAX/pencarian data di halaman depan berlangsung.
  5. **Bouncy Liquid Modal (`.modal`):** Seluruh modal dialog aplikasi muncul dengan efek mengembang elastis yang mewah (bouncing transition dari `scale(0.96)` ke `scale(1)` dengan kurva `cubic-bezier(0.34, 1.56, 0.64, 1)`).
  6. **Glassmorphism Navbar (`#navbar-main`):** Navbar landing page bertransisi mulus menjadi kaca semi-transparan (`backdrop-filter: blur(12px)`) saat halaman digulir ke bawah (`.scrolled`).
- **Batas Tabel Tajam:** Menggunakan pembatas (*border*) tabel yang tegas guna mempermudah pemindaian ribuan baris data.
- **Plat Nomor Identik:** Nomor Polisi wajib dibungkus dengan kelas `.plate-number` (font **Monospace** tebal) agar konsisten secara visual.
- **Format Akuntansi**: Seluruh tampilan mata uang (seperti `nilai_perolehan`) wajib menggunakan format titik ribuan (Contoh: Rp 150.000.000). 
  - Di Blade: `number_format($val, 0, ',', '.')`.
  - Di JavaScript (Modal): `.toLocaleString('id-ID')`.
- **Bahasa Antarmuka**: Seluruh notifikasi, pesan kesalahan (validation errors), peringatan (warnings), dan label UI **WAJIB** menggunakan Bahasa Indonesia yang profesional dan mudah dimengerti.
- **Arsitektur SCSS Modular (7-1 Pattern)**: 
  - Gaya kustom diatur secara terorganisir dalam folder `resources/css/` (abstracts, base, components, layout, pages, themes).
  - **DILARANG** menulis gaya langsung di `app.scss`. File tersebut hanya sebagai pusat impor modul.
  - Setiap penambahan gaya baru wajib diletakkan pada modul yang sesuai agar tidak menumpuk.

### Standar Responsivitas Tabel Seluler (*Mobile-First UX*)
- **Sticky First Column:** Kolom pertama tabel dikunci menggunakan CSS `position: sticky` agar tidak hilang saat digeser horizontal di layar HP.
- **Responsive Column Hiding:** Kolom pelengkap disembunyikan di layar kecil melalui utilitas `d-none d-md-table-cell`.
- **Visual Swipe Hint:** Indikator *swipe* di bagian bawah tabel pada mode *mobile* sebagai panduan UX.

### Standar Tampilan Tabel & Komponen Reusable
- **Paginasi Global**: Menggunakan `Paginator::useBootstrapFive()` di `AppServiceProvider` untuk memastikan template navigasi yang bersih dan konsisten.
- **Penomoran Tabel**: Menggunakan `$loop->iteration` yang dikombinasikan dengan metadata paginasi: `($collection->currentPage() - 1) * $collection->perPage() + $loop->iteration`.
- **Komponen Lainnya**: Menggunakan `x-modal` sebagai shell modal, `x-stat-card` untuk kartu statistik, dan `x-condition-badge` untuk label kondisi.
- **Kepatuhan Tema**: Komponen baru, termasuk halaman laporan dan `.plate-number`, wajib memakai token tema (`var(--card-bg)`, `var(--text-color)`, `var(--card-border)`) agar konsisten pada mode terang dan gelap.
- **Field OPD Admin OPD**: Pada antarmuka kendaraan, user OPD hanya melihat OPD miliknya dalam bentuk read-only; dropdown OPD hanya tersedia untuk Admin/Superadmin.

### Standar Komponen Blade (Wajib Dipakai)
Dilarang keras menulis elemen mentah berulang. Gunakan komponen Blade berikut:

#### `<x-table-card>`
Pembungkus tabel utama yang otomatis menyediakan slot pencarian, *empty state*, dan pembungkus *scroll* responsif.
- Wajib mengirimkan `:collection="$data"` agar informasi *"Menampilkan X sampai Y dari Z data"* muncul secara otomatis.
- Slot `:pagination` digunakan untuk merender tombol navigasi `{{ $data->links() }}`.
```html
<x-table-card title="Daftar Kendaraan" :search="true" placeholder="Cari aset...">
    <x-slot name="actions">
        <!-- Tombol Aksi -->
    </x-slot>
    <!-- Struktur tabel standar -->
</x-table-card>
```

#### `<x-modal>`
Komponen modal untuk CRUD *Single Page Interaction*, mendukung perilaku *mobile-first full-screen*, serta *header/footer* yang tetap (*sticky*).

### Penanganan Foto Kendaraan
- Maksimal 4 foto per kendaraan disimpam dalam kolom JSON `foto_kendaraan`.
- Logika Edit menggunakan **Replace All**: Mengunggah foto baru akan menghapus seluruh foto lama.
- Foto disimpan di disk `public/vehicles`. Wajib menjalankan `php artisan storage:link`.

---

## 6. 📦 Peta Fitur Penuh (*Full Feature Stack*)

### Manajemen Aset Kendaraan (`VehicleController`)
- **Pencarian Publik Landing Page:** Antarmuka pencarian bagi masyarakat di rute `/` dan `/vehicle-search`. Input otomatis dibersihkan oleh `VehicleService::formatPlateNumber()` (kapitalisasi, penghapusan spasi ganda, dan filter karakter alfanumerik).
- **Impor Excel Massal AI (`/vehicles/import`):** Menggunakan kelas `VehicleImport` yang mendukung **AI Smart Import** (pemetaan kolom dinamis). Sistem menganalisis header Excel secara otomatis, mencocokkannya menggunakan algoritma kemiripan teks semantik, memilih sheet valid pertama dari berkas multi-sheet, menampilkan visualisasi pratinjau data (3 baris sampel), lalu mengeksekusi impor menggunakan `import_token` aman berbasis cache server.
- **Impor Excel Tradisional (`/vehicles/import-legacy`):** Menangani unggahan template standar lama secara terpisah menggunakan mapping default template E-RANDIS agar alur non-AI tetap stabil dan tidak bercampur dengan eksekusi Smart Import.

- **Cek & Resolusi Duplikasi Data (Fase 3 - Lanjutan):** Sistem dilengkapi dengan modul pendeteksi dan penyelesai duplikasi data kendaraan serta instansi OPD secara cerdas:
  - **Deteksi Duplikasi Kendaraan (`checkDuplicates`):** Menganalisis database secara global lintas instansi (`withoutGlobalScopes()`) untuk menemukan kendaraan dengan akhiran plat ganda hasil impor (misal: `DN 2806 B` vs `DN 2806 B (2)`) atau Nomor Mesin identik ganda. Menampilkan tabel perbandingan dinamis di frontend yang menyorot perbedaan atribut secara visual.
  - **Resolusi Gabung Kendaraan (`mergeVehicles`):** Menggabungkan data kendaraan ganda ke data asli secara atomik (mengisi otomatis kolom kosong pada data asli dengan nilai dari data ganda) lalu menghapus data ganda secara aman.
  - **Resolusi Hapus Kendaraan (`resolveDuplicateVehicle`):** Menghapus salah satu data ganda hanya jika pasangan duplikatnya tervalidasi sah oleh sistem.
  - **Deteksi & Gabung OPD Ganda (`mergeOpds`):** Mendeteksi instansi OPD dengan nama yang mirip/sama persis akibat kesalahan ketik saat impor. Menggabungkan instansi tersebut secara atomik, memindahkan seluruh kendaraan dari OPD duplikat ke OPD utama, menyinkronkan `opd_id` dan teks `opd`, lalu menghapus OPD duplikat secara aman.
  - **Robust OPD Relation & Fallback Mapping:** Mendukung pembacaan nama OPD yang tangguh menggunakan kombinasi relasi Eloquent `opdRelation` dengan fallback kolom string `opd` (`$original->opdRelation?->nama ?? $original->opd ?? 'BELUM DIKETAHUI'`), menjamin data OPD tidak pernah hilang atau tertulis kosong/belum diketahui.

### Modul Laporan Modular (`ReportController`)
- Menyediakan empat jenis laporan ter-optimasi:
  - **Status dan Kondisi Fisik Kendaraan**
  - **Distribusi Aset per OPD**
  - **Masa Berlaku Dokumen/STNK**
  - **Identifikasi Data Kendaraan Ganda/Identik** (Laporan khusus Admin/Superadmin dengan analisis visual in-memory bebas kueri per baris / anti-N+1 menggunakan dataset referensi eksplisit secara global lintas OPD bahkan ketika laporan difilter berdasarkan OPD tertentu, untuk menjamin akurasi tinggi pada seluruh jalur preview, export, dan print).
- **Otorisasi Ketat Laporan Duplikasi**: Laporan tipe `duplicate` dilindungi secara berlapis. Di `ReportRegistry`, tipe laporan ini otomatis disembunyikan dari user OPD. Di `ReportFilterRequest`, akses ditolak secara keras dengan melempar HTTP 403 Forbidden bagi OPD (aman untuk preview, export, dan print).
- **Pembersihan Kebocoran Tenant**: Menghapus seluruh accessor duplikasi dari model `Vehicle.php`. Logika analisis duplikasi dipindahkan seutuhnya ke method `postProcess()` di dalam strategy `DuplicateVehicleReport.php` sehingga data global tidak pernah bocor ke konteks tenant OPD biasa.
- Mendukung ringkasan cepat berbasis cache, pratinjau HTML parsial via AJAX, ekspor Excel modular, cetak browser ramah printer, dan unduhan PDF formal server-side via mPDF.
- Seluruh query strategy dibangun dari `Vehicle::query()` agar otomatis tunduk pada `TenantScope` (kecuali strategy duplikasi khusus admin global).
- Pengguna OPD tidak melihat filter OPD lain, dan `ReportFilterRequest` tetap mengunci `opd_id` di backend sebagai pertahanan berlapis.
- Pengujian keamanan laporan mencakup isolasi tenant, perlindungan cache lintas-role, invalidasi cache pasca CRUD, otorisasi ketat laporan duplikasi (403 untuk OPD), serta pencegahan pemindahan kendaraan lintas OPD saat update.
- **PDF mPDF & Data Guard**: Endpoint `/reports/pdf` menghasilkan berkas PDF fisik. Untuk menjaga RAM server, sistem menolak ekspor PDF saat hasil laporan melebihi 1.000 baris dan mengarahkan pengguna memakai Excel.
- **Template PDF**: `resources/views/reports/pdf.blade.php` memakai konfigurasi dari `ReportDocumentSettingService`: kop surat, logo, ukuran kertas, orientasi, ringkasan, dan blok tanda tangan.
- **Pengaturan Dokumen Laporan**: Halaman `/reports/settings` hanya untuk `superadmin`. Berkas logo disimpan ke `public/uploads/report/logo`, sedangkan gambar tanda tangan ke `public/uploads/report/signature`. Saat unggahan baru masuk, berkas lama dihapus agar storage tidak menumpuk.
- **Seeder Wajib**: `ReportSettingSeeder` mengisi konfigurasi awal. Pastikan dipanggil dari `DatabaseSeeder` setelah migration `2026_05_19_100810_create_report_settings_tables.php`.

### Pengaturan Dokumen Laporan (`ReportSettingController`)
- **Akses**: `auth` + `role:superadmin`.
- **Kop Surat**: Mengelola nama pemerintah, nama instansi, unit, alamat, telepon, email, website, dan logo.
- **Pejabat Penanda Tangan**: Mengelola nama, jabatan, NIP, pangkat/golongan, kota tanda tangan, serta gambar tanda tangan.
- **Aturan Ekspor per Tipe Laporan**: Mengelola `paper_size`, `orientation`, `show_summary`, dan `show_signature` berdasarkan tipe yang tersedia di `ReportRegistry`.
- **Fallback Kritis**: Jangan hapus fallback pada `ReportDocumentSettingService`; fallback tersebut sengaja dibuat agar `/reports/pdf` tetap hidup saat konfigurasi database belum tersedia.

### Manajemen Master Data (Hub)
- Rute terpusat (`/master-data`) untuk mengelola **Jenis Kendaraan** (`VehicleTypeController`) dan **OPD / Dinas** (`OpdController`).
- Dilengkapi fitur pembersihan otomatis (*atomic cleanup*) untuk menghapus entitas jenis kendaraan yang tidak lagi memiliki relasi aset aktif:
  ```php
  VehicleType::whereDoesntHave('vehicles')->delete();
  ```

### CMS Pengaturan Web (`SettingController`)
- Antarmuka manajemen konfigurasi situs (`/settings`) untuk memodifikasi identitas web (logo, judul aplikasi, teks *footer*).
- Berkas gambar/logo diunggah secara aman ke direktori `public/uploads/settings` dengan penamaan UUID untuk mencegah bentrokan nama berkas.

---

## 7. 🗺️ Peta Rute Aplikasi (*Route Map*)

| Metode | URI | Controller@Method | Akses | Keterangan |
|--------|-----|-------------------|-------|------------|
| GET | `/` | `VehicleController@search` | Publik | Landing page + pencarian |
| GET | `/vehicle-search` | `VehicleController@searchLandingVehicle` | Publik | API AJAX pencarian kendaraan |
| GET | `/home` | `HomeController@index` | Auth | Dashboard admin |
| GET | `/vehicles` | `VehicleController@index` | Auth | Daftar kendaraan + filter |
| POST | `/vehicles` | `VehicleController@store` | Auth | Simpan kendaraan baru |
| PUT | `/vehicles/{id}` | `VehicleController@update` | Auth | Perbarui data kendaraan |
| DELETE | `/vehicles/{id}` | `VehicleController@destroy` | Auth | Hapus kendaraan |
| GET | `/vehicles/export` | `VehicleController@export` | Auth | Ekspor Excel |
| GET | `/vehicles/template` | `VehicleController@downloadTemplate` | Auth | Unduh template import |
| POST | `/vehicles/import` | `VehicleController@import` | Auth | Eksekusi AI Smart Import berbasis token sesi |
| POST | `/vehicles/import-legacy` | `VehicleController@importLegacy` | Auth | Import template standar tradisional |
| POST | `/vehicles/import-preview` | `VehicleController@importPreview` | Auth | Pratinjau & Analisis Pemetaan Kolom (AI) |
| POST | `/vehicles/truncate` | `VehicleController@truncate` | Auth | Kosongkan seluruh data |
| GET | `/vehicles/check-duplicates` | `VehicleController@checkDuplicates` | Admin / Superadmin | Pindai duplikasi kendaraan & OPD (JSON) |
| POST | `/vehicles/resolve-duplicate-vehicle` | `VehicleController@resolveDuplicateVehicle` | Admin / Superadmin | Gabungkan/hapus kendaraan ganda tervalidasi |
| POST | `/vehicles/resolve-duplicate-opd` | `VehicleController@resolveDuplicateOpd` | Admin / Superadmin | Gabungkan instansi OPD ganda tervalidasi |
| GET | `/master-data` | `MasterDataController@index` | Auth | Hub master data |
| Resource | `/vehicle-types` | `VehicleTypeController` | Auth | CRUD tipe kendaraan |
| POST | `/vehicle-types/cleanup` | `VehicleTypeController@cleanup` | Auth | Bersihkan tipe kosong |
| Resource | `/opds` | `OpdController` | Auth | CRUD data OPD |
| GET | `/settings` | `SettingController@index` | Auth | Halaman pengaturan |
| POST | `/settings` | `SettingController@update` | Auth | Simpan pengaturan |
| GET | `/profile` | `ProfileController@index` | Auth | Halaman profil saya |
| PUT | `/profile` | `ProfileController@update` | Auth | Perbarui data profil |
| Resource | `/users` | `UserController` | Superadmin | CRUD data pengguna |
| DELETE | `/activities/clear` | `ActivityController@clear` | Superadmin | Bersihkan seluruh log |
| DELETE | `/opds/truncate` | `OpdController@truncate` | Superadmin | Reset seluruh data OPD |
| GET | `/reports` | `ReportController@index` | Auth | Dashboard Modul Laporan |
| GET | `/reports/preview` | `ReportController@preview` | Auth | Preview laporan via AJAX HTML partial |
| GET | `/reports/export` | `ReportController@export` | Auth | Ekspor Excel laporan dinamis |
| GET | `/reports/print` | `ReportController@print` | Auth | Halaman cetak laporan ramah browser |
| GET | `/reports/pdf` | `ReportController@pdf` | Auth | Unduh PDF formal mPDF dengan Data Guard |
| GET | `/reports/settings` | `ReportSettingController@index` | Superadmin | Pengaturan kop, TTD, dan ekspor laporan |
| POST | `/reports/settings/letterhead` | `ReportSettingController@updateLetterhead` | Superadmin | Simpan kop surat laporan |
| POST | `/reports/settings/signatory` | `ReportSettingController@updateSignatory` | Superadmin | Simpan pejabat penanda tangan |
| POST | `/reports/settings/export` | `ReportSettingController@updateExportSetting` | Superadmin | Simpan aturan ekspor per tipe laporan |

---

## 8. 📚 Standar Dokumentasi Kode (PHPDoc)
Seluruh kode backend (Models, Controllers, Services, Enums, dll) wajib memiliki dokumentasi **PHPDoc dalam Bahasa Indonesia**.
- **Kelas/Enum:** Berikan penjelasan singkat mengenai tujuan dan fungsi utama kelas tersebut.
- **Properti Model:** Gunakan anotasi `@property` untuk mendefinisikan kolom database agar *auto-complete* pada editor berfungsi optimal.
- **Metode:** Sertakan penjelasan fungsionalitas, penjelasan parameter (`@param`), dan tipe nilai kembalian (`@return`).
- **Konsistensi:** Hindari penggunaan Bahasa Inggris dalam blok komentar dokumentasi untuk menjaga keseragaman codebase.

**Contoh Format Standar:**
```php
/**
 * Mendapatkan statistik dashboard untuk kendaraan.
 * 
 * Data di-cache selama 5 menit untuk performa optimal.
 * 
 * @param string|null $query Kata kunci pencarian
 * @return array{total: int, available: int, damaged: int}
 */
```

---

## 9. 🚨 Aturan Kritis untuk Sesi AI Berikutnya
1. **Jangan asumsikan konteks:** Selalu gunakan `view_file` untuk membaca berkas sebelum memodifikasi.
2. **Kepatuhan Desain & Interaksi Mikro:** Seluruh pemolesan visual kustom wajib diletakkan secara terisolasi di dalam [_vanilla-touches.scss](file:///d:/arif/laragon/www/E-RANDIS_PHP/resources/css/components/_vanilla-touches.scss). Dilarang mengotori berkas SCSS bawaan di folder `components/` lainnya. Pertahankan harmoni warna formal pemerintah (Navy/Putih/Gray) dengan dukungan animasi mikro premium yang menggunakan kurva bezier yang presisi dan hardware-accelerated.
3. **Penyusunan Aset:** Selalu jalankan `npm run build` setelah memodifikasi berkas SCSS atau Javascript agar aset terkompilasi bersih oleh Vite ke folder publik. Pastikan penambahan CSS mengikuti arsitektur SCSS Modular (7-1 Pattern) yang sudah ditetapkan.
4. **Bahasa Indonesia Wajib:** Seluruh dokumentasi kode (PHPDoc, komentar inline, pesan commit) dan komunikasi pengembangan wajib menggunakan **Bahasa Indonesia** secara konsisten.
5. **Jangan eksekusi tanpa persetujuan:** Jika user meminta perubahan pada area spesifik, jangan memperluas cakupan ke file lain tanpa konfirmasi terlebih dahulu.
6. **Konsistensi Validasi:** Untuk endpoint `store` dan `update`, utamakan `FormRequest` khusus dibanding validasi inline di controller, kecuali ada alasan teknis yang jelas untuk tidak melakukannya.
7. **Sinkronisasi Status:** Gunakan `AI_HANDOVER.md`, `PROJECT_MASTER.md`, dan dokumen fitur terbaru sebagai referensi utama sebelum memulai perubahan. Jangan mengandalkan dokumen status lama yang tidak lagi dipelihara.
8. **⚠️ WAJIB: Penambahan Fitur Baru:** Setiap penambahan fitur baru **HARUS** mengikuti prosedur yang tercantum dalam file `ATURAN_PENAMBAHAN_FITUR.md`. Dokumen tersebut berisi checklist lengkap mulai dari perencanaan, analisis dampak (multi-tenancy, cache, observer), implementasi teknis, testing, hingga deployment. **DILARANG KERAS** menambahkan fitur tanpa melalui checklist ini karena sistem memiliki arsitektur kompleks dengan risiko error 60-80% jika tidak mengikuti aturan. Baca dan ikuti `ATURAN_PENAMBAHAN_FITUR.md` sebelum memulai development fitur baru.
9. **Konteks Kritis Modul Laporan:** PDF formal sekarang bergantung pada tabel `report_letterheads`, `report_signatories`, `report_export_settings`, service `ReportDocumentSettingService`, dan folder publik `public/uploads/report/`. Saat deploy, jalankan migration + seeder pengaturan laporan sebelum menguji `/reports/pdf` dan `/reports/settings`.
