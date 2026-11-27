<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Stock taken out (used, sold, damaged) or a count correction. Stock cannot go below zero.
 */
class StockAdjustmentRequest extends FormRequest
{
    protected $errorBag = 'stock';

    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'direction' => ['required', Rule::in(['in', 'out'])],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Product $product */
                $product = $this->route('product');

                if ($validator->errors()->isEmpty() && $this->input('direction') === 'out' && (float) $this->input('quantity') > $product->onHand()) {
                    $validator->errors()->add('quantity', __('Only :count :unit in stock.', ['count' => $product->onHand(), 'unit' => $product->unit]));
                }
            },
        ];
    }

    /**
     * Signed quantity: negative for stock out.
     */
    public function signedQuantity(): float
    {
        return (float) $this->validated('quantity') * ($this->validated('direction') === 'out' ? -1 : 1);
    }
}
