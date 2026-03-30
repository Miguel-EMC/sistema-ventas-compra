<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditNoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'sale_return_id' => $this->sale_return_id,
            'sale_id' => $this->sale_id,
            'invoice_id' => $this->invoice_id,
            'status' => $this->status,
            'sequence_number' => (int) $this->sequence_number,
            'credit_note_number' => $this->credit_note_number,
            'invoice_number_reference' => $this->invoice_number_reference,
            'authorization_number' => $this->authorization_number_snapshot,
            'company_name' => $this->company_name_snapshot,
            'company_tax_id' => $this->company_tax_id_snapshot,
            'customer_name' => $this->customer_name_snapshot,
            'customer_document_type' => $this->customer_document_type_snapshot,
            'customer_document_number' => $this->customer_document_number_snapshot,
            'reason' => $this->reason,
            'subtotal' => (float) $this->subtotal,
            'tax_total' => (float) $this->tax_total,
            'grand_total' => (float) $this->grand_total,
            'currency_code' => $this->currency_code,
            'footer' => $this->footer,
            'legend' => $this->legend,
            'issued_at' => $this->issued_at?->toIso8601String(),
            'issued_by' => $this->whenLoaded('user', function (): ?array {
                if ($this->user === null) {
                    return null;
                }

                return [
                    'id' => $this->user->id,
                    'name' => $this->user->display_name ?: $this->user->name,
                    'username' => $this->user->username,
                ];
            }),
            'tax_resolution' => $this->whenLoaded(
                'taxResolution',
                fn () => new TaxResolutionResource($this->taxResolution),
            ),
            'items' => CreditNoteItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
