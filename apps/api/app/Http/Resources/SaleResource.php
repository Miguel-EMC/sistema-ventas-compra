<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
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
            'document_type' => $this->document_type,
            'subtotal' => (float) $this->subtotal,
            'tax_total' => (float) $this->tax_total,
            'grand_total' => (float) $this->grand_total,
            'paid_total' => (float) $this->paid_total,
            'change_total' => (float) $this->change_total,
            'notes' => $this->notes,
            'sold_at' => $this->sold_at?->toIso8601String(),
            'items_count' => (int) ($this->items_count ?? $this->items?->count() ?? 0),
            'payment_methods' => $this->whenLoaded(
                'payments',
                fn () => $this->payments->pluck('method')->values()->all(),
                [],
            ),
            'customer' => $this->whenLoaded('customer', function (): ?array {
                if ($this->customer === null) {
                    return null;
                }

                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'document_number' => $this->customer->document_number,
                ];
            }),
            'cash_session' => $this->whenLoaded('cashSession', function (): ?array {
                if ($this->cashSession === null) {
                    return null;
                }

                return [
                    'id' => $this->cashSession->id,
                    'register_name' => $this->cashSession->register?->name,
                ];
            }),
        ];
    }
}
