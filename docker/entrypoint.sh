#!/bin/bash
set -e

# Terapkan kepemilikan folder yang benar untuk user web server.
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Jangan menjalankan key:generate/migrate otomatis saat container boot.
# Perintah artisan dijalankan manual agar startup container tetap cepat dan mudah di-debug.
php artisan config:clear || true
php artisan storage:link --force || true

echo "Menjalankan php-fpm dan nginx via Supervisord..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
