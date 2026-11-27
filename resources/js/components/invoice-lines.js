/**
 * Repeating item lines for an invoice or bill, with subtotal, tax and total.
 *
 * <div x-data="invoiceLines({ lines: @js($lines), taxRate: 8, defaultAccount: 12 })">
 */
export default ({ lines = [], taxRate = 0, defaultAccount = '' } = {}) => {
    const blank = () => ({ account_id: defaultAccount ?? '', description: '', quantity: 1, unit_price: '' });

    return {
        lines: lines.length ? lines : [blank()],
        taxRate,

        add() {
            this.lines.push(blank());
        },

        remove(index) {
            if (this.lines.length > 1) {
                this.lines.splice(index, 1);
            }
        },

        amount(line) {
            return Math.round((parseFloat(line.quantity) || 0) * (parseFloat(line.unit_price) || 0) * 100);
        },

        get subtotal() {
            return this.lines.reduce((sum, line) => sum + this.amount(line), 0);
        },

        get tax() {
            return Math.round(this.subtotal * (parseFloat(this.taxRate) || 0) / 100);
        },

        get total() {
            return this.subtotal + this.tax;
        },

        format(cents) {
            return (cents / 100).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
    };
};
