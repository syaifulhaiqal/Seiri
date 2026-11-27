/**
 * Item lines for purchase requests, purchase orders and quotations, with subtotal, tax and total.
 * Each line may link a product: picking one fills in its name and price (cost for purchasing,
 * sale price for quotations).
 *
 * <div x-data="itemLines({ lines: @js($lines), products: @js($products), priceKey: 'cost', taxRate: 0 })">
 */
export default ({ lines = [], products = {}, priceKey = 'cost', taxRate = 0 } = {}) => {
    const blank = () => ({ product_id: '', description: '', quantity: 1, unit_price: '' });

    return {
        lines: lines.length ? lines.map((line) => ({ ...blank(), ...line, product_id: line.product_id ?? '' })) : [blank()],
        products,
        taxRate,

        add() {
            this.lines.push(blank());
        },

        remove(index) {
            if (this.lines.length > 1) {
                this.lines.splice(index, 1);
            }
        },

        pick(line) {
            const product = this.products[line.product_id];

            if (product) {
                line.description = product.name;
                line.unit_price = product[priceKey];
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
