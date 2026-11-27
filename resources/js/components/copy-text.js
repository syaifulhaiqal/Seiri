/**
 * Copies a field's text to the clipboard. Falls back to selecting it where the clipboard API is not
 * available (pages not served over HTTPS).
 *
 * <div x-data="copyText"> <input x-ref="text" readonly> <button x-on:click="copy()"> ... x-show="copied"
 */
export default () => {
    return {
        copied: false,

        async copy() {
            const field = this.$refs.text;

            try {
                await navigator.clipboard.writeText(field.value);
            } catch {
                field.select();
                document.execCommand('copy');
            }

            this.copied = true;
            setTimeout(() => (this.copied = false), 2000);
        },
    };
};
