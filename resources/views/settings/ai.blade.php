@extends('layouts.app')

@section('title', 'Pengaturan AI Assistant - SIPAT Terpadu')

@push('styles')
<style>
    .provider-card {
        border: 2px solid transparent;
        transition: all 0.25s ease-in-out;
        cursor: pointer;
    }
    .provider-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
    }
    .provider-card.selected {
        border-color: #3b82f6 !important;
        background-color: #f8faff !important;
    }
    .preset-badge {
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .preset-badge:hover {
        transform: scale(1.05);
        opacity: 0.9;
    }
    .test-box {
        background-color: #f8fafc;
        border-left: 4px solid #64748b;
        border-radius: 0.5rem;
    }
    .test-box.success {
        background-color: #f0fdf4;
        border-left-color: #22c55e;
    }
    .test-box.failed {
        background-color: #fef2f2;
        border-left-color: #ef4444;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
    <!-- Header & Breadcrumb -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-secondary">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('settings.index') }}" class="text-decoration-none text-secondary">Pengaturan Sistem</a></li>
                    <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">AI Assistant</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-navy mb-1">
                <i class="bi bi-robot text-primary me-2"></i>Pengaturan Mesin AI Terpadu
            </h3>
            <p class="text-secondary mb-0 small">
                Konfigurasi provider, kredensial API, model LLM (9router, OpenAI, OpenRouter, DeepSeek, Gemini), serta persona asisten cerdas SIPAT.
            </p>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary fw-semibold rounded-3 shadow-sm d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Kembali ke Pengaturan
            </a>
        </div>
    </div>

    <!-- Live Status Banner -->
    <div class="admin-card p-3 mb-4 bg-white border rounded-3 shadow-sm">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle p-3 {{ $currentServiceStatus['is_available'] ? 'bg-success bg-opacity-10 text-success' : 'bg-warning bg-opacity-10 text-warning' }}">
                    <i class="bi {{ $currentServiceStatus['is_available'] ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' }} fs-3"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge {{ $currentServiceStatus['is_available'] ? 'bg-success' : 'bg-secondary' }} px-2 py-1 rounded-pill">
                            {{ $currentServiceStatus['is_available'] ? 'ONLINE (Siap Digunakan)' : 'OFFLINE (Belum Siap)' }}
                        </span>
                        <span class="badge bg-light text-dark border px-2 py-1 rounded-pill text-uppercase">
                            Provider Aktif: <strong>{{ $currentServiceStatus['active_provider'] }}</strong>
                        </span>
                    </div>
                    <div class="small text-muted mt-1">
                        Model Aktif: <strong class="text-dark">{{ $currentServiceStatus['active_model'] ?: 'Tidak ada model' }}</strong>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="testCurrentActiveProvider()">
                    <i class="bi bi-broadcast me-1"></i> Quick Ping Test
                </button>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-shield-check fs-4 me-3 text-success"></i>
                <div>
                    <strong>Berhasil!</strong> {{ session('success') }}
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(isset($errors) && $errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert">
            <div class="d-flex align-items-start">
                <i class="bi bi-shield-x fs-4 me-3 text-danger"></i>
                <div>
                    <strong>Peringatan Keamanan / Kesalahan Validasi:</strong>
                    <ul class="mb-0 mt-1 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Form Pengaturan AI -->
    <form action="{{ route('settings.ai.update') }}" method="POST" id="formAiSettings">
        @csrf

        <!-- Card 1: Pilihan Mode Provider -->
        <div class="admin-card p-4 mb-4">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 me-3">
                    <i class="bi bi-cpu fs-4"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-navy mb-0">Prioritas & Mode Provider AI</h5>
                    <p class="text-muted small mb-0">Tentukan mesin utama pemroses prompt asisten cerdas SIPAT & E-RANDIS.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="card h-100 p-3 provider-card shadow-sm {{ old('ai_provider', $activeProvider) === 'auto' ? 'selected' : '' }}" for="provider_auto">
                        <div class="d-flex align-items-center">
                            <input class="form-check-input me-3 mt-0 fs-5" type="radio" name="ai_provider" id="provider_auto" value="auto" {{ old('ai_provider', $activeProvider) === 'auto' ? 'checked' : '' }} onchange="highlightProvider(this)">
                            <div>
                                <span class="badge bg-primary-subtle text-primary mb-1">Rekomendasi</span>
                                <h6 class="fw-bold text-dark mb-1">Auto-Detect & Fallback</h6>
                                <p class="text-muted small mb-0">Otomatis memilih OpenAI-Compatible jika siap, dan berpindah ke Gemini jika gagal.</p>
                            </div>
                        </div>
                    </label>
                </div>
                <div class="col-md-4">
                    <label class="card h-100 p-3 provider-card shadow-sm {{ old('ai_provider', $activeProvider) === 'openai' ? 'selected' : '' }}" for="provider_openai">
                        <div class="d-flex align-items-center">
                            <input class="form-check-input me-3 mt-0 fs-5" type="radio" name="ai_provider" id="provider_openai" value="openai" {{ old('ai_provider', $activeProvider) === 'openai' ? 'checked' : '' }} onchange="highlightProvider(this)">
                            <div>
                                <span class="badge bg-success-subtle text-success mb-1">9Router / DeepSeek / Groq</span>
                                <h6 class="fw-bold text-dark mb-1">OpenAI-Compatible Gateway</h6>
                                <p class="text-muted small mb-0">Prioritas penuh ke gateway 9router, OpenRouter, OpenAI resmi, atau DeepSeek.</p>
                            </div>
                        </div>
                    </label>
                </div>
                <div class="col-md-4">
                    <label class="card h-100 p-3 provider-card shadow-sm {{ old('ai_provider', $activeProvider) === 'gemini' ? 'selected' : '' }}" for="provider_gemini">
                        <div class="d-flex align-items-center">
                            <input class="form-check-input me-3 mt-0 fs-5" type="radio" name="ai_provider" id="provider_gemini" value="gemini" {{ old('ai_provider', $activeProvider) === 'gemini' ? 'checked' : '' }} onchange="highlightProvider(this)">
                            <div>
                                <span class="badge bg-info-subtle text-info mb-1">Google Cloud</span>
                                <h6 class="fw-bold text-dark mb-1">Google Gemini API</h6>
                                <p class="text-muted small mb-0">Prioritas penuh ke Google Gemini Cloud (Gemini 1.5 Flash / Pro).</p>
                            </div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Card 2: Konfigurasi OpenAI-Compatible (9Router, OpenRouter, DeepSeek, Groq, OpenAI) -->
        <div class="admin-card p-4 mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                <div class="d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 text-success rounded-3 p-2 me-3">
                        <i class="bi bi-hdd-network fs-4"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-navy mb-0">Konfigurasi OpenAI-Compatible (9Router / OpenRouter / DeepSeek / Groq)</h5>
                        <p class="text-muted small mb-0">Mendukung semua LLM provider dengan standar API OpenAI v1.</p>
                    </div>
                </div>
                <!-- 1-Click Presets -->
                <div class="mt-2 mt-md-0 d-flex flex-wrap align-items-center gap-1">
                    <span class="small text-muted me-1 fw-semibold">Preset Cepat:</span>
                    <span class="badge bg-primary preset-badge py-2 px-2.5" onclick="applyPreset('9router')">
                        <i class="bi bi-lightning-fill me-1"></i>9Router SIPAT
                    </span>
                    <span class="badge bg-dark preset-badge py-2 px-2.5" onclick="applyPreset('openrouter')">
                        OpenRouter
                    </span>
                    <span class="badge bg-info text-dark preset-badge py-2 px-2.5" onclick="applyPreset('deepseek')">
                        DeepSeek
                    </span>
                    <span class="badge bg-warning text-dark preset-badge py-2 px-2.5" onclick="applyPreset('groq')">
                        Groq Fast
                    </span>
                    <span class="badge bg-secondary preset-badge py-2 px-2.5" onclick="applyPreset('openai')">
                        OpenAI Resmi
                    </span>
                </div>
            </div>

            <div class="row g-3">
                <!-- Base URL -->
                <div class="col-md-7">
                    <label class="form-label fw-semibold text-dark small text-uppercase" for="ai_openai_base_url">
                        Base URL API <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-secondary"><i class="bi bi-link-45deg"></i></span>
                        <input type="url" name="ai_openai_base_url" id="ai_openai_base_url" class="form-control rounded-end-3" 
                               value="{{ old('ai_openai_base_url', $openAiBaseUrl) }}" required 
                               placeholder="https://9router.sipat-donggala.my.id/v1">
                    </div>
                    <div class="form-text small text-muted">
                        Pastikan diakhiri dengan <code>/v1</code> (misal: <code>https://9router.sipat-donggala.my.id/v1</code> atau <code>https://openrouter.ai/api/v1</code>).
                    </div>
                </div>

                <!-- Model -->
                <div class="col-md-5">
                    <label class="form-label fw-semibold text-dark small text-uppercase" for="ai_openai_model">
                        Nama Model LLM <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-secondary"><i class="bi bi-box-seam"></i></span>
                        <input type="text" name="ai_openai_model" id="ai_openai_model" class="form-control rounded-end-3" 
                               value="{{ old('ai_openai_model', $openAiModel) }}" required 
                               placeholder="gh/gpt-4o-mini atau deepseek/deepseek-chat">
                    </div>
                    <div class="form-text small text-muted">
                        Rekomendasi stabil 9Router: <code>gh/gpt-4o-mini</code> atau <code>anthropic/claude-3.5-haiku</code>.
                    </div>
                </div>

                <!-- API Key -->
                <div class="col-md-9">
                    <label class="form-label fw-semibold text-dark small text-uppercase d-flex justify-content-between align-items-center" for="ai_openai_api_key">
                        <span>API Key OpenAI / Gateway</span>
                        @if($hasSavedOpenAiKey)
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                <i class="bi bi-shield-check me-1"></i>Key Tersimpan Aman
                            </span>
                        @else
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                <i class="bi bi-exclamation-circle me-1"></i>Belum Diatur
                            </span>
                        @endif
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-secondary"><i class="bi bi-key-fill"></i></span>
                        <input type="password" name="ai_openai_api_key" id="ai_openai_api_key" class="form-control" 
                               value="{{ old('ai_openai_api_key', $maskedOpenAiKey) }}" 
                               placeholder="Masukkan API Key (cth: sk-...) atau biarkan jika tidak ingin mengubah">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('ai_openai_api_key', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="form-text small text-muted">
                        <i class="bi bi-info-circle me-1"></i>Demi keamanan, API Key disembunyikan. Jika input tidak diubah atau mengandung bullet mask, kunci lama tetap digunakan.
                    </div>
                </div>

                <!-- Timeout -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold text-dark small text-uppercase" for="ai_openai_timeout">
                        Timeout (Detik) <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-secondary"><i class="bi bi-stopwatch"></i></span>
                        <input type="number" name="ai_openai_timeout" id="ai_openai_timeout" class="form-control rounded-end-3" 
                               value="{{ old('ai_openai_timeout', $openAiTimeout) }}" min="5" max="120" required>
                    </div>
                    <div class="form-text small text-muted">Bawaan: 35 detik (Max: 120s).</div>
                </div>

                <!-- Action Button: Test Connection OpenAI -->
                <div class="col-12 mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                    <span class="small text-muted">Uji kelayakan endpoint dan key sebelum menyimpan:</span>
                    <button type="button" class="btn btn-outline-success fw-semibold rounded-3 px-3 shadow-sm d-flex align-items-center gap-2" id="btnTestOpenAi" onclick="testConnection('openai')">
                        <i class="bi bi-broadcast"></i> Test Koneksi Gateway / OpenAI
                    </button>
                </div>

                <!-- Live Test Result Container OpenAI -->
                <div class="col-12 d-none" id="openaiTestContainer">
                    <div class="test-box p-3" id="openaiTestBox">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fw-bold mb-0" id="openaiTestTitle"><i class="bi bi-arrow-repeat spin me-2"></i>Sedang Menguji...</h6>
                            <span class="badge bg-dark rounded-pill px-2.5" id="openaiTestLatency">- ms</span>
                        </div>
                        <div class="small" id="openaiTestMessage">Menghubungi endpoint API...</div>
                        <div class="mt-2 pt-2 border-top border-secondary border-opacity-10 small font-monospace d-none" id="openaiTestReplyWrapper">
                            <span class="text-muted">Respon AI:</span> <span class="text-dark" id="openaiTestReply"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Konfigurasi Google Gemini Cloud AI -->
        <div class="admin-card p-4 mb-4">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-info bg-opacity-10 text-info rounded-3 p-2 me-3">
                    <i class="bi bi-google fs-4"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-navy mb-0">Konfigurasi Google Gemini Cloud AI (Cadangan / Alternatif)</h5>
                    <p class="text-muted small mb-0">Layanan AI bawaan Google Developer Studio dengan kecepatan tinggi dan token gratis melimpah.</p>
                </div>
            </div>

            <div class="row g-3">
                <!-- Gemini Model -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold text-dark small text-uppercase" for="ai_gemini_model">
                        Model Gemini
                    </label>
                    <select name="ai_gemini_model" id="ai_gemini_model" class="form-select rounded-3">
                        <option value="gemini-1.5-flash" {{ old('ai_gemini_model', $geminiModel) === 'gemini-1.5-flash' ? 'selected' : '' }}>gemini-1.5-flash (Cepat & Direkomendasikan)</option>
                        <option value="gemini-1.5-flash-8b" {{ old('ai_gemini_model', $geminiModel) === 'gemini-1.5-flash-8b' ? 'selected' : '' }}>gemini-1.5-flash-8b (Ultra Cepat)</option>
                        <option value="gemini-1.5-pro" {{ old('ai_gemini_model', $geminiModel) === 'gemini-1.5-pro' ? 'selected' : '' }}>gemini-1.5-pro (Akurasi Tinggi)</option>
                        <option value="gemini-2.0-flash" {{ old('ai_gemini_model', $geminiModel) === 'gemini-2.0-flash' ? 'selected' : '' }}>gemini-2.0-flash (Generasi Terbaru)</option>
                    </select>
                </div>

                <!-- Gemini API Key -->
                <div class="col-md-5">
                    <label class="form-label fw-semibold text-dark small text-uppercase d-flex justify-content-between align-items-center" for="ai_gemini_api_key">
                        <span>Gemini API Key</span>
                        @if($hasSavedGeminiKey)
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                <i class="bi bi-shield-check me-1"></i>Key Tersimpan
                            </span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                Tidak Aktif
                            </span>
                        @endif
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-secondary"><i class="bi bi-key"></i></span>
                        <input type="password" name="ai_gemini_api_key" id="ai_gemini_api_key" class="form-control" 
                               value="{{ old('ai_gemini_api_key', $maskedGeminiKey) }}" 
                               placeholder="Masukkan AI Studio API Key (AIzaSy...)">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('ai_gemini_api_key', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Gemini Timeout -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold text-dark small text-uppercase" for="ai_gemini_timeout">
                        Timeout (Detik)
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-secondary"><i class="bi bi-stopwatch"></i></span>
                        <input type="number" name="ai_gemini_timeout" id="ai_gemini_timeout" class="form-control rounded-end-3" 
                               value="{{ old('ai_gemini_timeout', $geminiTimeout) }}" min="5" max="120">
                    </div>
                </div>

                <!-- Action Button: Test Connection Gemini -->
                <div class="col-12 mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                    <span class="small text-muted">Uji kelayakan Google Gemini API:</span>
                    <button type="button" class="btn btn-outline-info fw-semibold rounded-3 px-3 shadow-sm d-flex align-items-center gap-2" id="btnTestGemini" onclick="testConnection('gemini')">
                        <i class="bi bi-broadcast"></i> Test Koneksi Google Gemini
                    </button>
                </div>

                <!-- Live Test Result Container Gemini -->
                <div class="col-12 d-none" id="geminiTestContainer">
                    <div class="test-box p-3" id="geminiTestBox">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fw-bold mb-0" id="geminiTestTitle"><i class="bi bi-arrow-repeat spin me-2"></i>Sedang Menguji...</h6>
                            <span class="badge bg-dark rounded-pill px-2.5" id="geminiTestLatency">- ms</span>
                        </div>
                        <div class="small" id="geminiTestMessage">Menghubungi Google Gemini API...</div>
                        <div class="mt-2 pt-2 border-top border-secondary border-opacity-10 small font-monospace d-none" id="geminiTestReplyWrapper">
                            <span class="text-muted">Respon AI:</span> <span class="text-dark" id="geminiTestReply"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: System Prompt & Persona Asisten -->
        <div class="admin-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center">
                    <div class="bg-purple bg-opacity-10 text-primary rounded-3 p-2 me-3">
                        <i class="bi bi-chat-quote-fill fs-4"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-navy mb-0">System Prompt & Persona Asisten SIPAT</h5>
                        <p class="text-muted small mb-0">Instruksi tersembunyi yang mendasari kepribadian dan format jawaban asisten kepada staf OPD.</p>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-link text-decoration-none" onclick="resetDefaultPrompt()">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Gunakan Standar SIPAT
                </button>
            </div>

            <div class="mb-2">
                <textarea name="ai_system_prompt" id="ai_system_prompt" class="form-control rounded-3 font-monospace" rows="5" 
                          placeholder="Instruksi sistem default SIPAT...">{{ old('ai_system_prompt', $systemPrompt) }}</textarea>
            </div>
            <div class="form-text small text-muted">
                Tip: Tekankan instruksi bahwa AI harus menjawab secara profesional, langsung pada poin, tanpa mencetak <code>&lt;think&gt;</code> atau catatan draft internal.
            </div>
        </div>

        <!-- Card 5: Security Notice & Submit -->
        <div class="row align-items-center g-3 mb-5">
            <div class="col-md-8">
                <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border">
                    <i class="bi bi-shield-lock-fill fs-2 text-primary"></i>
                    <div class="small text-secondary">
                        <strong class="text-dark d-block">Perlindungan Keamanan Maksimal (Enterprise Grade)</strong>
                        Endpoint dilindungi verifikasi otorisasi Super Administrator, pencegahan SSRF (Server-Side Request Forgery) terhadap jaringan privat, masking kredensial, dan pencatatan audit log terpadu.
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <button type="submit" class="btn btn-primary btn-lg rounded-3 fw-bold px-4 py-2.5 shadow-sm w-100 w-md-auto d-inline-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-save2-fill"></i> Simpan Konfigurasi AI
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    // Presets Definition
    const AI_PRESETS = {
        '9router': {
            baseUrl: 'https://9router.sipat-donggala.my.id/v1',
            model: 'gh/gpt-4o-mini',
            timeout: 35
        },
        'openrouter': {
            baseUrl: 'https://openrouter.ai/api/v1',
            model: 'deepseek/deepseek-chat',
            timeout: 45
        },
        'deepseek': {
            baseUrl: 'https://api.deepseek.com/v1',
            model: 'deepseek-chat',
            timeout: 45
        },
        'groq': {
            baseUrl: 'https://api.groq.com/openai/v1',
            model: 'llama-3.3-70b-versatile',
            timeout: 25
        },
        'openai': {
            baseUrl: 'https://api.openai.com/v1',
            model: 'gpt-4o-mini',
            timeout: 35
        }
    };

    function applyPreset(presetKey) {
        const config = AI_PRESETS[presetKey];
        if (!config) return;

        document.getElementById('ai_openai_base_url').value = config.baseUrl;
        document.getElementById('ai_openai_model').value = config.model;
        document.getElementById('ai_openai_timeout').value = config.timeout;

        // Visual flash highlight
        const inputs = [
            document.getElementById('ai_openai_base_url'),
            document.getElementById('ai_openai_model'),
            document.getElementById('ai_openai_timeout')
        ];
        inputs.forEach(el => {
            el.style.backgroundColor = '#dbeafe';
            setTimeout(() => { el.style.backgroundColor = ''; }, 600);
        });
    }

    function highlightProvider(radio) {
        document.querySelectorAll('.provider-card').forEach(card => card.classList.remove('selected'));
        const label = document.querySelector(`label[for="${radio.id}"]`);
        if (label) {
            label.classList.add('selected');
        }
    }

    function togglePasswordVisibility(fieldId, btn) {
        const field = document.getElementById(fieldId);
        const icon = btn.querySelector('i');
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }

    function resetDefaultPrompt() {
        const defaultPrompt = "Kamu adalah Asisten Pintar Pengelolaan Barang Milik Daerah (BMD) dan Aset Terpadu (SIPAT & E-RANDIS).\n\n"
            + "PETUNJUK:\n"
            + "- Langsung berikan jawaban akhir yang rapi dan profesional untuk pengguna dalam Bahasa Indonesia.\n"
            + "- Dilarang keras menampilkan proses berpikir, catatan drafting, internal monologue, atau analisis peran.\n"
            + "- Gunakan format poin-poin yang terstruktur, padat, dan informatif.";
        document.getElementById('ai_system_prompt').value = defaultPrompt;
    }

    // Ajax Test Connection
    async function testConnection(provider) {
        const isOai = provider === 'openai';
        const container = document.getElementById(isOai ? 'openaiTestContainer' : 'geminiTestContainer');
        const box = document.getElementById(isOai ? 'openaiTestBox' : 'geminiTestBox');
        const title = document.getElementById(isOai ? 'openaiTestTitle' : 'geminiTestTitle');
        const latency = document.getElementById(isOai ? 'openaiTestLatency' : 'geminiTestLatency');
        const message = document.getElementById(isOai ? 'openaiTestMessage' : 'geminiTestMessage');
        const replyWrapper = document.getElementById(isOai ? 'openaiTestReplyWrapper' : 'geminiTestReplyWrapper');
        const replyText = document.getElementById(isOai ? 'openaiTestReply' : 'geminiTestReply');
        const btn = document.getElementById(isOai ? 'btnTestOpenAi' : 'btnTestGemini');

        container.classList.remove('d-none');
        box.className = 'test-box p-3';
        title.innerHTML = '<i class="bi bi-arrow-repeat spin me-2"></i>Sedang Menguji Koneksi...';
        latency.textContent = 'Memproses...';
        message.textContent = 'Mengirim prompt handshake ke endpoint ' + (isOai ? 'OpenAI-Compatible' : 'Google Gemini') + '...';
        replyWrapper.classList.add('d-none');
        btn.disabled = true;

        const payload = {
            _token: '{{ csrf_token() }}',
            provider: provider,
            model: isOai ? document.getElementById('ai_openai_model').value : document.getElementById('ai_gemini_model').value,
            timeout: 15,
            api_key: isOai ? document.getElementById('ai_openai_api_key').value : document.getElementById('ai_gemini_api_key').value
        };

        if (isOai) {
            payload.base_url = document.getElementById('ai_openai_base_url').value;
        }

        try {
            const res = await fetch("{{ route('settings.ai.test') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();

            if (res.ok && data.success) {
                box.className = 'test-box success p-3';
                title.innerHTML = '<i class="bi bi-check-circle-fill text-success me-2"></i>Koneksi Berhasil!';
                latency.className = 'badge bg-success rounded-pill px-2.5';
                latency.textContent = (data.latency_ms || 0) + ' ms';
                message.textContent = data.message || 'Layanan merespons dengan normal.';
                if (data.reply) {
                    replyWrapper.classList.remove('d-none');
                    replyText.textContent = data.reply;
                }
            } else {
                box.className = 'test-box failed p-3';
                title.innerHTML = '<i class="bi bi-x-circle-fill text-danger me-2"></i>Koneksi Gagal';
                latency.className = 'badge bg-danger rounded-pill px-2.5';
                latency.textContent = (data.latency_ms ? data.latency_ms + ' ms' : 'Gagal');
                message.textContent = data.message || 'Terjadi kesalahan saat menghubungi API.';
            }
        } catch (err) {
            box.className = 'test-box failed p-3';
            title.innerHTML = '<i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Kesalahan Jaringan';
            latency.className = 'badge bg-danger rounded-pill px-2.5';
            latency.textContent = 'Error';
            message.textContent = err.message || 'Gagal mengirim request ke server lokal.';
        } finally {
            btn.disabled = false;
        }
    }

    function testCurrentActiveProvider() {
        const active = '{{ $currentServiceStatus["active_provider"] }}';
        if (active === 'gemini') {
            testConnection('gemini');
        } else {
            testConnection('openai');
        }
    }
</script>
<style>
    .spin {
        animation: spin 1s linear infinite;
        display: inline-block;
    }
    @keyframes spin {
        100% { transform: rotate(360deg); }
    }
</style>
@endpush
