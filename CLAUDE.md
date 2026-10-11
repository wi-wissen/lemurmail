# Lemur Mail — architecture notes

Receive-only throwaway mailboxes for schools. Teachers create addresses (with PINs), students sign in with
address + PIN and read confirmation / password-reset mails. Nothing can be sent. Requirements: `docs/requirements.md`.

## Language rules
- Chat with the owner in German. Code, comments, README and commit messages in English.
- All UI text goes through i18n: `lang/de/*.php` and `lang/en/*.php` (German first, every key in both). No hard-coded UI strings in Blade/JS.
- JS gets its strings from the server (data attributes / `@json(__(...))`), never from its own tables.

## Hard rules
1. **Pure core without DOM/framework.** `app/Core/**` (PHP) and `resources/js/core/**` (JS) contain only plain logic: no Eloquent, no Request/Facades/Carbon globals, no `window`/`document`, no `Date.now()`/`time()`. Time and randomness come in as parameters.
2. **Integer times.** Every instant is a Unix timestamp in whole seconds (`int`), every duration is whole seconds. DB columns for domain times are `unsignedBigInteger`. No Carbon/`Date` objects inside the core, no floats, no milliseconds (JS only uses ms for timers at the DOM edge).
3. **No new dependencies without asking the owner.** Approved by the requirements: `ezyang/htmlpurifier`, `friendly-challenge`, `@fontsource/atkinson-hyperlegible`, `@fontsource-variable/jetbrains-mono`, `vitest`, Breeze/Pest/Alpine/Tailwind. The `ossycodes/friendlycaptcha` package is NOT used (incompatible with Laravel 13's Guzzle 8); `App\Rules\FriendlyCaptcha` talks to the siteverify API through the `Http` facade.
4. No Livewire, no Filament. Admin views are plain Blade.
5. Never log mail content, headers, PINs, secrets or signed URLs.

## Layers
- `app/Core/**` — pure domain logic (PIN generation/format, address series, sender whitelist + auth-alignment policy, domain policy, hour window, linkify, inbound payload parsing, webhook signature, retention). Unit-tested in `tests/Unit/Core`.
- `app/Support/Clock.php` — the only place that reads the current time (`Clock::now(): int`, honours Carbon test time).
- `app/Models`, `database/migrations` — persistence. Domain timestamps are ints.
- `app/Services/**` — glue between core and Eloquent (inbound processing, PIN issuing, HTML sanitizing).
- `app/Http/**`, `routes/*.php`, `resources/views/**` — thin controllers + Blade. Routes are split per area: `routes/web.php` (includes the others), `mailbox.php`, `teacher.php`, `admin.php`, `webhook.php`.
- `resources/js/core/**` — pure JS (Vitest). `resources/js/components/**` — Alpine components that touch the DOM and call the core.

## Behaviour decisions (made by the tech lead)
- A mail is stored only if the **From address** is whitelisted AND the sender is authenticated and aligned (DMARC pass, or DKIM/SPF pass for the From domain). The SMTP envelope sender is *not* required to be whitelisted because mail services use bounce domains. Missing authentication data = reject. Rejected mails still get a 2xx answer.
- Whitelist entries: exact address, `@domain.tld` or `domain.tld` (exact domain, no subdomains).
- Student sessions store `address_id` + a fingerprint of the current PIN hash; a new PIN or an expired PIN ends the session on the next request.
- Webhook signature: `X-Lettermint-Signature: t=<unix>,v1=<hex hmac-sha256 of "<t>.<raw body>">`, 300 s tolerance.
- Locale: `App\Http\Middleware\SetLocale` (session choice, then Accept-Language, supported `de`,`en`, fallback `de`).
- Retention: mails older than min(configured days, 7) are pruned hourly.

## Commands (all must be green before every commit)
- `vendor/bin/pest` — PHP tests.
- `npx vitest run` — JS core tests.
- `composer typecheck` — `php -l` over app/tests/routes/config/database + `vendor/bin/pint --test` (there is no static analyser dependency).
- `npm run build` — Vite build.
- Local dev server: `php artisan serve --port=<own port>`; Vite: `npm run dev -- --port=<own port>`.

## Parallel work rules
Each work package owns an explicit list of files; never edit files outside it. Shared files (`routes/web.php`, `bootstrap/app.php`, `layouts/*`, `resources/js/app.js`, models, migrations) belong to the tech lead.
