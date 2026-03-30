<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderPaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'method' => $this->method,
            'amount' => (float) $this->amount,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'paid_by' => $this->whenLoaded('user', function (): ?array {
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
        ];
    }
}
