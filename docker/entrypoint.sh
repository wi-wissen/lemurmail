#!/bin/sh
# Container start: check the configuration, prepare SQLite, migrate, warm caches, run the scheduler, serve HTTP.
set -e

cd /app

# Started as root: create the mounted host folders if Docker made them (as root) or they are missing, give them to
# the app user, then continue as that user. Started with --user already: skip this.
if [ "$(id -u)" = "0" ]; then
    mkdir -p /app/storage
    [ "$(stat -c %u /app/storage)" = "$(id -u lemur)" ] || chown -R lemur:lemur /app/storage
    exec su-exec lemur "$0" "$@"
fi

# `docker run IMAGE php artisan ...` (and other one-off commands) run the given command as is.
if [ "$#" -gt 0 ]; then
    exec "$@"
fi

fail() {
    echo "ERROR: $1" >&2
    exit 1
}

: "${DB_DATABASE:=/app/storage/database.sqlite}"
DB_DIR="$(dirname "$DB_DATABASE")"

[ -w "$DB_DIR" ] || fail "$DB_DIR is not writable for uid $(id -u). Fix on the host: mkdir -p storage && sudo chown $(id -u):$(id -g) storage"

# Required settings, with a hint where to set them (the .env file next to docker-compose.yml).
[ -n "$APP_URL" ] || fail "APP_URL is not set (for example https://tmp-mail.example.org). Set it in your .env file."
[ -n "$MAIL_DOMAIN" ] || fail "MAIL_DOMAIN is not set (the app's own subdomain, for example tmp-mail.example.org). Set it in your .env file."
[ -n "$LETTERMINT_WEBHOOK_SECRET" ] || fail "LETTERMINT_WEBHOOK_SECRET is not set (the signing secret of your Lettermint inbound route). Set it in your .env file."

# No APP_KEY given: create one on the first start and keep it next to the database, so restarts reuse it.
# Set APP_KEY yourself if you want the key stored apart from the data (recommended for backups that leave the server).
if [ -z "$APP_KEY" ]; then
    KEY_FILE="$DB_DIR/app.key"
    if [ ! -s "$KEY_FILE" ]; then
        (umask 077 && php artisan key:generate --show > "$KEY_FILE")
        echo "Created a new APP_KEY in $KEY_FILE"
    fi
    APP_KEY="$(cat "$KEY_FILE")"
    export APP_KEY
fi

# Behind an HTTPS reverse proxy the session cookie must be marked secure.
case "$APP_URL" in
    https://*) : "${SESSION_SECURE_COOKIE:=true}"; export SESSION_SECURE_COOKIE ;;
esac

# /app/storage is a host volume (docker-compose.yml): create the folders Laravel needs, because the mount starts empty.
[ -w /app/storage ] || fail "/app/storage is not writable for uid $(id -u). Fix on the host: mkdir -p storage && sudo chown $(id -u):$(id -g) storage"
mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views storage/app/public

case "${MAIL_MAILER:-smtp}" in
    lettermint) [ -n "$LETTERMINT_PROJECT_TOKEN" ] || echo "WARNING: LETTERMINT_PROJECT_TOKEN is not set, so verification and approval mails cannot be sent." >&2 ;;
    smtp) [ -n "$MAIL_HOST" ] || echo "WARNING: MAIL_HOST is not set, so verification and approval mails cannot be sent." >&2 ;;
esac

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
