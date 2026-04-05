<?php

namespace App\Http\Requests\Purchases;

use App\Http\Requests\Concerns\InteractsWithCompanyValidation;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
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
            'supplier_id' => ['required', 'integer', $this->existsForCurrentCompany('suppliers')],
            'reference' => ['nullable', 'string', 'max:120'],
            'ordered_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', $this->existsForCurrentCompany('products')],
            'items.*.quantity_ordered' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
