<?php

namespace App\Http\Requests\Partners;

use App\Http\Requests\Concerns\InteractsWithCompanyValidation;
use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
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
        /** @var Supplier $supplier */
        $supplier = $this->route('supplier');

        return [
            'document_type' => ['nullable', 'string', 'max:32'],
            'document_number' => ['nullable', 'string', 'max:80', $this->uniqueForCurrentCompany('suppliers', 'document_number', $supplier->id)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
