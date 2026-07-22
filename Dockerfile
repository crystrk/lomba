############################################
# Base Image
############################################
FROM serversideup/php:8.4-fpm-nginx AS base

USER root

# Install required PHP extensions using Server Side Up helper
RUN install-php-extensions intl gd

# Install Node.js 22 for building frontend assets
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

USER www-data

############################################
# Development Image
############################################
FROM base AS development

USER root

# Install development tools
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

USER www-data

############################################
# CI Image (for testing/building)
############################################
FROM base AS ci

USER root

WORKDIR /var/www/html

COPY composer.json composer.lock ./

# Install dependencies without running scripts (artisan doesn't exist yet)
RUN composer install --no-scripts --no-dev --no-interaction --prefer-dist

# Copy all application files
COPY . .

# Run composer scripts and set permissions
RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data /var/www/html

USER www-data

############################################
# Build Frontend Assets
############################################
FROM ci AS build

RUN npm ci \
    && npm run build \
    && rm -rf node_modules

############################################
# Production Image
############################################
FROM base AS production

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV AUTORUN_ENABLED=true
ENV AUTORUN_LARAVEL_STORAGE_LINK=true
ENV AUTORUN_LARAVEL_MIGRATION=false
ENV AUTORUN_LARAVEL_MIGRATION_ISOLATION=false
ENV PHP_OPCACHE_ENABLE=1
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0

USER root

WORKDIR /var/www/html

COPY --from=build /var/www/html /var/www/html

# Create storage & cache directories and set permissions
RUN mkdir -p /var/www/html/bootstrap/cache \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

EXPOSE 80

USER www-data
