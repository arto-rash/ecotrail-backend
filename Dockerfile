# syntax=docker/dockerfile:1

############################################
# Base: PHP 8.4 + Nginx + PHP-FPM (serversideup)
############################################
FROM serversideup/php:8.4-fpm-nginx AS base

USER root
RUN install-php-extensions intl bcmath pdo_pgsql
USER www-data

############################################
# Development: code is bind-mounted, deps installed at runtime
############################################
FROM base AS development

############################################
# Composer dependencies (production)
############################################
FROM base AS vendor
WORKDIR /var/www/html
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

############################################
# Production image
############################################
FROM base AS production
WORKDIR /var/www/html

ENV PHP_OPCACHE_ENABLE=1 \
    AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=true \
    AUTORUN_LARAVEL_STORAGE_LINK=true

COPY --chown=www-data:www-data --from=vendor /var/www/html/vendor ./vendor
COPY --chown=www-data:www-data . .

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && php artisan package:discover --ansi

EXPOSE 8080
