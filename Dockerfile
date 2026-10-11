# syntax=docker/dockerfile:1

# Production image only. Development runs through Laravel Sail (compose.yaml).
# One container: FrankenPHP (Caddy + PHP). TLS is terminated by a reverse proxy in front.

ARG IMAGE_SOURCE=https://github.com/wi-wissen/lemurmail

# Stage 1: frontend assets (Vite build) -------------------------------------
FROM node:22-alpine AS assets
WORKDIR /build
COPY package.json package-lock.json .npmrc ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: Composer dependencies without dev packages -----------------------
FROM composer:2 AS vendor
WORKDIR /build
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader --prefer-dist
COPY . .
RUN composer dump-autoload --no-dev --optimize-autoloader --no-scripts

# Stage 3: runtime ----------------------------------------------------------
FROM dunglas/frankenphp:1-php8.4-alpine AS runtime

ARG IMAGE_SOURCE
LABEL org.opencontainers.image.source="${IMAGE_SOURCE}" \
      org.opencontainers.image.title="Lemur Mail"

RUN install-php-extensions pdo_sqlite intl opcache

# Non-root user. /data (SQLite) and /caddy (Caddy state) are the only writable paths besides storage and bootstrap/cache.
RUN addgroup -S -g 1000 lemur \
    && adduser -S -D -H -u 1000 -G lemur lemur \
    && mkdir -p /data /caddy/config /caddy/data \
    && chown -R lemur:lemur /data /caddy

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=info \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/data/database.sqlite \
    XDG_CONFIG_HOME=/caddy/config \
    XDG_DATA_HOME=/caddy/data

WORKDIR /app
COPY . /app
COPY --from=vendor /build/vendor /app/vendor
COPY --from=assets /build/public/build /app/public/build
COPY docker/php.ini /usr/local/etc/php/conf.d/lemurmail.ini
COPY docker/Caddyfile /etc/frankenphp/Caddyfile
COPY docker/entrypoint.sh /usr/local/bin/lemurmail-entrypoint

RUN php artisan package:discover --ansi \
    && chmod 0755 /usr/local/bin/lemurmail-entrypoint \
    && chown -R lemur:lemur storage bootstrap/cache

USER lemur

VOLUME ["/data"]
EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD wget -q -O /dev/null http://127.0.0.1:8080/up || exit 1

ENTRYPOINT ["/usr/local/bin/lemurmail-entrypoint"]
