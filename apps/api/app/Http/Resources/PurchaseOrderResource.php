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
        $returnedTotal = round((float) ($this->returned_total ?? $this->returns?->sum('return_total') ?? 0), 2);
        $paidTotal = round((float) ($this->paid_total ?? $this->payments?->sum('amount') ?? 0), 2);
        $netPayableTotal = round(max(0, (float) $this->grand_total - $returnedTotal), 2);
        $balanceDue = round($netPayableTotal - $paidTotal, 2);

        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'status' => $this->status,
            'reference' => $this->reference,
            'subtotal' => (float) $this->subtotal,
            'tax_total' => (float) $this->tax_total,
            'grand_total' => (float) $this->grand_total,
            'paid_total' => $paidTotal,
            'net_payable_total' => $netPayableTotal,
            'balance_due' => $balanceDue,
            'payment_status' => $this->resolvePaymentStatus($paidTotal, $balanceDue),
            'notes' => $this->notes,
            'cancellation_reason' => $this->cancellation_reason,
            'ordered_at' => $this->ordered_at?->toIso8601String(),
            'received_at' => $this->received_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'items_count' => (int) ($this->items_count ?? $this->items?->count() ?? 0),
            'returns_count' => (int) ($this->returns_count ?? $this->returns?->count() ?? 0),
            'payments_count' => (int) ($this->payments_count ?? $this->payments?->count() ?? 0),
            'returned_total' => $returnedTotal,
            'supplier' => $this->whenLoaded('supplier', fn () => new SupplierResource($this->supplier)),
            'created_by' => $this->whenLoaded('createdBy', fn () => new UserResource($this->createdBy)),
            'received_by' => $this->whenLoaded('receivedBy', fn () => new UserResource($this->receivedBy)),
            'cancelled_by' => $this->whenLoaded('cancelledBy', fn () => new UserResource($this->cancelledBy)),
            'items' => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
            'payments' => PurchaseOrderPaymentResource::collection($this->whenLoaded('payments')),
            'returns' => PurchaseReturnResource::collection($this->whenLoaded('returns')),
        ];
    }

    private function resolvePaymentStatus(float $paidTotal, float $balanceDue): string
    {
        if ($balanceDue < 0) {
            return 'credit';
        }

        if ($balanceDue === 0.0) {
            return 'paid';
        }

        if ($paidTotal > 0) {
            return 'partial';
        }

        return 'pending';
    }
}
