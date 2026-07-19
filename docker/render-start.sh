#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

if [ -n "$DATABASE_URL" ] && [ -z "$DB_URL" ]; then
  export DB_URL="$DATABASE_URL"
fi

# Neon pooler (PgBouncer) breaks Laravel migrations/DDL — use the direct host.
if [ -n "$DB_URL" ]; then
  export DB_URL="$(printf '%s' "$DB_URL" | sed 's/-pooler././g')"
fi

if [ -n "$RENDER_EXTERNAL_URL" ]; then
  export APP_URL="$RENDER_EXTERNAL_URL"
fi

if [ -z "$APP_KEY" ]; then
  echo "APP_KEY is required. Set it in Render Environment."
  exit 1
fi

export QUEUE_CONNECTION="${QUEUE_CONNECTION:-sync}"
export CACHE_STORE="${CACHE_STORE:-database}"
export SESSION_DRIVER="${SESSION_DRIVER:-database}"
export DB_CONNECTION="${DB_CONNECTION:-pgsql}"
export DB_SSLMODE="${DB_SSLMODE:-require}"

php artisan config:clear || true

if ! php artisan migrate --force --no-interaction; then
  echo "Initial migrate failed (often Neon Auth leftover tables). Running migrate:fresh once..."
  php artisan migrate:fresh --force --no-interaction
fi

PORT="${PORT:-10000}"
exec php artisan serve --host=0.0.0.0 --port="$PORT"
