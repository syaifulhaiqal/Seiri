/**
 * Remote clock in / out: asks the browser for the employee's location (if they allow it) and sends it
 * with the form, so HR can see where remote work happened. Submits anyway if location is refused.
 *
 * <form x-data="remoteClock" x-on:submit.prevent="submit()"> ... <x-submit-button>
 */
export default () => {
    return {
        submitting: false,
        latitude: '',
        longitude: '',

        submit() {
            if (this.submitting) {
                return;
            }

            this.submitting = true;

            if (! navigator.geolocation) {
                this.send();

                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.latitude = position.coords.latitude.toFixed(7);
                    this.longitude = position.coords.longitude.toFixed(7);
                    this.send();
                },
                () => this.send(),
                { enableHighAccuracy: true, timeout: 8000, maximumAge: 60000 },
            );
        },

        send() {
            this.$nextTick(() => this.$root.submit());
        },
    };
};
