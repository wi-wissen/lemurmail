import { clockOffset, formatRemaining, secondsLeft } from '../core/time.js';
import { stepFontScale } from '../core/fontScale.js';

const FONT_KEY = 'lemurmail.beamer.fontScale';
const HIDE_KEY = 'lemurmail.beamer.hidePins';

const nowSeconds = () => Math.floor(Date.now() / 1000);

// Per-viewer convenience only: storage may be blocked or unavailable.
function load(key, fallback) {
    try {
        return localStorage.getItem(key) ?? fallback;
    } catch {
        return fallback;
    }
}

function save(key, value) {
    try {
        localStorage.setItem(key, String(value));
    } catch {
        // Keep the in-page state only.
    }
}

export default (Alpine) => {
    Alpine.data('beamer', () => ({
        endsAt: 0,
        offset: 0,
        left: 0,
        timer: null,
        scale: 100,
        hidePins: false,

        init() {
            this.endsAt = Number(this.$el.dataset.endsAt);
            this.offset = clockOffset(Number(this.$el.dataset.serverNow), nowSeconds());
            this.scale = Number(load(FONT_KEY, 100));
            this.hidePins = load(HIDE_KEY, '0') === '1';
            this.tick();
            this.timer = setInterval(() => this.tick(), 1000);
        },

        // When the lesson is over the server redirects away from the beamer on reload.
        tick() {
            this.left = secondsLeft(this.endsAt, nowSeconds(), this.offset);
            if (this.left <= 0) {
                clearInterval(this.timer);
                window.location.reload();
            }
        },

        get remaining() {
            return formatRemaining(this.left);
        },

        changeScale(direction) {
            this.scale = stepFontScale(this.scale, direction);
            save(FONT_KEY, this.scale);
        },

        togglePins() {
            this.hidePins = !this.hidePins;
            save(HIDE_KEY, this.hidePins ? '1' : '0');
        },

        toggleFullscreen() {
            try {
                const pending = document.fullscreenElement
                    ? document.exitFullscreen()
                    : document.documentElement.requestFullscreen();
                pending?.catch(() => {});
            } catch {
                // Fullscreen not supported by this browser.
            }
        },
    }));
};
