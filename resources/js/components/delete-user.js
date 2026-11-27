/**
 * One shared confirmation modal for deleting users.
 * Trigger buttons call open(name, url); the modal form then submits a DELETE to that url.
 */
export default () => {
    return {
        name: '',
        action: '',
        submitting: false,
        modal: null,

        init() {
            this.modal = new window.bootstrap.Modal(this.$refs.modal);
        },

        destroy() {
            this.modal?.dispose();
        },

        open(name, action) {
            this.name = name;
            this.action = action;
            this.submitting = false;
            this.modal.show();
        },
    };
};
