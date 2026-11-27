/**
 * Show / hide toggle for a password input.
 */
export default () => {
    return {
        visible: false,

        toggle() {
            this.visible = !this.visible;
            this.$refs.input.focus();
        },
    };
};
