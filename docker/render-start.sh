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

# Prefer a stable key file so encrypted PbInfo credentials survive restarts when
# the dashboard APP_KEY is malformed. Env APP_KEY still wins when valid.
APP_KEY_FILE="storage/app/.render_app_key"
mkdir -p storage/app
APP_KEY="$(php -r '
$key = getenv("APP_KEY") ?: "";
$file = $argv[1];
$raw = str_starts_with($key, "base64:") ? base64_decode(substr($key, 7), true) : $key;
if ($raw !== false && in_array(strlen($raw), [16, 32], true)) {
    echo $key;
    exit(0);
}
if (is_file($file)) {
    $stored = trim((string) file_get_contents($file));
    $storedRaw = str_starts_with($stored, "base64:") ? base64_decode(substr($stored, 7), true) : $stored;
    if ($storedRaw !== false && in_array(strlen($storedRaw), [16, 32], true)) {
        fwrite(STDERR, "APP_KEY env invalid; reusing key from storage/app/.render_app_key\n");
        echo $stored;
        exit(0);
    }
}
$fresh = "base64:" . base64_encode(random_bytes(32));
file_put_contents($file, $fresh);
fwrite(STDERR, "APP_KEY invalid/missing length; generated and saved to storage/app/.render_app_key\n");
echo $fresh;
' "$APP_KEY_FILE")"
export APP_KEY

# Force file session/cache on Render free tier so requests do not depend on
# Neon/sessions tables (database driver caused RuntimeException on session.store).
export QUEUE_CONNECTION="${QUEUE_CONNECTION:-sync}"
export CACHE_STORE=file
export SESSION_DRIVER=file
export SESSION_ENCRYPT=false
export SESSION_SECURE_COOKIE=true
export DB_CONNECTION="${DB_CONNECTION:-pgsql}"
export DB_SSLMODE="${DB_SSLMODE:-require}"
export LOG_CHANNEL="${LOG_CHANNEL:-stderr}"
export LOG_LEVEL="${LOG_LEVEL:-info}"

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

  echo "WARNING: migrations failed this attempt."
  return 1
}

# Give the server a moment to bind before DB work.
sleep 2

if ! kill -0 "$SERVER_PID" 2>/dev/null; then
  echo "HTTP server exited unexpectedly during startup."
  wait "$SERVER_PID"
  exit 1
fi

# Retry migrations while /up stays healthy — Neon free can take a while to wake.
attempt=1
max_attempts=12
until run_migrations; do
  if [ "$attempt" -ge "$max_attempts" ]; then
    echo "WARNING: migrations still failing after ${max_attempts} attempts; DB routes will 503."
    break
  fi
  echo "Retrying migrations in 10s (attempt ${attempt}/${max_attempts})..."
  attempt=$((attempt + 1))
  sleep 10
done

echo "Ready. Waiting on HTTP server (pid ${SERVER_PID})."
wait "$SERVER_PID"
