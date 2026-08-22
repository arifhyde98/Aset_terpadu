# 📋 ATURAN PENAMBAHAN FITUR BARU - SIPAT TERPADU

**Dokumen ini WAJIB dibaca dan diikuti sebelum menambahkan fitur baru ke platform SIPAT Terpadu.**

---

## ⚠️ PERINGATAN PENTING

**DILARANG KERAS** menambahkan fitur baru tanpa melalui checklist ini. Platform SIPAT Terpadu memiliki arsitektur kompleks dengan:
- Multi-tenancy (isolasi data antar OPD)
- Observer chain (reaksi berantai otomatis)
- Cache strategy (pembersihan cache tertarget)
- Import/Export Excel (pemetaan kolom kompleks)

**Risiko jika tidak mengikuti aturan: 60-80% kemungkinan error atau kebocoran data antar instansi.**

---

## 📊 FASE 1: PERENCANAAN & DOKUMENTASI (WAJIB)

### 1.1 Buat Dokumen Spesifikasi Fitur

Buat file: `resources/features/[nama-fitur]/requirements.md`

**Template Minimum:**
```markdown
# SPESIFIKASI FITUR: [NAMA FITUR]

## 1. Ringkasan
- **Nama Fitur**: [Nama yang jelas]
- **Tujuan**: [Masalah yang diselesaikan]
- **User Target**: [Superadmin/Admin/OPD]
- **Prioritas**: [P0/P1/P2/P3]

## 2. User Story
Sebagai [role], saya ingin [aksi], sehingga [manfaat].

## 3. Acceptance Criteria
- [ ] Kriteria 1
- [ ] Kriteria 2
- [ ] Kriteria 3

## 4. Kebutuhan Teknis
- Tabel baru: [Ya/Tidak - Sebutkan]
- Kolom baru: [Ya/Tidak - Sebutkan]
- Foreign Key: [Ya/Tidak - Sebutkan]
- Endpoint baru: [Ya/Tidak - Sebutkan]
```

### 1.2 Buat Diagram Alur (Flowchart)

Minimal buat 3 diagram:
1. **User Flow** - Bagaimana user menggunakan fitur
2. **Data Flow** - Alur data dari input sampai output
3. **System Flow** - Controller → Service → Model → Observer


### 1.3 Desain Skema Database

**WAJIB** buat ERD (Entity Relationship Diagram) jika ada perubahan database.

```sql
-- Template SQL Schema
CREATE TABLE nama_tabel (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nama_kolom VARCHAR(255) NOT NULL,
    opd_id BIGINT NULLABLE, -- ⚠️ WAJIB untuk multi-tenancy
    user_id BIGINT NULLABLE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (opd_id) REFERENCES opds(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_opd_id (opd_id),
    INDEX idx_user_id (user_id)
);
```

**Aturan Database:**
- ✅ Selalu gunakan `BIGINT` untuk ID
- ✅ Selalu tambahkan `created_at` dan `updated_at`
- ✅ Gunakan `NULLABLE` untuk foreign key (kecuali ada alasan kuat)
- ✅ Tambahkan index untuk kolom yang sering di-query
- ✅ Gunakan `ON DELETE SET NULL` untuk audit trail
- ✅ Gunakan `ON DELETE CASCADE` hanya jika yakin

---

## 🔍 FASE 2: ANALISIS DAMPAK (CRITICAL)

### 2.1 Checklist Multi-Tenancy (WAJIB)

**PERTANYAAN KRITIS:**


- [ ] **Apakah tabel baru perlu isolasi data per OPD?**
  - Jika YA → Tambahkan kolom `opd_id`
  - Jika YA → Implementasi `TenantScope` di Model
  
- [ ] **Bagaimana jika `opd_id` NULL?**
  - Sistem harus LOCK akses (fail-safe)
  - JANGAN buka akses global
  
