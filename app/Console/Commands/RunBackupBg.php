<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class RunBackupBg extends Command
{
    /**
     * Nama dan tanda tangan dari perintah console.
     *
     * @var string
     */
    protected $signature = 'app:run-backup-bg {option=all}';

    /**
     * Deskripsi perintah console.
     *
     * @var string
     */
    protected $description = 'Menjalankan Laravel Backup di background dan mencatat progress ke Cache';

    /**
     * Eksekusi perintah console.
     *
     * @return int
     */
    public function handle()
    {
        $option = $this->argument('option');
        $onlyDb = $option === 'db';

        Cache::put('backup_progress', [
            'status' => 'running',
            'step' => 'Menginisialisasi sistem pencadangan...',
            'percentage' => 10,
            'log' => "Memulai proses backup...\n"
        ], 600);

        // Siapkan argumen proses
        $args = ['php', 'artisan', 'backup:run', '--only-to-disk=backups'];
        if ($onlyDb) {
            $args[] = '--only-db';
        }

        $process = new Process($args, base_path(), null, null, 600); // timeout 10 menit
        $process->start();

        $log = "";
        
        foreach ($process->getIterator() as $type => $buffer) {
            $log .= $buffer;
            
            // Analisis teks log untuk memperkirakan persentase kemajuan
            $percentage = 20;
            $step = 'Sedang memproses...';
            
            if (str_contains($buffer, 'Dumping database')) {
                $step = 'Mengekspor skema dan data database...';
                $percentage = 35;
            } elseif (str_contains($buffer, 'Determining which files to back up')) {
                $step = 'Menganalisis file unggahan (public storage)...';
                $percentage = 55;
            } elseif (str_contains($buffer, 'Zipping')) {
                $step = 'Mengompres file ke dalam arsip zip (Zipping)...';
                $percentage = 75;
            } elseif (str_contains($buffer, 'Copying zip to')) {
                $step = 'Menyimpan berkas cadangan ke disk lokal...';
                $percentage = 90;
            }

            Cache::put('backup_progress', [
                'status' => 'running',
                'step' => $step,
                'percentage' => $percentage,
                'log' => $log
            ], 600);
        }

        $process->wait();

        if ($process->isSuccessful()) {
            $telegramService = new \App\Services\TelegramBackupService();
            if ($telegramService->isConfigured()) {
                try {
                    $allFiles = Storage::disk('backups')->allFiles();
                    $zipFiles = array_values(array_filter($allFiles, fn($f) => str_ends_with(strtolower($f), '.zip')));

                    if (!empty($zipFiles)) {
                        usort($zipFiles, fn($a, $b) => Storage::disk('backups')->lastModified($b) <=> Storage::disk('backups')->lastModified($a));
                        $latestRelative = $zipFiles[0];
                        $fullPath = storage_path('app/backups/' . $latestRelative);

                        $tgResult = $telegramService->sendBackupFile($fullPath);
                        if ($tgResult['success']) {
                            $log .= "\n[TELEGRAM] Berkas backup (" . basename($latestRelative) . ") berhasil dikirim ke Telegram!";
                        } else {
                            $log .= "\n[TELEGRAM ERROR] Gagal mengirim ke Telegram: " . $tgResult['message'];
                        }
                    } else {
                        $log .= "\n[TELEGRAM WARNING] Tidak ada berkas zip yang ditemukan di disk backups.";
                    }
                } catch (\Throwable $te) {
                    $log .= "\n[TELEGRAM EXCEPTION] " . $te->getMessage();
                }
            } else {
                $log .= "\n[TELEGRAM INFO] Telegram Token / Chat ID belum diatur di .env.";
            }

            Cache::put('backup_progress', [
                'status' => 'success',
                'step' => 'Backup Berhasil Selesai!',
                'percentage' => 100,
                'log' => $log . "\n[SUKSES] Berkas backup berhasil dibuat dan disimpan."
            ], 600);
            return Command::SUCCESS;
        } else {
            $errorOutput = $process->getErrorOutput();
            Cache::put('backup_progress', [
                'status' => 'failed',
                'step' => 'Backup Gagal!',
                'percentage' => 100,
                'log' => $log . "\n[ERROR] Terjadi kesalahan saat backup:\n" . $errorOutput
            ], 600);
            return Command::FAILURE;
        }
    }
}
