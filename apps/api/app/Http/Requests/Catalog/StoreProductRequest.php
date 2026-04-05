<?php

namespace App\Http\Requests\Catalog;

use App\Http\Requests\Concerns\InteractsWithCompanyValidation;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    use InteractsWithCompanyValidation;

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
            'sku' => ['nullable', 'string', 'max:80', $this->uniqueForCurrentCompany('products', 'sku')],
            'barcode' => ['nullable', 'string', 'max:80', $this->uniqueForCurrentCompany('products', 'barcode')],
            'category_id' => ['nullable', 'integer', $this->existsForCurrentCompany('product_categories')],
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
