<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleReturnItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale_item_id' => $this->sale_item_id,
            'product_id' => $this->product_id,
            'name_snapshot' => $this->name_snapshot,
            'sku_snapshot' => $this->sku_snapshot,
            'quantity' => (float) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'unit_cost' => $this->unit_cost !== null ? (float) $this->unit_cost : null,
            'line_subtotal' => (float) $this->line_subtotal,
            'line_tax' => (float) $this->line_tax,
            'line_total' => (float) $this->line_total,
            'reason' => $this->reason,
        ];
    }
}
