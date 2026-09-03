<?php

namespace App\Reports\Strategies;

use App\Reports\Contracts\ReportStrategy;
use App\Reports\Contracts\PostProcessesReportRows;
use App\Models\Vehicle;
use App\Models\EbmdVehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Strategi Laporan khusus untuk mendeteksi Kendaraan Dinas Ganda/Identik.
 * 
 * Menyaring data kendaraan yang memiliki kecocokan plat nomor, nomor mesin, atau nomor rangka secara ganda.
 */
class DuplicateVehicleReport implements ReportStrategy, PostProcessesReportRows
{
    /**
     * Membangun kueri basis data untuk mendeteksi kendaraan ganda/identik.
     *
     * @param array<string, mixed> $filters Kumpulan filter pencarian (opd_id, source)
     * @return Builder Kueri Eloquent ter-eager load untuk mencegah N+1
     */
    public function query(array $filters): Builder
    {
        $modelClass = ($filters['source'] ?? 'real') === 'ebmd' ? EbmdVehicle::class : Vehicle::class;

        // 1. Ambil HANYA plat nomor yang memiliki suffix ganda hasil impor (mengandung '(').
        // Ini sangat skalabel karena hanya menarik segelintir baris terduplikasi saja (bukan seluruh tabel).
        $duplicatePlates = $modelClass::withoutGlobalScopes()
            ->where('no_polisi', 'like', '%(%')
            ->pluck('no_polisi');

        // 2. Bersihkan suffix untuk mendapatkan list base plates unik
        $basePlates = $duplicatePlates->map(function($plate) {
            if (preg_match('/^(.+?)\s*\(\d+\)$/', $plate, $matches)) {
                return trim($matches[1]);
            }
            return $plate;
        })->unique()->filter()->toArray();

        // 3. Ambil nomor mesin yang terdeteksi ganda (patuh ONLY_FULL_GROUP_BY)
        $duplicateEngines = $modelClass::withoutGlobalScopes()
            ->select('no_mesin')
            ->whereNotNull('no_mesin')
            ->whereNotIn('no_mesin', ['', '-'])
            ->groupBy('no_mesin')
            ->havingRaw('count(*) > 1')
            ->pluck('no_mesin')
            ->toArray();

        // 4. Ambil nomor rangka yang terdeteksi ganda (patuh ONLY_FULL_GROUP_BY)
        $duplicateRangkas = $modelClass::withoutGlobalScopes()
            ->select('no_rangka')
            ->whereNotNull('no_rangka')
            ->whereNotIn('no_rangka', ['', '-'])
            ->groupBy('no_rangka')
            ->havingRaw('count(*) > 1')
            ->pluck('no_rangka')
            ->toArray();

        // 5. Bangun kueri Eloquent utama
        $query = $modelClass::withoutGlobalScopes()
            ->with(['opdRelation', 'vehicleType'])
            ->select([
                'id',
                'no_polisi',
                'nomor_register',
                'merk',
                'tipe',
                'jenis',
                'status',
                'kondisi',
                'opd_id',
                'opd',
                'pemegang',
                'nilai_perolehan',
                'no_mesin',
                'no_rangka',
            ]);

        // Terapkan Filter Instansi OPD jika dipilih
        if (!empty($filters['opd_id'])) {
            $query->where('opd_id', $filters['opd_id']);
        }

        // Cari kendaraan yang terindikasi duplikat
        $query->where(function($q) use ($basePlates, $duplicateEngines, $duplicateRangkas) {
            $q->where(function($inner) use ($basePlates) {
                if (!empty($basePlates)) {
                    $inner->whereIn('no_polisi', $basePlates)
                          ->orWhere(function($subInner) use ($basePlates) {
                              foreach ($basePlates as $bp) {
                                  $subInner->orWhere('no_polisi', 'like', $bp . ' (%');
                              }
                          });
                } else {
                    $inner->whereRaw('1 = 0');
                }
            });

            // Skenario B: Nomor Mesin ganda
            if (!empty($duplicateEngines)) {
                $q->orWhereIn('no_mesin', $duplicateEngines);
            }

            // Skenario C: Nomor Rangka ganda
            if (!empty($duplicateRangkas)) {
                $q->orWhereIn('no_rangka', $duplicateRangkas);
            }
        });

        return $query->orderBy('no_polisi');
    }

    /**
     * Membangun kueri referensi global (tanpa filter OPD) untuk mendeteksi pasangan duplikat lintas OPD.
     *
     * @param array<string, mixed> $filters Kumpulan filter pencarian
     * @return Builder
     */
    public function referenceQuery(array $filters): Builder
    {
        $referenceFilters = $filters;
        unset($referenceFilters['opd_id']);

        return $this->query($referenceFilters);
    }


