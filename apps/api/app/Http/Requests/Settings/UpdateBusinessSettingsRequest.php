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
            'company_profile.metadata' => ['nullable', 'array'],
            'company_profile.metadata.billing_owner_name' => ['nullable', 'string', 'max:255'],
            'company_profile.metadata.billing_address_reference' => ['nullable', 'string', 'max:120'],
            'system_settings.currency_code' => ['required', 'string', Rule::exists('currencies', 'code')],
            'system_settings.locale_code' => ['required', 'string', Rule::exists('locales', 'code')],
            'system_settings.timezone' => ['required', 'string', 'max:100'],
            'system_settings.tax_included_prices' => ['required', 'boolean'],
            'system_settings.allow_negative_stock' => ['required', 'boolean'],
            'system_settings.default_document_type' => ['required', 'string', Rule::in(['ticket', 'factura', 'nota'])],
            'system_settings.invoice_footer' => ['nullable', 'string', 'max:1000'],
            'active_tax_resolution' => ['nullable', 'array'],
            'active_tax_resolution.id' => ['nullable', 'integer', Rule::exists('tax_resolutions', 'id')],
            'active_tax_resolution.name' => ['required_with:active_tax_resolution', 'string', 'max:255'],
            'active_tax_resolution.authorization_number' => ['required_with:active_tax_resolution', 'string', 'max:120'],
            'active_tax_resolution.series' => ['nullable', 'string', 'max:32'],
            'active_tax_resolution.invoice_number_start' => ['required_with:active_tax_resolution', 'integer', 'min:1'],
            'active_tax_resolution.invoice_number_end' => ['required_with:active_tax_resolution', 'integer', 'gte:active_tax_resolution.invoice_number_start'],
            'active_tax_resolution.next_invoice_number' => [
                'required_with:active_tax_resolution',
                'integer',
                'gte:active_tax_resolution.invoice_number_start',
                'lte:active_tax_resolution.invoice_number_end',
            ],
            'active_tax_resolution.starts_at' => ['required_with:active_tax_resolution', 'date'],
            'active_tax_resolution.ends_at' => ['nullable', 'date', 'after_or_equal:active_tax_resolution.starts_at'],
            'active_tax_resolution.technical_key' => ['nullable', 'string', 'max:255'],
            'active_tax_resolution.legend' => ['nullable', 'string', 'max:500'],
        ];
    }
}
