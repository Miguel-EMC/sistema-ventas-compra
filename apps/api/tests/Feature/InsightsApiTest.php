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
            ->assertJsonPath('data.payment_methods.0.method', 'cash')
            ->assertJsonPath('data.payment_methods.0.total', 20)
            ->assertJsonPath('data.sales_by_day.0.day', '2026-03-27')
            ->assertJsonPath('data.sales_by_day.1.total', 20)
            ->assertJsonPath('data.top_products.0.name', 'Cafe pasado')
            ->assertJsonPath('data.cash_sessions.0.sales_total', 20);
    }

    private function seedBusinessData(): void
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
            'paid_total' => 15,
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
            'amount' => 15,
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
    }
}
