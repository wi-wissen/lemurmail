// ALTCHA widget texts come from the server (lang/*/captcha.php), not from the widget's own translation tables.
export default (Alpine) => {
    Alpine.data('captcha', () => ({
        init() {
            const { locale, strings } = this.$el.dataset;
            globalThis.$altcha?.i18n.set(locale, JSON.parse(strings));
        },
    }));
};
