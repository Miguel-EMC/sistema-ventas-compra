<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashSessionResource extends JsonResource
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
            'opening_amount' => (float) $this->opening_amount,
            'closing_amount' => $this->closing_amount !== null ? (float) $this->closing_amount : null,
            'notes' => $this->notes,
            'opened_at' => $this->opened_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'sales_total' => (float) ($this->sales_total ?? 0),
            'sales_count' => (int) ($this->sales_count ?? 0),
            'cash_income_total' => (float) ($this->cash_income_total ?? 0),
            'cash_balance' => round(
                (float) $this->opening_amount
                + (float) ($this->cash_income_total ?? 0)
                - (float) ($this->cash_out_total ?? 0),
                2,
            ),
            'register' => $this->whenLoaded('register', fn () => new CashRegisterResource($this->register)),
            'opened_by' => $this->whenLoaded('openedBy', function (): ?array {
                if ($this->openedBy === null) {
                    return null;
                }

                return [
                    'id' => $this->openedBy->id,
                    'name' => $this->openedBy->display_name ?: $this->openedBy->name,
                ];
            }),
            'closed_by' => $this->whenLoaded('closedBy', function (): ?array {
                if ($this->closedBy === null) {
                    return null;
                }

                return [
                    'id' => $this->closedBy->id,
                    'name' => $this->closedBy->display_name ?: $this->closedBy->name,
                ];
            }),
        ];
    }
}
