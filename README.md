# Lemur Mail

Receive-only pseudonymous mailboxes for schools. A teacher creates addresses such as `klasse7a-03@tmp-mail.example.org`
with a PIN for each one. Students sign in with address and PIN and read the confirmation and password-reset mails
they receive while signing up for online services. Nothing can be sent from the system, and every mail is deleted
after 7 days at the latest.

The mascot is a ring-tailed lemur. The name comes from the Roman *lemures*, the restless shadow spirits of the dead,
which the Romans drove out of the house at the festival of Lemuria. Mail that nobody should keep around anyway fits.

## Features

- **Teachers:** register with e-mail verification, create single addresses or series (prefix and count), organise
  addresses into groups, and set a cap per teacher.
- **Lessons:** start a lesson for a group with fresh PINs that expire after 45 or 90 minutes or at a fixed time.
  Extend, end or regenerate PINs at any time.
- **Beamer view:** a dark, full-screen view with large type, a countdown and adjustable font size, which can hide
  the PINs.
- **Sender whitelist:** only mails from allowed senders are stored, with a default list per teacher and extra entries
  per address. Mails must come from an authenticated sender (DMARC, or DKIM/SPF for the From domain).
- **Students:** address and PIN plus Friendly Captcha, rate-limited. The inbox refreshes every ~10 seconds. Text is
  shown by default; HTML mails are shown in a sandboxed iframe with purified markup, a strict Content Security Policy
  and no remote images.
- **Secrets:** an optional secret per address (stored encrypted), visible only after PIN login.
- **Super admin:** approves teacher registrations, maintains organisation and freemail domains and address caps.
- **Languages:** German and English.

<!-- screenshots -->

## Quick start (development with Laravel Sail)

Requirements: Docker, and PHP 8.4 with Composer to install the Sail wrapper (`composer install` runs on the host once).

```bash
git clone https://github.com/wi-wissen/lemurmail.git
cd lemurmail
cp .env.example .env
composer install
./vendor/bin/sail up -d
./vendor/bin/sail npm ci
./vendor/bin/sail npm run dev        # or: npm run build
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan lemurmail:make-admin you@example.org   # asks for the password
```

- The app runs at <http://localhost> (port 80 unless changed in `compose.yaml`).
- Verification and other outgoing mails are caught by **Mailpit** at <http://localhost:8025>.
- Simulate an incoming mail through the signed webhook, without Lettermint:

  ```bash
  ./vendor/bin/sail artisan mail:simulate klasse7a-03@tmp-mail.example.org --subject="Your code" --text="Code: 123456"
  ```

  The webhook secret from `LETTERMINT_WEBHOOK_SECRET` is used to sign the request, so `.env` needs a value there.

## Lettermint setup (inbound mail)

