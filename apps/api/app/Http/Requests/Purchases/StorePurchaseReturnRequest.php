<?php

namespace App\Http\Requests\Purchases;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseReturnRequest extends FormRequest
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
            'returned_at' => ['nullable', 'date'],
            'reason' => ['required', 'string', 'max:3000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'integer', Rule::exists('purchase_order_items', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
