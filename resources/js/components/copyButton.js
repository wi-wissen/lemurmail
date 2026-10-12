const COPIED_MS = 2000;

export async function writeClipboard(text) {
    try {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(text);
            return true;
        }
    } catch {
        // Permission denied or insecure context: fall back to a selection copy.
    }

    const area = document.createElement('textarea');
    area.value = text;
    area.setAttribute('readonly', '');
    area.style.position = 'fixed';
    area.style.opacity = '0';
    document.body.appendChild(area);
    try {
        area.select();
        return document.execCommand('copy');
    } catch {
        return false;
    } finally {
        area.remove();
    }
}

export default (Alpine) => {
    Alpine.data('copyButton', () => ({
        copied: false,
        copiedLabel: '',
        timer: null,

        init() {
            this.copiedLabel = this.$el.dataset.copiedLabel ?? '';
        },

        async copy() {
            const ok = await writeClipboard(this.$el.dataset.copy ?? '');
            if (!ok) {
                return;
            }
            this.copied = true;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => {
                this.copied = false;
            }, COPIED_MS);
        },

        destroy() {
            clearTimeout(this.timer);
        },
    }));
};
