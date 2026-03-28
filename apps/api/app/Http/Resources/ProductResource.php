<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'description' => $this->description,
            'sale_price' => (float) $this->sale_price,
            'cost_price' => (float) $this->cost_price,
            'tax_rate' => (float) $this->tax_rate,
            'unit' => $this->unit,
            'track_stock' => (bool) $this->track_stock,
            'minimum_stock' => (float) $this->minimum_stock,
            'current_stock' => (float) ($this->current_stock ?? 0),
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'category' => $this->whenLoaded('category', fn () => new ProductCategoryResource($this->category)),
        ];
    }
}
