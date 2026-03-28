<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessSettingsRequest extends FormRequest
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
            'company_profile.legal_name' => ['required', 'string', 'max:255'],
            'company_profile.trade_name' => ['nullable', 'string', 'max:255'],
            'company_profile.tax_id' => ['nullable', 'string', 'max:120'],
            'company_profile.email' => ['nullable', 'email', 'max:255'],
            'company_profile.phone' => ['nullable', 'string', 'max:80'],
            'company_profile.website' => ['nullable', 'string', 'max:255'],
            'company_profile.address_line' => ['nullable', 'string', 'max:255'],
            'company_profile.city' => ['nullable', 'string', 'max:120'],
            'company_profile.region' => ['nullable', 'string', 'max:120'],
            'company_profile.country_code' => ['nullable', 'string', 'size:2'],
            'system_settings.currency_code' => ['required', 'string', Rule::exists('currencies', 'code')],
            'system_settings.locale_code' => ['required', 'string', Rule::exists('locales', 'code')],
            'system_settings.timezone' => ['required', 'string', 'max:100'],
            'system_settings.tax_included_prices' => ['required', 'boolean'],
            'system_settings.allow_negative_stock' => ['required', 'boolean'],
            'system_settings.default_document_type' => ['required', 'string', Rule::in(['ticket', 'factura', 'nota'])],
            'system_settings.invoice_footer' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
