# Multi-stage build for PHP 8.4 FPM
FROM php:8.4-fpm as base

WORKDIR /var/www/html

# System deps + Node.js in one layer
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev zip unzip \
    libjpeg62-turbo-dev \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions — GD configured with JPEG support
RUN docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ── Asset build stage (Node lives here, dies here) ──
FROM base as assets

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build

# ── Development stage ──
FROM base as development

RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]

# ── Production stage ──
FROM base as production

COPY --chown=www-data:www-data . .

# Grab only the compiled assets from the assets stage
COPY --from=assets --chown=www-data:www-data /var/www/html/public/build ./public/build

RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

ARG APP_KEY=base64:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa=
RUN APP_KEY=${APP_KEY} \
    CACHE_STORE=file \
    SESSION_DRIVER=file \
    composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

RUN APP_KEY=${APP_KEY} \
    CACHE_STORE=file \
    SESSION_DRIVER=file \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

EXPOSE 9000
CMD ["php-fpm"]