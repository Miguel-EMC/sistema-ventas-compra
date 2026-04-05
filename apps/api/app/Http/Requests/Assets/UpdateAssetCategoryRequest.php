<?php

namespace App\Http\Requests\Assets;

use App\Http\Requests\Concerns\InteractsWithCompanyValidation;
use App\Models\AssetCategory;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAssetCategoryRequest extends FormRequest
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
        /** @var AssetCategory $category */
        $category = $this->route('asset_category');

        return [
            'name' => ['required', 'string', 'max:255', $this->uniqueForCurrentCompany('asset_categories', 'name', $category->id)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
