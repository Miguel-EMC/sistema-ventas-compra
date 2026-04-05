<?php

namespace App\Http\Requests\Sales;

use App\Http\Requests\Concerns\InteractsWithCompanyValidation;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSaleDraftRequest extends FormRequest
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
            'customer_id' => ['nullable', 'integer', $this->existsForCurrentCompany('customers')],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
