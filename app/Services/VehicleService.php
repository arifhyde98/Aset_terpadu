<?php

namespace App\Services;

use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

/**
 * Service untuk Logika Bisnis Kendaraan
 * 
 * Menangani operasi kompleks terkait data kendaraan seperti statistik dashboard,
 * pembersihan nomor polisi, dan pencarian khusus.
 */
class VehicleService
{
    /**
     * Mendapatkan statistik dashboard untuk kendaraan.
     * 
     * Data di-cache selama 5 menit untuk performa optimal.
     * 
     * @return array<string, int>
     */
    public function getDashboardStats(): array
    {
        $user = auth()->user();
        $cacheKey = 'dashboard.stats.' . ($user?->role?->value ?? 'guest') . '.' . ($user?->opd_id ?? 'global');

        return cache()->remember($cacheKey, 300, function () {
            // Menggunakan kueri agregasi tunggal untuk performa maksimal
            $stats = Vehicle::query()
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN kondisi = 'Baik' THEN 1 ELSE 0 END) as baik,
                    SUM(CASE WHEN kondisi = 'Rusak Ringan' THEN 1 ELSE 0 END) as rusak_ringan,
                    SUM(CASE WHEN kondisi = 'Rusak Berat' THEN 1 ELSE 0 END) as rusak_berat,
                    SUM(CASE WHEN kondisi IN ('Hilang', 'Dalam Penelusuran') THEN 1 ELSE 0 END) as hilang,
                    SUM(CASE WHEN status = 'Dipinjam' THEN 1 ELSE 0 END) as borrowed
                ")
                ->first();

            return [
                'total' => (int) ($stats->total ?? 0),
                'baik' => (int) ($stats->baik ?? 0),
                'available' => (int) ($stats->baik ?? 0),
                'rusak_ringan' => (int) ($stats->rusak_ringan ?? 0),
                'rusak_berat' => (int) ($stats->rusak_berat ?? 0),
                'hilang' => (int) ($stats->hilang ?? 0),
                'borrowed' => (int) ($stats->borrowed ?? 0),
            ];
        });
    }

    /**
     * Mendapatkan statistik e-BMD.
     * 
     * Data di-cache selama 5 menit.
     * 
     * @return array<string, int>
     */
    public function getEbmdStats(): array
    {
        $user = auth()->user();
        $cacheKey = 'ebmd.stats.' . ($user?->role?->value ?? 'guest') . '.' . ($user?->opd_id ?? 'global');

        return cache()->remember($cacheKey, 300, function () {
            // Hanya query `vehicle_type_id` yang lebih cepat daripada query join string
            $rawStats = \App\Models\EbmdVehicle::query()
                ->selectRaw('vehicle_type_id, count(*) as total')
                ->groupBy('vehicle_type_id')
                ->pluck('total', 'vehicle_type_id')
                ->toArray();
            
            // Query tipe kendaraan (cepat & sedikit row)
            $types = \App\Models\VehicleType::pluck('name', 'id')->toArray();

            $ebmdStats = [
                'Mobil' => 0,
                'Motor' => 0,
                'Lainnya' => 0
            ];

            foreach ($rawStats as $typeId => $total) {
                // Tangani kasus typeId null (kendaraan tak terhubung dengan type)
                $jenis = $typeId && isset($types[$typeId]) ? $types[$typeId] : 'Lainnya';
                
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

            return $ebmdStats;
        });
    }

    /**
     * Membersihkan cache statistik dashboard secara terarah.
     * 
     * Digunakan setelah operasi CRUD kendaraan atau OPD untuk memastikan
     * data di dashboard tetap akurat tanpa melakukan Cache::flush() global.
     * 
     * @param int|null $opdId ID OPD yang terdampak (opsional)
     * @param int|null $oldOpdId ID OPD lama jika terjadi perpindahan instansi (opsional)
     * @param bool $invalidateAllOpd Jika true, hapus semua cache statistik seluruh OPD
     * @return void
     */
    public function invalidateDashboardStats(?int $opdId = null, ?int $oldOpdId = null, bool $invalidateAllOpd = false): void
    {
        // 1. Selalu hapus key statistik global
        \Illuminate\Support\Facades\Cache::forget('dashboard.stats.superadmin.global');
        \Illuminate\Support\Facades\Cache::forget('dashboard.stats.admin.global');
        \Illuminate\Support\Facades\Cache::forget('dashboard.stats.guest.global');
        
        \Illuminate\Support\Facades\Cache::forget('ebmd.stats.superadmin.global');
        \Illuminate\Support\Facades\Cache::forget('ebmd.stats.admin.global');
        \Illuminate\Support\Facades\Cache::forget('ebmd.stats.guest.global');

        // 2. Hapus statistik OPD spesifik atau global OPD role jika ada
        if ($opdId) {
            \Illuminate\Support\Facades\Cache::forget("dashboard.stats.opd.{$opdId}");
            \Illuminate\Support\Facades\Cache::forget("ebmd.stats.opd.{$opdId}");
        }
        \Illuminate\Support\Facades\Cache::forget("dashboard.stats.opd.global");
        \Illuminate\Support\Facades\Cache::forget("ebmd.stats.opd.global");

        // 3. Hapus statistik OPD lama (kasus pindah instansi)
        if ($oldOpdId && $oldOpdId !== $opdId) {
            \Illuminate\Support\Facades\Cache::forget("dashboard.stats.opd.{$oldOpdId}");
            \Illuminate\Support\Facades\Cache::forget("ebmd.stats.opd.{$oldOpdId}");
        }

        // 4. Invalidation massal (untuk Import/Truncate/Hapus OPD)
        if ($invalidateAllOpd) {
            // Hapus semua cache yang mungkin ada untuk role OPD
            $opdIds = \App\Models\Opd::pluck('id');
            foreach ($opdIds as $id) {
                \Illuminate\Support\Facades\Cache::forget("dashboard.stats.opd.{$id}");
                \Illuminate\Support\Facades\Cache::forget("ebmd.stats.opd.{$id}");
            }
            
            // Tambahan: Pastikan cache admin/superadmin juga terhapus (sudah di poin 1 tapi dipertegas)
            \Illuminate\Support\Facades\Cache::forget('dashboard.stats.superadmin.global');
            \Illuminate\Support\Facades\Cache::forget('dashboard.stats.admin.global');
            \Illuminate\Support\Facades\Cache::forget('ebmd.stats.superadmin.global');
            \Illuminate\Support\Facades\Cache::forget('ebmd.stats.admin.global');
        }

        // 5. Selaraskan invalidasi cache summary laporan secara otomatis
        try {
            app(\App\Services\ReportService::class)->invalidateSummaryCache($opdId, $oldOpdId, $invalidateAllOpd);
        } catch (\Throwable $e) {
            // Safety fallback jika Service Container belum terikat sempurna, sertakan logging agar masalah terdeteksi
            \Illuminate\Support\Facades\Log::warning('Gagal menghapus cache summary laporan.', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Membersihkan dan memformat Nomor Polisi.
     * 
     * Mengubah ke huruf kapital, menghapus karakter non-alfanumerik,
     * dan merapikan spasi.
     * 
     * @param string|null $plate
     * @return string|null
     */
    public function formatPlateNumber(?string $plate): ?string
    {
        if (!$plate) return null;

        // 1. Ubah ke Uppercase & Trim
        $clean = strtoupper(trim($plate));

        // 2. Hapus semua karakter kecuali Huruf (A-Z), Angka (0-9), dan Spasi
        $clean = preg_replace('/[^A-Z0-9\s]/', '', $clean);

        // 3. Ubah spasi ganda menjadi spasi tunggal
        $clean = preg_replace('/\s+/', ' ', $clean);
        
        return $clean;
    }

    /**
     * Mencari kendaraan untuk fitur pencarian di landing page.
     * 
     * @param string|null $query
     * @return \App\Models\Vehicle|null
     */
    public function findForLanding(?string $query): ?Vehicle
    {
        if (!$query) return null;

        $search = $this->formatPlateNumber($query);

        // 1. Prioritaskan Exact Match (Sangat cepat jika ada Index)
        $exact = Vehicle::where('no_polisi', $search)->first();
        if ($exact) return $exact;

        // 2. Prioritaskan Prefix Match (Masih bisa menggunakan Index)
        $prefix = Vehicle::where('no_polisi', 'LIKE', "{$search}%")->first();
        if ($prefix) return $prefix;

        // 3. Fallback ke pencarian luas (Lambat - Full Table Scan)
        return Vehicle::where('no_polisi', 'LIKE', "%{$search}%")
            ->orWhere('pemegang', 'LIKE', "%{$query}%")
            ->first();
    }

    /**
     * Menganalisis header Excel dan merekomendasikan pemetaan kolom ke database E-RANDIS.
     * 
     * Menggunakan analisis semantik berbasis kamus sinonim dan pencocokan kemiripan teks.
     * 
     * @param array $headers Daftar header kolom dari file Excel
     * @return array Rekomendasi pemetaan: [ExcelHeader => TargetDbColumn]
     */
    public function suggestColumnMapping(array $headers): array
    {
        $suggestions = [];
        
        // Kamus sinonim kolom target database
        $synonyms = [
            'no_polisi' => ['no polisi', 'no. polisi', 'nomor polisi', 'plat', 'no plat', 'no. plat', 'nomor plat', 'nopol', 'plat nomor', 'plate', 'plate number'],
            'jenis' => ['jenis', 'jenis kendaraan', 'kategori', 'kategori kendaraan', 'roda', 'class', 'category', 'jenis roda'],
            'merk' => ['merk', 'merek', 'brand', 'pabrikan', 'nama aset', 'nama kendaraan', 'make', 'merk jenis', 'merkjenis', 'merk/jenis'],
            'tipe' => ['tipe', 'type', 'model', 'jenis tipe', 'tipe kendaraan', 'merk jenis', 'merkjenis', 'merk/jenis'],
            'no_mesin' => ['no mesin', 'no. mesin', 'nomor mesin', 'engine number', 'engine no', 'nomer mesin'],
            'no_rangka' => ['no rangka', 'no. rangka', 'nomor rangka', 'chassis number', 'vin', 'chassis no', 'nomer rangka'],
            'tahun_pembuatan' => ['tahun', 'tahun pembuatan', 'thn', 'tahun rakit', 'tahun buat', 'year', 'thn pembuatan', 'thn buat'],
            'tgl_perolehan' => ['tgl perolehan', 'tanggal perolehan', 'tgl beli', 'tanggal beli', 'tanggal perolehan aset', 'acquisition date', 'tgl perolehan aset'],
            'nilai_perolehan' => ['harga', 'nilai perolehan', 'harga perolehan', 'nilai', 'nilai aset', 'harga beli', 'price', 'value', 'jumlah perolehan'],
            'stnk_ada' => ['stnk', 'status stnk', 'kelengkapan stnk', 'ada stnk', 'surat stnk'],
            'bpkb_ada' => ['bpkb', 'status bpkb', 'kelengkapan bpkb', 'ada bpkb', 'surat bpkb'],
            'kondisi' => ['kondisi', 'kondisi fisik', 'keadaan', 'status kondisi', 'condition', 'kondisi aset', 'kondisi kendaraan'],
            'pemegang' => ['pemegang', 'nama pemegang', 'penanggung jawab', 'peminjam', 'user', 'driver', 'nama pemakai', 'penggunaan', 'pengguna'],
            'keterangan' => ['keterangan', 'ket', 'note', 'notes', 'keterangan tambahan', 'keterangan aset'],
            'opd' => ['opd', 'instansi', 'dinas', 'skpd', 'kantor', 'bagian', 'department', 'organisasi'],
            'nomor_register' => ['nomor register', 'no register', 'no. register', 'nomer register', 'register', 'register number', 'reg number', 'no_register', 'no reg'],
        ];

        foreach ($headers as $header) {
            $cleanHeader = strtolower(trim($header));
            $cleanHeader = preg_replace('/[^a-z0-9\s]/', '', $cleanHeader); // Bersihkan simbol
            $cleanHeader = preg_replace('/\s+/', ' ', $cleanHeader); // Bersihkan spasi ganda
            
            $bestMatch = null;
            $highestSimilarity = 0;

            // 1. Cari kecocokan eksak di kamus sinonim
            foreach ($synonyms as $targetColumn => $list) {
                if (in_array($cleanHeader, $list)) {
                    $bestMatch = $targetColumn;
                    break;
                }
            }

            // 2. Jika tidak ada kecocokan eksak, gunakan String Similarity (similar_text)
            if (!$bestMatch) {
                foreach ($synonyms as $targetColumn => $list) {
                    foreach ($list as $synonym) {
                        similar_text($cleanHeader, $synonym, $percent);
                        if ($percent > $highestSimilarity && $percent >= 65) { // Threshold minimal 65% kemiripan
                            $highestSimilarity = $percent;
                            $bestMatch = $targetColumn;
                        }
                    }
                }
            }

            // Jika ada rekomendasi kecocokan, masukkan ke daftar saran
            if ($bestMatch) {
                $suggestions[$header] = $bestMatch;
            } else {
                $suggestions[$header] = ''; // Biarkan kosong agar user memilih sendiri di UI
            }
        }

        return $suggestions;
    }

    /**
     * Menganalisis dan mendeteksi daftar kendaraan ganda/identik di database (AI/Sufiks ganda & no_mesin ganda).
     *
     * @param string|null $targetTable
     * @return array
     */
    public function getDuplicateVehiclesList(?string $targetTable = 'real'): array
    {
        $modelClass = $targetTable === 'ebmd' ? \App\Models\EbmdVehicle::class : Vehicle::class;
        $vehicles = $modelClass::withoutGlobalScopes()->get();
        $duplicates = [];

        foreach ($vehicles as $v) {
            $plate = $v->no_polisi;
            // Deteksi jika plat berakhir dengan sufiks impor ganda "(2)", "(3)", dst
            if (preg_match('/^(.+?)\s*\(\d+\)$/', $plate, $matches)) {
                $originalPlate = trim($matches[1]);
                
                // Cari kendaraan asli dengan plat induk
                $originalVehicle = $modelClass::withoutGlobalScopes()
                    ->where('no_polisi', $originalPlate)
                    ->where('id', '!=', $v->id)
                    ->first();

                if ($originalVehicle) {
                    $duplicates[] = [
                        'duplicate_vehicle' => $v,
                        'original_vehicle'  => $originalVehicle,
                        'reason'            => "Plat terindikasi ganda hasil impor: \"{$plate}\" vs \"{$originalPlate}\""
                    ];
                }
            }
        }

        // Deteksi duplikasi berdasarkan Nomor Mesin yang identik
        $engineDuplicates = $modelClass::withoutGlobalScopes()
            ->select('no_mesin', \DB::raw('count(*) as count'))
            ->whereNotNull('no_mesin')
            ->whereNotIn('no_mesin', ['', '-'])
            ->groupBy('no_mesin')
            ->having('count', '>', 1)
            ->pluck('no_mesin')
            ->toArray();

        foreach ($engineDuplicates as $noMesin) {
            $vList = $modelClass::withoutGlobalScopes()
                ->where('no_mesin', $noMesin)
                ->get();
            
            if ($vList->count() > 1) {
                $original = $vList->first();
                for ($i = 1; $i < $vList->count(); $i++) {
                    $dup = $vList[$i];
                    
                    // Hindari duplikasi entri di list jika sudah terdeteksi di plat
                    $alreadyAdded = collect($duplicates)->contains(function($item) use ($dup) {
                        return $item['duplicate_vehicle']->id === $dup->id;
                    });

                    if (!$alreadyAdded) {
                        $duplicates[] = [
                            'duplicate_vehicle' => $dup,
                            'original_vehicle'  => $original,
                            'reason'            => "Nomor Mesin identik ganda: \"{$noMesin}\""
                        ];
                    }
                }
            }
        }

        return $duplicates;
    }

    /**
     * Menganalisis dan mendeteksi daftar OPD/Dinas yang terindikasi ganda atau mirip.
     *
     * @param string|null $targetTable
     * @return array
     */
    public function getDuplicateOpdsList(?string $targetTable = 'real'): array
    {
        $modelClass = $targetTable === 'ebmd' ? \App\Models\EbmdVehicle::class : Vehicle::class;
        $opds = \App\Models\Opd::all();
        $duplicates = [];
        $checked = [];

        foreach ($opds as $opdA) {
            if (in_array($opdA->id, $checked)) continue;

            foreach ($opds as $opdB) {
                if ($opdA->id === $opdB->id) continue;
                if (in_array($opdB->id, $checked)) continue;

                $nameA = strtoupper(trim($opdA->nama));
                $nameB = strtoupper(trim($opdB->nama));

                // Hilangkan kata-kata umum instansi untuk membandingkan kemiripan inti nama dinas
                $cleanA = trim(str_replace(['DINAS', 'KANTOR', 'BADAN', 'KABUPATEN', 'KOTA', 'KAB', 'UPTD'], '', $nameA));
                $cleanB = trim(str_replace(['DINAS', 'KANTOR', 'BADAN', 'KABUPATEN', 'KOTA', 'KAB', 'UPTD'], '', $nameB));

                $isDuplicate = false;
                $reason = "";

                if ($nameA === $nameB) {
                    $isDuplicate = true;
                    $reason = "Nama OPD sama persis (Case-Insensitive): \"{$opdA->nama}\"";
                } elseif (!empty($cleanA) && !empty($cleanB) && strlen($cleanA) > 3 && ($cleanA === $cleanB)) {
                    $isDuplicate = true;
                    $reason = "Indikasi kemiripan nama instansi: \"{$opdA->nama}\" vs \"{$opdB->nama}\"";
                }

                if ($isDuplicate) {
                    $countA = $modelClass::withoutGlobalScopes()->where('opd_id', $opdA->id)->count();
                    $countB = $modelClass::withoutGlobalScopes()->where('opd_id', $opdB->id)->count();

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
     * Menggabungkan data dari kendaraan ganda ke kendaraan asli (mengisi kolom kosong) lalu menghapus yang ganda.
     *
     * @param int $originalId
     * @param int $duplicateId
     * @param string|null $targetTable
     * @return bool
     */
    public function mergeVehicles(int $originalId, int $duplicateId, ?string $targetTable = 'real'): bool
    {
        $modelClass = $targetTable === 'ebmd' ? \App\Models\EbmdVehicle::class : Vehicle::class;
        return \DB::transaction(function () use ($originalId, $duplicateId, $modelClass) {
            $original = $modelClass::withoutGlobalScopes()->find($originalId);
            $duplicate = $modelClass::withoutGlobalScopes()->find($duplicateId);

            if (!$original || !$duplicate) return false;

            // Salin kolom yang kosong pada data asli dari data ganda
            $fields = [
                'jenis', 'merk', 'tipe', 'no_mesin', 'no_rangka', 'tahun_pembuatan',
                'tgl_stnk', 'tgl_perolehan', 'nilai_perolehan', 'stnk_ada', 'bpkb_ada',
                'kondisi', 'pemegang', 'keterangan', 'foto_kendaraan'
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

            // Hapus kendaraan ganda
            $duplicate->delete();
            
            return true;
        });
    }

    /**
     * Menggabungkan OPD duplikat: Memindahkan seluruh kendaraan dari OPD sumber ke OPD target, lalu menghapus OPD sumber.
     *
     * @param int $targetOpdId
     * @param int $sourceOpdId
     * @param string|null $targetTable
     * @return bool
     */
    public function mergeOpds(int $targetOpdId, int $sourceOpdId, ?string $targetTable = 'real'): bool
    {
        $modelClass = $targetTable === 'ebmd' ? \App\Models\EbmdVehicle::class : Vehicle::class;
        return \DB::transaction(function () use ($targetOpdId, $sourceOpdId, $modelClass) {
            $target = \App\Models\Opd::find($targetOpdId);
            $source = \App\Models\Opd::find($sourceOpdId);

            if (!$target || !$source) return false;

            // Pindahkan seluruh kendaraan dari OPD sumber ke OPD target dan sinkronkan nama OPD-nya
            $modelClass::withoutGlobalScopes()
                ->where('opd_id', $sourceOpdId)
                ->update([
                    'opd_id' => $targetOpdId,
                    'opd'    => $target->nama
                ]);

            // Hapus OPD sumber
            $source->delete();

            return true;
        });
    }

    /**
     * Membersihkan kolom no_rangka dan no_mesin dari seluruh kendaraan yang dapat diakses.
     * 
     * Menghapus semua karakter kecuali huruf (A-Z, a-z) dan angka (0-9).
     * 
     * @return int Jumlah kendaraan yang berhasil diperbarui
     */
    public function sanitizeIdentifiers(): int
    {
        $count = 0;

        \DB::transaction(function () use (&$count) {
            Vehicle::chunk(100, function ($vehicles) use (&$count) {
                foreach ($vehicles as $vehicle) {
                    $dirtyRangka = $vehicle->no_rangka;
                    $dirtyMesin = $vehicle->no_mesin;

                    $cleanRangka = $dirtyRangka ? preg_replace('/[^a-zA-Z0-9]/', '', $dirtyRangka) : $dirtyRangka;
                    $cleanMesin = $dirtyMesin ? preg_replace('/[^a-zA-Z0-9]/', '', $dirtyMesin) : $dirtyMesin;

                    if ($dirtyRangka !== $cleanRangka || $dirtyMesin !== $cleanMesin) {
                        $vehicle->no_rangka = $cleanRangka;
                        $vehicle->no_mesin = $cleanMesin;
                        $vehicle->save();
                        $count++;
                    }
                }
            });
        });

        if ($count > 0) {
            $this->invalidateDashboardStats(invalidateAllOpd: true);
        }

        return $count;
    }
    /**
     * Memperbaiki posisi Nomor Mesin dan Nomor Rangka yang tertukar.
     * 
     * Menggunakan heuristik: jika panjang nomor mesin lebih besar dari nomor rangka,
     * kemungkinan besar mereka tertukar posisinya (karena VIN/No Rangka biasanya lebih panjang, yaitu 17 digit).
     * 
     * @return int Jumlah kendaraan yang posisinya ditukar
     */
    public function fixSwappedIdentifiers(): int
    {
        $count = 0;

        \DB::transaction(function () use (&$count) {
            Vehicle::chunk(100, function ($vehicles) use (&$count) {
                foreach ($vehicles as $vehicle) {
                    $mesin = trim($vehicle->no_mesin ?? '');
                    $rangka = trim($vehicle->no_rangka ?? '');

                    // Heuristik: Nomor Mesin lebih panjang dari Nomor Rangka
                    // (Biasanya VIN itu panjangnya pas 17 karakter, sedangkan mesin lebih pendek)
                    if (!empty($mesin) && !empty($rangka) && strlen($mesin) > strlen($rangka)) {
                        $vehicle->no_mesin = $rangka;
                        $vehicle->no_rangka = $mesin;
                        $vehicle->save();
                        $count++;
                    }
                }
            });
        });

        if ($count > 0) {
            $this->invalidateDashboardStats(invalidateAllOpd: true);
        }

        return $count;
    }

    /**
     * Menyimpan data kendaraan baru beserta foto fisiknya.
     *
     * @param array $data
     * @param array|null $images
     * @param string|null $targetTable
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function storeVehicle(array $data, ?array $images, ?string $targetTable)
    {
        $data['no_polisi'] = $this->formatPlateNumber($data['no_polisi']);

        if ($images) {
            $paths = [];
            foreach ($images as $image) {
                $paths[] = $image->store('vehicles', 'public');
            }
            $data['foto_kendaraan'] = $paths;
        }

        $modelClass = $targetTable === 'ebmd' ? \App\Models\EbmdVehicle::class : Vehicle::class;
        $vehicle = $modelClass::create($data);
        
        $this->invalidateDashboardStats(opdId: $vehicle->opd_id);

        return $vehicle;
    }

    /**
     * Memperbarui data kendaraan beserta foto fisiknya.
     *
     * @param int $id
     * @param array $data
     * @param array|null $images
     * @param string|null $targetTable
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function updateVehicle(int $id, array $data, ?array $images, ?string $targetTable)
    {
        $modelClass = $targetTable === 'ebmd' ? \App\Models\EbmdVehicle::class : Vehicle::class;
        $vehicle = $modelClass::findOrFail($id);

        $data['no_polisi'] = $this->formatPlateNumber($data['no_polisi']);

        if ($images) {
            // Hapus foto lama
            if ($vehicle->foto_kendaraan) {
                foreach ($vehicle->foto_kendaraan as $oldPath) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $paths = [];
            foreach ($images as $image) {
                $paths[] = $image->store('vehicles', 'public');
            }
            $data['foto_kendaraan'] = $paths;
        }

        $oldOpdId = $vehicle->opd_id;
        $vehicle->update($data);
        
        $this->invalidateDashboardStats(opdId: $vehicle->opd_id, oldOpdId: $oldOpdId);

        return $vehicle;
    }

    /**
     * Menghapus data kendaraan beserta foto fisiknya.
     *
     * @param int $id
     * @param string|null $targetTable
     * @return void
     */
    public function deleteVehicle(int $id, ?string $targetTable): void
    {
        $modelClass = $targetTable === 'ebmd' ? \App\Models\EbmdVehicle::class : Vehicle::class;
        $vehicle = $modelClass::findOrFail($id);

        if ($vehicle->foto_kendaraan) {
            foreach ($vehicle->foto_kendaraan as $path) {
                Storage::disk('public')->delete($path);
            }
        }
        
        $opdId = $vehicle->opd_id;
        $vehicle->delete();
        
        $this->invalidateDashboardStats(opdId: $opdId);
    }

    /**
     * Menyinkronkan data dari e-BMD ke tabel Real.
     *
     * @param int $id
     * @return Vehicle
     * @throws \Exception
     */
    public function syncEbmdToReal(int $id): Vehicle
    {
        $ebmdVehicle = \App\Models\EbmdVehicle::findOrFail($id);
        
        if ($ebmdVehicle->is_synced) {
            throw new \Exception('Data e-BMD ini sudah pernah disinkronkan sebelumnya.');
        }

        $data = $ebmdVehicle->toArray();
        unset($data['id']);
        unset($data['is_synced']);
        unset($data['created_at']);
        unset($data['updated_at']);

        $data['no_polisi'] = $this->formatPlateNumber($data['no_polisi']);
        
        $vehicle = Vehicle::create($data);
        $ebmdVehicle->update(['is_synced' => true]);
        
        $this->invalidateDashboardStats(opdId: $vehicle->opd_id);

        return $vehicle;
    }
}

