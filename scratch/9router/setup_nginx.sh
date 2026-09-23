#!/bin/bash
cat << 'EOF' | sudo tee /etc/nginx/sites-available/9router
server {
    listen 80;
    listen [::]:80;

    server_name 9router.sipat-donggala.my.id 9router.192-168-1-184.nip.io router.sipat-donggala.my.id 9router.test;

    location / {
        proxy_pass http://127.0.0.1:20128;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
EOF

sudo ln -sf /etc/nginx/sites-available/9router /etc/nginx/sites-enabled/9router
sudo nginx -t && sudo systemctl reload nginx
echo "✅ Nginx reverse proxy untuk 9Router berhasil diaktifkan di http://9router.sipat-donggala.my.id!"
