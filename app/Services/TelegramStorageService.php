<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TelegramStorageService
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

    /**
     * Upload berkas ke Telegram Cloud Storage.
     *
     * @param UploadedFile $file
     * @param string $caption
     * @return array|null
     */
    public function uploadFile(UploadedFile $file, string $caption = ''): ?array
    {
        if (!$this->isConfigured()) {
            Log::warning('TelegramStorageService: Kredensial Telegram belum dikonfigurasi di .env');
            return null;
        }

        try {
            $url = "https://api.telegram.org/bot{$this->botToken}/sendDocument";
            $originalName = $file->getClientOriginalName();

            $response = Http::timeout(120)
                ->attach('document', file_get_contents($file->getRealPath()), $originalName)
                ->post($url, [
                    'chat_id' => $this->chatId,
                    'caption' => $caption ?: "📄 *DOKUMEN DINDING ARSIP SIPAT*\n" . $originalName,
                    'parse_mode' => 'Markdown',
                ]);

            if ($response->successful() && $response->json('ok') === true) {
                $doc = $response->json('result.document');
                $fileId = $doc['file_id'] ?? null;

                if ($fileId) {
                    return [
                        'file_id'   => $fileId,
                        'file_name' => $doc['file_name'] ?? $originalName,
                        'mime_type' => $doc['mime_type'] ?? $file->getClientMimeType(),
                        'file_size' => $doc['file_size'] ?? $file->getSize(),
                        'tg_path'   => 'tg:' . $fileId,
                    ];
                }
            }

            Log::error('TelegramStorageService upload failed: ' . $response->body());
            return null;
        } catch (\Throwable $e) {
            Log::error('TelegramStorageService exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Mengambil URL unduh langsung dari Telegram API untuk file_id tertentu.
     *
     * @param string $fileId
     * @return string|null
     */
    public function getDirectUrl(string $fileId): ?string
    {
        if (!$this->isConfigured()) return null;

        // Bersihkan prefix tg: jika ada
        $fileId = str_replace('tg:', '', $fileId);

        try {
            $response = Http::get("https://api.telegram.org/bot{$this->botToken}/getFile", [
                'file_id' => $fileId,
            ]);

            if ($response->successful() && $response->json('ok') === true) {
                $filePath = $response->json('result.file_path');
                if ($filePath) {
                    return "https://api.telegram.org/file/bot{$this->botToken}/{$filePath}";
                }
            }
        } catch (\Throwable $e) {
            Log::error("Gagal mendapatkan Telegram file URL: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Stream berkas langsung ke browser pengguna.
     *
     * @param string $fileId
     * @param string|null $downloadName
     * @return StreamedResponse|\Illuminate\Http\Response
     */
    public function streamToBrowser(string $fileId, ?string $downloadName = null)
    {
        $directUrl = $this->getDirectUrl($fileId);
        if (!$directUrl) {
            abort(404, 'Berkas di Telegram Cloud tidak ditemukan atau token tidak valid.');
        }

        $remoteStream = @fopen($directUrl, 'rb');
        if (!$remoteStream) {
            abort(404, 'Gagal terhubung ke Telegram Cloud Storage.');
        }

        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . ($downloadName ?: 'dokumen.pdf') . '"',
        ];

        return response()->stream(function () use ($remoteStream) {
            fpassthru($remoteStream);
            if (is_resource($remoteStream)) {
                fclose($remoteStream);
            }
        }, 200, $headers);
    }
}
