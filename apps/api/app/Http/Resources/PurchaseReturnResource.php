<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReturnResource extends JsonResource
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
            'subtotal' => (float) $this->subtotal,
            'tax_total' => (float) $this->tax_total,
            'return_total' => (float) $this->return_total,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'returned_at' => $this->returned_at?->toIso8601String(),
            'returned_by' => $this->whenLoaded('user', function (): ?array {
                if ($this->user === null) {
                    return null;
                }

                return [
                    'id' => $this->user->id,
                    'name' => $this->user->display_name ?: $this->user->name,
                    'username' => $this->user->username,
                ];
            }),
            'items' => PurchaseReturnItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
