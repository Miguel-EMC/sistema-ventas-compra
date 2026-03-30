<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleReturnResource extends JsonResource
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
            'refund_method' => $this->refund_method,
            'subtotal' => (float) $this->subtotal,
            'tax_total' => (float) $this->tax_total,
            'refund_total' => (float) $this->refund_total,
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
            'cash_session' => $this->whenLoaded('cashSession', function (): ?array {
                if ($this->cashSession === null) {
                    return null;
                }

                return [
                    'id' => $this->cashSession->id,
                    'register_name' => $this->cashSession->register?->name,
                ];
            }),
            'credit_note' => $this->whenLoaded(
                'creditNote',
                fn () => $this->creditNote ? (new CreditNoteResource($this->creditNote))->resolve() : null,
            ),
            'items' => SaleReturnItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
