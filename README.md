# PbTrack — PbInfo Progress Tracker

Multi-user Laravel platform that signs you in with your **PbInfo** username/password, syncs the problem catalog + your solve journal from [pbinfo.ro](https://www.pbinfo.ro/), and shows progress by status and category.

> PbInfo has no official API. This app uses the same login flow and journal endpoint the site already exposes (`/ajx-module/profil/json-jurnal.php`). Credentials are encrypted at rest and used only to sync *your* progress.

## Stack

- Laravel + Inertia + React + Tailwind
- PostgreSQL + Redis (production Compose)
- Queue workers + scheduler for catalog/progress sync

## Local development

Requirements: PHP 8.3+, Composer, Node 20+, SQLite (default) or Postgres.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install && npm run build
php artisan serve
```

Open `http://127.0.0.1:8000`, sign in with your PbInfo account, then use **Sync now**.

Local default uses `QUEUE_CONNECTION=sync` so sync runs in the same request — you do **not** need `queue:work`.

### Useful commands

```bash
# Queue a catalog crawl
php artisan pbinfo:sync catalog

# Run catalog sync inline (blocking)
php artisan pbinfo:sync catalog --sync

# Sync one user (or all) progress
php artisan pbinfo:sync progress --user=YOUR_USERNAME --sync
```

### Tests

```bash
php artisan test
```

Fixture-based unit/feature tests cover journal aggregation and listing HTML parsing — no live PbInfo calls in CI.

## Docker deploy (VPS)

1. Copy env and set a strong `APP_KEY` + DB password:

```bash
cp .env.example .env
# generate key locally: php artisan key:generate --show
# put APP_KEY=base64:... into .env
```

2. Required production env vars:

| Variable | Example |
|----------|---------|
| `APP_KEY` | `base64:...` from `php artisan key:generate --show` |
| `APP_URL` | `https://pbtrack.example.com` |
| `DB_PASSWORD` | strong password |
| `HTTP_PORT` | `80` (or `8080` behind a reverse proxy) |

3. Build and start:

```bash
docker compose up -d --build
```

Services: `app` (php-fpm), `nginx`, `postgres`, `redis`, `queue`, `scheduler`.

4. First catalog sync:

```bash
docker compose exec app php artisan pbinfo:sync catalog
```

5. Put Caddy/nginx/Traefik in front for HTTPS (Let’s Encrypt) pointing at `HTTP_PORT`.

### Compose notes

- App storage is a named volume; rebuild images after frontend/PHP code changes.
- Scheduler runs `schedule:work` (daily catalog + every-four-hours progress).
- Queue worker timeout is 900s for long catalog crawls.

## Security

- PbInfo passwords are stored with Laravel’s `encrypted` cast (APP_KEY).
- Passwords are never sent back to the browser after login.
- Login is rate-limited.
- Use HTTPS in production.

# Free cloud (Render)
# 1. Deploy Blueprint from this repo on Render
# 2. Create a free Postgres DB on https://console.neon.tech
# 3. In Render → Environment set:
#    APP_KEY=<php artisan key:generate --show>
#    DB_URL=postgresql://USER:PASS@HOST/DB?sslmode=require
# 4. Share your Render URL with family (browser login only)

## Known gotcha: PbInfo blocking

PbInfo sometimes returns **HTTP 403** to datacenter IPs or non-browser clients. If login/sync fails:

- Run the app from a residential network / VPS that isn’t blocked
- Confirm `php artisan pbinfo:health` can reach the site
- Re-try sync after a short wait (rate limiting / flaky DNS is common)

Progress sync still works from the journal endpoint once login succeeds and cookies are stored.
