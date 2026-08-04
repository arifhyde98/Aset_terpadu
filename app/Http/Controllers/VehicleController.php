<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\User;
use App\Models\VehicleType;
use App\Models\Opd;
use Illuminate\Http\Request;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Exports\VehicleExport;
use App\Exports\VehicleTemplateExport;
use App\Imports\VehicleImport;
use App\Imports\VehicleMultiSheetImport;
use App\Http\Requests\ImportVehicleRequest;
use App\Http\Requests\ExecuteSmartImportRequest;
use App\Http\Requests\ResolveDuplicateVehicleRequest;
use App\Http\Requests\ResolveDuplicateOpdRequest;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

use App\Services\VehicleService;

/**
 * Controller untuk Manajemen Data Kendaraan
 * 
 * Menangani CRUD data kendaraan, pencarian, serta fitur import/export Excel.
 */
class VehicleController extends Controller implements HasMiddleware
{
    /**
     * Mendapatkan middleware yang ditugaskan ke controller ini.
     * 
     * @return array
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth', except: ['search', 'searchLandingVehicle']),
            new Middleware('role:superadmin', only: ['truncate', 'sanitizeIdentifiers', 'sanitizeSwappedIdentifiers']),
            new Middleware('role:superadmin,admin', only: ['checkDuplicates', 'resolveDuplicateVehicle', 'resolveDuplicateOpd']),
        ];
    }

    protected $vehicleService;

    /**
     * Konstruktor Controller.
     * 
     * @param VehicleService $vehicleService
     */
    public function __construct(VehicleService $vehicleService)
    {
        $this->vehicleService = $vehicleService;
    }