    /**
     * Melakukan pengayaan data dinamis (keterangan_duplikat & duplicate_group_key) secara in-memory (0% N+1).
     *
     * @param \Illuminate\Support\Collection $vehicles Koleksi kendaraan dinas hasil kueri
     * @param \Illuminate\Support\Collection|null $referenceRows Koleksi referensi penuh untuk menemukan pasangan duplikat
     * @return void
     */
    public function postProcess(Collection $vehicles, ?Collection $referenceRows = null): void
    {
        if ($vehicles->isEmpty()) {
            return;
        }

        // Dataset referensi dikirim eksplisit oleh pemanggil:
        // preview memakai seluruh hasil query terfilter, sedangkan export/print memakai koleksi penuh yang sama.
        $allDuplicates = $referenceRows ?? $vehicles;

        foreach ($vehicles as $vehicle) {
            $plate = $vehicle->no_polisi;
            $basePlate = $plate;
            if (preg_match('/^(.+?)\s*\(\d+\)$/', $plate, $matches)) {
                $basePlate = trim($matches[1]);
            }

            // Cari pasangan ganda yang cocok di memori PHP
            $dups = $allDuplicates->filter(function($dup) use ($vehicle, $basePlate) {
                if ($dup->id === $vehicle->id) {
                    return false;
                }

                // Cocokkan Plat
                $dupBase = $dup->no_polisi;
                if (preg_match('/^(.+?)\s*\(\d+\)$/', $dup->no_polisi, $dupMatches)) {
                    $dupBase = trim($dupMatches[1]);
                }
                if ($basePlate === $dupBase) {
                    return true;
                }

                // Cocokkan No. Mesin
                if (!empty($vehicle->no_mesin) && !in_array($vehicle->no_mesin, ['', '-'])) {
                    if ($vehicle->no_mesin === $dup->no_mesin) {
                        return true;
                    }
                }

                // Cocokkan No. Rangka
                if (!empty($vehicle->no_rangka) && !in_array($vehicle->no_rangka, ['', '-'])) {
                    if ($vehicle->no_rangka === $dup->no_rangka) {
                        return true;
                    }
                }

                return false;
            });

            if ($dups->isEmpty()) {
                $vehicle->keterangan_duplikat = 'Tidak terdeteksi duplikasi';
                $vehicle->duplicate_group_key = 'none_' . $vehicle->id;
                continue;
            }

            $matchesList = [];
            $groupKeys = [];

            foreach ($dups as $dup) {
                $dupBase = $dup->no_polisi;
                if (preg_match('/^(.+?)\s*\(\d+\)$/', $dup->no_polisi, $dupMatches)) {
                    $dupBase = trim($dupMatches[1]);
                }

                $identicalFields = [];
                if ($basePlate === $dupBase) {
                    $identicalFields[] = 'Plat';
                    $groupKeys[] = 'plate_' . md5(strtolower($basePlate));
                }
                if (!empty($vehicle->no_mesin) && $vehicle->no_mesin === $dup->no_mesin) {
                    $identicalFields[] = 'No. Mesin';
                    $groupKeys[] = 'engine_' . md5(strtolower($vehicle->no_mesin));
                }
                if (!empty($vehicle->no_rangka) && $vehicle->no_rangka === $dup->no_rangka) {
                    $identicalFields[] = 'No. Rangka';
                    $groupKeys[] = 'chassis_' . md5(strtolower($vehicle->no_rangka));
                }

                if (!empty($identicalFields)) {
                    $opdName = $dup->opdRelation?->singkatan
                        ?? $dup->opdRelation?->nama
                        ?? $dup->opd
                        ?? 'Tanpa OPD';
                    $matchesList[] = implode(' & ', $identicalFields) . ' dengan ' . $dup->no_polisi . ' (' . $opdName . ')';
                }
            }

            if (!empty($matchesList)) {
                $vehicle->keterangan_duplikat = 'Identik ' . implode('; ', $matchesList);
            } else {
                $vehicle->keterangan_duplikat = 'Terindikasi ganda';
            }

            // Tetapkan duplicate_group_key grup pertama yang cocok
            $vehicle->duplicate_group_key = !empty($groupKeys) ? $groupKeys[0] : 'none_' . $vehicle->id;
        }
    }

    /**
     * Mendapatkan daftar judul kolom (headers) untuk Laporan Kendaraan Ganda/Identik.
     *
     * @return array<string, string> Asosiasi nama kolom ke label Bahasa Indonesia
     */
    public function headers(): array
    {
        return [
            'no_polisi'           => 'Plat Nomor',
            'nomor_register'      => 'Nomor Register',
            'merk'                => 'Merek',
            'tipe'                => 'Tipe',
            'jenis'               => 'Jenis Kendaraan',
            'opd'                 => 'Instansi Pengelola',
            'pemegang'            => 'Pemegang / Penanggung Jawab',
            'no_mesin'            => 'No. Mesin',
            'no_rangka'           => 'No. Rangka',
            'keterangan_duplikat' => 'Analisis Identik / Keterangan Ganda',
        ];
    }
}