- [ ] **Apakah OPD A bisa lihat data OPD B?**
  - Jawaban HARUS: TIDAK
  - Jika bisa → CRITICAL SECURITY BUG
  
- [ ] **Bagaimana Superadmin/Admin melihat semua data?**
  - TenantScope harus bypass untuk role ini

**Implementasi TenantScope:**
```php
// app/Models/NamaModel.php
protected static function booted(): void
{
    static::addGlobalScope(new \App\Models\Scopes\TenantScope);
}
```

**Testing Multi-Tenancy:**
```php
// WAJIB buat test ini
public function test_opd_cannot_see_other_opd_data()
{
    $opdA = Opd::factory()->create();
    $opdB = Opd::factory()->create();
    
    $userA = User::factory()->create(['opd_id' => $opdA->id, 'role' => 'opd']);
    $dataA = NamaModel::factory()->create(['opd_id' => $opdA->id]);
    $dataB = NamaModel::factory()->create(['opd_id' => $opdB->id]);
    
    $this->actingAs($userA);
    
    // User A hanya bisa lihat data A
    $this->assertTrue(NamaModel::find($dataA->id) !== null);
    $this->assertTrue(NamaModel::find($dataB->id) === null); // HARUS NULL
}
```


### 2.2 Checklist Cache Strategy (WAJIB)

**PERTANYAAN KRITIS:**

- [ ] **Apakah fitur ini mempengaruhi statistik dashboard?**
  - Jika YA → Update `VehicleService::getDashboardStats()`
  - Jika YA → Update `VehicleService::invalidateDashboardStats()`
  
- [ ] **Apakah perlu cache baru untuk fitur ini?**
  - Tentukan cache key pattern
  - Tentukan TTL (Time To Live)
  
- [ ] **Kapan cache harus dibersihkan?**
  - Saat create data baru
  - Saat update data
  - Saat delete data
  - Saat import data massal

**Template Cache Key:**
```php
// Pattern: fitur.action.{role}.{opd_id}.{filter}
$cacheKey = "peminjaman.stats.{$user->role->value}.{$user->opd_id}";
```

**Template Cache Invalidation:**
```php
// Di Observer atau Service
public function clearCache(?int $opdId = null): void
{
    // Clear global cache
    Cache::forget("fitur.stats.superadmin.global");
    Cache::forget("fitur.stats.admin.global");
    
    // Clear OPD-specific cache
    if ($opdId) {
        Cache::forget("fitur.stats.opd.{$opdId}");
    }
}
```


### 2.3 Checklist Observer Impact (WAJIB)

**PERTANYAAN KRITIS:**

- [ ] **Apakah perlu observer baru?**
  - Untuk audit log → YA
  - Untuk cache clearing → YA
  - Untuk automated workflow → Pertimbangkan
  
- [ ] **Observer apa yang terpicu saat CRUD?**
  - List semua observer yang akan terpicu
  - Cek apakah ada risiko infinite loop
  
- [ ] **Apakah perlu audit log untuk fitur ini?**
  - Jika YA → Implementasi di Observer

**Template Observer:**
```php
// app/Observers/NamaObserver.php
namespace App\Observers;

use App\Models\NamaModel;
use App\Models\Activity;

class NamaObserver
{
    public function created(NamaModel $model): void
    {
        // 1. Audit log
        Activity::create([
            'user_id' => auth()->id(),
            'description' => "Menambahkan {$model->nama}",
            'type' => 'success',
        ]);
        
        // 2. Cache invalidation
        app(\App\Services\NamaService::class)->clearCache($model->opd_id);
    }
    
    public function updated(NamaModel $model): void
    {
        Activity::create([
            'user_id' => auth()->id(),
            'description' => "Memperbarui {$model->nama}",
            'type' => 'info',
        ]);
        
        app(\App\Services\NamaService::class)->clearCache($model->opd_id);
    }
    
    public function deleted(NamaModel $model): void
    {
        Activity::create([
            'user_id' => auth()->id(),
            'description' => "Menghapus {$model->nama}",
            'type' => 'danger',
        ]);
        
        app(\App\Services\NamaService::class)->clearCache($model->opd_id);
    }
}
```

