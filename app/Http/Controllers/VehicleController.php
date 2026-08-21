<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\User;
use App\Models\VehicleType;
use App\Models\Op;
use App\Models\Opd;
use Illuminate\Http\Request;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Exports\VehicleExport;
use App\Exports\VehicleTemplateExport;
use App\Http\Requests\ImportVehicleRequest;
use App\Http\Requests\ExecuteSmartImportRequest;
use App\Http\Requests\ResolveDuplicateVehicleRequest;
use App\Http\Requests\ResolveDuplicateOpdRequest;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

use App\Services\VehicleService;
use App\Services\VehicleQueryService;
use App\Services\VehicleImportService;

/**
 * Controller untuk Manajemen Data Kendaraan
 * 
 * Menangani CRUD data kendaraan, pencarian, serta fitur import/export Excel.
 */
class VehicleController extends Controller implements HasMiddleware
{
    protected $vehicleService;
    protected $vehicleQueryService;
    protected $vehicleImportService;

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

    /**
     * Konstruktor Controller dengan dependency injection.
     * 
     * @param VehicleService $vehicleService
     * @param VehicleQueryService $vehicleQueryService
     * @param VehicleImportService $vehicleImportService
     */
    public function __construct(
        VehicleService $vehicleService,
        VehicleQueryService $vehicleQueryService,
        VehicleImportService $vehicleImportService
    ) {
        $this->vehicleService = $vehicleService;
        $this->vehicleQueryService = $vehicleQueryService;
        $this->vehicleImportService = $vehicleImportService;
    }

    /**
     * Menampilkan daftar kendaraan dengan fitur filter dan pencarian.
     * 
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $data = $this->vehicleQueryService->getPaginatedVehicles($request->all());
        
        $vehicles = $data['vehicles'];
        $vehicleDataMap = $data['vehicleDataMap'];
        $statuses = $data['statuses'];
        $conditions = $data['conditions'];
        $isEbmd = $data['isEbmd'];

        $vehicleTypes = VehicleType::orderBy('name')->get();
        $stats = $this->vehicleService->getDashboardStats();
        $ebmdStats = $isEbmd ? $this->vehicleService->getEbmdStats() : [];
        $opds = Opd::orderBy('nama')->get();

        return view('vehicles.index', compact(
            'vehicles', 'stats', 'ebmdStats', 'vehicleTypes', 
            'opds', 'statuses', 'conditions', 'vehicleDataMap'
        ));
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

        // Ambil Pengaturan Web dalam satu kali proses
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
        $this->vehicleService->storeVehicle(
            $request->validated(),
            $request->file('foto_kendaraan'),
            $request->input('target_table')
        );

        return redirect()->route('vehicles.index', ['tab' => $request->input('target_table')])
            ->with('success', 'Data kendaraan berhasil ditambahkan.');
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
     * @param int $id
     * @return RedirectResponse
     */
    public function update(UpdateVehicleRequest $request, $id): RedirectResponse
    {
        $this->vehicleService->updateVehicle(
            $id,
            $request->validated(),
            $request->file('foto_kendaraan'),
            $request->input('target_table')
        );

        return redirect()->route('vehicles.index', ['tab' => $request->input('target_table')])
            ->with('success', 'Data kendaraan berhasil diperbarui.');
    }

