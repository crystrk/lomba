# Stage 1: Build Frontend Assets (Vite & Vue)
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: Install PHP Dependencies (Composer)
FROM composer:2 AS vendor
WORKDIR /app
COPY composer*.json ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs \
    --optimize-autoloader

# Stage 3: Final Image using Server Side Up PHP FPM + Nginx
FROM serversideup/php:8.4-fpm-nginx

# Set document root for Nginx
ENV WEB_DOCUMENT_ROOT=/var/www/html/public
ENV PHP_OPCACHE_ENABLE=1

WORKDIR /var/www/html

# Copy application source code
COPY --chown=www-data:www-data . /var/www/html

# Copy vendor packages from Stage 2
COPY --chown=www-data:www-data --from=vendor /app/vendor /var/www/html/vendor

# Copy compiled frontend assets from Stage 1
COPY --chown=www-data:www-data --from=frontend /app/public/build /var/www/html/public/build

# Set permissions for storage & bootstrap cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