**Daftarkan Observer:**
```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    \App\Models\NamaModel::observe(\App\Observers\NamaObserver::class);
}
```


### 2.4 Checklist Import/Export Impact

**PERTANYAAN KRITIS:**

- [ ] **Apakah data bisa di-import dari Excel?**
  - Jika YA → Buat class Import
  - Jika YA → Update template Excel
  
- [ ] **Apakah data bisa di-export ke Excel?**
  - Jika YA → Buat class Export
  - Jika YA → Mapping kolom dengan benar
  
- [ ] **Apakah perlu normalisasi data saat import?**
  - Contoh: "RB" → "Rusak Berat"
  - Contoh: "ada" → "Ada"

**Template Import:**
```php
// app/Imports/NamaImport.php
namespace App\Imports;

use App\Models\NamaModel;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class NamaImport implements ToModel, WithHeadingRow, WithBatchInserts
{
    public function model(array $row)
    {
        return new NamaModel([
            'nama' => $row['nama'],
            'opd_id' => $this->getOpdId($row['nama_opd']),
            'user_id' => auth()->id(),
        ]);
    }
    
    public function batchSize(): int
    {
        return 100; // Batch insert untuk performa
    }
    
    private function getOpdId(?string $namaOpd): ?int
    {
        // Cache OPD untuk performa
        // Implementasi sesuai kebutuhan
    }
}
```


---

## 🏗️ FASE 3: IMPLEMENTASI TEKNIS

### 3.1 Struktur File yang Harus Dibuat

**Checklist File Backend:**
```
- [ ] Migration: database/migrations/YYYY_MM_DD_HHMMSS_create_nama_table.php
- [ ] Model: app/Models/NamaModel.php (dengan PHPDoc Bahasa Indonesia)
- [ ] Controller: app/Http/Controllers/NamaController.php
- [ ] FormRequest Store: app/Http/Requests/StoreNamaRequest.php
- [ ] FormRequest Update: app/Http/Requests/UpdateNamaRequest.php
- [ ] Service (opsional): app/Services/NamaService.php
- [ ] Observer (jika perlu): app/Observers/NamaObserver.php
- [ ] Enum (jika perlu): app/Enums/NamaEnum.php
```

**Checklist File Frontend:**
```
- [ ] View Index: resources/views/nama/index.blade.php
- [ ] Component (jika perlu): resources/views/components/nama-card.blade.php
- [ ] JavaScript (jika perlu): resources/js/nama.js
- [ ] SCSS (jika perlu): resources/css/_nama.scss
```

**Checklist File Testing:**
```
- [ ] Unit Test: tests/Unit/NamaModelTest.php
- [ ] Feature Test: tests/Feature/NamaControllerTest.php
- [ ] Multi-tenancy Test: tests/Feature/NamaTenancyTest.php
```

### 3.2 Migration File (WAJIB)

**Aturan Migration:**
1. Selalu buat migration baru, JANGAN edit migration lama
2. Gunakan `nullable()` untuk kolom baru di tabel existing
3. Tambahkan index untuk kolom yang sering di-query
4. Gunakan `constrained()` untuk foreign key

