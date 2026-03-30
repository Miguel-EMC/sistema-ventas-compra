<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxResolutionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $remainingInvoices = max(0, ((int) $this->invoice_number_end) - ((int) $this->next_invoice_number) + 1);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'authorization_number' => $this->authorization_number,
            'series' => $this->series,
            'invoice_number_start' => (int) $this->invoice_number_start,
            'invoice_number_end' => (int) $this->invoice_number_end,
            'next_invoice_number' => (int) $this->next_invoice_number,
            'remaining_invoices' => $remainingInvoices,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'technical_key' => $this->technical_key,
            'legend' => $this->legend,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
