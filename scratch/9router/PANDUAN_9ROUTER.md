# 🚀 PANDUAN PENGGUNAAN & INTEGRASI 9ROUTER DOCKER

Dokumen ini berisi panduan lengkap pengoperasian, konfigurasi, dan integrasi **9Router** (AI Gateway & Smart LLM Proxy) yang telah terpasang berbasis Docker di server Anda.

---

## 📌 Ringkasan Instalasi 9Router

- **Direktori Projek**: `/home/arif/Projek/9router`
- **Container Name**: `9router`
- **Docker Image**: `decolua/9router:latest`
- **Port Default**: `20128`
- **Web Dashboard GUI**: `http://localhost:20128` atau `http://192.168.1.184:20128`
- **API Endpoint (`v1` OpenAI Compatible)**: `http://localhost:20128/v1`
- **Volume Data Persisten**: `/home/arif/Projek/9router/data` (Menyimpan SQLite DB & konfigurasi API Key)

---

## 🛠️ Perintah Pengelolaan Docker (CLI)

Seluruh perintah dijalankan dari folder `/home/arif/Projek/9router`:

### 1. Menjalankan Container 9Router (Background Mode)
```bash
cd /home/arif/Projek/9router && docker-compose up -d
```

### 2. Memeriksa Status Container & Log Aktivitas
```bash
# Cek status berjalan
docker ps | grep 9router

# Cek log aktivitas real-time
cd /home/arif/Projek/9router && docker-compose logs -f
```

### 3. Menghentikan Container
```bash
cd /home/arif/Projek/9router && docker-compose down
```

### 4. Memperbarui ke Versi Terbaru (*Update Latest Build*)
```bash
cd /home/arif/Projek/9router
docker-compose pull
docker-compose up -d
```

---

## 🌐 Cara Akses Web Dashboard GUI

1. Buka browser komputer Anda dan akses:
   ```text
   http://192.168.1.184:20128
   ```
   *(Atau via VS Code SSH Port Forwarding pada `http://localhost:20128`)*

2. Di dalam Web Dashboard 9Router, Anda dapat:
   - Menambahkan **API Keys** dari berbagai provider AI (Google Gemini, OpenAI, Anthropic Claude, Groq, DeepSeek, Ollama, dll).
   - Mengatur **Fallback Rules** (misalnya: Jika Gemini 1.5 Pro kena rate-limit, otomatis alihkan request ke Claude 3.5 Sonnet atau Groq Llama 3).
   - Mengaktifkan **RTK Token Saver** untuk mengompresi prompt & git log.
   - Memantau grafik statistik penggunaan token & estimasi biaya (*Token Usage Analytics*).

---

## 💻 Integrasi 9Router ke Alat Coding AI

Setelah 9Router berjalan dan API Key dikonfigurasi di Web Dashboard, sambungkan tool AI pilihan Anda ke endpoint 9Router:

### 1. Integrasi pada **Cursor IDE**
1. Buka Cursor IDE `Settings > OpenAI API Key`.
2. Centang **Override OpenAI Base URL**.
3. Set **Base URL**:
   ```text
   http://localhost:20128/v1
   ```
4. Masukkan **API Key**: (Gunakan API Key buatan 9Router atau string acak `sk-9router-local`).

### 2. Integrasi pada **Claude Code CLI**
Jalankan perintah ini di terminal sebelum memanggil `claude`:
```bash
export ANTHROPIC_BASE_URL="http://localhost:20128/v1"
export ANTHROPIC_API_KEY="sk-9router-local"
```

### 3. Integrasi pada **Cline / Roo Code (VS Code Extension)**
1. Buka konfigurasi extension Cline.
2. Pilih **API Provider**: `OpenAI Compatible`.
3. Set **Base URL**: `http://localhost:20128/v1`
4. Set **Model ID**: (Pilih model yang diaktifkan di 9Router, contoh: `gemini-1.5-pro` atau `claude-3-5-sonnet`).

### 4. Integrasi pada **Continue.dev (VS Code / JetBrains)**
Tambahkan konfigurasi pada `~/.continue/config.json`:
```json
{
  "models": [
    {
      "title": "9Router Unified Proxy",
      "provider": "openai",
      "model": "gemini-1.5-pro",
      "apiBase": "http://localhost:20128/v1",
      "apiKey": "sk-9router-local"
    }
  ]
}
```

---

## 🔒 Catatan Keamanan
- Berkas data dan SQLite terisolasi di folder `/home/arif/Projek/9router/data`.
- Seluruh API Key provider yang Anda simpan tersimpan aman di server lokal tanpa dikirim ke server pihak ketiga.
