<?php

namespace App\Application\Services\Settings;

use App\Models\CompanyProfile;
use App\Models\Currency;
use App\Models\Locale;
use App\Models\SystemSetting;
use App\Models\TaxResolution;
use Illuminate\Support\Facades\DB;

class BusinessSettingsService
{
    /**
     * @var array<string, mixed>
     */
    private const DEFAULT_SETTINGS = [
        'currency_code' => 'USD',
        'locale_code' => 'es-EC',
        'timezone' => 'America/Guayaquil',
        'tax_included_prices' => false,
        'allow_negative_stock' => false,
        'default_document_type' => 'ticket',
        'invoice_footer' => null,
    ];

    /**
     * @return array<string, mixed>
     */
    public function show(): array
    {
        $companyProfile = $this->primaryCompanyProfile();
        $currencies = Currency::query()->orderByDesc('is_default')->orderBy('name')->get();
        $locales = Locale::query()->orderByDesc('is_default')->orderBy('name')->get();
        $systemSettings = $this->loadSystemSettings();
        $taxResolutions = TaxResolution::query()
            ->orderByDesc('is_active')
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->get();
        $activeTaxResolution = $taxResolutions->firstWhere('is_active', true);

        $currency = $currencies->firstWhere('code', $systemSettings['currency_code'])
            ?? $currencies->firstWhere('is_default', true)
            ?? $currencies->first();
        $locale = $locales->firstWhere('code', $systemSettings['locale_code'])
            ?? $locales->firstWhere('is_default', true)
            ?? $locales->first();

        if ($currency instanceof Currency) {
            $systemSettings['currency_code'] = $currency->code;
        }

        if ($locale instanceof Locale) {
            $systemSettings['locale_code'] = $locale->code;
        }

        return [
            'company_profile' => $companyProfile,
            'currency' => $currency,
            'locale' => $locale,
            'currencies' => $currencies,
            'locales' => $locales,
            'active_tax_resolution' => $activeTaxResolution,
            'tax_resolutions' => $taxResolutions,
            'system_settings' => $systemSettings,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(array $payload): array
    {
        return DB::transaction(function () use ($payload): array {
            $companyPayload = $payload['company_profile'] ?? [];
            $systemPayload = $payload['system_settings'] ?? [];

            $companyProfile = $this->primaryCompanyProfile();
            $companyProfile->update([
                'legal_name' => $companyPayload['legal_name'],
                'trade_name' => $this->nullableText($companyPayload['trade_name'] ?? null),
                'tax_id' => $this->nullableText($companyPayload['tax_id'] ?? null),
                'email' => $this->nullableText($companyPayload['email'] ?? null),
                'phone' => $this->nullableText($companyPayload['phone'] ?? null),
                'website' => $this->nullableText($companyPayload['website'] ?? null),
                'address_line' => $this->nullableText($companyPayload['address_line'] ?? null),
                'city' => $this->nullableText($companyPayload['city'] ?? null),
                'region' => $this->nullableText($companyPayload['region'] ?? null),
                'country_code' => $this->nullableCountryCode($companyPayload['country_code'] ?? null),
                'is_primary' => true,
                'metadata' => $this->syncCompanyMetadata(
                    $companyProfile,
                    is_array($companyPayload['metadata'] ?? null) ? $companyPayload['metadata'] : [],
                ),
            ]);

            $currencyCode = (string) ($systemPayload['currency_code'] ?? self::DEFAULT_SETTINGS['currency_code']);
            $localeCode = (string) ($systemPayload['locale_code'] ?? self::DEFAULT_SETTINGS['locale_code']);

            Currency::query()->update(['is_default' => false]);
            Currency::query()->where('code', $currencyCode)->update(['is_default' => true]);

            Locale::query()->update(['is_default' => false]);
            Locale::query()->where('code', $localeCode)->update(['is_default' => true]);

            foreach (self::DEFAULT_SETTINGS as $key => $defaultValue) {
                $this->persistSetting($key, $systemPayload[$key] ?? $defaultValue);
            }

            if (array_key_exists('active_tax_resolution', $payload)) {
                $this->syncActiveTaxResolution($payload['active_tax_resolution'] ?? null, $companyProfile);
            }

            return $this->show();
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function loadSystemSettings(): array
    {
        $settings = self::DEFAULT_SETTINGS;

        SystemSetting::query()
            ->whereIn('key', array_keys(self::DEFAULT_SETTINGS))
            ->get()
            ->each(function (SystemSetting $setting) use (&$settings): void {
                $settings[$setting->key] = $setting->value;
            });

        return $settings;
    }

    private function primaryCompanyProfile(): CompanyProfile
    {
        return CompanyProfile::query()->where('is_primary', true)->first()
            ?? CompanyProfile::query()->first()
            ?? CompanyProfile::query()->create([
                'legal_name' => 'VentasPOS Demo',
                'trade_name' => 'VentasPOS',
                'country_code' => 'EC',
                'is_primary' => true,
            ]);
    }

    private function persistSetting(string $key, mixed $value): void
    {
        SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'group' => $this->settingGroup($key),
                'value' => $value,
            ],
        );
    }

    private function settingGroup(string $key): string
    {
        return match ($key) {
            'tax_included_prices', 'default_document_type', 'invoice_footer' => 'sales',
            'allow_negative_stock' => 'stock',
            default => 'general',
        };
    }

    private function nullableText(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }

    private function nullableCountryCode(mixed $value): ?string
    {
        $normalized = $this->nullableText($value);

        return $normalized ? strtoupper($normalized) : null;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|null
     */
    private function syncCompanyMetadata(CompanyProfile $companyProfile, array $payload): ?array
    {
        $metadata = is_array($companyProfile->metadata) ? $companyProfile->metadata : [];

        $metadata['billing_owner_name'] = $this->nullableText($payload['billing_owner_name'] ?? null);
        $metadata['billing_address_reference'] = $this->nullableText($payload['billing_address_reference'] ?? null);

        $metadata = array_filter(
            $metadata,
            static fn (mixed $value): bool => $value !== null && $value !== ''
        );

        return $metadata === [] ? null : $metadata;
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function syncActiveTaxResolution(?array $payload, CompanyProfile $companyProfile): void
    {
        if ($payload === null) {
            TaxResolution::query()->where('is_active', true)->update(['is_active' => false]);

            return;
        }

        TaxResolution::query()->where('is_active', true)->update(['is_active' => false]);

        $resolution = isset($payload['id'])
            ? TaxResolution::query()->find($payload['id'])
            : null;

        $attributes = [
            'company_profile_id' => $companyProfile->id,
            'name' => trim((string) $payload['name']),
            'authorization_number' => trim((string) $payload['authorization_number']),
            'series' => $this->nullableText($payload['series'] ?? null),
            'invoice_number_start' => (int) $payload['invoice_number_start'],
            'invoice_number_end' => (int) $payload['invoice_number_end'],
            'next_invoice_number' => (int) $payload['next_invoice_number'],
            'starts_at' => $payload['starts_at'],
            'ends_at' => $payload['ends_at'] ?? null,
            'technical_key' => $this->nullableText($payload['technical_key'] ?? null),
            'legend' => $this->nullableText($payload['legend'] ?? null),
            'is_active' => true,
        ];

        if ($resolution instanceof TaxResolution) {
            $resolution->update($attributes);

            return;
        }

        TaxResolution::query()->create($attributes);
    }
}
