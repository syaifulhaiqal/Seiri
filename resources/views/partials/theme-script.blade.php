{{-- Runs before the page paints, so dark mode does not flash light first. The toggle is resources/js/components/theme-toggle.js. --}}
<script>
    (() => {
        const root = document.documentElement;
        const theme = () => localStorage.getItem('theme') ?? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

        root.dataset.bsTheme = theme();

        // Payslips, invoices and reports always print on white.
        window.addEventListener('beforeprint', () => root.dataset.bsTheme = 'light');
        window.addEventListener('afterprint', () => root.dataset.bsTheme = theme());
    })();
</script>
