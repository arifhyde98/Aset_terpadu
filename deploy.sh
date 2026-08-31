#!/bin/bash
set -e

echo "=========================================="
echo "🚀 [CI/CD] Memulai Proses Deployment SIPAT"
echo "=========================================="

# Navigasi ke direktori proyek
cd "$(dirname "$0")"

# 1. Ambil kode terbaru dari Git
echo "📥 1. Mengambil kode terbaru dari GitHub..."
git fetch origin main
git reset --hard origin/main

# 2. Install dependensi Composer
echo "📦 2. Menginstal dependensi Composer..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# 3. Jalankan migrasi database (aman, tidak mereset data)
echo "🗄️ 3. Menjalankan migrasi database..."
php artisan migrate --force

# 4. Build Frontend Assets
echo "🎨 4. Membangun aset frontend (Vite)..."
if [ -f package-lock.json ]; then
    npm ci --prefer-offline --no-audit || npm install
else
    npm install
fi
npm run build

# 5. Optimasi Cache Laravel
echo "⚡ 5. Mengoptimasi cache konfigurasi & rute..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=========================================="
echo "✅ [CI/CD] Deployment Sukses!"
echo "=========================================="

