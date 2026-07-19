#!/bin/sh
set -e

cd /var/www/html

if [ -f composer.json ]; then
  if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
  fi
fi

if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  php artisan key:generate --force || true
fi

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true

php artisan migrate --force --no-interaction || true

exec "$@"
