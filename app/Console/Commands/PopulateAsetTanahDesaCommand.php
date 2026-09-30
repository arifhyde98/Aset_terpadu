<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\AsetTanah;
use App\Models\Kecamatan;
use App\Models\Desa;
use App\Services\Sipat\SipatService;

class PopulateAsetTanahDesaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sipat:populate-desa {--dry-run : Menampilkan preview pencocokan tanpa menyimpan ke database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mendeteksi dan memasangkan desa_id pada aset tanah berdasarkan kecocokan ketat teks alamat dengan master data desa';

    /**
     * Kata-kata awalan yang ambigu jika hanya muncul kata dasarnya saja tanpa kata penjelas kedua.
     */
    protected array $ambiguousPrefixes = [
        'Kabonga' => ['Kabonga Besar', 'Kabonga Kecil'],
        'Loli' => ['Loli Dondo', 'Loli Oge', 'Loli Pesua', 'Loli Saluran', 'Loli Tasiburi'],
        'Wani' => ['Wani Satu', 'Wani Dua', 'Wani Tiga', 'Wani Lumbumpetigo', 'Wani I', 'Wani II', 'Wani III'],
        'Wombo' => ['Wombo Kalonggo', 'Wombo Mpanau'],
        'Ogoamas' => ['Ogoamas I', 'Ogoamas II'],
    ];

