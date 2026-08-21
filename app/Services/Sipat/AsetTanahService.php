<?php

namespace App\Services\Sipat;

use App\Models\AsetTanah;
use App\Models\OpdSipat;
use App\Models\ProsesAset;
use App\Models\StatusProses;
use App\Models\Activity;
use App\Services\SipatService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class AsetTanahService
{
    protected $sipatService;

    public function __construct(SipatService $sipatService)
    {
        $this->sipatService = $sipatService;
    }

    /**
     * Mendapatkan daftar aset tanah terpaginasi beserta filter pencarian.
     *
     * @param array $filters
     * @return array
     */
    public function getPaginatedAset(array $filters): array
    {
        $query = AsetTanah::with(['latestProses.statusProses']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('kode_aset', 'LIKE', "%{$search}%")
                  ->orWhere('nama_aset', 'LIKE', "%{$search}%")
                  ->orWhere('opd', 'LIKE', "%{$search}%")
                  ->orWhere('peruntukan', 'LIKE', "%{$search}%")
                  ->orWhere('alamat', 'LIKE', "%{$search}%")
                  ->orWhereExists(function($sub) use ($search) {
                      $sub->select(DB::raw(1))
                          ->from('elabel_sertifikat_tanah')
                          ->whereColumn('elabel_sertifikat_tanah.nibar', 'aset_tanah.kode_aset')
                          ->where(function($sub2) use ($search) {
                              $sub2->where('nama_pemilik', 'LIKE', "%{$search}%")
                                   ->orWhere('status_penggunaan', 'LIKE', "%{$search}%");
                          });
                  });
            });
        }

        if (!empty($filters['opd'])) {
            if ($filters['opd'] === 'KOSONG') {
                $query->where(function($q) {
                    $q->whereNull('opd')->orWhere('opd', '');
                });
            } else {
                $query->where('opd', $filters['opd']);
            }
        }

        if (!empty($filters['status'])) {
            $statusInput = (array) $filters['status'];
            $statusIds = array_filter($statusInput);
            if (!empty($statusIds)) {
                $query->whereHas('latestProses', function($q) use ($statusIds) {
                    $q->whereIn('id_status', $statusIds);
                });
            }
        }

        if (!empty($filters['tanggal_perolehan'])) {
            $query->whereDate('tanggal_perolehan', $filters['tanggal_perolehan']);
        }

        $perPage = $filters['per_page'] ?? 15;
        if ($perPage === 'all') {
            $asetTanah = $query->orderBy('id_aset', 'desc')->paginate(1000)->withQueryString();
        } else {
            $asetTanah = $query->orderBy('id_aset', 'desc')->paginate((int)$perPage)->withQueryString();
        }

        $opdList = OpdSipat::where('aktif', 1)->orderBy('nama', 'asc')->get();
        $statusList = StatusProses::orderBy('urutan', 'asc')->get();

        return [
            'asetTanah' => $asetTanah,
            'opdList' => $opdList,
            'statusList' => $statusList,
        ];
    }

    /**
     * Menyimpan aset tanah baru beserta status awalnya.
     *
     * @param array $data
     * @param int|null $initialStatusId
     * @return AsetTanah
     */
    public function storeAset(array $data, ?int $initialStatusId): AsetTanah
    {
        $aset = AsetTanah::create($data);

        if ($initialStatusId) {
            ProsesAset::create([
                'id_aset' => $aset->id_aset,
                'id_status' => $initialStatusId,
                'tgl_mulai' => $data['tanggal_perolehan'] ?? date('Y-m-d'),
                'keterangan' => 'Status awal pensertifikatan saat pendaftaran aset'
            ]);
        }

        $this->sipatService->invalidateDashboardCache();

        Activity::logSipat("Menambahkan data aset tanah baru: {$aset->nama_aset} (NIB: {$aset->kode_aset})", 'success');

        return $aset;
    }

    /**
     * Mengambil data lengkap untuk tampilan modal.
     *
     * @param int $id
     * @return array
     */
    public function getAsetDetailsForModal(int $id): array
    {
        $aset = AsetTanah::with(['prosesAset.statusProses', 'latestProses.statusProses'])->findOrFail($id);
        
        $prosesList = ProsesAset::with('statusProses')
            ->where('id_aset', $id)
            ->orderBy('id_proses', 'desc')
            ->get();
            
        $statusList = StatusProses::orderBy('urutan', 'asc')->get();
        $pengamanan = DB::table('pengamanan_fisik')->where('id_aset', $id)->first();
        $dokumenList = DB::table('dokumen_aset')->where('id_aset', $id)->orderBy('uploaded_at', 'desc')->get();

        $elabelSertifikat = null;
        if (!empty($aset->no_sertifikat)) {
            $cleanNo = trim($aset->no_sertifikat);
            $elabelSertifikat = \App\Models\Elabel\ElabelSertifikat::with('box')
                ->where('no_sertipikat', $cleanNo)
                ->orWhere('no_sertipikat', 'LIKE', '%' . $cleanNo . '%')
                ->first();
        }

        if (!$elabelSertifikat && (!empty($aset->kode_aset) || !empty($aset->nama_aset))) {
            $elabelSertifikat = \App\Models\Elabel\ElabelSertifikat::with('box')
                ->where(function ($q) use ($aset) {
                    if (!empty($aset->kode_aset)) {
                        $q->where('nibar', $aset->kode_aset);
                    }
                    if (!empty($aset->nama_aset)) {
                        $q->orWhere('nama_pemilik', 'LIKE', '%' . $aset->nama_aset . '%');
                    }
                })
                ->first();
        }

        return compact('aset', 'prosesList', 'statusList', 'pengamanan', 'dokumenList', 'elabelSertifikat');
    }

    /**
     * Memperbarui data informasi aset tanah.
     *
     * @param int $id
     * @param array $data
     * @return AsetTanah
     */
    public function updateAset(int $id, array $data): AsetTanah
    {
        $aset = AsetTanah::findOrFail($id);
        $aset->update($data);

        $konteks = $aset->peruntukan ? "Peruntukan: {$aset->peruntukan}" : "Peruntukan: -";
        
        $this->sipatService->invalidateDashboardCache();

        Activity::logSipat("Memperbarui informasi aset tanah: {$aset->nama_aset} ({$konteks})", 'info');

        return $aset;
    }

    /**
     * Menghapus secara permanen data aset tanah beserta riwayatnya.
     *
     * @param int $id
     * @return void
     */
    public function deleteAset(int $id): void
    {
        $aset = AsetTanah::findOrFail($id);
        $kodeAset = $aset->kode_aset;
        $namaAset = $aset->nama_aset;
        
        ProsesAset::where('id_aset', $id)->delete();
        $aset->delete();

        $this->sipatService->invalidateDashboardCache();

        Activity::logSipat("Menghapus data aset tanah secara permanen: {$namaAset} (NIB: {$kodeAset})", 'danger');
    }

    /**
     * Menyimpan riwayat proses pengurusan BPN baru.
     *
     * @param int $id
     * @param array $data
     * @return ProsesAset
     */
    public function addProsesBpn(int $id, array $data): ProsesAset
    {
        $aset = AsetTanah::findOrFail($id);
        $durasi = $this->sipatService->calculateDuration($data['tgl_mulai'] ?? null, $data['tgl_selesai'] ?? null);

        $proses = ProsesAset::create([
            'id_aset'     => $aset->id_aset,
            'id_status'   => $data['id_status'],
            'tgl_mulai'   => $data['tgl_mulai'] ?? null,
            'tgl_selesai' => $data['tgl_selesai'] ?? null,
            'keterangan'  => $data['keterangan'] ?? null,
            'durasi_hari' => $durasi,
        ]);

        $this->sipatService->invalidateDashboardCache();

        Activity::logSipat("Memperbarui status pengurusan BPN (Status baru: {$proses->statusProses->nama_status}) untuk aset tanah: {$aset->nama_aset}", 'success');

        return $proses;
    }

    /**
     * Menyimpan atau memperbarui data pengamanan fisik aset.
     *
     * @param int $id
     * @param array $data
     * @return void
     */
    public function savePengamananFisik(int $id, array $data): void
    {
        $aset = AsetTanah::findOrFail($id);

        DB::table('pengamanan_fisik')->updateOrInsert(
            ['id_aset' => $id],
            [
                'sertifikat_ada' => isset($data['sertifikat_ada']) ? 1 : 0,
                'papan_nama' => isset($data['papan_nama']) ? 1 : 0,
                'pagar' => isset($data['pagar']) ? 1 : 0,
                'dikuasai_pihak_lain' => isset($data['dikuasai_pihak_lain']) ? 1 : 0,
                'tgl_cek' => $data['tgl_cek'] ?? date('Y-m-d'),
                'catatan' => $data['catatan'] ?? null,
                'updated_at' => now(),
            ]
        );

        $this->sipatService->invalidateDashboardCache();

        Activity::logSipat("Mencatat laporan pengamanan fisik lapangan untuk aset tanah: {$aset->nama_aset}", 'info');
    }

    /**
     * Menyimpan / mengunggah file dokumen lampiran aset.
     *
     * @param int $id
     * @param array $data
     * @param UploadedFile|null $file
     * @return void
     */
    public function saveDokumenAset(int $id, array $data, ?UploadedFile $file): void
    {
        $aset = AsetTanah::findOrFail($id);

        $path = null;
        if ($file) {
            $path = $file->store('dokumen_aset', 'public');
        }

        DB::table('dokumen_aset')->insert([
            'id_aset' => $id,
            'jenis_dokumen' => $data['jenis_dokumen'],
            'status_dokumen' => $data['status_dokumen'] ?? 'Asli',
            'file_path' => $path,
            'uploaded_at' => now(),
        ]);

        Activity::logSipat("Mengunggah dokumen pendukung ({$data['jenis_dokumen']}) untuk aset tanah: {$aset->nama_aset}", 'success');
    }
}
