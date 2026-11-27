/**
 * Submits a GET filter form when a select changes.
 */
export default () => {
    return {
        submit() {
            this.$root.requestSubmit();
        },
    };
};
