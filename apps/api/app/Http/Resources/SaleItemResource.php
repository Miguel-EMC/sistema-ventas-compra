<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $quantity = (float) $this->quantity;
        $returnedQuantity = round((float) ($this->returned_quantity ?? 0), 2);

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'name_snapshot' => $this->name_snapshot,
            'sku_snapshot' => $this->sku_snapshot,
            'unit_price' => (float) $this->unit_price,
            'unit_cost' => $this->unit_cost !== null ? (float) $this->unit_cost : null,
            'quantity' => $quantity,
            'returned_quantity' => $returnedQuantity,
            'remaining_quantity' => round(max(0, $quantity - $returnedQuantity), 2),
            'line_subtotal' => (float) $this->line_subtotal,
            'line_tax' => (float) $this->line_tax,
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
