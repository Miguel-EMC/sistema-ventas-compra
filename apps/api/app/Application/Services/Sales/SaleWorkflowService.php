<?php

namespace App\Application\Services\Sales;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDraft;
use App\Models\SaleDraftItem;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleWorkflowService
{
    public function getCurrentDraft(User $user): SaleDraft
    {
        $draft = SaleDraft::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'status' => 'draft',
            ],
            [
                'public_id' => (string) Str::uuid(),
                'channel' => 'pos',
            ],
        );

        return $this->loadDraftRelations($draft);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function updateDraft(User $user, array $payload): SaleDraft
    {
        $draft = $this->getCurrentDraft($user);

        $draft->update([
            'customer_id' => $payload['customer_id'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ]);

        return $this->loadDraftRelations($draft->fresh());
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function addItem(User $user, array $payload): SaleDraft
    {
        return DB::transaction(function () use ($user, $payload): SaleDraft {
            $draft = $this->getCurrentDraft($user);

            /** @var Product $product */
            $product = Product::query()
                ->withSum('stockMovements as current_stock', 'quantity')
                ->findOrFail($payload['product_id']);

            if (! $product->is_active) {
                throw ValidationException::withMessages([
                    'product_id' => 'Solo se pueden agregar productos activos al borrador.',
                ]);
            }

            $quantity = (float) $payload['quantity'];

            $item = SaleDraftItem::query()
                ->where('sale_draft_id', $draft->id)
                ->where('product_id', $product->id)
                ->first();

            if ($item instanceof SaleDraftItem) {
                $nextQuantity = (float) $item->quantity + $quantity;

                $item->update([
                    'quantity' => $nextQuantity,
                    'line_total' => round(((float) $product->sale_price) * $nextQuantity, 2),
                ]);
            } else {
                SaleDraftItem::query()->create([
                    'sale_draft_id' => $draft->id,
                    'product_id' => $product->id,
                    'name_snapshot' => $product->name,
                    'unit_price' => $product->sale_price,
                    'quantity' => $quantity,
                    'line_total' => round(((float) $product->sale_price) * $quantity, 2),
                ]);
            }

            return $this->loadDraftRelations($draft->fresh());
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function updateItem(SaleDraftItem $item, array $payload): SaleDraft
    {
        $product = $item->product()->first();

        if (! $product instanceof Product) {
            throw ValidationException::withMessages([
                'item' => 'El producto asociado al item ya no existe.',
            ]);
        }

        $quantity = (float) $payload['quantity'];

        $item->update([
            'quantity' => $quantity,
            'line_total' => round(((float) $product->sale_price) * $quantity, 2),
        ]);

        return $this->loadDraftRelations($item->draft()->firstOrFail());
    }

    public function removeItem(SaleDraftItem $item): SaleDraft
    {
        $draft = $item->draft()->firstOrFail();
        $item->delete();

        return $this->loadDraftRelations($draft->fresh());
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function checkout(User $user, array $payload): Sale
    {
        return DB::transaction(function () use ($user, $payload): Sale {
            $draft = $this->getCurrentDraft($user);

            if ($draft->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'draft' => 'El borrador no tiene items para procesar.',
                ]);
            }

            $products = Product::query()
                ->withSum('stockMovements as current_stock', 'quantity')
                ->whereKey($draft->items->pluck('product_id')->filter()->all())
                ->get()
                ->keyBy('id');

            $lineRows = [];
            $subtotal = 0.0;
            $taxTotal = 0.0;

            foreach ($draft->items as $item) {
                $product = $products->get($item->product_id);

                if (! $product instanceof Product) {
                    throw ValidationException::withMessages([
                        'draft' => "El producto '{$item->name_snapshot}' ya no esta disponible.",
                    ]);
                }

                $quantity = (float) $item->quantity;

                if ($product->track_stock && (float) ($product->current_stock ?? 0) < $quantity) {
                    throw ValidationException::withMessages([
                        'draft' => "No hay stock suficiente para '{$product->name}'.",
                    ]);
                }

                $lineSubtotal = round(((float) $item->unit_price) * $quantity, 2);
                $lineTax = round($lineSubtotal * (((float) $product->tax_rate) / 100), 2);
                $lineTotal = round($lineSubtotal + $lineTax, 2);

                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;

                $lineRows[] = [
                    'draft_item' => $item,
                    'product' => $product,
                    'line_subtotal' => $lineSubtotal,
                    'line_tax' => $lineTax,
                    'line_total' => $lineTotal,
                ];
            }

            $subtotal = round($subtotal, 2);
            $taxTotal = round($taxTotal, 2);
            $grandTotal = round($subtotal + $taxTotal, 2);
            $amountPaid = round((float) $payload['amount_paid'], 2);

            if ($amountPaid < $grandTotal) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'El monto pagado no cubre el total de la venta.',
                ]);
            }

            $sale = Sale::query()->create([
                'public_id' => (string) Str::uuid(),
                'customer_id' => $draft->customer_id,
                'user_id' => $user->id,
                'status' => 'completed',
                'document_type' => $payload['document_type'] ?? null,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => 0,
                'grand_total' => $grandTotal,
                'paid_total' => $amountPaid,
                'change_total' => round($amountPaid - $grandTotal, 2),
                'notes' => $payload['notes'] ?? $draft->notes,
                'sold_at' => now(),
            ]);

            foreach ($lineRows as $line) {
                /** @var Product $product */
                $product = $line['product'];
                /** @var SaleDraftItem $draftItem */
                $draftItem = $line['draft_item'];

                SaleItem::query()->create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'name_snapshot' => $draftItem->name_snapshot,
                    'sku_snapshot' => $product->sku,
                    'unit_price' => $draftItem->unit_price,
                    'unit_cost' => $product->cost_price,
                    'quantity' => $draftItem->quantity,
                    'line_subtotal' => $line['line_subtotal'],
                    'line_tax' => $line['line_tax'],
                    'line_total' => $line['line_total'],
                ]);

                if ($product->track_stock) {
                    StockMovement::query()->create([
                        'product_id' => $product->id,
                        'user_id' => $user->id,
                        'type' => 'sale_out',
                        'reason' => 'sale_checkout',
                        'reference_type' => 'sale',
                        'reference_id' => $sale->id,
                        'quantity' => -1 * (float) $draftItem->quantity,
                        'unit_cost' => $product->cost_price,
                        'notes' => "Salida por venta {$sale->public_id}",
                        'occurred_at' => now(),
                    ]);
                }
            }

            SalePayment::query()->create([
                'sale_id' => $sale->id,
                'method' => $payload['payment_method'],
                'amount' => $amountPaid,
                'reference' => $payload['reference'] ?? null,
                'paid_at' => now(),
            ]);

            $draft->update([
                'status' => 'checked_out',
                'notes' => $payload['notes'] ?? $draft->notes,
                'expires_at' => now(),
            ]);

            return $sale->load('customer', 'payments')->loadCount('items');
        });
    }

    /**
     * @return Collection<int, Sale>
     */
    public function recentSales(): Collection
    {
        return Sale::query()
            ->with('customer', 'payments')
            ->withCount('items')
            ->orderByDesc('sold_at')
            ->limit(15)
            ->get();
    }

    private function loadDraftRelations(SaleDraft $draft): SaleDraft
    {
        return $draft->load([
            'customer',
            'items.product' => fn ($query) => $query->withSum('stockMovements as current_stock', 'quantity'),
        ]);
    }
}
