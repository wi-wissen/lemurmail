const TABS = ['addresses', 'senders', 'settings'];

// Tabs of the group page. The panels are all rendered by the server; this only toggles them
// and keeps the choice in the URL hash so a reload (or a link) lands on the same tab.
export default (Alpine) => {
    Alpine.data('groupTabs', (initial) => ({
        tab: initial,

        init() {
            const fromHash = window.location.hash.slice(1);
            if (TABS.includes(fromHash) && !this.hasServerChoice) {
                this.tab = fromHash;
            }
        },

        // Validation errors pick the tab on the server; the hash must not override that.
        get hasServerChoice() {
            return this.tab !== TABS[0];
        },

        select(name) {
            this.tab = name;
            try {
                history.replaceState(null, '', `#${name}`);
            } catch {
                // Keep the in-page state only.
            }
        },
    }));
};
