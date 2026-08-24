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
        $query = AsetTanah::with(['latestProses.statusProses', 'opdSipat']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('kode_aset', 'LIKE', "%{$search}%")
                  ->orWhere('nama_aset', 'LIKE', "%{$search}%")
                  ->orWhere('opd', 'LIKE', "%{$search}%")
                  ->orWhereHas('opdSipat', function ($opdQuery) use ($search) {
                      $opdQuery->where('nama', 'LIKE', "%{$search}%");
                  })
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

        $opdFilter = $filters['opd_id'] ?? ($filters['opd'] ?? '');
        if ($opdFilter !== '') {
            if ($opdFilter === 'KOSONG') {
                // Tampilkan aset yang opd_id-nya NULL (belum terpetakan ke master OPD)
                // Termasuk data lama yang nama OPD-nya tidak cocok saat migrasi
                $query->where(function($q) {
                    $q->whereNull('opd_id')
                      ->orWhere(function ($q2) {
                          // Juga tangkap data yang opd_id ada tapi kolom opd kosong
                          $q2->whereNull('opd')->orWhere('opd', '');
                      });
                });
            } else {
                if (is_numeric($opdFilter)) {
                    $query->where('opd_id', (int) $opdFilter);
                } else {
                    $query->where('opd', $opdFilter);
                }
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
        $this->syncLegacyOpdLabel($data);
        $aset = AsetTanah::create($data);

        if ($initialStatusId) {
            ProsesAset::create([
                'id_aset' => $aset->id_aset,
                'id_status' => $initialStatusId,
                'tanggal_proses' => $data['tanggal_perolehan'] ?? date('Y-m-d'),
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
        $aset = AsetTanah::with(['prosesAset.statusProses', 'latestProses.statusProses', 'opdSipat'])->findOrFail($id);
        
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
        $this->syncLegacyOpdLabel($data);
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
        $tglProses = $data['tanggal_proses'] ?? ($data['tgl_mulai'] ?? date('Y-m-d'));

        $proses = ProsesAset::create([
            'id_aset'        => $aset->id_aset,
            'id_status'      => $data['id_status'],
            'tanggal_proses' => $tglProses,
            'tgl_mulai'      => $tglProses,
            'tgl_selesai'    => $data['tgl_selesai'] ?? null,
            'keterangan'     => $data['keterangan'] ?? null,
            'durasi_hari'    => null,
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

    /**
     * Menjaga kolom legacy `opd` tetap selaras dengan relasi `opd_id`.
     */
    private function syncLegacyOpdLabel(array &$data): void
    {
        if (empty($data['opd_id'])) {
            return;
        }

        $opd = OpdSipat::find($data['opd_id']);
        if ($opd) {
            $data['opd'] = $opd->nama;
        }
    }

    /**
     * Menganalisis dan mendeteksi daftar aset tanah ganda/identik di database.
     *
     * @return array
     */
    public function getDuplicateAsetList(): array
    {
        $asets = AsetTanah::all();
        $duplicates = [];

        foreach ($asets as $a) {
            $kode = $a->kode_aset;
            if (!$kode) continue;

            // 1. Deteksi jika kode_aset berakhir dengan (2), (3), dst.
            if (preg_match('/^(.+?)\s*\(\d+\)$/', $kode, $matches)) {
                $originalKode = trim($matches[1]);
                $originalAset = AsetTanah::where('kode_aset', $originalKode)
                    ->where('id_aset', '!=', $a->id_aset)
                    ->first();

                if ($originalAset) {
                    $duplicates[] = [
                        'duplicate_aset' => $a,
                        'original_aset'  => $originalAset,
                        'reason'         => "NIB terindikasi ganda hasil impor: \"{$kode}\" vs \"{$originalKode}\""
                    ];
                }
            }
        }

        // 2. Deteksi duplikasi berdasarkan kode_aset yang dibersihkan sama persis secara global
        $cleanCodes = [];
        foreach ($asets as $a) {
            $kode = $a->kode_aset;
            if (!$kode || preg_match('/\(\d+\)$/', $kode)) continue;
            
            $clean = preg_replace('/[^a-zA-Z0-9]/', '', $kode);
            if ($clean === '') continue;
            
            $cleanCodes[$clean][] = $a;
        }

        foreach ($cleanCodes as $clean => $list) {
            if (count($list) > 1) {
                $original = $list[0];
                for ($i = 1; $i < count($list); $i++) {
                    $dup = $list[$i];
                    
                    $alreadyAdded = collect($duplicates)->contains(function($item) use ($dup) {
                        return $item['duplicate_aset']->id_aset === $dup->id_aset;
                    });

                    if (!$alreadyAdded) {
                        $duplicates[] = [
                            'duplicate_aset' => $dup,
                            'original_aset'  => $original,
                            'reason'         => "NIB identik ganda setelah dibersihkan: \"{$dup->kode_aset}\""
                        ];
                    }
                }
            }
        }

        return $duplicates;
    }

    /**
     * Menganalisis dan mendeteksi daftar OPD/Dinas SIPAT yang terindikasi ganda atau mirip.
     *
     * @return array
     */
    public function getDuplicateOpdSipatList(): array
    {
        $opds = OpdSipat::all();
        $duplicates = [];
        $checked = [];

        foreach ($opds as $opdA) {
            if (in_array($opdA->id, $checked)) continue;

            foreach ($opds as $opdB) {
                if ($opdA->id === $opdB->id) continue;
                if (in_array($opdB->id, $checked)) continue;

                $nameA = strtoupper(trim($opdA->nama));
                $nameB = strtoupper(trim($opdB->nama));

                $cleanA = trim(str_replace(['DINAS', 'KANTOR', 'BADAN', 'KABUPATEN', 'KOTA', 'KAB', 'UPTD'], '', $nameA));
                $cleanB = trim(str_replace(['DINAS', 'KANTOR', 'BADAN', 'KABUPATEN', 'KOTA', 'KAB', 'UPTD'], '', $nameB));

                $isDuplicate = false;
                $reason = "";

                if ($nameA === $nameB) {
                    $isDuplicate = true;
                    $reason = "Nama OPD sama persis: \"{$opdA->nama}\"";
                } elseif (!empty($cleanA) && !empty($cleanB) && strlen($cleanA) > 3 && ($cleanA === $cleanB)) {
                    $isDuplicate = true;
                    $reason = "Indikasi kemiripan nama instansi: \"{$opdA->nama}\" vs \"{$opdB->nama}\"";
                }

                if ($isDuplicate) {
                    $countA = AsetTanah::where('opd_id', $opdA->id)->count();
                    $countB = AsetTanah::where('opd_id', $opdB->id)->count();

                    $duplicates[] = [
                        'opd_a'   => $opdA,
                        'opd_b'   => $opdB,
                        'count_a' => $countA,
                        'count_b' => $countB,
                        'reason'  => $reason
                    ];

                    $checked[] = $opdB->id;
                }
            }
            $checked[] = $opdA->id;
        }

        return $duplicates;
    }

    /**
     * Menggabungkan data dari aset ganda ke aset asli (mengisi kolom kosong) lalu menghapus yang ganda.
     *
     * @param int $originalId
     * @param int $duplicateId
     * @return bool
     */
    public function mergeAset(int $originalId, int $duplicateId): bool
    {
        return DB::transaction(function () use ($originalId, $duplicateId) {
            $original = AsetTanah::find($originalId);
            $duplicate = AsetTanah::find($duplicateId);

            if (!$original || !$duplicate) return false;

            // 1. Pindahkan riwayat proses sertifikasi
            ProsesAset::where('id_aset', $duplicateId)->update(['id_aset' => $originalId]);

            // 2. Pindahkan surat SKPT
            DB::table('surat_skpt')->where('aset_tanah_id', $duplicateId)->update(['aset_tanah_id' => $originalId]);

            // 3. Pindahkan dokumen lampiran
            DB::table('dokumen_aset')->where('id_aset', $duplicateId)->update(['id_aset' => $originalId]);

            // 4. Pindahkan pengamanan fisik
            $originalPengamanan = DB::table('pengamanan_fisik')->where('id_aset', $originalId)->first();
            $duplicatePengamanan = DB::table('pengamanan_fisik')->where('id_aset', $duplicateId)->first();
            if (!$originalPengamanan && $duplicatePengamanan) {
                DB::table('pengamanan_fisik')->where('id_aset', $duplicateId)->update(['id_aset' => $originalId]);
            } else {
                DB::table('pengamanan_fisik')->where('id_aset', $duplicateId)->delete();
            }

            // 5. Salin atribut kosong pada data asli dari data ganda
            $fields = [
                'kode_aset', 'nama_aset', 'peruntukan', 'luas', 'alamat', 'lat', 'lng', 
                'opd_id', 'opd', 'dasar_perolehan', 'harga_perolehan', 'tanggal_perolehan', 'keterangan'
            ];

            $updated = false;
            foreach ($fields as $field) {
                if (empty($original->{$field}) && !empty($duplicate->{$field})) {
                    $original->{$field} = $duplicate->{$field};
                    $updated = true;
                }
            }

            if ($updated) {
                $original->save();
            }

            // 6. Hapus data ganda
            $duplicate->delete();

            $this->sipatService->invalidateDashboardCache();

            return true;
        });
    }

    /**
     * Menggabungkan OPD duplikat SIPAT.
     *
     * @param int $targetId
     * @param int $sourceId
     * @return bool
     */
    public function mergeOpdSipat(int $targetId, int $sourceId): bool
    {
        return DB::transaction(function () use ($targetId, $sourceId) {
            $target = OpdSipat::find($targetId);
            $source = OpdSipat::find($sourceId);

            if (!$target || !$source) return false;

            // 1. Pindahkan seluruh aset tanah ke OPD target
            AsetTanah::where('opd_id', $sourceId)->update([
                'opd_id' => $targetId,
                'opd'    => $target->nama
            ]);

            // 2. Perbarui mapping OPD
            DB::table('opd_mappings')->where('sipat_opd_id', $sourceId)->update([
                'sipat_opd_id' => $targetId
            ]);

            // 3. Pindahkan dokumen BPKB/Sertifikat di eLABEL
            if (Schema::hasTable('elabel_bpkb')) {
                DB::table('elabel_bpkb')->where('sipat_opd_id', $sourceId)->update(['sipat_opd_id' => $targetId]);
            }
            if (Schema::hasTable('elabel_sertifikat_tanah')) {
                DB::table('elabel_sertifikat_tanah')->where('sipat_opd_id', $sourceId)->update(['sipat_opd_id' => $targetId]);
            }
            if (Schema::hasTable('elabel_surat_penyerahan')) {
                DB::table('elabel_surat_penyerahan')->where('sipat_opd_id', $sourceId)->update(['sipat_opd_id' => $targetId]);
            }
            if (Schema::hasTable('elabel_loans')) {
                DB::table('elabel_loans')->where('sipat_opd_id', $sourceId)->update(['sipat_opd_id' => $targetId]);
            }

            // 4. Hapus OPD sumber
            $source->delete();

            $this->sipatService->invalidateDashboardCache();

            return true;
        });
    }
}

