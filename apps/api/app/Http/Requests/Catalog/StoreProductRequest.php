<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'sku' => ['nullable', 'string', 'max:80', Rule::unique('products', 'sku')],
            'barcode' => ['nullable', 'string', 'max:80', Rule::unique('products', 'barcode')],
            'category_id' => ['nullable', 'integer', Rule::exists('product_categories', 'id')],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:32'],
            'track_stock' => ['sometimes', 'boolean'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'initial_stock' => ['nullable', 'numeric'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
