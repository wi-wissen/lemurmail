import { mergeMessages, nextSelection, pollDelay } from '../core/inbox.js';
import { clockOffset, formatClock, remainingDays, remainingMinutes, secondsLeft } from '../core/time.js';

const MAX_POLL_DELAY_MS = 60000;

const DAY_SECONDS = 86400;

const localNow = () => Math.floor(Date.now() / 1000);

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

/** Replaces ":key" placeholders of a server-side translation. */
const fill = (template, values) =>
    Object.entries(values).reduce((text, [key, value]) => text.replace(`:${key}`, String(value)), template);

export default (Alpine) => {
    // Mailbox list, polling, selected mail and secret. Strings and URLs come from the Blade view.
    Alpine.data('inbox', (config) => ({
        strings: config.strings,
        hasSecret: config.hasSecret,
        messages: [],
        loaded: false,
        loadError: false,
        failures: 0,
        selectedId: null,
        detail: null,
        view: 'text',
        secretVisible: false,
        secret: null,
        ended: false,

        init() {
            this.poll();
        },

        get countText() {
            if (!this.loaded) {
                return this.strings.loading;
            }
            const count = this.messages.length;
            if (count === 0) {
                return this.strings.countNone;
            }
            return fill(count === 1 ? this.strings.countOne : this.strings.countMany, { count });
        },

        get secretText() {
            if (!this.hasSecret) {
                return this.strings.secretNone;
            }
            return this.secretVisible ? (this.secret ?? '') : this.strings.secretMask;
        },

        clock(unixSeconds) {
            return formatClock(unixSeconds, config.utcOffsetMinutes);
        },

        async poll() {
            try {
                const response = await fetch(config.messagesUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (response.status === 401) {
                    return this.endSession();
                }
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                this.receive(await response.json());
                this.failures = 0;
                this.loadError = false;
            } catch {
                this.failures += 1;
                this.loadError = true;
            }
            setTimeout(() => this.poll(), pollDelay(config.pollMs, this.failures, MAX_POLL_DELAY_MS));
        },

        receive(data) {
            const incoming = data.messages.map((message) => ({
                id: message.id,
                from: message.from,
                subject: message.subject,
                receivedAt: message.received_at,
            }));
            this.messages = mergeMessages(this.messages, incoming).messages;
            this.loaded = true;
            this.select(nextSelection(this.messages, this.selectedId));
        },

        async select(id) {
            if (id === this.selectedId) {
                return;
            }
            this.selectedId = id;
            this.detail = null;
            this.view = 'text';
            if (id === null) {
                return;
            }
            try {
                const response = await fetch(`${config.messagesUrl}/${id}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (response.status === 401) {
                    return this.endSession();
                }
                if (response.ok && this.selectedId === id) {
                    this.detail = await response.json();
                }
            } catch {
                // The next poll reconciles the list; the detail stays empty until then.
            }
        },

        async remove() {
            const id = this.selectedId;
            if (id === null || !window.confirm(this.strings.deleteConfirm)) {
                return;
            }
            try {
                const response = await fetch(`${config.messagesUrl}/${id}`, {
                    method: 'DELETE',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                    credentials: 'same-origin',
                });
                if (response.status === 401) {
                    return this.endSession();
                }
                if (!response.ok) {
                    return;
                }
            } catch {
                return;
            }
            this.messages = this.messages.filter((message) => message.id !== id);
            this.selectedId = null;
            this.select(nextSelection(this.messages, id));
        },

        async toggleSecret() {
            if (this.secretVisible) {
                this.secretVisible = false;
                return;
            }
            try {
                const response = await fetch(config.secretUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (response.status === 401) {
                    return this.endSession();
                }
                if (!response.ok) {
                    return;
                }
                this.secret = (await response.json()).secret;
                this.secretVisible = true;
            } catch {
                this.secretVisible = false;
            }
        },

        endSession() {
            this.ended = true;
            window.location.assign(config.homeUrl);
        },
    }));

    // Counts down the PIN validity on the server clock (offset from the render time).
    Alpine.data('pinCountdown', (config) => {
        const offset = clockOffset(config.serverNow, localNow());

        return {
            now: localNow(),
            timer: null,

            init() {
                this.timer = setInterval(() => {
                    this.now = localNow();
                    if (this.left === 0) {
                        clearInterval(this.timer);
                        window.location.assign(config.homeUrl);
                    }
                }, 1000);
            },

            get left() {
                return secondsLeft(config.expiresAt, this.now, offset);
            },

            // Whole days while a day or more is left, minutes below that.
            get label() {
                if (this.left >= DAY_SECONDS) {
                    const days = remainingDays(this.left);
                    return fill(days === 1 ? config.daysOne : config.daysOther, { count: days });
                }
                return fill(config.template, { minutes: remainingMinutes(this.left) });
            },
        };
    });
};
