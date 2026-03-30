<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $quantityReceived = (float) $this->quantity_received;
        $returnedQuantity = round((float) ($this->returned_quantity ?? 0), 2);

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'name_snapshot' => $this->name_snapshot,
            'sku_snapshot' => $this->sku_snapshot,
            'quantity_ordered' => (float) $this->quantity_ordered,
            'quantity_received' => $quantityReceived,
            'returned_quantity' => $returnedQuantity,
            'remaining_returnable_quantity' => round(max(0, $quantityReceived - $returnedQuantity), 2),
            'unit_cost' => (float) $this->unit_cost,
            'line_total' => (float) $this->line_total,
            'notes' => $this->notes,
            'product' => $this->whenLoaded('product', fn () => new ProductResource($this->product)),
        ];
    }
}
