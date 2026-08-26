# Rencana Perbaikan & Peningkatan Smart BPKB PDF Folder Scanner
**Tanggal:** 26 Agustus 2026  
**Proyek:** staging_SIPAT  
**Status:** Menunggu Eksekusi

---

## File yang Akan Diubah

| File | Aksi |
|------|------|
| `app/Http/Controllers/Elabel/ElabelSmartBpkbExtractorController.php` | MODIFY |
| `resources/views/elabel/bpkb/smart_extractor.blade.php` | MODIFY |
| `routes/elabel.php` | MODIFY (tambah 1 route) |

---

## A. Perbaikan Bug (B1–B4)

### B1 — Format Rename File Tidak Konsisten
**Masalah:** Smart Extractor menggunakan `cleanString()` → huruf BESAR (`DN1234AB_2024_BOX2024R4.pdf`), sedangkan modul utama `ElabelBpkbController` menggunakan `filenameToken()` → huruf kecil (`dn1234ab_2024_box2024r4.pdf`).

**Solusi:** Tambahkan method `filenameToken()` yang identik dengan modul utama, dan gunakan di `execute()`.

```php
// TAMBAH method baru di controller
private function filenameToken(string $value): string
{
    $value = strtolower(trim($value));
    return preg_replace('/[^a-z0-9]+/', '', $value) ?: '';
}
```

```diff
// UBAH di method execute(), baris ~298-300
- $cleanPlate = $this->cleanString($bpkb->plate_number);
- $cleanBox = $this->cleanString($boxCode);
+ $cleanPlate = $this->filenameToken($bpkb->plate_number);
+ $cleanBox = $this->filenameToken($boxCode);
```

---

### B2 — Path Storage Tidak Konsisten
**Masalah:** Smart Extractor menyimpan ke `elabel/bpkb/mobil/` dan `elabel/bpkb/motor/`, tapi modul utama menyimpan ke `elabel/bpkb/` (tanpa subfolder jenis).

**Solusi:** Ikuti pola modul utama → semua ke `elabel/bpkb/`.

```diff
// UBAH di method execute(), baris ~296, 304, 308
- $subFolder = strtoupper($bpkb->vehicle_type) === 'R2' ? 'motor' : 'mobil';
  $cleanPlate = $this->filenameToken($bpkb->plate_number);
  ...
- $targetStoragePath = "elabel/bpkb/{$subFolder}/{$newFilename}";
+ $targetStoragePath = "elabel/bpkb/{$newFilename}";
  ...
- $targetStoragePath = "elabel/bpkb/{$subFolder}/{$cleanPlate}_{$year}_{$cleanBox}_{$counter}.pdf";
+ $targetStoragePath = "elabel/bpkb/{$cleanPlate}_{$year}_{$cleanBox}_{$counter}.pdf";
```

> Catatan: variabel `$subFolder` tetap dipakai di audit log untuk kejelasan, hanya path fisik yang disamakan.

---

### B3 — `copy()` Tanpa Pengecekan Error
**Masalah:** `copy($filePath, $destFullPath)` bisa gagal tanpa terdeteksi (permission denied, file hilang), tapi DB tetap terupdate.

**Solusi:** Tambahkan pengecekan hasil copy.

```diff
// UBAH di method execute(), baris ~319
- copy($filePath, $destFullPath);
+ $copySuccess = @copy($filePath, $destFullPath);
+ if (!$copySuccess || !file_exists($destFullPath)) {
+     $failedCount++;
+     continue;
+ }
```

---

### B4 — Pencarian `.pdf` dan `.PDF` Tidak Digabungkan
**Masalah:** Jika folder berisi campuran `.pdf` dan `.PDF`, yang `.PDF` diabaikan saat `.pdf` sudah ditemukan.

**Solusi:** Gabungkan keduanya.

```diff
// UBAH di method scan(), baris ~89-92
- $allPdfPaths = $this->rglob(rtrim($folderPath, '/') . '/*.pdf');
- if (empty($allPdfPaths)) {
-     $allPdfPaths = $this->rglob(rtrim($folderPath, '/') . '/*.PDF');
- }
+ $allPdfPaths = array_unique(array_merge(
+     $this->rglob(rtrim($folderPath, '/') . '/*.pdf'),
+     $this->rglob(rtrim($folderPath, '/') . '/*.PDF')
+ ));
```

---

## B. Fitur Baru (S1–S6)

### S1 — Timer Elapsed Saat Loading
**Lokasi:** `smart_extractor.blade.php`

Tambahkan timer detik berjalan saat proses scan berlangsung:

```html
<!-- Tambah di dalam div#scanLoadingState -->
<p class="text-muted small mt-1" id="scanElapsedTimer">Waktu berjalan: 0 detik</p>
```

```javascript
// JavaScript: mulai timer saat scan, hentikan saat selesai
let scanTimerInterval = null;
let scanStartTime = null;

function startScanTimer() {
    scanStartTime = Date.now();
    scanTimerInterval = setInterval(() => {
        const elapsed = Math.floor((Date.now() - scanStartTime) / 1000);
        document.getElementById('scanElapsedTimer').innerText = `Waktu berjalan: ${elapsed} detik`;
    }, 1000);
}

function stopScanTimer() {
    if (scanTimerInterval) clearInterval(scanTimerInterval);
}
```

---

### S2 — Checkbox Selektif pada Tabel VALID
**Lokasi:** `smart_extractor.blade.php`

- Tambah kolom checkbox di header & setiap baris tabel (hanya tab VALID).
- Tambah "Select All" checkbox.
- Tombol "Simpan" hanya mengirim item yang dicentang.

