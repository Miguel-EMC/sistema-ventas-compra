<?php

namespace App\Http\Requests\Assets;

use App\Models\AssetCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetCategoryRequest extends FormRequest
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
        /** @var AssetCategory $category */
        $category = $this->route('asset_category');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('asset_categories', 'name')->ignore($category->id)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
