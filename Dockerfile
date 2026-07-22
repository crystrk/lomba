############################################
# Base Image
############################################
FROM serversideup/php:8.4-fpm-nginx AS base

USER root

# Install required PHP extensions using Server Side Up helper
RUN install-php-extensions intl gd bcmath

# Install Node.js 22 for building frontend assets
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

USER www-data

############################################
# CI Image (Composer & Source Setup)
############################################
FROM base AS ci

USER root

WORKDIR /var/www/html

# Copy composer files for efficient layer caching
COPY composer.json composer.lock ./

# Install dependencies without running scripts
RUN composer install \
    --no-scripts \
    --no-dev \
    --no-interaction \
    --prefer-dist

# Copy application source code
COPY . .

# Dump autoload and fix permissions
RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data /var/www/html

USER www-data

############################################
# Build Frontend Assets (Vite & Vue)
############################################
FROM ci AS build

USER root

WORKDIR /var/www/html

RUN npm ci \
    && npm run build \
    && rm -rf node_modules

USER www-data

############################################
# Production Image
############################################
FROM base AS production

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV PHP_OPCACHE_ENABLE=1
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0

USER root

WORKDIR /var/www/html

# Copy compiled application & vendor from build stage
COPY --from=build /var/www/html /var/www/html

# Create framework directories and set proper permissions
RUN mkdir -p \
        bootstrap/cache \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        database \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage \
    && chmod -R 775 bootstrap/cache \
    && chmod -R 775 database

EXPOSE 80

USER www-data
