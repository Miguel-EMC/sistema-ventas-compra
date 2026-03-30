<?php

namespace App\Application\Services\Reports;

use App\Models\CashSession;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BusinessInsightsService
{
    private const MANUAL_MOVEMENT_REFERENCE_TYPE = 'manual_cash_movement';

    /**
     * @return array<string, mixed>
     */
    public function dashboardSummary(): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $salesToday = $this->salesSummaryBetween($todayStart, $todayEnd);
        $salesMonth = $this->salesSummaryBetween($monthStart, $monthEnd);
        $lowStockCount = $this->lowStockProductsQuery()->count();
        $openCashSessions = CashSession::query()->where('status', 'open')->count();
        $activeCustomers = Customer::query()->where('is_active', true)->count();

        $recentSales = Sale::query()
            ->with(['customer', 'payments', 'cashSession.register', 'cancelledBy'])
            ->withCount('items')
            ->orderByDesc('sold_at')
            ->limit(5)
            ->get()
            ->map(fn (Sale $sale): array => [
                'id' => $sale->id,
                'public_id' => $sale->public_id,
                'status' => $sale->status,
                'customer_name' => $sale->customer?->name,
                'register_name' => $sale->cashSession?->register?->name,
                'grand_total' => (float) $sale->grand_total,
                'items_count' => (int) $sale->items_count,
                'payment_methods' => $sale->payments->pluck('method')->values()->all(),
                'sold_at' => $sale->sold_at?->toIso8601String(),
                'cancelled_at' => $sale->cancelled_at?->toIso8601String(),
                'cancelled_by_name' => $sale->cancelledBy?->display_name ?: $sale->cancelledBy?->name,
            ])
            ->all();

        return [
            'summary' => [
                'sales_today_total' => $salesToday['total'],
                'sales_today_count' => $salesToday['count'],
                'sales_month_total' => $salesMonth['total'],
                'sales_month_count' => $salesMonth['count'],
                'open_cash_sessions' => $openCashSessions,
                'low_stock_products' => $lowStockCount,
                'active_customers' => $activeCustomers,
            ],
            'recent_sales' => $recentSales,
            'low_stock_products' => $this->lowStockProductsQuery()
                ->limit(5)
                ->get()
                ->map(fn (Product $product): array => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'current_stock' => (float) ($product->current_stock ?? 0),
                    'minimum_stock' => (float) $product->minimum_stock,
                    'unit' => $product->unit,
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function reportsOverview(?string $dateFrom = null, ?string $dateTo = null, ?int $customerId = null): array
    {
        $from = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : now()->startOfMonth()->startOfDay();
        $to = $dateTo ? Carbon::parse($dateTo)->endOfDay() : now()->endOfMonth()->endOfDay();

        $summary = $this->salesSummaryBetween($from, $to);
        $profitability = $this->profitabilityBetween($from, $to, $summary['total']);
        $paymentMethods = $this->paymentMethodsBetween($from, $to);
        $salesByDay = $this->salesByDayBetween($from, $to);
        $salesByMonth = $this->salesByMonthForYear((int) $to->format('Y'));
        $salesLastSixMonths = $this->salesLastSixMonths($to);
        $salesDocuments = $this->salesDocumentsBetween($from, $to);
        $topProducts = $this->topProductsBetween($from, $to);
        $productSales = $this->productSalesBetween($from, $to);
        $cashSessions = $this->cashSessionsBetween($from, $to);
        $receivables = $this->receivablesBetween($from, $to, $customerId);
        $expenseCategories = $this->expenseCategoriesBetween($from, $to);
        $operationalMovements = $this->operationalMovementsBetween($from, $to);
        $cashPayments = $paymentMethods->firstWhere('method', 'cash');

        return [
            'range' => [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'customer_id' => $customerId,
            ],
            'summary' => [
                'sales_total' => $summary['total'],
                'sales_count' => $summary['count'],
                'average_ticket' => $summary['average_ticket'],
                'cash_sales_total' => is_array($cashPayments) ? round((float) ($cashPayments['total'] ?? 0), 2) : 0.0,
                'customers_total' => Customer::query()->where('is_active', true)->count(),
                'open_cash_sessions' => CashSession::query()->where('status', 'open')->count(),
                'low_stock_products' => $this->lowStockProductsQuery()->count(),
                'receivables_total' => $receivables['balance_due_total'],
                'receivables_sales_count' => $receivables['sales_count'],
                'customers_with_receivables' => $receivables['customers_count'],
            ],
            'profitability' => $profitability,
            'payment_methods' => $paymentMethods->values()->all(),
            'sales_by_day' => $salesByDay->values()->all(),
            'sales_by_month' => $salesByMonth->values()->all(),
            'sales_last_six_months' => $salesLastSixMonths->values()->all(),
            'sales_documents' => $salesDocuments->values()->all(),
            'top_products' => $topProducts->values()->all(),
            'product_sales' => $productSales->values()->all(),
            'cash_sessions' => $cashSessions->values()->all(),
            'receivables' => $receivables,
            'expense_categories' => $expenseCategories->values()->all(),
            'operational_movements' => $operationalMovements->values()->all(),
        ];
    }

    /**
     * @return array{total: float, count: int, average_ticket: float}
     */
    private function salesSummaryBetween(Carbon $from, Carbon $to): array
    {
        $row = Sale::query()
            ->selectRaw('COUNT(*) as aggregate_count, COALESCE(SUM(grand_total), 0) as aggregate_total')
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$from, $to])
            ->first();

        $count = (int) ($row?->aggregate_count ?? 0);
        $total = (float) ($row?->aggregate_total ?? 0);

        return [
            'total' => round($total, 2),
            'count' => $count,
            'average_ticket' => $count > 0 ? round($total / $count, 2) : 0.0,
        ];
    }

    /**
     * @return Collection<int, array{method:string,total:float,count:int}>
     */
    private function paymentMethodsBetween(Carbon $from, Carbon $to): Collection
    {
        return DB::table('sale_payments')
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->selectRaw(
                'sale_payments.method as method, COUNT(sale_payments.id) as aggregate_count, COALESCE(SUM(sale_payments.amount), 0) as aggregate_total',
            )
            ->where('sales.status', 'completed')
            ->whereBetween('sale_payments.paid_at', [$from, $to])
            ->groupBy('sale_payments.method')
            ->orderByDesc('aggregate_total')
            ->get()
            ->map(fn ($row): array => [
                'method' => (string) $row->method,
                'count' => (int) $row->aggregate_count,
                'total' => round((float) $row->aggregate_total, 2),
            ]);
    }

    /**
     * @return array<string, float>
     */
    private function profitabilityBetween(Carbon $from, Carbon $to, float $salesTotal): array
    {
        $refundTotal = (float) DB::table('sale_returns')
            ->where('status', 'completed')
            ->whereBetween('returned_at', [$from, $to])
            ->sum('refund_total');

        $soldCostTotal = (float) DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sold_at', [$from, $to])
            ->selectRaw('COALESCE(SUM(COALESCE(sale_items.unit_cost, 0) * sale_items.quantity), 0) as aggregate_total')
            ->value('aggregate_total');

        $returnedCostTotal = (float) DB::table('sale_return_items')
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->where('sale_returns.status', 'completed')
            ->whereBetween('sale_returns.returned_at', [$from, $to])
            ->selectRaw('COALESCE(SUM(COALESCE(sale_return_items.unit_cost, 0) * sale_return_items.quantity), 0) as aggregate_total')
            ->value('aggregate_total');

        $operationalIncomeTotal = (float) $this->manualCashMovementsQuery($from, $to)
            ->where('type', 'income')
            ->sum('amount');

        $operationalExpensesTotal = (float) $this->manualCashMovementsQuery($from, $to)
            ->where('type', 'expense')
            ->sum('amount');

        $netSalesTotal = $salesTotal - $refundTotal;
        $costTotal = $soldCostTotal - $returnedCostTotal;
        $grossMarginTotal = $netSalesTotal - $costTotal;
        $netUtilityTotal = $grossMarginTotal + $operationalIncomeTotal - $operationalExpensesTotal;

        return [
            'refund_total' => round($refundTotal, 2),
            'net_sales_total' => round($netSalesTotal, 2),
            'cost_total' => round($costTotal, 2),
            'gross_margin_total' => round($grossMarginTotal, 2),
            'operational_income_total' => round($operationalIncomeTotal, 2),
            'operational_expenses_total' => round($operationalExpensesTotal, 2),
            'net_utility_total' => round($netUtilityTotal, 2),
        ];
    }

    /**
     * @return Collection<int, array{day:string,sales_count:int,total:float}>
     */
    private function salesByDayBetween(Carbon $from, Carbon $to): Collection
    {
        return DB::table('sales')
            ->selectRaw('date(sold_at) as day, COUNT(id) as aggregate_count, COALESCE(SUM(grand_total), 0) as aggregate_total')
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$from, $to])
            ->groupBy(DB::raw('date(sold_at)'))
            ->orderBy('day')
            ->get()
            ->map(fn ($row): array => [
                'day' => (string) $row->day,
                'sales_count' => (int) $row->aggregate_count,
                'total' => round((float) $row->aggregate_total, 2),
            ]);
    }

    /**
     * @return Collection<int, array{month:string,sales_count:int,total:float}>
     */
    private function salesByMonthForYear(int $year): Collection
    {
        $from = Carbon::create($year, 1, 1)->startOfDay();
        $to = $from->copy()->endOfYear();

        return $this->normalizeMonthlySalesRows(
            $this->salesByMonthRows($from, $to),
            $from,
            $to,
        );
    }

    /**
     * @return Collection<int, array{month:string,sales_count:int,total:float}>
     */
    private function salesLastSixMonths(Carbon $referenceDate): Collection
    {
        $startMonth = $referenceDate->copy()->startOfMonth()->subMonths(5);
        $endMonth = $referenceDate->copy()->startOfMonth();

        return $this->normalizeMonthlySalesRows(
            $this->salesByMonthRows($startMonth->copy()->startOfDay(), $endMonth->copy()->endOfMonth()),
            $startMonth,
            $endMonth,
        );
    }

    /**
     * @return Collection<int, array{month:string,sales_count:int,total:float}>
     */
    private function salesByMonthRows(Carbon $from, Carbon $to): Collection
    {
        $monthExpression = $this->monthBucketExpression('sold_at');

        return DB::table('sales')
            ->selectRaw(
                "{$monthExpression} as aggregate_month, COUNT(id) as aggregate_count, COALESCE(SUM(grand_total), 0) as aggregate_total",
            )
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$from, $to])
            ->groupBy(DB::raw($monthExpression))
            ->orderBy('aggregate_month')
            ->get()
            ->map(fn ($row): array => [
                'month' => (string) $row->aggregate_month,
                'sales_count' => (int) $row->aggregate_count,
                'total' => round((float) $row->aggregate_total, 2),
            ]);
    }

    /**
     * @param Collection<int, array{month:string,sales_count:int,total:float}> $rows
     * @return Collection<int, array{month:string,sales_count:int,total:float}>
     */
    private function normalizeMonthlySalesRows(Collection $rows, Carbon $startMonth, Carbon $endMonth): Collection
    {
        $rowsByMonth = $rows->keyBy('month');
        $normalized = collect();
        $cursor = $startMonth->copy()->startOfMonth();
        $lastMonth = $endMonth->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($lastMonth)) {
            $monthKey = $cursor->format('Y-m');
            /** @var array{month:string,sales_count:int,total:float}|null $row */
            $row = $rowsByMonth->get($monthKey);

            $normalized->push([
                'month' => $monthKey,
                'sales_count' => $row['sales_count'] ?? 0,
                'total' => $row['total'] ?? 0.0,
            ]);

            $cursor->addMonth();
        }

        return $normalized;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function salesDocumentsBetween(Carbon $from, Carbon $to): Collection
    {
        return Sale::query()
            ->with(['customer', 'payments', 'invoice'])
            ->withCount('items')
            ->withSum('items as quantity_total', 'quantity')
            ->withSum('returns as returned_total', 'refund_total')
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$from, $to])
            ->orderByDesc('sold_at')
            ->limit(30)
            ->get()
            ->map(function (Sale $sale): array {
                $paidTotal = round((float) $sale->payments->sum('amount'), 2);
                $returnedTotal = round((float) ($sale->returned_total ?? 0), 2);
                $netTotal = round(max(0, (float) $sale->grand_total - $returnedTotal), 2);
                $balanceDue = round($netTotal - $paidTotal, 2);

                return [
                    'id' => $sale->id,
                    'public_id' => $sale->public_id,
                    'customer_name' => $sale->customer?->name ?? 'Consumidor final',
                    'document_type' => $sale->document_type,
                    'invoice_number' => $sale->invoice?->invoice_number,
                    'document_reference' => $sale->invoice?->invoice_number ?: ($sale->public_id ?: '#'.$sale->id),
                    'items_count' => (int) $sale->items_count,
                    'quantity_total' => round((float) ($sale->quantity_total ?? 0), 2),
                    'payment_methods' => $sale->payments->pluck('method')->unique()->values()->all(),
                    'grand_total' => (float) $sale->grand_total,
                    'paid_total' => $paidTotal,
                    'returned_total' => $returnedTotal,
                    'net_total' => $netTotal,
                    'balance_due' => $balanceDue,
                    'payment_status' => $this->resolvePaymentStatus($paidTotal, $balanceDue),
                    'sold_at' => $sale->sold_at?->toIso8601String(),
                ];
            });
    }

    /**
     * @return Collection<int, array{name:string,quantity:float,total:float}>
     */
    private function topProductsBetween(Carbon $from, Carbon $to): Collection
    {
        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->selectRaw(
                'sale_items.name_snapshot as product_name, COALESCE(SUM(sale_items.quantity), 0) as aggregate_quantity, COALESCE(SUM(sale_items.line_total), 0) as aggregate_total',
            )
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sold_at', [$from, $to])
            ->groupBy('sale_items.name_snapshot')
            ->orderByDesc('aggregate_total')
            ->limit(10)
            ->get()
            ->map(fn ($row): array => [
                'name' => (string) $row->product_name,
                'quantity' => round((float) $row->aggregate_quantity, 2),
                'total' => round((float) $row->aggregate_total, 2),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function productSalesBetween(Carbon $from, Carbon $to): Collection
    {
        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->selectRaw(
                'sale_items.name_snapshot as product_name, sale_items.sku_snapshot as sku, COUNT(DISTINCT sales.id) as aggregate_sales_count, COALESCE(SUM(sale_items.quantity), 0) as aggregate_quantity, COALESCE(SUM(sale_items.line_total), 0) as aggregate_total, MAX(sales.sold_at) as last_sold_at',
            )
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sold_at', [$from, $to])
            ->groupBy('sale_items.name_snapshot', 'sale_items.sku_snapshot')
            ->orderByDesc('aggregate_total')
            ->limit(25)
            ->get()
            ->map(function ($row): array {
                $quantity = round((float) $row->aggregate_quantity, 2);
                $total = round((float) $row->aggregate_total, 2);

                return [
                    'name' => (string) $row->product_name,
                    'sku' => $row->sku !== null ? (string) $row->sku : null,
                    'sales_count' => (int) $row->aggregate_sales_count,
                    'quantity' => $quantity,
                    'average_unit_price' => $quantity > 0 ? round($total / $quantity, 2) : 0.0,
                    'total' => $total,
                    'last_sold_at' => $row->last_sold_at !== null ? Carbon::parse((string) $row->last_sold_at)->toIso8601String() : null,
                ];
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function cashSessionsBetween(Carbon $from, Carbon $to): Collection
    {
        return CashSession::query()
            ->with(['register', 'openedBy', 'closedBy'])
            ->withCount(['sales' => fn ($query) => $query->where('status', 'completed')])
            ->withSum(['sales as sales_total' => fn ($query) => $query->where('status', 'completed')], 'grand_total')
            ->withSum(['movements as cash_income_total' => fn ($query) => $query->where('type', 'income')], 'amount')
            ->withSum(['movements as cash_out_total' => fn ($query) => $query->where('type', 'expense')], 'amount')
            ->whereBetween('opened_at', [$from, $to])
            ->orderByDesc('opened_at')
            ->limit(10)
            ->get()
            ->map(fn (CashSession $session): array => [
                'id' => $session->id,
                'status' => $session->status,
                'register_name' => $session->register?->name,
                'opened_by_name' => $session->openedBy?->display_name ?: $session->openedBy?->name,
                'opened_at' => $session->opened_at?->toIso8601String(),
                'closed_at' => $session->closed_at?->toIso8601String(),
                'sales_count' => (int) $session->sales_count,
                'sales_total' => round((float) ($session->sales_total ?? 0), 2),
                'cash_balance' => round(
                    (float) $session->opening_amount
                    + (float) ($session->cash_income_total ?? 0)
                    - (float) ($session->cash_out_total ?? 0),
                    2,
                ),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function receivablesBetween(Carbon $from, Carbon $to, ?int $customerId = null): array
    {
        $selectedCustomer = $customerId === null ? null : Customer::query()->find($customerId);
        $sales = $this->openReceivableSalesBetween($from, $to, $customerId);
        $customers = $sales
            ->groupBy(fn (array $sale): string => (string) ($sale['customer_id'] ?? "guest-{$sale['public_id']}"))
            ->map(function (Collection $group): array {
                $first = $group->first();

                return [
                    'customer_id' => $first['customer_id'],
                    'name' => $first['customer_name'],
                    'document_number' => $first['customer_document_number'],
                    'sales_count' => $group->count(),
                    'net_receivable_total' => round((float) $group->sum('net_receivable_total'), 2),
                    'paid_total' => round((float) $group->sum('paid_total'), 2),
                    'balance_due' => round((float) $group->sum('balance_due'), 2),
                    'last_sale_at' => $group->max('sold_at'),
                ];
            })
            ->sortByDesc('balance_due')
            ->values();

        return [
            'customer' => $selectedCustomer === null ? null : [
                'id' => $selectedCustomer->id,
                'name' => $selectedCustomer->name,
                'document_number' => $selectedCustomer->document_number,
            ],
            'balance_due_total' => round((float) $sales->sum('balance_due'), 2),
            'sales_count' => $sales->count(),
            'customers_count' => $customers->count(),
            'customers' => $customers->take(10)->all(),
            'sales' => $sales->take(20)->values()->all(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function openReceivableSalesBetween(Carbon $from, Carbon $to, ?int $customerId = null): Collection
    {
        return Sale::query()
            ->with(['customer', 'payments', 'invoice'])
            ->withCount('items')
            ->withSum('returns as returned_total', 'refund_total')
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$from, $to])
            ->when($customerId !== null, fn ($query) => $query->where('customer_id', $customerId))
            ->orderByDesc('sold_at')
            ->get()
            ->map(function (Sale $sale): array {
                $paidTotal = round((float) $sale->payments->sum('amount'), 2);
                $returnedTotal = round((float) ($sale->returned_total ?? 0), 2);
                $netReceivableTotal = round(max(0, (float) $sale->grand_total - $returnedTotal), 2);
                $balanceDue = round($netReceivableTotal - $paidTotal, 2);

                return [
                    'id' => $sale->id,
                    'public_id' => $sale->public_id,
                    'customer_id' => $sale->customer?->id,
                    'customer_name' => $sale->customer?->name ?? 'Consumidor final',
                    'customer_document_number' => $sale->customer?->document_number,
                    'document_type' => $sale->document_type,
                    'invoice_number' => $sale->invoice?->invoice_number,
                    'items_count' => (int) $sale->items_count,
                    'payment_methods' => $sale->payments->pluck('method')->unique()->values()->all(),
                    'grand_total' => (float) $sale->grand_total,
                    'paid_total' => $paidTotal,
                    'returned_total' => $returnedTotal,
                    'net_receivable_total' => $netReceivableTotal,
                    'balance_due' => $balanceDue,
                    'payment_status' => $this->resolvePaymentStatus($paidTotal, $balanceDue),
                    'sold_at' => $sale->sold_at?->toIso8601String(),
                ];
            })
            ->filter(fn (array $sale): bool => $sale['balance_due'] > 0)
            ->values();
    }

    /**
     * @return Collection<int, array{category:string,count:int,total:float}>
     */
    private function expenseCategoriesBetween(Carbon $from, Carbon $to): Collection
    {
        return $this->manualCashMovementsQuery($from, $to)
            ->where('type', 'expense')
            ->selectRaw(
                'COALESCE(category, ?) as category, COUNT(id) as aggregate_count, COALESCE(SUM(amount), 0) as aggregate_total',
                ['sin_categoria'],
            )
            ->groupBy('category')
            ->orderByDesc('aggregate_total')
            ->get()
            ->map(fn ($row): array => [
                'category' => (string) $row->category,
                'count' => (int) $row->aggregate_count,
                'total' => round((float) $row->aggregate_total, 2),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function operationalMovementsBetween(Carbon $from, Carbon $to): Collection
    {
        return $this->manualCashMovementsQuery($from, $to)
            ->with(['session.register', 'user'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn ($movement): array => [
                'id' => $movement->id,
                'type' => $movement->type,
                'category' => $movement->category ?: 'sin_categoria',
                'amount' => round((float) $movement->amount, 2),
                'notes' => $movement->notes,
                'user_name' => $movement->user?->display_name ?: $movement->user?->name,
                'register_name' => $movement->session?->register?->name,
                'occurred_at' => $movement->occurred_at?->toIso8601String(),
            ]);
    }

    private function manualCashMovementsQuery(Carbon $from, Carbon $to): Builder
    {
        return \App\Models\CashMovement::query()
            ->where('reference_type', self::MANUAL_MOVEMENT_REFERENCE_TYPE)
            ->whereBetween('occurred_at', [$from, $to]);
    }

    private function lowStockProductsQuery()
    {
        $currentStockExpression = $this->currentStockExpression();

        return Product::query()
            ->select('products.*')
            ->selectRaw("{$currentStockExpression} as current_stock")
            ->where('track_stock', true)
            ->where('is_active', true)
            ->whereRaw("{$currentStockExpression} <= products.minimum_stock")
            ->orderBy('name');
    }

    private function currentStockExpression(): string
    {
        return <<<'SQL'
            (
                SELECT COALESCE(SUM(stock_movements.quantity), 0)
                FROM stock_movements
                WHERE stock_movements.product_id = products.id
            )
        SQL;
    }

    private function monthBucketExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => "to_char(date_trunc('month', {$column}), 'YYYY-MM')",
            'mysql', 'mariadb' => "DATE_FORMAT({$column}, '%Y-%m')",
            default => "strftime('%Y-%m', {$column})",
        };
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
