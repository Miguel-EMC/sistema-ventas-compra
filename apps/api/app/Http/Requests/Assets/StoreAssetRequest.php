<?php

namespace App\Http\Requests\Assets;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
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
            'code' => ['nullable', 'string', 'max:80', Rule::unique('assets', 'code')],
            'category_id' => ['nullable', 'integer', Rule::exists('asset_categories', 'id')],
            'description' => ['nullable', 'string', 'max:3000'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'acquisition_cost' => ['nullable', 'numeric', 'min:0'],
            'acquired_at' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:32'],
        ];
    }
}
