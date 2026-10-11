import { clockOffset, remainingMinutes, secondsLeft } from '../core/time.js';

const nowSeconds = () => Math.floor(Date.now() / 1000);

// Start form on the group page: the choice (45, 90 or until a time) maps to the mode/minutes fields.
// Also hosts the countdown of a running lesson on the same page.
export default (Alpine) => {
    Alpine.data('lessonForm', () => ({
        choice: '45',

        get mode() {
            return this.choice === 'until' ? 'until' : 'minutes';
        },

        get minutes() {
            return this.choice === 'until' ? '' : this.choice;
        },
    }));

    Alpine.data('lessonCountdown', () => ({
        endsAt: 0,
        offset: 0,
        template: '',
        text: '',
        timer: null,

        init() {
            this.endsAt = Number(this.$el.dataset.endsAt);
            this.template = this.$el.dataset.remainingTemplate;
            this.offset = clockOffset(Number(this.$el.dataset.serverNow), nowSeconds());
            this.tick();
            this.timer = setInterval(() => this.tick(), 1000);
        },

        tick() {
            const left = secondsLeft(this.endsAt, nowSeconds(), this.offset);
            if (left <= 0) {
                clearInterval(this.timer);
                window.location.reload();
                return;
            }
            this.text = this.template.replace(':minutes', String(remainingMinutes(left)));
        },
    }));
};