    /**
     * Alias ejaan resmi/lokal yang sudah diverifikasi 100% aman.
     */
    protected array $safeAliases = [
        'Bone Oge' => 'Boneoge',
        'Lolitasiburi' => 'Loli Tasiburi',
        'Tanamea' => 'Tanah Mea',
        'Tanahmea' => 'Tanah Mea',
        'Tanampulu' => 'Tanampuru',
        'Nupabomba' => 'Nupa Bomba',
        'Wani 1' => 'Wani Satu',
        'Wani 2' => 'Wani Dua',
        'Wani 3' => 'Wani Tiga',
        'Wani I' => 'Wani Satu',
        'Wani II' => 'Wani Dua',
        'Wani III' => 'Wani Tiga',
        'Balentuma' => 'Balintuma',
        'Walan Dano' => 'Walandano',
        'Lemba Mukti' => 'Lembah Mukti',
        'Pani i' => "Pani'i",
        'Toviora' => 'Towiora',
        'Tawiora' => 'Towiora',
        'Polanto Jaya' => 'Polando Jaya',
        'Batusuya Go o' => "Batusuya Go'o",
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info("Memulai verifikasi dan pencocokan desa_id pada data Aset Tanah...");
        if ($isDryRun) {
            $this->warn("MODUS PREVIEW AKTIF (--dry-run). Data tidak akan disimpan ke database.");
        }

        $kecamatans = Kecamatan::with(['desa'])->get()->keyBy('id');
        $assets = AsetTanah::withoutGlobalScopes()
            ->whereNotNull('kecamatan_id')
            ->where('kecamatan_id', '>', 0)
            ->get();

        $matched = [];
        $skippedAmbiguous = [];
        $unmatched = [];

        foreach ($assets as $aset) {
            $kec = $kecamatans[$aset->kecamatan_id] ?? null;
            if (!$kec) continue;

            $text = ($aset->alamat ?? '') . ' ' . ($aset->peruntukan ?? '') . ' ' . ($aset->nama_aset ?? '');
            $normText = strtolower(preg_replace("/[^\p{L}\p{N}\s]/u", " ", $text));
            $normText = trim(preg_replace("/\s+/", " ", $normText));

            // Urutkan desa di kecamatan ini berdasarkan PANJANG NAMA DESCENDING (Pencocokan Nama Terpanjang Dahulu)
            $desasSorted = $kec->desa->sortByDesc(fn($d) => strlen($d->nama));
            $foundDesa = null;

            // 1. Pencocokan nama resmi terpanjang
            foreach ($desasSorted as $d) {
                $dClean = strtolower(preg_replace("/[^\p{L}\p{N}\s]/u", " ", $d->nama));
                $dClean = trim(preg_replace("/\s+/", " ", $dClean));

                // Word boundary matching
                if (preg_match('/\b' . preg_quote($dClean, '/') . '\b/i', $normText)) {
                    // Cek apakah ambigu (hanya menyebut kata depan tanpa kata kedua)
                    $isAmbiguous = false;
                    foreach ($this->ambiguousPrefixes as $prefix => $variants) {
                        if (strcasecmp($dClean, $prefix) === 0) {
                            $hasSpecific = false;
                            foreach ($variants as $v) {
                                $vClean = strtolower(preg_replace("/[^\p{L}\p{N}\s]/u", " ", $v));
                                $vClean = trim(preg_replace("/\s+/", " ", $vClean));
                                if (preg_match('/\b' . preg_quote($vClean, '/') . '\b/i', $normText)) {
                                    $hasSpecific = true;
                                    break;
                                }
                            }
                            if (!$hasSpecific) {
                                $isAmbiguous = true;
                                break;
                            }
                        }
                    }

                    if ($isAmbiguous) {
                        $skippedAmbiguous[] = [
                            'id' => $aset->id_aset,
                            'kode' => $aset->kode_aset,
                            'kecamatan' => $kec->nama,
                            'word' => $d->nama,
                            'alamat' => $aset->alamat
                        ];
                        continue 2; // Lewatkan aset ini agar tidak salah tebak
                    }

                    $foundDesa = $d;
                    break;
                }
            }

            // 2. Pencocokan alias ejaan aman
            if (!$foundDesa) {
                foreach ($this->safeAliases as $alias => $targetName) {
                    $aliasClean = strtolower(preg_replace("/[^\p{L}\p{N}\s]/u", " ", $alias));
                    $aliasClean = trim(preg_replace("/\s+/", " ", $aliasClean));

                    if (preg_match('/\b' . preg_quote($aliasClean, '/') . '\b/i', $normText)) {
                        $target = $kec->desa->first(fn($item) => strcasecmp($item->nama, $targetName) === 0);
                        if ($target) {
                            $foundDesa = $target;
                            break;
                        }
                    }
                }
            }

            if ($foundDesa) {
                $matched[] = [
                    'id_aset' => $aset->id_aset,
                    'desa_id' => $foundDesa->id,
                    'desa_nama' => $foundDesa->nama,
                    'kecamatan_nama' => $kec->nama
                ];
            } else {
                $unmatched[] = [
                    'id_aset' => $aset->id_aset,
                    'kode' => $aset->kode_aset,
                    'kecamatan' => $kec->nama,
                    'alamat' => $aset->alamat
                ];
            }
        }

        $this->line("");
        $this->info("HASIL PEMERIKSAAN KETAT:");
        $this->line("- Total Aset Ber-Kecamatan : " . $assets->count());
        $this->info("- Siap Dipasangkan (Valid) : " . count($matched) . " aset (" . round(count($matched) / $assets->count() * 100, 1) . "%)");
        $this->warn("- Dilewati Karena Ambigu   : " . count($skippedAmbiguous) . " aset");
        $this->line("- Belum Mencantumkan Desa  : " . count($unmatched) . " aset");

        if (!$isDryRun) {
            $this->info("\nMenyimpan pasangan desa_id ke database aset_tanah...");
            DB::transaction(function() use ($matched) {
                foreach ($matched as $item) {
                    DB::table('aset_tanah')
                        ->where('id_aset', $item['id_aset'])
                        ->update(['desa_id' => $item['desa_id']]);
                }
            });

            app(SipatService::class)->invalidateDashboardCache();
            $this->info("SUKSES: " . count($matched) . " data aset tanah berhasil dipasangkan dengan desa_id!");
        } else {
            $this->warn("\nModus dry-run selesai. Jalankan tanpa --dry-run untuk mengeksekusi penyimpanan.");
        }

        return 0;
    }
}
