<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Service Unified AI yang menggabungkan provider OpenAI-Compatible (OpenRouter, 9router, DeepSeek, OpenAI)
 * dan Google Gemini Cloud dengan auto-detection dan graceful fallback.
 */
class UnifiedAiService
{
    protected OpenAiService $openAi;
    protected GeminiAiService $gemini;
    protected string $preferredProvider;

    public function __construct(OpenAiService $openAi, GeminiAiService $gemini)
    {
        $this->openAi = $openAi;
        $this->gemini = $gemini;
        $this->preferredProvider = strtolower((string) (\App\Models\Setting::get('ai_provider') ?: env('AI_PROVIDER', 'auto')));
    }

    public function getOpenAiService(): OpenAiService
    {
        return $this->openAi;
    }

    public function getGeminiService(): GeminiAiService
    {
        return $this->gemini;
    }

    /**
     * Mengetahui provider aktif saat ini ('openai' atau 'gemini').
     */
    public function getActiveProvider(): string
    {
        if ($this->preferredProvider === 'openai' && $this->openAi->isAvailable()) {
            return 'openai';
        }

        if ($this->preferredProvider === 'gemini' && $this->gemini->isAvailable()) {
            return 'gemini';
        }

        // Mode 'auto': utamakan OpenAI/OpenRouter jika API key tersedia, lalu Gemini
        if ($this->openAi->isAvailable()) {
            return 'openai';
        }

        if ($this->gemini->isAvailable()) {
            return 'gemini';
        }

        return 'none';
    }

    /**
     * Memeriksa apakah ada salah satu provider AI yang siap digunakan.
     */
    public function isAvailable(): bool
    {
        return $this->getActiveProvider() !== 'none';
    }

    /**
     * Mendapatkan nama model aktif.
     */
    public function getActiveModelName(): string
    {
        $provider = $this->getActiveProvider();

        if ($provider === 'openai') {
            return $this->openAi->getActiveModelName();
        }

        if ($provider === 'gemini') {
            return $this->gemini->getActiveModelName();
        }

        return 'Tidak ada provider aktif';
    }

    /**
     * Eksekusi generate teks prompt dengan auto-fallback.
     *
     * @param string $prompt
     * @param string|null $system
     * @param array $options
     * @return array ['success' => bool, 'content' => ?string, 'error' => ?string, 'source' => string]
     */
    public function generate(string $prompt, ?string $system = null, array $options = []): array
    {
        $provider = $this->getActiveProvider();

        if ($provider === 'none') {
            return [
                'success' => false,
                'content' => null,
                'error'   => 'Layanan AI belum dikonfigurasi. Silakan isi OPENAI_API_KEY atau GEMINI_API_KEY pada file .env.',
                'source'  => 'fallback',
            ];
        }

        if ($provider === 'openai') {
            $result = $this->openAi->generate($prompt, $system, $options);
            if ($result['success']) {
                return $result;
            }

            // Fallback ke Gemini jika OpenAI gagal dan Gemini tersedia
            if ($this->gemini->isAvailable()) {
                Log::warning('OpenAI generate failed, attempting fallback to Gemini: ' . ($result['error'] ?? ''));
                $fallbackResult = $this->gemini->generate($prompt, $system, $options);
                if ($fallbackResult['success']) {
                    return $fallbackResult;
                }
            }

            return $result;
        }

        // Provider aktif adalah Gemini
        $result = $this->gemini->generate($prompt, $system, $options);
        if ($result['success']) {
            return $result;
        }

        // Fallback ke OpenAI jika Gemini gagal dan OpenAI tersedia
        if ($this->openAi->isAvailable()) {
            Log::warning('Gemini generate failed, attempting fallback to OpenAI: ' . ($result['error'] ?? ''));
            $fallbackResult = $this->openAi->generate($prompt, $system, $options);
            if ($fallbackResult['success']) {
                return $fallbackResult;
            }
        }

        return $result;
    }

    /**
     * Multi-turn chat dengan auto-fallback.
     */
    public function chat(array $messages, array $options = []): array
    {
        $provider = $this->getActiveProvider();

        if ($provider === 'openai') {
            $result = $this->openAi->chat($messages, $options);
            if ($result['success']) {
                return $result;
            }
            if ($this->gemini->isAvailable()) {
                return $this->gemini->chat($messages, $options);
            }
            return $result;
        }

        if ($provider === 'gemini') {
            $result = $this->gemini->chat($messages, $options);
            if ($result['success']) {
                return $result;
            }
            if ($this->openAi->isAvailable()) {
                return $this->openAi->chat($messages, $options);
            }
            return $result;
        }

        return [
            'success' => false,
            'content' => null,
            'error'   => 'Layanan AI belum dikonfigurasi.',
            'source'  => 'fallback',
        ];
    }
}
