<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Setting;
use App\Services\GeminiAiService;
use App\Services\OpenAiService;
use App\Services\UnifiedAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controller untuk Pengaturan Terpusat AI Assistant (SIPAT & E-RANDIS)
 * Dilengkapi proteksi keamanan tingkat tinggi:
 * - Otorisasi Super Admin Only
 * - Proteksi SSRF (Server-Side Request Forgery) terhadap Base URL
 * - Masking Kredensial / API Key
 * - Sanitasi Input & Rate Limiting Test Connection
 * - Audit Trail Logging (Activity Log)
 */
class AiSettingController extends Controller implements HasMiddleware
{
    protected UnifiedAiService $unifiedAi;
    protected OpenAiService $openAi;
    protected GeminiAiService $gemini;

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:superadmin'),
        ];
    }

    public function __construct(UnifiedAiService $unifiedAi, OpenAiService $openAi, GeminiAiService $gemini)
    {
        $this->unifiedAi = $unifiedAi;
        $this->openAi = $openAi;
        $this->gemini = $gemini;
    }

    /**
     * Memastikan hanya superadmin yang dapat mengeksekusi aksi.
     */
    protected function authorizeSuperAdmin(): void
    {
        $user = auth()->user();
        $isSuperAdmin = false;

        if ($user) {
            $roleValue = is_object($user->role) ? ($user->role->value ?? '') : (string) $user->role;
            if ($roleValue === 'superadmin' || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())) {
                $isSuperAdmin = true;
            }
        }

        if (!$isSuperAdmin) {
            abort(403, 'Akses Ditolak: Hanya Super Administrator yang berwenang mengelola konfigurasi AI.');
        }
    }

    /**
     * Menampilkan halaman konfigurasi AI.
     */
    public function index(): View
    {
        $this->authorizeSuperAdmin();

        $activeProvider = Setting::get('ai_provider', env('AI_PROVIDER', 'auto'));

        // OpenAI / Compatible Settings
        $rawOpenAiKey = Setting::get('ai_openai_api_key', config('services.openai.api_key', env('OPENAI_API_KEY')));
        $openAiBaseUrl = Setting::get('ai_openai_base_url', config('services.openai.base_url', env('OPENAI_BASE_URL', 'https://9router.sipat-donggala.my.id/v1')));
        $openAiModel = Setting::get('ai_openai_model', config('services.openai.model', env('OPENAI_MODEL', 'gh/gpt-4o-mini')));
        $openAiTimeout = (int) Setting::get('ai_openai_timeout', config('services.openai.timeout', env('OPENAI_TIMEOUT', 35)));

        // Gemini Settings
        $rawGeminiKey = Setting::get('ai_gemini_api_key', config('services.gemini.api_key', env('GEMINI_API_KEY')));
        $geminiModel = Setting::get('ai_gemini_model', config('services.gemini.model', env('GEMINI_MODEL', 'gemini-1.5-flash')));
        $geminiTimeout = (int) Setting::get('ai_gemini_timeout', config('services.gemini.timeout', env('GEMINI_TIMEOUT', 30)));

        // System Prompt
        $systemPrompt = Setting::get('ai_system_prompt', '');

        // Masked keys for display
        $maskedOpenAiKey = $this->maskSecret($rawOpenAiKey);
        $hasSavedOpenAiKey = !empty($rawOpenAiKey);

        $maskedGeminiKey = $this->maskSecret($rawGeminiKey);
        $hasSavedGeminiKey = !empty($rawGeminiKey);

        $currentServiceStatus = [
            'is_available' => $this->unifiedAi->isAvailable(),
            'active_provider' => $this->unifiedAi->getActiveProvider(),
            'active_model' => $this->unifiedAi->getActiveModelName(),
        ];

        return view('settings.ai', compact(
            'activeProvider',
            'openAiBaseUrl',
            'openAiModel',
            'openAiTimeout',
            'maskedOpenAiKey',
            'hasSavedOpenAiKey',
            'geminiModel',
            'geminiTimeout',
            'maskedGeminiKey',
            'hasSavedGeminiKey',
            'systemPrompt',
            'currentServiceStatus'
        ));
    }

    /**
     * Menyimpan pembaruan konfigurasi AI.
     */
    public function update(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'ai_provider'         => 'required|in:auto,openai,gemini',
            'ai_openai_base_url'  => 'required|url|max:255',
            'ai_openai_model'     => 'required|string|max:100|regex:/^[a-zA-Z0-9\/\-\._:]+$/',
            'ai_openai_timeout'   => 'required|integer|min:5|max:120',
            'ai_openai_api_key'   => 'nullable|string|max:500',
            'ai_gemini_model'     => 'nullable|string|max:100|regex:/^[a-zA-Z0-9\/\-\._:]+$/',
            'ai_gemini_timeout'   => 'nullable|integer|min:5|max:120',
            'ai_gemini_api_key'   => 'nullable|string|max:500',
            'ai_system_prompt'    => 'nullable|string|max:5000',
        ], [
            'ai_openai_model.regex' => 'Format nama model OpenAI hanya boleh berisi huruf, angka, garis miring, titik, strip, dan titik dua.',
            'ai_gemini_model.regex' => 'Format nama model Gemini hanya boleh berisi huruf, angka, garis miring, titik, strip, dan titik dua.',
        ]);

        // Proteksi SSRF terhadap Base URL
        $ssrfCheck = $this->validateSafeUrl($validated['ai_openai_base_url']);
        if (!$ssrfCheck['safe']) {
            return back()
                ->withInput()
                ->withErrors(['ai_openai_base_url' => 'Keamanan URL Ditolak (SSRF Protection): ' . $ssrfCheck['reason']]);
        }

        // 1. Simpan Provider
        Setting::set('ai_provider', $validated['ai_provider'], 'text', 'ai');

        // 2. Simpan OpenAI Base URL, Model, Timeout
        Setting::set('ai_openai_base_url', rtrim($validated['ai_openai_base_url'], '/'), 'text', 'ai');
        Setting::set('ai_openai_model', trim($validated['ai_openai_model']), 'text', 'ai');
        Setting::set('ai_openai_timeout', (string) $validated['ai_openai_timeout'], 'text', 'ai');

        // 3. Simpan OpenAI Key (Hanya jika diisi nilai baru dan bukan masked bullet)
        $inputOpenAiKey = trim((string) $request->input('ai_openai_api_key'));
        if ($inputOpenAiKey !== '' && !str_contains($inputOpenAiKey, '••••')) {
            Setting::set('ai_openai_api_key', $inputOpenAiKey, 'text', 'ai');
        }

        // 4. Simpan Gemini Model & Timeout
        if (!empty($validated['ai_gemini_model'])) {
            Setting::set('ai_gemini_model', trim($validated['ai_gemini_model']), 'text', 'ai');
        }
        if (!empty($validated['ai_gemini_timeout'])) {
            Setting::set('ai_gemini_timeout', (string) $validated['ai_gemini_timeout'], 'text', 'ai');
        }

        // 5. Simpan Gemini Key (Hanya jika diisi nilai baru dan bukan masked bullet)
        $inputGeminiKey = trim((string) $request->input('ai_gemini_api_key'));
        if ($inputGeminiKey !== '' && !str_contains($inputGeminiKey, '••••')) {
            Setting::set('ai_gemini_api_key', $inputGeminiKey, 'text', 'ai');
        }

        // 6. Simpan System Prompt
        Setting::set('ai_system_prompt', trim((string) $request->input('ai_system_prompt')), 'textarea', 'ai');

        // Log Aktivitas Audit Keamanan
        $userName = auth()->user()->name ?? 'Super Admin';
        $ip = $request->ip();
        $safeHost = parse_url($validated['ai_openai_base_url'], PHP_URL_HOST);
        Activity::log(
            "Memperbarui konfigurasi AI Assistant (Provider: {$validated['ai_provider']}, Model: {$validated['ai_openai_model']}, Host: {$safeHost}) oleh {$userName} [IP: {$ip}]",
            'warning'
        );

        return redirect()->route('settings.ai.index')
            ->with('success', 'Konfigurasi AI Assistant berhasil diperbarui dan disimpan secara aman.');
    }

    /**
     * Endpoint AJAX Uji Koneksi Langsung (Live Test) ke endpoint provider.
     */
    public function testConnection(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin();

        $request->validate([
            'provider'   => 'required|in:openai,gemini',
            'api_key'    => 'nullable|string|max:500',
            'base_url'   => 'nullable|url|max:255',
            'model'      => 'required|string|max:100|regex:/^[a-zA-Z0-9\/\-\._:]+$/',
            'timeout'    => 'nullable|integer|min:3|max:60',
        ]);

        $provider = $request->input('provider');
        $model = trim((string) $request->input('model'));
        $timeout = (int) $request->input('timeout', 15);

        if ($provider === 'openai') {
            $baseUrl = rtrim((string) $request->input('base_url', 'https://9router.sipat-donggala.my.id/v1'), '/');
            
            // SSRF Check
            $ssrf = $this->validateSafeUrl($baseUrl);
            if (!$ssrf['safe']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi Keamanan Ditolak (SSRF): ' . $ssrf['reason'],
                ], 422);
            }

            // Ambil API Key: jika input user mengandung bullet mask atau kosong, gunakan key yang tersimpan
            $rawKey = trim((string) $request->input('api_key'));
            if ($rawKey === '' || str_contains($rawKey, '••••')) {
                $rawKey = Setting::get('ai_openai_api_key') ?: (config('services.openai.api_key') ?: env('OPENAI_API_KEY'));
            }

            if (empty($rawKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'API Key OpenAI belum diisi untuk pengujian koneksi.',
                ], 422);
            }

            $testResult = $this->openAi->testCustomConnection($rawKey, $baseUrl, $model, $timeout);
            return response()->json($testResult);
        }

        if ($provider === 'gemini') {
            $rawKey = trim((string) $request->input('api_key'));
            if ($rawKey === '' || str_contains($rawKey, '••••')) {
                $rawKey = Setting::get('ai_gemini_api_key') ?: (config('services.gemini.api_key') ?: env('GEMINI_API_KEY'));
            }

            if (empty($rawKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'API Key Gemini belum diisi untuk pengujian koneksi.',
                ], 422);
            }

            $testResult = $this->gemini->testCustomConnection($rawKey, $model, $timeout);
            return response()->json($testResult);
        }

        return response()->json([
            'success' => false,
            'message' => 'Provider tidak dikenali.',
        ], 400);
    }

    /**
     * Memvalidasi bahwa URL aman dan tidak mengarah ke target SSRF berbahaya.
     *
     * @param string $url
     * @return array ['safe' => bool, 'reason' => ?string]
     */
    protected function validateSafeUrl(string $url): array
    {
        $parsed = parse_url($url);
        if (!$parsed || empty($parsed['scheme']) || empty($parsed['host'])) {
            return ['safe' => false, 'reason' => 'Format URL tidak valid atau tidak memiliki hostname.'];
        }

        // 1. Skema hanya boleh http atau https
        $scheme = strtolower($parsed['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            return ['safe' => false, 'reason' => 'Hanya protokol HTTP dan HTTPS yang diizinkan.'];
        }

        // 2. Tolak autentikasi tersemat di URL (misal user:pass@evil.com)
        if (!empty($parsed['user']) || !empty($parsed['pass'])) {
            return ['safe' => false, 'reason' => 'Kredensial URL tersemat dilarang untuk alasan keamanan.'];
        }

        // 3. Port filtering jika ada
        if (!empty($parsed['port'])) {
            $allowedPorts = [80, 443, 8080, 8443, 11434]; // Port HTTP/S umum + Ollama
            if (!in_array((int) $parsed['port'], $allowedPorts, true)) {
                return ['safe' => false, 'reason' => "Port {$parsed['port']} tidak diizinkan. Gunakan port standar (80, 443, 8080, 8443, 11434)."];
            }
        }

        $host = strtolower($parsed['host']);

        // 4. Blacklist Cloud Metadata & Internal Hostnames
        $dangerousHosts = [
            '169.254.169.254',             // AWS / GCP / Azure IMDS
            'metadata.google.internal',     // GCP Metadata
            'instance-data',                // Cloud internal
            '100.100.100.200',             // Alibaba Cloud Metadata
            'fd00:ec2::254',               // AWS IPv6 IMDS
        ];

        if (in_array($host, $dangerousHosts, true)) {
            return ['safe' => false, 'reason' => 'Akses ke metadata internal cloud dilarang keras.'];
        }

        // 5. Cek IP Resolving
        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);

        if ($ip) {
            // Cek apakah mengarah ke metadata IP
            if ($ip === '169.254.169.254' || str_starts_with($ip, '169.254.')) {
                return ['safe' => false, 'reason' => 'IP terdeteksi mengarah ke subnet link-local / cloud metadata.'];
            }

            // Di production, blokir private loopback dan range internal
            if (app()->environment('production')) {
                $isPrivate = filter_var(
                    $ip,
                    FILTER_VALIDATE_IP,
                    FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                ) === false;

                if ($isPrivate) {
                    return ['safe' => false, 'reason' => 'Target IP mengarah ke jaringan privat/internal yang diblokir pada mode produksi.'];
                }
            }
        }

        return ['safe' => true, 'reason' => null];
    }

    /**
     * Memformat masking rahasia/API Key agar aman ditampilkan di browser.
     */
    protected function maskSecret(?string $key): string
    {
        if (empty($key)) {
            return '';
        }

        $len = strlen($key);
        if ($len <= 8) {
            return str_repeat('•', 8);
        }

        $prefix = substr($key, 0, 5);
        $suffix = substr($key, -4);
        return $prefix . '••••••••••••••••••••' . $suffix;
    }
}
