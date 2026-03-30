<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'status' => $this->status,
            'sequence_number' => (int) $this->sequence_number,
            'invoice_number' => $this->invoice_number,
            'authorization_number' => $this->authorization_number_snapshot,
            'company_name' => $this->company_name_snapshot,
            'company_tax_id' => $this->company_tax_id_snapshot,
            'customer_name' => $this->customer_name_snapshot,
            'customer_document_type' => $this->customer_document_type_snapshot,
            'customer_document_number' => $this->customer_document_number_snapshot,
            'subtotal' => (float) $this->subtotal,
            'tax_total' => (float) $this->tax_total,
            'grand_total' => (float) $this->grand_total,
            'currency_code' => $this->currency_code,
            'footer' => $this->footer,
            'legend' => $this->legend,
            'issued_at' => $this->issued_at?->toIso8601String(),
            'tax_resolution' => $this->whenLoaded(
                'taxResolution',
                fn () => new TaxResolutionResource($this->taxResolution),
            ),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
