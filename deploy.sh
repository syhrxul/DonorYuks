#!/bin/bash

set -e

SERVER_USER="arul"
SERVER_IP="100.107.202.80"
REMOTE_PATH="/var/www/donoryuks"

echo "🚀 Starting rsync transfer to server ($SERVER_IP)..."

rsync -avzP \
  --exclude='.git' \
  --exclude='.env' \
  --exclude='vendor' \
  --exclude='node_modules' \
  --exclude='storage/*.key' \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  ./ ${SERVER_USER}@${SERVER_IP}:${REMOTE_PATH}

echo "✅ Sync complete!"
echo "🔄 Optimizing Laravel application on server..."

# Menambahkan -t agar SSH menyediakan tty untuk prompt password sudo
ssh -t ${SERVER_USER}@${SERVER_IP} "cd ${REMOTE_PATH} && \
  composer install --optimize-autoloader --no-dev && \
  php artisan migrate --force && \
  php artisan config:cache && \
  php artisan route:cache && \
  php artisan view:cache && \
  sudo chown -R www-data:www-data storage bootstrap/cache && \
  sudo chmod -R 775 storage bootstrap/cache"

echo "🎉 Deployment to donor-yuks finished successfully!"
