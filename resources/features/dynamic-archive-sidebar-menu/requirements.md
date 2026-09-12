# SPESIFIKASI FITUR: MENU OTOMATIS SIDEBAR ARSIP DINAMIS (OPSI A)

## 1. Ringkasan Eksekutif
- **Nama Fitur**: Menu Otomatis Sidebar Arsip Dinamis (Opsi A: Daftar Terbuka Langsung)
- **Modul Terkait**: eLABEL (Universal Dynamic Archive Engine) & Core Navigation
- **Tujuan Bisnis**: Memudahkan operator dan admin dalam mengakses berkas arsip dinamis secara instan langsung dari sidebar navigasi tanpa harus melalui menu terpusat Katalog Berkas.
- **User Target**: Superadmin, Admin, OPD
- **Tingkat Prioritas**: P1 - Tinggi

## 2. User Story
Sebagai Operator OPD maupun Admin BPKAD,
Saya ingin melihat jenis arsip dinamis yang aktif langsung tertera di sidebar navigasi beserta jumlah berkasnya,
Sehingga saya dapat bernavigasi dan mengelola dokumen kategori tertentu secara cepat dengan satu kali klik.

## 3. Kriteria Keberterimaan (Acceptance Criteria)
- [x] Setiap kategori/jenis arsip dinamis baru yang aktif (`is_active = true`) di tabel `archive_types` otomatis membuat **grup menu & submenu mandiri** di sidebar (sejajar dan memiliki pola visual identik dengan *DOKUMEN BPKB* dan *SERTIFIKAT TANAH*).
- [x] Header grup menu mandiri menampilkan icon kustom pengguna (`icon`), warna tema badge (`warna_badge`), dan teks nama kategori (misal: *SK PENGHAPUSAN*).
- [x] Submenu di dalam grup kategori mandiri terdiri dari:
  - `Katalog [Kode/Berkas]` -> filter langsung ke `/elabel/dynamic/items?type_id={id}`
  - `Box [Kode/Arsip]` -> filter langsung ke `/elabel/dynamic/boxes?type_id={id}`
- [x] Menu **ARSIP DINAMIS** difokuskan khusus untuk pengaturan sistem terpusat:
  - `Master Kategori & Form` (`route('elabel.dynamic.types.index')`)
  - `Semua Berkas` (`route('elabel.dynamic.items.index')`)
  - `Manajemen Box` (`route('elabel.dynamic.boxes.index')`)
  - `Layanan Peminjaman` (`route('elabel.dynamic.loans.index')`)
- [x] Menu memiliki indikator aktif (`class="active"`) dan header otomatis terbuka (*expanded*) jika pengguna sedang membuka halaman kategori terkait.
- [x] Menggunakan caching terversi (`sidebar_dynamic_archive_types_{version}_{scope}`) ber-TTL 1 jam untuk performa instan tanpa beban query database setiap request.
- [x] Invalidation cache otomatis dipicu oleh `ArchiveTypeObserver` dan `ArchiveItemObserver` tanpa pernah memanggil `Cache::flush()` global.
- [x] Drawer mobile (`bottom-nav.blade.php`) juga terintegrasi menampilkan kategori mandiri dan seksi pengaturan.

## 4. Analisis Kebutuhan Arsitektur
- Tabel Baru: Tidak (Menggunakan tabel `archive_types` & `archive_items` yang sudah ada).
- Kolom Baru: Tidak.
- Relasi: Menggunakan relasi kanonikal `ArchiveType::items()`.
- Endpoint: Mengarah ke `route('elabel.dynamic.items.index', ['type_id' => $type->id])`.
- Kebutuhan Cache: Cache key `sidebar_dynamic_archive_types_{version}_{scope}`, invalidasi via `Cache::increment('sidebar_dynamic_archive_version')`.
