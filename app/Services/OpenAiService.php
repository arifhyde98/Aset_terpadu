<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk integrasi API LLM berbasis OpenAI Compatible
 * (Mendukung OpenRouter, 9router, DeepSeek, Groq, OpenAI resmi, Ollama v1, dll).
 */
class OpenAiService
{
    protected ?string $apiKey;
    protected string $baseUrl;
    protected string $model;
    protected int $timeout;

    public function __construct()
    {
        $this->apiKey  = \App\Models\Setting::get('ai_openai_api_key') ?: (config('services.openai.api_key') ?: env('OPENAI_API_KEY'));
        $this->baseUrl = rtrim((string) (\App\Models\Setting::get('ai_openai_base_url') ?: (config('services.openai.base_url') ?: env('OPENAI_BASE_URL', 'https://openrouter.ai/api/v1'))), '/');
        $this->model   = \App\Models\Setting::get('ai_openai_model') ?: (config('services.openai.model') ?: env('OPENAI_MODEL', 'gh/gpt-4o-mini'));
        $this->timeout = (int) (\App\Models\Setting::get('ai_openai_timeout') ?: (config('services.openai.timeout') ?: env('OPENAI_TIMEOUT', 35)));
    }

    /**
     * Memeriksa apakah API Key telah dikonfigurasi.
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Mendapatkan nama provider dan model aktif saat ini.
     */
    public function getActiveModelName(): string
    {
        $host = parse_url($this->baseUrl, PHP_URL_HOST) ?: 'OpenAI-Compatible';
        return "{$this->model} ({$host})";
    }