Incoming mail is received by [Lettermint](https://lettermint.co) and forwarded to the app as a signed webhook.
Inbound mail requires the **Starter** plan.

1. In Lettermint, create an **inbound route**.
2. Set the app's own subdomain (the value of `MAIL_DOMAIN`, for example `tmp-mail.example.org`) as the **catch-all
   domain** of that route, so every address below it is accepted.
3. Add an **MX record** for that subdomain only, pointing to `inbound.lettermint.co`. Do not change the MX records of
   the main domain.
4. Set the webhook URL to `https://<MAIL_DOMAIN>/webhooks/lettermint`.
5. Copy the **signing secret** into `LETTERMINT_WEBHOOK_SECRET`.
6. Set a **strict spam threshold**. Lemur Mail does not check spam itself; it relies on the sender whitelist and on
   authentication results.

A valid webhook is answered with a 2xx status even when the recipient is unknown, so Lettermint does not retry
mails nobody asked for. An invalid signature is answered with 401. Signatures older than 5 minutes are rejected, so the server
clock must be synchronised.

## Lettermint setup (outgoing system mail)

Verification, password-reset and approval mails that Lemur Mail itself sends can go through the same Lettermint account.
Students still cannot send anything: there is no way to send from a mailbox, only the system sends.

1. In Lettermint, verify a **sending domain** (SPF and DKIM records) and use an address on it as `MAIL_FROM_ADDRESS`,
   for example `noreply@nein-ev.de`. It can also be a subdomain; Lettermint's documentation does not say that sending
   and receiving on the very same subdomain is a problem, but a separate sending domain is the safe choice.
2. In the project settings, enable **SMTP** and create a **project API token**.
3. Set the SMTP variables (see [Lettermint's SMTP guide](https://lettermint.co/docs/guides/send-email-with-smtp)):

   ```
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.lettermint.co
   MAIL_PORT=587            # STARTTLS; use 465 with MAIL_SCHEME=smtps for implicit TLS
   MAIL_USERNAME=lettermint
   MAIL_PASSWORD=<project API token>
   MAIL_FROM_ADDRESS=noreply@nein-ev.de
   ```

Sent mail counts against the monthly quota of your plan, together with the received mail. Replies to the system address
that arrive on the catch-all domain are dropped (no such mailbox). The official `lettermint-laravel` package would use the
HTTP API instead of SMTP; it is not needed here and not a dependency of this project.

## Production deployment

The production image is built from the `Dockerfile` in this repository and published to GitHub Container Registry
on every version tag (`v1.2.3`). It contains one process that serves HTTP through FrankenPHP (Caddy + PHP) and runs
the scheduler. The SQLite database lives in `/data`, which the compose file mounts from the folder `./data` on the host, so
it survives container updates and can be backed up like any file.

1. Copy `docker-compose.yml` and `.env.docker.example` to the server. Rename the example to `.env` and edit it: that
   file is the only thing you configure, the container reads it directly (see the [configuration table](#configuration)).
   You do not need to create an `APP_KEY`: if it is empty, the container generates one on the first start and keeps it in
   `data/app.key`. Set it yourself if you prefer to store the key apart from the data.
2. Create the data folder for the SQLite database. The container runs as uid 1000:

   ```bash
   mkdir -p data && sudo chown 1000:1000 data
   ```

   SQLite is enough for this service: a few dozen teachers and mail in the hundreds per day is far below what it
   handles. The database runs in WAL mode, so `data/` also holds `database.sqlite-wal` and `-shm` while the app runs;
   always mount and back up the folder, never only the file.
3. Start the container:

   ```bash
   docker compose up -d
   ```

4. Put a reverse proxy in front that terminates TLS and forwards to `127.0.0.1:8080`. The compose file binds the
   container to localhost only, on port 8080, so it does not compete with the proxy for ports 80 and 443. Change it with
   `APP_PORT` and `BIND_ADDRESS` in `.env` (for example `APP_PORT=80` and `BIND_ADDRESS=0.0.0.0` to publish plain HTTP
   directly; the app itself has no TLS, so do that only behind another TLS terminator). Example with Caddy:

   ```
   tmp-mail.example.org {
       reverse_proxy 127.0.0.1:8080
   }
   ```

   The app trusts the proxy's forwarding headers. Set `SESSION_SECURE_COOKIE=true` (the compose file does this by default)
   and make sure the proxy forwards `X-Forwarded-Proto`.

5. Create the first super admin (see [docs/operations.md](docs/operations.md)).

Migrations run automatically on every start. Read [docs/operations.md](docs/operations.md) for backups, upgrades and
troubleshooting.

## Configuration

Variables marked *required* must be set in production. The values in `.env.example` are defaults for development.

| Variable | Default | Purpose |
| --- | --- | --- |
| `APP_KEY` | – (generated) | Encryption key. In the container it is generated on the first start and stored in `data/app.key` when empty. To set it yourself: `docker run --rm ghcr.io/wi-wissen/lemurmail:latest php artisan key:generate --show`. Back it up: without it stored secrets cannot be read. |
| `APP_URL` | `http://localhost` | Public URL, for example `https://tmp-mail.example.org`. |
| `APP_NAME` | `Lemur Mail` | Name shown in the UI and mail sender name. |
| `APP_ENV` | `local` (`production` in the image) | Environment. |
| `APP_DEBUG` | `true` (`false` in the image) | Show error details. Keep `false` in production. |
| `APP_LOCALE` | `de` | Default language. |
| `APP_FALLBACK_LOCALE` | `de` | Language used when a translation is missing. |
| `APP_TIMEZONE` | `Europe/Berlin` | Time zone for the UI and the lesson times. |
| `MAIL_DOMAIN` | `tmp-mail.example.org` (*required*) | Domain of the temporary addresses (the app's own subdomain). |
| `LETTERMINT_WEBHOOK_SECRET` | – (*required*) | Signing secret of the Lettermint inbound webhook. |
| `ADMIN_EMAIL` | – | Receives "new teacher waiting for approval" mails. |
| `IMPRINT_URL` | – | Full URL of the operator's imprint. The footer link only appears when set. |
| `PRIVACY_URL` | – | Full URL of the operator's privacy policy. The footer link only appears when set. |
| `INBOUND_AUTH_MODE` | `aligned` | Sender authentication of whitelisted mail: `aligned` (DMARC, or DKIM/SPF aligned with the From domain), `authenticated` (any SPF/DKIM/DMARC pass) or `off` (whitelist only). Relax it if mail of a legitimate service is rejected. |
| `PIN_IP_MAX_ATTEMPTS` | `0` | Failed PIN logins per IP in 15 minutes. `0` disables the IP limit (recommended: a whole school shares one IP). Logins are limited per browser session (5) and per address (50) and need the Friendly Captcha. |
| `MAX_PIN_DAYS` | `365` | Longest PIN validity when a teacher starts a lesson with an end date. |
| `MAIL_RETENTION_DAYS` | `7` | Retention of stored mails in days. Hard-capped at 7. |
| `DEFAULT_ADDRESS_LIMIT` | `200` | Default cap of addresses per teacher. |
| `MAX_LESSON_MINUTES` | `720` | Longest allowed lesson or PIN validity in minutes. |
| `FRIENDLY_CAPTCHA_ENABLED` | `false` (`true` in compose) | Turn Friendly Captcha on. Off only for local development. |
| `FRIENDLY_CAPTCHA_SITEKEY` | – | Friendly Captcha site key (public, used by the forms). |
| `FRIENDLY_CAPTCHA_SECRET` | – | Friendly Captcha API secret (used for server-side verification). |
| `FRIENDLY_CAPTCHA_PUZZLE_ENDPOINT` | Friendly Captcha v1 puzzle API | Override only for testing. |
| `FRIENDLY_CAPTCHA_VERIFY_ENDPOINT` | Friendly Captcha v1 siteverify API | Override only for testing. |
| `MAIL_MAILER` | `smtp` | Mailer for verification and notification mails. |
| `MAIL_HOST` | `mailpit` | SMTP host. |
| `MAIL_PORT` | `1025` (`587` in compose) | SMTP port. |
| `MAIL_SCHEME` | `null` | `smtps` for implicit TLS, `null` for STARTTLS on port 587. |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | – | SMTP credentials. |
| `MAIL_FROM_ADDRESS` | `hello@example.com` (*required* in production) | Sender address of outgoing mails. |
| `MAIL_FROM_NAME` | `${APP_NAME}` | Sender name of outgoing mails. |
| `DB_CONNECTION` | `sqlite` | Database driver. The app is built for SQLite. |
| `DB_DATABASE` | `database/database.sqlite` (`/data/database.sqlite` in the image) | Path to the SQLite file. |
| `SESSION_DRIVER` | `database` | Session storage. |
| `SESSION_LIFETIME` | `120` | Session lifetime in minutes. |
| `SESSION_SECURE_COOKIE` | unset (`true` in compose) | Send session cookies over HTTPS only. |
| `CACHE_STORE` | `database` | Cache storage. |
| `QUEUE_CONNECTION` | `database` | Queue connection. |
| `LOG_CHANNEL` | `stack` (`stderr` in the image) | Log channel. |
| `LOG_LEVEL` | `debug` (`info` in the image) | Log level. |
| `BCRYPT_ROUNDS` | `12` | Password hashing cost. |

## Security notes

- **What is stored:** sender address, subject, the text body and, if present, the HTML body (purified before it is
  stored), and the time of receipt. Attachments are dropped and the raw mail is not kept.
- **Only allowed senders:** a mail is stored only if its From address is on the whitelist and the sender is
  authenticated and aligned with the From domain. Everything else is accepted with a 2xx answer and discarded.
- **Retention:** mails are pruned hourly once they are older than `MAIL_RETENTION_DAYS`, which is capped at 7 days.
- **HTML mails** are shown only in a sandboxed iframe with a strict Content Security Policy. Remote images are not loaded.
- **Logs** never contain mail content, PINs, secrets or signed URLs.
- **PIN brute force:** student logins are rate-limited per address and IP.

Please report security problems privately to the maintainers instead of opening a public issue.

## Development

| Task | Command |
| --- | --- |
| PHP tests (Pest) | `vendor/bin/pest` |
| JavaScript core tests (Vitest) | `npx vitest run` |
| Syntax and code style checks | `composer typecheck` |
| Frontend build (Vite) | `npm run build` |
| Frontend dev server with hot reload | `npm run dev` |

Architecture notes and the rules for pure core code are in [CLAUDE.md](CLAUDE.md). The requirements are in
[docs/requirements.md](docs/requirements.md).

## Contributing

Contributions are welcome. Write the tests first: every behaviour change needs a Pest test (or a Vitest test for the
JavaScript core) that fails before the change and passes after it. Keep the pure core free of framework code, use
integer Unix timestamps, and put all UI text into `lang/de` and `lang/en` with identical keys.

Code, comments, identifiers and commit messages are in English.

## License

License: to be decided.
