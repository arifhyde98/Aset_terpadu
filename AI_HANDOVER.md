# 🤖 AI Handover & Architecture Guide: SIPAT Terpadu

Dokumen ini merupakan sumber kebenaran tunggal (*Single Source of Truth*) mengenai arsitektur, jejak rekam fitur, skema database, konvensi antarmuka (UI/UX), dan aturan backend untuk platform **SIPAT Terpadu** yang mengintegrasikan tiga modul utama:
1. **E-RANDIS** (Manajemen & Inventarisasi Kendaraan Dinas)
2. **SIPAT** (Administrasi Pertanahan & Sertifikasi Aset Tanah KIB A)
3. **eLABEL** (Labelisasi Fisik, Berkas Sertifikat/BPKB & Universal Dynamic Archive Engine)

> **⚠️ PERHATIAN BAGI SETIAP AGEN AI / PENGEMBANG:**
> - Setiap agen AI yang melanjutkan pengembangan proyek ini **DIWAJIBKAN** membaca dokumen ini terlebih dahulu untuk menjaga konsistensi standar kode dan kelangsungan arsitektur.
> - Untuk penambahan fitur baru, **WAJIB** membaca dan mematuhi checklist di [`ATURAN_PENAMBAHAN_FITUR.md`](file:///home/arifhyde98/Projek/Aset_terpadu/ATURAN_PENAMBAHAN_FITUR.md) sebelum mengeksekusi kode.

---

## 📑 Daftar Isi
1. [🛠️ Environment & Technology Stack](#1-️-environment--technology-stack)
2. [🔐 Arsitektur Keamanan, Otorisasi & Multi-Tenancy](#2--arsitektur-keamanan-otorisasi--multi-tenancy)
3. [🛡️ Integritas Data, Transaksi & Relasi Lintas Modul](#3-️-integritas-data-transaksi--relasi-lintas-modul)
4. [📜 Sistem Audit Trail & Log Aktivitas Terpadu](#4--sistem-audit-trail--log-aktivitas-terpadu)
5. [⚡ Performa, Caching & Sinkronisasi Database](#5--performa-caching--sinkronisasi-database)
6. [🗄️ Skema Database Utama](#6-️-skema-database-utama)
7. [⚙️ Backend Architecture, Services & Aturan Validasi](#7-️-backend-architecture-services--aturan-validasi)
8. [📄 Standardisasi Laporan, Dokumen Cetak & Ekspor Resmi](#8--standardisasi-laporan-dokumen-cetak--ekspor-resmi)
9. [🎨 Design System, Estetika & Standar UI/UX](#9--design-system-estetika--standar-uiux)
10. [📦 Peta Fitur Penuh (Full Feature Stack)](#10--peta-fitur-penuh-full-feature-stack)
11. [🗺️ Peta Rute Aplikasi (Route Map)](#11-️-peta-rute-aplikasi-route-map)
12. [🚨 Aturan Kritis untuk Sesi AI Berikutnya](#12--aturan-kritis-untuk-sesi-ai-berikutnya)

---

## 1. 🛠️ Environment & Technology Stack

- **Framework Core:** Laravel 12 / PHP 8.2+
- **Database Engine:** MySQL / MariaDB / PostgreSQL (Supabase Compatible).
  - *Migrasi Idempoten:* Seluruh migrasi Laravel Blueprint murni (`database/migrations/`) dirancang idempoten menggunakan guard `if (!Schema::hasTable(...))` sehingga aman dijalankan via `php artisan migrate` tanpa risiko konflik `Table already exists` ataupun kehilangan data riil.
  - *Peringatan Kritis:* **JANGAN PERNAH** menjalankan `migrate:fresh` atau `migrate:reset` pada environment aktif/produksi!
- **Timezone:** Dikonfigurasi secara terpusat melalui variabel lingkungan `APP_TIMEZONE` di `.env` & `config/app.php`.
- **Frontend / Assets:**
  - **Bundler:** Vite
  - **UI Framework:** Bootstrap 5 (Disesuaikan secara terpusat via SCSS di `resources/sass/app.scss` dan komponen modular)
  - **Iconography:** Bootstrap Icons (Local via NPM/Vite)
  - **Notifications:** SweetAlert2 (Local via NPM/Vite) untuk peringatan, validasi *real-time*, & konfirmasi aksi CRUD
  - **Typography:** Plus Jakarta Sans (Local via `@fontsource`) & Monospace untuk plat nomor kendaraan, NIBAR, dan kode box arsip.
  - **GIS & Pemetaan Spasial:** Leaflet.js, Turf.js (`turf.min.js`), Shp.js (`shp.js`), Leaflet.Draw (`leaflet.draw.js`/`css`), serta aset GeoJSON dan Shapefile (.shp/.dbf) untuk render peta interaktif sebaran aset tanah & target pensertifikatan.
- **Data & Document Engine:**
  - **Laravel Excel (Maatwebsite/Excel):** Mesin utama impor dan ekspor data tabular massal (.xlsx, .csv).
  - **mPDF:** Mesin render PDF formal server-side untuk Modul Laporan, ekspor Surat SKPT, dan dokumen cetak resmi A4 Landscape/Portrait.
- **Artificial Intelligence Engine:**
  - **Google Gemini Cloud AI (`GeminiAiService`):** Menggunakan API model stabil Google Gemini (seperti `gemini-1.5-flash`) via `GEMINI_API_KEY` untuk asisten cerdas konsultasi BMD, tanya-jawab aset, dan pembuatan ringkasan otomatis pada floating widget interaktif.
  - **Ollama Engine (`OllamaService`):** Fallback LLM lokal (seperti model `qwen2.5:7b`) untuk lingkungan tanpa koneksi internet langsung.
- **Infrastruktur / Deployment:** Berjalan secara *native* di Linux / server lokal (Nginx + PHP-FPM 8.2+ & MySQL/MariaDB).
- **CI/CD Automation:** Menggunakan GitHub Actions (`.github/workflows/deploy.yml`) yang memicu eksekusi remote script `deploy.sh` (Git Pull, Composer Install, Artisan Migrate, NPM Build Vite, & Artisan Optimize) via SSH Key saat ada *push* ke branch `main`.

---

## 2. 🔐 Arsitektur Keamanan, Otorisasi & Multi-Tenancy

### 2.1 Sistem Role & Hak Akses
- **Enum Role:** Menggunakan Enum `App\Enums\UserRole` dengan tingkatan:
  - `SUPERADMIN`: Akses penuh konfigurasi sistem, database restore/sync, manajemen seluruh OPD, dan audit trail global.
  - `ADMIN`: Administrator pengelola aset tingkat BPKAD/daerah (akses lintas OPD, manajemen master data, verifikasi target).
  - `OPD`: Operator/Pengelola aset tingkat unit kerja instansi (terisolasi hanya pada data instansinya sendiri).

### 2.2 Isolasi Data Multi-Tenancy (Fail-Safe Access Control)
- **E-RANDIS (Kendaraan Dinas):**
  - Mengimplementasikan `App\Models\Scopes\TenantScope` pada model `Vehicle`.
  - Admin OPD secara otomatis dibatasi aksesnya hanya pada catatan berelasi `opd_id` miliknya.
  - *Fail-Safe:* Jika `opd_id` pengguna hilang atau bernilai `null`, sistem mengunci akses (menolak akses data) dan **bukan** membuka akses global.
- **SIPAT (Aset Tanah) & eLABEL (Pengarsipan):**
  - Data diisolasi berdasarkan instansi OPD.
  - Kolom `opd_id` pada model `AsetTanah` terhubung ke tabel `opd` (`OpdSipat` model).
  - Kolom `sipat_opd_id` pada tabel `elabel_bpkb`, `elabel_sertifikat_tanah`, dan `archive_items` menjamin kepemilikan arsip per instansi OPD.
- **OPD Mapping Hub:**
  - Menjembatani heterogenitas identitas instansi lintas modul melalui tabel `opd_mappings` yang memetakan `sipat_opd_id` (SIPAT/eLABEL) ke `erandis_opd_id` (E-RANDIS).

### 2.3 Single Sign-On (SSO) & Keamanan Sesi Terpusat
- **Middleware SSO (`App\Http\Middleware\SsoAuthenticate`):** Terdaftar dengan alias `sso` pada `bootstrap/app.php`.
- Memvalidasi token JWT (`sso_token`) terpusat dari portal SSO Pemerintah Kabupaten Donggala (`https://auth.sipat-donggala.my.id/auth/login`) dengan verifikasi signature HMAC SHA-256 dan sinkronisasi otomatis akun instansi (`User::firstOrCreate`).

### 2.4 Penguatan Otorisasi Pengontrol (`HasMiddleware` Standar Laravel 12)
Sesuai hasil audit keamanan, seluruh controller wajib menggunakan interface `HasMiddleware` dengan sintaks standar `new Middleware(...)`:
1. **Proteksi Akses Master Data SIPAT (`role:superadmin,admin`):**
   - `StatusProsesController`: Proteksi penuh konfigurasi alur tahapan status proses BPN.
   - `MasterSipatOpdController`: Proteksi pengelolaan daftar unit/instansi SIPAT.
   - `MasterDataWilayahController`: Proteksi master kecamatan, desa, camat, kepala desa, pemohon SKPT, dan judul laporan.
   - `KopSettingsController`: Proteksi pengaturan KOP surat resmi dan spesimen tanda tangan pejabat.
   - `AuditLogsController`: Proteksi audit trail aktivitas pengguna SIPAT.
2. **Pembatasan Mutasi Kritis Operasional SIPAT:**
   - `AsetTanahController@destroy`: Khusus Superadmin & Admin (mencegah akun ber-role OPD menghapus aset tanah).
   - `TargetSertifikatController`: Pembatasan method `store`, `update`, dan `destroy` (pengguna OPD hanya berstatus *read-only* memantau capaian target).
   - `TanahTakTercatatController@updateNibar`: Khusus Superadmin & Admin untuk memvalidasi promosi NIBAR resmi BPKAD.
   - `PetaController@importPoligon`: Khusus Superadmin & Admin untuk unggah dan penimpaan massal data spasial GIS.
   - `SuratController@deleteSkpt`: Khusus Superadmin & Admin untuk menghapus berkas SKPT resmi.
3. **Penyembunyian Elemen UI:** Tombol aksi hapus, penetapan target baru, dan validasi NIBAR otomatis disembunyikan pada template Blade (`aset.index`, `tanah_tak_tercatat.index`, dan `target_sertifikat.index`) bagi pengguna dengan role OPD.

### 2.5 Otomasi Akun & Sanitasi Kredensial
- **Otomasi Akun (Observer Level):** Logika pembuatan akun admin OPD dijalankan melalui `OpdObserver::created()`. Setiap OPD baru yang ditambahkan (baik melalui formulir manual maupun impor Excel) otomatis dibuatkan akun admin instansi secara instan.
- **Penyimpanan Terenkripsi Dua Arah:** Password akun disimpan terenkripsi dua arah (AES-256 via cast `encrypted` pada kolom `plain_password`), memungkinkan Superadmin melihat kredensial resmi untuk distribusi akun ke OPD tanpa menyimpan teks terbuka di database mentah/dump.
- **Sanitasi Kredensial Log:** `UserObserver` secara ketat membersihkan atribut sensitif (`password`, `plain_password`, `remember_token`) agar tidak pernah bocor ke tabel audit log.

---

## 3. 🛡️ Integritas Data, Transaksi & Relasi Lintas Modul

### 3.1 Aturan Integritas Luas Tanah Bersertifikat (SIPAT & e-LABEL)
Seluruh tanah bersertifikat di tabel `aset_tanah` **wajib sama persis** dengan luas fisik pada sertifikat di `elabel_sertifikat_tanah`. Aturan ini dijamin dua arah secara otomatis:
- `AsetTanahObserver::saving()`: Mengunci dan memastikan nilai `luas` di `AsetTanah` selalu mengikuti luas sertifikat e-Label jika sertifikat resmi telah terbit.
- `ElabelSertifikatObserver::saved()`: Otomatis menyinkronkan nilai `luas` pada `AsetTanah` jika data luas pada sertifikat e-Label ditambahkan atau diperbarui.
- **Audit Command:** Jalankan perintah audit integritas berkala via terminal:
  ```bash
  php artisan sipat:sync-luas-sertifikat
  ```

### 3.2 Aturan Integritas Kepemilikan OPD Sertifikat Tanah (SIPAT ↔ e-LABEL)
Sertifikat tanah di tabel `elabel_sertifikat_tanah` yang memiliki NIBAR di `aset_tanah` (KIB A) **wajib 100% mengacu pada OPD pemilik aset tanah di SIPAT** (`aset_tanah.opd_id` & `opd.nama`). Aturan kepemilikan instansi ini dikunci dan disinkronkan secara dua arah:
- `AsetTanahObserver::saved()`: Setiap kali data aset tanah KIB A disimpan atau diperbarui OPD-nya, sistem otomatis menyinkronkan kolom `sipat_opd_id` dan nama `dinas` pada sertifikat di e-Label yang memiliki NIBAR identik (`nibar = kode_aset`). Sinkronisasi dijalankan di dalam blok `\App\Models\Elabel\ElabelSertifikat::withoutEvents(...)` guna mencegah *circular observer trigger*.
- `ElabelSertifikatObserver::saving()`: Ketika berkas sertifikat di e-Label hendak disimpan/diperbarui, jika sertifikat memiliki NIBAR yang terdaftar pada katalog `AsetTanah`, nilai `sipat_opd_id` dan teks `dinas` otomatis dipaksa mengikuti instansi OPD pemilik di Aset Tanah SIPAT.
- **Relasi Eloquent Model:** Model `ElabelSertifikat` terhubung langsung ke `AsetTanah` melalui relasi kanonikal NIBAR:
  ```php
  public function asetTanah(): BelongsTo
  {
      return $this->belongsTo(\App\Models\AsetTanah::class, 'nibar', 'kode_aset');
  }
  ```
- **Proteksi Form Antarmuka (`resources/views/elabel/sertifikat/edit.blade.php`):** Pada formulir edit sertifikat, jika berkas terhubung ke NIBAR KIB A ber-OPD, pilihan dropdown OPD dikunci otomatis (`<input type="hidden" name="sipat_opd_id">` + input text disabled dengan badge `<i class="bi bi-lock-fill"></i> Mengacu KIB A` dan teks informasi `<i class="bi bi-shield-check"></i> Terkunci sesuai Master Aset Tanah`).
- **Artisan Command Audit Kepemilikan OPD:**
  ```bash
  php artisan sipat:sync-opd-sertifikat {--dry-run}
  ```
  - Memeriksa selisih OPD antara sertifikat e-Label (`sipat_opd_id`) dan aset tanah KIB A (`aset_tanah.opd_id`).
  - Menampilkan tabel verifikasi selisih: ID Sertifikat, NIBAR, No. Sertifikat, OPD Sertifikat (Saat Ini), dan OPD Aset Tanah (Tujuan).
  - Opsi `--dry-run` digunakan untuk audit tanpa menyimpan perubahan.
  - Mode eksekusi langsung memperbarui seluruh selisih via query SQL JOIN massal serta memicu pembersihan cache `SipatService::invalidateDashboardCache()`.

### 3.3 Penyelarasan Relasi Sertifikat Kanonikal & Deteksi Duplikasi BPN (`AsetTanahService`)
- **Penghapusan Dead Code:** Menghapus pemanggilan properti palsu `$aset->no_sertifikat` langsung pada model `AsetTanah` (kolom ini tersimpan di tabel `elabel_sertifikat_tanah` sebagai `no_sertipikat`).
- **Kunci Kanonikal Resmi NIBAR (`getAsetDetailsForModal`):** Menghubungkan aset tanah ke arsip sertifikat e-Label **hanya** melalui kunci kanonikal resmi NIBAR (`nibar = kode_aset`). Menghilangkan pencarian kabur (*fuzzy match* `LIKE %nama_aset%` ke `nama_pemilik`) guna mengeliminasi risiko *false positive* (salah menghubungkan sertifikat tanah milik aset lain).
- **Deteksi Duplikasi BPN (`getDuplicateAsetList`):** Mengaktifkan deteksi duplikasi nomor sertifikat BPN dengan mengambil data `no_sertipikat` dari tabel `elabel_sertifikat_tanah` yang terhubung dengan `aset_tanah.kode_aset`.

### 3.3 Aturan Arsitektur Modul Gedung & Bangunan (KIB C)
Modul KIB C mengelola gedung dan bangunan pemerintah daerah sesuai Permendagri No. 19/2016 jo. Permendagri No. 7/2024:
- **Tabel Basis Data:** `aset_bangunan` (terhubung ke `opd` via `opd_id`, dan ke `aset_tanah` via `aset_tanah_id` nullable).
- **Pengamanan Hukum:** Bangunan menunjuk ke bidang tanah KIB A tempatnya berdiri via NIBAR/`aset_tanah_id`. Pada model `AsetTanah`, relasi `bangunan()` dideklarasikan sebagai `hasMany(Bangunan::class, 'aset_tanah_id')`.
- **Standarisasi Permendagri No. 7/2024:** Menangani klasifikasi rumah jabatan/dinas tipe Khusus s/d E dengan batas standar luas bangunan dan tanah, serta pencatatan nama & NIP pejabat penghuni.
- **AI Smart Semantic Import:** Mengadopsi arsitektur `BangunanImportService` dan `BangunanMultiSheetImport` dengan kamus sinonim semantik teks (`similar_text >= 65%`) untuk impor fleksibel dari file Excel Simda/SIPD/Dinas ke tabel `aset_bangunan`.
- **Pusat Laporan mPDF:** Render laporan 18 kolom KIB C standar Permendagri dalam format A4-Landscape dengan KOP surat resmi dan lembar pengesahan tanda tangan ganda.
- **Observer & Audit Log:** `BangunanObserver` mencatat aktivitas perubahan snapshot ke tabel `activities` (`module_id = 4, module_key = 'bangunan'`) serta menginvalidasi cache `bangunan.stats.*`.

### 3.4 Proteksi Dependensi Data Master & Guard Cascade Delete
Mencegah terjadinya *cascade delete* database yang dapat melenyapkan data historis secara tidak sengaja:
- **Master Status Proses (`StatusProsesController@destroy`):** Memeriksa dependensi `ProsesAset::where('id_status', $id)->count()`. Menolak penghapusan jika status masih digunakan oleh catatan aset tanah aktif.
- **Master OPD SIPAT (`MasterSipatOpdController@destroy`):** Memeriksa dependensi pada `AsetTanah::where('opd_id', $id)`, `opd_mappings`, dan `elabel_sertifikat_tanah`. Menolak penghapusan jika OPD masih menaungi aset aktif.
- **Master Data Wilayah (`MasterDataWilayahController`):**
  - Mengganti seluruh pemanggilan `$request->all()` dengan data tervalidasi `$validated = $request->validate(...)` guna mencegah celah *mass assignment*.
  - Menambahkan guard dependensi relasi:
    - `kecamatanDestroy`: Memvalidasi catatan aset aktif di `aset_tanah` serta keberadaan `Camat` aktif sebelum menghapus kecamatan.
    - `desaDestroy`: Memvalidasi keterkaitan pada `surat_skpt` dan `KepalaDesa` sebelum menghapus desa/kelurahan.
    - `kadesDestroy`: Memvalidasi penggunaan pejabat kepala desa pada dokumen `surat_skpt`.
    - `camatDestroy`: Memvalidasi penggunaan pejabat camat pada dokumen `surat_skpt`.
    - `pemohonDestroy`: Memvalidasi penggunaan pemohon pada dokumen `surat_skpt`.
  - Otomatis memicu pembersihan cache `SipatService::invalidateDashboardCache()` pada setiap mutasi data kecamatan.

### 3.5 Atomisitas Transaksi & Pembersihan Berkas Fisik
- **Transaksi Aset Baru (`AsetTanahService::storeAset`):** Dibungkus penuh dalam `DB::transaction()` untuk menjamin atomisitas pembuatan aset tanah baru dan riwayat status BPN awal (*All-or-Nothing*).
- **Penghapusan Bersih (`AsetTanahService::deleteAset`):** Dibungkus dalam `DB::transaction()` dan secara otomatis mendeteksi serta menghapus berkas fisik lampiran dari disk (`Storage::disk('public')->delete(...)`) sebelum baris database dihapus, mencegah terjadinya penumpukan berkas sampah yatim (*orphaned files*).
- **Pendaftaran Tanah Belum Tercatat (`TanahTakTercatatController`):** Pembuatan aset tanah usulan dan status awal dibungkus dalam `DB::transaction()`. Memanggil `ProsesAset::create()` dengan key kolom resmi: `id_status`, `tanggal_proses`, dan `tgl_mulai` untuk mencegah galat database field default value.

### 3.6 Normalisasi Otomatis Master Wilayah Kecamatan (`PopulateAsetTanahKecamatanCommand`)
- **Artisan Command:**
  ```bash
  php artisan sipat:populate-kecamatan {--dry-run} {--sync-legacy}
  ```
- **Fungsi:** Mendeteksi string kecamatan pada field `peruntukan` dan `nama_aset` untuk aset tanah yang belum terisi `kecamatan_id` (null atau 0), serta menyelaraskan data lama via opsi `--sync-legacy`.
- **Aturan Pencocokan Majemuk (*Longest String First*):**
  - Memprioritaskan string terpanjang: `Banawa Selatan` / `Banawa Tengah` dievaluasi sebelum `Banawa`.
  - `Sindue Tobata` / `Sindue Tombusabora` dievaluasi sebelum `Sindue`.
  - `Balaesang Tanjung` dievaluasi sebelum `Balaesang`.
  - Normalisasi variasi spasi (`Rio Pakava` vs `Riopakava`).
  - Negative lookahead pada `Labuan` (mengecualikan kelurahan `Labuan Bajo`).
- Memicu invalidasi cache `SipatService::invalidateDashboardCache()` agar statistik dashboard wilayah langsung terbarukan.

### 3.7 Aturan Integritas Skema Relasi Fisik
- `onDelete('cascade')`: Pada relasi foreign key `opd_id` di tabel `users` (penghapusan instansi menghapus user terkait).
- `onDelete('set null')`: Pada kolom foreign key `user_id` di tabel `activities` untuk menjaga keutuhan riwayat audit trail meskipun akun pengguna yang bersangkutan dihapus dari sistem.
- `UserObserver::deleting`: Secara otomatis menghapus berkas fisik `avatar` dari storage disk saat akun pengguna dihapus.

---

## 4. 📜 Sistem Audit Trail & Log Aktivitas Terpadu

### 4.1 Konsolidasi Tiga Modul & Struktur Diff Data
Sistem mengonsolidasikan jejak audit dari 3 modul utama melalui `ActivityController@index`:
- `activities`: Log aktivitas E-RANDIS dan sistem umum.
- `elabel_activity_logs`: Log aktivitas modul pengarsipan eLABEL.
- `audit_logs`: Log aktivitas modul SIPAT (legacy & operasional pertanahan).

**Perekaman Data Sebelum vs Sesudah:**
Didukung penuh oleh kolom `old_data` dan `new_data` bertipe `longText` pada tabel `activities` dan `elabel_activity_logs` (migrasi `2026_09_04_000001_add_data_fields_to_elabel_activity_logs_table`).

### 4.2 Eloquent Observers & Sanitasi Keamanan
- `VehicleObserver`, `UserObserver`, dan `OpdObserver` menangani event:
  - `created`: Mencatat payload data baru (`new_data`).
  - `deleted` / `deleting`: Mencatat snapshot data lama (`old_data`).
  - `updated`: Mengekstrak diff kolom sebelum (`getOriginal`) dan sesudah (`getChanges`), secara cerdas mengabaikan timestamp internal `updated_at`.
- **Sanitasi Kredensial:** Atribut `password`, `plain_password`, dan `remember_token` dieliminasi otomatis dari payload sebelum disimpan.
- **Helper SIPAT:** Method `Activity::logSipat($description, $model, $oldData, $newData)` mendukung pencatatan data sebelum dan sesudah yang dimanfaatkan oleh `AsetTanahService` pada operasi pembuatan, pembaruan, penghapusan aset, serta pencatatan proses BPN dan lampiran.

### 4.3 UI/UX Tabel Log & Modal Diff Dual-Mode
- **Tabel Ramping:** Menggabungkan kolom Modul & Aksi dengan indikator dot warna, kolom pengguna & waktu dua baris yang proporsional, serta micro-badge payload (`Perubahan Data`, `Data Baru`, `Data Dihapus`).
- **Toolbar Filter:** Dilengkapi filter tab modul berkounter dinamis dan pencarian teks cepat (`search`).
- **Modal Diff Dual-Mode:**
  1. **Tabel Perbandingan Kolom:** Membandingkan nilai lama vs nilai baru kolom per kolom dengan highlight penanda warna diff.
  2. **JSON Mentah:** Menampilkan format raw JSON terstruktur lengkap dengan tombol 1-klik salin ke clipboard.
- **Keamanan Render Blade:** Payload JSON di-escape menggunakan `{{ json_encode(...) }}` dengan decoding entitas HTML otomatis pada browser untuk mencegah kerusakan struktur DOM akibat karakter kutip.

---

## 5. ⚡ Performa, Caching & Sinkronisasi Database

### 5.1 Strategi Targeted Cache Invalidation
Untuk mencegah penurunan performa akibat kueri agregasi berat berulang, data statistik di-cache menggunakan key dinamis:
- **Statistik Dashboard Kendaraan:** `dashboard.stats.[role].[opd_id]`
- **Ringkasan Modul Laporan E-RANDIS:** `reports.summary.{role}.{scope}`
- **Statistik Dashboard SIPAT:** Dikelola oleh `SipatService`

**Aturan Invalidation:**
- Seluruh aksi CRUD kendaraan dan OPD menggunakan helper terpusat `VehicleService::invalidateDashboardStats()`.
- Mutasi data tanah, status BPN, dan wilayah menggunakan `SipatService::invalidateDashboardCache()`.
- **DILARANG KERAS** menggunakan `Cache::flush()` global karena akan menghapus cache pengaturan sistem (`setting.{key}`) dan sesi aplikasi lainnya.

### 5.2 Utilitas Replikasi Staging & Database Restore
- **Restore Dump Database (`BackupController@restoreSql`):** Utilitas unggah dan restore database komprehensif dari berkas `.sql`, `.gz`, atau `.zip` dump database MySQL/MariaDB.
- **Replikasi Real-Time Staging (`BackupController@syncDbStream`, `syncDb`, & `RunSyncDbBg`):**
  - Mengalirkan replikasi penuh dari `db_sipat_terpadu` ke `db_sipat_staging`.
  - Menggunakan arsitektur **Server-Sent Events (SSE) streaming per-tabel** via perintah:
    ```bash
    mysqldump --single-transaction --quick --extended-insert --add-drop-table
    ```
  - Menampilkan progress visual real-time per tabel ke antarmuka pengguna tanpa jeda/hang, bebas dari risiko HTTP 504 Gateway Timeout (Cloudflare / Nginx), dan menjamin staging 100% identik dengan basis data sumber.

---

## 6. 🗄️ Skema Database Utama

### 6.1 Tabel Core & E-RANDIS
- **users:**
  - `id` (PK, BigInt)
  - `name`, `email`, `password`
  - `role` (String: `superadmin`, `admin`, `opd`)
  - `opd_id` (Nullable ForeignId ke `opds.id`, ON DELETE CASCADE)
  - `avatar` (String, Nullable)
  - `plain_password` (Text, Nullable, terenkripsi AES-256 via cast `encrypted`)
- **opds:** Master data unit kerja instansi E-RANDIS (`id`, `nama_opd`, `singkatan`, `alamat`, `telepon`, `is_active`). Terhubung 1-to-1 dengan user admin OPD.
- **vehicles:**
  - `id` (PK, BigInt)
  - `no_polisi` (String, Unique) — Nomor plat kendaraan.
  - `nomor_register` (String, Nullable, Unique) — Nomor register internal aset (unik global jika diisi).
  - `merk`, `tipe`, `warna`, `no_rangka`, `no_mesin` — Detail fisik aset.
  - `tahun_pembuatan`, `tgl_perolehan`, `nilai_perolehan` — Akuntansi perolehan aset.
  - `stnk_ada`, `bpkb_ada` (String: 'Ada' / 'Tidak') — Kelengkapan berkas legalitas.
  - `status` (String: 'Tersedia', 'Dipinjam', 'Nonaktif') — Status operasional.
  - `kondisi` (String: 'Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang', 'Dalam Penelusuran') — Kondisi fisik kendaraan.
  - `opd` (String) & `pemegang` (String) — Riwayat penanggung jawab.
  - `opd_id` (Nullable FK ke `opds.id`, ON DELETE SET NULL)
  - `vehicle_type_id` (Nullable FK ke `vehicle_types.id`, ON DELETE SET NULL)
- **activities:** Log audit sistem (`id`, `user_id` [FK Set Null], `type`, `description`, `old_data` [longText], `new_data` [longText], `created_at`).
- **settings:** Konfigurasi web/CMS (`id`, `key`, `value`, `group`), termasuk dukungan logo ganda:
  - `site_logo`: Logo Kiri (Lambang Daerah Pemerintah Kabupaten Donggala).
  - `site_logo_right`: Logo Kanan (Logo Instansi BPKAD / Khusus).

### 6.2 Tabel Modul SIPAT (Pertanahan)
- **opd (Model `OpdSipat`):** Master data OPD khusus modul pertanahan (`id`, `nama`, `aktif`).
- **aset_tanah:**
  - `id_aset` (PK, BigInt)
  - `kode_aset` (String) — NIBAR resmi atau kode aset usulan.
  - `status_pencatatan` (Enum: `TERCATAT_KIB_A`, `USULAN_BELUM_TERCATAT`) — Pemisah aset resmi KIB A vs usulan/draft.
  - `nama_aset`, `peruntukan` (String)
  - `luas` (Double) — Luas bidang tanah dalam m² (terkunci ke sertifikat fisik jika terbit).
  - `alamat` (Text)
  - `lat`, `lng` (Double) — Titik koordinat GPS.
  - `geojson` (Text / JSON) — Poligon spasial batas bidang tanah.
  - `opd_id` (FK ke `opd.id`), `opd` (String fallback)
  - `kecamatan_id` (FK ke `kecamatan.id`), `desa_id` (FK ke `desa.id`)
  - `dasar_perolehan`, `harga_perolehan`, `tanggal_perolehan`, `keterangan`
- **sipat_target_sertifikat:** Penetapan target pensertifikatan tahunan (`id`, `tahun`, `aset_tanah_id` [FK ke `aset_tanah.id_aset`], `target_jumlah`, `keterangan`, `created_at`, `updated_at`). Relasi OPD diturunkan langsung dari `asetTanah->opdSipat`.
- **proses_aset:** Riwayat tahapan pengurusan sertifikat BPN (`id_proses`, `id_aset`, `status_proses_id`, `id_status`, `tanggal_proses`, `tgl_mulai`, `keterangan`, `dokumen`). Status aktif diambil dari relasi baris terbaru (`latestProses`).
- **surat_skpt:** Dokumen Surat Keterangan Pendaftaran Tanah (`id`, `aset_tanah_id`, `nomor_surat`, `tanggal_surat`, `pemohon_id`, `camat_id`, `kades_id`, `keterangan`).
- **opd_mappings:** Jembatan relasi instansi antar-modul (`id`, `sipat_opd_id`, `erandis_opd_id`, `status_verifikasi`).

### 6.3 Tabel Modul eLABEL & Universal Dynamic Archive Engine
- **Katalog Berkas Fisik Legacy:**
  - `elabel_boxes`: Box penyimpanan fisik berkas BPKB.
  - `elabel_box_years`: Tahun berkas di dalam box BPKB.
  - `elabel_bpkb`: Katalog fisik berkas BPKB (`id`, `box_id`, `plate_number`, `no_bpkb`, `nibar`, `vehicle_type`, `status`, `sipat_opd_id`, `pdf_path`).
  - `elabel_bpkb_deletes`: Riwayat penyerahan/penghapusan BPKB keluar (*soft deleted*).
  - `elabel_sertifikat_boxes`: Box penyimpanan fisik berkas sertifikat tanah.
  - `elabel_sertifikat_tanah`: Katalog sertifikat tanah resmi (`id`, `no_sertipikat`, `tanggal_sertifikat`, `nibar`, `status_penggunaan`, `spesifikasi`, `luas`, `tanggal_perolehan`, `nilai_perolehan`, `nama_pemilik`, `cara_perolehan`, `alamat`, `lokasi`, `dinas`, `box_id`, `pdf_path`, `sipat_opd_id`). Model `ElabelSertifikat` memiliki relasi kanonikal `asetTanah()` (`belongsTo(AsetTanah::class, 'nibar', 'kode_aset')`) dan relasi `box()`.
  - `elabel_surat_penyerahan_boxes`: Box fisik penyimpanan berkas surat penyerahan.
  - `elabel_surat_penyerahan`: Berita acara penyerahan berkas fisik aset.
  - `elabel_loans`: Riwayat peminjaman berkas atau pengajuan scan berkas BPKB legacy.
- **Universal Dynamic Archive Engine (e-Arsip Dinamis Multi-Entitas):**
  - `archive_types`: Master jenis & skema form builder arsip dinamis (`id`, `kode`, `nama`, `deskripsi`, `icon`, `warna_badge`, `schema_fields` [JSON], `is_active`).
  - `archive_boxes`: Manajemen box fisik universal (`id`, `archive_type_id`, `nomor_box`, `barcode_code`, `lokasi_rak`, `tahun`, `kapasitas_maksimal`, `keterangan`, `created_by`).
  - `archive_items`: Dokumen berkas arsip dinamis (`id`, `archive_type_id`, `archive_box_id`, `opd_id`, `nomor_dokumen`, `nama_dokumen`, `tahun_dokumen`, `metadata` [JSON], `file_scan_pdf`, `status`, `keterangan`, `input_by`).
  - `archive_attachments`: Berkas lampiran pendukung multi-file (`id`, `archive_item_id`, `field_name`, `file_title`, `file_path`, `file_type`, `file_size`).
  - `archive_loans`: Layanan permohonan peminjaman & scan berkas dinamis (`id`, `archive_item_id`, `user_id`, `opd_id`, `requester_name`, `jenis_layanan`, `tanggal_pinjam`, `tanggal_kembali`, `status_persetujuan`, `keperluan`, `catatan_admin`, `approved_by`, `approved_at`).

---

## 7. ⚙️ Backend Architecture, Services & Aturan Validasi

### 7.1 Lapisan Layanan (*Service Layer*)
Seluruh logika kalkulasi dan query bisnis wajib dienkapsulasi di dalam kelas Service:
- `UnifiedAssetSearchService` (`app/Services/`): Layanan pencarian publik terpadu (*Unified Asset Search*) untuk Landing Page lintas 3 modul (Kendaraan Dinas, Sertifikat Tanah, dan Arsip Dokumen) dengan proteksi data privat (*Public Data Privacy*), deteksi relasi multi-modul, serta kalkulasi statistik live berbasis cache.
- `VehicleService` (`app/Services/`): Kalkulasi statistik dashboard kendaraan, manajemen cache kendaraan, helper penomoran plat nomor, validasi registrasi unik global, dan aturan mutasi kendaraan.
- `VehicleService` (`app/Services/Erandis/VehicleService.php`): Bisnis, format nopol, cache statistik dashboard, dan sinkronisasi data kendaraan dinas.
- `VehicleImportService` (`app/Services/Erandis/VehicleImportService.php`): Eksekusi pemetaan dan impor AI Smart Import Excel.
- `VehicleQueryService` (`app/Services/Erandis/VehicleQueryService.php`): Query builder terpaginasi dan penyaringan kendaraan dinas.
- `ReportService` (`app/Services/Erandis/ReportService.php`): Orkestrasi ringkasan data laporan E-RANDIS (mendukung data riil `vehicles` dan data historis e-BMD `ebmd_vehicles`) serta pemanggilan strategy aktif.
- `ReportGenerationService` (`app/Services/Erandis/ReportGenerationService.php`): Pembuatan berkas ekspor dan PDF laporan kendaraan dinas.
- `ReportDocumentSettingService` (`app/Services/Erandis/ReportDocumentSettingService.php`): Pengaturan dokumen, kop surat, dan penanda tangan laporan kendaraan.
- `SipatService` (`app/Services/Sipat/SipatService.php`): Menyediakan statistik ringkasan pertanahan, agregasi capaian BPN, cache dashboard SIPAT, dan sebaran wilayah kecamatan/OPD.
- `LaporanService` (`app/Services/Sipat/LaporanService.php`): Mesin pengolah laporan pertanahan resmi: resolusi judul 3 baris dinamis, ekspor Excel 11/12 kolom bersertifikat, dan penataan lembar pengesahan tanda tangan ganda.
- `AsetTanahService` (`app/Services/Sipat/AsetTanahService.php`):
  - Kueri Master Aset Tanah diurutkan menggunakan `CASE` SQL agar aset ber-NIBAR resmi selalu di urutan paling atas dan usulan draft (`DRAFT-`, `BELUM-`, null, `-`) di paling bawah.
  - Menggunakan **Eloquent Query Scopes** pada Model `AsetTanah` (`scopeSudahBersertifikat`, `scopeDalamProses`, `scopeBermasalah`, `scopeBelumBersertifikat`, dan `scopeFilterKategoriStatus`) sebagai *Single Source of Truth (SSOT)* filter status pertanahan.
  - Menghubungkan modal detail aset ke arsip `elabel_sertifikat_tanah` via relasi kanonikal `nibar = kode_aset` serta mendeteksi duplikasi nomor sertifikat BPN.
- `DynamicArchiveService` (`app/Services/Elabel/DynamicArchiveService.php`): Service layer inti **Universal Dynamic Archive Engine** untuk validasi skema form dinamis, penanganan berkas scan PDF utama & lampiran pendukung, penomoran kode box otomatis (`BOX-{KODE}-{NUM}`), audit trail aktivitas arsip dinamis, serta penyedia data menu otomatis sidebar (`getActiveTypesForSidebar`) dengan sistem caching terversi yang dilindungi `try-catch` dan *graceful database fallback*.
- **Dynamic Archive Observer (`ArchiveTypeObserver`):** Menjamin pembaruan otomatis menu navigasi sidebar & offcanvas mobile (`invalidateSidebarCache`) secara atomik tanpa memicu `Cache::flush()` global saat jenis arsip baru ditambahkan, diubah, atau dihapus. Dokumen arsip (`ArchiveItem`) tidak memicu invalidasi cache sidebar untuk menjaga performa simpan/upload berkas yang tinggi.
- `ElabelSmartBpkbExtractorController`: Modul isolasi pembacaan isi dokumen PDF BPKB otomatis (*Smart PDF Extractor & OCR*) pada rute `/elabel/bpkb-smart-extractor` dengan verifikasi 4 aturan presisi (Pencocokan Nopol 100% Persis, Proteksi Berkas Ganda, dan Dry-Run Audit Preview).
- `GeminiAiService` (`app/Services/GeminiAiService.php`): Integrasi Google Gemini Cloud AI via `GEMINI_API_KEY` (dengan fallback `OllamaService`) untuk melayani endpoint asisten cerdas `/ai/ask` dan `/ai/generate-summary`.

### 7.2 Arsitektur Modul Laporan E-RANDIS (*Strategy & Registry Pattern*)
Dibangun secara modular dan fleksibel:
- `Erandis\ReportController`: Menangani HTTP request halaman laporan, preview AJAX, ekspor Excel, dan cetak browser.
- `ReportRegistry`: Registry terpusat yang memetakan identifier tipe laporan ke kelas Strategy yang sesuai.
- `ReportStrategy`: Kontrak *interface/abstract* bersama untuk seluruh strategi laporan kendaraan, mendukung `referenceQuery` lintas tenant dan pemilihan model dinamis (`Vehicle` vs `EbmdVehicle`).
- **Kelas Strategi Laporan:** `VehicleStatusReport`, `OpdAssetReport`, `DocumentValidityReport`, dan `DuplicateVehicleReport`.
- `DynamicReportExport`: Kelas induk abstrak ekspor Excel (menyertakan informasi filter dan sumber data pada header).
- `DynamicQueryReportExport` & `DynamicCollectionReportExport`: Subclass pembeda antara streaming kueri hemat memori (`FromQuery`) untuk laporan standar dan ekspor berbasis koleksi (`FromCollection`) untuk laporan dengan pengayaan data.
- `Erandis\ReportDocumentSettingService`: Mengelola relasi konfigurasi dokumen cetak (`report_export_settings`, `report_letterheads`, `report_signatories`) dengan fallback terprogram agar ekspor PDF tidak pernah gagal saat konfigurasi database kosong.

### 7.3 Validasi Kelas Permintaan (*Form Request Validation*)
Penyimpanan dan pembaruan data wajib menggunakan kelas Form Request:
- `Erandis\StoreVehicleRequest` / `Erandis\UpdateVehicleRequest`: Validasi data fisik kendaraan dan isolasi otomatis `opd_id` untuk pengguna ber-role OPD.
- `StoreUserRequest` / `UpdateUserRequest`: Validasi manajemen akun pengguna, role, dan enkripsi password.
- `StoreSuratSkptRequest`: Validasi pembuatan berkas SKPT dengan integritas relasi pejabat (camat, kepala desa, dan pemohon).
- `Erandis\ReportFilterRequest`: Validasi filter laporan kendaraan (sumber data `real`/`ebmd`, status, OPD, tahun) serta mengunci `opd_id` akun OPD agar tidak dapat disusupi via parameter URL.

### 7.4 Konvensi Middleware & Akses Rute (Laravel 12 Standard)
Semua Controller wajib mengimplementasikan interface `HasMiddleware` dengan sintaks statis `middleware()`:
```php
// ✅ BENAR (Laravel 12 Best Practice)
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ContohController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:superadmin,admin'),
        ];
    }
}
```

---

## 8. 📄 Standardisasi Laporan, Dokumen Cetak & Ekspor Resmi

### 8.1 Format 11/12 Kolom Laporan Aset Tanah (SIPAT)
Format tabel laporan pertanahan diselaraskan persis dengan format cetak resmi Pemerintah Kabupaten Donggala:
- **11 Kolom Standar Resmi:**
  1. `NO.`
  2. `Kode Aset / NIBAR`
  3. `Nama Barang` (`nama_aset`)
  4. `Lokasi` (`alamat`)
  5. `Bidang` (`peruntukan`)
  6. `Luas (m²)` (`luas`)
  7. `Nilai (Rp)` (`harga_perolehan`)
  8. `Tanggal Perolehan` (`tanggal_perolehan`)
  9. `Cara Perolehan` (`dasar_perolehan`)
  10. `Status` (`latestProses.statusProses.nama_status`)
  11. `Keterangan` (Murni catatan `$row->keterangan`, dilarang keras fallback ke nama barang).
- **Dukungan Dinamis 12 Kolom (Sub-Kolom `No. Sertifikat`):**
  - Ketika filter `kategori_status === 'sudah_bersertifikat'` aktif, dokumen cetak dan ekspor otomatis berekspansi menjadi 12 kolom dengan menyisipkan sub-kolom `No. Sertifikat` di bawah grup header `Aset Sudah Bersertifikat` persis di antara sub-kolom `Bidang` dan `Luas (m²)`:
    `Bidang` | `No. Sertifikat` | `Luas (m²)` | `Nilai (Rp)`
  - Nomor sertifikat diambil secara efisien melalui eager loading relasi `sertifikatElabel` (`$row->sertifikatElabel?->no_sertipikat ?? '-'`).
- **Implementasi Kanal Output:**
  1. **Tampilan Web (`sipat/laporan/index.blade.php`):** Desain modern 2-kolom (`.report-shell`) yang memisahkan panel filter dinamis dan kartu ringkasan hasil query (Total Bidang, Total Luas m², Total Nilai Rp) di sisi kiri, serta kartu aksi ekspor cepat (Pratinjau PDF, Unduh PDF, Unduh Excel, Cetak Browser, dan Tab Rekapitulasi per OPD) di sisi kanan.
  2. **Cetak / Unduh PDF (`sipat/laporan/print_pdf.blade.php`):** Merender 11 kolom atau 12 kolom dinamis (dengan sub-kolom `No. Sertifikat` `colspan="4"` di grup header dan total baris `colspan="6"`) dengan tata letak A4-Landscape presisi 100%.
  3. **Ekspor Excel (`LaporanService::exportExcel`):** Kolom A s.d. L (12 kolom), KOP A:L, sub-kolom `Bidang` (E), `No. Sertifikat` (F), `Luas (m²)` (G), `Nilai (Rp)` (H), merge total `A:F`, dan blok tanda tangan pada kolom `J:L`.
  - Untuk kategori lainnya (Semua/Rekap Umum, Belum Diproses, Dalam Proses, Bermasalah/Sengketa), struktur 11 kolom standar tetap dipertahankan.

### 8.2 Mesin Judul Dinamis 3 Baris & Mode Judul
- **Tata Naskah Resmi 3 Baris (`LaporanService::resolveReportTitleLines`):**
  - **Baris 1:** Kategori / Status Laporan (contoh: `LAPORAN ASET TANAH SUDAH BERSERTIFIKAT`).
  - **Baris 2:** Nama Instansi OPD (contoh: `DINAS PENDIDIKAN DAN KEBUDAYAAN`) atau fallback `PEMERINTAH KABUPATEN DONGGALA` jika memilih seluruh OPD.
  - **Baris 3:** Lokasi Wilayah & Tahun (contoh: `KECAMATAN BANAWA TAHUN 2026` atau `TAHUN 2026`).
  - Format ini mengeliminasi tahun mengambang sendirian (*orphan year*) dan mencegah pembungkusan teks sembarangan.
- **Fleksibilitas 3 Mode Judul:**
  - `auto`: Otomatis digenerate sesuai filter aktif (default).
  - `master`: Memilih dari master judul yang telah tersimpan.
  - `manual`: Mengetik judul kustom bebas.

### 8.3 Format Penanda Tangan Ganda (*Dual Signatories*) & KOP Surat Resmi
- **Pengesahan Berdampingan:** Lembar pengesahan laporan SIPAT menampilkan dua penanda tangan berdampingan:
  - **Sisi Kiri:** Pejabat Pengurus Barang / Bidang Aset (`kop_pejabat1_*`).
  - **Sisi Kanan:** Pengguna Barang / Kepala Badan BPKAD (`kop_pejabat2_*`) dengan titimangsa lokasi (`kop_kota_ttd`) dan tanggal cetak.
- **Sanitasi Format NIP:** Menghapus duplikasi prefiks otomatis menjadi `NIP. [Nomor]`.
- **Ruang Tanda Tangan Lapang:** Menggunakan baris khusus pemisah tanda tangan (`signature-space-row` / `ttd-space-row` setinggi 75px pada CSS PDF) untuk mencegah runtuhnya elemen kosong (*collapsing empty div*) pada mPDF, serta menyisipkan 4 baris berjarak 16pt pada lembar kerja Excel.
- **Resolusi Jalur Logo Fisik:** Logo KOP Pemda dimuat melalui path fisik lokal (`public/storage/kop/...` atau fallback `images/logo.png`), aman saat di-render oleh mPDF maupun disematkan sebagai drawing object pada file Excel.
- **Stabilitas Memori mPDF:** Ekspor PDF dataset penuh (1.190+ baris aset tanah) diperkuat dengan konfigurasi `pcre.backtrack_limit` (15M) dan alokasi memori `1024M` agar terhindar dari galat pemutusan regex backtrack.
- **Master Pengaturan KOP Surat:** Dikelola terpusat di `/master-data/kop-surat` (route name: `master.kop-settings.index`) dengan pratinjau live berdampingan.

### 8.4 Optimasi Ekspor E-RANDIS & Konfigurasi Dokumen Cetak
- **Trigger Ekspor:** `exportExcel()`, `exportPdf()`, dan `printReport()` aktif pada antarmuka laporan kendaraan.
- **Data Guard & Chunking:** Batas guard PDF ditetapkan pada 3.500 baris, menggunakan teknik *table chunking* (100 baris per sub-tabel) guna mencegah batas memori dan PCRE backtrack limit pada mPDF.
- **Kolom Jenis Kendaraan:** Kolom "Jenis Kendaraan" (`jenis`) ditampilkan konsisten di seluruh strategi laporan E-RANDIS (Status, Distribusi OPD, STNK/Dokumen, Duplikasi) pada Pratinjau Web, Cetak Browser, PDF, dan Excel.
- **Integrasi Pengaturan Cetak:** Tabel `report_letterheads` dan `report_signatories` diselaraskan dengan identitas resmi Pemerintah Kabupaten Donggala (BPKAD Banawa). Dikelola melalui rute `/settings/reports` yang terhubung langsung dari menu navigasi E-RANDIS dan Pengaturan Sistem.

### 8.5 Penanganan Safety Dokumen SKPT
- Pada `SuratController@pdfSkpt`, penamaan unduhan menggunakan fallback aman `$skpt->nomor_surat ?? $skpt->id` (mencegah galat runtime PHP `Undefined variable $id`).
- Mengganti seluruh fungsi usang `esc()` pada template SKPT dengan helper standar Laravel `e()`.

---

## 9. 🎨 Design System, Estetika & Standar UI/UX

### 9.1 Skema Warna & Prinsip Estetika Formal
- **Nuansa Formal Instansi:** Memprioritaskan kombinasi **Navy (`#1E40AF`), Putih, dan Slate Gray** yang bersih, berwibawa, dan profesional.
- **Tipografi:** Plus Jakarta Sans untuk keterbacaan teks modern, dipadukan dengan Monospace untuk plat kendaraan, NIBAR, dan kode register.

### 9.2 Sentuhan Vanilla SCSS & Animasi Mikro Premium
Seluruh peningkatan visual kustom diisolasi pada file [`resources/sass/components/_vanilla-touches.scss`](file:///home/arifhyde98/Projek/Aset_terpadu/resources/sass/components/_vanilla-touches.scss):
1. **Elevasi Kartu (`.hover-elevate`):** Kartu statistik dashboard dan widget terangkat secara halus (`translateY(-5px)`) disertai bayangan lembut saat disentuh kursor.
2. **Dropdown Liquid Smooth (`.dropdown-menu`):** Dropdown menu bertransisi meluncur lembut dari atas (`translateY(12px)`) dibarengi efek fade-in.
3. **Efek Sapuan Kilat (`.btn-premium-glow`):** Tombol aksi utama memancarkan animasi kilatan cahaya halus dari kiri ke kanan saat disorot kursor.
4. **Skeleton Shimmer (`.skeleton-shimmer`):** Efek bayangan memuat data (shimmer loader) menggantikan spinner kaku pada proses pencarian AJAX dan kalkulasi data.
5. **Bouncy Liquid Modal (`.modal`):** Dialog modal muncul dengan animasi mengembang elastis (kurva `cubic-bezier(0.34, 1.56, 0.64, 1)` dari `scale(0.96)` ke `scale(1)`).
6. **Glassmorphism Navbar (`#navbar-main`):** Navbar portal utama bertransisi menjadi panel kaca semi-transparan (`backdrop-filter: blur(12px)`) ketika halaman digulir vertikal (`.scrolled`).

### 9.3 Standar Ergonomi Interaksi, Konfirmasi Hapus & Formulir
- **Standardisasi Modal SweetAlert2 Tunggal:**
  - Menghilangkan atribut inline `onsubmit="return confirm(...)"` pada form yang telah memiliki class `.delete-confirm`.
  - Mencegah kemunculan dialog konfirmasi ganda (dialog native browser disusul modal SweetAlert2) sehingga seluruh aksi hapus konsisten menggunakan modal SweetAlert2 tunggal.
- **Eliminasi Auto-Submit Agresif:**
  - Menghapus listener debounce auto-submit reload pada input pencarian teks (`q`) dan judul manual (`manual_title`) pada halaman laporan agar kursor tidak kehilangan fokus (*focus lost*) saat pengguna sedang mengetik. Form disubmit melalui tombol **Enter** atau tombol **Terapkan Filter**.
- **Sticky Table Header Solid:** Header tabel pratinjau diberi warna latar belakang solid (`--bs-tertiary-bg`) sehingga teks data di bawahnya tidak terlihat tembus pandang saat tabel digulir ke bawah.
- **Interaktivitas AJAX Laporan Kendaraan:** Fungsi JS `sortByField(field)` untuk pengurutan kolom tabel AJAX dan `handleTypeChange()` untuk pergantian jenis laporan berjalan mulus tanpa memicu error console peramban.

### 9.4 Optimasi Layout Dashboard & Formulir Proteksi Instansi
- **Proporsi Card Breakdown Dashboard (`resources/views/home.blade.php`):** Rincian metrik pada kartu e-RANDIS, eLABEL, dan Layanan Aktif distandarkan menggunakan tipografi `0.81rem`, utility class `text-nowrap`, serta padding adaptif `p-3 p-xxl-4` guna mencegah pemotongan teks atau pembungkusan baris (*unwanted text wrapping*) pada resolusi layar kerja.
- **Formulir Edit Terproteksi (`resources/views/elabel/sertifikat/edit.blade.php`):** Pilihan dropdown instansi OPD secara visual bertransformasi menjadi field terkunci (*read-only / disabled*) berlabel badge `<i class="bi bi-lock-fill"></i> Mengacu KIB A` serta hidden input ketika dokumen sertifikat terhubung dengan NIBAR aset tanah KIB A.

---

## 10. 📦 Peta Fitur Penuh (Full Feature Stack)

### 10.1 Modul E-RANDIS (Manajemen Kendaraan Dinas)
- **Pencarian Publik Landing Page (`/` dan `/vehicle-search`):** Antarmuka pencarian kendaraan dinas bagi masyarakat dengan auto-formatting plat nomor via `VehicleService::formatPlateNumber()`.
- **AI Smart Import Excel (`/vehicles/import`):** Impor kendaraan massal dengan analisis header semantik otomatis, pemilihan sheet pertama yang valid, visualisasi sampel 3 baris pratinjau, dan eksekusi berbasis `import_token` yang aman.
- **Diagnosis & Resolusi Duplikasi Data 4 Tingkat:** Deteksi duplikasi aset tanah dengan algoritma presisi tinggi (NIB identik, penambahan suffix `(2)` hasil impor, Nomor Sertifikat BPN sama, serta kombinasi Peruntukan + OPD + Luas identik) dilengkapi aksi konsolidasi/merge data.
- **Rekonsiliasi BPKB Kendaraan (`/vehicles/rekon-bpkb`):** Fitur verifikasi silang kepemilikan berkas fisik BPKB di eLABEL dengan data inventaris fisik kendaraan di E-RANDIS.
- **Sanitasi Identifier Kendaraan:** Perbaikan otomatis nomor rangka/mesin yang tertukar (`/vehicles/sanitize-swapped-identifiers`) dan sinkronisasi data e-BMD ke data riil.
- **Laporan Komprehensif Kendaraan (`/reports`):** Laporan status kendaraan, sebaran per OPD, validitas STNK/dokumen, dan analisis duplikasi dengan ekspor Excel, PDF mPDF (chunked), dan pengaturan kop/penanda tangan dinamis.

### 10.2 Modul SIPAT (Administrasi Pertanahan)
- **Katalog Aset Tanah (`/sipat/aset`):**
  - Pendataan aset tanah KIB A Pemkab Donggala, luas, peruntukan, dasar perolehan, koordinat GPS, dan batas bidang.
  - **Filter Kategori Aset Presisi:** Menyaring status pertanahan dengan sinkronisasi presisi (opsi *Belum Bersertifikat* / `belum_diproses` selaras 100% dengan metrik Dashboard Utama: Total - Bersertifikat - Kendala - Target = tepat 669 bidang).
  - **Filter Status BPN Multi-Select Dinamis:** Checkbox status proses menyesuaikan secara dinamis dengan Kategori Aset yang sedang aktif dan otomatis reset jika kategori berganti.
  - **Session Filter Persistence (User-Scoped, 15-Min TTL):** Filter aktif tersimpan per User ID dengan batas kedaluwarsa 15 menit, dibersihkan otomatis saat logout.
  - **Ekspor & Impor 2 Tab:** Ekspor Excel & PDF sesuai filter aktif, serta modal impor 2 tab (Unggah Aset Baru & Pembaruan Status BPN Massal).
- **Target Pensertifikatan & Pemetaan Spasial GIS (`/sipat/target-pensertifikatan`):**
  - Pengelolaan KPI target pensertifikatan tanah tahunan.
  - **5 Kotak Metrik Ringkasan:** Total Target Bidang, Belum Diproses, Sedang Proses, Realisasi (Tercapai), dan Capaian Target Pemda (%).
  - **Ekspor Kinerja Target:** Ekspor Excel berlabel resmi (`/sipat/target-pensertifikatan/export-excel`) dan unduh dokumen PDF formal (`/sipat/target-pensertifikatan/export-pdf`).
  - Peta spasial GIS interaktif (Leaflet.js & Shapefile/GeoJSON) untuk visualisasi poligon bidang tanah target.
- **Tanah Belum / Tak Tercatat (`/sipat/tanah-tak-tercatat`):**
  - Pengelolaan khusus bidang tanah yang belum masuk KIB A atau belum memiliki NIBAR definitif (`DRAFT-YYYYMMDD-XXXX`).
  - **Dashboard Breakdown:** Pemisahan jelas antara Tanah Keseluruhan (1.189 bidang), Tercatat KIB A (1.188 bidang), dan Belum Tercatat (1 bidang).
  - Promosi usulan menjadi NIBAR resmi khusus Superadmin/Admin.
- **Rekonsiliasi Sertifikat Tanah SIPAT ↔ eLABEL (`/sipat/rekonsiliasi`):** Fitur audit silang untuk mencocokkan aset tanah bersertifikat di SIPAT dengan fisik sertifikat resmi yang telah diarsipkan di eLABEL.
- **Progres Pengurusan Sertifikat BPN:** Pencatatan tahapan pengurusan sertifikat tanah (Pendaftaran, Pengukuran, PBT, Surat Keputusan, hingga Terbit Sertifikat) dengan histori lengkap.
- **Modul Surat Tanah (SKPT & Batas):** Pembuatan Surat Keterangan Pendaftaran Tanah resmi dengan ekspor PDF mPDF, Word (.docx), dan cetak langsung.
- **Peta Aset Tanah Spasial (`/sipat/peta`):** Visualisasi marker dan batas poligon seluruh sebaran aset tanah daerah.
- **Pusat Laporan Aset Tanah & Ekspor Resmi 11/12 Kolom (`/sipat/laporan`):** Standardisasi format resmi Pemkab Donggala dengan dukungan sub-kolom sertifikat dinamis, mesin judul 3 baris, dan pengesahan tanda tangan ganda.
- **Rekapitulasi Pensertifikatan Aset Tanah per OPD (`/sipat/laporan/rekap-opd`):** Halaman dan dokumen rekapitulasi progres pensertifikatan seluruh instansi OPD Pemkab Donggala dengan ekspor Excel, unduh PDF A4-L, dan cetak browser.
- **Master Wilayah & Status Proses Dinamis:**
  - Master Kecamatan & Desa Kabupaten Donggala terhubung langsung ke sebaran aset tanah (`/master-data/wilayah`).
  - Master Status Proses BPN dengan dukungan multi-kategori per status (`/master-data/status-proses`).
  - Master KOP Surat & Pejabat Penanda Tangan Pemda (`/master-data/kop-surat`).
- **Visualisasi Dashboard & Landing Page:**
  - Widget distribusi aset Top 5 OPD dengan doughnut chart representasi 100% di dashboard SIPAT.
  - Tabel sebaran lengkap 54 OPD dan 16 Kecamatan dengan pencarian cepat (*live search*).
  - Integrasi 2 Donut Chart berdampingan di Landing Page (`/`) dengan modal interaktif full-featured (`#modalSebaranOpdLanding` dan `#modalSebaranKecamatanLanding`).

### 10.3 Modul eLABEL (Pengarsipan & Universal Dynamic Archive)
- **Katalog & Box Berkas Fisik BPKB:**
  - Pengarsipan fisik dokumen BPKB Kendaraan ke dalam box arsip berlabel barcode.
  - Paginasi dinamis dan optimasi performa query (eager loading `box`, `inputUser`, `opdSipat`, kontrol `per_page`: 15, 50, 100, Semua, serta pagination bar mirip modul SIPAT) pada rute `/elabel/bpkb`.
  - Fitur pemecahan (*split*) dan penggabungan (*merge*) box arsip.
  - Cetak label stiker barcode box fisik (`/elabel/boxes/{id}/label`).
- **Katalog BPKB Keluar / Soft-Deleted (`/elabel/bpkb-deleted`):**
  - Pencatatan dokumen BPKB yang diserahkan/dihapus sementara dengan histori penyerahan, upload surat tanda terima/dokumen pendukung, export data, dan fitur pemulihan (*restore*).
- **Smart BPKB PDF Extractor & OCR (`/elabel/bpkb-smart-extractor`):**
  - Ekstraksi otomatis isi dokumen PDF BPKB dengan pencocokan nopol 100% persis dan proteksi berkas ganda.
  - Pratinjau PDF tab baru dan verifikasi hasil sebelum disimpan.
- **Sertifikat Tanah Fisik & Box (`/elabel/sertifikat`, `/elabel/sertifikat-boxes`):**
  - Penyimpanan fisik sertifikat tanah, penataan box fisik khusus sertifikat, operasi split/merge box, dan impor Excel.
  - Sinkronisasi otomatis dua arah untuk luas fisik tanah dan instansi kepemilikan OPD dengan modul SIPAT (dilengkapi command audit terminal `php artisan sipat:sync-opd-sertifikat {--dry-run}`).
  - Proteksi form edit sertifikat dengan penguncian dropdown OPD otomatis dan badge `Mengacu KIB A` untuk berkas yang terhubung dengan NIBAR aset tanah KIB A.
- **Surat Penyerahan & Box Penyerahan (`/elabel/surat-penyerahan`, `/elabel/surat-penyerahan-boxes`):**
  - Administrasi berita acara penyerahan fisik berkas aset dan penataan box fisik surat penyerahan.
- **Alur Peminjaman Dokumen (`/elabel/peminjaman`):**
  - Pengajuan pinjam berkas fisik atau request scan dokumen BPKB/Sertifikat oleh operator OPD dengan persetujuan admin.
- **Universal Dynamic Archive Engine (e-Arsip Dinamis):**
  - **Visual Form Builder (`/elabel/dynamic/types`):** Pembuatan master jenis arsip kustom berbasis schema JSON (mendukung input teks, angka, tanggal, dropdown, checkbox, dan lampiran).
  - **Manajemen Box Universal (`/elabel/dynamic/boxes`):** Penataan box fisik dengan penomoran otomatis (`BOX-{KODE}-{NUM}`), barcode, rak, dan tahun.
  - **Katalog Berkas Dinamis (`/elabel/dynamic/items`):** Pengarsipan dokumen dinamis multi-lampiran dengan pencarian metadata JSON dan viewer PDF terintegrasi.
  - **Layanan Peminjaman Dokumen (`/elabel/dynamic/loans`):** Alur permohonan pinjam berkas fisik atau request scan dokumen oleh operator OPD dengan persetujuan admin.
  - **Menu & Submenu Sidebar Otomatis (Grup Mandiri per Kategori):** Setiap kategori arsip dinamis aktif otomatis dibuatkan grup menu collapsible mandiri (seperti halnya Dokumen BPKB dan Sertifikat Tanah) lengkap dengan icon kustom, warna tema, submenu Katalog Dokumen (`/elabel/dynamic/items?type_id={id}`), dan submenu Box Arsip (`/elabel/dynamic/boxes?type_id={id}`). Sedangkan menu **ARSIP DINAMIS** difokuskan khusus untuk pengaturan sistem (*Master Kategori & Form*, *Semua Berkas*, *Manajemen Box*, dan *Layanan Peminjaman*) dan diproteksi ketat hanya dapat diakses oleh role **Superadmin** (baik pada visibilitas menu sidebar & mobile bottom-nav maupun pembatasan controller middleware `role:superadmin`).
  - **Antarmuka Terkontekstualisasi & Modal Input Terintegrasi:** Saat pengguna mengakses menu kategori spesifik (contoh: *SK Penghapusan* via `type_id={id}`), seluruh elemen antarmuka otomatis menyesuaikan diri: judul halaman, breadcrumb, toolbar (`Input {kode} Baru` & `Box {kode}`), pencarian & filter dengan hidden `type_id`, tombol reset yang mempertahankan kategori aktif, serta kolom tabel "Kategori" yang otomatis disembunyikan. Penambahan data berkas ([`#createItemModal`](file:///home/arifhyde98/Projek/Aset_terpadu/resources/views/elabel/dynamic/items/index.blade.php)) dan box fisik ([`#createBoxModal`](file:///home/arifhyde98/Projek/Aset_terpadu/resources/views/elabel/dynamic/boxes/index.blade.php)) keduanya menggunakan **Modal Popup** responsif langsung di halaman katalog, konsisten dengan standar modul Kendaraan dan Aset Tanah.

### 10.4 Modul Administrasi, Asisten AI & Manajemen Pengguna
- **Asisten Pintar AI (Google Gemini Cloud AI Integration):**
  - Floating Widget interaktif (`resources/views/layouts/partials/ai-floating-widget.blade.php`) di seluruh antarmuka internal aplikasi.
  - Konsultasi dan Q&A seputar regulasi BMD, panduan fitur, serta ringkasan data aset via endpoint `/ai/ask` dan `/ai/generate-summary` yang ditenagai oleh `GeminiAiService` (model Google Gemini 1.5 Flash).
- **Manajemen Akun Terpadu (`/users`):** Pengelolaan seluruh akun pengguna (Superadmin, Admin, Admin OPD).
- **Detail Kredensial Interaktif:** Tombol ikon mata (`bi bi-eye`) pada tabel pengguna untuk membuka modal kredensial akun.
- **Lihat / Sembunyikan Kata Sandi:** Toggle password (`bi-eye` / `bi-eye-slash`) untuk membaca kata sandi pengguna secara aman via dekripsi AES-256.
- **Salin Kredensial Cepat:** Tombol 1-klik untuk menyalin Email dan Password ke clipboard guna mempermudah pendistribusian akun ke admin OPD.
- **Reset Password Otomatis:** Pembuatan password acak berformat `DGL-XXXX` dengan sinkronisasi langsung ke database terenkripsi dan konfirmasi notifikasi SweetAlert2.

### 10.5 Portal Terpadu & Unified Asset Search
- **Landing Page Publik (`/`):** Portal pencarian terpadu publik untuk memeriksa keberadaan kendaraan dinas, sertifikat tanah, dan dokumen arsip daerah.
- **Proteksi Privasi Publik:** Informasi sensitif disaring otomatis sehingga data yang tampil ke publik aman dan sesuai ketentuan keterbukaan informasi.
- **Statistik Terpadu:** Endpoint JSON statistik live (`/api/public/stats`) berbasis cache yang menyajikan capaian aset tanah, kendaraan, dan arsip secara terpadu.

---

## 11. 🗺️ Peta Rute Aplikasi (Route Map)

| Modul | Metode | URI | Controller@Method | Akses | Keterangan |
|---|---|---|---|---|---|
| **Terpadu** | GET | `/` | `LandingPageController@index` | Publik | Portal Terpadu Landing Page & Unified Asset Search |
| **Terpadu** | GET | `/search/vehicles` | `LandingPageController@searchVehicles` | Publik | Endpoint AJAX Pencarian Publik Kendaraan |
| **Terpadu** | GET | `/search/land` | `LandingPageController@searchLand` | Publik | Endpoint AJAX Pencarian Publik Sertifikat Tanah |
| **Terpadu** | GET | `/search/archives` | `LandingPageController@searchArchives` | Publik | Endpoint AJAX Pencarian Publik Arsip Aset |
| **Terpadu** | GET | `/api/public/stats` | `LandingPageController@getStats` | Publik | Endpoint JSON Statistik Ringkasan Portal |
| **Terpadu** | GET | `/vehicle-search` | `LandingPageController@searchVehicles` | Publik | Alias kompatibilitas pencarian kendaraan |
| **AI** | GET | `/ai/status` | `AiAssistantController@status` | Auth | Cek status ketersediaan Google Gemini AI |
| **AI** | POST | `/ai/ask` | `AiAssistantController@ask` | Auth | Tanya-jawab asistensi data aset cerdas AI |
| **AI** | POST | `/ai/generate-summary` | `AiAssistantController@generateSummary` | Auth | Generate ringkasan cerdas data aset |
| **System** | GET | `/api/health-check` | `HealthCheckController@check` | Publik | Endpoint monitoring status & koneksi aplikasi |
| **E-RANDIS** | Resource | `/vehicles` | `Erandis\VehicleController` | Auth | CRUD Kendaraan Dinas |
| **E-RANDIS** | POST | `/vehicles/import` | `Erandis\VehicleController@import` | Auth | Eksekusi AI Smart Import Excel |
| **E-RANDIS** | GET | `/vehicles/export` | `Erandis\VehicleController@export` | Auth | Ekspor data inventaris kendaraan ke Excel |
| **E-RANDIS** | GET | `/vehicles/rekon-bpkb` | `Erandis\VehicleController@rekonBpkb` | Auth | Halaman Rekonsiliasi BPKB E-RANDIS vs eLABEL |
| **E-RANDIS** | GET | `/vehicles/check-duplicates` | `Erandis\VehicleController@checkDuplicates` | Auth | Deteksi duplikasi data kendaraan dinas |
| **E-RANDIS** | POST | `/vehicles/resolve-duplicate-vehicle` | `Erandis\VehicleController@resolveDuplicateVehicle` | Auth | Resolusi / merge plat nomor kendaraan ganda |
| **E-RANDIS** | POST | `/vehicles/resolve-duplicate-opd` | `Erandis\VehicleController@resolveDuplicateOpd` | Auth | Resolusi duplikasi instansi OPD E-RANDIS |
| **E-RANDIS** | GET | `/master-data/opd-mapping` | `MasterOpdMappingController@index` | Auth | Hub pemetaan instansi SIPAT ↔ E-RANDIS |
| **E-RANDIS** | Resource | `/vehicle-types` | `Erandis\VehicleTypeController` | Auth | CRUD Master Jenis Kendaraan Dinas |
| **E-RANDIS** | Resource | `/opds` | `OpdController` | Auth | CRUD Master OPD Kendaraan Dinas |
| **E-RANDIS** | GET | `/reports` | `Erandis\ReportController@index` | Auth | Dashboard Modul Laporan Kendaraan |
| **E-RANDIS** | GET | `/reports/preview` | `Erandis\ReportController@preview` | Auth | Pratinjau AJAX Laporan Kendaraan |
| **E-RANDIS** | GET | `/reports/export` | `Erandis\ReportController@export` | Auth | Ekspor Excel Laporan Kendaraan |
| **E-RANDIS** | GET | `/reports/print` | `Erandis\ReportController@print` | Auth | Cetak Browser Laporan Kendaraan |
| **E-RANDIS** | GET | `/reports/pdf` | `Erandis\ReportController@pdf` | Auth | Unduh PDF formal laporan mPDF |
| **E-RANDIS** | GET | `/reports/settings` | `Erandis\ReportSettingController@index` | Superadmin | Pengaturan KOP, TTD, & Ekspor Laporan |
| **SIPAT** | GET | `/sipat/aset` | `Sipat\AsetTanahController@index` | Auth | Daftar Master Aset Tanah KIB A |
| **SIPAT** | POST | `/sipat/aset/bulk-proses` | `Sipat\AsetTanahController@bulkStoreProses` | Auth | Pembaruan status proses BPN massal |
| **SIPAT** | GET | `/sipat/aset/check-duplicates` | `Sipat\AsetTanahController@checkDuplicates` | Auth | Diagnosis duplikasi data aset tanah |
| **SIPAT** | POST | `/sipat/aset/resolve-duplicate-aset` | `Sipat\AsetTanahController@resolveDuplicateAset` | Auth | Resolusi penggabungan aset tanah duplikat |
| **SIPAT** | GET | `/sipat/tanah-tak-tercatat` | `Sipat\TanahTakTercatatController@index` | Auth | Pengelolaan Tanah Belum / Tak Tercatat |
| **SIPAT** | POST/PUT | `/sipat/tanah-tak-tercatat/*` | `Sipat\TanahTakTercatatController` | Auth | CRUD Tanah Belum Tercatat & Validasi NIBAR |
| **SIPAT** | GET | `/sipat/target-pensertifikatan` | `Sipat\TargetSertifikatController@index` | Auth | Target Pensertifikatan & GIS Map |
| **SIPAT** | POST/PUT/DEL | `/sipat/target-pensertifikatan/*` | `Sipat\TargetSertifikatController` | Auth | CRUD Target Pensertifikatan (Restricted) |
| **SIPAT** | GET | `/sipat/target-pensertifikatan/export-excel` | `Sipat\TargetSertifikatController@exportExcel` | Auth | Ekspor Excel Laporan Kinerja Target |
| **SIPAT** | GET | `/sipat/target-pensertifikatan/export-pdf` | `Sipat\TargetSertifikatController@exportPdf` | Auth | Unduh PDF Resmi Capaian Target Pensertifikatan |
| **SIPAT** | GET | `/sipat/rekonsiliasi` | `Sipat\RekonsiliasiController@index` | Auth | Rekonsiliasi Aset Bersertifikat SIPAT vs eLABEL |
| **SIPAT** | GET | `/sipat/laporan` | `Sipat\LaporanController@index` | Auth | Pusat Laporan Rincian Aset KIB A (11/12 Kolom) |
| **SIPAT** | GET | `/sipat/laporan/export/xlsx` | `Sipat\LaporanController@exportXlsx` | Auth | Ekspor Excel Laporan KIB A (.xlsx) |
| **SIPAT** | GET | `/sipat/laporan/preview-pdf` | `Sipat\LaporanController@previewPdf` | Auth | Pratinjau Dokumen PDF Formal Laporan |
| **SIPAT** | GET | `/sipat/laporan/download-pdf` | `Sipat\LaporanController@downloadPdf` | Auth | Unduh Berkas PDF Resmi Laporan (mPDF A4-L) |
| **SIPAT** | GET | `/sipat/laporan/rekap-opd` | `Sipat\LaporanController@rekapOpd` | Auth | Rekapitulasi Pensertifikatan per OPD (Web) |
| **SIPAT** | GET | `/sipat/laporan/rekap-opd/export-xlsx` | `Sipat\LaporanController@exportRekapOpdXlsx` | Auth | Ekspor Excel Rekapitulasi per OPD (.xlsx) |
| **SIPAT** | GET | `/sipat/laporan/rekap-opd/download-pdf` | `Sipat\LaporanController@downloadRekapOpdPdf` | Auth | Unduh PDF Rekapitulasi per OPD (mPDF A4-L) |
| **SIPAT** | GET | `/sipat/laporan/rekap-opd/print` | `Sipat\LaporanController@printRekapOpd` | Auth | Cetak Dokumen Browser Rekapitulasi per OPD |
| **SIPAT** | GET | `/sipat/surat/skpt` | `Sipat\SuratController@skpt` | Auth | Modul Pembuatan Surat SKPT |
| **SIPAT** | GET | `/sipat/peta` | `Sipat\PetaController@index` | Auth | Peta Interaktif Sebaran Aset Spasial |
| **SIPAT** | POST | `/sipat/peta/import-poligon` | `Sipat\PetaController@importPoligon` | Auth | Unggah Poligon Spasial GeoJSON/Shapefile |
| **SIPAT** | Resource | `/master-data/status-proses` | `StatusProsesController` | Auth | CRUD Master Status & Kategori BPN |
| **SIPAT** | Resource | `/master-data/opd-sipat` | `MasterSipatOpdController` | Auth | CRUD Master OPD Modul SIPAT |
| **SIPAT** | GET/POST | `/master-data/kop-surat` | `KopSettingsController` | Superadmin, Admin | Pengaturan KOP Surat Resmi & Pejabat Pemda |
| **SIPAT** | GET | `/master-data/wilayah` | `MasterDataWilayahController@index` | Auth | Master Kecamatan, Desa, Camat, Kades, Pemohon |
| **SIPAT** | GET | `/sipat/dashboard` | `Sipat\SipatDashboardController@index` | Auth | Dashboard Utama SIPAT |
| **SIPAT** | GET | `/master-data/import` | `Sipat\SipatImportController@index` | Auth | Halaman Impor Data Aset & Update Status BPN |
| **SIPAT** | GET | `/master-data/log-aktivitas` | `AuditLogsController@index` | Auth | Audit Log Riwayat Mutasi Modul SIPAT |
| **eLABEL** | GET | `/elabel/dashboard` | `Elabel\ElabelDashboardController@index` | Auth | Dashboard Utama eLABEL |
| **eLABEL** | GET | `/elabel/bpkb` | `Elabel\ElabelBpkbController@index` | Auth | Katalog Fisik BPKB |
| **eLABEL** | GET | `/elabel/bpkb-deleted` | `Elabel\ElabelBpkbDeletedController@index` | Auth | Arsip BPKB Keluar (*Soft Deleted*) & Restore |
| **eLABEL** | GET | `/elabel/bpkb-smart-extractor` | `Elabel\ElabelSmartBpkbExtractorController@index` | Auth | Halaman Smart BPKB PDF Extractor |
| **eLABEL** | GET | `/elabel/bpkb-smart-extractor/preview` | `Elabel\ElabelSmartBpkbExtractorController@previewPdf` | Auth | Pratinjau PDF lokal di tab baru |
| **eLABEL** | GET | `/elabel/boxes/{id}/label` | `Elabel\ElabelBoxController@label` | Auth | Cetak Label Barcode Box BPKB |
| **eLABEL** | GET | `/elabel/sertifikat` | `Elabel\ElabelSertifikatController@index` | Auth | Katalog Fisik Sertifikat Tanah |
| **eLABEL** | GET | `/elabel/sertifikat-boxes` | `Elabel\ElabelSertifikatBoxController@index` | Auth | Manajemen Box Fisik Sertifikat Tanah |
| **eLABEL** | GET | `/elabel/surat-penyerahan` | `Elabel\ElabelSuratPenyerahanController@index` | Auth | Katalog Berkas Surat Penyerahan Aset |
| **eLABEL** | GET | `/elabel/surat-penyerahan-boxes` | `Elabel\ElabelSuratPenyerahanBoxController@index` | Auth | Manajemen Box Berkas Surat Penyerahan |
| **eLABEL** | POST | `/elabel/sertifikat/import` | `Elabel\ElabelSertifikatController@import` | Auth | Impor Excel Berkas Sertifikat Tanah |
| **eLABEL** | GET | `/elabel/peminjaman` | `Elabel\ElabelLoanController@index` | Auth | Permohonan Peminjaman / Scan Berkas BPKB |
| **eLABEL** | Resource | `/elabel/dynamic/types` | `Elabel\Dynamic\ArchiveTypeController` | Superadmin | Form Builder & Jenis Arsip Dinamis |
| **eLABEL** | Resource | `/elabel/dynamic/boxes` | `Elabel\Dynamic\ArchiveBoxController` | Auth | Manajemen Box Fisik Universal & Barcode |
| **eLABEL** | GET | `/elabel/dynamic/boxes/{id}/label` | `Elabel\Dynamic\ArchiveBoxController@label` | Auth | Cetak Stiker Label Barcode Box Dinamis |
| **eLABEL** | Resource | `/elabel/dynamic/items` | `Elabel\Dynamic\ArchiveItemController` | Auth | Katalog & Input Berkas Arsip Dinamis |
| **eLABEL** | GET | `/elabel/dynamic/items/export` | `Elabel\Dynamic\ArchiveItemController@export` | Auth | Ekspor Excel Katalog Arsip Dinamis |
| **eLABEL** | GET | `/elabel/dynamic/items/{id}/view-pdf` | `Elabel\Dynamic\ArchiveItemController@viewPdf` | Auth | Viewer Berkas Scan PDF Arsip Dinamis |
| **eLABEL** | GET/POST | `/elabel/dynamic/loans` | `Elabel\Dynamic\ArchiveLoanController` | Auth | Layanan Peminjaman & Scan Arsip Dinamis |
| **System** | GET | `/users` | `UserController@index` | Superadmin, Admin | Manajemen Pengguna & Kredensial Akun |
| **System** | GET | `/activities` | `ActivityController@index` | Superadmin, Admin | Audit Trail Terpadu Tiga Modul |
| **System** | POST | `/settings/backups/sync-db` | `BackupController@syncDb` | Auth | Trigger background sinkronisasi DB Staging |
| **System** | GET | `/settings/backups/sync-db-status` | `BackupController@syncDbStatus` | Auth | Polling status sinkronisasi DB Staging |
| **System** | GET | `/settings/backups/sync-db-stream` | `BackupController@syncDbStream` | Auth | Real-time SSE streaming sinkronisasi DB Staging |
| **System** | POST | `/settings/backups/restore-sql` | `BackupController@restoreSql` | Auth | Unggah & restore dump database SQL |

---

## 12. 🚨 Aturan Kritis untuk Sesi AI Berikutnya

1. **Jangan Asumsikan Konteks:** Selalu gunakan `view_file` untuk memeriksa kode terkini sebelum melakukan modifikasi atau perbaikan bug.
2. **Kepatuhan Standar Desain:** Peningkatan visual kustom wajib diletakkan di `resources/sass/components/_vanilla-touches.scss`. Patuhi token warna formal instansi pemerintah (Navy, Putih, Slate Gray).
3. **Bahasa Indonesia Baku & Profesional:** Seluruh interaksi UI, judul tabel, label formulir, pesan validasi, notifikasi SweetAlert2, dan anotasi kode wajib menggunakan Bahasa Indonesia yang formal dan konsisten.
4. **Keamanan Otorisasi `HasMiddleware`:** Seluruh *Controller* baru wajib mengimplementasikan interface `HasMiddleware` dengan sintaks standar Laravel 12 (`new Middleware(...)`).
5. **No Destructive DB Operations:** Dilarang keras menyarankan *Soft Deletes* jika tidak ada pada skema tabel asli. Hormati arsitektur foreign key `ON DELETE SET NULL` pada tabel audit dan `CASCADE` pada relasi instansi pengguna. Jangan pernah mengeksekusi `migrate:fresh` atau `migrate:reset`.
6. **Wajib Memperbarui Dokumentasi (.md):** Setiap kali ada penambahan fitur, perubahan skema database/migrasi, penambahan rute, atau refaktorisasi arsitektur, agen AI **WAJIB** langsung memperbarui dokumen [`AI_HANDOVER.md`](file:///home/arifhyde98/Projek/Aset_terpadu/AI_HANDOVER.md), [`PROJECT_MASTER.md`](file:///home/arifhyde98/Projek/Aset_terpadu/PROJECT_MASTER.md), serta file spesifikasi fitur terkait sebelum mengakhiri sesi kerja.
