#!/bin/sh

set -e

echo "Generating Laravel .env from container environment..."

cat > /app/.env <<EOF
APP_NAME=${APP_NAME}
APP_ENV=${APP_ENV}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG}
APP_URL=${APP_URL}

LOG_CHANNEL=${LOG_CHANNEL}
LOG_DEPRECATIONS_CHANNEL=${LOG_DEPRECATIONS_CHANNEL}
LOG_LEVEL=${LOG_LEVEL}

DB_CONNECTION=${DB_CONNECTION}
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}
DB_SSL_MODE=${DB_SSL_MODE}
DB_SOCKET=${DB_SOCKET}
EOF

echo ".env generated successfully."

php artisan config:clear
php artisan cache:clear
php artisan optimize:clear

exec php-fpm
