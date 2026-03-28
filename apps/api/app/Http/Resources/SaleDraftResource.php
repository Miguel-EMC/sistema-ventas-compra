<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleDraftResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $items = $this->relationLoaded('items') ? $this->items : collect();
        $subtotal = round((float) $items->sum(fn ($item) => (float) $item->line_total), 2);
        $taxTotal = round((float) $items->sum(function ($item): float {
            $rate = (float) ($item->product?->tax_rate ?? 0);

            return ((float) $item->line_total) * ($rate / 100);
        }), 2);

        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'status' => $this->status,
            'channel' => $this->channel,
            'notes' => $this->notes,
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
            'items' => SaleDraftItemResource::collection($items),
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'grand_total' => round($subtotal + $taxTotal, 2),
            'total_items' => round((float) $items->sum(fn ($item) => (float) $item->quantity), 2),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
