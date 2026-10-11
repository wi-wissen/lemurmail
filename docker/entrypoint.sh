#!/bin/sh
# Container start: prepare SQLite, migrate, warm caches, run the scheduler, serve HTTP.
set -e

cd /app

: "${APP_KEY:?APP_KEY must be set (php artisan key:generate --show)}"
: "${DB_DATABASE:=/data/database.sqlite}"

if [ ! -f "$DB_DATABASE" ]; then
    touch "$DB_DATABASE"
fi

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Runs the hourly mail:prune (routes/console.php). If it dies, pruning stops until the container restarts.
php artisan schedule:work &

exec frankenphp run --config /etc/frankenphp/Caddyfile
