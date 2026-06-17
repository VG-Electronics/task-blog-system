#!/bin/sh
set -e

until nc -z "${DB_HOST:-mysql}" "${DB_PORT:-3306}" 2>/dev/null; do
    echo "Waiting for MySQL..."
    sleep 2
done

if [ "${SKIP_SETUP:-false}" != "true" ]; then
    if [ ! -f .env ]; then
        cp .env.example .env
    fi

    composer install --no-interaction --optimize-autoloader --no-ansi

    php artisan key:generate --force --no-ansi
    php artisan migrate --force --no-ansi
    php artisan db:seed --force --no-ansi --class=DatabaseSeeder

    touch /tmp/app-ready
fi

exec "$@"
