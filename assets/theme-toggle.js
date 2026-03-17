(function () {
    const STORAGE_KEY = 'theme';
    const root = document.documentElement;

    function getPreferredTheme() {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored === 'light' || stored === 'dark') {
            return stored;
        }
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
            return 'light';
        }
        return 'dark';
    }

    function setTheme(theme) {
        root.dataset.theme = theme;
        localStorage.setItem(STORAGE_KEY, theme);
    }

    function init() {
        // Don't overwrite theme - head script + inline script already handle init
        const toggle = document.getElementById('theme-toggle');
        if (toggle) {
            toggle.addEventListener('click', () => {
                const current = root.dataset.theme || 'dark';
                setTheme(current === 'dark' ? 'light' : 'dark');
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
