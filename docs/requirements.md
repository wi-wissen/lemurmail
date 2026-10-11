# Requirements (summary)

Source: the owner's requirements document (German) and the mockup canvas "Mockups: Lemur Mail".

- Receive-only throwaway mailbox service for schools (~20 teachers + their classes). Mails are deleted after 7 days at the latest.
- Roles: Super-Admin (approves registrations, maintains organisation + freemail domains, address caps), Teacher (addresses, PINs, groups, lessons, whitelist), Mailbox user/student (address + PIN, no account).
- Teacher registration: e-mail + password + Friendly Captcha, e-mail verification (Breeze). Approved organisation domain = active at once, otherwise "pending approval" and the admin is notified. Own mailbox domain and freemail/disposable domains are rejected.
- Addresses: single or series (prefix + count), local part `a-z 0-9 - _ .`, globally unique, 8-character PIN without look-alike characters, cap per teacher (default 200).
- Lessons ("Stunde"): per group, duration 45/90 min or until a time (max configurable, default 12 h), fresh PINs with expiry, beamer view (dark, huge type, countdown, fullscreen, font size, hide PINs), extend +15 min, end now, regenerate all/one PIN. Expiry is checked per request, no cron.
- Student login: address + PIN + Friendly Captcha + rate limit (5 failures / 15 min per address and IP, same error always). Inbox polls every ~10 s, text view by default, HTML only in a sandboxed iframe (purified, strict CSP, no remote images).
- Whitelist: only mails from allowed senders are stored; empty whitelist is an error; default whitelist per teacher + per-address additions.
- Secret per address: optional, stored encrypted, visible only after PIN login; students and teachers may set/change it.
- Lettermint inbound webhook `POST /webhooks/lettermint`: signature check on the raw body, idempotency on the top-level id, routing by `data.recipient`, always 2xx for unknown recipients, no attachments, no raw mail. Artisan `mail:simulate` sends a correctly signed fake webhook.
- Stack: Laravel + Breeze (Blade, Alpine, Tailwind, Pest), SQLite, Vite, self-hosted fonts (Atkinson Hyperlegible, JetBrains Mono), i18n de + en, Sail for development, own Dockerfile for production, GitHub Actions (tests on push, image to GHCR on version tags).
- Name: Lemur Mail (repo/image `lemurmail`, product page lemurmail.wi7.net, instance tmp-mail.nein-ev.de). Mascot: ring-tailed lemur; the name comes from the Roman *lemures* (easter egg in README / about page).
