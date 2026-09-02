<?php

namespace App\Http\Controllers\Sipat;

use App\Http\Controllers\Controller;
use App\Models\AsetTanah;
use App\Models\OpdSipat;
use App\Models\StatusProses;
use App\Services\SipatService;
use App\Services\Sipat\AsetTanahService;
use App\Http\Requests\Sipat\StoreAsetTanahRequest;
use App\Http\Requests\Sipat\UpdateAsetTanahRequest;
use App\Http\Requests\Sipat\StoreProsesAsetRequest;
use App\Http\Requests\Sipat\BulkStoreProsesRequest;
use App\Http\Requests\Sipat\StoreDokumenAsetRequest;
use App\Http\Requests\Sipat\StorePengamananFisikRequest;
use App\Http\Requests\Sipat\StoreResolveDuplicateAset;
use App\Http\Requests\Sipat\StoreResolveDuplicateOpdSipat;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class AsetTanahController extends Controller implements HasMiddleware
{
    protected $sipatService;
    protected $asetTanahService;

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:superadmin,admin', only: ['checkDuplicates', 'resolveDuplicateAset', 'resolveDuplicateOpdSipat']),
        ];
    }

    /**
     * Konstruktor Controller.
     *
     * @param SipatService $sipatService
     * @param AsetTanahService $asetTanahService
     */
    public function __construct(SipatService $sipatService, AsetTanahService $asetTanahService)
    {
        $this->sipatService = $sipatService;
        $this->asetTanahService = $asetTanahService;
    }

    /**
     * Menampilkan daftar aset tanah terpaginasi dengan filter atau mengekspor data.
     *
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        if ($request->has('reset')) {
            session()->forget('sipat_aset_filters');
            return redirect()->route('sipat.aset.index');
        }

        $filterKeys = ['opd_id', 'opd', 'status', 'kategori_status', 'search', 'per_page', 'page'];
        $queryFilters = array_filter($request->only($filterKeys), function($v) {
            if (is_array($v)) return !empty(array_filter($v));
            return !is_null($v) && $v !== '';
        });

        if (!empty($queryFilters)) {
            session(['sipat_aset_filters' => $queryFilters]);
        } elseif (session()->has('sipat_aset_filters') && !$request->has('export')) {
            $savedFilters = session('sipat_aset_filters', []);
            if (!empty($savedFilters)) {
                return redirect()->route('sipat.aset.index', $savedFilters);
            }
        }

        if ($request->has('export')) {
            $exportType = strtolower($request->input('export'));
            $laporanService = app(\App\Services\LaporanService::class);
            
            $filters = $request->all();
            if (isset($filters['opd_id']) && !empty($filters['opd_id'])) {
                $filters['opd'] = $filters['opd_id'];
            }
            if (isset($filters['status']) && !empty($filters['status'])) {
                $filters['status_proses_id'] = $filters['status'];
            }

            $rows = $laporanService->buildQuery($filters)->get();
            $summary = $laporanService->buildSummary($rows, $filters);
            $kop = $laporanService->getKopSettings();
            $selectedTitle = $laporanService->resolveReportTitle($filters);

            if (in_array($exportType, ['csv', 'xlsx', 'excel'], true)) {
                return $laporanService->exportExcel($rows, $filters, $summary, $kop, $selectedTitle);
            }

            if (in_array($exportType, ['print', 'pdf'], true)) {
                $html = view('sipat.laporan.print_pdf', compact('rows', 'filters', 'summary', 'kop', 'selectedTitle'))->render();
                
                $pdfTempDir = storage_path('framework/cache/mpdf');
                if (!is_dir($pdfTempDir)) {
                    @mkdir($pdfTempDir, 0775, true);
                }

                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4-L',
                    'margin_top' => 10,
                    'margin_bottom' => 26,
                    'margin_left' => 10,
                    'margin_right' => 10,
                    'tempDir' => $pdfTempDir,
                ]);
                
                $mpdf->SetTitle($selectedTitle);
                $mpdf->SetHTMLFooter('<div style="font-size:9pt;color:#64748b;border-top:1px solid #dbe3ef;padding-top:6px;text-align:center;">Halaman {PAGENO} dari {nbpg} | ' . htmlspecialchars((string) ($kop['kop_footer'] ?? ''), ENT_QUOTES, 'UTF-8') . '</div>');
                $mpdf->WriteHTML($html);
                $filename = 'Laporan_Aset_Tanah_' . date('Ymd_His') . '.pdf';
                return response($mpdf->Output($filename, 'I'), 200, ['Content-Type' => 'application/pdf']);
            }
        }

        $data = $this->asetTanahService->getPaginatedAset($request->all());
        $unrecordedCount = AsetTanah::where('status_pencatatan', 'USULAN_BELUM_TERCATAT')->count();
        $kecamatanList = $data['kecamatanList'] ?? \App\Models\Kecamatan::orderBy('nama', 'asc')->get();

        return view('sipat.aset.index', [
            'asetTanah'       => $data['asetTanah'],
            'opdList'         => $data['opdList'],
            'statusList'      => $data['statusList'],
            'kecamatanList'   => $kecamatanList,
            'unrecordedCount' => $unrecordedCount,
        ]);
    }

    /**
     * Helper privat untuk melakukan redirect dengan mempertahankan filter aktif.
     */
    private function redirectWithFilters(string $message, string $type = 'success'): RedirectResponse
    {
        $params = session('sipat_aset_filters', []);
        return redirect()->route('sipat.aset.index', $params)->with($type, $message);
    }

    /**
     * Menampilkan form pendaftaran aset baru.
     *
     * @return View
     */
    public function create(): View
    {
        $opdList = OpdSipat::where('aktif', 1)->orderBy('nama', 'asc')->get();
        $statusList = StatusProses::orderBy('urutan', 'asc')->get();
        $kecamatanList = \App\Models\Kecamatan::orderBy('nama', 'asc')->get();
        $desaList = \App\Models\Desa::orderBy('nama', 'asc')->get();
        return view('sipat.aset.create', compact('opdList', 'statusList', 'kecamatanList', 'desaList'));
    }

    /**
     * Menyimpan data aset tanah baru.
     *
     * @param StoreAsetTanahRequest $request
     * @return RedirectResponse
     */
    public function store(StoreAsetTanahRequest $request): RedirectResponse
    {
        $this->asetTanahService->storeAset(
            $request->validated(),
            $request->input('initial_status_id')
        );

        return $this->redirectWithFilters('Data Aset Tanah berhasil ditambahkan.');
    }

    /**
     * Mengambil detail mentah aset tanah dalam JSON (AJAX).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(AsetTanah $aset): JsonResponse
    {
        return response()->json($aset->load(['prosesAset.statusProses', 'latestProses.statusProses', 'wilayahKecamatan', 'wilayahDesa']));
    }

    /**
     * Menampilkan isi modal detail aset tanah.
     *
     * @param int $id
     * @return View
     */
    public function modal(AsetTanah $aset): View
    {
        $data = $this->asetTanahService->getAsetDetailsForModal($aset->id_aset);

        return view('sipat.aset.modal', [
            'aset'             => $data['aset'],
            'prosesList'       => $data['prosesList'],
            'statusList'       => $data['statusList'],
            'pengamanan'       => $data['pengamanan'],
            'dokumenList'      => $data['dokumenList'],
            'elabelSertifikat' => $data['elabelSertifikat'],
        ]);
    }

    /**
     * Menampilkan form edit aset tanah.
     *
     * @param int $id
     * @return View
     */
    public function edit(AsetTanah $aset): View
    {
        $opdList = OpdSipat::where('aktif', 1)->orderBy('nama', 'asc')->get();
        $statusList = StatusProses::orderBy('urutan', 'asc')->get();
        $kecamatanList = \App\Models\Kecamatan::orderBy('nama', 'asc')->get();
        $desaList = \App\Models\Desa::orderBy('nama', 'asc')->get();
        return view('sipat.aset.edit', compact('aset', 'opdList', 'statusList', 'kecamatanList', 'desaList'));
    }

    /**
     * Memperbarui informasi aset tanah.
     *
     * @param UpdateAsetTanahRequest $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update(UpdateAsetTanahRequest $request, AsetTanah $aset): RedirectResponse
    {
        $this->asetTanahService->updateAset($aset->id_aset, $request->validated());

        return $this->redirectWithFilters('Data Aset Tanah berhasil diperbarui.');
    }

    /**
     * Menghapus aset tanah beserta relasi.
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy(AsetTanah $aset): RedirectResponse
    {
        $this->asetTanahService->deleteAset($aset->id_aset);

        return $this->redirectWithFilters('Data Aset Tanah berhasil dihapus.');
    }

    /**
     * Menyimpan riwayat proses pengurusan BPN baru.
     *
     * @param StoreProsesAsetRequest $request
     * @param int $id
     * @return RedirectResponse
     */
    public function storeProses(StoreProsesAsetRequest $request, AsetTanah $aset): RedirectResponse
    {
        $this->asetTanahService->addProsesBpn($aset->id_aset, $request->validated());

        return $this->redirectWithFilters('Riwayat Proses BPN berhasil ditambahkan.');
    }

    /**
     * Memperbarui status proses BPN secara massal.
     *
     * @param BulkStoreProsesRequest $request
     * @return RedirectResponse
     */
    public function bulkStoreProses(BulkStoreProsesRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        $insertedCount = $this->sipatService->bulkUpdateStatus(
            $validated['aset_ids'] ?? [],
            $validated['nibar_list'] ?? '',
            $validated['id_status'],
            $validated['tanggal_proses'] ?? ($validated['tgl_mulai'] ?? null),
            $validated['tgl_mulai'] ?? null,
            $validated['tgl_selesai'] ?? null,
            $validated['keterangan'] ?? null
        );

        if ($insertedCount === 0) {
            return redirect()->back()->with('error', 'Tidak ada data aset yang ditemukan untuk diproses.');
        }

        $statusName = StatusProses::find($validated['id_status'])->nama_status ?? 'Unknown';
        Activity::logSipat("Memperbarui status pengurusan BPN secara massal menjadi '{$statusName}' untuk {$insertedCount} aset tanah", 'success');

        return $this->redirectWithFilters("Berhasil memperbarui status untuk {$insertedCount} aset.");
    }

    /**
     * Menyimpan/memperbarui data pengamanan fisik lapangan.
     *
     * @param StorePengamananFisikRequest $request
     * @param int $id
     * @return RedirectResponse
     */
    public function storePengamanan(StorePengamananFisikRequest $request, AsetTanah $aset): RedirectResponse
    {
        $this->asetTanahService->savePengamananFisik($aset->id_aset, $request->validated());

        return $this->redirectWithFilters('Status pengamanan fisik aset berhasil diperbarui.');
    }

    /**
     * Mengunggah dokumen lampiran aset.
     *
     * @param StoreDokumenAsetRequest $request
     * @param int $id
     * @return RedirectResponse
     */
    public function storeDokumen(StoreDokumenAsetRequest $request, AsetTanah $aset): RedirectResponse
    {
        $this->asetTanahService->saveDokumenAset(
            $aset->id_aset, 
            $request->validated(), 
            $request->file('file')
        );

        return $this->redirectWithFilters('Dokumen lampiran aset berhasil diunggah.');
    }

    /**
     * Menganalisis database pertanahan dan mengembalikan daftar duplikasi aset & OPD untuk modal diagnosis.
     *
     * @return JsonResponse
     */
    public function checkDuplicates(): JsonResponse
    {
        try {
            $duplicateAsets = $this->asetTanahService->getDuplicateAsetList();
            $duplicateOpds = $this->asetTanahService->getDuplicateOpdSipatList();

            $columnsToCompare = [
                'kode_aset'       => 'NIB / Kode Aset',
                'nama_aset'       => 'Nama Aset',
                'peruntukan'      => 'Peruntukan',
                'luas'            => 'Luas Tanah (m²)',
                'alamat'          => 'Alamat Aset',
                'opd'             => 'OPD Pengelola',
                'dasar_perolehan' => 'Dasar Perolehan',
                'harga_perolehan' => 'Harga Perolehan',
            ];

            // Transform data aset ganda
            $formattedAsets = array_map(function ($item) use ($columnsToCompare) {
                $differences = [];
                $original = $item['original_aset'];
                $duplicate = $item['duplicate_aset'];

                foreach ($columnsToCompare as $field => $label) {
                    if ($field === 'opd') {
                        $valOriginal = $original ? ($original->opdSipat?->nama ?? $original->opd ?? '-') : '-';
                        $valDuplicate = $duplicate->opdSipat?->nama ?? $duplicate->opd ?? '-';
                    } elseif ($field === 'harga_perolehan') {
                        $valOriginal = ($original && $original->harga_perolehan) ? 'Rp ' . number_format($original->harga_perolehan, 0, ',', '.') : '-';
                        $valDuplicate = $duplicate->harga_perolehan ? 'Rp ' . number_format($duplicate->harga_perolehan, 0, ',', '.') : '-';
                    } elseif ($field === 'luas') {
                        $valOriginal = $original ? number_format($original->luas, 2, ',', '.') : '-';
                        $valDuplicate = number_format($duplicate->luas, 2, ',', '.');
                    } else {
                        $valOriginal = $original ? ($original->{$field} ?? '-') : '-';
                        $valDuplicate = $duplicate->{$field} ?? '-';
                    }

                    $isDifferent = (trim(strtoupper((string)$valOriginal)) !== trim(strtoupper((string)$valDuplicate)));

                    $differences[] = [
                        'label'         => $label,
                        'original_val'  => $valOriginal,
                        'duplicate_val' => $valDuplicate,
                        'is_different'  => $isDifferent
                    ];
                }

                return [
                    'duplicate_id'     => $duplicate->id_aset,
                    'duplicate_code'   => $duplicate->kode_aset,
                    'duplicate_nama'   => $duplicate->nama_aset ?? 'Tanpa Nama',
                    'duplicate_opd'    => $duplicate->opdSipat?->nama ?? $duplicate->opd ?? '-',
                    
                    'original_id'      => $original ? $original->id_aset : null,
                    'original_code'    => $original ? $original->kode_aset : null,
                    'original_nama'    => $original ? $original->nama_aset : null,
                    'original_opd'     => $original ? ($original->opdSipat?->nama ?? $original->opd ?? '-') : null,
                    
                    'reason'           => $item['reason'],
                    'differences'      => $differences
                ];
            }, $duplicateAsets);

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
                'asets'    => $formattedAsets,
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
     * Mengeksekusi penggabungan (merge) atau pembersihan aset tanah ganda.
     *
     * @param StoreResolveDuplicateAset $request
     * @return JsonResponse
     */
    public function resolveDuplicateAset(StoreResolveDuplicateAset $request): JsonResponse
    {
        try {
            $originalId = (int)$request->input('original_id');
            $duplicateId = (int)$request->input('duplicate_id');
            $action = $request->input('action');
            $direction = $request->input('direction', 'keep_original');

            if ($action === 'merge') {
                if ($direction === 'keep_duplicate') {
                    $success = $this->asetTanahService->mergeAset($duplicateId, $originalId);
                } else {
                    $success = $this->asetTanahService->mergeAset($originalId, $duplicateId);
                }
                
                if (!$success) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Proses penggabungan gagal. Aset tanah tidak ditemukan.'
                    ], 404);
                }
                $message = 'Data aset tanah berhasil digabungkan dan duplikat dibersihkan.';
            } else {
                $success = \DB::transaction(function () use ($duplicateId) {
                    $duplicate = AsetTanah::find($duplicateId);
                    if ($duplicate) {
                        \App\Models\ProsesAset::where('id_aset', $duplicateId)->delete();
                        $duplicate->delete();
                        return true;
                    }
                    return false;
                });

                if (!$success) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Proses penghapusan gagal. Aset tanah duplikat tidak ditemukan.'
                    ], 404);
                }
                $message = 'Aset tanah duplikat berhasil dibersihkan dari database.';
            }

            Activity::logSipat(
                "Pembersihan duplikasi aset tanah [Aksi: {$action}, ID Induk: {$originalId}, ID Duplikat: {$duplicateId}]", 
                'success'
            );

            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mengeksekusi penggabungan (merge) OPD pertanahan duplikat.
     *
     * @param StoreResolveDuplicateOpdSipat $request
     * @return JsonResponse
     */
    public function resolveDuplicateOpdSipat(StoreResolveDuplicateOpdSipat $request): JsonResponse
    {
        try {
            $targetId = (int)$request->input('target_opd_id');
            $sourceId = (int)$request->input('source_opd_id');

            $success = $this->asetTanahService->mergeOpdSipat($targetId, $sourceId);
            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proses konsolidasi gagal. Salah satu atau kedua instansi OPD tidak ditemukan.'
                ], 404);
            }
            
            Activity::logSipat(
                "Pembersihan dan konsolidasi OPD duplikat SIPAT [OPD Target ID: {$targetId}, OPD Sumber ID: {$sourceId}]", 
                'success'
            );

            return response()->json([
                'success' => true, 
                'message' => 'OPD berhasil dikonsolidasikan. Semua aset tanah dipindahkan ke instansi utama.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
