<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutSaleRequest extends FormRequest
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
            'payment_method' => ['required', 'string', Rule::in(['cash', 'card', 'transfer', 'check', 'credit'])],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:120'],
            'document_type' => ['nullable', 'string', Rule::in(['ticket', 'factura', 'nota'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
