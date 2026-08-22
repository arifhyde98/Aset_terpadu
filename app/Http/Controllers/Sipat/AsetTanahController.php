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
     * Menampilkan daftar aset tanah terpaginasi dengan filter.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $data = $this->asetTanahService->getPaginatedAset($request->all());

        return view('sipat.aset.index', [
            'asetTanah'  => $data['asetTanah'],
            'opdList'    => $data['opdList'],
            'statusList' => $data['statusList'],
        ]);
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
        return view('sipat.aset.create', compact('opdList', 'statusList'));
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

        return redirect()->route('sipat.aset.index')->with('success', 'Data Aset Tanah berhasil ditambahkan.');
    }

    /**
     * Mengambil detail mentah aset tanah dalam JSON (AJAX).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(AsetTanah $aset): JsonResponse
    {
        return response()->json($aset->load(['prosesAset.statusProses', 'latestProses.statusProses']));
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
        return view('sipat.aset.edit', compact('aset', 'opdList', 'statusList'));
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

        return redirect()->route('sipat.aset.index')->with('success', 'Data Aset Tanah berhasil diperbarui.');
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

        return redirect()->route('sipat.aset.index')->with('success', 'Data Aset Tanah berhasil dihapus.');
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

        return redirect()->route('sipat.aset.index')->with('success', 'Riwayat Proses BPN berhasil ditambahkan.');
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
            $validated['tgl_mulai'] ?? null,
            $validated['tgl_selesai'] ?? null,
            $validated['keterangan'] ?? null
        );

        if ($insertedCount === 0) {
            return redirect()->back()->with('error', 'Tidak ada data aset yang ditemukan untuk diproses.');
        }

        $statusName = StatusProses::find($validated['id_status'])->nama_status ?? 'Unknown';
        Activity::logSipat("Memperbarui status pengurusan BPN secara massal menjadi '{$statusName}' untuk {$insertedCount} aset tanah", 'success');

        return redirect()->route('sipat.aset.index')->with('success', "Berhasil memperbarui status untuk {$insertedCount} aset.");
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

        return redirect()->route('sipat.aset.index')->with('success', 'Status pengamanan fisik aset berhasil diperbarui.');
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

        return redirect()->route('sipat.aset.index')->with('success', 'Dokumen lampiran aset berhasil diunggah.');
    }
}
