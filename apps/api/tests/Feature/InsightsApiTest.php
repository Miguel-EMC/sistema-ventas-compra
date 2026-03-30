<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\CoreReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InsightsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_summary_returns_real_metrics(): void
    {
        Carbon::setTestNow('2026-03-28 12:00:00');

        $this->seed(CoreReferenceSeeder::class);
        $this->seedBusinessData();

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/dashboard/summary')
            ->assertOk()
            ->assertJsonPath('data.summary.sales_today_total', 20)
            ->assertJsonPath('data.summary.sales_month_total', 35)
            ->assertJsonPath('data.summary.open_cash_sessions', 1)
            ->assertJsonPath('data.summary.low_stock_products', 1)
            ->assertJsonPath('data.summary.active_customers', 1)
            ->assertJsonPath('data.recent_sales.0.grand_total', 20)
            ->assertJsonPath('data.low_stock_products.0.name', 'Te verde');
    }

    public function test_reports_overview_returns_range_metrics(): void
    {
        Carbon::setTestNow('2026-03-28 12:00:00');

        $this->seed(CoreReferenceSeeder::class);
        $this->seedBusinessData();

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/reports/overview?date_from=2026-03-27&date_to=2026-03-28')
            ->assertOk()
            ->assertJsonPath('data.summary.sales_total', 35)
            ->assertJsonPath('data.summary.sales_count', 2)
            ->assertJsonPath('data.summary.average_ticket', 17.5)
            ->assertJsonPath('data.summary.cash_sales_total', 20)
            ->assertJsonPath('data.summary.receivables_total', 10)
            ->assertJsonPath('data.summary.receivables_sales_count', 1)
            ->assertJsonPath('data.summary.customers_with_receivables', 1)
            ->assertJsonPath('data.profitability.refund_total', 0)
            ->assertJsonPath('data.profitability.net_sales_total', 35)
            ->assertJsonPath('data.profitability.cost_total', 12)
            ->assertJsonPath('data.profitability.gross_margin_total', 23)
            ->assertJsonPath('data.profitability.operational_income_total', 4)
            ->assertJsonPath('data.profitability.operational_expenses_total', 5)
            ->assertJsonPath('data.profitability.net_utility_total', 22)
            ->assertJsonPath('data.payment_methods.0.method', 'cash')
            ->assertJsonPath('data.payment_methods.0.total', 20)
            ->assertJsonPath('data.sales_by_day.0.day', '2026-03-27')
            ->assertJsonPath('data.sales_by_day.1.total', 20)
            ->assertJsonPath('data.sales_by_month.0.month', '2026-01')
            ->assertJsonPath('data.sales_by_month.0.total', 8)
            ->assertJsonPath('data.sales_by_month.1.month', '2026-02')
            ->assertJsonPath('data.sales_by_month.1.total', 12)
            ->assertJsonPath('data.sales_by_month.2.month', '2026-03')
            ->assertJsonPath('data.sales_by_month.2.total', 35)
            ->assertJsonPath('data.sales_last_six_months.0.month', '2025-10')
            ->assertJsonPath('data.sales_last_six_months.0.total', 6)
            ->assertJsonPath('data.sales_last_six_months.1.month', '2025-11')
            ->assertJsonPath('data.sales_last_six_months.1.total', 0)
            ->assertJsonPath('data.sales_last_six_months.5.month', '2026-03')
            ->assertJsonPath('data.sales_last_six_months.5.total', 35)
            ->assertJsonPath('data.sales_documents.0.customer_name', 'Cliente Reporte')
            ->assertJsonPath('data.sales_documents.0.document_type', 'ticket')
            ->assertJsonPath('data.sales_documents.0.net_total', 20)
            ->assertJsonPath('data.top_products.0.name', 'Cafe pasado')
            ->assertJsonPath('data.product_sales.0.name', 'Cafe pasado')
            ->assertJsonPath('data.product_sales.0.sku', 'CAFE-01')
            ->assertJsonPath('data.product_sales.0.sales_count', 2)
            ->assertJsonPath('data.product_sales.0.quantity', 3)
            ->assertJsonPath('data.product_sales.0.average_unit_price', 11.67)
            ->assertJsonPath('data.product_sales.0.total', 35)
            ->assertJsonPath('data.cash_sessions.0.sales_total', 20)
            ->assertJsonPath('data.receivables.customers.0.name', 'Cliente Reporte')
            ->assertJsonPath('data.receivables.customers.0.balance_due', 10)
            ->assertJsonPath('data.receivables.sales.0.balance_due', 10)
            ->assertJsonPath('data.receivables.sales.0.payment_status', 'partial')
            ->assertJsonPath('data.expense_categories.0.category', 'delivery')
            ->assertJsonPath('data.expense_categories.0.total', 3)
            ->assertJsonPath('data.operational_movements.0.type', 'income')
            ->assertJsonPath('data.operational_movements.0.category', 'other_income')
            ->assertJsonPath('data.operational_movements.0.amount', 4);
    }

    public function test_reports_overview_can_filter_receivable_statement_by_customer(): void
    {
        Carbon::setTestNow('2026-03-28 12:00:00');

        $this->seed(CoreReferenceSeeder::class);
        $customer = $this->seedBusinessData();

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($admin);

        $this->getJson("/api/v1/reports/overview?date_from=2026-03-27&date_to=2026-03-28&customer_id={$customer->id}")
            ->assertOk()
            ->assertJsonPath('data.range.customer_id', $customer->id)
            ->assertJsonPath('data.receivables.customer.id', $customer->id)
            ->assertJsonPath('data.receivables.customer.name', 'Cliente Reporte')
            ->assertJsonPath('data.receivables.balance_due_total', 10)
            ->assertJsonPath('data.receivables.sales_count', 1)
            ->assertJsonPath('data.receivables.sales.0.customer_id', $customer->id)
            ->assertJsonPath('data.receivables.sales.0.balance_due', 10);
    }

    public function test_authenticated_user_can_download_receivables_statement_pdf(): void
    {
        Carbon::setTestNow('2026-03-28 12:00:00');

        $this->seed(CoreReferenceSeeder::class);
        $customer = $this->seedBusinessData();

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($admin);

        $response = $this->get(
            "/api/v1/reports/receivables/pdf?date_from=2026-03-27&date_to=2026-03-28&customer_id={$customer->id}"
        )
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader(
                'content-disposition',
                'attachment; filename="estado-cuenta-Cliente-Reporte-2026-03-27-a-2026-03-28.pdf"',
            );

        $this->assertStringStartsWith('%PDF-', (string) $response->getContent());
    }

    public function test_authenticated_user_can_download_profitability_pdf(): void
    {
        Carbon::setTestNow('2026-03-28 12:00:00');

        $this->seed(CoreReferenceSeeder::class);
        $this->seedBusinessData();

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($admin);

        $response = $this->get('/api/v1/reports/profitability/pdf?date_from=2026-03-27&date_to=2026-03-28')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader(
                'content-disposition',
                'attachment; filename="utilidad-operativa-2026-03-27-a-2026-03-28.pdf"',
            );

        $this->assertStringStartsWith('%PDF-', (string) $response->getContent());
    }

    public function test_authenticated_user_can_download_sales_report_pdf(): void
    {
        Carbon::setTestNow('2026-03-28 12:00:00');

        $this->seed(CoreReferenceSeeder::class);
        $this->seedBusinessData();

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($admin);

        $response = $this->get('/api/v1/reports/sales/pdf?date_from=2026-03-27&date_to=2026-03-28')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader(
                'content-disposition',
                'attachment; filename="ventas-rango-2026-03-27-a-2026-03-28.pdf"',
            );

        $this->assertStringStartsWith('%PDF-', (string) $response->getContent());
    }

    public function test_authenticated_user_can_download_sales_report_csv(): void
    {
        Carbon::setTestNow('2026-03-28 12:00:00');

        $this->seed(CoreReferenceSeeder::class);
        $this->seedBusinessData();

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($admin);

        $response = $this->get('/api/v1/reports/sales/csv?date_from=2026-03-27&date_to=2026-03-28')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertHeader(
                'content-disposition',
                'attachment; filename="ventas-rango-2026-03-27-a-2026-03-28.csv"',
            );

        $content = (string) $response->getContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Documento,Cliente,Tipo,Items,Cantidad,Cobros,Total,Cobrado,Devuelto,Neto,Saldo,Estado,Fecha', $content);
        $this->assertStringContainsString('Cliente Reporte', $content);
    }

    public function test_authenticated_user_can_download_product_sales_csv(): void
    {
        Carbon::setTestNow('2026-03-28 12:00:00');

        $this->seed(CoreReferenceSeeder::class);
        $this->seedBusinessData();

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($admin);

        $response = $this->get('/api/v1/reports/products/csv?date_from=2026-03-27&date_to=2026-03-28')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertHeader(
                'content-disposition',
                'attachment; filename="ventas-productos-2026-03-27-a-2026-03-28.csv"',
            );

        $content = (string) $response->getContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Producto,SKU,Ventas,Cantidad,"Precio promedio",Total,"Ultima venta"', $content);
        $this->assertStringContainsString('"Cafe pasado",CAFE-01,2,3.00,11.67,35.00', $content);
    }

    private function seedBusinessData(): Customer
    {
        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        /** @var ProductCategory $category */
        $category = ProductCategory::query()->firstOrFail();
        /** @var CashRegister $register */
        $register = CashRegister::query()->firstOrFail();

        $customer = Customer::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Cliente Reporte',
            'document_type' => 'cedula',
            'document_number' => '0101010101',
            'is_active' => true,
        ]);

        $productMain = Product::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Cafe pasado',
            'sku' => 'CAFE-01',
            'category_id' => $category->id,
            'sale_price' => 10,
            'cost_price' => 4,
            'tax_rate' => 0,
            'unit' => 'taza',
            'track_stock' => true,
            'minimum_stock' => 1,
            'is_active' => true,
        ]);

        $productLowStock = Product::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Te verde',
            'sku' => 'TE-01',
            'category_id' => $category->id,
            'sale_price' => 3,
            'cost_price' => 1,
            'tax_rate' => 0,
            'unit' => 'taza',
            'track_stock' => true,
            'minimum_stock' => 5,
            'is_active' => true,
        ]);

        StockMovement::query()->create([
            'product_id' => $productMain->id,
            'user_id' => $admin->id,
            'type' => 'opening_balance',
            'reason' => 'seed',
            'quantity' => 10,
            'unit_cost' => $productMain->cost_price,
            'occurred_at' => Carbon::parse('2026-03-27 08:00:00'),
        ]);

        StockMovement::query()->create([
            'product_id' => $productLowStock->id,
            'user_id' => $admin->id,
            'type' => 'opening_balance',
            'reason' => 'seed',
            'quantity' => 3,
            'unit_cost' => $productLowStock->cost_price,
            'occurred_at' => Carbon::parse('2026-03-27 08:00:00'),
        ]);

        $openSession = CashSession::query()->create([
            'public_id' => (string) Str::uuid(),
            'cash_register_id' => $register->id,
            'opened_by_id' => $admin->id,
            'opened_at' => Carbon::parse('2026-03-28 09:00:00'),
            'opening_amount' => 50,
            'status' => 'open',
        ]);

        $closedSession = CashSession::query()->create([
            'public_id' => (string) Str::uuid(),
            'cash_register_id' => $register->id,
            'opened_by_id' => $admin->id,
            'closed_by_id' => $admin->id,
            'opened_at' => Carbon::parse('2026-03-27 09:00:00'),
            'closed_at' => Carbon::parse('2026-03-27 18:00:00'),
            'opening_amount' => 40,
            'closing_amount' => 55,
            'status' => 'closed',
        ]);

        $saleToday = Sale::query()->create([
            'public_id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'user_id' => $admin->id,
            'cash_session_id' => $openSession->id,
            'status' => 'completed',
            'document_type' => 'ticket',
            'subtotal' => 20,
            'tax_total' => 0,
            'discount_total' => 0,
            'grand_total' => 20,
            'paid_total' => 20,
            'change_total' => 0,
            'sold_at' => Carbon::parse('2026-03-28 10:00:00'),
        ]);

        $saleYesterday = Sale::query()->create([
            'public_id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'user_id' => $admin->id,
            'cash_session_id' => $closedSession->id,
            'status' => 'completed',
            'document_type' => 'ticket',
            'subtotal' => 15,
            'tax_total' => 0,
            'discount_total' => 0,
            'grand_total' => 15,
            'paid_total' => 5,
            'change_total' => 0,
            'sold_at' => Carbon::parse('2026-03-27 11:00:00'),
        ]);

        SaleItem::query()->create([
            'sale_id' => $saleToday->id,
            'product_id' => $productMain->id,
            'name_snapshot' => $productMain->name,
            'sku_snapshot' => $productMain->sku,
            'unit_price' => 10,
            'unit_cost' => 4,
            'quantity' => 2,
            'line_subtotal' => 20,
            'line_tax' => 0,
            'line_total' => 20,
        ]);

        SaleItem::query()->create([
            'sale_id' => $saleYesterday->id,
            'product_id' => $productMain->id,
            'name_snapshot' => $productMain->name,
            'sku_snapshot' => $productMain->sku,
            'unit_price' => 15,
            'unit_cost' => 4,
            'quantity' => 1,
            'line_subtotal' => 15,
            'line_tax' => 0,
            'line_total' => 15,
        ]);

        SalePayment::query()->create([
            'sale_id' => $saleToday->id,
            'method' => 'cash',
            'amount' => 20,
            'paid_at' => Carbon::parse('2026-03-28 10:00:00'),
        ]);

        SalePayment::query()->create([
            'sale_id' => $saleYesterday->id,
            'method' => 'card',
            'amount' => 5,
            'paid_at' => Carbon::parse('2026-03-27 11:00:00'),
        ]);

        CashMovement::query()->create([
            'public_id' => (string) Str::uuid(),
            'cash_session_id' => $openSession->id,
            'user_id' => $admin->id,
            'type' => 'income',
            'category' => 'sale',
            'amount' => 20,
            'reference_type' => 'sale',
            'reference_id' => $saleToday->id,
            'occurred_at' => Carbon::parse('2026-03-28 10:00:00'),
        ]);

        CashMovement::query()->create([
            'public_id' => (string) Str::uuid(),
            'cash_session_id' => $closedSession->id,
            'user_id' => $admin->id,
            'type' => 'expense',
            'category' => 'delivery',
            'amount' => 3,
            'reference_type' => 'manual_cash_movement',
            'reference_id' => null,
            'notes' => 'Pago de reparto',
            'occurred_at' => Carbon::parse('2026-03-27 14:00:00'),
        ]);

        CashMovement::query()->create([
            'public_id' => (string) Str::uuid(),
            'cash_session_id' => $openSession->id,
            'user_id' => $admin->id,
            'type' => 'expense',
            'category' => 'papeleria',
            'amount' => 2,
            'reference_type' => 'manual_cash_movement',
            'reference_id' => null,
            'notes' => 'Compra de rollos',
            'occurred_at' => Carbon::parse('2026-03-28 11:30:00'),
        ]);

        CashMovement::query()->create([
            'public_id' => (string) Str::uuid(),
            'cash_session_id' => $openSession->id,
            'user_id' => $admin->id,
            'type' => 'income',
            'category' => 'other_income',
            'amount' => 4,
            'reference_type' => 'manual_cash_movement',
            'reference_id' => null,
            'notes' => 'Recuperacion de gasto',
            'occurred_at' => Carbon::parse('2026-03-28 12:30:00'),
        ]);

        $this->createHistoricalSale($admin, $productMain, 6, 6, Carbon::parse('2025-10-15 10:00:00'));
        $this->createHistoricalSale($admin, $productMain, 8, 8, Carbon::parse('2026-01-12 10:00:00'));
        $this->createHistoricalSale($admin, $productMain, 12, 12, Carbon::parse('2026-02-18 10:00:00'));

        return $customer;
    }

    private function createHistoricalSale(
        User $admin,
        Product $product,
        float $unitPrice,
        float $grandTotal,
        Carbon $soldAt,
    ): void {
        $sale = Sale::query()->create([
            'public_id' => (string) Str::uuid(),
            'customer_id' => null,
            'user_id' => $admin->id,
            'cash_session_id' => null,
            'status' => 'completed',
            'document_type' => 'ticket',
            'subtotal' => $grandTotal,
            'tax_total' => 0,
            'discount_total' => 0,
            'grand_total' => $grandTotal,
            'paid_total' => $grandTotal,
            'change_total' => 0,
            'sold_at' => $soldAt,
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'unit_price' => $unitPrice,
            'unit_cost' => $product->cost_price,
            'quantity' => 1,
            'line_subtotal' => $grandTotal,
            'line_tax' => 0,
            'line_total' => $grandTotal,
        ]);

        SalePayment::query()->create([
            'sale_id' => $sale->id,
            'method' => 'cash',
            'amount' => $grandTotal,
            'paid_at' => $soldAt,
        ]);
    }
}
