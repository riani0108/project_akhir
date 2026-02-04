FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git zip unzip curl \
    libpng-dev libonig-dev libxml2-dev libzip-dev \
    && apt-get clean

# Install PHP extensions for Laravel
RUN docker-php-ext-install \
    pdo pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy source code Laravel
COPY . /var/www

# Copy Composer
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# Copy entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/entrypoint.sh"]
