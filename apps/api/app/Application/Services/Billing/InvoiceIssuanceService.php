<?php

namespace App\Application\Services\Billing;

use App\Models\CompanyProfile;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SystemSetting;
use App\Models\TaxResolution;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class InvoiceIssuanceService
{
    public function issueForSale(Sale $sale): Invoice
    {
        $sale->loadMissing('customer', 'items');

        if ($sale->customer === null) {
            throw ValidationException::withMessages([
                'document_type' => 'Debes seleccionar un cliente para emitir factura.',
            ]);
        }

        if (blank($sale->customer->document_number)) {
            throw ValidationException::withMessages([
                'document_type' => 'El cliente seleccionado debe tener documento para emitir factura.',
            ]);
        }

        $resolution = TaxResolution::query()
            ->where('is_active', true)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if (! $resolution instanceof TaxResolution) {
            throw ValidationException::withMessages([
                'document_type' => 'No hay una dosificacion activa para emitir facturas.',
            ]);
        }

        $sequenceNumber = (int) $resolution->next_invoice_number;

        if ($sequenceNumber < (int) $resolution->invoice_number_start || $sequenceNumber > (int) $resolution->invoice_number_end) {
            throw ValidationException::withMessages([
                'document_type' => 'La dosificacion activa ya no tiene numeros disponibles.',
            ]);
        }

        $companyProfile = $this->primaryCompanyProfile();
        $companyMetadata = is_array($companyProfile->metadata) ? $companyProfile->metadata : [];
        $currencyCode = (string) ($this->settingValue('currency_code') ?? 'USD');
        $footer = $this->nullableText($this->settingValue('invoice_footer'));

        $invoice = Invoice::query()->create([
            'public_id' => (string) Str::uuid(),
            'sale_id' => $sale->id,
            'tax_resolution_id' => $resolution->id,
            'customer_id' => $sale->customer_id,
            'status' => 'issued',
            'sequence_number' => $sequenceNumber,
            'invoice_number' => $this->formatInvoiceNumber($resolution, $sequenceNumber),
            'authorization_number_snapshot' => $resolution->authorization_number,
            'company_name_snapshot' => $companyProfile->trade_name ?: $companyProfile->legal_name,
            'company_tax_id_snapshot' => $companyProfile->tax_id,
            'customer_name_snapshot' => $sale->customer->name,
            'customer_document_type_snapshot' => $sale->customer->document_type,
            'customer_document_number_snapshot' => $sale->customer->document_number,
            'subtotal' => $sale->subtotal,
            'tax_total' => $sale->tax_total,
            'grand_total' => $sale->grand_total,
            'currency_code' => strtoupper($currencyCode),
            'footer' => $footer,
            'legend' => $resolution->legend,
            'issued_at' => $sale->sold_at ?? now(),
            'metadata' => [
                'resolution_name' => $resolution->name,
                'resolution_series' => $resolution->series,
                'technical_key' => $resolution->technical_key,
                'company_phone' => $companyProfile->phone,
                'company_address_line' => $companyProfile->address_line,
                'company_city' => $companyProfile->city,
                'company_region' => $companyProfile->region,
                'billing_owner_name' => $this->nullableText($companyMetadata['billing_owner_name'] ?? null),
                'billing_address_reference' => $this->nullableText($companyMetadata['billing_address_reference'] ?? null),
            ],
        ]);

        $sale->items->each(function (SaleItem $item) use ($invoice): void {
            InvoiceItem::query()->create([
                'invoice_id' => $invoice->id,
                'sale_item_id' => $item->id,
                'product_id' => $item->product_id,
                'description' => $item->name_snapshot,
                'sku_snapshot' => $item->sku_snapshot,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_subtotal' => $item->line_subtotal,
                'line_tax' => $item->line_tax,
                'line_total' => $item->line_total,
            ]);
        });

        $resolution->update([
            'next_invoice_number' => $sequenceNumber + 1,
        ]);

        return $invoice->load('taxResolution', 'items');
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

    private function settingValue(string $key): mixed
    {
        return SystemSetting::query()->where('key', $key)->first()?->value;
    }

    private function formatInvoiceNumber(TaxResolution $resolution, int $sequenceNumber): string
    {
        $number = str_pad((string) $sequenceNumber, 8, '0', STR_PAD_LEFT);
        $series = trim((string) ($resolution->series ?? ''));

        return $series === '' ? $number : "{$series}-{$number}";
    }

    private function nullableText(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }
}
