#!/bin/bash

# 1. Install System Dependencies (FFmpeg, etc. - remove if not needed for this project)
echo "Installing Dependencies..."
apt-get update && apt-get install -y ffmpeg

# 2. SSL Certificate for Database
if [ ! -f /home/site/wwwroot/ssl/DigiCertGlobalRootG2.crt.pem ]; then
    echo "Downloading Azure MySQL SSL Certificate..."
    mkdir -p /home/site/wwwroot/ssl
    wget -O DigiCertGlobalRootG2.crt.pem https://dl.cacerts.digicert.com/DigiCertGlobalRootG2.crt.pem
fi

# 3. Create Storage Directories (Prevents 500 Errors)
echo "Creating storage directories..."
mkdir -p /home/site/wwwroot/storage/framework/sessions
mkdir -p /home/site/wwwroot/storage/framework/views
mkdir -p /home/site/wwwroot/storage/framework/cache
mkdir -p /home/site/wwwroot/storage/logs

# 4. Permissions
chmod -R 775 /home/site/wwwroot/storage
chown -R www-data:www-data /home/site/wwwroot/storage

# 5. Nginx Config
cp /home/site/wwwroot/nginx.conf /etc/nginx/sites-available/default
service nginx reload

# 6. Laravel Setup
cd /home/site/wwwroot

# Install Vendor (Backend)
if [ ! -d "vendor" ]; then
    composer install --optimize-autoloader --no-dev
fi

# Migrations & Caching
php artisan storage:link
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Start Queue (Optional - Remove if not using queues)
nohup php artisan queue:work --daemon --tries=3 > /dev/null 2>&1 &



