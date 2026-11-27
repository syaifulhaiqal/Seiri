/**
 * Office clock-in screen: swaps in a new QR code every few seconds and can go full screen.
 *
 * <div x-data="qrKiosk(codeUrl, refreshSeconds)"> <div x-ref="code">...svg...</div>
 */
export default (codeUrl, refreshSeconds) => {
    return {
        timer: null,
        offline: false,

        init() {
            this.timer = setInterval(() => this.refresh(), refreshSeconds * 1000);
        },

        destroy() {
            clearInterval(this.timer);
        },

        async refresh() {
            try {
                const response = await fetch(codeUrl, { headers: { Accept: 'application/json' } });

                if (! response.ok) {
                    throw new Error(response.statusText);
                }

                this.$refs.code.innerHTML = (await response.json()).svg;
                this.offline = false;
            } catch {
                this.offline = true;
            }
        },

        fullscreen() {
            this.$root.requestFullscreen?.();
        },
    };
};
