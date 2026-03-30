<?php

namespace App\Application\Services\Sales;

use App\Application\Services\Billing\InvoiceIssuanceService;
use App\Application\Services\Cash\CashSessionService;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDraft;
use App\Models\SaleDraftItem;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StockMovement;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleWorkflowService
{
    public function __construct(
        private readonly CashSessionService $cashSessionService,
        private readonly InvoiceIssuanceService $invoiceIssuanceService,
    ) {
    }

    public function getCurrentDraft(User $user): SaleDraft
    {
        $activeSession = $this->cashSessionService->getCurrentSession($user);

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

        if ($draft->cash_session_id !== $activeSession?->id) {
            $draft->forceFill([
                'cash_session_id' => $activeSession?->id,
            ])->save();
        }

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
            $activeSession = $this->cashSessionService->getCurrentSession($user);

            if (! $activeSession instanceof CashSession) {
                throw ValidationException::withMessages([
                    'cash_session' => 'Debes abrir una caja antes de confirmar ventas.',
                ]);
            }

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
            $paymentMethod = trim((string) ($payload['payment_method'] ?? 'cash'));
            $tenderedAmount = round((float) $payload['amount_paid'], 2);
            $documentType = $this->resolveDocumentType($payload['document_type'] ?? null);
            $payment = $this->resolveCheckoutPaymentAmounts($paymentMethod, $tenderedAmount, $grandTotal);
            $amountPaid = $payment['applied_amount'];
            $changeTotal = $payment['change_total'];

            $sale = Sale::query()->create([
                'public_id' => (string) Str::uuid(),
                'customer_id' => $draft->customer_id,
                'user_id' => $user->id,
                'cash_session_id' => $activeSession->id,
                'status' => 'completed',
                'document_type' => $documentType,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => 0,
                'grand_total' => $grandTotal,
                'paid_total' => $amountPaid,
                'change_total' => $changeTotal,
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

            if ($amountPaid > 0) {
                SalePayment::query()->create([
                    'public_id' => (string) Str::uuid(),
                    'sale_id' => $sale->id,
                    'cash_session_id' => $paymentMethod === 'cash' ? $activeSession->id : null,
                    'user_id' => $user->id,
                    'method' => $paymentMethod,
                    'amount' => $amountPaid,
                    'reference' => $this->nullableText($payload['reference'] ?? null),
                    'notes' => null,
                    'paid_at' => now(),
                    'metadata' => [
                        'source' => 'sale_checkout',
                        'balance_due_before' => $grandTotal,
                        'balance_due_after' => round(max(0, $grandTotal - $amountPaid), 2),
                        'tendered_amount' => $tenderedAmount,
                        'change_total' => $changeTotal,
                    ],
                ]);
            }

            if ($paymentMethod === 'cash' && $amountPaid > 0) {
                CashMovement::query()->create([
                    'public_id' => (string) Str::uuid(),
                    'cash_session_id' => $activeSession->id,
                    'user_id' => $user->id,
                    'type' => 'income',
                    'category' => 'sale',
                    'amount' => $amountPaid,
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'notes' => "Ingreso por venta {$sale->public_id}",
                    'occurred_at' => now(),
                ]);
            }

            if ($documentType === 'factura') {
                $this->invoiceIssuanceService->issueForSale($sale);
            }

            $draft->update([
                'status' => 'checked_out',
                'notes' => $payload['notes'] ?? $draft->notes,
                'expires_at' => now(),
            ]);

            return $this->loadSaleRelations($sale);
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function registerPayment(User $user, Sale $sale, array $payload): SalePayment
    {
        if ($sale->status !== 'completed') {
            throw ValidationException::withMessages([
                'sale' => 'Solo puedes registrar cobros sobre ventas completadas.',
            ]);
        }

        return DB::transaction(function () use ($user, $sale, $payload): SalePayment {
            $sale = $this->loadSaleRelations($sale);
            $summary = $this->paymentSummary($sale);

            if ($summary['balance_due'] <= 0) {
                throw ValidationException::withMessages([
                    'sale' => 'La venta ya no tiene saldo pendiente por cobrar.',
                ]);
            }

            $amount = round((float) ($payload['amount'] ?? 0), 2);

            if ($amount > $summary['balance_due']) {
                throw ValidationException::withMessages([
                    'amount' => "Solo puedes registrar hasta {$summary['balance_due']} en esta venta.",
                ]);
            }

            $method = trim((string) ($payload['method'] ?? ''));
            $paidAt = $this->parseDateTime($payload['paid_at'] ?? null) ?? now();
            $cashSession = $method === 'cash'
                ? $this->cashSessionService->getCurrentSession($user)
                : null;

            if ($method === 'cash' && ! $cashSession instanceof CashSession) {
                throw ValidationException::withMessages([
                    'cash_session' => 'Debes abrir una caja para registrar cobros en efectivo.',
                ]);
            }

            $payment = SalePayment::query()->create([
                'public_id' => (string) Str::uuid(),
                'sale_id' => $sale->id,
                'cash_session_id' => $cashSession?->id,
                'user_id' => $user->id,
                'method' => $method,
                'amount' => $amount,
                'reference' => $this->nullableText($payload['reference'] ?? null),
                'notes' => $this->nullableText($payload['notes'] ?? null),
                'paid_at' => $paidAt,
                'metadata' => [
                    'source' => 'sale_payment',
                    'balance_due_before' => $summary['balance_due'],
                    'balance_due_after' => round($summary['balance_due'] - $amount, 2),
                ],
            ]);

            if ($cashSession instanceof CashSession) {
                CashMovement::query()->create([
                    'public_id' => (string) Str::uuid(),
                    'cash_session_id' => $cashSession->id,
                    'user_id' => $user->id,
                    'type' => 'income',
                    'category' => 'sale_payment',
                    'amount' => $amount,
                    'reference_type' => 'sale_payment',
                    'reference_id' => $payment->id,
                    'notes' => "Cobro venta {$sale->public_id}",
                    'occurred_at' => $paidAt,
                ]);
            }

            $sale->update([
                'paid_total' => round($summary['paid_total'] + $amount, 2),
            ]);

            return $this->loadPaymentRelations($payment);
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function cancelSale(User $user, Sale $sale, array $payload): Sale
    {
        return DB::transaction(function () use ($user, $sale, $payload): Sale {
            $sale = $this->loadSaleRelations($sale);

            if ($sale->status !== 'completed') {
                throw ValidationException::withMessages([
                    'sale' => 'Solo se pueden anular ventas completadas.',
                ]);
            }

            foreach ($sale->items as $item) {
                $product = $item->product;

                if ($product instanceof Product && $product->track_stock) {
                    StockMovement::query()->create([
                        'product_id' => $product->id,
                        'user_id' => $user->id,
                        'type' => 'sale_return',
                        'reason' => 'sale_cancellation',
                        'reference_type' => 'sale',
                        'reference_id' => $sale->id,
                        'quantity' => (float) $item->quantity,
                        'unit_cost' => $item->unit_cost,
                        'notes' => "Reversion por anulacion {$sale->public_id}",
                        'occurred_at' => now(),
                    ]);
                }
            }

            $cashCollected = round((float) $sale->payments
                ->where('method', 'cash')
                ->sum('amount'), 2);

            if ($sale->cash_session_id !== null && $cashCollected > 0) {
                CashMovement::query()->create([
                    'public_id' => (string) Str::uuid(),
                    'cash_session_id' => $sale->cash_session_id,
                    'user_id' => $user->id,
                    'type' => 'expense',
                    'category' => 'sale_refund',
                    'amount' => $cashCollected,
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'notes' => "Salida por anulacion {$sale->public_id}",
                    'occurred_at' => now(),
                ]);
            }

            $sale->update([
                'status' => 'cancelled',
                'cancelled_by_id' => $user->id,
                'cancellation_reason' => $payload['cancellation_reason'],
                'cancelled_at' => now(),
            ]);

            return $this->loadSaleRelations($sale->fresh());
        });
    }

    /**
     * @return Collection<int, Sale>
     */
    public function recentSales(): Collection
    {
        return Sale::query()
            ->with('customer', 'payments', 'cashSession.register', 'cancelledBy', 'invoice.taxResolution')
            ->withCount(['items', 'returns', 'payments'])
            ->withSum('returns as returned_total', 'refund_total')
            ->orderByDesc('sold_at')
            ->limit(15)
            ->get();
    }

    public function showSale(Sale $sale): Sale
    {
        return $this->loadSaleRelations($sale);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function registerReturn(User $user, Sale $sale, array $payload): SaleReturn
    {
        return DB::transaction(function () use ($user, $sale, $payload): SaleReturn {
            $sale = $this->loadSaleRelations($sale);

            if ($sale->status !== 'completed') {
                throw ValidationException::withMessages([
                    'sale' => 'Solo se pueden devolver ventas completadas.',
                ]);
            }

            if ($sale->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'sale' => 'La venta no tiene items para devolver.',
                ]);
            }

            $refundMethod = trim((string) ($payload['refund_method'] ?? ''));
            $cashSession = $refundMethod === 'cash'
                ? $this->cashSessionService->getCurrentSession($user)
                : null;

            if ($refundMethod === 'cash' && ! $cashSession instanceof CashSession) {
                throw ValidationException::withMessages([
                    'cash_session' => 'Debes abrir una caja para devolver efectivo.',
                ]);
            }

            $returnedQuantities = SaleReturnItem::query()
                ->selectRaw('sale_item_id, COALESCE(SUM(quantity), 0) as aggregate_quantity')
                ->whereIn('sale_item_id', $sale->items->pluck('id')->all())
                ->groupBy('sale_item_id')
                ->pluck('aggregate_quantity', 'sale_item_id');

            $saleItems = $sale->items->keyBy('id');
            $preparedItems = [];
            $subtotal = 0.0;
            $taxTotal = 0.0;

            foreach (($payload['items'] ?? []) as $index => $itemPayload) {
                /** @var SaleItem|null $saleItem */
                $saleItem = $saleItems->get((int) ($itemPayload['sale_item_id'] ?? 0));

                if (! $saleItem instanceof SaleItem) {
                    throw ValidationException::withMessages([
                        "items.{$index}.sale_item_id" => 'El item seleccionado no pertenece a la venta.',
                    ]);
                }

                $requestedQuantity = round((float) ($itemPayload['quantity'] ?? 0), 2);
                $alreadyReturned = round((float) ($returnedQuantities[$saleItem->id] ?? 0), 2);
                $remainingQuantity = round((float) $saleItem->quantity - $alreadyReturned, 2);

                if ($requestedQuantity > $remainingQuantity) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => "Solo puedes devolver hasta {$remainingQuantity} unidad(es) de '{$saleItem->name_snapshot}'.",
                    ]);
                }

                $lineSubtotal = round((((float) $saleItem->line_subtotal) / (float) $saleItem->quantity) * $requestedQuantity, 2);
                $lineTax = round((((float) $saleItem->line_tax) / (float) $saleItem->quantity) * $requestedQuantity, 2);
                $lineTotal = round($lineSubtotal + $lineTax, 2);

                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;

                $preparedItems[] = [
                    'sale_item' => $saleItem,
                    'product' => $saleItem->product,
                    'quantity' => $requestedQuantity,
                    'line_subtotal' => $lineSubtotal,
                    'line_tax' => $lineTax,
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
            $taxTotal = round($taxTotal, 2);
            $refundTotal = round($subtotal + $taxTotal, 2);

            $saleReturn = SaleReturn::query()->create([
                'public_id' => (string) Str::uuid(),
                'sale_id' => $sale->id,
                'user_id' => $user->id,
                'cash_session_id' => $cashSession?->id,
                'status' => 'completed',
                'refund_method' => $refundMethod,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'refund_total' => $refundTotal,
                'reason' => $payload['reason'],
                'notes' => $payload['notes'] ?? null,
                'returned_at' => now(),
                'metadata' => [
                    'sale_document_type' => $sale->document_type,
                    'credit_note_pending' => $sale->invoice !== null,
                ],
            ]);

            foreach ($preparedItems as $preparedItem) {
                /** @var SaleItem $saleItem */
                $saleItem = $preparedItem['sale_item'];
                /** @var Product|null $product */
                $product = $preparedItem['product'];

                SaleReturnItem::query()->create([
                    'sale_return_id' => $saleReturn->id,
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'name_snapshot' => $saleItem->name_snapshot,
                    'sku_snapshot' => $saleItem->sku_snapshot,
                    'quantity' => $preparedItem['quantity'],
                    'unit_price' => $saleItem->unit_price,
                    'unit_cost' => $saleItem->unit_cost,
                    'line_subtotal' => $preparedItem['line_subtotal'],
                    'line_tax' => $preparedItem['line_tax'],
                    'line_total' => $preparedItem['line_total'],
                    'reason' => $preparedItem['reason'],
                ]);

                if ($product instanceof Product && $product->track_stock) {
                    StockMovement::query()->create([
                        'product_id' => $product->id,
                        'user_id' => $user->id,
                        'type' => 'sale_return',
                        'reason' => 'sale_partial_return',
                        'reference_type' => 'sale_return',
                        'reference_id' => $saleReturn->id,
                        'quantity' => (float) $preparedItem['quantity'],
                        'unit_cost' => $saleItem->unit_cost,
                        'notes' => "Devolucion parcial de venta {$sale->public_id}",
                        'occurred_at' => now(),
                    ]);
                }
            }

            if ($cashSession instanceof CashSession) {
                CashMovement::query()->create([
                    'public_id' => (string) Str::uuid(),
                    'cash_session_id' => $cashSession->id,
                    'user_id' => $user->id,
                    'type' => 'expense',
                    'category' => 'sale_return',
                    'amount' => $refundTotal,
                    'reference_type' => 'sale_return',
                    'reference_id' => $saleReturn->id,
                    'notes' => "Devolucion de venta {$sale->public_id}",
                    'occurred_at' => now(),
                ]);
            }

            return $this->loadSaleReturnRelations($saleReturn);
        });
    }

    private function loadSaleRelations(Sale $sale): Sale
    {
        return $sale->load([
            'customer',
            'payments.user',
            'payments.cashSession.register',
            'cashSession.register',
            'cancelledBy',
            'invoice.taxResolution',
            'invoice.items',
            'items' => fn ($query) => $query
                ->with(['product' => fn ($productQuery) => $productQuery->withSum('stockMovements as current_stock', 'quantity')])
                ->withSum('returnItems as returned_quantity', 'quantity'),
            'returns' => fn ($query) => $query->with([
                'user',
                'cashSession.register',
                'items',
                'creditNote.user',
                'creditNote.taxResolution',
                'creditNote.items',
            ]),
        ])->loadCount(['items', 'returns', 'payments'])
            ->loadSum('returns as returned_total', 'refund_total');
    }

    private function loadDraftRelations(SaleDraft $draft): SaleDraft
    {
        return $draft->load([
            'customer',
            'cashSession.register',
            'items.product' => fn ($query) => $query->withSum('stockMovements as current_stock', 'quantity'),
        ]);
    }

    private function loadSaleReturnRelations(SaleReturn $saleReturn): SaleReturn
    {
        return $saleReturn->load([
            'user',
            'cashSession.register',
            'items',
            'creditNote.user',
            'creditNote.taxResolution',
            'creditNote.items',
        ]);
    }

    private function loadPaymentRelations(SalePayment $payment): SalePayment
    {
        return $payment->load([
            'user',
            'cashSession.register',
        ]);
    }

    private function resolveDocumentType(mixed $value): string
    {
        $documentType = trim((string) ($value ?? ''));

        if ($documentType !== '') {
            return $documentType;
        }

        $configuredDefault = SystemSetting::query()->where('key', 'default_document_type')->first()?->value;

        return in_array($configuredDefault, ['ticket', 'factura', 'nota'], true)
            ? $configuredDefault
            : 'ticket';
    }

    /**
     * @return array{applied_amount: float, change_total: float}
     */
    private function resolveCheckoutPaymentAmounts(string $paymentMethod, float $amountPaid, float $grandTotal): array
    {
        if ($paymentMethod === 'credit') {
            if ($amountPaid > 0) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'La cuenta por cobrar no registra un monto inicial pagado.',
                ]);
            }

            return [
                'applied_amount' => 0.0,
                'change_total' => 0.0,
            ];
        }

        if ($amountPaid <= 0) {
            throw ValidationException::withMessages([
                'payment_method' => 'Selecciona cuenta por cobrar cuando no registras un pago inicial.',
            ]);
        }

        if ($paymentMethod !== 'cash' && $amountPaid > $grandTotal) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Solo el efectivo puede registrar vuelto sobre el total de la venta.',
            ]);
        }

        return [
            'applied_amount' => round(min($amountPaid, $grandTotal), 2),
            'change_total' => $paymentMethod === 'cash'
                ? round(max(0, $amountPaid - $grandTotal), 2)
                : 0.0,
        ];
    }

    /**
     * @return array{paid_total: float, returned_total: float, net_receivable_total: float, balance_due: float}
     */
    private function paymentSummary(Sale $sale): array
    {
        $paidTotal = round((float) ($sale->payments?->sum('amount') ?? $sale->paid_total ?? 0), 2);
        $returnedTotal = round((float) ($sale->returned_total ?? $sale->returns?->sum('refund_total') ?? 0), 2);
        $netReceivableTotal = round(max(0, (float) $sale->grand_total - $returnedTotal), 2);

        return [
            'paid_total' => $paidTotal,
            'returned_total' => $returnedTotal,
            'net_receivable_total' => $netReceivableTotal,
            'balance_due' => round($netReceivableTotal - $paidTotal, 2),
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
