<?php

namespace App\Http\Requests\Sales;

use App\Http\Requests\Concerns\InteractsWithCompanyValidation;
use Illuminate\Foundation\Http\FormRequest;

class StoreSaleDraftItemRequest extends FormRequest
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
            'product_id' => ['required', 'integer', $this->existsForCurrentCompany('products')],
            'quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
