<?php

namespace App\Http\Requests\Concerns;

use App\Models\Product;
use Illuminate\Validation\Rule;

/**
 * Item lines (product, description, quantity, unit price) for purchase requests, purchase orders and quotations.
 * Blank rows from the form are dropped before validation.
 */
trait ValidatesLineItems
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'lines' => collect($this->input('lines', []))
                ->reject(fn ($line) => blank($line['description'] ?? null) && blank($line['unit_price'] ?? null) && blank($line['product_id'] ?? null))
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function lineRules(): array
    {
        return [
            'lines' => ['required', 'array', 'min:1', 'max:50'],
            'lines.*.product_id' => ['nullable', Rule::exists(Product::class, 'id')],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lines.required' => __('Add at least one line.'),
            'lines.*.description.required' => __('Describe every line.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'contact_id' => __('supplier'),
            'lines.*.quantity' => __('quantity'),
            'lines.*.unit_price' => __('price'),
        ];
    }
}
