<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
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
            'reference' => $this->reference,
            'subtotal' => (float) $this->subtotal,
            'tax_total' => (float) $this->tax_total,
            'grand_total' => (float) $this->grand_total,
            'notes' => $this->notes,
            'ordered_at' => $this->ordered_at?->toIso8601String(),
            'received_at' => $this->received_at?->toIso8601String(),
            'items_count' => (int) ($this->items_count ?? $this->items?->count() ?? 0),
            'supplier' => $this->whenLoaded('supplier', fn () => new SupplierResource($this->supplier)),
            'created_by' => $this->whenLoaded('createdBy', fn () => new UserResource($this->createdBy)),
            'received_by' => $this->whenLoaded('receivedBy', fn () => new UserResource($this->receivedBy)),
            'items' => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
