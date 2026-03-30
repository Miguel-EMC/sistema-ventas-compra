<?php

namespace App\Application\Services\Billing;

use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Invoice;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreditNoteIssuanceService
{
    public function issueForSaleReturn(User $user, SaleReturn $saleReturn): CreditNote
    {
        return DB::transaction(function () use ($user, $saleReturn): CreditNote {
            /** @var SaleReturn $lockedSaleReturn */
            $lockedSaleReturn = SaleReturn::query()
                ->whereKey($saleReturn->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedSaleReturn->loadMissing([
                'sale.invoice.taxResolution',
                'sale.customer',
                'items',
                'creditNote',
            ]);

            if ($lockedSaleReturn->status !== 'completed') {
                throw ValidationException::withMessages([
                    'sale_return' => 'Solo se pueden emitir notas de credito para devoluciones completadas.',
                ]);
            }

            if ($lockedSaleReturn->creditNote !== null) {
                throw ValidationException::withMessages([
                    'sale_return' => 'Esta devolucion ya tiene una nota de credito emitida.',
                ]);
            }

            if ($lockedSaleReturn->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'sale_return' => 'La devolucion no tiene items para emitir una nota de credito.',
                ]);
            }

            $sale = $lockedSaleReturn->sale;
            $invoice = $sale?->invoice;

            if ($sale === null || ! $invoice instanceof Invoice) {
                throw ValidationException::withMessages([
                    'sale_return' => 'Solo las devoluciones de ventas facturadas pueden emitir nota de credito.',
                ]);
            }

            /** @var Invoice $lockedInvoice */
            $lockedInvoice = Invoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedInvoice->loadMissing('taxResolution');

            $sequenceNumber = CreditNote::query()
                ->where('invoice_id', $lockedInvoice->id)
                ->lockForUpdate()
                ->count() + 1;

            $creditNote = CreditNote::query()->create([
                'public_id' => (string) Str::uuid(),
                'sale_return_id' => $lockedSaleReturn->id,
                'sale_id' => $sale->id,
                'invoice_id' => $lockedInvoice->id,
                'tax_resolution_id' => $lockedInvoice->tax_resolution_id,
                'user_id' => $user->id,
                'status' => 'issued',
                'sequence_number' => $sequenceNumber,
                'credit_note_number' => $this->formatCreditNoteNumber($lockedInvoice, $sequenceNumber),
                'invoice_number_reference' => $lockedInvoice->invoice_number,
                'authorization_number_snapshot' => $lockedInvoice->authorization_number_snapshot,
                'company_name_snapshot' => $lockedInvoice->company_name_snapshot,
                'company_tax_id_snapshot' => $lockedInvoice->company_tax_id_snapshot,
                'customer_name_snapshot' => $lockedInvoice->customer_name_snapshot,
                'customer_document_type_snapshot' => $lockedInvoice->customer_document_type_snapshot,
                'customer_document_number_snapshot' => $lockedInvoice->customer_document_number_snapshot,
                'reason' => $lockedSaleReturn->reason,
                'subtotal' => $lockedSaleReturn->subtotal,
                'tax_total' => $lockedSaleReturn->tax_total,
                'grand_total' => $lockedSaleReturn->refund_total,
                'currency_code' => $lockedInvoice->currency_code,
                'footer' => $lockedInvoice->footer,
                'legend' => $lockedInvoice->legend,
                'issued_at' => now(),
                'metadata' => [
                    'sale_public_id' => $sale->public_id,
                    'sale_return_public_id' => $lockedSaleReturn->public_id,
                    'invoice_public_id' => $lockedInvoice->public_id,
                    'company_phone' => $lockedInvoice->metadata['company_phone'] ?? null,
                    'company_address_line' => $lockedInvoice->metadata['company_address_line'] ?? null,
                    'company_city' => $lockedInvoice->metadata['company_city'] ?? null,
                    'company_region' => $lockedInvoice->metadata['company_region'] ?? null,
                    'billing_owner_name' => $lockedInvoice->metadata['billing_owner_name'] ?? null,
                    'billing_address_reference' => $lockedInvoice->metadata['billing_address_reference'] ?? null,
                ],
            ]);

            $lockedSaleReturn->items->each(function (SaleReturnItem $item) use ($creditNote): void {
                CreditNoteItem::query()->create([
                    'credit_note_id' => $creditNote->id,
                    'sale_return_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'description' => $item->name_snapshot,
                    'sku_snapshot' => $item->sku_snapshot,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_subtotal' => $item->line_subtotal,
                    'line_tax' => $item->line_tax,
                    'line_total' => $item->line_total,
                    'reason' => $item->reason,
                ]);
            });

            $metadata = is_array($lockedSaleReturn->metadata) ? $lockedSaleReturn->metadata : [];
            $metadata['credit_note_pending'] = false;
            $metadata['credit_note_issued_at'] = now()->toIso8601String();
            $metadata['credit_note_number'] = $creditNote->credit_note_number;

            $lockedSaleReturn->forceFill([
                'metadata' => $metadata,
            ])->save();

            return $this->loadCreditNoteRelations($creditNote);
        });
    }

    private function loadCreditNoteRelations(CreditNote $creditNote): CreditNote
    {
        return $creditNote->load([
            'user',
            'taxResolution',
            'items',
        ]);
    }

    private function formatCreditNoteNumber(Invoice $invoice, int $sequenceNumber): string
    {
        return sprintf('NC-%s-%02d', $invoice->invoice_number, $sequenceNumber);
    }
}
