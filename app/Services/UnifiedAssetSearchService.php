<?php

namespace App\Services;

use App\Enums\VehicleCondition;
use App\Enums\VehicleStatus;
use App\Models\AsetTanah;
use App\Models\Desa;
use App\Models\Elabel\ElabelBox;
use App\Models\Elabel\ElabelBpkb;
use App\Models\Elabel\ElabelSertifikat;
use App\Models\Elabel\ElabelSertifikatBox;
use App\Models\Elabel\ElabelSuratPenyerahan;
use App\Models\Kecamatan;
use App\Models\Opd;
use App\Models\OpdSipat;
use App\Models\StatusProses;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UnifiedAssetSearchService
{
    /**
     * Mengambil statistik ringkasan portal untuk landing page (dengan caching).
     *
     * @return array
     */
    public function getPortalStats(): array
    {
        return Cache::remember('sipat_landing_portal_stats', 300, function () {
            $totalKendaraan = Vehicle::withoutGlobalScopes()->count();
            $totalTanah = AsetTanah::count();
            $totalBpkb = ElabelBpkb::count();
            $totalSertifikat = ElabelSertifikat::count();
            $totalPenyerahan = ElabelSuratPenyerahan::count();
            $totalArsip = $totalBpkb + $totalSertifikat + $totalPenyerahan;
            $totalAset = $totalKendaraan + $totalTanah;

            // Integrasikan langsung dengan Rekap Resmi SIPAT Dashboard agar 100% identik & real-time
            $sipatService = app(\App\Services\SipatService::class);
            $sipatStats = $sipatService->getDashboardStats();

            $bersertifikat = (int) ($sipatStats['asetBersertifikat'] ?? 396);
            $proses = (int) (($sipatStats['asetProses'] ?? 54) + ($sipatStats['asetKendala'] ?? 23));
            $belum = (int) ($sipatStats['asetBelumDiurus'] ?? 716);
            $persenTerbit = $totalTanah > 0 ? round(($bersertifikat / $totalTanah) * 100, 1) : 0;

            return [
                'total_aset' => $totalAset,
                'total_kendaraan' => $totalKendaraan,
                'total_tanah' => $totalTanah,
                'total_arsip' => $totalArsip,
                'sertifikasi' => [
                    'bersertifikat' => $bersertifikat,
                    'proses' => $proses,
                    'belum' => $belum,
                    'total' => $totalTanah,
                    'persen_terbit' => $persenTerbit,
                ],
            ];
        });
    }

    /**
     * Mengambil data master untuk opsi filter pada form pencarian.
     *
     * @return array
     */
    public function getFilterOptions(): array
    {
        return Cache::remember('sipat_landing_filter_options', 600, function () {
            // OPD gabungan untuk filter
            $opdsSipat = OpdSipat::orderBy('nama')->get(['id', 'nama']);
            $opdsErandis = Opd::orderBy('nama')->get(['id', 'nama']);

            // Vehicle types & statuses
            $vehicleTypes = VehicleType::orderBy('name')->pluck('name')->toArray();
            $vehicleStatuses = array_map(fn($case) => $case->value, VehicleStatus::cases());

            // Status proses sertifikasi tanah
            $statusProses = StatusProses::orderBy('urutan')->get(['id_status', 'nama_status', 'kategori']);

            // Wilayah
            $kecamatans = Kecamatan::orderBy('nama')->get(['id', 'nama']);
            $desas = Desa::orderBy('nama')->get(['id', 'kecamatan_id', 'nama']);

            // Box arsip
            $boxesBpkb = ElabelBox::orderBy('box_code')->get(['id', 'box_code', 'location']);
            $boxesSertifikat = ElabelSertifikatBox::orderBy('box_code')->get(['id', 'box_code', 'lokasi']);

            return [
                'opd_sipat' => $opdsSipat,
                'opd_erandis' => $opdsErandis,
                'vehicle_types' => $vehicleTypes,
                'vehicle_statuses' => $vehicleStatuses,
                'status_proses' => $statusProses,
                'kecamatans' => $kecamatans,
                'desas' => $desas,
                'boxes_bpkb' => $boxesBpkb,
                'boxes_sertifikat' => $boxesSertifikat,
            ];
        });
    }

    /**
     * Pencarian publik Kendaraan Dinas (E-RANDIS).
     *
     * @param array $params
     * @return array
     */
    public function searchVehicles(array $params): array
    {
        $query = trim((string) ($params['q'] ?? ''));
        $searchBy = $params['search_by'] ?? 'all'; // no_polisi, nibar, kode_barang, all
        $opd = $params['opd'] ?? null;
        $jenis = $params['jenis'] ?? null;
        $status = $params['status'] ?? null;
        $limit = min((int) ($params['limit'] ?? 10), 30);

        if ($query === '' && empty($opd) && empty($jenis) && empty($status)) {
            return [
                'found' => false,
                'count' => 0,
                'query' => $query,
                'data' => [],
            ];
        }

        $builder = Vehicle::withoutGlobalScopes();

        if ($query !== '') {
            $cleanPlate = strtoupper(preg_replace('/[^A-Z0-9\s]/', '', $query));
            $cleanPlate = preg_replace('/\s+/', ' ', $cleanPlate);

            if ($searchBy === 'no_polisi') {
                $builder->where(function ($q) use ($query, $cleanPlate) {
                    $q->where('no_polisi', 'LIKE', "%{$cleanPlate}%")
                      ->orWhere('no_polisi', 'LIKE', "%{$query}%");
                });
            } elseif ($searchBy === 'nibar') {
                $builder->where('nomor_register', 'LIKE', "%{$query}%");
            } elseif ($searchBy === 'kode_barang') {
                $builder->where(function ($q) use ($query) {
                    $q->where('merk', 'LIKE', "%{$query}%")
                      ->orWhere('tipe', 'LIKE', "%{$query}%")
                      ->orWhere('nomor_register', 'LIKE', "%{$query}%");
                });
            } else {
                // 'all'
                $builder->where(function ($q) use ($query, $cleanPlate) {
                    $q->where('no_polisi', 'LIKE', "%{$cleanPlate}%")
                      ->orWhere('no_polisi', 'LIKE', "%{$query}%")
                      ->orWhere('nomor_register', 'LIKE', "%{$query}%")
                      ->orWhere('merk', 'LIKE', "%{$query}%")
                      ->orWhere('tipe', 'LIKE', "%{$query}%");
                });
            }
        }

        if (!empty($opd)) {
            $builder->where(function ($q) use ($opd) {
                $q->where('opd', $opd)
                  ->orWhere('opd_id', $opd);
            });
        }

        if (!empty($jenis)) {
            $builder->where('jenis', $jenis);
        }

        if (!empty($status)) {
            $builder->where('status', $status);
        }

        $results = $builder->orderBy('no_polisi', 'asc')->limit($limit)->get();

        // Cross-module linkage check: Cek BPKB di eLABEL
        $plates = $results->pluck('no_polisi')->filter()->toArray();
        $nibars = $results->pluck('nomor_register')->filter()->toArray();

        $bpkbMap = [];
        if (!empty($plates) || !empty($nibars)) {
            $bpkbItems = ElabelBpkb::with('box')
                ->where(function ($q) use ($plates, $nibars) {
                    if (!empty($plates)) {
                        $q->whereIn('plate_number', $plates);
                    }
                    if (!empty($nibars)) {
                        $q->orWhereIn('nibar', $nibars);
                    }
                })
                ->get();

            foreach ($bpkbItems as $b) {
                $cleanP = strtoupper(trim($b->plate_number));
                $boxLabel = $b->box ? "Box {$b->box->box_code}" : 'Arsip Tersedia';
                $bpkbMap[$cleanP] = [
                    'tersedia' => true,
                    'status' => $b->status ?? 'Tersedia',
                    'box_code' => $b->box->box_code ?? null,
                    'box_lokasi' => $b->box->location ?? null,
                    'no_bpkb' => $b->no_bpkb ? 'Tercatat' : null,
                ];
                if ($b->nibar) {
                    $bpkbMap[trim($b->nibar)] = $bpkbMap[$cleanP];
                }
            }
        }

        $formatted = $results->map(function ($v) use ($bpkbMap) {
            $cleanP = strtoupper(trim($v->no_polisi));
            $nibar = trim((string) $v->nomor_register);
            $bpkbInfo = $bpkbMap[$cleanP] ?? ($nibar !== '' ? ($bpkbMap[$nibar] ?? null) : null);

            return [
                'id' => $v->id,
                'no_polisi' => $v->no_polisi,
                'nama' => trim($v->merk . ' ' . $v->tipe),
                'merk' => $v->merk,
                'tipe' => $v->tipe,
                'jenis' => $v->jenis,
                'tahun' => $v->tahun_pembuatan,
                'nomor_register' => $v->nomor_register,
                'opd' => $v->opd,
                'pemegang' => $v->pemegang ?: '-',
                'kondisi' => VehicleCondition::tryFrom($v->kondisi)?->label() ?? $v->kondisi,
                'status' => VehicleStatus::tryFrom($v->status)?->label() ?? $v->status,
                'foto_kendaraan' => $v->foto_kendaraan,
                'arsip_bpkb' => $bpkbInfo ? [
                    'tersedia' => true,
                    'status_label' => '🟢 BPKB Tersedia' . ($bpkbInfo['box_code'] ? " ({$bpkbInfo['box_code']})" : ''),
                    'box_code' => $bpkbInfo['box_code'],
                    'status' => $bpkbInfo['status'],
                ] : [
                    'tersedia' => false,
                    'status_label' => '⚪ Belum Diarsipkan di Box',
                    'box_code' => null,
                    'status' => 'Belum Ada',
                ],
            ];
        });

        return [
            'found' => $formatted->isNotEmpty(),
            'count' => $formatted->count(),
            'query' => $query,
            'data' => $formatted,
        ];
    }

    /**
     * Pencarian publik Sertifikat Tanah (SIPAT).
     *
     * @param array $params
     * @return array
     */
    public function searchLand(array $params): array
    {
        $query = trim((string) ($params['q'] ?? ''));
        $searchBy = $params['search_by'] ?? 'all'; // nibar, no_sertifikat, nib_nama, all
        $opd = $params['opd'] ?? null;
        $statusSertifikasi = $params['status_sertifikasi'] ?? null;
        $kecamatan = $params['kecamatan'] ?? null;
        $desa = $params['desa'] ?? null;
        $limit = min((int) ($params['limit'] ?? 10), 30);

        if ($query === '' && empty($opd) && empty($statusSertifikasi) && empty($kecamatan) && empty($desa)) {
            return [
                'found' => false,
                'count' => 0,
                'query' => $query,
                'data' => [],
            ];
        }

        $builder = AsetTanah::with(['opdSipat', 'latestProses.statusProses']);

        if ($query !== '') {
            if ($searchBy === 'nibar') {
                $builder->where('kode_aset', 'LIKE', "%{$query}%");
            } elseif ($searchBy === 'no_sertifikat') {
                // Cari tanah yang punya relasi ke elabel sertifikat atau proses_aset dengan no sertifikat
                $builder->where(function ($q) use ($query) {
                    $q->where('kode_aset', 'LIKE', "%{$query}%")
                      ->orWhereExists(function ($sub) use ($query) {
                          $sub->select(DB::raw(1))
                              ->from('elabel_sertifikat_tanah')
                              ->whereColumn('elabel_sertifikat_tanah.nibar', 'aset_tanah.kode_aset')
                              ->where('elabel_sertifikat_tanah.no_sertipikat', 'LIKE', "%{$query}%");
                      })
                      ->orWhereExists(function ($sub) use ($query) {
                          $sub->select(DB::raw(1))
                              ->from('proses_aset')
                              ->whereColumn('proses_aset.id_aset', 'aset_tanah.id_aset')
                              ->where('proses_aset.keterangan', 'LIKE', "%{$query}%");
                      });
                });
            } elseif ($searchBy === 'nib_nama') {
                $builder->where(function ($q) use ($query) {
                    $q->where('nama_aset', 'LIKE', "%{$query}%")
                      ->orWhere('peruntukan', 'LIKE', "%{$query}%")
                      ->orWhere('kode_aset', 'LIKE', "%{$query}%");
                });
            } else {
                // all
                $builder->where(function ($q) use ($query) {
                    $q->where('kode_aset', 'LIKE', "%{$query}%")
                      ->orWhere('nama_aset', 'LIKE', "%{$query}%")
                      ->orWhere('peruntukan', 'LIKE', "%{$query}%")
                      ->orWhere('alamat', 'LIKE', "%{$query}%")
                      ->orWhereExists(function ($sub) use ($query) {
                          $sub->select(DB::raw(1))
                              ->from('elabel_sertifikat_tanah')
                              ->whereColumn('elabel_sertifikat_tanah.nibar', 'aset_tanah.kode_aset')
                              ->where('elabel_sertifikat_tanah.no_sertipikat', 'LIKE', "%{$query}%");
                      });
                });
            }
        }

        if (!empty($opd)) {
            $builder->where(function ($q) use ($opd) {
                $q->where('opd_id', $opd)
                  ->orWhere('opd', 'LIKE', "%{$opd}%");
            });
        }

        if (!empty($kecamatan)) {
            $builder->where('alamat', 'LIKE', "%{$kecamatan}%");
        }

        if (!empty($desa)) {
            $builder->where('alamat', 'LIKE', "%{$desa}%");
        }

        $results = $builder->orderBy('id_aset', 'desc')->limit($limit)->get();

        // Cross-module linkage check: Cek Sertifikat Fisik di eLABEL
        $nibars = $results->pluck('kode_aset')->filter()->toArray();
        $certMap = [];

        if (!empty($nibars)) {
            $certs = ElabelSertifikat::with('box')
                ->whereIn('nibar', $nibars)
                ->get();

            foreach ($certs as $c) {
                $certMap[trim($c->nibar)] = [
                    'tersedia' => true,
                    'no_sertipikat' => $c->no_sertipikat,
                    'box_code' => $c->box->box_code ?? null,
                    'box_lokasi' => $c->box->lokasi ?? null,
                    'status_penggunaan' => $c->status_penggunaan,
                ];
            }
        }

        $sipatService = app(SipatService::class);

        $formatted = $results->map(function ($item) use ($certMap, $sipatService) {
            $nibar = trim((string) $item->kode_aset);
            $certInfo = $certMap[$nibar] ?? null;

            // Tentukan status sertifikasi
            $latestProses = $item->latestProses;
            $namaStatus = $latestProses && $latestProses->statusProses ? $latestProses->statusProses->nama_status : 'Belum Diurus';
            $kategoriRaw = $latestProses && $latestProses->statusProses ? $latestProses->statusProses->kategori : null;

            if ($certInfo && $namaStatus === 'Belum Diurus') {
                $namaStatus = 'Sertifikat Terbit';
                $kategoriRaw = 'bersertifikat';
            }

            $category = $sipatService->getStatusCategory($namaStatus, $kategoriRaw);

            $statusBadge = match ($category) {
                'bersertifikat' => ['label' => '🟢 ' . ($namaStatus !== 'Belum Diurus' ? $namaStatus : 'Sertifikat Terbit'), 'class' => 'bg-success-subtle text-success border border-success-subtle'],
                'proses' => ['label' => '🟡 ' . $namaStatus, 'class' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'],
                'kendala' => ['label' => '🔴 ' . $namaStatus, 'class' => 'bg-danger-subtle text-danger border border-danger-subtle'],
                default => ['label' => '⚪ ' . $namaStatus, 'class' => 'bg-secondary-subtle text-secondary border border-secondary-subtle'],
            };

            return [
                'id_aset' => $item->id_aset,
                'nibar' => $item->kode_aset,
                'nama_aset' => $item->nama_aset ?: 'Tanah Aset Pemerintah',
                'peruntukan' => $item->peruntukan ?: '-',
                'luas' => $item->luas ? number_format($item->luas, 0, ',', '.') . ' m²' : '-',
                'alamat' => $item->alamat ?: '-',
                'opd' => $item->opdSipat->nama ?? ($item->opd ?: '-'),
                'status_sertifikasi' => $statusBadge['label'],
                'status_badge_class' => $statusBadge['class'],
                'status_kategori' => $category,
                'arsip_sertifikat' => $certInfo ? [
                    'tersedia' => true,
                    'status_label' => '🟢 Sertifikat Fisik Tersedia' . ($certInfo['box_code'] ? " ({$certInfo['box_code']})" : ''),
                    'no_sertipikat' => $certInfo['no_sertipikat'],
                    'box_code' => $certInfo['box_code'],
                ] : [
                    'tersedia' => false,
                    'status_label' => $category === 'bersertifikat' ? '🟡 Belum Disimpan di Box' : '⚪ Sertifikat Belum Terbit',
                    'no_sertipikat' => null,
                    'box_code' => null,
                ],
            ];
        });

        // Filter by status sertifikasi jika dipilih
        if (!empty($statusSertifikasi)) {
            $formatted = $formatted->filter(function ($item) use ($statusSertifikasi) {
                return $item['status_kategori'] === $statusSertifikasi ||
                       stripos($item['status_sertifikasi'], $statusSertifikasi) !== false;
            })->values();
        }

        return [
            'found' => $formatted->isNotEmpty(),
            'count' => $formatted->count(),
            'query' => $query,
            'data' => $formatted,
        ];
    }

    /**
     * Pencarian publik Ketersediaan Arsip (EARSIP / eLABEL).
     *
     * @param array $params
     * @return array
     */
    public function searchArchives(array $params): array
    {
        $query = trim((string) ($params['q'] ?? ''));
        $searchBy = $params['search_by'] ?? 'all'; // nibar, no_dokumen, kode_barang, all
        $opd = $params['opd'] ?? null;
        $docType = $params['doc_type'] ?? 'all'; // all, bpkb, sertifikat, penyerahan
        $statusArsip = $params['status_arsip'] ?? null;
        $boxLocation = $params['box_location'] ?? null;
        $limit = min((int) ($params['limit'] ?? 10), 30);

        if ($query === '' && empty($opd) && empty($statusArsip) && empty($boxLocation) && $docType === 'all') {
            return [
                'found' => false,
                'count' => 0,
                'query' => $query,
                'data' => [],
            ];
        }

        $results = collect();

        // 1. Search BPKB jika docType 'all' atau 'bpkb'
        if ($docType === 'all' || $docType === 'bpkb') {
            $bpkbBuilder = ElabelBpkb::with(['box', 'opdSipat']);

            if ($query !== '') {
                $cleanPlate = strtoupper(preg_replace('/[^A-Z0-9\s]/', '', $query));
                if ($searchBy === 'nibar') {
                    $bpkbBuilder->where('nibar', 'LIKE', "%{$query}%");
                } elseif ($searchBy === 'no_dokumen') {
                    $bpkbBuilder->where('no_bpkb', 'LIKE', "%{$query}%");
                } elseif ($searchBy === 'kode_barang') {
                    $bpkbBuilder->where(function ($q) use ($cleanPlate, $query) {
                        $q->where('plate_number', 'LIKE', "%{$cleanPlate}%")
                          ->orWhere('merek', 'LIKE', "%{$query}%")
                          ->orWhere('tipe', 'LIKE', "%{$query}%");
                    });
                } else {
                    $bpkbBuilder->where(function ($q) use ($cleanPlate, $query) {
                        $q->where('nibar', 'LIKE', "%{$query}%")
                          ->orWhere('no_bpkb', 'LIKE', "%{$query}%")
                          ->orWhere('plate_number', 'LIKE', "%{$cleanPlate}%")
                          ->orWhere('merek', 'LIKE', "%{$query}%")
                          ->orWhere('tipe', 'LIKE', "%{$query}%");
                    });
                }
            }

            if (!empty($opd)) {
                $bpkbBuilder->where(function ($q) use ($opd) {
                    $q->where('sipat_opd_id', $opd)
                      ->orWhere('pengguna', 'LIKE', "%{$opd}%");
                });
            }

            if (!empty($statusArsip)) {
                $bpkbBuilder->where('status', $statusArsip);
            }

            if (!empty($boxLocation)) {
                $bpkbBuilder->whereHas('box', function ($q) use ($boxLocation) {
                    $q->where('box_code', 'LIKE', "%{$boxLocation}%")
                      ->orWhere('location', 'LIKE', "%{$boxLocation}%");
                });
            }

            $bpkbs = $bpkbBuilder->limit($limit)->get();

            foreach ($bpkbs as $b) {
                $results->push([
                    'type' => 'BPKB Kendaraan',
                    'type_code' => 'bpkb',
                    'type_icon' => 'bi-car-front',
                    'type_badge' => 'bg-primary-subtle text-primary border border-primary-subtle',
                    'nibar' => $b->nibar ?: '-',
                    'no_dokumen' => $b->no_bpkb ? "BPKB: {$b->no_bpkb}" : ($b->plate_number ? "Plat: {$b->plate_number}" : 'Tercatat'),
                    'nama_aset' => trim(($b->merek ?: '') . ' ' . ($b->tipe ?: '')) ?: 'Kendaraan Dinas',
                    'identitas' => $b->plate_number ?: '-',
                    'opd' => $b->opdSipat->nama ?? ($b->pengguna ?: 'Pemerintah Daerah'),
                    'status_arsip' => $b->status ?: 'Tersedia',
                    'status_label' => $b->status === 'Tersedia' ? '🟢 Arsip Tersedia' : '🟡 ' . $b->status,
                    'box_code' => $b->box ? $b->box->box_code : '-',
                    'lokasi_box' => $b->box ? $b->box->location : '-',
                    'tahun' => $b->year ?: '-',
                ]);
            }
        }

        // 2. Search Sertifikat Tanah jika docType 'all' atau 'sertifikat'
        if (($docType === 'all' || $docType === 'sertifikat') && $results->count() < $limit) {
            $certBuilder = ElabelSertifikat::with(['box', 'opdSipat']);

            if ($query !== '') {
                if ($searchBy === 'nibar') {
                    $certBuilder->where('nibar', 'LIKE', "%{$query}%");
                } elseif ($searchBy === 'no_dokumen') {
                    $certBuilder->where('no_sertipikat', 'LIKE', "%{$query}%");
                } elseif ($searchBy === 'kode_barang') {
                    $certBuilder->where(function ($q) use ($query) {
                        $q->where('nibar', 'LIKE', "%{$query}%")
                          ->orWhere('spesifikasi', 'LIKE', "%{$query}%");
                    });
                } else {
                    $certBuilder->where(function ($q) use ($query) {
                        $q->where('nibar', 'LIKE', "%{$query}%")
                          ->orWhere('no_sertipikat', 'LIKE', "%{$query}%")
                          ->orWhere('nama_pemilik', 'LIKE', "%{$query}%")
                          ->orWhere('lokasi', 'LIKE', "%{$query}%")
                          ->orWhere('alamat', 'LIKE', "%{$query}%");
                    });
                }
            }

            if (!empty($opd)) {
                $certBuilder->where(function ($q) use ($opd) {
                    $q->where('sipat_opd_id', $opd)
                      ->orWhere('dinas', 'LIKE', "%{$opd}%");
                });
            }

            if (!empty($boxLocation)) {
                $certBuilder->whereHas('box', function ($q) use ($boxLocation) {
                    $q->where('box_code', 'LIKE', "%{$boxLocation}%")
                      ->orWhere('lokasi', 'LIKE', "%{$boxLocation}%");
                });
            }

            $certs = $certBuilder->limit($limit - $results->count())->get();

            foreach ($certs as $c) {
                $results->push([
                    'type' => 'Sertifikat Tanah',
                    'type_code' => 'sertifikat',
                    'type_icon' => 'bi-file-earmark-check',
                    'type_badge' => 'bg-success-subtle text-success border border-success-subtle',
                    'nibar' => $c->nibar ?: '-',
                    'no_dokumen' => "No: {$c->no_sertipikat}",
                    'nama_aset' => $c->nama_pemilik ?: 'Sertifikat Tanah Pemda',
                    'identitas' => $c->lokasi ?: ($c->alamat ?: '-'),
                    'opd' => $c->opdSipat->nama ?? ($c->dinas ?: 'Pemerintah Daerah'),
                    'status_arsip' => 'Tersedia',
                    'status_label' => '🟢 Arsip Tersedia',
                    'box_code' => $c->box ? $c->box->box_code : '-',
                    'lokasi_box' => $c->box ? $c->box->lokasi : '-',
                    'tahun' => $c->tanggal_perolehan ? date('Y', strtotime($c->tanggal_perolehan)) : '-',
                ]);
            }
        }

        // 3. Search Surat Penyerahan jika docType 'all' atau 'penyerahan'
        if (($docType === 'all' || $docType === 'penyerahan') && $results->count() < $limit) {
            $penyerahanBuilder = ElabelSuratPenyerahan::with(['box', 'opdSipat']);

            if ($query !== '') {
                $penyerahanBuilder->where(function ($q) use ($query) {
                    $q->where('nibar', 'LIKE', "%{$query}%")
                      ->orWhere('no_surat', 'LIKE', "%{$query}%")
                      ->orWhere('pemberi_hibah', 'LIKE', "%{$query}%")
                      ->orWhere('lokasi', 'LIKE', "%{$query}%");
                });
            }

            if (!empty($opd)) {
                $penyerahanBuilder->where(function ($q) use ($opd) {
                    $q->where('sipat_opd_id', $opd)
                      ->orWhere('dinas', 'LIKE', "%{$opd}%");
                });
            }

            $docs = $penyerahanBuilder->limit($limit - $results->count())->get();

            foreach ($docs as $d) {
                $results->push([
                    'type' => 'Surat Penyerahan',
                    'type_code' => 'penyerahan',
                    'type_icon' => 'bi-file-earmark-text',
                    'type_badge' => 'bg-info-subtle text-info border border-info-subtle',
                    'nibar' => $d->nibar ?: '-',
                    'no_dokumen' => "Surat: {$d->no_surat}",
                    'nama_aset' => $d->pemberi_hibah ? "Penyerahan dari {$d->pemberi_hibah}" : 'Dokumen Penyerahan Aset',
                    'identitas' => $d->lokasi ?: ($d->alamat ?: '-'),
                    'opd' => $d->opdSipat->nama ?? ($d->dinas ?: 'Pemerintah Daerah'),
                    'status_arsip' => 'Tersedia',
                    'status_label' => '🟢 Arsip Tersedia',
                    'box_code' => $d->box ? $d->box->box_code : '-',
                    'lokasi_box' => $d->box ? $d->box->lokasi : '-',
                    'tahun' => $d->tanggal_perolehan ? date('Y', strtotime($d->tanggal_perolehan)) : '-',
                ]);
            }
        }

        return [
            'found' => $results->isNotEmpty(),
            'count' => $results->count(),
            'query' => $query,
            'data' => $results->values(),
        ];
    }
}
