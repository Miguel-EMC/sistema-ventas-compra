<?php

namespace App\Http\Requests\Assets;

use App\Http\Requests\Concerns\InteractsWithCompanyValidation;
use Illuminate\Foundation\Http\FormRequest;

class StoreAssetRequest extends FormRequest
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
            'code' => ['nullable', 'string', 'max:80', $this->uniqueForCurrentCompany('assets', 'code')],
            'category_id' => ['nullable', 'integer', $this->existsForCurrentCompany('asset_categories')],
            'description' => ['nullable', 'string', 'max:3000'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'acquisition_cost' => ['nullable', 'numeric', 'min:0'],
            'acquired_at' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:32'],
        ];
    }
}
