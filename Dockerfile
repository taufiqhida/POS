# syntax=docker/dockerfile:1
# Jelly Potter Smart Cashier — image produksi (Nginx + PHP 8.4-FPM dalam satu container).

# ---------- 1. Dependensi PHP ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative --no-scripts

# ---------- 2. Build CSS/JS (Vite + Tailwind) ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
# Tailwind memindai view vendor (pagination) — ambil dari tahap composer.
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

# ---------- 3. Runtime ----------
FROM php:8.4-fpm-alpine

RUN apk add --no-cache nginx supervisor su-exec tzdata icu-libs libzip libpng libjpeg-turbo freetype \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl zip gd bcmath exif opcache pcntl \
    && apk del .build-deps \
    && cp /usr/share/zoneinfo/Asia/Jakarta /etc/localtime && echo "Asia/Jakarta" > /etc/timezone

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

# Gagal saat build (bukan saat jalan) bila versi PHP tidak cocok dengan composer.lock.
RUN php -r 'require "vendor/autoload.php"; echo "Platform PHP ".PHP_VERSION." OK\n";'

RUN rm -rf tests .claude node_modules storage/logs/*.log \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
        storage/app/public bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80
ENTRYPOINT ["entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
