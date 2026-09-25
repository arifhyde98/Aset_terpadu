#!/bin/bash
set -e

NGINX_CONF="/etc/nginx/sites-available/sipat-terpadu"

echo "=== Menerapkan Aturan Penguncian Nginx ==="

if [ ! -f "$NGINX_CONF" ]; then
    echo "Berkas $NGINX_CONF tidak ditemukan!"
    exit 1
fi

if grep -q "location ^~ /storage/elabel/" "$NGINX_CONF"; then
    echo "Aturan blokir /storage/elabel/ sudah terpasang di $NGINX_CONF."
else
    # Sisipkan aturan blokir tepat sebelum 'location / {'
    sed -i '/location \/ {/i \    # Blokir akses publik langsung ke berkas arsip elabel (BPKB & Sertifikat)\n    location ^~ /storage/elabel/ {\n        deny all;\n        return 404;\n    }\n' "$NGINX_CONF"
    echo "Aturan berhasil disisipkan ke $NGINX_CONF."
fi

echo "Memverifikasi konfigurasi Nginx..."
nginx -t

echo "Memuat ulang Nginx..."
systemctl reload nginx

echo "=================================================="
echo "✅ SUKSES: Nginx telah mengunci /storage/elabel/!"
echo "Semua request langsung ke berkas publik lama kini dibalas 404."
echo "=================================================="