**Template Migration:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nama_tabel', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            
            // Multi-tenancy (WAJIB jika perlu isolasi data)
            $table->foreignId('opd_id')->nullable()->constrained()->onDelete('set null');
            $table->index('opd_id'); // Index untuk performa
            
            // Audit trail
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nama_tabel');
    }
};
```


### 3.3 Model (WAJIB)

**Aturan Model:**
1. WAJIB PHPDoc dalam Bahasa Indonesia
2. WAJIB definisikan `$fillable`
3. WAJIB definisikan `$casts` jika ada
4. WAJIB implementasi TenantScope jika perlu isolasi data
5. WAJIB definisikan relasi dengan type hint

**Template Model:**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk [Deskripsi Model].
 * 
 * @property int $id ID Utama
 * @property string $nama Nama [Entity]
 * @property string|null $deskripsi Deskripsi [Entity]
 * @property int|null $opd_id ID OPD (Relasi)
 * @property int|null $user_id ID User Pembuat
 * @property \Carbon\Carbon $created_at Tanggal Dibuat
 * @property \Carbon\Carbon $updated_at Tanggal Diperbarui
 */
class NamaModel extends Model
{
    use HasFactory;

    /**
     * Bootstrap model dan traits.
     */
    protected static function booted(): void
    {
        // Terapkan TenantScope jika perlu isolasi data per OPD
        static::addGlobalScope(new \App\Models\Scopes\TenantScope);
    }

    /**
     * Kolom yang dapat diisi secara massal.
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'nama',
        'deskripsi',
        'opd_id',
        'user_id',
    ];

    /**
     * Konversi tipe data otomatis.
     * 
     * @var array<string, string>
     */
    protected $casts = [
        // Contoh: 'is_active' => 'boolean',
    ];

    /**
     * Relasi ke model Opd.
     * 
     * @return BelongsTo
     */
    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    /**
     * Relasi ke model User.
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```


### 3.4 Controller (WAJIB)

**Aturan Controller:**
1. WAJIB implementasi `HasMiddleware` interface
2. WAJIB gunakan `new Middleware()` syntax (Laravel 12)
3. WAJIB gunakan FormRequest untuk validasi
4. WAJIB PHPDoc dalam Bahasa Indonesia
5. DILARANG validasi inline `$request->validate()`

**Template Controller:**
```php
<?php

namespace App\Http\Controllers;

use App\Models\NamaModel;
use App\Http\Requests\StoreNamaRequest;
use App\Http\Requests\UpdateNamaRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Controller untuk Manajemen [Nama Fitur].
 */
class NamaController extends Controller implements HasMiddleware
{
    /**
     * Middleware yang diterapkan pada controller ini.
     * 
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:superadmin,admin'),
        ];
    }

    /**
     * Menampilkan daftar data.
     * 
     * @return View
     */
    public function index(): View
    {
        $data = NamaModel::with(['opd', 'user'])
            ->latest()
            ->paginate(15);

        return view('nama.index', compact('data'));
    }

    /**
     * Menyimpan data baru.
     * 
     * @param StoreNamaRequest $request
     * @return RedirectResponse
     */
    public function store(StoreNamaRequest $request): RedirectResponse
    {
        NamaModel::create($request->validated());

        return redirect()->route('nama.index')
            ->with('success', 'Data berhasil ditambahkan.');
    }

    /**
     * Memperbarui data.
     * 
     * @param UpdateNamaRequest $request
     * @param NamaModel $nama
     * @return RedirectResponse
     */
    public function update(UpdateNamaRequest $request, NamaModel $nama): RedirectResponse
    {
        $nama->update($request->validated());

        return redirect()->route('nama.index')
            ->with('success', 'Data berhasil diperbarui.');
    }

    /**
     * Menghapus data.
     * 
     * @param NamaModel $nama
     * @return RedirectResponse
     */
    public function destroy(NamaModel $nama): RedirectResponse
    {
        $nama->delete();

        return redirect()->route('nama.index')
            ->with('success', 'Data berhasil dihapus.');
    }
}
```


### 3.5 FormRequest (WAJIB)

**Aturan FormRequest:**
1. WAJIB pisahkan Store dan Update request
2. WAJIB pesan validasi dalam Bahasa Indonesia
3. WAJIB gunakan `Rule::enum()` untuk enum validation
4. WAJIB method `prepareForValidation()` untuk data cleaning

**Template StoreRequest:**
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request validasi untuk menyimpan data baru.
 */
