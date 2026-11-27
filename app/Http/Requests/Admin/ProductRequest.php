<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Adding or editing a product.
 */
class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sku' => strtoupper(trim((string) $this->input('sku'))),
            'is_active' => $this->boolean('is_active'),
            'cost_price' => filled($this->input('cost_price')) ? $this->input('cost_price') : 0,
            'sale_price' => filled($this->input('sale_price')) ? $this->input('sale_price') : 0,
            'reorder_level' => filled($this->input('reorder_level')) ? $this->input('reorder_level') : 0,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:50', Rule::unique(Product::class)->ignore($this->route('product'))],
            'name' => ['required', 'string', 'max:150'],
            'unit' => ['required', 'string', 'max:20'],
            'cost_price' => ['numeric', 'min:0', 'max:99999999'],
            'sale_price' => ['numeric', 'min:0', 'max:99999999'],
            'reorder_level' => ['numeric', 'min:0', 'max:999999'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sku' => __('SKU'),
        ];
    }
}