    /**
     * Mengirim prompt teks ke endpoint /chat/completions.
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
                'error'   => 'OPENAI_API_KEY belum dikonfigurasi di file .env aplikasi.',
                'source'  => 'fallback',
            ];
        }

        $systemText = $system ?: "Kamu adalah Asisten Pintar Pengelolaan Barang Milik Daerah (BMD) dan Aset Terpadu (SIPAT & E-RANDIS).\n"
            . "Berikan jawaban langsung yang terstruktur, padat, informatif, dan profesional dalam Bahasa Indonesia.\n"
            . "Dilarang menampilkan proses internal thinking atau catatan draft.";

        $messages = [
            ['role' => 'system', 'content' => $systemText],
            ['role' => 'user', 'content' => $prompt],
        ];

        return $this->chat($messages, $options);
    }

    /**
     * Melakukan panggilan chat completion multi-turn ke OpenAI Compatible API.
     *
     * @param array $messages
     * @param array $options
     * @return array ['success' => bool, 'content' => ?string, 'error' => ?string, 'source' => string]
     */
    public function chat(array $messages, array $options = []): array
    {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'content' => null,
                'error'   => 'OPENAI_API_KEY belum dikonfigurasi di file .env aplikasi.',
                'source'  => 'fallback',
            ];
        }

        $model = $options['model'] ?? $this->model;
        $url   = "{$this->baseUrl}/chat/completions";

        $payload = [
            'model'       => $model,
            'messages'    => array_values($messages),
            'temperature' => (float) ($options['temperature'] ?? 0.3),
            'max_tokens'  => (int) ($options['max_tokens'] ?? 2048),
        ];

        $headers = [
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'HTTP-Referer'  => config('app.url', 'https://sipat-donggala.my.id'),
            'X-Title'       => 'SIPAT Terpadu AI Assistant',
        ];

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->post($url, $payload);

            if ($response->successful()) {
                $rawBody = $response->body();
                $content = '';

                // Skenario 1: Format JSON (bersihkan suffix "data: [DONE]" jika proxy menempelkannya)
                $cleanBody = preg_replace('/\s*data:\s*\[DONE\]\s*$/i', '', trim($rawBody));
                $data = json_decode($cleanBody, true);
                if (is_array($data)) {
                    $content = $data['choices'][0]['message']['content'] 
                        ?? $data['choices'][0]['text'] 
                        ?? null;
                }

                // Skenario 2: Format SSE Stream chunk (data: {"choices":[{"delta":...}]})
                if (empty($content) && str_contains($rawBody, 'data:')) {
                    $content = $this->parseSseStream($rawBody);
                }

                if (!empty($content)) {
                    return [
                        'success' => true,
                        'content' => $this->cleanResponseText($content),
                        'error'   => null,
                        'source'  => "openai-compatible ({$model})",
                    ];
                }

                return [
                    'success' => false,
                    'content' => null,
                    'error'   => 'Respon AI kosong dari provider.',
                    'source'  => 'fallback',
                ];
            }

            $errorBody = $response->json('error.message') 
                ?? $response->json('message') 
                ?? $response->body();

            Log::error("OpenAI/Router API Error ({$response->status()}): {$errorBody}");

            return [
                'success' => false,
                'content' => null,
                'error'   => "AI Gateway Error ({$response->status()}): " . ($errorBody ?: 'Gagal memproses permintaan.'),
                'source'  => 'fallback',
            ];
        } catch (\Throwable $e) {
            Log::error('OpenAI/Router request exception: ' . $e->getMessage());

            return [
                'success' => false,
                'content' => null,
                'error'   => 'Koneksi ke AI Gateway timeout atau gagal: ' . $e->getMessage(),
                'source'  => 'fallback',
            ];
        }
    }

    /**
     * Mem-parsing respon yang dikembalikan dalam bentuk Server-Sent Events (SSE) stream
     * (banyak digunakan oleh gateway/proxy seperti 9router).
     */
    protected function parseSseStream(string $rawBody): string
    {
        $accumulated = '';
        $lines = explode("\n", $rawBody);

        foreach ($lines as $line) {
            $line = trim($line);
            if (!str_starts_with($line, 'data:')) {
                continue;
            }

            $jsonStr = trim(substr($line, 5));
            if ($jsonStr === '' || $jsonStr === '[DONE]') {
                continue;
            }

            $chunk = json_decode($jsonStr, true);
            if (!is_array($chunk)) {
                continue;
            }

            // Ambil delta content
            $deltaContent = $chunk['choices'][0]['delta']['content'] ?? null;
            if ($deltaContent !== null && $deltaContent !== '') {
                $accumulated .= $deltaContent;
            }
        }

        return trim($accumulated);
    }

    /**
     * Membersihkan output teks dari tag internal (seperti <think> pada model reasoning).
     */
    protected function cleanResponseText(string $text): string
    {
        // Bersihkan tag <think>...</think> jika menggunakan model reasoning (DeepSeek R1 dsb)
        $text = preg_replace('/<think>[\s\S]*?<\/think>/i', '', $text);
        $text = preg_replace('/<thought>[\s\S]*?<\/thought>/i', '', $text);

        // Bersihkan prefix "Jawaban:" di awal jika ada
        $text = preg_replace('/^Jawaban:\s*/i', '', trim($text));

        return trim($text);
    }

    /**
     * Uji koneksi ad-hoc dengan parameter kustom (tanpa harus disimpan ke database).
     *
     * @param string $apiKey
     * @param string $baseUrl
     * @param string $model
     * @param int $timeout
     * @return array ['success' => bool, 'latency_ms' => int, 'message' => string, 'reply' => ?string]
     */
    public function testCustomConnection(string $apiKey, string $baseUrl, string $model, int $timeout = 15): array
    {
        $startTime = microtime(true);
        $endpoint = rtrim($baseUrl, '/') . '/chat/completions';

        try {
            $payload = [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => 'Halo! Ini uji koneksi sistem SIPAT Terpadu. Balas dengan: "Koneksi Berhasil, [Nama Model]".'],
                ],
                'max_tokens' => 60,
                'temperature' => 0.3,
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])
            ->timeout($timeout)
            ->post($endpoint, $payload);

            $latency = (int) round((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                $status = $response->status();
                $errBody = $response->json();
                $msg = $errBody['error']['message'] ?? ($errBody['message'] ?? "HTTP {$status}");
                return [
                    'success'    => false,
                    'latency_ms' => $latency,
                    'message'    => "Gagal terhubung ke API (Status: {$status}): {$msg}",
                    'reply'      => null,
                ];
            }

            $rawBody = $response->body();
            // Handle SSE/trailing done
            $cleanJsonStr = preg_replace('/\s*data:\s*\[DONE\]\s*$/i', '', trim($rawBody));
            $data = json_decode($cleanJsonStr, true);

            $reply = null;
            if (isset($data['choices'][0]['message']['content'])) {
                $reply = $this->cleanResponseText((string) $data['choices'][0]['message']['content']);
            } elseif (str_contains($rawBody, 'data:')) {
                $reply = $this->extractFromStreamResponse($rawBody);
            }

            if (empty($reply)) {
                $reply = "Koneksi berhasil dan handshake endpoint valid (200 OK).";
            }

            return [
                'success'    => true,
                'latency_ms' => $latency,
                'message'    => "Koneksi Berhasil! Respons diterima dalam {$latency} ms.",
                'reply'      => $reply,
            ];
        } catch (\Throwable $e) {
            $latency = (int) round((microtime(true) - $startTime) * 1000);
            return [
                'success'    => false,
                'latency_ms' => $latency,
                'message'    => "Kesalahan Jaringan / Timeout: " . $e->getMessage(),
                'reply'      => null,
            ];
        }
    }
}