    /**
     * Menghapus data kendaraan dari database.
     * 
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy(Request $request, $id): RedirectResponse
    {
        $this->vehicleService->deleteVehicle($id, $request->input('target_table'));

        return redirect()->route('vehicles.index', ['tab' => $request->input('target_table')])
            ->with('success', 'Data kendaraan berhasil dihapus.');
    }

    /**
     * Mengosongkan seluruh data di tabel kendaraan.
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function truncate(Request $request): RedirectResponse
    {
        $isEbmd = $request->input('target_table') === 'ebmd';

        if ($isEbmd) {
            \App\Models\EbmdVehicle::truncate();
            $tabLabel = 'e-BMD';
        } else {
            \Illuminate\Support\Facades\Storage::disk('public')->deleteDirectory('vehicles');
            Vehicle::truncate();
            $tabLabel = 'Data Real';
        }
        
        $this->vehicleService->invalidateDashboardStats(invalidateAllOpd: true);

        return redirect()->route('vehicles.index', ['tab' => $request->input('target_table', 'real')])
            ->with('success', "Seluruh data kendaraan {$tabLabel} berhasil dikosongkan.");
    }

    /**
     * Membersihkan massal seluruh nomor rangka dan nomor mesin kendaraan dari karakter khusus.
     * 
     * @return RedirectResponse
     */
    public function sanitizeIdentifiers(): RedirectResponse
    {
        $count = $this->vehicleService->sanitizeIdentifiers();

        \App\Models\Activity::log("Melakukan pembersihan massal karakter khusus nomor mesin dan nomor rangka kendaraan [Jumlah data: {$count}]", 'info');

        return redirect()->route('vehicles.index')->with('success', "Pembersihan berhasil. {$count} data kendaraan telah diperbarui.");
    }

    /**
     * Memperbaiki posisi massal nomor mesin dan rangka yang tertukar.
     * 
     * @return RedirectResponse
     */
    public function sanitizeSwappedIdentifiers(): RedirectResponse
    {
        $count = $this->vehicleService->fixSwappedIdentifiers();

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
            $this->vehicleImportService->executeSmartImport(
                $request->input('import_token'),
                $request->input('mapping', []),
                $request->input('headers', []),
                (int) $request->input('header_row_index', 0),
                $request->input('target_table', 'real'),
                auth()->id()
            );

            \App\Models\Activity::log("Melakukan import data kendaraan secara massal (AI Smart Import)", 'success');

            return redirect()->route('vehicles.index', ['tab' => $request->input('target_table')])
                ->with('success', 'Data kendaraan berhasil diimport menggunakan AI Smart Import.');
        } catch (\Exception $e) {
            return redirect()->route('vehicles.index', ['tab' => $request->input('target_table')])
                ->with('error', 'Gagal mengimport data: ' . $e->getMessage());
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
            $this->vehicleImportService->executeLegacyImport(
                $request->file('file'),
                $request->input('target_table', 'real')
            );

            \App\Models\Activity::log("Melakukan import data kendaraan secara massal (Legacy Template)", 'success');
            
            return redirect()->route('vehicles.index', ['tab' => $request->input('target_table')])
                ->with('success', 'Data kendaraan berhasil diimport menggunakan format template standar.');
        } catch (\Exception $e) {
            return redirect()->route('vehicles.index', ['tab' => $request->input('target_table')])
                ->with('error', 'Gagal mengimport data: ' . $e->getMessage());
        }
    }

    /**
     * Membaca file Excel yang diunggah dan mengembalikan preview header & data sampel.
     * 
     * @param ImportVehicleRequest $request
     * @return JsonResponse
     */
    public function importPreview(ImportVehicleRequest $request): JsonResponse
    {
        try {
            $preview = $this->vehicleImportService->generateImportPreview(
                $request->file('file'),
                $request->input('target_table', 'real'),
                auth()->id()
            );

            return response()->json($preview);
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
            $vehicle = $this->vehicleService->syncEbmdToReal($id);
            
            \App\Models\Activity::log("Menyinkronkan data e-BMD menjadi data fisik [Nopol: {$vehicle->no_polisi}]", 'success');

            return redirect()->route('vehicles.index', ['tab' => 'ebmd'])
                ->with('success', 'Data e-BMD berhasil disinkronkan menjadi data fisik.');
        } catch (\Exception $e) {
            return redirect()->route('vehicles.index', ['tab' => 'ebmd'])
                ->with('error', 'Gagal menyinkronkan data: ' . $e->getMessage());
        }
    }
}
