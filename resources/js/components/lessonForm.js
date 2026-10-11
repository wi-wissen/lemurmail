import { clockOffset, remainingDays, remainingMinutes, secondsLeft } from '../core/time.js';

const nowSeconds = () => Math.floor(Date.now() / 1000);

const DAY_SECONDS = 86400;

const MODES = { until: 'until', date: 'date' };

// Start form on the group page: the choice (45, 90, until a time or until a date) maps to the mode/minutes fields.
// Also hosts the countdown of a running lesson on the same page.
export default (Alpine) => {
    Alpine.data('lessonForm', () => ({
        choice: '45',

        get mode() {
            return MODES[this.choice] ?? 'minutes';
        },

        get minutes() {
            return this.mode === 'minutes' ? this.choice : '';
        },
    }));

    Alpine.data('lessonCountdown', () => ({
        endsAt: 0,
        offset: 0,
        template: '',
        daysOne: '',
        daysOther: '',
        text: '',
        timer: null,

        init() {
            this.endsAt = Number(this.$el.dataset.endsAt);
            this.template = this.$el.dataset.remainingTemplate;
            this.daysOne = this.$el.dataset.remainingDaysOne;
            this.daysOther = this.$el.dataset.remainingDaysOther;
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
            this.text = left >= DAY_SECONDS
                ? this.dayText(remainingDays(left))
                : this.template.replace(':minutes', String(remainingMinutes(left)));
        },

        dayText(days) {
            return (days === 1 ? this.daysOne : this.daysOther).replace(':count', String(days));
        },
    }));
};
