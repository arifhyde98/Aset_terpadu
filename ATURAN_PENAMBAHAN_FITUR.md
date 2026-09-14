# 📋 ATURAN PENAMBAHAN FITUR BARU - SIPAT TERPADU
### (E-RANDIS, SIPAT, & eLABEL)

**Dokumen ini WAJIB dibaca, dipahami, dan dipatuhi oleh setiap agen AI dan pengembang sebelum merencanakan, menulis kode, atau menambahkan fitur baru ke platform SIPAT Terpadu.**

> **🔗 REFERENSI TEKNIS:**
> - Arsitektur mendalam & skema database: **[`AI_HANDOVER.md`](file:///home/arifhyde98/Projek/Aset_terpadu/AI_HANDOVER.md)** (*Single Source of Truth*).
> - Ringkasan eksekutif & status fitur: **[`PROJECT_MASTER.md`](file:///home/arifhyde98/Projek/Aset_terpadu/PROJECT_MASTER.md)**.
> - Aturan pembaruan dokumentasi otomatis: **[`.skills/auto-doc-update/SKILL.md`](file:///home/arifhyde98/Projek/Aset_terpadu/.skills/auto-doc-update/SKILL.md)**.

---

## 📑 Daftar Isi
1. [⚠️ Peringatan Penting & Prinsip Utama](#1-️-peringatan-penting--prinsip-utama)
2. [📊 FASE 1: Perencanaan & Spesifikasi Fitur](#2--fase-1-perencanaan--spesifikasi-fitur)
3. [🔍 FASE 2: Analisis Dampak Terpadu (Impact Analysis)](#3--fase-2-analisis-dampak-terpadu-impact-analysis)
4. [🏗️ FASE 3: Implementasi Teknis & Standar Backend](#4-️-fase-3-implementasi-teknis--standar-backend)
5. [🎨 FASE 4: Frontend, Design System & Standar UI/UX](#5--fase-4-frontend-design-system--standar-uiux)
6. [🧪 FASE 5: Pengujian (Testing Suite)](#6--fase-5-pengujian-testing-suite)
7. [🚀 FASE 6: Deployment & Prosedur Operasional](#7--fase-6-deployment--prosedur-operasional)
8. [📚 FASE 7: Pemutakhiran Dokumentasi Wajib](#8--fase-7-pemutakhiran-dokumentasi-wajib)
9. [⚠️ 10 Kesalahan Fatal yang Wajib Dihindari](#9-️-10-kesalahan-fatal-yang-wajib-dihindari)
10. [🎯 Checklist Final Sebelum Merge](#10--checklist-final-sebelum-merge)

---

## 1. ⚠️ Peringatan Penting & Prinsip Utama

Platform SIPAT Terpadu mengintegrasikan tiga modul utama yang saling terhubung erat:
1. **E-RANDIS** (Inventarisasi & Manajemen Kendaraan Dinas)
2. **SIPAT** (Administrasi Pertanahan, Sertifikasi BPN, & Legalitas KIB A)
3. **eLABEL** (Pengarsipan Fisik Dokumen Berharga & Universal Dynamic Archive Engine)

Platform ini memiliki karakteristik arsitektur khusus:
- **Multi-Tenancy Heterogen:** Isolasi data ketat antar unit kerja instansi (OPD) lintas 3 modul berbeda.
- **Observer Chain & Audit Trail:** Reaksi berantai otomatis antar-modul dan pencatatan diff sebelum vs sesudah (`old_data` & `new_data`).
- **Targeted Cache Invalidation:** Agregasi dashboard dan laporan di-cache secara dinamis; kesalahan pembersihan cache dapat merusak performa atau menampilkan data usang (*stale data*).
- **Integrasi Dokumen Formal:** Format cetak mPDF (A4-Landscape/Portrait) dan ekspor Excel dengan standarisasi kop surat dan pejabat resmi Pemkab Donggala.

> 🔴 **RISIKO PELANGGARAN:** Menambahkan fitur tanpa mematuhi protokol ini memiliki risiko 60-80% menyebabkan galat runtime, kebocoran data rahasia antar-instansi, atau inkonsistensi data akuntansi daerah.

---

## 2. 📊 FASE 1: Perencanaan & Spesifikasi Fitur

### 1.1 Dokumen Spesifikasi Fitur
Sebelum menulis sebaris kode pun, buat dokumen spesifikasi pada direktori:
`resources/features/[nama-fitur]/requirements.md`

**Template Minimum Spesifikasi:**
```markdown
# SPESIFIKASI FITUR: [NAMA FITUR]

## 1. Ringkasan Eksekutif
- **Nama Fitur**: [Nama yang jelas dan formal]
- **Modul Terkait**: [E-RANDIS / SIPAT / eLABEL / Terpadu / System]
- **Tujuan Bisnis**: [Masalah tata kelola aset yang diselesaikan]
- **User Target**: [Superadmin / Admin / OPD]
- **Tingkat Prioritas**: [P0 - Kritis / P1 - Tinggi / P2 - Menengah]

## 2. User Story
Sebagai [Superadmin/Admin/OPD],
Saya ingin [melakukan aksi / melihat data],
Sehingga [manfaat operasional atau akuntabilitas tercapai].

## 3. Kriteria Keberterimaan (Acceptance Criteria)
- [ ] Kriteria 1 (Fungsionalitas utama)
- [ ] Kriteria 2 (Isolasi data tenant OPD)
- [ ] Kriteria 3 (Validasi input & audit trail)
- [ ] Kriteria 4 (Format ekspor/cetak jika ada)

## 4. Analisis Kebutuhan Arsitektur
- Tabel Baru: [Ya/Tidak - sebutkan nama tabel]
- Kolom Baru: [Ya/Tidak - sebutkan nama kolom & tipe data]
- Relasi Foreign Key: [Ya/Tidak - sebutkan tabel referensi & onDelete]
- Endpoint Rute Baru: [Sebutkan metode HTTP & URI]
- Kebutuhan Cache: [Sebutkan key cache & trigger invalidation]
```

### 1.2 Diagram Alur (Flowchart)
Sertakan minimal 2 alur diagram:
1. **Alur Pengguna (User Flow):** Bagaimana pengguna bernavigasi dan berinteraksi dengan UI.
2. **Alur Sistem & Data:** Rantai eksekusi `Request → Controller → Service → Model/DB → Observer → Audit Trail → Cache Invalidation`.

---

## 3. 🔍 FASE 2: Analisis Dampak Terpadu (Impact Analysis)

### 2.1 Checklist Multi-Tenancy Lintas Modul (WAJIB)
Kenali tabel instansi yang menjadi rujukan entitas fitur baru:
- **Jika Fitur E-RANDIS:**
  - Terhubung ke tabel `opds` via kolom `opd_id`.
  - Model wajib mengimplementasikan `TenantScope`:
    ```php
    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\TenantScope);
    }
    ```
- **Jika Fitur SIPAT (Pertanahan):**
  - Terhubung ke tabel `opd` (Model `OpdSipat`) via kolom `opd_id`.
  - Pastikan query membatasi akses pengguna OPD hanya pada `auth()->user()->opd_id`.
- **Jika Fitur eLABEL (Pengarsipan Dokumen):**
  - Menggunakan kolom `sipat_opd_id` untuk berkas fisik (BPKB, Sertifikat).
  - Menggunakan `opd_id` untuk berkas dinamis (`archive_items`).
- **Jika Fitur Menjembatani Antar-Modul:**
  - Wajib memeriksa ketersediaan pemetaan instansi pada tabel `opd_mappings` (`sipat_opd_id` ↔ `erandis_opd_id`).

**Pertanyaan Uji Fail-Safe Multi-Tenancy:**
- [ ] Apakah jika pengguna memiliki `opd_id = null`, sistem otomatis mengunci akses (*fail-safe*) dan **TIDAK** membuka data secara global?
- [ ] Apakah pengguna instansi A dapat melihat data instansi B melalui manipulasi URL parameter ID? (Jawaban harus: **TIDAK BISA**).

---

### 2.2 Checklist Strategi Caching (WAJIB)
SIPAT Terpadu memanfaatkan caching terpusat untuk menjaga respon aplikasi tetap instan:
- **Statistik Dashboard Kendaraan:** `dashboard.stats.[role].[opd_id]`
- **Statistik Dashboard Pertanahan:** Dikelola oleh `SipatService`
- **Summary Laporan Kendaraan:** `reports.summary.{role}.{scope}`

**Aturan Invalidation:**
- [ ] Jika fitur memodifikasi data kendaraan atau instansi E-RANDIS:
  ```php
  app(\App\Services\Erandis\VehicleService::class)->invalidateDashboardStats();
  ```
- [ ] Jika fitur memodifikasi data tanah KIB A, status proses BPN, target sertifikat, atau wilayah:
  ```php
  app(\App\Services\Sipat\SipatService::class)->invalidateDashboardCache();
  ```
- [ ] 🔴 **DILARANG KERAS** memanggil `Cache::flush()` global karena akan menghapus cache pengaturan sistem (`setting.{key}`) dan sesi pengguna lain!

---

### 2.3 Checklist Observer Chain & Audit Trail Terpadu (WAJIB)
Setiap mutasi data penting wajib tercatat di sistem audit log terpadu (`activities` atau `elabel_activity_logs`).

**Perekaman Snapshot Nilai Sebelum vs Sesudah:**
- `created`: Simpan data baru pada atribut `new_data`.
- `deleted`: Simpan data lama pada atribut `old_data`.
- `updated`: Ekstrak selisih nilai sebelum (`$model->getOriginal()`) dan sesudah (`$model->getChanges()`), serta abaikan kolom internal `updated_at`.
- **Sanitasi Kredensial:** Wajib menghapus atribut sensitif (`password`, `plain_password`, `remember_token`) agar tidak tersimpan di log audit.
- **Helper SIPAT:** Manfaatkan helper resmi:
  ```php
  \App\Models\Activity::logSipat($keterangan, $model, $oldData, $newData);
  ```

---

### 2.4 Checklist Integritas Relasi Lintas Modul (WAJIB)
- **Integritas Luas Tanah Bersertifikat (SIPAT ↔ eLABEL):**
  - Jika sertifikat tanah telah diterbitkan di eLABEL (`elabel_sertifikat_tanah`), luas tanah pada katalog `aset_tanah` wajib terkunci mengikuti luas sertifikat fisik.
  - Dipantau otomatis oleh `AsetTanahObserver::saving()` dan `ElabelSertifikatObserver::saved()`.
- **Integritas Kepemilikan OPD Sertifikat Tanah (SIPAT ↔ eLABEL):**
  - Sertifikat tanah di tabel `elabel_sertifikat_tanah` yang memiliki NIBAR di `aset_tanah` (KIB A) **wajib 100% mengikuti OPD pemilik di Master Aset Tanah** (`aset_tanah.opd_id` & `opd.nama`).
  - Dijamin dua arah secara otomatis:
    - `AsetTanahObserver::saved()`: Menyelaraskan `sipat_opd_id` dan teks `dinas` pada `ElabelSertifikat` (via `withoutEvents`) jika NIBAR identik.
    - `ElabelSertifikatObserver::saving()`: Mengunci dan menyelaraskan `sipat_opd_id` serta `dinas` sertifikat e-Label mengikuti `AsetTanah` jika NIBAR terdaftar di KIB A.
    - Form edit sertifikat (`resources/views/elabel/sertifikat/edit.blade.php`) wajib mengunci pilihan OPD (`<input type="hidden">` + input text disabled) dengan badge indikator `Mengacu KIB A`.
  - Dilengkapi audit command:
    ```bash
    php artisan sipat:sync-opd-sertifikat {--dry-run}
    ```
- **Relasi Kanonikal NIBAR:**
  - Penautan antara data aset tanah KIB A dengan berkas fisik sertifikat e-Label **hanya boleh** menggunakan kunci kanonikal `nibar = kode_aset` (melalui relasi model `ElabelSertifikat::asetTanah()`).
  - 🔴 **DILARANG KERAS** menggunakan pencarian teks kabur (*fuzzy match* `LIKE %nama_aset%` ke nama pemilik) karena berisiko menghubungkan sertifikat ke aset yang salah (*false positive*).

---

### 2.5 Checklist Guard Dependensi Master Data (Anti-Cascade Delete)
Sebelum menambahkan aksi hapus (`destroy`) pada controller master data:
- [ ] **Master Status Proses:** Wajib memvalidasi `ProsesAset::where('id_status', $id)->count()`. Tolak penghapusan jika status masih digunakan oleh riwayat proses aset tanah.
- [ ] **Master OPD:** Wajib memvalidasi keterkaitan pada `AsetTanah::where('opd_id', $id)`, `opd_mappings`, dan `elabel_sertifikat_tanah`.
- [ ] **Master Wilayah:** Wajib memeriksa relasi ke dokumen `surat_skpt`, pejabat aktif (`Camat`, `KepalaDesa`), dan catatan aset tanah sebelum menghapus kecamatan atau desa.

---

## 4. 🏗️ FASE 3: Implementasi Teknis & Standar Backend

### 3.1 Struktur Berkas yang Wajib Disiapkan
```text
Backend:
- [ ] Migration: database/migrations/YYYY_MM_DD_HHMMSS_*.php (Idempoten)
- [ ] Model: app/Models/[NamaModel].php (Lengkap PHPDoc Indonesia & $fillable)
- [ ] Controller: app/Http/Controllers/[Namespace]/[Nama]Controller.php (HasMiddleware)
- [ ] FormRequest: app/Http/Requests/[Store/Update][Nama]Request.php
- [ ] Service (Jika logika kompleks): app/Services/[Nama]Service.php
- [ ] Observer (Jika ada audit/cache): app/Observers/[Nama]Observer.php

Frontend:
- [ ] Blade Views: resources/views/[modul]/[fitur]/...
- [ ] SCSS Kustom (Jika ada): resources/sass/components/_vanilla-touches.scss
```

---

### 3.2 Migrasi Database Idempoten (WAJIB)
Seluruh migrasi baru wajib bersifat idempoten dan kompatibel dengan PostgreSQL / Supabase:
- Selalu gunakan pengecekan `if (!Schema::hasTable('nama_tabel'))`.
- Jika menambahkan kolom ke tabel eksisting, gunakan `if (!Schema::hasColumn('nama_tabel', 'nama_kolom'))`.
- Gunakan `foreignId()->nullable()->constrained()->onDelete('set null')` untuk audit trail.

**Template Migrasi Idempoten:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('nama_tabel')) {
            Schema::create('nama_tabel', function (Blueprint $table) {
                $table->id();
                $table->string('nama');
                $table->text('keterangan')->nullable();
                
                // Multi-tenancy instansi
                $table->foreignId('opd_id')->nullable()->constrained('opds')->onDelete('set null');
                $table->index('opd_id');
                
                // Audit log user
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nama_tabel');
    }
};
```

---

### 3.3 Konvensi Controller (Standard Laravel 12 `HasMiddleware`)
Seluruh controller wajib mengimplementasikan interface `HasMiddleware` dengan sintaks statis `middleware()`:
```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContohRequest;
use App\Http\Requests\UpdateContohRequest;
use App\Models\Contoh;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Controller untuk mengelola [Nama Fitur].
 */
class ContohController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:superadmin,admin', only: ['create', 'store', 'edit', 'update', 'destroy']),
        ];
    }

    public function index(): View
    {
        $data = Contoh::latest()->paginate(15);
        return view('contoh.index', compact('data'));
    }

    public function store(StoreContohRequest $request): RedirectResponse
    {
        Contoh::create($request->validated());
        return redirect()->route('contoh.index')->with('success', 'Data berhasil ditambahkan.');
    }
}
```

---

### 3.4 Validasi Form Request Terpusat
- Pisahkan antara `StoreRequest` dan `UpdateRequest`.
- Seluruh pesan validasi (`messages()`) dan nama atribut (`attributes()`) wajib dalam **Bahasa Indonesia**.
- Kunci `opd_id` pada pengguna role OPD di `prepareForValidation()` agar parameter form tidak bisa dipalsukan (*tampered*):
```php
protected function prepareForValidation(): void
{
    if (auth()->user()?->role === \App\Enums\UserRole::OPD) {
        $this->merge([
            'opd_id' => auth()->user()->opd_id,
        ]);
    }
}
```

---

### 3.5 Pendaftaran Rute pada Berkas Modular
Daftarkan rute baru pada berkas yang sesuai di dalam folder `routes/`:
- `routes/erandis.php`: Fitur kendaraan dinas.
- `routes/sipat.php`: Fitur pertanahan dan sertifikasi tanah.
- `routes/elabel.php`: Fitur pengarsipan berkas fisik dan arsip dinamis.
- `routes/web.php`: Portal publik, asisten AI, dashboard utama, dan pengaturan sistem.

---

## 5. 🎨 FASE 4: Frontend, Design System & Standar UI/UX

### 5.1 Skema Warna & Identitas Formal
- Gunakan warna formal instansi: **Navy (`#1E40AF`)**, Putih Bersih, dan Slate Gray.
- Gunakan font **Plus Jakarta Sans** untuk teks umum, dan font **Monospace** untuk plat nomor kendaraan, NIBAR, dan kode box.
- Format mata uang selalu menggunakan format rupiah akuntansi baku (`Rp 150.000.000`).

### 5.2 Penempatan Aset CSS / SCSS (Vite)
- Seluruh berkas SCSS berada di `resources/sass/` (bukan `resources/css/`).
- Seluruh efek visual mikro, transisi cair, dan kartu elevasi wajib ditempatkan terpusat di:
  [`resources/sass/components/_vanilla-touches.scss`](file:///home/arifhyde98/Projek/Aset_terpadu/resources/sass/components/_vanilla-touches.scss)
- Gunakan utility class yang sudah ada: `.hover-elevate`, `.btn-premium-glow`, `.skeleton-shimmer`, `.modal` (animasi bouncy).

### 5.3 Standar Dialog Konfirmasi Hapus (SweetAlert2 Tunggal)
- Tombol atau form hapus menggunakan class `.delete-confirm`.
- 🔴 **DILARANG MENAMBAHKAN** inline atribut `onsubmit="return confirm(...)"` pada form yang sudah memiliki class `.delete-confirm` agar tidak muncul konfirmasi ganda di browser.

### 5.4 Standar Formulir & Tabel Laporan
- **Dilarang Auto-Submit Agresif:** Input teks pencarian dan judul manual dilarang auto-reload setiap kali tombol keyboard ditekan. Submit dilakukan via tombol Enter atau tombol "Terapkan".
- **Sticky Table Header Solid:** Beri latar belakang solid (`--bs-tertiary-bg`) pada header tabel yang diberi class `.sticky-top` agar data di bawahnya tidak tembus pandang saat digulir.

---

## 6. 🧪 FASE 5: Pengujian (Testing Suite)

### 6.1 Multi-Tenancy Feature Test (WAJIB)
Setiap penambahan fitur ber-tenant OPD wajib menyertakan pengujian isolasi:
```php
public function test_opd_tidak_dapat_melihat_data_opd_lain(): void
{
    $opdA = Opd::factory()->create();
    $opdB = Opd::factory()->create();
    
    $userA = User::factory()->create(['opd_id' => $opdA->id, 'role' => 'opd']);
    $dataA = ContohModel::factory()->create(['opd_id' => $opdA->id]);
    $dataB = ContohModel::factory()->create(['opd_id' => $opdB->id]);
    
    $this->actingAs($userA);
    
    $this->assertNotNull(ContohModel::find($dataA->id));
    $this->assertNull(ContohModel::find($dataB->id)); // Wajib null
}
```

### 6.2 Fail-Safe Test (Ketika OPD ID bernilai NULL)
Pastikan akun OPD yang tidak memiliki instansi terkunci dari data:
```php
public function test_opd_tanpa_instansi_terkunci_dari_akses(): void
{
    $userNull = User::factory()->create(['opd_id' => null, 'role' => 'opd']);
    $data = ContohModel::factory()->create();
    
    $this->actingAs($userNull);
    $this->assertNull(ContohModel::find($data->id));
}
```

---

## 7. 🚀 FASE 6: Deployment & Prosedur Operasional

### 7.1 Parameter Basis Data Produksi
- **Nama Basis Data Utama:** `db_sipat_terpadu`
- **Nama Basis Data Staging:** `db_sipat_staging`
- **Replikasi Staging:** Gunakan fitur sinkronisasi real-time via Server-Sent Events (SSE) di `/settings/backups/sync-db` atau perintah streaming `mysqldump` tanpa memblokir lalu lintas HTTP.

### 7.2 Urutan Perintah Deployment
```bash
# 1. Backup database aktif
mysqldump -u root -p db_sipat_terpadu > backup_$(date +%Y%m%d_%H%M%S).sql

# 2. Ambil update kode
git pull origin main

# 3. Dependensi PHP
composer install --no-dev --optimize-autoloader

# 4. Kompilasi Aset Frontend
npm run build

# 5. Eksekusi Migrasi Idempoten
php artisan migrate --force

# 6. Optimasi Cache Laravel
php artisan optimize
```

---

## 8. 📚 FASE 7: Pemutakhiran Dokumentasi Wajib

Sesuai aturan pada [`.skills/auto-doc-update/SKILL.md`](file:///home/arifhyde98/Projek/Aset_terpadu/.skills/auto-doc-update/SKILL.md), **DILARANG MENGAKHIRI SESI KERJA** sebelum memperbarui dokumentasi:

1. **[`AI_HANDOVER.md`](file:///home/arifhyde98/Projek/Aset_terpadu/AI_HANDOVER.md):**
   - Tambahkan skema tabel/kolom baru di **Bab 6 (Skema Database)**.
   - Tambahkan deskripsi service baru di **Bab 7 (Backend Architecture)**.
   - Tambahkan rincian fitur di **Bab 10 (Peta Fitur Penuh)**.
   - Tambahkan seluruh URI endpoint baru di **Bab 11 (Peta Rute Aplikasi)**.
2. **[`PROJECT_MASTER.md`](file:///home/arifhyde98/Projek/Aset_terpadu/PROJECT_MASTER.md):**
   - Perbarui sub-tabel yang relevan di **Bab 8 (Existing Features)** dengan status `DONE`.
   - Tambahkan ringkasan arsitektur di **Bab 3** jika ada pola desain baru.
3. **Spesifikasi Fitur (`resources/features/[nama-fitur]/requirements.md`):**
   - Tandai seluruh kriteria keberterimaan yang telah selesai (`[x]`).

---

## 9. ⚠️ 10 Kesalahan Fatal yang Wajib Dihindari

1. ❌ **Menjalankan `migrate:fresh` atau `migrate:reset`:** Fatal, menghapus seluruh data riil aset Pemkab Donggala.
2. ❌ **Lupa Menerapkan `TenantScope` atau Isolasi `opd_id`:** Menyebabkan kebocoran data rahasia antar-OPD.
3. ❌ **Memanggil `Cache::flush()` Global:** Menghapus cache pengaturan sistem dan sesi pengguna lain.
4. ❌ **Menggunakan Pencarian Kabur (*Fuzzy Match*) pada Sertifikat Tanah:** Mengakibatkan salah menautkan sertifikat ke tanah milik dinas lain.
5. ❌ **Menghapus Master Data Tanpa Guard Dependensi:** Memicu *cascade delete* yang melenyapkan ratusan histori proses BPN atau SKPT.
6. ❌ **Validasi Inline `$request->validate()` di Controller:** Kode menjadi kotor dan melanggar standar `FormRequest` terpusat.
7. ❌ **Inline `onsubmit="return confirm(...)"` di Blade:** Memunculkan konfirmasi dialog ganda yang merusak estetika UI.
8. ❌ **Meletakkan SCSS di `resources/css/`:** Menghindari kegagalan kompilasi bundle Vite yang berpusat di `resources/sass/`.
9. ❌ **Menyimpan Kredensial Terbuka di Audit Log:** Membocorkan password pengguna ke tabel log aktivitas.
10. ❌ **Menunda Pembaruan Berkas Dokumentasi (.md):** Menyebabkan agen AI atau developer berikutnya kehilangan konteks arsitektur dan mengulangi kesalahan fatal.

---

## 10. 🎯 Checklist Final Sebelum Merge

Sebelum kode di-merge ke branch `main`:
- [ ] Dokumen spesifikasi fitur (`requirements.md`) telah lengkap dan disetujui.
- [ ] Migrasi bersifat idempoten (`if (!Schema::hasTable(...))`).
- [ ] Multi-tenancy telah diuji dengan akun OPD dan terbukti terisolasi.
- [ ] Audit trail diff (`old_data` vs `new_data`) telah terpasang dan kredensial disanitasi.
- [ ] Cache invalidation terarah telah terintegrasi di Service/Observer.
- [ ] Controller menggunakan interface `HasMiddleware` standar Laravel 12.
- [ ] FormRequest terpisah dibuat dengan pesan Bahasa Indonesia yang baku.
- [ ] Tampilan UI mematuhi palet Navy/Slate Gray, responsive, dan modal SweetAlert2 tunggal.
- [ ] Berkas [`AI_HANDOVER.md`](file:///home/arifhyde98/Projek/Aset_terpadu/AI_HANDOVER.md) telah diperbarui (termasuk tabel rute dan skema database).
- [ ] Berkas [`PROJECT_MASTER.md`](file:///home/arifhyde98/Projek/Aset_terpadu/PROJECT_MASTER.md) telah diperbarui pada tabel fitur aktif.
- [ ] Bundle aset telah dikompilasi bersih (`npm run build`).

---

**Dokumen ini adalah ATURAN WAJIB yang mengikat untuk seluruh pengembangan platform SIPAT Terpadu.**