    /**
     * Menampilkan daftar kendaraan dengan fitur filter dan pencarian.
     * 
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $sortBy = $request->input('sort_by');
        $sortOrder = $request->input('sort_order', 'asc');
        $allowedSorts = ['no_polisi', 'jenis', 'merk', 'tahun_pembuatan', 'pemegang', 'kondisi', 'status'];

        $isEbmd = $request->input('tab') === 'ebmd';
        $modelClass = $isEbmd ? \App\Models\EbmdVehicle::class : Vehicle::class;

        $query = $modelClass::with(['user', 'vehicleType', 'opdRelation']);

        if ($sortBy && in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        // Filter Pencarian Global
        if ($request->filled('q')) {
            $search = strtoupper(preg_replace('/\s+/', ' ', trim($request->q)));
            $query->where(function($q) use ($search) {
                $q->where('no_polisi', 'LIKE', "%{$search}%")
                  ->orWhere('nomor_register', 'LIKE', "%{$search}%")
                  ->orWhere('pemegang', 'LIKE', "%{$search}%")
                  ->orWhere('merk', 'LIKE', "%{$search}%")
                  ->orWhere('opd', 'LIKE', "%{$search}%");
            });
        }

        // Filter Berdasarkan Status Operasional
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter Berdasarkan Kondisi Fisik
        if ($request->filled('kondisi')) {
            $query->where('kondisi', $request->kondisi);
        }

        // Filter Berdasarkan Jenis Kendaraan
        if ($request->filled('jenis')) {
            $query->where(function($q) use ($request) {
                $q->whereHas('vehicleType', function($sq) use ($request) {
                    $sq->where('name', $request->jenis);
                })->orWhere('jenis', $request->jenis);
            });
        }

        $vehicles = $query->paginate(10)->withQueryString();
        
        $vehicleTypes = VehicleType::orderBy('name')->get();
        $stats = $this->vehicleService->getDashboardStats();
        
        $ebmdStats = [];
        if ($isEbmd) {
            $rawStats = \App\Models\EbmdVehicle::query()
                ->leftJoin('vehicle_types', 'ebmd_vehicles.vehicle_type_id', '=', 'vehicle_types.id')
                ->selectRaw('COALESCE(vehicle_types.name, ebmd_vehicles.jenis, "Lainnya") as jenis_kendaraan, count(*) as total')
                ->groupBy('jenis_kendaraan')
                ->pluck('total', 'jenis_kendaraan')
                ->toArray();

            $ebmdStats = [
                'Mobil' => 0,
                'Motor' => 0,
                'Lainnya' => 0
            ];

            foreach ($rawStats as $jenis => $total) {
                $jenisLower = strtolower($jenis);
                
                // Kategori Motor
                if (str_contains($jenisLower, 'motor') && !str_contains($jenisLower, 'tak bermotor') && !str_contains($jenisLower, 'khusus') || str_contains($jenisLower, 'scooter') || str_contains($jenisLower, 'roda dua')) {
                    $ebmdStats['Motor'] += $total;
                } 
                // Kategori Mobil
                elseif (str_contains($jenisLower, 'mobil') || str_contains($jenisLower, 'bus') || str_contains($jenisLower, 'pick up') || str_contains($jenisLower, 'jeep') || str_contains($jenisLower, 'truck') || str_contains($jenisLower, 'wagon') || str_contains($jenisLower, 'roda empat') || str_contains($jenisLower, 'sedan')) {
                    $ebmdStats['Mobil'] += $total;
                } 
                // Kategori Lainnya
                else {
                    $ebmdStats['Lainnya'] += $total;
                }
            }
        }

        $opds = Opd::orderBy('nama')->get();
        $statuses = $modelClass::getStatuses();
        $conditions = $modelClass::getConditions();

        $vehicleDataMap = $vehicles->getCollection()->keyBy('id')->map(function($v) {
            $data = $v->only([
                'id', 'no_polisi', 'nomor_register', 'merk', 'tipe', 'jenis', 'opd_id', 'pemegang', 'status', 'kondisi',
                'vehicle_type_id', 'tahun_pembuatan', 'warna', 'stnk_ada', 'bpkb_ada', 
                'tgl_stnk', 'tgl_perolehan', 'nilai_perolehan', 'no_mesin', 'no_rangka', 
                'keterangan', 'foto_kendaraan'
            ]);
            
            // Gunakan nama OPD terbaru dari relasi untuk konsistensi Modal
            $data['opd'] = $v->opdRelation?->nama ?? $v->opd;
            
            return $data;
        });

        return view('vehicles.index', compact('vehicles', 'stats', 'ebmdStats', 'vehicleTypes', 'opds', 'statuses', 'conditions', 'vehicleDataMap'));
    }

    /**
     * Fungsi pencarian untuk Landing Page (Akses Publik).
     * 
     * @param Request $request
     * @return View
     */
    public function search(Request $request): View
    {
        $query = $request->input('q');
        $vehicle = $this->vehicleService->findForLanding($query);

        // Statistik untuk Hero Landing Page
        $stats = $this->vehicleService->getDashboardStats();
        $total = $stats['total'];
        $activeCount = $stats['available'];
        $activePercentage = $total > 0 ? round(($activeCount / $total) * 100) : 0;

        // Ambil Pengaturan Web dalam satu kali proses (Optimasi Fase 2)
        $settings = [
            'site_name' => \App\Models\Setting::get('site_name', 'PEMERINTAH DAERAH'),
            'site_logo' => \App\Models\Setting::get('site_logo'),
            'hero_title' => \App\Models\Setting::get('hero_title', 'E-RANDIS'),
            'hero_subtitle' => \App\Models\Setting::get('hero_subtitle', 'Sistem Monitoring Kendaraan Dinas Pemerintah Daerah'),
            'hero_image' => \App\Models\Setting::get('hero_image', 'images/hero-illustration.png'),
            'hero_bg_image' => \App\Models\Setting::get('hero_bg_image', 'images/hero-illustration.png'),
        ];

        return view('welcome', compact('vehicle', 'query', 'total', 'activePercentage', 'settings'));
    }

    /**
     * Endpoint API pencarian kendaraan untuk dipanggil via AJAX di landing page.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function searchLandingVehicle(Request $request): JsonResponse
    {
        $query = $request->input('q');
        $vehicle = $this->vehicleService->findForLanding($query);

        return response()->json([
            'found' => (bool) $vehicle,
            'query' => $query,
            'vehicle' => $vehicle ? [
                'no_polisi' => $vehicle->no_polisi,
                'nama' => trim($vehicle->merk.' '.$vehicle->tipe),
                'opd' => $vehicle->opd,
                'pemegang' => $vehicle->pemegang,
                'kondisi' => \App\Enums\VehicleCondition::tryFrom($vehicle->kondisi)?->label() ?? $vehicle->kondisi,
                'status' => \App\Enums\VehicleStatus::tryFrom($vehicle->status)?->label() ?? $vehicle->status,
                'foto_kendaraan' => $vehicle->foto_kendaraan,
            ] : null,
        ]);
    }

    /**
     * Menampilkan form untuk menambah kendaraan baru.
     * 
     * @return View
     */
    public function create(): View
    {
        $users = User::all();
        $vehicleTypes = VehicleType::orderBy('name')->get();
        $statuses = Vehicle::getStatuses();
        $conditions = Vehicle::getConditions();
        $opds = Opd::orderBy('nama')->get();
        return view('vehicles.create', compact('users', 'vehicleTypes', 'statuses', 'conditions', 'opds'));
    }

