# Rencana Refactoring Front-End SIPAT Terpadu (Anti-AI Slop & Clean UI)

> **For Hermes / Developer:** Gunakan pedoman skill `claude-design` dan sistem token dari `popular-web-designs` (Linear/Vercel) untuk mengeksekusi refactoring ini secara bertahap.

**Goal:** Menghilangkan elemen visual "AI Slop" (aurora blobs, gradient warna-warni berlebihan, chips dekoratif klise) pada portal SIPAT Terpadu dan menggantinya dengan antarmuka yang bersih, berbobot, institusional (khas portal aset pemda), serta fokus pada pencarian data.

**Arsitektur & Konsep:**
- **Surface Classification:**
  - Halaman Landing: Mengubah orientasi dari *Decide/Marketing flashy* menjadi **Search / Gateway Portal** (cepat, fokus, minim distraksi).
  - Halaman Admin/Tabel: Mengokohkan orientasi **Operate / Monitor** (densitas data tinggi, border halus, kontras tegas).
- **Design Tokens:**
  - Mengadopsi palet netral (Slate / Zinc) dengan 1 warna aksen primer (Navy Blue `#1E3A8A` / `#2563EB`) dan aksen status fungsional (Emerald `#059669` untuk terverifikasi/baik, Amber `#D97706` untuk rekonsiliasi, Rose `#DC2626` untuk rusak/hilang).
  - Menghapus efek `aurora-blob`, radial gradient mencolok, dan `backdrop-filter` yang tidak perlu.

---

### Task 1: Bersihkan Hero Section dari Efek Aurora & Chip Klise

**Objective:** Menghapus elemen aurora orbs dan chip dekoratif di `landing/components/hero.blade.php`.

**Files:**
- Modify: `resources/views/landing/components/hero.blade.php`

**Step 1: Hapus Aurora Orbs & Sederhanakan Komposisi**
Hapus div:
```html
<div class="aurora-blob aurora-blob-1"></div>
<div class="aurora-blob aurora-blob-2"></div>
<div class="aurora-blob aurora-blob-3"></div>
```
Ganti background hero di SCSS dengan background solid institusional atau pola grid halus bergaris tipis (*subtle technical grid*).

**Step 2: Re-layout Modul Quick Link**
Ganti 3 "module-chip" warna-warni yang bertumpuk di tengah menjadi navigasi kategori pencarian yang terintegrasi langsung dengan kotak pencarian utama (tab switcher).

**Step 3: Verifikasi Tampilan**
Buka landing page di browser lokal dan verifikasi tidak ada layout patah atau ornamen bayangan liar.

---

### Task 2: Standardisasi Palet Warna & Styling SCSS Landing Page

**Objective:** Menghapus multi-color gradients di `_landing.scss` dan menerapkan sistem token warna konsisten.

**Files:**
- Modify: `resources/css/pages/_landing.scss`

**Langkah:**
1. Hapus variabel gradient pelangi:
   - `--landing-blue-cyan`
   - `--landing-purple-indigo`
   - `--landing-amber-orange`
2. Ganti dengan solid semantic tokens:
   - `--color-surface: #FFFFFF;`
   - `--color-surface-subtle: #F8FAFC;`
   - `--color-border: #E2E8F0;`
   - `--color-text-main: #0F172A;`
   - `--color-text-muted: #64748B;`
   - `--color-primary: #1E3A8A;` (Deep Navy)
3. Kurangi border-radius ekstrem (ubah dari rounded pill/lingkaran besar ke standard `rounded-lg` 8px–10px khas sistem institusi modern).

---

### Task 3: Optimasi Unified Asset Search sebagai Fokus Utama

**Objective:** Menjadikan form pencarian aset sebagai komponen paling menonjol dan cepat diakses.

**Files:**
- Modify: `resources/views/landing/components/unified-search.blade.php`

**Langkah:**
1. Desain input bar pencarian dengan gaya *Command Bar* yang tegas (mirip Raycast/Linear):
   - Input field tinggi 48px–52px dengan border 1px solid yang presisi.
   - Tab pemilihan filter (Semua, Kendaraan, Tanah, Arsip) dibuat menyatu di dalam header card pencarian, bukan tombol melayang terpisah.
   - Shortcut keyboard instan (misal tekan `/` untuk langsung fokus ke kolom cari).
2. Sederhanakan card preview hasil pencarian agar mudah di-scan secara cepat oleh ASN/publik.

---

### Task 4: Harmonisasikan Halaman Admin & Rekonsiliasi (Operate Surface)

**Objective:** Memastikan halaman admin kendaraan (`vehicles/index.blade.php`) dan tabel data memiliki visual yang senada dengan landing page baru.

**Files:**
- Modify: `resources/views/vehicles/index.blade.php`
- Modify: `resources/css/components/_stats.scss`

**Langkah:**
1. Ubah kartu statistik (`x-stat-card`) yang memakai `bg-gradient-*` radial mencolok menjadi kartu clean border-subtle:
   - Background putih / light surface (`#FFFFFF`).
   - Angka metrik tebal berwarna gelap.
   - Indikator warna kondisi (Baik, Rusak, Hilang) diletakkan sebagai strip aksen vertikal tipis atau badge minimalis, bukan mewarnai seluruh kartu.
2. Pastikan tabel data kendaraan tetap padat (*dense layout*) dengan font tabular/monospace untuk nomor rangka, nomor mesin, dan nomor polisi agar angka sejajar rapi.

---

### Verifikasi Akhir
1. Compile assets: `npm run build` di terminal proyek.
2. Render screenshot via Google Chrome Headless untuk memastikan:
   - Tidak ada lagi glow/aurora ungu/cyan yang mengganggu.
   - Tampilan terlihat berwibawa, bersih, dan profesional setara portal instansi modern.
