/**
 * Disables the submit button and shows a spinner while the form submits,
 * so a double click cannot send the form twice.
 *
 * <form x-data="formSubmit" x-on:submit="submit()"> ... <button x-bind:disabled="submitting">
 */
export default () => {
    return {
        submitting: false,

        submit() {
            this.submitting = true;
        },
    };
};
