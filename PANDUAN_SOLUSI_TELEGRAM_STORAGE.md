# Panduan Implementasi: Pemisahan Kolom Local Storage & Telegram Cloud (Solusi 2)

Dokumen ini berisi panduan lengkap untuk merapikan integrasi **Telegram Cloud Storage & Local Dual-Redundancy** pada aplikasi SIPAT Terpadu agar pengelolaan dokumen (Create, Read, Update, Delete) menjadi 100% aman, presisi, dan tetap mendukung kemudahan pengembangan lintas-PC tanpa perlu SSH/SCP.

---

## 1. Ringkasan Masalah Utama

Saat ini, setelah berkas diunggah ke Telegram, kolom `pdf_path` di database diubah menjadi string `tg:<file_id>`. Hal ini menimbulkan beberapa kendala:
1. **Penghapusan File Gagal Saat Update & Delete:** Laravel mengecek `Storage::exists('tg:xxx')` yang selalu bernilai `false`, sehingga berkas fisik lokal lama tertinggal di harddisk dan menjadi sampah.
2. **Risiko Tabrakan Nama (*Fuzzy Match Collision*):** Saat membuka PDF di lokal, sistem harus menebak nama file dengan mencocokkan potongan string plat/sertifikat, yang berisiko salah membuka file milik aset lain jika ada kesamaan karakter.
3. **Penyebab:** Kolom `pdf_path` merangkap dua fungsi (menyimpan path lokal sekaligus ID Telegram).

---

## 2. Solusi 2 (Pisahkan Kolom Database)

Struktur yang benar adalah memisahkan pencatatan path:
* **`pdf_path`**: Menyimpan path fisik asli di harddisk lokal (misal: `elabel/bpkb/kt1234aa_2024_BOX01.pdf`).
* **`telegram_file_id`**: Menyimpan ID berkas di cloud Telegram (misal: `BAACAg...`).

---

## 3. Langkah-Langkah Eksekusi Mandiri

### Langkah 1: Buat & Jalankan Migrasi Database

Jalankan perintah di terminal:
```bash
php artisan make:migration add_telegram_file_id_to_elabel_tables
```

Buka file migrasi yang baru dibuat di `database/migrations/xxxx_xx_xx_add_telegram_file_id_to_elabel_tables.php` dan isi dengan kode berikut:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('elabel_bpkb', function (Blueprint $table) {
            $table->string('telegram_file_id')->nullable()->after('pdf_path');
        });

        Schema::table('elabel_sertifikat', function (Blueprint $table) {
            $table->string('telegram_file_id')->nullable()->after('pdf_path');
        });

        Schema::table('elabel_bpkb_delete', function (Blueprint $table) {
            $table->string('telegram_file_id')->nullable()->after('pdf_path');
            $table->string('telegram_support_doc_id')->nullable()->after('support_doc_path');
        });
    }

    public function down(): void
    {
        Schema::table('elabel_bpkb', function (Blueprint $table) {
            $table->dropColumn('telegram_file_id');
        });

        Schema::table('elabel_sertifikat', function (Blueprint $table) {
            $table->dropColumn('telegram_file_id');
        });

        Schema::table('elabel_bpkb_delete', function (Blueprint $table) {
            $table->dropColumn(['telegram_file_id', 'telegram_support_doc_id']);
        });
    }
};
```

Jalankan migrasi:
```bash
php artisan migrate
```

---

### Langkah 2: Migrasi Data Eksisting (Satu Kali Eksekusi)

Jika di database Anda saat ini sudah ada data dengan `pdf_path` berformat `tg:...`, pindahkan nilainya ke `telegram_file_id` dengan menjalankan via `php artisan tinker`:

```php
// Jalankan di "php artisan tinker":

// 1. Pindahkan data BPKB
\App\Models\Elabel\ElabelBpkb::where('pdf_path', 'LIKE', 'tg:%')->get()->each(function ($item) {
    $item->update([
        'telegram_file_id' => str_replace('tg:', '', $item->pdf_path),
    ]);
});

// 2. Pindahkan data Sertifikat
\App\Models\Elabel\ElabelSertifikat::where('pdf_path', 'LIKE', 'tg:%')->get()->each(function ($item) {
    $item->update([
        'telegram_file_id' => str_replace('tg:', '', $item->pdf_path),
    ]);
});

