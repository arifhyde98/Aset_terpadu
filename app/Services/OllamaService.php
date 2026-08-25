<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    // Konfigurasi Ollama
    protected bool $ollamaEnabled;
    protected string $ollamaBaseUrl;
    protected string $ollamaDefaultModel;
    protected int $ollamaTimeout;

    // Konfigurasi Gemini
    protected ?string $geminiApiKey;
    protected string $geminiModel;
    protected int $geminiTimeout;

    public function __construct()
    {
        // Ollama
        $this->ollamaEnabled      = (bool) config('services.ollama.enabled', true);
        $this->ollamaBaseUrl      = config('services.ollama.base_url', 'http://127.0.0.1:11434');
        $this->ollamaDefaultModel = config('services.ollama.model', 'qwen2.5:7b');
        $this->ollamaTimeout      = (int) config('services.ollama.timeout', 120);

        // Gemini Cloud
        $this->geminiApiKey       = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
        $this->geminiModel        = config('services.gemini.model', 'gemini-1.5-flash');
        $this->geminiTimeout      = (int) config('services.gemini.timeout', 30);
    }

    /**
     * Mendapatkan provider aktif saat ini (Gemini Cloud atau Ollama Lokal).
     */
    public function getActiveProvider(): string
    {
        if (!empty($this->geminiApiKey)) {
            return 'gemini';
        }
        return 'ollama';
    }

    /**
     * Memeriksa apakah layanan AI (Gemini atau Ollama) siap digunakan.
     */
    public function isAvailable(bool $forceRefresh = false): bool
    {
        // 1. Jika ada Gemini API Key, selalu aktif (Cloud AI)
        if (!empty($this->geminiApiKey)) {
            return true;
        }

        // 2. Jika tidak ada Gemini, cek koneksi ke Ollama Lokal
        if (!$this->ollamaEnabled) {
            return false;
        }

        if ($forceRefresh) {
            Cache::forget('ollama_service_available');
            Cache::forget('ollama_installed_models');
        }

        return (bool) Cache::remember('ollama_service_available', 60, function () {
            try {
                $response = Http::timeout(2)->get("{$this->ollamaBaseUrl}/api/tags");
                return $response->successful();
            } catch (\Throwable $e) {
                return false;
            }
        });
    }

    /**
     * Mendapatkan nama model aktif.
     */
    public function getActiveModelName(): string
    {
        if ($this->getActiveProvider() === 'gemini') {
            return $this->geminiModel . ' (Google Cloud AI)';
        }
        return $this->ollamaDefaultModel . ' (Ollama Lokal)';
    }

    /**
     * Mengambil daftar model yang terinstal di Ollama host.
     */
    public function getInstalledModels(): array
    {
        if (!empty($this->geminiApiKey)) {
            return [
                ['name' => 'gemini-1.5-flash (Cloud Free)', 'size' => 0, 'modified_at' => null],
                ['name' => 'gemini-2.0-flash (Cloud Free)', 'size' => 0, 'modified_at' => null],
            ];
        }

        if (!$this->isAvailable()) {
            return [];
        }

        return Cache::remember('ollama_installed_models', 60, function () {
            try {
                $response = Http::timeout(3)->get("{$this->ollamaBaseUrl}/api/tags");
                if ($response->successful()) {
                    $models = $response->json('models') ?? [];
                    return array_map(function ($m) {
                        return [
                            'name'       => $m['name'] ?? '',
                            'size'       => $m['size'] ?? 0,
                            'modified_at'=> $m['modified_at'] ?? null,
                        ];
                    }, $models);
                }
            } catch (\Throwable $e) {
                Log::warning('Ollama getInstalledModels exception: ' . $e->getMessage());
            }
            return [];
        });
    }

    /**
     * Memeriksa apakah model tertentu tersedia.
     */
    public function hasModel(?string $modelName = null): bool
    {
        if ($this->getActiveProvider() === 'gemini') {
            return true;
        }

        $target = $modelName ?: $this->ollamaDefaultModel;
        $installed = $this->getInstalledModels();
        
        foreach ($installed as $model) {
            if (str_starts_with($model['name'], $target) || $model['name'] === $target) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mengirim prompt teks dengan Graceful Fallback (Gemini Cloud -> Ollama Lokal).
     *
     * @param string $prompt
     * @param string|null $system
     * @param array $options
     * @return array ['success' => bool, 'content' => ?string, 'error' => ?string, 'source' => string]
     */
    public function generate(string $prompt, ?string $system = null, array $options = []): array
    {
        // 1. PRIORITAS 1: Gunakan Google Gemini Cloud AI (Jika API Key terpasang)
        if (!empty($this->geminiApiKey)) {
            $geminiResult = $this->generateWithGemini($prompt, $system, $options);
            if ($geminiResult['success']) {
                return $geminiResult;
            }
            Log::warning('Gemini generate failed, trying fallback to Ollama: ' . ($geminiResult['error'] ?? ''));
        }

        // 2. PRIORITAS 2: Gunakan Ollama Lokal (Fallback)
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'content' => null,
                'error'   => 'Layanan AI sedang offline. Silakan masukkan GEMINI_API_KEY di .env atau jalankan Ollama lokal.',
                'source'  => 'fallback',
            ];
        }

        return $this->generateWithOllama($prompt, $system, $options);
    }

    /**
     * Eksekusi prompt menggunakan Google Gemini API (Cloud)
     */
    protected function generateWithGemini(string $prompt, ?string $system = null, array $options = []): array
    {
        $candidateModels = array_unique([
            $options['model'] ?? $this->geminiModel,
            'gemini-1.5-flash',
            'gemini-2.0-flash',
            'gemini-1.5-flash-latest',
            'gemini-1.5-pro',
            'gemini-pro',
        ]);

        $apiVersions = ['v1beta', 'v1'];
        $lastError = 'Gagal memproses respon dari Gemini API.';

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature'     => $options['temperature'] ?? 0.7,
                'maxOutputTokens' => $options['max_tokens'] ?? 800,
            ]
        ];

        if ($system) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $system]
                ]
            ];
        }

        foreach ($candidateModels as $model) {
            foreach ($apiVersions as $version) {
                try {
                    $url = "https://generativelanguage.googleapis.com/{$version}/models/{$model}:generateContent?key={$this->geminiApiKey}";

                    $response = Http::timeout($this->geminiTimeout)
                        ->withHeaders([
                            'x-goog-api-key' => $this->geminiApiKey,
                        ])
                        ->post($url, $payload);

                    if ($response->successful()) {
                        $text = $response->json('candidates.0.content.parts.0.text');
                        if (!empty($text)) {
                            return [
                                'success' => true,
                                'content' => trim($text),
                                'error'   => null,
                                'source'  => "google-gemini ({$model})",
                            ];
                        }
                    }

                    $errorMsg = $response->json('error.message') ?? $response->body();
                    $lastError = $errorMsg;

                    // Jika bukan 404 (misal invalid key 400), jangan loop model lain
                    if ($response->status() !== 404) {
                        break 2;
                    }
                } catch (\Throwable $e) {
                    $lastError = $e->getMessage();
                }
            }
        }

        return [
            'success' => false,
            'content' => null,
            'error'   => 'Gemini API Error: ' . $lastError,
            'source'  => 'fallback',
        ];
    }

    /**
     * Eksekusi prompt menggunakan Ollama Lokal
     */
    protected function generateWithOllama(string $prompt, ?string $system = null, array $options = []): array
    {
        try {
            $model = $options['model'] ?? $this->ollamaDefaultModel;
            $payload = [
                'model'  => $model,
                'prompt' => $prompt,
                'stream' => false,
            ];

            if ($system) {
                $payload['system'] = $system;
            }

            if (!empty($options['format'])) {
                $payload['format'] = $options['format'];
            }

            $defaultModelOptions = [
                'num_predict' => 280,
                'num_ctx'     => 2048,
                'temperature' => 0.6,
            ];
            $customModelOptions = array_intersect_key($options, array_flip(['temperature', 'num_predict', 'num_ctx', 'top_p', 'top_k', 'stop']));
            $payload['options'] = array_merge($defaultModelOptions, $customModelOptions);

            $response = Http::timeout($this->ollamaTimeout)->post("{$this->ollamaBaseUrl}/api/generate", $payload);

            if ($response->successful() && !empty($response->json('response'))) {
                return [
                    'success' => true,
                    'content' => trim($response->json('response')),
                    'error'   => null,
                    'source'  => 'ollama-local',
                ];
            }

            $errorDetail = $response->json('error') ?? 'Gagal memproses respon dari model Ollama.';
            return [
                'success' => false,
                'content' => null,
                'error'   => $errorDetail,
                'source'  => 'fallback',
            ];
        } catch (\Throwable $e) {
            Log::warning('Ollama generate exception: ' . $e->getMessage());
            Cache::forget('ollama_service_available');
            Cache::forget('ollama_installed_models');

            return [
                'success' => false,
                'content' => null,
                'error'   => 'Koneksi ke Ollama terputus: ' . $e->getMessage(),
                'source'  => 'fallback',
            ];
        }
    }

    /**
     * Percakapan interaktif (Multi-turn chat) dengan Graceful Fallback.
     */
    public function chat(array $messages, array $options = []): array
    {
        if (!empty($this->geminiApiKey)) {
            // Konversi pesan chat ke format Gemini
            $geminiContents = [];
            $systemText = null;

            foreach ($messages as $msg) {
                $role = $msg['role'] ?? 'user';
                $text = $msg['content'] ?? '';

                if ($role === 'system') {
                    $systemText = $text;
                    continue;
                }

                $geminiContents[] = [
                    'role' => $role === 'assistant' ? 'model' : 'user',
                    'parts' => [['text' => $text]],
                ];
            }

            try {
                $model = $options['model'] ?? $this->geminiModel;
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->geminiApiKey}";

                $payload = [
                    'contents' => $geminiContents,
                    'generationConfig' => [
                        'temperature'     => $options['temperature'] ?? 0.7,
                        'maxOutputTokens' => $options['max_tokens'] ?? 800,
                    ]
                ];

                if ($systemText) {
                    $payload['systemInstruction'] = ['parts' => [['text' => $systemText]]];
                }

                $response = Http::timeout($this->geminiTimeout)
                    ->withHeaders([
                        'x-goog-api-key' => $this->geminiApiKey,
                    ])
                    ->post($url, $payload);
                if ($response->successful()) {
                    $text = $response->json('candidates.0.content.parts.0.text');
                    if (!empty($text)) {
                        return [
                            'success' => true,
                            'content' => trim($text),
                            'error'   => null,
                            'source'  => 'google-gemini',
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Gemini chat exception: ' . $e->getMessage());
            }
        }

        // Fallback to Ollama
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'content' => null,
                'error'   => 'Layanan AI sedang offline.',
                'source'  => 'fallback',
            ];
        }

        try {
            $model = $options['model'] ?? $this->ollamaDefaultModel;
            $payload = [
                'model'    => $model,
                'messages' => $messages,
                'stream'   => false,
            ];

            if (!empty($options['format'])) {
                $payload['format'] = $options['format'];
            }

            $defaultModelOptions = [
                'num_predict' => 280,
                'num_ctx'     => 2048,
                'temperature' => 0.6,
            ];
            $customModelOptions = array_intersect_key($options, array_flip(['temperature', 'num_predict', 'num_ctx', 'top_p', 'top_k', 'stop']));
            $payload['options'] = array_merge($defaultModelOptions, $customModelOptions);

            $response = Http::timeout($this->ollamaTimeout)->post("{$this->ollamaBaseUrl}/api/chat", $payload);

            if ($response->successful() && !empty($response->json('message.content'))) {
                return [
                    'success' => true,
                    'content' => trim($response->json('message.content')),
                    'error'   => null,
                    'source'  => 'ollama-local',
                ];
            }

            $errorDetail = $response->json('error') ?? 'Gagal memproses chat dari Ollama.';
            return [
                'success' => false,
                'content' => null,
                'error'   => $errorDetail,
                'source'  => 'fallback',
            ];
        } catch (\Throwable $e) {
            Log::warning('Ollama chat exception: ' . $e->getMessage());
            Cache::forget('ollama_service_available');
            Cache::forget('ollama_installed_models');

            return [
                'success' => false,
                'content' => null,
                'error'   => 'Koneksi chat ke Ollama terputus: ' . $e->getMessage(),
                'source'  => 'fallback',
            ];
        }
    }
}
