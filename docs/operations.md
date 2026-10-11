# Operations

Runbook for the production container described in the [README](../README.md#production-deployment).
The commands assume the compose file `docker-compose.yml` in the current directory, and the service name `lemurmail`.

## Health and logs

```bash
docker compose ps                                   # status incl. health check
curl -fsS http://127.0.0.1:8080/up                  # on the host, through the published port
docker compose logs -f lemurmail                    # app, web server and scheduler output
```

The container writes logs to stderr only (`LOG_CHANNEL=stderr`). Nothing is stored inside the container except the
SQLite database in the volume.

## Backup

The data lives in the volume `lemurmail-data` (mounted at `/data`): the SQLite database. Backups contain **personal
data** (mails and addresses), so store them as carefully as the server.

Create a consistent copy while the app keeps running (`VACUUM INTO` is safe under concurrent writes):

```bash
docker compose exec lemurmail php -r '(new PDO("sqlite:/data/database.sqlite"))->exec("VACUUM INTO \"/data/backup.sqlite\"");'
docker compose cp lemurmail:/data/backup.sqlite "./lemurmail-$(date +%F).sqlite"
docker compose exec lemurmail rm /data/backup.sqlite
```

Also keep a copy of `APP_KEY` (from your `.env`) in your password manager. Encrypted address secrets can only be read
with the key that encrypted them, and a backup without the key does not restore them.

Restore (replace the file in the volume while the container is stopped):

```bash
docker compose down
docker run --rm -v "$(basename "$PWD")_lemurmail-data":/data -v "$PWD":/backup alpine \
    sh -c 'cp /backup/lemurmail-2026-10-01.sqlite /data/database.sqlite && chown 1000:1000 /data/database.sqlite'
docker compose up -d
```

`docker volume ls` shows the exact volume name (`<project>_lemurmail-data`). Test a restore on another machine at
least once.

## Upgrade

1. Take a backup (see above).
2. Pull the new image and restart:

   ```bash
   docker compose pull
   docker compose up -d
   ```

Migrations run automatically on every start (`php artisan migrate --force`). Migrations are not reversible from the
container. If a release fails after a migration, stop the container and restore the backup taken in step 1, then start
the previous image tag (set `image: ghcr.io/wi-wissen/lemurmail:<previous version>` in `docker-compose.yml`).

To pin a version, use a tag such as `v1.2.3` instead of `latest` in `docker-compose.yml`.

## First super admin

Teachers register themselves, but a super admin must approve them. Create the first super admin once, after the
first start. The command creates a verified, active account and asks for the password interactively:

```bash
docker compose exec -it lemurmail php artisan lemurmail:make-admin you@example.org
```

The command refuses an address that already has an account. Use `--password=` only in scripts; a password passed on
the command line ends up in the shell history.

## Publishing the image (GHCR)

The release workflow pushes `ghcr.io/wi-wissen/lemurmail` on every version tag (`v1.2.3`). After the **first** push, set the
package to public: on GitHub open the package under the organisation or user, then *Package settings* → *Change
visibility* → *Public*. Until then, the server cannot pull the image without a login.

## Troubleshooting

| Symptom | Cause and fix |
| --- | --- |
| Container exits at start with `APP_KEY must be set` | Set `APP_KEY` in `.env` (see README). Changing the key later makes encrypted secrets unreadable. |
| Lettermint webhook answers **401** | The signing secret does not match `LETTERMINT_WEBHOOK_SECRET`, or the server clock differs from the real time by more than 5 minutes. Check the secret first, then the time (`timedatectl status`, enable NTP). |
| Nothing arrives for an address | Check in this order: (1) MX record of the app subdomain points to `inbound.lettermint.co` (`dig MX tmp-mail.example.org`); (2) the sender is on the teacher's whitelist; (3) the sender is authenticated and aligned (DMARC, or DKIM/SPF for the From domain); (4) the Lettermint route has the subdomain as catch-all and the webhook URL is `https://<MAIL_DOMAIN>/webhooks/lettermint`. |
| Old mails stay longer than 7 days | The scheduler is not running. Look for `schedule:work` errors in `docker compose logs`, then restart the container. |
| `502` or `504` from the reverse proxy | The container is not healthy. Check `docker compose ps` and the logs; the proxy must point to `127.0.0.1:8080`. |
| Students get redirected to HTTP or sessions do not persist | `SESSION_SECURE_COOKIE=true` requires HTTPS on the public URL; check `APP_URL` and that the proxy forwards `X-Forwarded-Proto`. |
| Verification mails do not arrive | Check the `MAIL_*` values and the SMTP logs of your provider. The inbox and outgoing mail use different paths; verification mails go out through `MAIL_HOST`. |
