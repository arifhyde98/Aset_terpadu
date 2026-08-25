<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAiService
{
    protected ?string $apiKey;
    protected string $model;
    protected int $timeout;

    public function __construct()
    {
        $this->apiKey  = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
        $this->model   = config('services.gemini.model', 'gemini-1.5-flash');
        $this->timeout = (int) config('services.gemini.timeout', 30);
    }

    /**
     * Memeriksa apakah API Key Gemini telah dikonfigurasi.
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Mendapatkan nama model aktif.
     */
    public function getActiveModelName(): string
    {
        return $this->model . ' (Google Gemini Cloud)';
    }

    /**
     * Mengambil daftar model teks stabil yang didukung oleh API Key.
     */
    public function getSupportedModels(): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        return Cache::remember('gemini_stable_models_' . md5($this->apiKey), 300, function () {
            $stableModels = [];
            
            // Prioritas utama: model produksi stabil (non-experimental / non-thinking)
            $preferred = ['gemini-1.5-flash', 'gemini-1.5-flash-8b', 'gemini-1.5-pro', 'gemini-2.0-flash'];

            foreach (['v1beta', 'v1'] as $version) {
                try {
                    $url = "https://generativelanguage.googleapis.com/{$version}/models?key={$this->apiKey}";
                    $response = Http::timeout(6)
                        ->withHeaders(['x-goog-api-key' => $this->apiKey])
                        ->get($url);

                    if ($response->successful()) {
                        $models = $response->json('models') ?? [];
                        foreach ($models as $m) {
                            $methods = $m['supportedGenerationMethods'] ?? [];
                            $name = str_replace('models/', '', $m['name'] ?? '');

                            // Buang model preview thinking, audio/tts, image, embedding
                            if (
                                str_contains($name, 'thinking') ||
                                str_contains($name, 'tts') ||
                                str_contains($name, 'embed') ||
                                str_contains($name, 'imagen') ||
                                str_contains($name, 'aqa') ||
                                str_contains($name, 'preview')
                            ) {
                                continue;
                            }

                            if (in_array('generateContent', $methods, true) && $name) {
                                $stableModels[] = [
                                    'name'    => $name,
                                    'version' => $version,
                                ];
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning("Gemini listModels exception ({$version}): " . $e->getMessage());
                }
            }

            // Urutkan model produksi stabil di posisi paling atas
            usort($stableModels, function ($a, $b) use ($preferred) {
                $posA = array_search($a['name'], $preferred);
                $posB = array_search($b['name'], $preferred);

                $valA = $posA === false ? 99 : $posA;
                $valB = $posB === false ? 99 : $posB;

                return $valA <=> $valB;
            });

            return $stableModels;
        });
    }

    /**
     * Mengirim prompt teks ke Google Gemini Cloud API.
     *
     * @param string $prompt
     * @param string|null $system
     * @param array $options
     * @return array ['success' => bool, 'content' => ?string, 'error' => ?string, 'source' => string]
     */
    public function generate(string $prompt, ?string $system = null, array $options = []): array
    {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'content' => null,
                'error'   => 'GEMINI_API_KEY belum dikonfigurasi di file .env aplikasi.',
                'source'  => 'fallback',
            ];
        }

        $supportedList = $this->getSupportedModels();
        $targets = [];

        if (!empty($supportedList)) {
            foreach ($supportedList as $s) {
                $targets[] = ['model' => $s['name'], 'version' => $s['version']];
            }
        }

        // Pastikan model standar gemini-1.5-flash selalu ada di daftar percobaan
        array_unshift(
            $targets,
            ['model' => 'gemini-1.5-flash', 'version' => 'v1beta'],
            ['model' => 'gemini-1.5-flash', 'version' => 'v1'],
            ['model' => 'gemini-2.0-flash', 'version' => 'v1beta']
        );

        // Hapus duplikat
        $uniqueTargets = [];
        foreach ($targets as $t) {
            $key = $t['model'] . '@' . $t['version'];
            if (!isset($uniqueTargets[$key])) {
                $uniqueTargets[$key] = $t;
            }
        }

        // Siapkan prompt dengan instruksi yang bersih
        $systemText = $system ?: "Kamu adalah Asisten Pintar Pengelolaan Barang Milik Daerah (BMD) dan Aset Terpadu (SIPAT & E-RANDIS).\n"
            . "Berikan jawaban langsung yang terstruktur, padat, informatif, dan profesional dalam Bahasa Indonesia.";

        $fullPrompt = "{$systemText}\n\nPertanyaan Pengguna:\n{$prompt}\n\nJawaban:";

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $fullPrompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature'     => $options['temperature'] ?? 0.3,
                'maxOutputTokens' => $options['max_tokens'] ?? 2048,
            ]
        ];

        $lastError = 'Gagal memproses respon dari Gemini API.';

        foreach ($uniqueTargets as $target) {
            $model = $target['model'];
            $version = $target['version'];

            try {
                $url = "https://generativelanguage.googleapis.com/{$version}/models/{$model}:generateContent?key={$this->apiKey}";

                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'x-goog-api-key' => $this->apiKey,
                    ])
                    ->post($url, $payload);

                if ($response->successful()) {
                    $text = $response->json('candidates.0.content.parts.0.text');
                    if (!empty($text)) {
                        return [
                            'success' => true,
                            'content' => $this->cleanResponseText($text),
                            'error'   => null,
                            'source'  => "google-gemini ({$model})",
                        ];
                    }
                }

                $errorMsg = $response->json('error.message') ?? $response->body();
                $lastError = $errorMsg;

                if (in_array($response->status(), [400, 401, 403], true) && !str_contains($errorMsg, 'not found')) {
                    break;
                }
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        Log::error('Gemini generate failed: ' . $lastError);
        return [
            'success' => false,
            'content' => null,
            'error'   => 'Gemini API Error: ' . $lastError,
            'source'  => 'fallback',
        ];
    }

    /**
     * Percakapan interaktif (Multi-turn chat) dengan Google Gemini Cloud API.
     */
    public function chat(array $messages, array $options = []): array
    {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'content' => null,
                'error'   => 'GEMINI_API_KEY belum dikonfigurasi di file .env.',
                'source'  => 'fallback',
            ];
        }

        // Ambil pesan terakhir sebagai prompt
        $lastUserMessage = '';
        foreach (array_reverse($messages) as $m) {
            if (($m['role'] ?? '') === 'user') {
                $lastUserMessage = $m['content'] ?? '';
                break;
            }
        }

        return $this->generate($lastUserMessage ?: 'Halo', null, $options);
    }

    /**
     * Membersihkan teks dari artifact / formatting yang tidak diinginkan
     */
    protected function cleanResponseText(string $text): string
    {
        // Bersihkan tag thought jika ada
        $text = preg_replace('/<thought>.*?<\/thought>/is', '', $text);
        
        // Bersihkan prefix "Jawaban:" di awal jika ada
        $text = preg_replace('/^Jawaban:\s*/i', '', trim($text));

        return trim($text);
    }
}