class StoreNamaRequest extends FormRequest
{
    /**
     * Menentukan apakah user diizinkan melakukan request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi yang diterapkan pada request.
     * 
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'opd_id' => ['nullable', 'exists:opds,id'],
        ];
    }

    /**
     * Pesan validasi kustom.
     * 
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama wajib diisi.',
            'nama.max' => 'Nama maksimal 255 karakter.',
            'opd_id.exists' => 'OPD tidak ditemukan.',
        ];
    }

    /**
     * Nama atribut kustom untuk pesan error.
     * 
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nama' => 'Nama',
            'deskripsi' => 'Deskripsi',
            'opd_id' => 'OPD',
        ];
    }

    /**
     * Persiapan data sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => auth()->id(),
        ]);
    }
}
```


### 3.6 Routes (WAJIB)

**Aturan Routes:**
1. Gunakan `Route::resource()` untuk CRUD standar
2. Gunakan `except(['create', 'edit', 'show'])` untuk modal-based UI
3. Middleware didefinisikan di Controller, bukan di routes

**Template Routes:**
```php
// routes/web.php

Route::middleware(['auth'])->group(function () {
    // Resource route untuk CRUD
    Route::resource('nama-fitur', NamaController::class)
         ->except(['create', 'edit', 'show']); // Modal-based UI
    
    // Custom routes (jika perlu)
    Route::post('nama-fitur/import', [NamaController::class, 'import'])
         ->name('nama-fitur.import');
    Route::get('nama-fitur/export', [NamaController::class, 'export'])
         ->name('nama-fitur.export');
});
```

---

## 🧪 FASE 4: TESTING (WAJIB)

### 4.1 Unit Tests

**WAJIB buat test untuk:**
- Model relationships
- Enum methods (jika ada)
- Service business logic
- Helper functions

**Template Unit Test:**
```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\NamaModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NamaModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function dapat_membuat_data_baru()
    {
        $data = NamaModel::factory()->create([
            'nama' => 'Test Nama',
        ]);

        $this->assertDatabaseHas('nama_tabel', [
            'nama' => 'Test Nama',
        ]);
    }

    /** @test */
    public function memiliki_relasi_ke_opd()
    {
        $data = NamaModel::factory()->create();

        $this->assertInstanceOf(\App\Models\Opd::class, $data->opd);
    }
}
```


### 4.2 Feature Tests

**WAJIB buat test untuk:**
- CRUD operations (Create, Read, Update, Delete)
- Validation rules
- Authorization (role-based)
- Import/Export (jika ada)

**Template Feature Test:**
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\NamaModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NamaControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function superadmin_dapat_melihat_halaman_index()
    {
        $user = User::factory()->create(['role' => 'superadmin']);

        $response = $this->actingAs($user)->get(route('nama.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function dapat_membuat_data_baru()
    {
        $user = User::factory()->create(['role' => 'superadmin']);

        $response = $this->actingAs($user)->post(route('nama.store'), [
            'nama' => 'Test Nama',
            'deskripsi' => 'Test Deskripsi',
        ]);

        $response->assertRedirect(route('nama.index'));
        $this->assertDatabaseHas('nama_tabel', [
            'nama' => 'Test Nama',
        ]);
    }

    /** @test */
    public function validasi_nama_wajib_diisi()
    {
        $user = User::factory()->create(['role' => 'superadmin']);

        $response = $this->actingAs($user)->post(route('nama.store'), [
            'nama' => '', // Kosong
        ]);

        $response->assertSessionHasErrors('nama');
    }
}
```


### 4.3 Multi-Tenancy Tests (CRITICAL)

**WAJIB buat test untuk:**
- Isolasi data antar OPD
- Fail-safe mechanism (opd_id null)
- Superadmin/Admin dapat melihat semua data

