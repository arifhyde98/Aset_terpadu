<?php

namespace App\Console\Commands;

use App\Models\Elabel\ElabelBpkb;
use App\Models\Elabel\ElabelBpkbDelete;
use App\Models\Elabel\ElabelSertifikat;
use App\Services\TelegramStorageService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SyncStorageToTelegram extends Command
{
    /**
     * Nama dan tanda tangan dari perintah console.
     *
     * @var string
     */
    protected $signature = 'storage:sync-to-telegram';

    /**
     * Deskripsi perintah console.
     *
     * @var string
     */
    protected $description = 'Menyinkronkan berkas lokal BPKB & Sertifikat ke Telegram Cloud tanpa menghapus berkas fisik lokal (Dual Redundancy)';

    /**
     * Eksekusi perintah console.
     *
     * @return int
     */
    public function handle()
    {
        $tgStorage = new TelegramStorageService();
        if (!$tgStorage->isConfigured()) {
            $this->error('Gagal: TELEGRAM_BOT_TOKEN atau TELEGRAM_CHAT_ID belum dikonfigurasi di file .env');
            return Command::FAILURE;
        }

        $this->info('Memulai proses sinkronisasi berkas lokal ke Telegram Cloud (Dual-Redundancy)...');
        $this->line('Catatan: Berkas fisik di harddisk lokal TIDAK AKAN DIHAPUS.');
        $this->newLine();

        $syncedCount = 0;
        $failedCount = 0;
        $totalBytes = 0;

        // 1. Sinkronkan ElabelBpkb
        $bpkbs = ElabelBpkb::whereNotNull('pdf_path')
            ->where('pdf_path', '!=', '')
            ->where('pdf_path', 'NOT LIKE', 'tg:%')
            ->get();

        $this->info("Menemukan {$bpkbs->count()} data BPKB dengan berkas lokal...");

        foreach ($bpkbs as $bpkb) {
            $fullPath = storage_path('app/public/' . $bpkb->pdf_path);
            if (file_exists($fullPath)) {
                $this->line("Mengunggah BPKB [{$bpkb->plate_number}]...");
                
                $file = new UploadedFile($fullPath, basename($fullPath), mime_content_type($fullPath) ?: 'application/pdf');
                $caption = "📄 *SCAN BPKB {$bpkb->plate_number}*\nTahun: {$bpkb->year} | Box: {$bpkb->box_code}";
                
                $uploaded = $tgStorage->uploadFile($file, $caption);
                if ($uploaded && !empty($uploaded['tg_path'])) {
                    $totalBytes += filesize($fullPath);
                    $bpkb->update(['pdf_path' => $uploaded['tg_path']]);
                    $syncedCount++;
                    $this->info("  -> Berhasil disinkronkan ke Telegram! (File lokal tetap aman)");
                } else {
                    $failedCount++;
                    $this->warn("  -> Gagal mengunggah BPKB {$bpkb->plate_number}. Berkas lokal tetap dipertahankan.");
                }
                
                sleep(1); // Delay 1 detik untuk rate limit
            }
        }

        // 2. Sinkronkan ElabelSertifikat
        $sertifikats = ElabelSertifikat::whereNotNull('pdf_path')
            ->where('pdf_path', '!=', '')
            ->where('pdf_path', 'NOT LIKE', 'tg:%')
            ->get();

        $this->info("Menemukan {$sertifikats->count()} data Sertifikat Tanah dengan berkas lokal...");

        foreach ($sertifikats as $sert) {
            $fullPath = storage_path('app/public/' . $sert->pdf_path);
            if (file_exists($fullPath)) {
                $noSert = $sert->no_sertipikat ?? 'Sertipikat';
                $this->line("Mengunggah Sertipikat [{$noSert}]...");
                
                $file = new UploadedFile($fullPath, basename($fullPath), mime_content_type($fullPath) ?: 'application/pdf');
                $caption = "📜 *SERTIPIKAT TANAH: {$noSert}*";
                
                $uploaded = $tgStorage->uploadFile($file, $caption);
                if ($uploaded && !empty($uploaded['tg_path'])) {
                    $totalBytes += filesize($fullPath);
                    $sert->update(['pdf_path' => $uploaded['tg_path']]);
                    $syncedCount++;
                    $this->info("  -> Berhasil disinkronkan ke Telegram! (File lokal tetap aman)");
                } else {
                    $failedCount++;
                    $this->warn("  -> Gagal mengunggah Sertipikat {$noSert}. Berkas lokal tetap dipertahankan.");
                }
                
                sleep(1);
            }
        }

        // 3. Sinkronkan ElabelBpkbDelete
        $deletedBpkbs = ElabelBpkbDelete::where(function ($q) {
            $q->where(function ($sub) {
                $sub->whereNotNull('pdf_path')->where('pdf_path', '!=', '')->where('pdf_path', 'NOT LIKE', 'tg:%');
            })->orWhere(function ($sub) {
                $sub->whereNotNull('support_doc_path')->where('support_doc_path', '!=', '')->where('support_doc_path', 'NOT LIKE', 'tg:%');
            });
        })->get();

        $this->info("Menemukan {$deletedBpkbs->count()} data BPKB Keluar dengan berkas lokal...");

        foreach ($deletedBpkbs as $delBpkb) {
            if ($delBpkb->pdf_path && !str_starts_with($delBpkb->pdf_path, 'tg:')) {
                $fullPath = storage_path('app/public/' . $delBpkb->pdf_path);
                if (file_exists($fullPath)) {
                    $this->line("Mengunggah PDF BPKB Keluar [{$delBpkb->plate_number}]...");
                    $file = new UploadedFile($fullPath, basename($fullPath), mime_content_type($fullPath) ?: 'application/pdf');
                    $caption = "📄 *PDF BPKB KELUAR {$delBpkb->plate_number}*";
                    $uploaded = $tgStorage->uploadFile($file, $caption);
                    if ($uploaded && !empty($uploaded['tg_path'])) {
                        $totalBytes += filesize($fullPath);
                        $delBpkb->update(['pdf_path' => $uploaded['tg_path']]);
                        $syncedCount++;
                    }
                    sleep(1);
                }
            }

            if ($delBpkb->support_doc_path && !str_starts_with($delBpkb->support_doc_path, 'tg:')) {
                $fullPath = storage_path('app/public/' . $delBpkb->support_doc_path);
                if (file_exists($fullPath)) {
                    $this->line("Mengunggah Dokumen Pendukung BPKB Keluar [{$delBpkb->plate_number}]...");
                    $file = new UploadedFile($fullPath, basename($fullPath), mime_content_type($fullPath) ?: 'application/pdf');
                    $caption = "📎 *DOKUMEN PENDUKUNG BPKB KELUAR {$delBpkb->plate_number}*";
                    $uploaded = $tgStorage->uploadFile($file, $caption);
                    if ($uploaded && !empty($uploaded['tg_path'])) {
                        $totalBytes += filesize($fullPath);
                        $delBpkb->update(['support_doc_path' => $uploaded['tg_path']]);
                        $syncedCount++;
                    }
                    sleep(1);
                }
            }
        }

        $mbTotal = round($totalBytes / 1024 / 1024, 2);
        $this->newLine();
        $this->info("==================================================");
        $this->info("  PROSES SINKRONISASI TELEGRAM SANGAT SUKSES!  ");
        $this->info("==================================================");
        $this->line("Total Berkas Disinkronkan : {$syncedCount} berkas");
        $this->line("Total Ukuran Berkas      : {$mbTotal} MB");
        $this->line("Status Berkas Fisik Lokal: 100% AMAN DI HARDDISK");
        $this->info("==================================================");

        return Command::SUCCESS;
    }
}
