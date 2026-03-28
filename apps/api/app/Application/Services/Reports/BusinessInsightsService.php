<?php

namespace App\Application\Services\Reports;

use App\Models\CashSession;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BusinessInsightsService
{
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
            ->with(['customer', 'payments', 'cashSession.register'])
            ->withCount('items')
            ->orderByDesc('sold_at')
            ->limit(5)
            ->get()
            ->map(fn (Sale $sale): array => [
                'id' => $sale->id,
                'public_id' => $sale->public_id,
                'customer_name' => $sale->customer?->name,
                'register_name' => $sale->cashSession?->register?->name,
                'grand_total' => (float) $sale->grand_total,
                'items_count' => (int) $sale->items_count,
                'payment_methods' => $sale->payments->pluck('method')->values()->all(),
                'sold_at' => $sale->sold_at?->toIso8601String(),
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
    public function reportsOverview(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $from = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : now()->startOfMonth()->startOfDay();
        $to = $dateTo ? Carbon::parse($dateTo)->endOfDay() : now()->endOfMonth()->endOfDay();

        $summary = $this->salesSummaryBetween($from, $to);
        $paymentMethods = $this->paymentMethodsBetween($from, $to);
        $salesByDay = $this->salesByDayBetween($from, $to);
        $topProducts = $this->topProductsBetween($from, $to);
        $cashSessions = $this->cashSessionsBetween($from, $to);
        $cashPayments = $paymentMethods->firstWhere('method', 'cash');

        return [
            'range' => [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
            ],
            'summary' => [
                'sales_total' => $summary['total'],
                'sales_count' => $summary['count'],
                'average_ticket' => $summary['average_ticket'],
                'cash_sales_total' => is_array($cashPayments) ? round((float) ($cashPayments['total'] ?? 0), 2) : 0.0,
                'customers_total' => Customer::query()->where('is_active', true)->count(),
                'open_cash_sessions' => CashSession::query()->where('status', 'open')->count(),
                'low_stock_products' => $this->lowStockProductsQuery()->count(),
            ],
            'payment_methods' => $paymentMethods->values()->all(),
            'sales_by_day' => $salesByDay->values()->all(),
            'top_products' => $topProducts->values()->all(),
            'cash_sessions' => $cashSessions->values()->all(),
        ];
    }

    /**
     * @return array{total: float, count: int, average_ticket: float}
     */
    private function salesSummaryBetween(Carbon $from, Carbon $to): array
    {
        $row = Sale::query()
            ->selectRaw('COUNT(*) as aggregate_count, COALESCE(SUM(grand_total), 0) as aggregate_total')
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
            ->whereBetween('sales.sold_at', [$from, $to])
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
     * @return Collection<int, array{day:string,sales_count:int,total:float}>
     */
    private function salesByDayBetween(Carbon $from, Carbon $to): Collection
    {
        return DB::table('sales')
            ->selectRaw('date(sold_at) as day, COUNT(id) as aggregate_count, COALESCE(SUM(grand_total), 0) as aggregate_total')
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
     * @return Collection<int, array{name:string,quantity:float,total:float}>
     */
    private function topProductsBetween(Carbon $from, Carbon $to): Collection
    {
        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->selectRaw(
                'sale_items.name_snapshot as product_name, COALESCE(SUM(sale_items.quantity), 0) as aggregate_quantity, COALESCE(SUM(sale_items.line_total), 0) as aggregate_total',
            )
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
    private function cashSessionsBetween(Carbon $from, Carbon $to): Collection
    {
        return CashSession::query()
            ->with(['register', 'openedBy', 'closedBy'])
            ->withCount('sales')
            ->withSum('sales as sales_total', 'grand_total')
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
}