**Template Multi-Tenancy Test:**
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Opd;
use App\Models\NamaModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NamaTenancyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function opd_tidak_dapat_melihat_data_opd_lain()
    {
        $opdA = Opd::factory()->create(['nama' => 'OPD A']);
        $opdB = Opd::factory()->create(['nama' => 'OPD B']);
        
        $userA = User::factory()->create(['opd_id' => $opdA->id, 'role' => 'opd']);
        $dataA = NamaModel::factory()->create(['opd_id' => $opdA->id]);
        $dataB = NamaModel::factory()->create(['opd_id' => $opdB->id]);
        
        $this->actingAs($userA);
        
        // User A hanya bisa lihat data A
        $this->assertNotNull(NamaModel::find($dataA->id));
        $this->assertNull(NamaModel::find($dataB->id)); // HARUS NULL
    }

    /** @test */
    public function superadmin_dapat_melihat_semua_data()
    {
        $opdA = Opd::factory()->create();
        $opdB = Opd::factory()->create();
        
        $superadmin = User::factory()->create(['role' => 'superadmin']);
        $dataA = NamaModel::factory()->create(['opd_id' => $opdA->id]);
        $dataB = NamaModel::factory()->create(['opd_id' => $opdB->id]);
        
        $this->actingAs($superadmin);
        
        // Superadmin bisa lihat semua
        $this->assertNotNull(NamaModel::find($dataA->id));
        $this->assertNotNull(NamaModel::find($dataB->id));
    }

    /** @test */
    public function opd_dengan_opd_id_null_tidak_dapat_melihat_data()
    {
        $opd = Opd::factory()->create();
        $userNull = User::factory()->create(['opd_id' => null, 'role' => 'opd']);
        $data = NamaModel::factory()->create(['opd_id' => $opd->id]);
        
        $this->actingAs($userNull);
        
        // User dengan opd_id null tidak bisa lihat data (fail-safe)
        $this->assertNull(NamaModel::find($data->id));
    }
}
```


---

## 🚀 FASE 5: DEPLOYMENT

### 5.1 Pre-Deployment Checklist

**WAJIB sebelum deploy:**
```markdown
- [ ] Backup database produksi
- [ ] Test di environment staging
- [ ] Review semua migration files
- [ ] Review rollback strategy
- [ ] Update dokumentasi (AI_HANDOVER.md)
- [ ] Semua test lolos (Unit, Feature, Integration)
- [ ] Code review oleh senior developer
- [ ] Performance test (query < 200ms)
```

### 5.2 Deployment Steps

**Urutan deployment:**
```bash
# 1. Backup database
mysqldump -u root -p erandis > backup_$(date +%Y%m%d_%H%M%S).sql

# 2. Pull latest code
git pull origin main

# 3. Install dependencies
composer install --no-dev --optimize-autoloader

# 4. Build assets
npm run build

# 5. Run migrations (HATI-HATI!)
php artisan migrate --force

# 6. Clear cache
php artisan optimize:clear

# 7. Link storage (jika perlu)
php artisan storage:link

# 8. Restart queue workers (jika ada)
php artisan queue:restart
```

### 5.3 Post-Deployment Checklist

**WAJIB setelah deploy:**
```markdown
- [ ] Monitor error logs (storage/logs/laravel.log)
- [ ] Monitor performance (response time)
- [ ] Verifikasi multi-tenancy (test dengan akun OPD)
- [ ] User acceptance testing
- [ ] Verifikasi cache berfungsi
- [ ] Verifikasi observer berfungsi
- [ ] Verifikasi import/export (jika ada)
```

### 5.4 Rollback Strategy

**Jika terjadi error:**
```bash
# 1. Rollback migration
php artisan migrate:rollback --step=1

# 2. Restore database dari backup
mysql -u root -p erandis < backup_YYYYMMDD_HHMMSS.sql

# 3. Rollback code
git revert HEAD
git push origin main

