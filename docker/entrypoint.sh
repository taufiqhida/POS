#!/bin/sh
# Dijalankan setiap container start: tunggu database, migrasi, lalu cache konfigurasi.
set -e
cd /var/www/html

if [ -z "$APP_KEY" ]; then
    echo 'APP_KEY belum diisi di .env. Buat di VPS dengan: echo "base64:$(openssl rand -base64 32)"' >&2
    exit 1
fi

# Volume storage kosong saat pertama kali dipasang.
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/public
chown -R www-data:www-data storage bootstrap/cache

# Perintah artisan dijalankan sebagai www-data supaya file yang dibuat bisa ditulis PHP-FPM.
artisan() { su-exec www-data php artisan "$@"; }

if [ "${CONTAINER_ROLE:-app}" = "app" ]; then
    echo "Menunggu database ${DB_HOST}:${DB_PORT:-3306}..."
    i=0
    until php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 3306), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); } catch (Throwable $e) { exit(1); }'; do
        i=$((i + 1))
        if [ "$i" -ge 60 ]; then echo "Database tidak bisa dihubungi." >&2; exit 1; fi
        sleep 2
    done

    artisan migrate --force
    artisan storage:link --force >/dev/null 2>&1 || true
fi

artisan optimize
artisan filament:optimize >/dev/null 2>&1 || true

exec "$@"
