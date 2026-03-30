<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $paidTotal = $this->resolvePaidTotal();
        $returnedTotal = round((float) ($this->returned_total ?? $this->returns?->sum('refund_total') ?? 0), 2);
        $netReceivableTotal = round(max(0, (float) $this->grand_total - $returnedTotal), 2);
        $balanceDue = round($netReceivableTotal - $paidTotal, 2);

        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'status' => $this->status,
            'document_type' => $this->document_type,
            'subtotal' => (float) $this->subtotal,
            'tax_total' => (float) $this->tax_total,
            'grand_total' => (float) $this->grand_total,
            'paid_total' => $paidTotal,
            'net_receivable_total' => $netReceivableTotal,
            'balance_due' => $balanceDue,
            'payment_status' => $this->resolvePaymentStatus($paidTotal, $balanceDue),
            'change_total' => (float) $this->change_total,
            'notes' => $this->notes,
            'cancellation_reason' => $this->cancellation_reason,
            'sold_at' => $this->sold_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'items_count' => (int) ($this->items_count ?? $this->items?->count() ?? 0),
            'returns_count' => (int) ($this->returns_count ?? $this->returns?->count() ?? 0),
            'payments_count' => (int) ($this->payments_count ?? $this->payments?->count() ?? 0),
            'returned_total' => $returnedTotal,
            'payment_methods' => $this->whenLoaded(
                'payments',
                fn () => $this->payments->pluck('method')->unique()->values()->all(),
                [],
            ),
            'cancelled_by' => $this->whenLoaded('cancelledBy', function (): ?array {
                if ($this->cancelledBy === null) {
                    return null;
                }

                return [
                    'id' => $this->cancelledBy->id,
                    'name' => $this->cancelledBy->display_name ?: $this->cancelledBy->name,
                ];
            }),
            'customer' => $this->whenLoaded('customer', function (): ?array {
                if ($this->customer === null) {
                    return null;
                }

                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'document_number' => $this->customer->document_number,
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
            'invoice' => $this->whenLoaded('invoice', function () {
                if ($this->invoice === null) {
                    return null;
                }

                return (new InvoiceResource($this->invoice))->resolve();
            }),
            'payments' => SalePaymentResource::collection($this->whenLoaded('payments')),
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
            'returns' => SaleReturnResource::collection($this->whenLoaded('returns')),
        ];
    }

    private function resolvePaidTotal(): float
    {
        if ($this->relationLoaded('payments')) {
            return round((float) $this->payments->sum('amount'), 2);
        }

        return round((float) $this->paid_total, 2);
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
