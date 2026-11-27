/**
 * Switches Bootstrap between light and dark mode and remembers the choice in this browser.
 * partials/theme-script.blade.php applies the saved choice before the page paints.
 */
export default () => {
    return {
        dark: document.documentElement.dataset.bsTheme === 'dark',

        toggle() {
            this.dark = ! this.dark;

            const theme = this.dark ? 'dark' : 'light';

            document.documentElement.dataset.bsTheme = theme;
            localStorage.setItem('theme', theme);
        },
    };
};
