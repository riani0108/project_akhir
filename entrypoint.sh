#!/bin/sh

set -e

php artisan config:clear
php artisan cache:clear
php artisan optimize:clear

exec php-fpm
