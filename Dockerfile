FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update -y \
 && apt-get install -y \
    openssl \
    zip \
    unzip \
    git \
    libicu-dev \
    zlib1g-dev \
    libzip-dev \
 && docker-php-ext-configure intl \
 && docker-php-ext-install pdo pdo_mysql mysqli intl zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy source code
COPY . .

# Install Laravel dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# Permission Laravel
RUN chown -R www-data:www-data /app \
 && chmod -R 775 storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
