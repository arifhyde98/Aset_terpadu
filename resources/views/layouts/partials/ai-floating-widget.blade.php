<!-- AI Assistant Floating Widget (Vibrant & Colourful Edition) -->
<div id="ai-assistant-widget" class="ai-widget-wrapper">
    <!-- Floating Trigger Button with Pulsing Glow -->
    <button id="aiWidgetToggle" type="button" class="btn ai-widget-btn shadow-2xl rounded-circle d-flex align-items-center justify-content-center border-0" aria-label="Buka Asisten AI" title="Tanya Asisten AI (qwen2.5:7b)">
        <div class="ai-glow-effect"></div>
        <i class="bi bi-stars fs-3 text-white ai-icon-spin" id="aiToggleIcon"></i>
        <span id="aiStatusDot" class="ai-status-indicator bg-warning" title="Memeriksa status AI..."></span>
    </button>

    <!-- Floating Chat Window Container -->
    <div id="aiChatWindow" class="ai-chat-window shadow-2xl rounded-4 d-none">
        <!-- Header with Colorful Gradient Mesh -->
        <div class="ai-chat-header px-3 py-3 rounded-top-4 position-relative overflow-hidden">
            <div class="ai-header-bg-shapes"></div>
            <div class="d-flex align-items-center justify-content-between position-relative z-1">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="ai-avatar-badge rounded-circle d-flex align-items-center justify-content-center shadow-sm">
                        <i class="bi bi-robot fs-5 text-white"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-1.5">
                            <span class="fw-bold fs-6 text-white lh-1">SIPAT AI Assistant</span>
                            <span class="badge bg-white bg-opacity-25 text-white fw-bold px-1.5 py-0.5 rounded-pill" style="font-size: 0.6rem; letter-spacing: 0.5px;">PRO</span>
                        </div>
                        <div class="d-flex align-items-center gap-1.5 mt-1">
                            <span id="aiHeaderStatusDot" class="ai-pulse-dot bg-warning"></span>
                            <span id="aiHeaderStatusText" class="text-white text-opacity-90 fw-medium" style="font-size: 0.72rem;">Menghubungkan...</span>
                            <span class="ai-model-tag badge rounded-pill px-1.5 py-0.5 text-white" id="aiModelTag" style="font-size: 0.62rem; display: none;">qwen2.5:7b</span>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <button type="button" id="aiRefreshStatusBtn" class="btn btn-sm ai-btn-icon text-white p-1.5 rounded-circle" title="Segarkan Status">
                        <i class="bi bi-arrow-clockwise fs-6"></i>
                    </button>
                    <button type="button" id="aiClearChatBtn" class="btn btn-sm ai-btn-icon text-white p-1.5 rounded-circle" title="Bersihkan Obrolan">
                        <i class="bi bi-trash3 fs-6"></i>
                    </button>
                    <button type="button" id="aiCloseChatBtn" class="btn btn-sm ai-btn-icon text-white p-1.5 rounded-circle" title="Tutup">
                        <i class="bi bi-x-lg fs-6"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Offline Banner Alert (Dynamic) -->
        <div id="aiOfflineBanner" class="ai-offline-banner py-2 px-3 d-none d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <span class="ai-badge-pulse-warning"></span>
                <span class="small fw-semibold text-amber-900">
                    Layanan AI Tidak Terhubung: Periksa koneksi internet atau <code>GEMINI_API_KEY</code> di .env
                </span>
            </div>
            <span class="badge bg-amber-500 text-white rounded-pill px-2 py-0.5" style="font-size: 0.65rem;">Periksa</span>
        </div>

        <!-- Body / Chat Messages Container -->
        <div id="aiChatMessages" class="ai-chat-body p-3 overflow-y-auto">
            <!-- Welcome Hero Card -->
            <div class="ai-welcome-card p-3 rounded-4 mb-3 border-0 shadow-sm position-relative overflow-hidden">
                <div class="ai-card-glow"></div>
                <div class="d-flex align-items-start gap-2.5 position-relative z-1">
                    <div class="ai-hero-icon rounded-3 p-2 d-flex align-items-center justify-content-center">
                        <i class="bi bi-magic fs-4 text-white"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 text-gradient-primary">Halo, {{ Auth::user()->name ?? 'Rekan Pengelola' }}! 👋</h6>
                        <p class="small text-muted mb-0 lh-sm">
                            Saya siap membantu analisis data aset daerah, status pensertifikatan tanah (SIPAT), dan pencatatan BPKB (E-RANDIS).
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quick Action Chips (Colorful Pills) -->
            <div class="mb-2">
                <div class="text-uppercase fw-bold text-muted px-1 mb-1.5" style="font-size: 0.65rem; letter-spacing: 0.8px;">
                    💡 Topik Populer
                </div>
                <div id="aiQuickChips" class="d-flex flex-wrap gap-1.5">
                    <button type="button" class="btn btn-sm ai-chip ai-chip-purple rounded-pill py-1 px-2.5" data-prompt="Jelaskan alur dan syarat pensertifikatan tanah aset daerah di SIPAT">
                        📜 Alur Pensertifikatan Tanah
                    </button>
                    <button type="button" class="btn btn-sm ai-chip ai-chip-emerald rounded-pill py-1 px-2.5" data-prompt="Bagaimana cara cetak QR Code dan stiker label dokumen BPKB?">
                        🏷️ Cetak Label & QR BPKB
                    </button>
                    <button type="button" class="btn btn-sm ai-chip ai-chip-blue rounded-pill py-1 px-2.5" data-prompt="Jelaskan 3 kriteria kondisi kendaraan dinas (Baik, Rusak Ringan, Rusak Berat) menurut Permendagri">
                        🚗 Kriteria Kondisi Randis
                    </button>
                    <button type="button" class="btn btn-sm ai-chip ai-chip-rose rounded-pill py-1 px-2.5" data-prompt="Bagaimana alur penghapusan atau pemindahtanganan BPKB keluar?">
                        📤 Alur BPKB Keluar
                    </button>
                </div>
            </div>
        </div>

        <!-- Thinking Indicator with Colorful Wave Animation -->
        <div id="aiThinkingIndicator" class="ai-thinking px-3 py-2.5 d-none">
            <div class="d-flex align-items-center gap-2">
                <div class="ai-wave-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <span class="small fw-semibold text-gradient-primary" id="aiThinkingText">
                    AI sedang meracik jawaban...
                </span>
            </div>
        </div>

        <!-- Footer / Input Form -->
        <div class="ai-chat-footer p-2.5 rounded-bottom-4">
            <form id="aiChatForm" class="d-flex align-items-center gap-2 mb-0">
                <div class="position-relative flex-grow-1">
                    <input type="text" id="aiInputPrompt" class="form-control ai-input-field rounded-pill px-3.5 py-2 border-0" placeholder="Tanyakan apa saja seputar data aset..." autocomplete="off" maxlength="1000" required>
                </div>
                <button type="submit" id="aiSendBtn" class="btn ai-send-btn rounded-circle d-flex align-items-center justify-content-center border-0 shadow-md" title="Kirim Pesan">
                    <i class="bi bi-arrow-up-short fs-4 text-white"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<style>
