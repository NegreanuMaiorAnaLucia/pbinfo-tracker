#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache || true

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

# Force file session/cache on Render free tier so requests do not depend on
# Neon/sessions tables (database driver caused RuntimeException on session.store).
export QUEUE_CONNECTION="${QUEUE_CONNECTION:-sync}"
export CACHE_STORE=file
export SESSION_DRIVER=file
export SESSION_ENCRYPT=false
export SESSION_SECURE_COOKIE=true
export DB_CONNECTION="${DB_CONNECTION:-pgsql}"
export DB_SSLMODE="${DB_SSLMODE:-require}"

php artisan config:clear || true

PORT="${PORT:-10000}"

# Bind HTTP immediately so Render health checks (/up) succeed while Neon wakes
# and migrations run. Blocking on migrate before serve caused 120s timeouts.
echo "Starting HTTP server on 0.0.0.0:${PORT}..."
php artisan serve --host=0.0.0.0 --port="$PORT" &
SERVER_PID=$!

run_migrations() {
  echo "Running database migrations..."
  if php artisan migrate --force --no-interaction; then
    echo "Migrations completed."
    return 0
  fi

  echo "Initial migrate failed (often Neon Auth leftover tables). Running migrate:fresh once..."
  if php artisan migrate:fresh --force --no-interaction; then
    echo "migrate:fresh completed."
    return 0
  fi

  echo "WARNING: migrations failed; app is up but DB-backed features may error."
  return 1
}

# Give the server a moment to bind before DB work.
sleep 2

if ! kill -0 "$SERVER_PID" 2>/dev/null; then
  echo "HTTP server exited unexpectedly during startup."
  wait "$SERVER_PID"
  exit 1
fi

run_migrations || true

echo "Ready. Waiting on HTTP server (pid ${SERVER_PID})."
wait "$SERVER_PID"
