<?php

namespace App\Http\Requests\Catalog;

use App\Http\Requests\Concerns\InteractsWithCompanyValidation;
use App\Models\ProductCategory;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductCategoryRequest extends FormRequest
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
        /** @var ProductCategory $category */
        $category = $this->route('product_category');

        return [
            'name' => ['required', 'string', 'max:255', $this->uniqueForCurrentCompany('product_categories', 'name', $category->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