# 4. Clear cache
php artisan optimize:clear
```

---

## 📚 FASE 6: DOKUMENTASI

### 6.1 Update AI_HANDOVER.md

**WAJIB update bagian:**
1. Skema Database (jika ada tabel/kolom baru)
2. Peta Fitur (tambahkan fitur baru)
3. Peta Rute (tambahkan rute baru)
4. Existing Features (update status)

### 6.2 Update PROJECT_MASTER.md

**WAJIB update bagian:**
1. Existing Features (tambahkan fitur baru)
2. Known Problems (jika ada)
3. Roadmap (jika perlu)

### 6.3 Buat Dokumentasi Fitur

**Buat file:** `docs/FITUR_[NAMA].md`

**Template:**
```markdown
# Dokumentasi Fitur: [Nama Fitur]

## Deskripsi
[Penjelasan fitur]

## User Guide
[Cara menggunakan fitur]

## Technical Details
- Tabel database: [Sebutkan]
- Endpoint API: [Sebutkan]
- Cache strategy: [Jelaskan]
- Multi-tenancy: [Ya/Tidak]

## Known Issues
[Jika ada]

## Future Improvements
[Jika ada]
```

---

## ⚠️ KESALAHAN UMUM YANG HARUS DIHINDARI

### 1. Langsung Coding Tanpa Dokumentasi
❌ **SALAH**: Langsung buat migration dan coding
✅ **BENAR**: Buat spesifikasi fitur dulu, analisis dampak, baru coding

### 2. Lupa Implementasi TenantScope
❌ **SALAH**: Tabel baru tanpa TenantScope
✅ **BENAR**: Selalu cek apakah perlu isolasi data per OPD

### 3. Lupa Update Cache Invalidation
❌ **SALAH**: Tambah fitur tapi lupa clear cache
✅ **BENAR**: Update `invalidateDashboardStats()` atau buat method baru

### 4. Lupa Daftarkan Observer
❌ **SALAH**: Buat Observer tapi lupa daftarkan di AppServiceProvider
✅ **BENAR**: Selalu daftarkan di `boot()` method

### 5. Migration Tidak Reversible
❌ **SALAH**: Migration tanpa `down()` method yang benar
✅ **BENAR**: Pastikan `down()` bisa rollback dengan aman

### 6. Validasi Inline di Controller
❌ **SALAH**: `$request->validate()` di Controller
✅ **BENAR**: Gunakan FormRequest terpisah

### 7. Tidak Ada Testing
❌ **SALAH**: Deploy tanpa test
✅ **BENAR**: Minimal buat Feature Test dan Multi-Tenancy Test

### 8. Pesan Error Bahasa Inggris
❌ **SALAH**: "Name is required"
✅ **BENAR**: "Nama wajib diisi"

---

## 🎯 CHECKLIST FINAL

**Sebelum merge ke main branch:**
```markdown
- [ ] Dokumentasi spesifikasi fitur lengkap
- [ ] Analisis dampak selesai (multi-tenancy, cache, observer)
- [ ] Semua file backend dibuat dengan benar
- [ ] Semua file frontend dibuat dengan benar
- [ ] PHPDoc dalam Bahasa Indonesia
- [ ] Pesan validasi dalam Bahasa Indonesia
- [ ] Unit tests lolos
- [ ] Feature tests lolos
- [ ] Multi-tenancy tests lolos
- [ ] Code review selesai
- [ ] Dokumentasi AI_HANDOVER.md diupdate
- [ ] Dokumentasi PROJECT_MASTER.md diupdate
- [ ] Backup database sudah dibuat
- [ ] Rollback strategy sudah disiapkan
```

---

## 📞 KONTAK & BANTUAN

Jika ada pertanyaan atau kesulitan dalam implementasi fitur baru:
1. Review kembali dokumen ini
2. Baca AI_HANDOVER.md untuk detail teknis
3. Baca PROJECT_MASTER.md untuk konteks bisnis
4. Konsultasi dengan senior developer

---

**Dokumen ini adalah ATURAN WAJIB yang harus diikuti untuk menjaga kualitas dan keamanan sistem E-RANDIS.**

**Terakhir diperbarui:** 16 Mei 2026
