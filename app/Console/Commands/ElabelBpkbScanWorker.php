<?php

namespace App\Console\Commands;

use App\Models\Elabel\ElabelBpkb;
use App\Models\Elabel\ElabelBpkbOcrStaging;
use App\Services\Elabel\BpkbOcrExtractionService;
use Illuminate\Console\Command;

class ElabelBpkbScanWorker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elabel:bpkb-scan-worker 
                            {--limit=20 : Jumlah maksimal berkas BPKB yang diproses}
                            {--bpkb_id= : ID BPKB spesifik jika hanya ingin scan 1 berkas}
                            {--sleep=1 : Jeda detik antar pemrosesan berkas}
                            {--force : Paksa scan ulang meskipun sudah ada di staging}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Worker pemindaian berkas scan PDF BPKB di latar belakang untuk ditampung ke staging buffer';

    /**
     * Execute the console command.
     */
    public function handle(BpkbOcrExtractionService $service): int
    {
        $limit = (int) $this->option('limit');
        $specificId = $this->option('bpkb_id');
        $sleepSeconds = (int) $this->option('sleep');
        $force = (bool) $this->option('force');

        $this->info("Memulai worker ekstraksi BPKB di latar belakang...");

        if ($specificId) {
            $bpkb = ElabelBpkb::find($specificId);
            if (!$bpkb) {
                $this->error("BPKB ID {$specificId} tidak ditemukan.");
                return self::FAILURE;
            }
            $items = collect([$bpkb]);
        } else {
            $query = ElabelBpkb::where('status', '!=', 'Dihapus')
                ->whereNotNull('pdf_path')
                ->where('pdf_path', '!=', '');

            if (!$force) {
                // Jangan proses yang sudah ada staging pending atau applied
                $existingStagedIds = ElabelBpkbOcrStaging::whereIn('status', ['pending', 'applied'])
                    ->pluck('bpkb_id')
                    ->toArray();

                if (!empty($existingStagedIds)) {
                    $query->whereNotIn('id', $existingStagedIds);
                }
            }

            // Prioritaskan yang data krusialnya masih kosong terlebih dahulu
            $items = $query->orderByRaw("CASE WHEN no_bpkb IS NULL OR no_bpkb = '' THEN 0 ELSE 1 END")
                ->orderByRaw("CASE WHEN no_rangka IS NULL OR no_rangka = '' THEN 0 ELSE 1 END")
                ->orderBy('id', 'asc')
                ->limit($limit)
                ->get();
        }

        $total = $items->count();
        if ($total === 0) {
            $this->info("Tidak ada berkas BPKB baru yang perlu dipindai ke staging.");
            return self::SUCCESS;
        }

        $this->info("Ditemukan {$total} berkas BPKB untuk dipindai.");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $successCount = 0;
        $failCount = 0;

        foreach ($items as $item) {
            $result = $service->extract($item);

            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }

            $bar->advance();

            if ($sleepSeconds > 0 && $total > 1) {
                sleep($sleepSeconds);
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("Selesai! Berhasil ditampung ke staging: {$successCount}, Gagal: {$failCount}.");

        return self::SUCCESS;
    }
}
