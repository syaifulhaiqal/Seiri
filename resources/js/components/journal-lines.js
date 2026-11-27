/**
 * Repeating debit / credit lines for a manual journal entry, with running totals.
 *
 * <div x-data="journalLines(@js($lines))"> ... <template x-for="(line, index) in lines">
 */
export default (initial = []) => {
    const blank = () => ({ account_id: '', description: '', debit: '', credit: '' });

    return {
        lines: initial.length >= 2 ? initial : [blank(), blank()],

        add() {
            this.lines.push(blank());
        },

        remove(index) {
            if (this.lines.length > 2) {
                this.lines.splice(index, 1);
            }
        },

        cents(value) {
            return Math.round((parseFloat(value) || 0) * 100);
        },

        get debit() {
            return this.lines.reduce((sum, line) => sum + this.cents(line.debit), 0);
        },

        get credit() {
            return this.lines.reduce((sum, line) => sum + this.cents(line.credit), 0);
        },

        get balanced() {
            return this.debit > 0 && this.debit === this.credit;
        },

        format(cents) {
            return (cents / 100).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
    };
};
