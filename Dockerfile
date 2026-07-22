# Stage 1: Install PHP Dependencies (Composer)
FROM composer:2 AS vendor
WORKDIR /app
COPY composer*.json ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs \
    --optimize-autoloader

# Stage 2: Build Frontend Assets (Vite & Vue)
FROM node:22-bookworm-slim AS frontend
WORKDIR /app

# Install PHP CLI required by @laravel/vite-plugin-wayfinder during build
RUN apt-get update && apt-get install -y --no-install-recommends \
    php-cli \
    php-mbstring \
    php-xml \
    && rm -rf /var/lib/apt/lists/*

COPY package*.json ./
RUN npm ci

# Copy full application code & vendor for Wayfinder route generation
COPY . .
COPY --from=vendor /app/vendor /app/vendor

RUN npm run build

# Stage 3: Final Image using Server Side Up PHP FPM + Nginx (PHP 8.4)
FROM serversideup/php:8.4-fpm-nginx

# Configure Document Root & OPcache
ENV WEB_DOCUMENT_ROOT=/var/www/html/public
ENV PHP_OPCACHE_ENABLE=1

WORKDIR /var/www/html

# Copy application source code
COPY --chown=www-data:www-data . /var/www/html

# Copy vendor packages from Stage 1
COPY --chown=www-data:www-data --from=vendor /app/vendor /var/www/html/vendor

# Copy compiled frontend assets from Stage 2
COPY --chown=www-data:www-data --from=frontend /app/public/build /var/www/html/public/build

# Set permissions for storage & bootstrap cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
