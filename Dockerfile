# Render / single-container production image
FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libzip-dev libpng-dev libonig-dev nodejs npm \
    && docker-php-ext-install pdo_pgsql pgsql zip bcmath pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

# Dummy key only for build-time artisan; real key comes from Render env
ENV APP_KEY=base64:dGVtcG9yYXJ5LWtleS1mb3ItZG9ja2VyLWJ1aWxkLTE=
ENV APP_ENV=production

RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi \
    && npm run build \
    && rm -rf node_modules \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chmod +x docker/render-start.sh

ENV PORT=10000
EXPOSE 10000

CMD ["docker/render-start.sh"]
