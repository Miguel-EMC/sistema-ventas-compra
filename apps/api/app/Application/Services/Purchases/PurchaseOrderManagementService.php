<?php

namespace App\Application\Services\Purchases;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseOrderManagementService
{
    /**
     * @param array<string, mixed> $filters
     * @return Collection<int, PurchaseOrder>
     */
    public function list(array $filters = []): Collection
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        return PurchaseOrder::query()
            ->with($this->relations())
            ->withCount('items')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery
                        ->where('public_id', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($supplierQuery) use ($search): void {
                            $supplierQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('document_number', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('ordered_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload, User $user): PurchaseOrder
    {
        return DB::transaction(function () use ($payload, $user): PurchaseOrder {
            $supplier = $this->resolveSupplier((int) $payload['supplier_id']);
            $prepared = $this->prepareItems($payload['items'] ?? []);

            $purchaseOrder = PurchaseOrder::query()->create([
                'public_id' => (string) Str::uuid(),
                'supplier_id' => $supplier->id,
                'user_id' => $user->id,
                'status' => 'ordered',
                'reference' => $this->nullableText($payload['reference'] ?? null),
                'subtotal' => $prepared['subtotal'],
                'tax_total' => 0,
                'grand_total' => $prepared['subtotal'],
                'notes' => $this->nullableText($payload['notes'] ?? null),
                'ordered_at' => $this->parseDateTime($payload['ordered_at'] ?? null) ?? now(),
            ]);

            $this->persistItems($purchaseOrder, $prepared['items']);

            return $this->loadRelations($purchaseOrder);
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(PurchaseOrder $purchaseOrder, array $payload): PurchaseOrder
    {
        $this->guardEditable($purchaseOrder);

        return DB::transaction(function () use ($purchaseOrder, $payload): PurchaseOrder {
            $supplier = $this->resolveSupplier((int) $payload['supplier_id']);
            $prepared = $this->prepareItems($payload['items'] ?? []);

            $purchaseOrder->update([
                'supplier_id' => $supplier->id,
                'reference' => $this->nullableText($payload['reference'] ?? null),
                'subtotal' => $prepared['subtotal'],
                'tax_total' => 0,
                'grand_total' => $prepared['subtotal'],
                'notes' => $this->nullableText($payload['notes'] ?? null),
                'ordered_at' => $this->parseDateTime($payload['ordered_at'] ?? null) ?? $purchaseOrder->ordered_at ?? now(),
            ]);

            $purchaseOrder->items()->delete();
            $this->persistItems($purchaseOrder, $prepared['items']);

            return $this->loadRelations($purchaseOrder);
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function receive(PurchaseOrder $purchaseOrder, array $payload, User $user): PurchaseOrder
    {
        if ($purchaseOrder->status === 'received') {
            throw ValidationException::withMessages([
                'purchase_order' => 'La orden ya fue recibida anteriormente.',
            ]);
        }

        $purchaseOrder = $this->loadRelations($purchaseOrder);
        $receivedAt = $this->parseDateTime($payload['received_at'] ?? null) ?? now();

        return DB::transaction(function () use ($purchaseOrder, $payload, $user, $receivedAt): PurchaseOrder {
            if ($purchaseOrder->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'purchase_order' => 'La orden no tiene items para recibir.',
                ]);
            }

            foreach ($purchaseOrder->items as $item) {
                $quantityToReceive = round((float) $item->quantity_ordered - (float) $item->quantity_received, 2);

                if ($quantityToReceive <= 0) {
                    continue;
                }

                $product = $item->product;

                if ($product instanceof Product) {
                    $product->update([
                        'cost_price' => $item->unit_cost,
                    ]);

                    if ($product->track_stock) {
                        StockMovement::query()->create([
                            'product_id' => $product->id,
                            'user_id' => $user->id,
                            'type' => 'purchase_in',
                            'reason' => 'purchase_receipt',
                            'reference_type' => 'purchase_order',
                            'reference_id' => $purchaseOrder->id,
                            'quantity' => $quantityToReceive,
                            'unit_cost' => $item->unit_cost,
                            'notes' => "Ingreso por compra {$purchaseOrder->public_id}",
                            'occurred_at' => $receivedAt,
                        ]);
                    }
                }

                $item->update([
                    'quantity_received' => $item->quantity_ordered,
                ]);
            }

            $purchaseOrder->update([
                'status' => 'received',
                'received_by_id' => $user->id,
                'received_at' => $receivedAt,
                'notes' => $this->nullableText($payload['notes'] ?? null) ?? $purchaseOrder->notes,
            ]);

            return $this->loadRelations($purchaseOrder);
        });
    }

    public function delete(PurchaseOrder $purchaseOrder): void
    {
        $this->guardEditable($purchaseOrder);
        $purchaseOrder->delete();
    }

    public function loadRelations(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        return $purchaseOrder->load($this->relations())->loadCount('items');
    }

    /**
     * @return array<int, string|\Closure>
     */
    private function relations(): array
    {
        return [
            'supplier',
            'createdBy.role',
            'receivedBy.role',
            'items.product' => fn ($query) => $query->with('category')->withSum('stockMovements as current_stock', 'quantity'),
        ];
    }

    private function guardEditable(PurchaseOrder $purchaseOrder): void
    {
        if ($purchaseOrder->status !== 'ordered') {
            throw ValidationException::withMessages([
                'purchase_order' => 'Solo puedes modificar o eliminar ordenes pendientes de recepcion.',
            ]);
        }
    }

    private function resolveSupplier(int $supplierId): Supplier
    {
        $supplier = Supplier::query()->find($supplierId);

        if (! $supplier instanceof Supplier || ! $supplier->is_active) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Debes seleccionar un proveedor activo.',
            ]);
        }

        return $supplier;
    }

    /**
     * @param array<int, array<string, mixed>> $itemsPayload
     * @return array{subtotal: float, items: array<int, array<string, mixed>>}
     */
    private function prepareItems(array $itemsPayload): array
    {
        if ($itemsPayload === []) {
            throw ValidationException::withMessages([
                'items' => 'Debes registrar al menos un item en la compra.',
            ]);
        }

        $products = Product::query()
            ->whereKey(collect($itemsPayload)->pluck('product_id')->all())
            ->get()
            ->keyBy('id');

        $preparedItems = [];
        $subtotal = 0.0;

        foreach ($itemsPayload as $index => $itemPayload) {
            $product = $products->get((int) ($itemPayload['product_id'] ?? 0));

            if (! $product instanceof Product || ! $product->is_active) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'El producto seleccionado no esta disponible.',
                ]);
            }

            $quantity = round((float) ($itemPayload['quantity_ordered'] ?? 0), 2);
            $unitCost = round((float) ($itemPayload['unit_cost'] ?? 0), 2);
            $lineTotal = round($quantity * $unitCost, 2);

            $subtotal += $lineTotal;

            $preparedItems[] = [
                'product_id' => $product->id,
                'name_snapshot' => $product->name,
                'sku_snapshot' => $product->sku,
                'quantity_ordered' => $quantity,
                'quantity_received' => 0,
                'unit_cost' => $unitCost,
                'line_total' => $lineTotal,
                'notes' => $this->nullableText($itemPayload['notes'] ?? null),
            ];
        }

        return [
            'subtotal' => round($subtotal, 2),
            'items' => $preparedItems,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function persistItems(PurchaseOrder $purchaseOrder, array $items): void
    {
        foreach ($items as $item) {
            PurchaseOrderItem::query()->create([
                'purchase_order_id' => $purchaseOrder->id,
                ...$item,
            ]);
        }
    }

    private function nullableText(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }

    private function parseDateTime(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse((string) $value);
    }
}
