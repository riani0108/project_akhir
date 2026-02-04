#!/bin/sh
set -e

echo "Fixing Laravel permissions"
chown -R www-data:www-data /var/www
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

echo "Starting PHP-FPM"
exec php-fpm