    /**
     * Menyimpan data kendaraan baru ke database.
     * 
     * @param StoreVehicleRequest $request
     * @return RedirectResponse
     */
    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Format nomor polisi menggunakan Service
        $validated['no_polisi'] = $this->vehicleService->formatPlateNumber($validated['no_polisi']);

        // Handle Foto Kendaraan
        if ($request->hasFile('foto_kendaraan')) {
            $paths = [];
            foreach ($request->file('foto_kendaraan') as $image) {
                $paths[] = $image->store('vehicles', 'public');
            }
            $validated['foto_kendaraan'] = $paths;
        }

        $isEbmd = $request->input('target_table') === 'ebmd';
        $modelClass = $isEbmd ? \App\Models\EbmdVehicle::class : Vehicle::class;

        $vehicle = $modelClass::create($validated);
        
        // Invalidation terarah (Hanya dashboard stats)
        $this->vehicleService->invalidateDashboardStats(opdId: $vehicle->opd_id);

        return redirect()->route('vehicles.index', ['tab' => $request->input('target_table')])->with('success', 'Data kendaraan berhasil ditambahkan.');
    }

    /**
     * Menampilkan detail data satu kendaraan.
     * 
     * @param Vehicle $vehicle
     * @return View
     */
    public function show(Vehicle $vehicle): View
    {
        $vehicle->load(['user', 'vehicleType']);
        return view('vehicles.show', compact('vehicle'));
    }

    /**
     * Menampilkan form untuk mengedit data kendaraan.
     * 
     * @param Vehicle $vehicle
     * @return View
     */
    public function edit(Vehicle $vehicle): View
    {
        $users = User::all();
        $vehicleTypes = VehicleType::orderBy('name')->get();
        $statuses = Vehicle::getStatuses();
        $conditions = Vehicle::getConditions();
        $opds = Opd::orderBy('nama')->get();
        return view('vehicles.edit', compact('vehicle', 'users', 'vehicleTypes', 'statuses', 'conditions', 'opds'));
    }

    /**
     * Memperbarui data kendaraan di database.
     * 
     * @param UpdateVehicleRequest $request
     * @param Vehicle $vehicle
     * @return RedirectResponse
     */
    public function update(UpdateVehicleRequest $request, $id): RedirectResponse
    {
        $isEbmd = $request->input('target_table') === 'ebmd';
        $modelClass = $isEbmd ? \App\Models\EbmdVehicle::class : Vehicle::class;
        $vehicle = $modelClass::findOrFail($id);

        $validated = $request->validated();

        // Format nomor polisi menggunakan Service
        $validated['no_polisi'] = $this->vehicleService->formatPlateNumber($validated['no_polisi']);

        // Handle Foto Kendaraan (Replace All)
        if ($request->hasFile('foto_kendaraan')) {
            // Hapus foto lama
            if ($vehicle->foto_kendaraan) {
                foreach ($vehicle->foto_kendaraan as $oldPath) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            // Simpan foto baru
            $paths = [];
            foreach ($request->file('foto_kendaraan') as $image) {
                $paths[] = $image->store('vehicles', 'public');
            }
            $validated['foto_kendaraan'] = $paths;
        }

        $oldOpdId = $vehicle->opd_id;
        $vehicle->update($validated);
        
        // Invalidation terarah (Handle kasus pindah instansi)
        $this->vehicleService->invalidateDashboardStats(
            opdId: $vehicle->opd_id, 
            oldOpdId: $oldOpdId
        );

        return redirect()->route('vehicles.index', ['tab' => $request->input('target_table')])->with('success', 'Data kendaraan berhasil diperbarui.');
    }

    /**
     * Menghapus data kendaraan dari database.
     * 
     * @param Vehicle $vehicle
     * @return RedirectResponse
     */
    public function destroy(Request $request, $id): RedirectResponse
    {
        $isEbmd = $request->input('target_table') === 'ebmd';
        $modelClass = $isEbmd ? \App\Models\EbmdVehicle::class : Vehicle::class;
        $vehicle = $modelClass::findOrFail($id);

        // Hapus foto fisik
        if ($vehicle->foto_kendaraan) {
            foreach ($vehicle->foto_kendaraan as $path) {
                Storage::disk('public')->delete($path);
            }
        }
        
        $opdId = $vehicle->opd_id;
        $vehicle->delete();
        
        // Invalidation terarah
        $this->vehicleService->invalidateDashboardStats(opdId: $opdId);

        return redirect()->route('vehicles.index', ['tab' => $request->input('target_table')])->with('success', 'Data kendaraan berhasil dihapus.');
    }

    /**
     * Mengosongkan seluruh data di tabel kendaraan.
     * 
     * @return RedirectResponse
     */
    public function truncate(Request $request): RedirectResponse
    {
        $isEbmd = $request->input('target_table') === 'ebmd';

        if ($isEbmd) {
            // Kosongkan tabel e-BMD
            \App\Models\EbmdVehicle::truncate();
            $tabLabel = 'e-BMD';
        } else {
            // Kosongkan tabel Data Real beserta foto fisik
            Storage::disk('public')->deleteDirectory('vehicles');
            Vehicle::truncate();
            $tabLabel = 'Data Real';
        }
        
        // Invalidation massal seluruh OPD (Dashboard stats)
        $this->vehicleService->invalidateDashboardStats(invalidateAllOpd: true);

        return redirect()->route('vehicles.index', ['tab' => $request->input('target_table', 'real')])->with('success', "Seluruh data kendaraan {$tabLabel} berhasil dikosongkan.");
    }

    /**
     * Membersihkan massal seluruh nomor rangka dan nomor mesin kendaraan dari karakter khusus.
     * 
     * Khusus diakses oleh Superadmin.
     * 
     * @return RedirectResponse
     */
    public function sanitizeIdentifiers(): RedirectResponse
    {
        $count = $this->vehicleService->sanitizeIdentifiers();

        // Catat audit log
        \App\Models\Activity::log("Melakukan pembersihan massal karakter khusus nomor mesin dan nomor rangka kendaraan [Jumlah data: {$count}]", 'info');

        return redirect()->route('vehicles.index')->with('success', "Pembersihan berhasil. {$count} data kendaraan telah diperbarui.");
    }

    /**
     * Memperbaiki posisi massal nomor mesin dan rangka yang tertukar.
     * 
     * Khusus diakses oleh Superadmin.
     * 
     * @return RedirectResponse
     */
    public function sanitizeSwappedIdentifiers(): RedirectResponse
    {
        $count = $this->vehicleService->fixSwappedIdentifiers();

        // Catat audit log
        \App\Models\Activity::log("Melakukan perbaikan massal posisi Nomor Mesin dan Nomor Rangka yang tertukar [Jumlah data: {$count}]", 'info');

        return redirect()->route('vehicles.index')->with('success', "Perbaikan berhasil. {$count} data kendaraan telah ditukar posisinya (Nomor Mesin & Rangka).");
    }

    /**
     * Mengekspor seluruh data kendaraan ke file Excel.
     * 
     * @return BinaryFileResponse
     */
    public function export(): BinaryFileResponse
    {
        return Excel::download(new VehicleExport, 'data_kendaraan_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Mengunduh file template Excel untuk import data.
     * 
     * @return BinaryFileResponse
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        return Excel::download(new VehicleTemplateExport, 'template_import_kendaraan.xlsx');
    }

    /**
     * Mengeksekusi impor data kendaraan menggunakan hasil pemetaan AI Smart Import.
     * 
     * @param ExecuteSmartImportRequest $request
     * @return RedirectResponse
     */
    public function import(ExecuteSmartImportRequest $request): RedirectResponse
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        try {
            $importToken = $request->input('import_token');
            
            // Ambil metadata sesi impor dari cache
            $metadata = \Illuminate\Support\Facades\Cache::get($importToken);
            
            // Validasi sesi impor
            if (!$metadata) {
                return redirect()->route('vehicles.index')->with('error', 'Sesi impor tidak valid atau sudah kedaluwarsa. Silakan unggah ulang berkas.');
            }
            
            if ($metadata['user_id'] !== auth()->id()) {
                return redirect()->route('vehicles.index')->with('error', 'Akses ditolak: Sesi impor ini milik pengguna lain.');
            }
            
            if (now()->timestamp > $metadata['expires_at']) {
                if (\Illuminate\Support\Facades\Storage::disk('local')->exists($metadata['file_path'])) {
                    \Illuminate\Support\Facades\Storage::disk('local')->delete($metadata['file_path']);
                }
                \Illuminate\Support\Facades\Cache::forget($importToken);
                return redirect()->route('vehicles.index')->with('error', 'Sesi impor sudah kedaluwarsa. Silakan lakukan proses ulang.');
            }

            $filePath = $metadata['file_path'];
            $mapping = $request->input('mapping', []);
            $headers = $request->input('headers', []);
            $headerRowIndex = (int) $request->input('header_row_index', 0);
            
            // Konversi startRow (1-indexed, baris setelah header)
            $startRow = $headerRowIndex + 2; 

            // Pastikan file fisik temp benar-benar ada di storage
            if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($filePath)) {
                \Illuminate\Support\Facades\Cache::forget($importToken);
                return redirect()->route('vehicles.index')->with('error', 'Berkas impor temporer tidak ditemukan pada penyimpanan server.');
            }

            $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($filePath);

            $isEbmd = $request->input('target_table') === 'ebmd';
            $modelClass = $isEbmd ? \App\Models\EbmdVehicle::class : Vehicle::class;

            // Eksekusi impor dengan pemetaan dinamis (mendukung multi-sheet)
            $modelClass::withoutEvents(function () use ($fullPath, $mapping, $headers, $startRow, $modelClass) {
                Excel::import(
                    new VehicleMultiSheetImport($mapping, $headers, $startRow, $fullPath, $modelClass), 
                    $fullPath
                );
            });

            // Bersihkan file sementara dan cache sesi setelah sukses eksekusi
            \Illuminate\Support\Facades\Storage::disk('local')->delete($filePath);
            \Illuminate\Support\Facades\Cache::forget($importToken);

            // Catat log aktivitas untuk seluruh proses import
            \App\Models\Activity::log("Melakukan import data kendaraan secara massal (AI Smart Import)", 'success');

            // Invalidation massal seluruh OPD (Dashboard stats)
            $this->vehicleService->invalidateDashboardStats(invalidateAllOpd: true);

            return redirect()->route('vehicles.index', ['tab' => $request->input('target_table')])->with('success', 'Data kendaraan berhasil diimport menggunakan AI Smart Import.');
        } catch (\Exception $e) {
            return redirect()->route('vehicles.index', ['tab' => $request->input('target_table')])->with('error', 'Gagal mengimport data: ' . $e->getMessage());
        }
    }

    /**
     * Mengeksekusi impor data kendaraan menggunakan template statis tradisional (Legacy).
     * 
     * @param ImportVehicleRequest $request
     * @return RedirectResponse
     */
    public function importLegacy(ImportVehicleRequest $request): RedirectResponse
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        try {
            $file = $request->file('file');
            $fullPath = $file->getRealPath();

            $isEbmd = $request->input('target_table') === 'ebmd';
            $modelClass = $isEbmd ? \App\Models\EbmdVehicle::class : Vehicle::class;

            // Gunakan default mapping (legacy template) secara otomatis di konstruktor VehicleImport
            $modelClass::withoutEvents(function () use ($fullPath, $modelClass) {
                Excel::import(
                    new VehicleMultiSheetImport([], [], 4, $fullPath, $modelClass), 
                    $fullPath
                );
            });

            // Catat log aktivitas
            \App\Models\Activity::log("Melakukan import data kendaraan secara massal (Legacy Template)", 'success');
            
            // Invalidation massal seluruh OPD (Dashboard stats)
            $this->vehicleService->invalidateDashboardStats(invalidateAllOpd: true);

            return redirect()->route('vehicles.index', ['tab' => $request->input('target_table')])->with('success', 'Data kendaraan berhasil diimport menggunakan format template standar.');
        } catch (\Exception $e) {
            return redirect()->route('vehicles.index', ['tab' => $request->input('target_table')])->with('error', 'Gagal mengimport data: ' . $e->getMessage());
        }
    }

    /**
     * Membaca file Excel yang diunggah dan mengembalikan preview header & data sampel.
     * 
     * Fitur pendukung utama AI Smart Import (Phase 3).
     * 
     * @param ImportVehicleRequest $request
     * @return JsonResponse
     */
    public function importPreview(ImportVehicleRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            
            // Baca 15 baris pertama file Excel ke dalam array (Konsisten dengan importer: 15 baris)
            $import = new \App\Imports\VehiclePreviewImport;
            
            // Cari sheet pertama yang valid secara dinamis (mencari baris pertama dengan 3+ kolom terisi)
            $rows = [];
            $activeSheetName = '';
            
            // Dapatkan seluruh data sheets
            $sheets = Excel::toArray($import, $file);
            
            // Dapatkan daftar nama sheet dari berkas menggunakan PhpSpreadsheet dengan penanganan error anggun (offline / mock safe)
            $sheetNames = [];
            try {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file->getRealPath());
                $sheetNames = $reader->listWorksheetNames($file->getRealPath());
            } catch (\Exception $e) {
                // Abaikan error pembacaan jika format berkas tiruan tidak dikenali (terutama saat unit testing)
            }

            $headerRowIndex = 0;
            $headers = [];
            
            foreach ($sheets as $sheetIdx => $sheetRows) {
                if (empty($sheetRows)) continue;
                
                foreach ($sheetRows as $rowIndex => $row) {
                    $nonEmptyCells = array_filter($row, function($cell) {
                        return !is_null($cell) && trim($cell) !== '';
                    });

                    if (count($nonEmptyCells) > 2) { // Minimal memiliki 3 kolom terisi
                        $headerRowIndex = $rowIndex;
                        $headers = array_map(function($header) {
                            return trim($header);
                        }, $row);
                        $rows = $sheetRows;
                        $activeSheetName = $sheetNames[$sheetIdx] ?? "Sheet " . ($sheetIdx + 1);
                        break 2; // Pecahkan pencarian segera setelah menemukan sheet valid pertama!
                    }
                }
            }

            if (empty($rows) || empty($headers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File Excel kosong atau tidak terdeteksi adanya kolom header di seluruh sheet.'
                ], 422);
            }

            // Ambil maksimal 3 sampel baris data setelah header
            $samples = [];
            $sampleCount = 0;
            for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
                if ($sampleCount >= 3) break;
                
                $nonEmptyCells = array_filter($rows[$i], function($cell) {
                    return !is_null($cell) && trim($cell) !== '';
                });
                
                if (!empty($nonEmptyCells)) {
                    $samples[] = $rows[$i];
                    $sampleCount++;
                }
            }

            // Kolom Target Database E-RANDIS yang diharapkan untuk dipetakan
            $targetColumns = [
                'no_polisi' => 'Nomor Polisi (Plat)',
                'nomor_register' => 'Nomor Register',
                'jenis' => 'Jenis Kendaraan (Roda 2 / Roda 4 / dll)',
                'merk' => 'Merk / Pabrikan',
                'tipe' => 'Tipe / Model',
                'no_mesin' => 'Nomor Mesin',
                'no_rangka' => 'Nomor Rangka',
                'tahun_pembuatan' => 'Tahun Pembuatan',
                'tgl_perolehan' => 'Tanggal Perolehan Aset',
                'nilai_perolehan' => 'Harga / Nilai Perolehan',
                'stnk_ada' => 'Status STNK (Ada/Tidak)',
                'bpkb_ada' => 'Status BPKB (Ada/Tidak)',
                'kondisi' => 'Kondisi Fisik Kendaraan',
                'pemegang' => 'Nama Pemegang / Penanggung Jawab',
                'keterangan' => 'Keterangan Tambahan',
                'opd' => 'Nama OPD / Instansi (Jika Superadmin)',
            ];
            
            if ($request->input('target_table') === 'ebmd') {
                unset($targetColumns['tahun_pembuatan']);
            }

            // Analisis Semantik AI untuk mendapatkan rekomendasi pemetaan kolom
            $suggestedMapping = $this->vehicleService->suggestColumnMapping($headers);

            // Simpan file sementara di storage
            $filePath = $file->store('temp_imports', 'local');
            
            // Generate token keamanan sesi impor temporer (Berlaku 30 Menit)
            $importToken = 'import_' . \Illuminate\Support\Str::random(40);
            
            \Illuminate\Support\Facades\Cache::put($importToken, [
                'file_path'  => $filePath,
                'user_id'    => auth()->id(),
                'expires_at' => now()->addMinutes(30)->timestamp,
            ], now()->addMinutes(30));

            return response()->json([
                'success'           => true,
                'headers'           => $headers,
                'samples'           => $samples,
                'target_columns'    => $targetColumns,
                'suggested_mapping' => $suggestedMapping,
                'header_row_index'  => $headerRowIndex,
                'import_token'      => $importToken,
                'active_sheet_name' => $activeSheetName,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menganalisis database dan mengembalikan daftar duplikasi kendaraan & OPD untuk modal diagnosis.
     *
     * @return JsonResponse
     */
    public function checkDuplicates(): JsonResponse
    {
        try {
            $duplicateVehicles = $this->vehicleService->getDuplicateVehiclesList();
            $duplicateOpds = $this->vehicleService->getDuplicateOpdsList();

            $columnsToCompare = [
                'no_polisi'       => 'Nomor Polisi',
                'jenis'           => 'Jenis Kendaraan',
                'merk'            => 'Merk/Pabrikan',
                'tipe'            => 'Tipe/Model',
                'opd'             => 'OPD/Instansi',
                'pemegang'        => 'Nama Pemegang',
                'kondisi'         => 'Kondisi Fisik',
                'tahun_pembuatan' => 'Tahun Pembuatan',
                'no_mesin'        => 'Nomor Mesin',
                'no_rangka'       => 'Nomor Rangka',
                'nilai_perolehan' => 'Nilai Perolehan'
            ];

            // Transform data kendaraan agar siap dikonsumsi di frontend
            $formattedVehicles = array_map(function ($item) use ($columnsToCompare) {
                $differences = [];
                $original = $item['original_vehicle'];
                $duplicate = $item['duplicate_vehicle'];

                foreach ($columnsToCompare as $field => $label) {
                    if ($field === 'opd') {
                        $valOriginal = $original ? ($original->opdRelation?->nama ?? $original->opd ?? 'BELUM DIKETAHUI') : 'Tidak Ada';
                        $valDuplicate = $duplicate->opdRelation?->nama ?? $duplicate->opd ?? 'BELUM DIKETAHUI';
                    } elseif ($field === 'nilai_perolehan') {
                        $valOriginal = ($original && $original->nilai_perolehan) ? 'Rp ' . number_format($original->nilai_perolehan, 0, ',', '.') : '-';
                        $valDuplicate = $duplicate->nilai_perolehan ? 'Rp ' . number_format($duplicate->nilai_perolehan, 0, ',', '.') : '-';
                    } else {
                        $valOriginal = $original ? ($original->{$field} ?? '-') : '-';
                        $valDuplicate = $duplicate->{$field} ?? '-';
                    }

                    // Deteksi perbedaan nilai secara case-insensitive
                    $isDifferent = (trim(strtoupper($valOriginal)) !== trim(strtoupper($valDuplicate)));

                    $differences[] = [
                        'label'         => $label,
                        'original_val'  => $valOriginal,
                        'duplicate_val' => $valDuplicate,
                        'is_different'  => $isDifferent
                    ];
                }

                return [
                    'duplicate_id'     => $duplicate->id,
                    'duplicate_plate'  => $duplicate->no_polisi,
                    'duplicate_merk'   => $duplicate->merk ?? 'Tidak Diketahui',
                    'duplicate_opd'    => $duplicate->opdRelation?->nama ?? $duplicate->opd ?? 'BELUM DIKETAHUI',
                    
                    'original_id'      => $original ? $original->id : null,
                    'original_plate'   => $original ? $original->no_polisi : null,
                    'original_merk'    => $original ? $original->merk : null,
                    'original_opd'     => $original ? ($original->opdRelation?->nama ?? $original->opd ?? 'BELUM DIKETAHUI') : null,
                    
                    'reason'           => $item['reason'],
                    'differences'      => $differences
                ];
            }, $duplicateVehicles);

            // Transform data OPD
            $formattedOpds = array_map(function ($item) {
                return [
                    'opd_a_id'   => $item['opd_a']->id,
                    'opd_a_nama' => $item['opd_a']->nama,
                    'count_a'    => $item['count_a'],
                    
                    'opd_b_id'   => $item['opd_b']->id,
                    'opd_b_nama' => $item['opd_b']->nama,
                    'count_b'    => $item['count_b'],
                    
                    'reason'     => $item['reason']
                ];
            }, $duplicateOpds);

            return response()->json([
                'success'  => true,
                'vehicles' => $formattedVehicles,
                'opds'     => $formattedOpds
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendiagnosis duplikasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mengeksekusi penggabungan (merge) kendaraan ganda.
     *
     * @param ResolveDuplicateVehicleRequest $request
     * @return JsonResponse
     */
    public function resolveDuplicateVehicle(ResolveDuplicateVehicleRequest $request): JsonResponse
    {
        try {
            $originalId = (int)$request->input('original_id');
            $duplicateId = (int)$request->input('duplicate_id');
            $action = $request->input('action');
            $direction = $request->input('direction', 'keep_original');

            if ($action === 'merge') {
                if ($direction === 'keep_duplicate') {
                    $success = $this->vehicleService->mergeVehicles($duplicateId, $originalId);
                } else {
                    $success = $this->vehicleService->mergeVehicles($originalId, $duplicateId);
                }
                
                if (!$success) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Proses penggabungan gagal. Pasangan kendaraan tidak ditemukan.'
                    ], 404);
                }
                $message = 'Data kendaraan berhasil digabungkan (kolom kosong terisi) dan duplikat dibersihkan.';
            } else {
                // Pastikan yang dihapus adalah duplikat yang sah
                $success = \DB::transaction(function () use ($duplicateId) {
                    $duplicate = Vehicle::withoutGlobalScopes()->find($duplicateId);
                    if ($duplicate) {
                        $duplicate->delete();
                        return true;
                    }
                    return false;
                });

                if (!$success) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Proses penghapusan gagal. Kendaraan duplikat tidak ditemukan.'
                    ], 404);
                }
                $message = 'Kendaraan duplikat berhasil dibersihkan dari database.';
            }

            // Simpan audit log lengkap dengan konteks detail (ID, aksi) (Rekomendasi PM #9)
            \App\Models\Activity::log(
                "Pembersihan duplikasi kendaraan [Aksi: {$action}, ID Induk: {$originalId}, ID Duplikat: {$duplicateId}]", 
                'success'
            );
            
            $this->vehicleService->invalidateDashboardStats(invalidateAllOpd: true);

            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mengeksekusi penggabungan (merge) OPD duplikat.
     *
     * @param ResolveDuplicateOpdRequest $request
     * @return JsonResponse
     */
    public function resolveDuplicateOpd(ResolveDuplicateOpdRequest $request): JsonResponse
    {
        try {
            $targetId = (int)$request->input('target_opd_id');
            $sourceId = (int)$request->input('source_opd_id');

            $success = $this->vehicleService->mergeOpds($targetId, $sourceId);
            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proses konsolidasi gagal. Salah satu atau kedua instansi OPD tidak ditemukan.'
                ], 404);
            }
            
            // Simpan audit log lengkap dengan konteks detail (ID OPD) (Rekomendasi PM #9)
            \App\Models\Activity::log(
                "Pembersihan dan konsolidasi OPD duplikat [OPD Target ID: {$targetId}, OPD Sumber ID: {$sourceId}]", 
                'success'
            );
            
            $this->vehicleService->invalidateDashboardStats(invalidateAllOpd: true);

            return response()->json([
                'success' => true, 
                'message' => 'OPD berhasil dikonsolidasikan. Semua kendaraan dipindahkan ke instansi utama.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Menyinkronkan data dari e-BMD ke tabel Real.
     *
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function syncToReal(Request $request, $id): RedirectResponse
    {
        try {
            $ebmdVehicle = \App\Models\EbmdVehicle::findOrFail($id);
            
            if ($ebmdVehicle->is_synced) {
                return redirect()->route('vehicles.index', ['tab' => 'ebmd'])->with('error', 'Data e-BMD ini sudah pernah disinkronkan sebelumnya.');
            }

            $data = $ebmdVehicle->toArray();
            unset($data['id']);
            unset($data['is_synced']);
            unset($data['created_at']);
            unset($data['updated_at']);

            $data['no_polisi'] = $this->vehicleService->formatPlateNumber($data['no_polisi']);
            
            $vehicle = Vehicle::create($data);
            $ebmdVehicle->update(['is_synced' => true]);
            
            $this->vehicleService->invalidateDashboardStats(opdId: $vehicle->opd_id);

            // Log activity
            \App\Models\Activity::log("Menyinkronkan data e-BMD menjadi data fisik [Nopol: {$vehicle->no_polisi}]", 'success');

            return redirect()->route('vehicles.index', ['tab' => 'ebmd'])->with('success', 'Data e-BMD berhasil disinkronkan menjadi data fisik.');
        } catch (\Exception $e) {
            return redirect()->route('vehicles.index', ['tab' => 'ebmd'])->with('error', 'Gagal menyinkronkan data: ' . $e->getMessage());
        }
    }
}