// 3. Pindahkan data BPKB Keluar (Delete)
\App\Models\Elabel\ElabelBpkbDelete::all()->each(function ($item) {
    $updates = [];
    if (str_starts_with($item->pdf_path ?? '', 'tg:')) {
        $updates['telegram_file_id'] = str_replace('tg:', '', $item->pdf_path);
    }
    if (str_starts_with($item->support_doc_path ?? '', 'tg:')) {
        $updates['telegram_support_doc_id'] = str_replace('tg:', '', $item->support_doc_path);
    }
    if (!empty($updates)) {
        $item->update($updates);
    }
});
```

---

### Langkah 3: Perbarui Model Eloquent (Mass Assignment)

Tambahkan field baru ke `$fillable` di model:
1. `app/Models/Elabel/ElabelBpkb.php` $\rightarrow$ tambahkan `'telegram_file_id'`
2. `app/Models/Elabel/ElabelSertifikat.php` $\rightarrow$ tambahkan `'telegram_file_id'`
3. `app/Models/Elabel/ElabelBpkbDelete.php` $\rightarrow$ tambahkan `'telegram_file_id'`, `'telegram_support_doc_id'`

---

### Langkah 4: Perbarui Controller BPKB (`ElabelBpkbController.php`)

#### A. Method `storeBpkbPdf`
Ubah agar mengembalikan array `['pdf_path' => ..., 'telegram_file_id' => ...]`:

```php
private function storeBpkbPdf($file, string $plateNumber, int $year, string $boxCode): array
{
    // 1. Simpan ke lokal harddisk
    $extension = strtolower($file->getClientOriginalExtension()) ?: 'pdf';
    $baseName = $this->filenameToken($plateNumber) . '_' . $year . '_' . strtoupper($this->filenameToken($boxCode));
    $newName = $baseName . '.' . $extension;

    $path = 'elabel/bpkb/' . $newName;
    $counter = 2;
    while (Storage::disk('public')->exists($path)) {
        $path = 'elabel/bpkb/' . $baseName . '_' . $counter . '.' . $extension;
        $counter++;
    }

    $file->storeAs('elabel/bpkb', basename($path), 'public');

    // 2. Upload salinan ke Telegram
    $telegramFileId = null;
    $tgStorage = new \App\Services\TelegramStorageService();
    if ($tgStorage->isConfigured()) {
        $caption = "📄 *SCAN BPKB {$plateNumber}*\nTahun: {$year} | Box: {$boxCode}";
        $uploaded = $tgStorage->uploadFile($file, $caption);
        if ($uploaded && !empty($uploaded['file_id'])) {
            $telegramFileId = $uploaded['file_id'];
        }
    }

    return [
        'pdf_path'         => $path,
        'telegram_file_id' => $telegramFileId,
    ];
}
```

#### B. Method `store` & `update`
Pada saat upload file baru:
```php
if ($request->hasFile('pdf') && $request->file('pdf')->isValid()) {
    // Hapus file fisik lama di harddisk jika ada
    if ($item->pdf_path && Storage::disk('public')->exists($item->pdf_path)) {
        Storage::disk('public')->delete($item->pdf_path);
    }

    $box = ElabelBox::find($boxId);
    $saved = $this->storeBpkbPdf(
        $request->file('pdf'),
        $identity['plate_number'],
        $year,
        (string) ($box->box_code ?? '')
    );

    $pdfPath = $saved['pdf_path'];
    $telegramFileId = $saved['telegram_file_id'];
}
```

#### C. Method `viewPdf`
Penyajian berkas menjadi sangat bersih:
```php
public function viewPdf(int $id)
{
    $item = ElabelBpkb::find($id);
    if (!$item || (!$item->pdf_path && !$item->telegram_file_id)) {
        return redirect()->back()->with('error', 'File PDF tidak ditemukan.');
    }

    // 1. Prioritas Utama: Buka dari Lokal (Cepat, Presisi, Tanpa Scanning Folder)
    if ($item->pdf_path && Storage::disk('public')->exists($item->pdf_path)) {
        return response()->file(storage_path('app/public/' . $item->pdf_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="bpkb-' . $id . '.pdf"',
        ]);
    }

    // 2. Prioritas Kedua: Buka dari Telegram (Untuk PC Dev hasil clone / Fallback)
    if ($item->telegram_file_id) {
        $tgStorage = new \App\Services\TelegramStorageService();
        return $tgStorage->streamToBrowserWithFallback($item->telegram_file_id, null, 'bpkb-' . $id . '.pdf');
    }

    return redirect()->back()->with('error', 'File PDF tidak tersedia di lokal maupun cloud.');
}
```

#### D. Method `destroy` (pada BPKB Keluar / `ElabelBpkbDeletedController.php`)
```php
public function destroy(int $id): RedirectResponse
{
    $item = ElabelBpkbDelete::find($id);
    if (!$item) {
        return redirect()->route('elabel.bpkb-deleted.index')->with('error', 'Data BPKB keluar tidak ditemukan.');
    }

    // Hapus fisik berkas lokal dengan aman dan tepat sasaran
    if ($item->pdf_path && Storage::disk('public')->exists($item->pdf_path)) {
        Storage::disk('public')->delete($item->pdf_path);
    }
    if ($item->support_doc_path && Storage::disk('public')->exists($item->support_doc_path)) {
        Storage::disk('public')->delete($item->support_doc_path);
    }

    $item->delete();
    ...
}
```

---

### Langkah 6: Perbarui Command `SyncStorageToTelegram.php`

Pada command sinkronisasi massal, pertahankan `pdf_path` lokal aslinya dan hanya perbarui `telegram_file_id`:

```php
// Ubah logika update pada command sync:
if ($uploaded && !empty($uploaded['file_id'])) {
    $totalBytes += filesize($fullPath);
    
    // PERBAIKAN: Hanya simpan telegram_file_id, jangan menimpa pdf_path!
    $bpkb->update(['telegram_file_id' => $uploaded['file_id']]);
    
    $syncedCount++;
    $this->info("  -> Berhasil disinkronkan ke Telegram! (Path lokal tetap utuh)");
}
```

---

## 4. Perbandingan Hasil

| Aspek | Sebelum Refactoring | Sesudah Refactoring (Solusi 2) |
| :--- | :--- | :--- |
| **Ganti File (Update)** | File lama menumpuk di harddisk | File lama langsung terhapus bersih |
| **Hapus Data (Delete)** | File fisik lokal tertinggal selamanya | File fisik langsung terhapus rapi |
| **Buka Dokumen (Lokal)** | Memindai ribuan file di folder (lambat & rawan salah file) | Langsung dibuka via path pasti (instan & 100% tepat) |
| **Buka Dokumen (PC Clone)** | Berfungsi via Telegram | **Tetap Berfungsi 100% via Telegram (Tanpa perlu SSH/SCP)** |
