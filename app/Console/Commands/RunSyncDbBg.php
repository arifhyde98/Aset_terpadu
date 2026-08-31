<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class RunSyncDbBg extends Command
{
    /**
     * Nama dan tanda tangan dari perintah console.
     *
     * @var string
     */
    protected $signature = 'app:sync-db-bg';

    /**
     * Deskripsi perintah console.
     *
     * @var string
     */
    protected $description = 'Menjalankan sinkronisasi database staging dari db_sipat_terpadu di background';

    /**
     * Eksekusi perintah console.
     *
     * @return int
     */
    public function handle()
    {
        Cache::put('sync_db_progress', [
            'status' => 'running',
            'step' => 'Menginisialisasi replikasi database...',
            'percentage' => 15,
            'log' => "Memulai proses sinkronisasi dari db_sipat_terpadu ke db_sipat_staging...\n"
        ], 600);

        try {
            Cache::put('sync_db_progress', [
                'status' => 'running',
                'step' => 'Mengekspor dan menyalin seluruh 48 tabel database...',
                'percentage' => 45,
                'log' => "Mengeksekusi mysqldump --single-transaction --quick --add-drop-table...\nSedang menimpa seluruh tabel di database staging...\n"
            ], 600);

            $command = "mysqldump -u bpkad.aset -padmin123 --single-transaction --quick --no-tablespaces --add-drop-table db_sipat_terpadu | mysql --force -u bpkad.aset -padmin123 db_sipat_staging 2>&1";
            
            $process = Process::fromShellCommandline($command, null, null, null, 600);
            $process->run();

            if ($process->isSuccessful()) {
                if (class_exists('\App\Models\Activity')) {
                    \App\Models\Activity::log("Menyinkronkan data penuh (100% replace) dari db_sipat_terpadu ke db_sipat_staging", 'info');
                }

                Cache::put('sync_db_progress', [
                    'status' => 'success',
                    'step' => 'Sinkronisasi Database Berhasil Selesai!',
                    'percentage' => 100,
                    'log' => "[SUKSES] Database staging kini 100% identik dengan SIPAT Terpadu.\n"
                ], 600);

                return Command::SUCCESS;
            } else {
                $errorOutput = $process->getErrorOutput() ?: $process->getOutput();
                Log::error('Gagal sinkronisasi database staging di background: ' . $errorOutput);

                Cache::put('sync_db_progress', [
                    'status' => 'failed',
                    'step' => 'Sinkronisasi Database Gagal!',
                    'percentage' => 100,
                    'log' => "[ERROR] Terjadi kesalahan saat sinkronisasi:\n" . $errorOutput
                ], 600);

                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            Log::error('Exception saat sinkronisasi database staging: ' . $e->getMessage());

            Cache::put('sync_db_progress', [
                'status' => 'failed',
                'step' => 'Sinkronisasi Database Gagal!',
                'percentage' => 100,
                'log' => "[ERROR] Exception: " . $e->getMessage()
            ], 600);

            return Command::FAILURE;
        }
    }
}
