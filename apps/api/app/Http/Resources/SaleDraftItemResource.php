<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleDraftItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'name_snapshot' => $this->name_snapshot,
            'unit_price' => (float) $this->unit_price,
            'quantity' => (float) $this->quantity,
            'line_total' => (float) $this->line_total,
            'product' => $this->whenLoaded('product', function (): ?array {
                if ($this->product === null) {
                    return null;
                }

                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'unit' => $this->product->unit,
                    'track_stock' => (bool) $this->product->track_stock,
                    'current_stock' => (float) ($this->product->current_stock ?? 0),
                ];
            }),
        ];
    }
}
