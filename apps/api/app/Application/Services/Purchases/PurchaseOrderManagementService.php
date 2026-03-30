<?php

namespace App\Application\Services\Purchases;

use App\Application\Services\Cash\CashSessionService;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderPayment;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
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
    public function __construct(
        private readonly CashSessionService $cashSessionService,
    ) {
    }

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
            ->withCount(['items', 'returns', 'payments'])
            ->withSum('returns as returned_total', 'return_total')
            ->withSum('payments as paid_total', 'amount')
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

    /**
     * @param array<string, mixed> $payload
     */
    public function cancel(PurchaseOrder $purchaseOrder, array $payload, User $user): PurchaseOrder
    {
        if ($purchaseOrder->status === 'cancelled') {
            throw ValidationException::withMessages([
                'purchase_order' => 'La orden ya fue anulada anteriormente.',
            ]);
        }

        return DB::transaction(function () use ($purchaseOrder, $payload, $user): PurchaseOrder {
            $purchaseOrder = $this->loadRelations($purchaseOrder);
            $this->guardPurchaseHasNoPayments($purchaseOrder);

            if ($purchaseOrder->status === 'received') {
                $this->guardStockForCancellation($purchaseOrder);

                foreach ($purchaseOrder->items as $item) {
                    $quantityToReverse = round((float) $item->quantity_received - (float) ($item->returned_quantity ?? 0), 2);

                    if ($quantityToReverse <= 0) {
                        continue;
                    }

                    $product = $item->product;

                    if ($product instanceof Product && $product->track_stock) {
                        StockMovement::query()->create([
                            'product_id' => $product->id,
                            'user_id' => $user->id,
                            'type' => 'purchase_return',
                            'reason' => 'purchase_cancellation',
                            'reference_type' => 'purchase_order',
                            'reference_id' => $purchaseOrder->id,
                            'quantity' => -1 * $quantityToReverse,
                            'unit_cost' => $item->unit_cost,
                            'notes' => "Salida por anulacion compra {$purchaseOrder->public_id}",
                            'occurred_at' => now(),
                        ]);
                    }
                }
            }

            $purchaseOrder->update([
                'status' => 'cancelled',
                'cancelled_by_id' => $user->id,
                'cancellation_reason' => $this->nullableText($payload['cancellation_reason'] ?? null),
                'cancelled_at' => now(),
            ]);

            return $this->loadRelations($purchaseOrder);
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function registerPayment(PurchaseOrder $purchaseOrder, array $payload, User $user): PurchaseOrderPayment
    {
        if ($purchaseOrder->status !== 'received') {
            throw ValidationException::withMessages([
                'purchase_order' => 'Solo puedes registrar pagos sobre compras recibidas.',
            ]);
        }

        return DB::transaction(function () use ($purchaseOrder, $payload, $user): PurchaseOrderPayment {
            $purchaseOrder = $this->loadRelations($purchaseOrder);
            $summary = $this->paymentSummary($purchaseOrder);

            if ($summary['balance_due'] <= 0) {
                throw ValidationException::withMessages([
                    'purchase_order' => 'La compra ya no tiene saldo pendiente por pagar.',
                ]);
            }

            $amount = round((float) ($payload['amount'] ?? 0), 2);

            if ($amount > $summary['balance_due']) {
                throw ValidationException::withMessages([
                    'amount' => "Solo puedes registrar hasta {$summary['balance_due']} en esta compra.",
                ]);
            }

            $method = trim((string) ($payload['method'] ?? ''));
            $paidAt = $this->parseDateTime($payload['paid_at'] ?? null) ?? now();
            $cashSession = $method === 'cash'
                ? $this->cashSessionService->getCurrentSession($user)
                : null;

            if ($method === 'cash' && ! $cashSession instanceof CashSession) {
                throw ValidationException::withMessages([
                    'cash_session' => 'Debes abrir una caja para registrar pagos en efectivo.',
                ]);
            }

            $payment = PurchaseOrderPayment::query()->create([
                'public_id' => (string) Str::uuid(),
                'purchase_order_id' => $purchaseOrder->id,
                'cash_session_id' => $cashSession?->id,
                'user_id' => $user->id,
                'method' => $method,
                'amount' => $amount,
                'reference' => $this->nullableText($payload['reference'] ?? null),
                'notes' => $this->nullableText($payload['notes'] ?? null),
                'paid_at' => $paidAt,
                'metadata' => [
                    'balance_due_before' => $summary['balance_due'],
                    'balance_due_after' => round($summary['balance_due'] - $amount, 2),
                ],
            ]);

            if ($cashSession instanceof CashSession) {
                CashMovement::query()->create([
                    'public_id' => (string) Str::uuid(),
                    'cash_session_id' => $cashSession->id,
                    'user_id' => $user->id,
                    'type' => 'expense',
                    'category' => 'purchase_payment',
                    'amount' => $amount,
                    'reference_type' => 'purchase_order_payment',
                    'reference_id' => $payment->id,
                    'notes' => "Pago compra {$purchaseOrder->public_id}",
                    'occurred_at' => $paidAt,
                ]);
            }

            return $this->loadPaymentRelations($payment);
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function registerReturn(PurchaseOrder $purchaseOrder, array $payload, User $user): PurchaseReturn
    {
        if ($purchaseOrder->status !== 'received') {
            throw ValidationException::withMessages([
                'purchase_order' => 'Solo puedes devolver compras que ya fueron recibidas.',
            ]);
        }

        return DB::transaction(function () use ($purchaseOrder, $payload, $user): PurchaseReturn {
            $purchaseOrder = $this->loadRelations($purchaseOrder);

            if ($purchaseOrder->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'purchase_order' => 'La orden no tiene items para devolver.',
                ]);
            }

            $returnedQuantities = PurchaseReturnItem::query()
                ->selectRaw('purchase_order_item_id, COALESCE(SUM(quantity), 0) as aggregate_quantity')
                ->whereIn('purchase_order_item_id', $purchaseOrder->items->pluck('id')->all())
                ->groupBy('purchase_order_item_id')
                ->pluck('aggregate_quantity', 'purchase_order_item_id');

            $orderItems = $purchaseOrder->items->keyBy('id');
            $preparedItems = [];
            $subtotal = 0.0;
            $returnedAt = $this->parseDateTime($payload['returned_at'] ?? null) ?? now();

            foreach (($payload['items'] ?? []) as $index => $itemPayload) {
                /** @var PurchaseOrderItem|null $orderItem */
                $orderItem = $orderItems->get((int) ($itemPayload['purchase_order_item_id'] ?? 0));

                if (! $orderItem instanceof PurchaseOrderItem) {
                    throw ValidationException::withMessages([
                        "items.{$index}.purchase_order_item_id" => 'El item seleccionado no pertenece a la orden.',
                    ]);
                }

                $requestedQuantity = round((float) ($itemPayload['quantity'] ?? 0), 2);
                $alreadyReturned = round((float) ($returnedQuantities[$orderItem->id] ?? 0), 2);
                $remainingQuantity = round((float) $orderItem->quantity_received - $alreadyReturned, 2);

                if ($requestedQuantity > $remainingQuantity) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => "Solo puedes devolver hasta {$remainingQuantity} unidad(es) de '{$orderItem->name_snapshot}'.",
                    ]);
                }

                $product = $orderItem->product;

                if (
                    $product instanceof Product
                    && $product->track_stock
                    && (float) ($product->current_stock ?? 0) < $requestedQuantity
                ) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => "No hay stock suficiente para devolver '{$orderItem->name_snapshot}'.",
                    ]);
                }

                $lineTotal = round($requestedQuantity * (float) $orderItem->unit_cost, 2);
                $subtotal += $lineTotal;

                $preparedItems[] = [
                    'purchase_order_item' => $orderItem,
                    'product' => $product,
                    'quantity' => $requestedQuantity,
                    'line_total' => $lineTotal,
                    'reason' => $this->nullableText($itemPayload['reason'] ?? null),
                ];
            }

            if ($preparedItems === []) {
                throw ValidationException::withMessages([
                    'items' => 'Debes registrar al menos un item para devolver.',
                ]);
            }

            $subtotal = round($subtotal, 2);

            $purchaseReturn = PurchaseReturn::query()->create([
                'public_id' => (string) Str::uuid(),
                'purchase_order_id' => $purchaseOrder->id,
                'user_id' => $user->id,
                'status' => 'completed',
                'subtotal' => $subtotal,
                'tax_total' => 0,
                'return_total' => $subtotal,
                'reason' => $payload['reason'],
                'notes' => $this->nullableText($payload['notes'] ?? null),
                'returned_at' => $returnedAt,
                'metadata' => [
                    'supplier_id' => $purchaseOrder->supplier_id,
                    'purchase_order_status' => $purchaseOrder->status,
                ],
            ]);

            foreach ($preparedItems as $preparedItem) {
                /** @var PurchaseOrderItem $orderItem */
                $orderItem = $preparedItem['purchase_order_item'];
                /** @var Product|null $product */
                $product = $preparedItem['product'];

                PurchaseReturnItem::query()->create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'purchase_order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'name_snapshot' => $orderItem->name_snapshot,
                    'sku_snapshot' => $orderItem->sku_snapshot,
                    'quantity' => $preparedItem['quantity'],
                    'unit_cost' => $orderItem->unit_cost,
                    'line_total' => $preparedItem['line_total'],
                    'reason' => $preparedItem['reason'],
                ]);

                if ($product instanceof Product && $product->track_stock) {
                    StockMovement::query()->create([
                        'product_id' => $product->id,
                        'user_id' => $user->id,
                        'type' => 'purchase_return',
                        'reason' => 'purchase_partial_return',
                        'reference_type' => 'purchase_return',
                        'reference_id' => $purchaseReturn->id,
                        'quantity' => -1 * (float) $preparedItem['quantity'],
                        'unit_cost' => $orderItem->unit_cost,
                        'notes' => "Salida por devolucion compra {$purchaseOrder->public_id}",
                        'occurred_at' => $returnedAt,
                    ]);
                }
            }

            return $this->loadReturnRelations($purchaseReturn);
        });
    }

    public function loadRelations(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        return $purchaseOrder->load($this->relations())
            ->loadCount(['items', 'returns', 'payments'])
            ->loadSum('returns as returned_total', 'return_total')
            ->loadSum('payments as paid_total', 'amount');
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
            'cancelledBy.role',
            'items' => fn ($query) => $query
                ->with(['product' => fn ($productQuery) => $productQuery->with('category')->withSum('stockMovements as current_stock', 'quantity')])
                ->withSum('returnItems as returned_quantity', 'quantity'),
            'payments' => fn ($query) => $query->with(['user', 'cashSession.register']),
            'returns' => fn ($query) => $query->with(['user', 'items']),
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

    private function guardStockForCancellation(PurchaseOrder $purchaseOrder): void
    {
        foreach ($purchaseOrder->items as $item) {
            $quantityToReverse = round((float) $item->quantity_received - (float) ($item->returned_quantity ?? 0), 2);

            if ($quantityToReverse <= 0) {
                continue;
            }

            $product = $item->product;

            if (
                $product instanceof Product
                && $product->track_stock
                && (float) ($product->current_stock ?? 0) < $quantityToReverse
            ) {
                throw ValidationException::withMessages([
                    'purchase_order' => "No hay stock suficiente para revertir '{$item->name_snapshot}'.",
                ]);
            }
        }
    }

    private function guardPurchaseHasNoPayments(PurchaseOrder $purchaseOrder): void
    {
        $paidTotal = round((float) ($purchaseOrder->paid_total ?? $purchaseOrder->payments?->sum('amount') ?? 0), 2);

        if ($paidTotal <= 0) {
            return;
        }

        throw ValidationException::withMessages([
            'purchase_order' => 'No puedes anular una compra con pagos registrados.',
        ]);
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

    private function loadReturnRelations(PurchaseReturn $purchaseReturn): PurchaseReturn
    {
        return $purchaseReturn->load([
            'user',
            'items',
        ]);
    }

    private function loadPaymentRelations(PurchaseOrderPayment $payment): PurchaseOrderPayment
    {
        return $payment->load([
            'user',
            'cashSession.register',
        ]);
    }

    /**
     * @return array{paid_total: float, returned_total: float, net_payable_total: float, balance_due: float}
     */
    private function paymentSummary(PurchaseOrder $purchaseOrder): array
    {
        $returnedTotal = round((float) ($purchaseOrder->returned_total ?? $purchaseOrder->returns?->sum('return_total') ?? 0), 2);
        $paidTotal = round((float) ($purchaseOrder->paid_total ?? $purchaseOrder->payments?->sum('amount') ?? 0), 2);
        $netPayableTotal = round(max(0, (float) $purchaseOrder->grand_total - $returnedTotal), 2);

        return [
            'paid_total' => $paidTotal,
            'returned_total' => $returnedTotal,
            'net_payable_total' => $netPayableTotal,
            'balance_due' => round($netPayableTotal - $paidTotal, 2),
        ];
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
