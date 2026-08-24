<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBackupService
{
    protected ?string $botToken;
    protected ?string $chatId;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token') ?: env('TELEGRAM_BOT_TOKEN');
        $this->chatId = config('services.telegram.chat_id') ?: env('TELEGRAM_CHAT_ID');
    }

    public function isConfigured(): bool
    {
        return !empty($this->botToken) && !empty($this->chatId);
    }

    public function sendBackupFile(string $filePath, string $caption = ''): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'TELEGRAM_BOT_TOKEN atau TELEGRAM_CHAT_ID belum diatur di file .env'
            ];
        }

        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'message' => 'Berkas backup tidak ditemukan pada jalur: ' . $filePath
            ];
        }

        try {
            $url = "https://api.telegram.org/bot{$this->botToken}/sendDocument";
            $fileName = basename($filePath);
            $fileSize = round(filesize($filePath) / 1024 / 1024, 2) . ' MB';
            
            $defaultCaption = "📦 *BERKAS BACKUP SIPAT TERPADU*\n"
                . "📄 Nama: `{$fileName}`\n"
                . "💾 Ukuran: *{$fileSize}*\n"
                . "⏰ Waktu: " . date('Y-m-d H:i:s') . "\n\n"
                . $caption;

            $response = Http::timeout(300)
                ->attach('document', file_get_contents($filePath), $fileName)
                ->post($url, [
                    'chat_id' => $this->chatId,
                    'caption' => $defaultCaption,
                    'parse_mode' => 'Markdown',
                ]);

            if ($response->successful() && ($response->json('ok') === true)) {
                Log::info("Berkas backup {$fileName} berhasil dikirim ke Telegram.");
                return [
                    'success' => true,
                    'message' => 'Berhasil dikirim ke Telegram',
                    'data' => $response->json('result')
                ];
            }

            $errorMsg = $response->json('description') ?? $response->body();
            Log::warning("Gagal mengirim backup ke Telegram: " . $errorMsg);
            return [
                'success' => false,
                'message' => 'Gagal dari API Telegram: ' . $errorMsg
            ];
        } catch (\Throwable $e) {
            Log::error("Exception saat mengirim backup ke Telegram: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }
}