/* ==========================================================================
   AI Floating Widget Modern Colorful UI
   ========================================================================== */

:root {
    --ai-grad-primary: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #ec4899 100%);
    --ai-grad-header: linear-gradient(135deg, #3730a3 0%, #6366f1 50%, #8b5cf6 100%);
    --ai-grad-user: linear-gradient(135deg, #4338ca 0%, #6366f1 100%);
    --ai-grad-hero: linear-gradient(135deg, #f0fdf4 0%, #e0e7ff 50%, #fdf2f8 100%);
    --ai-grad-hero-dark: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #3730a3 100%);
}

.ai-widget-wrapper {
    position: fixed;
    bottom: 26px;
    right: 26px;
    z-index: 1060;
    font-family: inherit;
}

/* Floating Trigger Button */
.ai-widget-btn {
    width: 58px;
    height: 58px;
    background: var(--ai-grad-primary);
    position: relative;
    box-shadow: 0 10px 25px -3px rgba(99, 102, 241, 0.5), 0 4px 6px -2px rgba(236, 72, 153, 0.3);
    transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
    cursor: pointer;
}

.ai-widget-btn:hover {
    transform: translateY(-4px) scale(1.06);
    box-shadow: 0 16px 32px -4px rgba(99, 102, 241, 0.6), 0 8px 16px -2px rgba(236, 72, 153, 0.4);
}

.ai-glow-effect {
    position: absolute;
    inset: -3px;
    border-radius: 50%;
    background: var(--ai-grad-primary);
    filter: blur(8px);
    opacity: 0.6;
    z-index: -1;
    animation: aiPulseGlow 3s ease-in-out infinite alternate;
}

@keyframes aiPulseGlow {
    0% { transform: scale(0.95); opacity: 0.4; }
    100% { transform: scale(1.15); opacity: 0.8; }
}

.ai-status-indicator {
    position: absolute;
    top: 2px;
    right: 2px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2.5px solid #ffffff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    transition: background-color 0.3s;
}

/* Chat Window */
.ai-chat-window {
    position: absolute;
    bottom: 72px;
    right: 0;
    width: 385px;
    max-width: calc(100vw - 32px);
    height: 540px;
    max-height: calc(100vh - 120px);
    background: #ffffff;
    border: 1px solid rgba(99, 102, 241, 0.15);
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 40px -10px rgba(15, 23, 42, 0.25), 0 0 0 1px rgba(99, 102, 241, 0.1);
    animation: aiSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    z-index: 1061;
}

/* Header */
.ai-chat-header {
    background: var(--ai-grad-header);
}

.ai-header-bg-shapes {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle at 15% 50%, rgba(236, 72, 153, 0.3) 0%, transparent 50%),
                      radial-gradient(circle at 85% 30%, rgba(59, 130, 246, 0.3) 0%, transparent 50%);
    pointer-events: none;
}

.ai-avatar-badge {
    width: 36px;
    height: 36px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.ai-btn-icon {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(4px);
    border: none;
    transition: all 0.2s;
}

.ai-btn-icon:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
}

.ai-pulse-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
}

.ai-pulse-dot.bg-success {
    animation: aiDotPulse 2s infinite;
}

@keyframes aiDotPulse {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

.ai-model-tag {
    background: rgba(255, 255, 255, 0.22);
    backdrop-filter: blur(4px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Offline Banner */
.ai-offline-banner {
    background: #fffbeb;
    border-bottom: 1px solid #fef3c7;
}

.ai-badge-pulse-warning {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #f59e0b;
    display: inline-block;
}

/* Welcome Card */
.ai-welcome-card {
    background: var(--ai-grad-hero);
    border: 1px solid rgba(99, 102, 241, 0.15);
}

.ai-hero-icon {
    background: var(--ai-grad-primary);
    box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
}

.text-gradient-primary {
    background: var(--ai-grad-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* Chips / Topic Pills */
.ai-chip {
    font-size: 0.74rem;
    font-weight: 500;
    transition: all 0.25s ease;
    border-width: 1px;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.ai-chip:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.08);
}

.ai-chip-purple {
    background: #f5f3ff;
    color: #6d28d9;
    border-color: #ddd6fe;
}
.ai-chip-purple:hover {
    background: #6d28d9;
    color: #ffffff;
}

.ai-chip-emerald {
    background: #ecfdf5;
    color: #047857;
    border-color: #a7f3d0;
}
.ai-chip-emerald:hover {
    background: #047857;
    color: #ffffff;
}

.ai-chip-blue {
    background: #eff6ff;
    color: #1d4ed8;
    border-color: #bfdbfe;
}
.ai-chip-blue:hover {
    background: #1d4ed8;
    color: #ffffff;
}

.ai-chip-rose {
    background: #fff1f2;
    color: #be123c;
    border-color: #fecdd3;
}
.ai-chip-rose:hover {
    background: #be123c;
    color: #ffffff;
}

/* Chat Messages */
.ai-chat-body {
    flex: 1;
    overflow-y: auto;
    background: #f8fafc;
}

.ai-message-user {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.ai-message-user .ai-bubble {
    background: var(--ai-grad-user) !important;
    color: #ffffff !important;
    border: none !important;
    border-bottom-right-radius: 4px !important;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
}

.ai-message-bot {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.ai-message-bot .ai-bubble {
    background: #ffffff;
    color: #1e293b;
    border: 1px solid #e2e8f0;
    border-bottom-left-radius: 4px !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    white-space: pre-wrap;
    word-break: break-word;
}

/* Thinking Animation */
.ai-thinking {
    background: #f1f5f9;
    border-top: 1px solid #e2e8f0;
}

.ai-wave-dots span {
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    margin-right: 3px;
    animation: aiWave 1.4s infinite ease-in-out both;
}

.ai-wave-dots span:nth-child(1) { background: #6366f1; animation-delay: -0.32s; }
.ai-wave-dots span:nth-child(2) { background: #8b5cf6; animation-delay: -0.16s; }
.ai-wave-dots span:nth-child(3) { background: #ec4899; }

@keyframes aiWave {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}

/* Footer & Input */
.ai-chat-footer {
    background: #ffffff;
    border-top: 1px solid #f1f5f9;
}

.ai-input-field {
    background: #f1f5f9;
    font-size: 0.84rem;
    transition: all 0.2s;
}

.ai-input-field:focus {
    background: #ffffff;
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.35);
}

.ai-send-btn {
    width: 36px;
    height: 36px;
    background: var(--ai-grad-primary);
    transition: all 0.2s;
}

.ai-send-btn:hover {
    transform: scale(1.08);
    box-shadow: 0 4px 10px rgba(99, 102, 241, 0.4);
}

/* Dark Mode Overrides */
[data-bs-theme="dark"] .ai-chat-window,
[data-theme="dark"] .ai-chat-window {
    background: #0f172a;
    border-color: rgba(99, 102, 241, 0.25);
    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.6);
}

[data-bs-theme="dark"] .ai-chat-body,
[data-theme="dark"] .ai-chat-body {
    background: #090d16;
}

[data-bs-theme="dark"] .ai-welcome-card,
[data-theme="dark"] .ai-welcome-card {
    background: var(--ai-grad-hero-dark);
    border-color: rgba(99, 102, 241, 0.25);
}

[data-bs-theme="dark"] .ai-welcome-card p,
[data-theme="dark"] .ai-welcome-card p {
    color: #94a3b8 !important;
}

[data-bs-theme="dark"] .ai-message-bot .ai-bubble,
[data-theme="dark"] .ai-message-bot .ai-bubble {
    background: #1e293b !important;
    color: #f1f5f9 !important;
    border-color: #334155 !important;
}

[data-bs-theme="dark"] .ai-chat-footer,
[data-theme="dark"] .ai-chat-footer {
    background: #0f172a !important;
    border-color: #1e293b !important;
}

[data-bs-theme="dark"] .ai-input-field,
[data-theme="dark"] .ai-input-field {
    background: #1e293b !important;
    color: #f1f5f9 !important;
}

[data-bs-theme="dark"] .ai-thinking,
[data-theme="dark"] .ai-thinking {
    background: #1e293b;
    border-color: #334155;
}

[data-bs-theme="dark"] .ai-offline-banner,
[data-theme="dark"] .ai-offline-banner {
    background: #451a03;
    border-color: #78350f;
    color: #fef3c7;
}

@keyframes aiSlideUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@media (max-width: 576px) {
    .ai-widget-wrapper {
        bottom: 80px;
        right: 16px;
    }
    .ai-chat-window {
        width: calc(100vw - 32px);
        height: 460px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const widget = document.getElementById('ai-assistant-widget');
    if (!widget) return;

    const toggleBtn     = document.getElementById('aiWidgetToggle');
    const chatWindow    = document.getElementById('aiChatWindow');
    const closeBtn      = document.getElementById('aiCloseChatBtn');
    const clearBtn      = document.getElementById('aiClearChatBtn');
    const refreshBtn    = document.getElementById('aiRefreshStatusBtn');
    const form          = document.getElementById('aiChatForm');
    const input         = document.getElementById('aiInputPrompt');
    const messagesBox   = document.getElementById('aiChatMessages');
    const thinking      = document.getElementById('aiThinkingIndicator');
    const statusDot     = document.getElementById('aiStatusDot');
    const headerDot     = document.getElementById('aiHeaderStatusDot');
    const headerStatus  = document.getElementById('aiHeaderStatusText');
    const modelTag      = document.getElementById('aiModelTag');
    const offlineBanner = document.getElementById('aiOfflineBanner');
    const csrfToken     = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    let isAiOnline = false;

    // Toggle Chat Window
    toggleBtn.addEventListener('click', () => {
        const isHidden = chatWindow.classList.contains('d-none');
        if (isHidden) {
            chatWindow.classList.remove('d-none');
            input.focus();
            checkAiStatus();
        } else {
            chatWindow.classList.add('d-none');
        }
    });

    closeBtn.addEventListener('click', () => {
        chatWindow.classList.add('d-none');
    });

    // Check Ollama Status
    async function checkAiStatus(force = false) {
        headerStatus.innerText = 'Mengecek...';
        headerDot.className = 'ai-pulse-dot bg-warning';
        
        try {
            const url = force ? '{{ route("ai.status") }}?refresh=1' : '{{ route("ai.status") }}';
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            const data = await res.json();

            isAiOnline = data.status === 'online';
            if (isAiOnline) {
                statusDot.className = 'ai-status-indicator bg-success';
                headerDot.className = 'ai-pulse-dot bg-success';
                headerStatus.innerText = 'Online';
                modelTag.innerText = data.default_model || 'qwen2.5:7b';
                modelTag.style.display = 'inline-block';
                offlineBanner.classList.add('d-none');
            } else {
                statusDot.className = 'ai-status-indicator bg-warning';
                headerDot.className = 'ai-pulse-dot bg-warning';
                headerStatus.innerText = 'Offline (Fallback)';
                modelTag.style.display = 'none';
                offlineBanner.classList.remove('d-none');
            }
        } catch (e) {
            isAiOnline = false;
            statusDot.className = 'ai-status-indicator bg-secondary';
            headerDot.className = 'ai-pulse-dot bg-secondary';
            headerStatus.innerText = 'Offline';
            modelTag.style.display = 'none';
            offlineBanner.classList.remove('d-none');
        }
    }

    refreshBtn.addEventListener('click', () => checkAiStatus(true));

    // Clear Chat
    clearBtn.addEventListener('click', () => {
        const welcomeCard = messagesBox.querySelector('.ai-welcome-card');
        const quickChips = messagesBox.querySelector('#aiQuickChips')?.parentElement;
        
        messagesBox.innerHTML = '';
        if (welcomeCard) messagesBox.appendChild(welcomeCard);
        if (quickChips) messagesBox.appendChild(quickChips);
    });

    // Quick Chips click
    document.addEventListener('click', (e) => {
        const chip = e.target.closest('.ai-chip');
        if (chip) {
            const prompt = chip.getAttribute('data-prompt');
            if (prompt) {
                input.value = prompt;
                form.dispatchEvent(new Event('submit'));
            }
        }
    });

    // Submit Prompt
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;

        input.value = '';
        appendMessage('user', text);
        thinking.classList.remove('d-none');
        messagesBox.scrollTop = messagesBox.scrollHeight;

        try {
            const response = await fetch('{{ route("ai.ask") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ prompt: text })
            });

            const result = await response.json();
            thinking.classList.add('d-none');

            if (result.success && result.data) {
                appendMessage('bot', result.data);
            } else {
                appendMessage('bot', `⚠️ ${result.message || 'Maaf, layanan AI sedang tidak dapat dihubungi di perangkat ini.'}`);
            }
        } catch (err) {
            thinking.classList.add('d-none');
            appendMessage('bot', '⚠️ Terjadi kesalahan saat menghubungi server.');
        }
    });

    function appendMessage(role, content) {
        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const isUser = role === 'user';

        const wrapper = document.createElement('div');
        wrapper.className = `ai-message ${isUser ? 'ai-message-user' : 'ai-message-bot'} mb-3`;

        wrapper.innerHTML = `
            <div class="ai-bubble p-3 rounded-4 ${isUser ? 'text-white' : ''}" style="font-size: 0.86rem; max-width: 88%;">
                ${!isUser ? '<div class="fw-bold text-gradient-primary mb-1 d-flex align-items-center gap-1.5" style="font-size: 0.78rem;"><i class="bi bi-stars"></i> Asisten SIPAT AI</div>' : ''}
                <div class="ai-message-text">${escapeHtml(content)}</div>
            </div>
            <small class="text-muted ${isUser ? 'me-1' : 'ms-1'} mt-1" style="font-size: 0.66rem;">${time}</small>
        `;

        messagesBox.appendChild(wrapper);
        messagesBox.scrollTop = messagesBox.scrollHeight;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML.replace(/\n/g, '<br>');
    }

    // Initial check on page load
    setTimeout(checkAiStatus, 800);
});
</script>
