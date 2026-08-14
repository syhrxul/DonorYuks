#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ]; then
    cat > .env <<EOF
APP_NAME=${APP_NAME:-DonorYuks}
APP_ENV=${APP_ENV:-production}
APP_KEY=
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost:8088}
APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=id_ID
APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=info
DB_CONNECTION=mysql
DB_HOST=${DB_HOST:-db}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-donoryuks}
DB_USERNAME=${DB_USERNAME:-donoryuks}
DB_PASSWORD=${DB_PASSWORD:-donoryuks}
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database
MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"
AI_PROVIDER=${AI_PROVIDER:-mock}
OPENAI_API_KEY=${OPENAI_API_KEY:-}
GEMINI_API_KEY=${GEMINI_API_KEY:-}
EOF
    php artisan key:generate
fi

if [ ! -d public/storage ]; then
    php artisan storage:link
fi

php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction || true

php-fpm