```html
<!-- Header tabel, tambah kolom pertama -->
<th class="py-3 text-center" style="width: 40px;" id="thCheckboxCol">
    <input type="checkbox" id="selectAllValid" checked>
</th>
```

```javascript
// Per baris (hanya di tab valid):
if (tabName === 'valid') {
    checkboxHtml = `<td class="text-center"><input type="checkbox" class="valid-item-check" data-index="${idx}" checked></td>`;
}

// Event listener Select All:
document.getElementById('selectAllValid').addEventListener('change', function() {
    document.querySelectorAll('.valid-item-check').forEach(cb => cb.checked = this.checked);
    updateExecuteButtonCount();
});

// Kirim hanya yang dicentang:
const checkedIndexes = [...document.querySelectorAll('.valid-item-check:checked')]
    .map(cb => parseInt(cb.dataset.index));
const selectedItems = checkedIndexes.map(i => currentAuditData.results.valid[i]);
```

---

### S3 — Preview PDF di Tab Baru
**Lokasi:** Controller + Route + Blade

#### Controller (tambah method):
```php
public function previewPdf(Request $request)
{
    $filePath = $request->query('path', '');
    if (!file_exists($filePath) || !is_file($filePath) 
        || strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) !== 'pdf') {
        abort(404, 'File PDF tidak ditemukan.');
    }
    return response()->file($filePath, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"',
    ]);
}
```

#### Route (tambah di `routes/elabel.php`):
```php
Route::get('bpkb-smart-extractor/preview', [ElabelSmartBpkbExtractorController::class, 'previewPdf'])
    ->name('bpkb.smart-extractor.preview');
```

#### Blade (tambah tombol di setiap baris tabel):
```javascript
// Tombol preview di kolom aksi:
const previewUrl = `/elabel/bpkb-smart-extractor/preview?path=${encodeURIComponent(item.file_path)}`;
actionHtml = `<a href="${previewUrl}" target="_blank" class="btn btn-sm btn-outline-primary" title="Lihat PDF"><i class="bi bi-eye"></i></a>`;
```

---

### S4 — Export Hasil Audit ke CSV
**Lokasi:** `smart_extractor.blade.php`

Tambahkan tombol "📥 Export Audit" di panel hasil, dan fungsi JavaScript:

```javascript
function exportAuditToCSV() {
    let csv = 'No,Status,Nama File,Plat Nomor,Kategori,Box,Ukuran,Keterangan\n';
    let no = 1;
    ['valid','duplicate','exists','unmatched'].forEach(tab => {
        (currentAuditData.results[tab] || []).forEach(item => {
            csv += `${no++},"${item.status}","${item.filename}","${item.extracted_plate || ''}","${item.vehicle_label || ''}","${item.box_code || ''}","${item.file_size || ''}","${item.reason || ''}"\n`;
        });
    });
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `audit_bpkb_scanner_${new Date().toISOString().slice(0,10)}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}
```

---

### S5 — Dukungan Nopol Multi-Prefix (Extensible)
**Lokasi:** Controller, method `parseBpkbText()`

Ubah regex dari hardcode `DN` menjadi daftar prefix yang mudah diperluas:

```diff
- if (preg_match('/DN\s*(\d{1,4})\s*([A-Z]{1,3})/i', $pdfText, $m)) {
-     $plate = "DN " . trim($m[1]) . " " . strtoupper(trim($m[2]));
- }
+ // Daftar prefix Nopol — tambahkan sesuai kebutuhan
+ $prefixes = 'DN|DD|DW|DA|DB|DC|DL|DM|DT';
+ if (preg_match('/(' . $prefixes . ')\s*(\d{1,4})\s*([A-Z]{1,3})/i', $pdfText, $m)) {
+     $plate = strtoupper(trim($m[1])) . " " . trim($m[2]) . " " . strtoupper(trim($m[3]));
+ }
```

> Catatan: Saat ini difokuskan pada prefix Sulawesi (DN, DD, DW, DA, DB, DC, DL, DM, DT). Bisa ditambah prefix lain kapan saja.

---

### S6 — Tombol Reset / Batal
**Lokasi:** `smart_extractor.blade.php`

Tambahkan tombol di samping "Simpan Penautan":

```html
<button type="button" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-bold" id="btnResetAudit">
    <i class="bi bi-arrow-counterclockwise"></i> Reset Hasil
</button>
```

```javascript
document.getElementById('btnResetAudit').addEventListener('click', function() {
    currentAuditData = null;
    document.getElementById('auditResultContainer').classList.add('d-none');
    document.getElementById('auditTableBody').innerHTML = '';
});
```

---

## Urutan Eksekusi yang Disarankan

1. **B1 + B2 + B3 + B4** → Perbaiki semua bug di Controller (1 kali edit)
2. **S3 + S5** → Tambah method `previewPdf()` dan ubah regex di Controller
3. **Route** → Tambah route preview di `routes/elabel.php`
4. **S1 + S2 + S4 + S6** → Semua perubahan UI di Blade view (1 kali edit)
5. **Verifikasi** → Jalankan test script

---

## Checklist Eksekusi

- [ ] B1: Format rename file konsisten (huruf kecil, `filenameToken()`)
- [ ] B2: Path storage konsisten (`elabel/bpkb/`)
- [ ] B3: Pengecekan `copy()` gagal/berhasil
- [ ] B4: Gabungkan pencarian `.pdf` + `.PDF`
- [ ] S1: Timer elapsed saat loading
- [ ] S2: Checkbox selektif pada tabel VALID
- [ ] S3: Preview PDF di tab baru (route + method + tombol)
- [ ] S4: Export hasil audit ke CSV
- [ ] S5: Regex multi-prefix Nopol
- [ ] S6: Tombol Reset / Batal
- [ ] Verifikasi test
- [ ] Update AI_HANDOVER.md
